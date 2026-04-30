<?php

declare(strict_types=1);

namespace App\Services\Attribution;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds a multi-touch attribution journey for a single order.
 *
 * Confidence tiers (applied in order, later tiers fill gaps):
 *   1. Shopify customer_journey_summary.moments — real multi-touch with timestamps
 *   2. GA4 ga4_order_attribution — first + last touch for non-Shopify / sparse orders
 *   3. WooCommerce PYS (raw_meta.pys_enrich_data) — first + last touch
 *   4. WooCommerce native (utm_* columns) — single touch
 *   5. Referrer heuristic (source_type) — single touch, weakest signal
 *
 * Each touch in the returned journey array has:
 *   source          string   — utm_source / inferred source
 *   medium          string   — utm_medium (optional)
 *   campaign        string   — utm_campaign (optional)
 *   channel         string   — channel_name from ChannelClassifierService (optional)
 *   channel_type    string   — channel_type from ChannelClassifierService (optional)
 *   landing_page    string   — landing page URL (optional)
 *   timestamp_at    string   — ISO 8601; inferred when absent (see below)
 *   fractional_credit float  — linear 1/N default; other models compute weights at query time
 *
 * Timestamp inference when moments lack real timestamps:
 *   first touch → occurred_at - 7 days (conservative prior)
 *   last touch  → occurred_at
 *   GA4 only gives first/last — same inference applies.
 *
 * Dedup: touches are deduped on (source, medium, landing_page). When Shopify
 * moments overlap with GA4 rows, Shopify wins (higher confidence); GA4 only
 * appends touches that Shopify doesn't already cover.
 *
 * Reads: orders.platform_data, orders.raw_meta, orders.utm_*, orders.source_type,
 *        ga4_order_attribution (by external_id = transaction_id)
 * Writes: nothing — caller writes orders.attribution_journey
 * Called by: BuildAttributionJourneyJob, BackfillAttributionJourneyCommand
 *
 * @see docs/planning/backend.md §7 (Attribution pipeline)
 * @see app/Jobs/BuildAttributionJourneyJob.php
 */
class AttributionJourneyBuilder
{
    /**
     * Conservative prior: when only first/last touch is known, assume the first
     * touch happened 7 days before the order.  No empirical basis; exists to give
     * time_decay model a non-zero gradient for two-touch orders.
     *
     * @see docs/planning/backend.md §7 — attribution journey builder
     */
    private const FIRST_TOUCH_PRIOR_DAYS = 7;

    public function __construct(
        private readonly ChannelClassifierService $classifier,
    ) {}

    /**
     * Build a full multi-touch journey for one order.
     *
     * Returns an array of touches ordered by timestamp_at ASC.
     * Returns [] when no signals are available.
     *
     * @return list<array{
     *   source: string,
     *   medium: string|null,
     *   campaign: string|null,
     *   channel: string|null,
     *   channel_type: string|null,
     *   landing_page: string|null,
     *   timestamp_at: string,
     *   fractional_credit: float
     * }>
     */
    public function buildForOrder(Order $order): array
    {
        $occurredAt = $order->occurred_at ?? Carbon::now();

        // ── Tier 1: Shopify customer_journey_summary.moments ────────────────────
        $touches = $this->buildFromShopifyMoments($order, $occurredAt);

        // ── Tier 2: GA4 — fill gaps or bootstrap when Shopify moments absent ───
        if (empty($touches) || count($touches) < 2) {
            $ga4Touches = $this->buildFromGA4($order, $occurredAt);
            $touches    = $this->mergeTouches($touches, $ga4Touches);
        }

        // ── Tier 3: PYS — for WC orders without Shopify/GA4 data ───────────────
        if (empty($touches)) {
            $touches = $this->buildFromPYS($order, $occurredAt);
        }

        // ── Tier 4: WC native UTMs ───────────────────────────────────────────────
        if (empty($touches)) {
            $touches = $this->buildFromWcNative($order, $occurredAt);
        }

        // ── Tier 5: Referrer heuristic ──────────────────────────────────────────
        if (empty($touches)) {
            $touches = $this->buildFromReferrerHeuristic($order, $occurredAt);
        }

        if (empty($touches)) {
            return [];
        }

        // Sort by timestamp ASC, then assign linear fractional_credit (1/N).
        usort($touches, static fn (array $a, array $b): int => strcmp($a['timestamp_at'], $b['timestamp_at']));

        $n      = count($touches);
        $credit = round(1.0 / $n, 10);

        foreach ($touches as &$touch) {
            $touch['fractional_credit'] = $credit;
        }
        unset($touch);

        return $touches;
    }

