<?php

declare(strict_types=1);

namespace App\Services\Reconciliation;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reconciles store-attributed revenue against ad-platform conversion_value claims.
 *
 * For each (workspace, store, date, channel) cell the service computes:
 *   store_claim    = SUM orders.total_in_reporting_currency WHERE attribution_source = channel
 *   platform_claim = SUM ad_insights.platform_conversions_value_in_reporting_currency
 *                    WHERE ad_account.platform = channel AND level = 'campaign'
 *   real_revenue   = store_claim  (v1 — store is ground truth)
 *   delta_abs      = platform_claim - store_claim
 *   delta_pct      = delta_abs / NULLIF(store_claim, 0) * 100
 *
 * Channels without a platform counterpart (gsc, direct, organic, email) produce a
 * row with platform_claim = NULL.
 *
 * Called by: App\Jobs\ReconcileSourceDisagreementsJob (nightly 03:30 UTC)
 * Called by: App\Services\Attribution\AttributionDataService::disagreementMatrix()
 * Reads:     orders, ad_insights, ad_accounts, stores
 * Writes:    daily_source_disagreements (upsert)
 *
 * @see docs/planning/backend.md §WS-A2c
 * @see docs/UX.md §7 (trust thesis / source badges)
 */
final class SourceReconciliationService
{
    /**
     * Channels that have an ad-platform counterpart in ad_insights.
     * platform value matches ad_accounts.platform CHECK constraint.
     */
    private const AD_CHANNELS = ['facebook', 'google'];

    /**
     * Channels without a platform counterpart — store-only attribution.
     */
    private const STORE_ONLY_CHANNELS = ['gsc', 'direct', 'organic', 'email'];

    /**
     * Build/upsert daily_source_disagreements rows for one workspace and date range.
     *
     * Iterates every date in [from, to] and every channel. For facebook/google the
     * platform_claim is resolved by joining ad_accounts to get the platform, then
     * summing campaign-level daily insights. Store-only channels get null platform_claim.
     *
     * The upsert is keyed on (workspace_id, store_id, date, channel).
     */
    public function reconcile(int $workspaceId, string $from, string $to): void
    {
        $syncedAt = now();

        // ── Step 1: per-store, per-date, per-channel store-attributed revenue ──────────
        // Group by (store_id, date, attribution_source) over the order table.
        // attribution_source values in the DB are lowercase and match channel names.
        $storeClaims = DB::select("
            SELECT
                store_id,
                DATE(occurred_at) AS date,
                LOWER(COALESCE(attribution_source, 'direct')) AS channel,
                SUM(COALESCE(total_in_reporting_currency, total)) AS store_claim
            FROM orders
            WHERE workspace_id = :workspace_id
              AND DATE(occurred_at) BETWEEN :from AND :to
              AND status NOT IN ('cancelled', 'refunded', 'failed', 'trash')
            GROUP BY store_id, DATE(occurred_at), channel
        ", [
            'workspace_id' => $workspaceId,
            'from'         => $from,
            'to'           => $to,
        ]);

        // Index as [storeId][date][channel] => claim for fast lookup.
        $storeClaimMap = [];
        foreach ($storeClaims as $row) {
            $storeClaimMap[$row->store_id][$row->date][$row->channel] = (float) $row->store_claim;
        }

