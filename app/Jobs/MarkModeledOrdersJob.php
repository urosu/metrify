<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\WorkspaceContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Populates orders.is_modeled and orders.attribution_modeled_signal_type for a workspace.
 *
 * Signal type classification (mutually exclusive, first match wins):
 *
 *  deterministic_click  — attribution_click_ids contains fbclid or gclid that can be
 *      matched to an ad_insights campaign for this workspace within a 7-day click window.
 *      Strongest signal; this is the "we have a real ad click record" case.
 *
 *  deterministic_utm    — no click_id match, but attribution_last_touch->>'source' matches
 *      a known paid channel via ChannelMapping (e.g. utm_source=facebook + utm_medium=paid_social).
 *      Still deterministic because we have explicit UTM parameters, just not a click ID.
 *
 *  modeled_pys          — attribution came from PixelYourSiteSource (raw_meta has pys_enrich_data).
 *      PYS data is third-party enrichment, not store-native attribution.
 *
 *  modeled_ga4          — attribution came from GA4Source (order has a row in ga4_order_attribution).
 *      GA4 data arrives independently of the store session data.
 *
 *  modeled_referrer     — attribution came from ReferrerHeuristicSource (source_type field only,
 *      no UTMs). Weakest deterministic signal; modeled because no marketing params present.
 *
 *  unattributed         — attribution_source IS NULL; no source resolved the order at all.
 *
 * Heuristic for source identification (applied in order above):
 *  - PYS:      raw_meta->'pys_enrich_data' IS NOT NULL
 *  - GA4:      row exists in ga4_order_attribution for this workspace + orders.external_id
 *  - Referrer: attribution_source IS NOT NULL AND attribution_click_ids IS NULL
 *              AND attribution_last_touch->>'source' IN ('direct','referral','google')
 *              AND raw_meta->'pys_enrich_data' IS NULL
 *              (ReferrerHeuristicSource stamps source_type-derived values, single-touch only)
 *
 * Queue:     low
 * Timeout:   1800 s
 * Tries:     3
 * Unique:    yes — one run per workspace at a time
 *
 * Dispatched by: schedule (nightly 03:20 UTC), RecomputeAttributionJob
 *
 * @see docs/planning/backend.md §3.3 (job spec)
 * @see docs/planning/schema.md §1.4 orders (is_modeled, attribution_modeled_signal_type)
 * @see app/Services/Attribution/Sources/PixelYourSiteSource.php
 * @see app/Services/Attribution/Sources/GA4Source.php
 * @see app/Services/Attribution/Sources/ReferrerHeuristicSource.php
 */
class MarkModeledOrdersJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 1800;
    public int $tries     = 3;
    public int $uniqueFor = 1860;

    private const CHUNK_SIZE = 500;

    /**
     * Click window used when matching click_ids against ad_insights.
     * 7 days is the standard Facebook / Google attribution window.
     */
    private const CLICK_WINDOW_DAYS = 7;

    /**
     * Paid-channel source values that indicate deterministic UTM attribution when
     * combined with a paid medium. These are the utm_source values written by
     * WooCommerceNativeSource and mapped via ChannelMappingResolver to paid_social / cpc.
     * Add 'tiktok', 'pinterest', etc. as new paid channels land.
     *
     * @see database/seeders/ChannelMappingsSeeder.php
     */
    private const PAID_SOURCES = ['facebook', 'instagram', 'google', 'googleads', 'bing', 'microsoft'];

    /** @var array<string, bool> Cached set of fbclids present in ad_insights for this workspace. */
    private array $adFbclids = [];

    /** @var array<string, bool> Cached set of gclids present in ad_insights for this workspace. */
    private array $adGclids = [];

    /** @var array<string, bool> Cached set of external_ids that have a ga4_order_attribution row. */
    private array $ga4TransactionIds = [];

    public function __construct(public readonly int $workspaceId)
    {
        $this->onQueue('low');
    }

    public function uniqueId(): string
    {
        return (string) $this->workspaceId;
    }

    public function handle(): void
    {
        app(WorkspaceContext::class)->set($this->workspaceId);

        Log::info('MarkModeledOrdersJob: starting', ['workspace_id' => $this->workspaceId]);

        // Pre-load click-ID sets and GA4 transaction IDs into memory for O(1) lookups.
        // ad_insights click IDs are stored on individual ad rows; we use the campaign-level
        // ad_external_id as a proxy: if any ad in the workspace has a matching click ID
        // within the click window, we consider it a deterministic_click match.
        // In practice the click_ids column on orders is compared against campaign external IDs
        // (fbclid/gclid values stored in raw_meta are ephemeral session identifiers, not
        // ad account IDs). We approximate: if the order has a fbclid/gclid AND the workspace
        // has active Facebook/Google campaigns on that date, classify as deterministic_click.
        $this->preloadAdCampaignDates();
        $this->preloadGa4TransactionIds();

        $processed = 0;

        DB::table('orders')
            ->where('workspace_id', $this->workspaceId)
            ->select([
                'id',
                'external_id',
                'attribution_source',
                'attribution_last_touch',
                'attribution_click_ids',
                'occurred_at',
                DB::raw("raw_meta->'pys_enrich_data' IS NOT NULL AS has_pys"),
            ])
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($orders) use (&$processed): void {
                $now  = now()->toDateTimeString();
                $rows = [];

                foreach ($orders as $order) {
                    [$isModeled, $signalType] = $this->classify($order);

                    $rows[] = [
                        'id'                              => $order->id,
                        'is_modeled'                      => $isModeled ? 'true' : 'false',
                        'attribution_modeled_signal_type' => $signalType,
                        'updated_at'                      => $now,
                    ];

                    $processed++;
                }

                $placeholders = implode(', ', array_fill(0, count($rows), '(?, ?::boolean, ?, ?::timestamp)'));
                $bindings = [];
                foreach ($rows as $row) {
                    $bindings[] = $row['id'];
                    $bindings[] = $row['is_modeled'];
                    $bindings[] = $row['attribution_modeled_signal_type'];
                    $bindings[] = $row['updated_at'];
                }

                DB::statement("
                    UPDATE orders AS o
                    SET is_modeled                      = v.is_modeled,
                        attribution_modeled_signal_type = v.signal_type,
                        updated_at                      = v.updated_at
                    FROM (VALUES {$placeholders}) AS v(id, is_modeled, signal_type, updated_at)
                    WHERE o.id = v.id::bigint
                      AND o.workspace_id = {$this->workspaceId}
                ", $bindings);
            });

        Log::info('MarkModeledOrdersJob: completed', [
            'workspace_id' => $this->workspaceId,
            'processed'    => $processed,
        ]);
    }

    /**
     * Classify a single order into (is_modeled, signal_type).
     *
     * @return array{bool, string|null}
     */
    private function classify(object $order): array
    {
        $attributionSource = $order->attribution_source;
        $clickIds          = $this->decodeJson($order->attribution_click_ids);
        $lastTouch         = $this->decodeJson($order->attribution_last_touch);
        $hasPys            = (bool) $order->has_pys;
        $occurredAt        = $order->occurred_at;

        // 1. Unattributed — no source resolved.
        if ($attributionSource === null) {
            return [true, 'unattributed'];
        }

        // 2. Deterministic click — has a click ID AND the workspace had active ads on that date.
        if ($clickIds !== null) {
            $fbclid = $clickIds['fbclid'] ?? null;
            $gclid  = $clickIds['gclid']  ?? null;

            if ($fbclid !== null && $this->hasActiveFacebookAds($occurredAt)) {
                return [false, 'deterministic_click'];
            }

            if ($gclid !== null && $this->hasActiveGoogleAds($occurredAt)) {
                return [false, 'deterministic_click'];
            }
        }

        // 3. Deterministic UTM — has explicit paid UTMs but no click ID match.
        if ($lastTouch !== null) {
            $source = strtolower((string) ($lastTouch['source'] ?? ''));
            $medium = strtolower((string) ($lastTouch['medium'] ?? ''));

            $isPaidSource = in_array($source, self::PAID_SOURCES, true);
            $isPaidMedium = in_array($medium, ['cpc', 'paid_social', 'paid', 'paidsocial', 'ppc'], true);

            if ($isPaidSource && $isPaidMedium) {
                return [false, 'deterministic_utm'];
            }
        }

        // 4. Modeled PYS — PixelYourSiteSource set the attribution.
        if ($hasPys) {
            return [true, 'modeled_pys'];
        }

        // 5. Modeled GA4 — GA4Source set the attribution (order has a ga4_order_attribution row).
        $externalId = (string) ($order->external_id ?? '');
        if ($externalId !== '' && isset($this->ga4TransactionIds[$externalId])) {
            return [true, 'modeled_ga4'];
        }

        // 6. Modeled referrer — ReferrerHeuristicSource: no UTMs, no click IDs, derived
        //    from WC source_type / referrer domain only. Single-touch and weakest signal.
        if ($attributionSource !== null && $clickIds === null) {
            return [true, 'modeled_referrer'];
        }

        // Fallback: deterministic UTM (organic / email channels with explicit UTM params).
        return [false, 'deterministic_utm'];
    }

    /**
     * Pre-load the date range of active ad campaigns per platform.
     *
     * We approximate click matching: if the workspace had Facebook/Google ads running
     * on the order date (within CLICK_WINDOW_DAYS look-back), a click ID on the order
     * is considered matched. This avoids a per-order join against ad_insights.
     *
     * Stores: $this->adFbclids = ['YYYY-MM-DD' => true, ...]
     *         $this->adGclids  = ['YYYY-MM-DD' => true, ...]
     */
    private function preloadAdCampaignDates(): void
    {
        // Dates with Facebook ad spend (level='campaign' to avoid double-counting).
        $fbDates = DB::table('ad_insights as ai')
            ->join('ad_accounts as aa', 'aa.id', '=', 'ai.ad_account_id')
            ->where('aa.workspace_id', $this->workspaceId)
            ->where('aa.platform', 'facebook')
            ->where('ai.level', 'campaign')
            ->where('ai.spend', '>', 0)
            ->select(DB::raw('DISTINCT ai.date::text AS dt'))
            ->pluck('dt')
            ->all();

        foreach ($fbDates as $dt) {
            $this->adFbclids[$dt] = true;
        }

        // Dates with Google ad spend.
        $gDates = DB::table('ad_insights as ai')
            ->join('ad_accounts as aa', 'aa.id', '=', 'ai.ad_account_id')
            ->where('aa.workspace_id', $this->workspaceId)
            ->whereIn('aa.platform', ['google', 'google_ads'])
            ->where('ai.level', 'campaign')
            ->where('ai.spend', '>', 0)
            ->select(DB::raw('DISTINCT ai.date::text AS dt'))
            ->pluck('dt')
            ->all();

        foreach ($gDates as $dt) {
            $this->adGclids[$dt] = true;
        }
    }

    /**
     * Pre-load GA4 transaction IDs for this workspace into a fast lookup set.
     */
    private function preloadGa4TransactionIds(): void
    {
        $ids = DB::table('ga4_order_attribution')
            ->where('workspace_id', $this->workspaceId)
            ->select('transaction_id')
            ->pluck('transaction_id')
            ->all();

        foreach ($ids as $id) {
            $this->ga4TransactionIds[(string) $id] = true;
        }
    }

    /**
     * True when the workspace had active Facebook ads within CLICK_WINDOW_DAYS of the order date.
     */
    private function hasActiveFacebookAds(?string $occurredAt): bool
    {
        if ($occurredAt === null) {
            return false;
        }

        $orderDate = substr($occurredAt, 0, 10); // YYYY-MM-DD

        for ($i = 0; $i <= self::CLICK_WINDOW_DAYS; $i++) {
            $date = date('Y-m-d', strtotime($orderDate . " -$i days"));
            if (isset($this->adFbclids[$date])) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when the workspace had active Google ads within CLICK_WINDOW_DAYS of the order date.
     */
    private function hasActiveGoogleAds(?string $occurredAt): bool
    {
        if ($occurredAt === null) {
            return false;
        }

        $orderDate = substr($occurredAt, 0, 10);

        for ($i = 0; $i <= self::CLICK_WINDOW_DAYS; $i++) {
            $date = date('Y-m-d', strtotime($orderDate . " -$i days"));
            if (isset($this->adGclids[$date])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Decode a JSONB column value to an array, or return null.
     *
     * @return array<string,mixed>|null
     */
    private function decodeJson(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