    // ─── Tier builders ────────────────────────────────────────────────────────

    /**
     * Build touches from Shopify's customer_journey_summary.moments array.
     *
     * Each moment contains: { occurredAt, utmParameters, referrerUrl, landingPage? }
     * This is the richest source — real timestamps and all intermediate touches.
     *
     * Falls back to firstVisit / lastVisit when moments array is empty or absent,
     * which happens for orders imported before Shopify shipped the moments field.
     *
     * @return list<array<string, mixed>>
     */
    private function buildFromShopifyMoments(Order $order, Carbon $occurredAt): array
    {
        $platformData = $order->platform_data;

        if (! is_array($platformData)) {
            return [];
        }

        $journey = $platformData['customer_journey_summary'] ?? null;

        if (! is_array($journey) || empty($journey)) {
            return [];
        }

        $touches = [];

        // Prefer the explicit moments array (multi-touch).
        $moments = $journey['moments'] ?? [];

        if (is_array($moments) && count($moments) > 0) {
            foreach ($moments as $moment) {
                if (! is_array($moment)) {
                    continue;
                }

                $touch = $this->parseMoment($moment, $order->workspace_id);

                if ($touch !== null) {
                    $touches[] = $touch;
                }
            }
        }

        // If moments is empty/absent, fall back to firstVisit + lastVisit only.
        if (empty($touches)) {
            $firstVisit = $journey['firstVisit'] ?? null;
            $lastVisit  = $journey['lastVisit']  ?? null;

            $firstTouch = $this->parseShopifyVisit($firstVisit, $order->workspace_id);
            $lastTouch  = $this->parseShopifyVisit($lastVisit,  $order->workspace_id);

            if ($firstTouch !== null) {
                // Assign synthetic timestamps when absent — first is 7 days prior.
                if (! isset($firstTouch['timestamp_at'])) {
                    $firstTouch['timestamp_at'] = $occurredAt->copy()
                        ->subDays(self::FIRST_TOUCH_PRIOR_DAYS)
                        ->toIso8601String();
                }
                $touches[] = $firstTouch;
            }

            if ($lastTouch !== null) {
                if (! isset($lastTouch['timestamp_at'])) {
                    $lastTouch['timestamp_at'] = $occurredAt->toIso8601String();
                }

                // Dedup: skip if identical to first touch.
                if (! $this->isSameTouchAs($lastTouch, $firstTouch)) {
                    $touches[] = $lastTouch;
                }
            }
        }

        return $touches;
    }