        // ── Step 2: per-platform, per-date platform conversion_value ─────────────────
        // ad_insights has no store_id column; all ad accounts for a workspace are
        // workspace-level. We aggregate at (platform, date) and distribute the claim
        // across stores proportional to each store's share of attributed revenue.
        // For workspaces with one store (the common case) this is a simple sum.
        //
        // Level is forced to 'campaign' — CLAUDE.md gotcha: never SUM across levels.
        $platformClaims = DB::select("
            SELECT
                aa.platform,
                ai.date::text AS date,
                SUM(COALESCE(ai.platform_conversions_value_in_reporting_currency,
                             ai.platform_conversions_value, 0)) AS platform_claim
            FROM ad_insights ai
            JOIN ad_accounts aa ON aa.id = ai.ad_account_id
            WHERE ai.workspace_id = :workspace_id
              AND ai.level = 'campaign'
              AND ai.hour IS NULL
              AND ai.date BETWEEN :from AND :to
            GROUP BY aa.platform, ai.date
        ", [
            'workspace_id' => $workspaceId,
            'from'         => $from,
            'to'           => $to,
        ]);

        // Index as [platform][date] => claim.
        $platformClaimMap = [];
        foreach ($platformClaims as $row) {
            $platformClaimMap[$row->platform][$row->date] = (float) $row->platform_claim;
        }

        // ── Step 3: collect all stores for this workspace ─────────────────────────────
        $stores = DB::table('stores')
            ->where('workspace_id', $workspaceId)
            ->where('status', 'active')
            ->select(['id'])
            ->pluck('id')
            ->all();

        if (empty($stores)) {
            return;
        }

        // ── Step 4: upsert one row per (store, date, channel) ─────────────────────────
        $dates   = $this->dateRange($from, $to);
        $rows    = [];
        $channels = array_merge(self::AD_CHANNELS, self::STORE_ONLY_CHANNELS);

        foreach ($stores as $storeId) {
            // For multi-store workspaces, split the platform claim proportionally.
            // storeShareRatio: this store's attributed revenue / total workspace attributed revenue.
            // Pre-computed once per platform per date to avoid repeated queries.
            $storeShareCache = [];

            foreach ($dates as $date) {
                foreach ($channels as $channel) {
                    $storeClaim = $storeClaimMap[$storeId][$date][$channel] ?? 0.0;

                    $platformClaim = null;

                    if (in_array($channel, self::AD_CHANNELS, true)) {
                        $rawPlatformClaim = $platformClaimMap[$channel][$date] ?? null;

                        if ($rawPlatformClaim !== null) {
                            // Distribute platform claim across stores by store's attributed share.
                            $shareKey = "{$channel}:{$date}";
                            if (! isset($storeShareCache[$shareKey])) {
                                $storeShareCache[$shareKey] = $this->computeStoreShares(
                                    $storeClaimMap,
                                    $stores,
                                    $date,
                                    $channel,
                                );
                            }

                            $ratio = $storeShareCache[$shareKey][$storeId] ?? (count($stores) === 1 ? 1.0 : 0.0);
                            $platformClaim = round($rawPlatformClaim * $ratio, 2);
                        }
                    }

                    // Skip rows where both store_claim = 0 and platform_claim = null.
                    // These are empty cells — no data for this channel on this date.
                    if ($storeClaim === 0.0 && $platformClaim === null) {
                        continue;
                    }

                    // v1 reconciliation rule: real_revenue anchors on store_claim.
                    $realRevenue = $storeClaim;

                    $deltaAbs = null;
                    $deltaPct = null;

                    if ($platformClaim !== null) {
                        $deltaAbs = round($platformClaim - $storeClaim, 2);
                        $deltaPct = $storeClaim !== 0.0
                            ? round($deltaAbs / $storeClaim * 100, 2)
                            : null;
                    }

                    $rows[] = [
                        'workspace_id'   => $workspaceId,
                        'store_id'       => $storeId,
                        'date'           => $date,
                        'channel'        => $channel,
                        'store_claim'    => $storeClaim,
                        'platform_claim' => $platformClaim,
                        'real_revenue'   => $realRevenue,
                        'delta_abs'      => $deltaAbs,
                        'delta_pct'      => $deltaPct,
                        'match_confidence' => null,
                        'synced_at'      => $syncedAt,
                        'created_at'     => $syncedAt,
                        'updated_at'     => $syncedAt,
                    ];
                }
            }
        }

        if (empty($rows)) {
            return;
        }

        // Upsert in batches of 500 to avoid large single-statement overhead.
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('daily_source_disagreements')->upsert(
                $chunk,
                ['workspace_id', 'store_id', 'date', 'channel'],
                ['store_claim', 'platform_claim', 'real_revenue', 'delta_abs', 'delta_pct', 'match_confidence', 'synced_at', 'updated_at'],
            );
        }
    }

    /**
     * Read the current disagreement state for the Attribution page Source Disagreement Matrix.
     *
     * Returns one row per channel with:
     *   channel, store_claim, platform_claim (nullable), real_revenue, delta_abs, delta_pct.
     *
     * Aggregated across all stores in the workspace for the date range.
     * Rows are ordered by store_claim DESC.
     *
     * @return list<array<string, mixed>> Matches Props['disagreement_matrix']
     */
    public function disagreementMatrix(int $workspaceId, string $from, string $to): array
    {
        $rows = DB::select("
            SELECT
                channel,
                SUM(store_claim)                              AS store_claim,
                SUM(platform_claim)                           AS platform_claim,
                SUM(real_revenue)                             AS real_revenue,
                SUM(delta_abs)                                AS delta_abs,
                CASE
                    WHEN COUNT(platform_claim) = 0 THEN NULL
                    WHEN SUM(store_claim) = 0 THEN NULL
                    ELSE ROUND(
                        (SUM(COALESCE(platform_claim, 0)) - SUM(store_claim))
                        / NULLIF(SUM(store_claim), 0) * 100,
                        2
                    )
                END                                           AS delta_pct
            FROM daily_source_disagreements
            WHERE workspace_id = :workspace_id
              AND date BETWEEN :from AND :to
            GROUP BY channel
            ORDER BY SUM(store_claim) DESC
        ", [
            'workspace_id' => $workspaceId,
            'from'         => $from,
            'to'           => $to,
        ]);

        return array_map(fn (object $row): array => [
            'channel'        => $row->channel,
            'store_claim'    => round((float) $row->store_claim, 2),
            'platform_claim' => $row->platform_claim !== null ? round((float) $row->platform_claim, 2) : null,
            'real_revenue'   => round((float) $row->real_revenue, 2),
            'delta_abs'      => $row->delta_abs !== null ? round((float) $row->delta_abs, 2) : null,
            'delta_pct'      => $row->delta_pct !== null ? round((float) $row->delta_pct, 2) : null,
        ], $rows);
    }

    // ─── private helpers ──────────────────────────────────────────────────────

    /**
     * Compute each store's proportional share of total attributed revenue for a
     * given (channel, date) so we can distribute the workspace-level platform claim.
     *
     * Returns [storeId => ratio] where ratio sums to 1.0 across all stores.
     * If total attributed = 0 the claim is split equally across stores.
     *
     * @param  array<int, array<string, array<string, float>>>  $storeClaimMap
     * @param  int[]  $stores
     * @return array<int, float>
     */
    private function computeStoreShares(
        array $storeClaimMap,
        array $stores,
        string $date,
        string $channel,
    ): array {
        $totals = [];
        $sum = 0.0;

        foreach ($stores as $storeId) {
            $v = $storeClaimMap[$storeId][$date][$channel] ?? 0.0;
            $totals[$storeId] = $v;
            $sum += $v;
        }

        if ($sum === 0.0) {
            // No attributed orders — distribute equally so the platform claim is not lost.
            $equal = count($stores) > 0 ? 1.0 / count($stores) : 0.0;
            return array_fill_keys($stores, $equal);
        }

        return array_map(fn (float $v): float => $v / $sum, $totals);
    }

    /**
     * Enumerate all calendar dates in [from, to] inclusive.
     *
     * @return list<string>
     */
    private function dateRange(string $from, string $to): array
    {
        $dates  = [];
        $cursor = Carbon::parse($from);
        $end    = Carbon::parse($to);

        while ($cursor->lte($end)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $dates;
    }
}