    /**
     * Build first + last touches from ga4_order_attribution.
     *
     * Timestamps are inferred (first = -7d, last = occurredAt) because GA4
     * aggregated attribution doesn't ship exact event timestamps.
     *
     * @return list<array<string, mixed>>
     */
    private function buildFromGA4(Order $order, Carbon $occurredAt): array
    {
        $transactionId = $order->external_id ?? null;

        if ($transactionId === null || $transactionId === '') {
            return [];
        }

        $row = DB::table('ga4_order_attribution')
            ->where('workspace_id', $order->workspace_id)
            ->where('transaction_id', (string) $transactionId)
            ->first();

        if ($row === null) {
            return [];
        }

        $touches = [];

        // First touch: firstUser* dimensions.
        $firstSource = ! empty($row->first_user_source) ? (string) $row->first_user_source : null;
        if ($firstSource !== null) {
            $touch = $this->makeTouchFromParts(
                source:      $firstSource,
                medium:      ! empty($row->first_user_medium)   ? (string) $row->first_user_medium   : null,
                campaign:    ! empty($row->first_user_campaign) ? (string) $row->first_user_campaign : null,
                landingPage: ! empty($row->landing_page)        ? (string) $row->landing_page        : null,
                workspaceId: $order->workspace_id,
                timestampAt: $occurredAt->copy()->subDays(self::FIRST_TOUCH_PRIOR_DAYS)->toIso8601String(),
            );
            $touches[] = $touch;
        }

        // Last touch: session* dimensions.
        $lastSource = ! empty($row->session_source) ? (string) $row->session_source : null;
        if ($lastSource !== null) {
            $touch = $this->makeTouchFromParts(
                source:      $lastSource,
                medium:      ! empty($row->session_medium)   ? (string) $row->session_medium   : null,
                campaign:    ! empty($row->session_campaign) ? (string) $row->session_campaign : null,
                landingPage: ! empty($row->landing_page)     ? (string) $row->landing_page     : null,
                workspaceId: $order->workspace_id,
                timestampAt: $occurredAt->toIso8601String(),
            );

            // Dedup: only add if different from first touch.
            if (empty($touches) || ! $this->isSameTouchAs($touch, $touches[0])) {
                $touches[] = $touch;
            }
        }

        return $touches;
    }

    /**
     * Build first + last touches from PixelYourSite (raw_meta.pys_enrich_data).
     *
     * PYS only records first and last session — no intermediate touches.
     *
     * @return list<array<string, mixed>>
     */
    private function buildFromPYS(Order $order, Carbon $occurredAt): array
    {
        $rawMeta = $order->raw_meta;

        if (! is_array($rawMeta) || ! isset($rawMeta['pys_enrich_data'])) {
            return [];
        }

        $pys = $rawMeta['pys_enrich_data'];

        if (! is_array($pys)) {
            return [];
        }

        $firstTouch = $this->parsePysUtmString(
            $pys['pys_utm']     ?? null,
            $pys['pys_source']  ?? null,
            $pys['pys_landing'] ?? null,
            $order->workspace_id,
            $occurredAt->copy()->subDays(self::FIRST_TOUCH_PRIOR_DAYS)->toIso8601String(),
        );

        $lastTouch = $this->parsePysUtmString(
            $pys['last_pys_utm']     ?? null,
            $pys['last_pys_source']  ?? null,
            $pys['last_pys_landing'] ?? null,
            $order->workspace_id,
            $occurredAt->toIso8601String(),
        );

        $touches = [];

        if ($firstTouch !== null) {
            $touches[] = $firstTouch;
        }

        if ($lastTouch !== null && ! $this->isSameTouchAs($lastTouch, $firstTouch)) {
            $touches[] = $lastTouch;
        }

        return $touches;
    }

    /**
     * Single-touch from WooCommerce native utm_* columns.
     *
     * WC native only records the most recent session — first === last.
     *
     * @return list<array<string, mixed>>
     */
    private function buildFromWcNative(Order $order, Carbon $occurredAt): array
    {
        $source = $order->utm_source;

        if ($source === null || $source === '') {
            return [];
        }

        return [$this->makeTouchFromParts(
            source:      $source,
            medium:      $order->utm_medium ?: null,
            campaign:    $order->utm_campaign ?: null,
            landingPage: null,
            workspaceId: $order->workspace_id,
            timestampAt: $occurredAt->toIso8601String(),
        )];
    }

    /**
     * Single-touch from the referrer heuristic (source_type column).
     *
     * Weakest signal — only fires when all richer tiers are absent.
     *
     * @return list<array<string, mixed>>
     */
    private function buildFromReferrerHeuristic(Order $order, Carbon $occurredAt): array
    {
        $sourceType = $order->source_type;

        if ($sourceType === null || $sourceType === '') {
            return [];
        }

        [$source, $medium] = match ($sourceType) {
            'direct', 'typein' => ['direct',   null],
            'organic_search'   => ['google',   'organic'],
            'referral', 'link' => ['referral', 'referral'],
            default            => [null, null],
        };

        if ($source === null) {
            return [];
        }

        return [$this->makeTouchFromParts(
            source:      $source,
            medium:      $medium,
            campaign:    null,
            landingPage: null,
            workspaceId: $order->workspace_id,
            timestampAt: $occurredAt->toIso8601String(),
        )];
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Parse a single Shopify moments entry.
     *
     * Moment shape:
     * {
     *   occurredAt: "2024-03-15T10:23:00Z"   (ISO 8601; may be absent on old data)
     *   utmParameters: { source, medium, campaign, content, term }
     *   referrerUrl: "https://..."
     *   landingPage: "https://..."            (not always present)
     * }
     *
     * @param  array<string, mixed> $moment
     * @return array<string, mixed>|null
     */
    private function parseMoment(array $moment, int $workspaceId): ?array
    {
        $utm    = $moment['utmParameters'] ?? null;
        $source = $this->clean(is_array($utm) ? ($utm['source'] ?? null) : null);

        // Fall back to referrerUrl host when UTM source is absent.
        if ($source === null) {
            $referrer = $this->clean($moment['referrerUrl'] ?? null);
            if ($referrer !== null) {
                $host = parse_url($referrer, PHP_URL_HOST) ?? $referrer;
                // Strip leading www.
                $source = preg_replace('/^www\./i', '', $host) ?: $host;
            }
        }

        if ($source === null) {
            return null;
        }

        $medium   = is_array($utm) ? $this->clean($utm['medium']   ?? null) : null;
        $campaign = is_array($utm) ? $this->clean($utm['campaign'] ?? null) : null;

        $landingPage = $this->clean($moment['landingPage'] ?? null)
            ?? $this->clean($moment['referrerUrl'] ?? null);

        // Parse timestamp; null is handled by callers that assign synthetic timestamps.
        $timestampAt = null;
        $rawTs = $moment['occurredAt'] ?? null;
        if ($rawTs !== null && $rawTs !== '') {
            try {
                $timestampAt = Carbon::parse((string) $rawTs)->toIso8601String();
            } catch (\Throwable) {
                $timestampAt = null;
            }
        }

        $touch = $this->makeTouchFromParts(
            source:      $source,
            medium:      $medium,
            campaign:    $campaign,
            landingPage: $landingPage,
            workspaceId: $workspaceId,
            timestampAt: $timestampAt ?? '',  // caller must fill real timestamp
        );

        // If we couldn't parse a real timestamp, mark it as needing inference.
        // BuildFromShopifyMoments assigns synthetic timestamps at the end.
        if ($timestampAt === null) {
            $touch['_needs_timestamp'] = true;
        }

        return $touch;
    }

    /**
     * Parse a Shopify firstVisit / lastVisit node (non-moments shape).
     *
     * Visit shape: { utmParameters, landingPage, referrerUrl }
     * No timestamp — caller assigns synthetic timestamp.
     *
     * @param  array<string, mixed>|null $visit
     * @return array<string, mixed>|null
     */
    private function parseShopifyVisit(?array $visit, int $workspaceId): ?array
    {
        if (! is_array($visit)) {
            return null;
        }

        $utm    = $visit['utmParameters'] ?? null;
        $source = is_array($utm) ? $this->clean($utm['source'] ?? null) : null;

        if ($source === null) {
            return null;
        }

        return $this->makeTouchFromParts(
            source:      $source,
            medium:      is_array($utm) ? $this->clean($utm['medium']   ?? null) : null,
            campaign:    is_array($utm) ? $this->clean($utm['campaign'] ?? null) : null,
            landingPage: $this->clean($visit['landingPage'] ?? null),
            workspaceId: $workspaceId,
            timestampAt: '',  // caller assigns
        );
    }

    /**
     * Parse a PYS pipe-delimited UTM string and supplementary fields into a touch.
     *
     * PYS UTM format: "utm_source:Klaviyo|utm_medium:email|utm_campaign:SPRING25"
     *
     * @return array<string, mixed>|null
     */
    private function parsePysUtmString(
        ?string $utmString,
        ?string $fallbackSource,
        ?string $landingPage,
        int $workspaceId,
        string $timestampAt,
    ): ?array {
        $utm = [];

        if ($utmString !== null && $utmString !== '') {
            foreach (explode('|', $utmString) as $pair) {
                $pos = strpos($pair, ':');
                if ($pos === false) {
                    continue;
                }
                $key   = trim(substr($pair, 0, $pos));
                $value = $this->normalisePys(substr($pair, $pos + 1));
                if ($key !== '' && $value !== null) {
                    $utm[$key] = $value;
                }
            }
        }

        $source = $utm['utm_source'] ?? $this->normalisePys($fallbackSource);

        if ($source === null) {
            return null;
        }

        return $this->makeTouchFromParts(
            source:      $source,
            medium:      $utm['utm_medium']   ?? null,
            campaign:    $utm['utm_campaign'] ?? null,
            landingPage: $this->normalisePys($landingPage),
            workspaceId: $workspaceId,
            timestampAt: $timestampAt,
        );
    }

    /**
     * Construct a canonical touch array and run channel classification.
     *
     * @return array<string, mixed>
     */
    private function makeTouchFromParts(
        string  $source,
        ?string $medium,
        ?string $campaign,
        ?string $landingPage,
        int     $workspaceId,
        string  $timestampAt,
    ): array {
        $channel = $this->classifier->classify($source, $medium, $workspaceId);

        $touch = [
            'source'           => $source,
            'medium'           => $medium,
            'campaign'         => $campaign,
            'channel'          => $channel['channel_name'],
            'channel_type'     => $channel['channel_type'],
            'landing_page'     => $landingPage,
            'timestamp_at'     => $timestampAt,
            'fractional_credit' => 0.0,  // set after all touches are collected
        ];

        // Strip null fields for compact JSONB storage.
        return array_filter($touch, static fn ($v): bool => $v !== null)
            + ['source' => $source, 'timestamp_at' => $timestampAt, 'fractional_credit' => 0.0];
    }

    /**
     * Merge base touches with supplementary touches, deduplicating on (source, medium, landing_page).
     *
     * Base touches (higher confidence) win. Supplementary touches that don't overlap
     * are appended, giving more signal for multi-model weighting.
     *
     * @param  list<array<string, mixed>> $base
     * @param  list<array<string, mixed>> $supplementary
     * @return list<array<string, mixed>>
     */
    private function mergeTouches(array $base, array $supplementary): array
    {
        if (empty($supplementary)) {
            return $base;
        }

        if (empty($base)) {
            return $supplementary;
        }

        foreach ($supplementary as $candidate) {
            $duplicate = false;

            foreach ($base as $existing) {
                if ($this->isSameTouchAs($candidate, $existing)) {
                    $duplicate = true;
                    break;
                }
            }

            if (! $duplicate) {
                $base[] = $candidate;
            }
        }

        return $base;
    }

    /**
     * Dedup check: two touches are "the same" when source, medium, and landing_page match.
     *
     * Campaign is intentionally excluded — same channel, different campaign copy
     * counts as the same touch source for dedup purposes.
     *
     * @param  array<string, mixed>|null $a
     * @param  array<string, mixed>|null $b
     */
    private function isSameTouchAs(?array $a, ?array $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        return strtolower($a['source']       ?? '')  === strtolower($b['source']       ?? '')
            && strtolower($a['medium']        ?? '')  === strtolower($b['medium']        ?? '')
            && strtolower($a['landing_page']  ?? '')  === strtolower($b['landing_page']  ?? '');
    }

    /**
     * Trim and treat empty / "undefined" PYS literals as null.
     */
    private function normalisePys(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'undefined') {
            return null;
        }
        $str = trim((string) $value);
        return $str === '' || $str === 'undefined' ? null : $str;
    }

    /**
     * Trim and return null for empty strings.
     */
    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);
        return $str === '' ? null : $str;
    }
}
