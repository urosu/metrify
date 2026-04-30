<?php

declare(strict_types=1);

namespace App\Services\Attribution;

use App\Models\DailySnapshot;
use App\Models\Order;
use App\Services\Metrics\MetricSourceResolver;
use App\Services\Reconciliation\SourceReconciliationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds the Attribution/Index page payload.
 *
 * One public method per top-level prop in resources/js/Pages/Attribution/Index.tsx.
 * Period aggregates use daily_snapshots; journey_orders reads raw orders (today-safe,
 * also scoped to the same date window which is fine for per-row reads).
 *
 * Called by: App\Http\Controllers\AttributionController
 * Reads:     daily_snapshots, orders
 * Writes:    nothing (pure read service)
 *
 * @see docs/pages/attribution.md
 * @see docs/planning/backend.md §7
 * @see docs/UX.md §7 (trust thesis / source badges)
 */
final class AttributionDataService
{
    private const JOURNEY_LIMIT = 50;

    public function __construct(
        private readonly MetricSourceResolver $sourceResolver,
    ) {}

    /**
     * Top KPIs: real revenue (with prev-period delta), attributed revenue by
     * platform, not-tracked bucket, and the raw attribution gap value.
     *
     * `real_revenue.store` and platform fields expose the disagreement directly —
     * consistent with the thesis that each source can disagree.
     *
     * @param string $source  Active source lens — 'real' (default) | 'store' |
     *                        'facebook' | 'google' | 'gsc' | 'ga4'
     * @return array<string, mixed> Matches Props['metrics'] in Attribution/Index.tsx
     */
    public function metrics(int $workspaceId, string $from, string $to, string $source = 'real'): array
    {
        [$prevFrom, $prevTo] = $this->previousRange($from, $to);

        $revenueColumn = $this->sourceResolver->columnFor('revenue', $source);

        $curr = $this->aggregateDaily($workspaceId, $from, $to);
        $prev = $this->aggregateDaily($workspaceId, $prevFrom, $prevTo);

        $store    = (float) $curr->store;
        $real     = (float) $curr->real;
        $fb       = (float) $curr->facebook;
        $google   = (float) $curr->google;
        $realPrev = (float) $prev->real;

        // Source-lens revenue: when not 'real', fetch the specific column.
        if ($source !== 'real') {
            $lensRow     = $this->aggregateDailyColumn($workspaceId, $from, $to, $revenueColumn);
            $lensRowPrev = $this->aggregateDailyColumn($workspaceId, $prevFrom, $prevTo, $revenueColumn);
            $lensRevenue     = (float) ($lensRow->v ?? 0);
            $lensRevenuePrev = (float) ($lensRowPrev->v ?? 0);
        } else {
            $lensRevenue     = $real;
            $lensRevenuePrev = $realPrev;
        }

        // Not tracked = store - real (can be negative per thesis).
        $notTracked = round($store - $real, 2);

        // Attribution gap = (fb + google) - real (platforms overclaim relative to real).
        $attributionGap = round(($fb + $google) - $real, 2);

        return [
            'real_revenue' => [
                'value'    => round($lensRevenue, 2),
                'prev'     => $lensRevenuePrev > 0 ? round($lensRevenuePrev, 2) : null,
                'store'    => round($store, 2),
                'facebook' => $fb > 0 ? round($fb, 2) : null,
                'google'   => $google > 0 ? round($google, 2) : null,
            ],
            'attributed_revenue' => [
                'facebook' => $fb > 0 ? round($fb, 2) : null,
                'google'   => $google > 0 ? round($google, 2) : null,
            ],
            'not_tracked'     => $notTracked,
            'attribution_gap' => $attributionGap,
        ];
    }

    /**
     * Six-source totals + order counts for the TrustBar.
     *
     * `not_tracked` and `orders_real` are derived — no extra DB round-trip.
     * The source lens does not change the TrustBar totals — it always shows all
     * six sources so the user can see the full disagreement picture regardless of
     * which lens is active.
     *
     * @param string $source  Active lens (unused here — TrustBar is always full-spectrum)
     * @return array<string, float|int|null> Matches Props['trust_bar']
     */
    public function trustBar(int $workspaceId, string $from, string $to, string $source = 'real'): array
    {
        $row = DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('
                COALESCE(SUM(revenue), 0)                                          AS store,
                COALESCE(SUM(revenue_facebook_attributed), 0)                     AS facebook,
                COALESCE(SUM(revenue_google_attributed), 0)                        AS google,
                COALESCE(SUM(revenue_gsc_attributed), 0)                           AS gsc,
                COALESCE(SUM(revenue_direct_attributed), 0)
                    + COALESCE(SUM(revenue_organic_attributed), 0)
                    + COALESCE(SUM(revenue_email_attributed), 0)                   AS site,
                COALESCE(SUM(revenue_real_attributed), 0)                          AS real,
                COALESCE(SUM(orders_count), 0)                                     AS orders_store
            ')
            ->first();

        $store      = round((float) $row->store, 2);
        $real       = round((float) $row->real, 2);
        $facebook   = round((float) $row->facebook, 2);
        $google     = round((float) $row->google, 2);
        $gsc        = round((float) $row->gsc, 2);
        $site       = round((float) $row->site, 2);
        $ordersStore= (int) $row->orders_store;

        return [
            'store'       => $store,
            'facebook'    => $facebook > 0 ? $facebook : null,
            'google'      => $google   > 0 ? $google   : null,
            'gsc'         => $gsc      > 0 ? $gsc      : null,
            'site'        => $site     > 0 ? $site     : null,
            'real'        => $real,
            'not_tracked' => round($store - $real, 2),
            'orders_store'=> $ordersStore,
            'orders_real' => $ordersStore, // proxy until per-source order counts land in snapshots
        ];
    }

    /**
     * Per-channel revenue from each source for the Source Disagreement Matrix.
     *
     * Delegates to SourceReconciliationService which reads from daily_source_disagreements
     * (written nightly by ReconcileSourceDisagreementsJob). Each row carries:
     *   channel, store_claim, platform_claim (nullable), real_revenue, delta_abs, delta_pct
     *
     * Falls back to a snapshot-derived summary when the reconciliation table has no
     * rows for the requested window (e.g., the night job hasn't run yet on a fresh install).
     *
     * The source lens does not filter the matrix columns — the matrix always shows
     * all sources to preserve the disagreement picture. The active lens is passed
     * through for future use (e.g. highlighting the active column).
     *
     * @param string $source  Active lens (reserved for future column highlight)
     * @return list<array<string, mixed>> Matches Props['disagreement_matrix']
     */
    public function disagreementMatrix(int $workspaceId, string $from, string $to, string $source = 'real'): array
    {
        $rawRows = app(SourceReconciliationService::class)
            ->disagreementMatrix($workspaceId, $from, $to);

        if (! empty($rawRows)) {
            return $this->buildMatrixShape($rawRows);
        }

        // ── Fallback: derive from daily_snapshots when reconciliation data is absent ──
        // Preserves the pre-WS-A2c behavior so the matrix never shows empty on first load.
        $row = $this->aggregateDaily($workspaceId, $from, $to);

        $fb     = round((float) $row->facebook, 2);
        $google = round((float) $row->google, 2);
        $gsc    = round((float) $row->gsc, 2);
        $site   = round((float) $row->site, 2);

        $fallbackRaw = [];

        if ($fb > 0) {
            $fallbackRaw[] = [
                'channel'        => 'facebook',
                'store_claim'    => $fb,
                'platform_claim' => null,
                'real_revenue'   => $fb,
                'delta_abs'      => null,
                'delta_pct'      => null,
            ];
        }

        if ($google > 0) {
            $fallbackRaw[] = [
                'channel'        => 'google',
                'store_claim'    => $google,
                'platform_claim' => null,
                'real_revenue'   => $google,
                'delta_abs'      => null,
                'delta_pct'      => null,
            ];
        }

        if ($gsc > 0) {
            $fallbackRaw[] = [
                'channel'        => 'gsc',
                'store_claim'    => $gsc,
                'platform_claim' => null,
                'real_revenue'   => $gsc,
                'delta_abs'      => null,
                'delta_pct'      => null,
            ];
        }

        // direct + organic + email are folded into one "site" bucket in daily_snapshots;
        // surface them as "direct" until the reconciliation table is warm with granular rows.
        if ($site > 0) {
            $fallbackRaw[] = [
                'channel'        => 'direct',
                'store_claim'    => $site,
                'platform_claim' => null,
                'real_revenue'   => $site,
                'delta_abs'      => null,
                'delta_pct'      => null,
            ];
        }

        return $this->buildMatrixShape($fallbackRaw);
    }

    /**
     * Transform SourceReconciliationService rows (one per channel) into the
     * cross-tab shape expected by DisagreementRow in Attribution/Index.tsx.
     *
     * Input row shape (from SourceReconciliationService::disagreementMatrix()):
     *   { channel, store_claim, platform_claim, real_revenue, delta_abs, delta_pct }
     *
     * Output row shape (DisagreementRow in TSX):
     *   { channel, store, facebook, google, gsc, real }
     *
     * Mapping rules:
     *   store   = store_claim  (what the store attributed to this channel)
     *   facebook = platform_claim when channel === 'facebook', else null
     *   google   = platform_claim when channel === 'google',   else null
     *   gsc      = store_claim  when channel === 'gsc' (no platform_claim for GSC), else null
     *   real     = real_revenue
     *
     * Delta chips (vs store) are computed by the React deltaChip() function from the
     * raw numeric values, so we don't need to pass delta_pct separately.
     *
     * @param  list<array<string, mixed>>  $rawRows
     * @return list<array<string, mixed>>  DisagreementRow[]
     *
     * @see resources/js/Pages/Attribution/Index.tsx  DisagreementRow interface
     */
    private function buildMatrixShape(array $rawRows): array
    {
        $result = [];

        foreach ($rawRows as $row) {
            $channel      = (string) ($row['channel'] ?? '');
            $storeClaim   = (float)  ($row['store_claim']    ?? 0);
            $platformClaim = isset($row['platform_claim']) && $row['platform_claim'] !== null
                ? (float) $row['platform_claim']
                : null;
            $realRevenue  = (float)  ($row['real_revenue']   ?? $storeClaim);

            $result[] = [
                'channel'  => $channel,
                'store'    => round($storeClaim, 2),
                'facebook' => $channel === 'facebook' ? $platformClaim : null,
                'google'   => $channel === 'google'   ? $platformClaim : null,
                // GSC has no ad-platform counterpart; expose store_claim in the gsc column
                // so the row shows non-null data rather than all dashes.
                'gsc'      => $channel === 'gsc' ? round($storeClaim, 2) : null,
                'real'     => round($realRevenue, 2),
            ];
        }

        return $result;
    }

    /**
     * Attribution model comparison by channel.
     *
     * Real models computed here (7 of 9):
     *   - first_touch:     revenue grouped by attribution_first_touch->>'channel_type'
     *   - last_touch:      revenue grouped by attribution_last_touch->>'channel_type'
     *   - last_non_direct: revenue grouped by attribution_source (last-non-direct click,
     *                      the existing column written by AttributionParserService)
     *   - linear:          per-touch credit = 1/N; sums across orders with attribution_journey
     *   - position_based:  first 40% / last 40% / middle touches share 20% evenly
     *   - time_decay:      exponential decay with 7-day half-life relative to order time
     *   - clicks_only:     last qualifying paid touch (paid_social / paid_search) gets 100%;
     *                      orders with no qualifying touch → Not Tracked
     *
     * Deferred (requires CAPI / Site connector):
     *   - clicks_modeled: TODO v2
     *   - data_driven:    TODO v2
     *
     * Orders where attribution_journey IS NULL are excluded from the journey-based
     * models (linear / position_based / time_decay / clicks_only); they still contribute
     * to first/last/last_non_direct via the existing attribution_* columns.
     *
     * @return list<array<string, mixed>> Matches Props['model_comparison']
     */
    public function modelComparison(int $workspaceId, string $from, string $to): array
    {
        // ── Snapshot fast-path ────────────────────────────────────────────────────
        // When daily_snapshot_attribution_models has rows for every date in the
        // requested range, read from the snapshot table instead of scanning orders.
        // The current (incomplete) day always falls back to live aggregation.
        if (Schema::hasTable('daily_snapshot_attribution_models')) {
            $snapshotResult = $this->modelComparisonFromSnapshot($workspaceId, $from, $to);
            if ($snapshotResult !== null) {
                return $snapshotResult;
            }
        }

        $dayStart = $from . ' 00:00:00';
        $dayEnd   = $to . ' 23:59:59';

        // ── first_touch: group by the channel_type stored in attribution_first_touch JSONB ──
        $firstTouchRows = DB::select("
            SELECT
                COALESCE(
                    attribution_first_touch->>'channel_type',
                    attribution_first_touch->>'source',
                    'Not Tracked'
                ) AS channel,
                SUM(COALESCE(total_in_reporting_currency, total)) AS revenue
            FROM orders
            WHERE workspace_id = ?
              AND occurred_at BETWEEN ? AND ?
            GROUP BY 1
            ORDER BY revenue DESC
            LIMIT 20
        ", [$workspaceId, $dayStart, $dayEnd]);

        // ── last_touch: group by channel_type in attribution_last_touch JSONB ──
        $lastTouchRows = DB::select("
            SELECT
                COALESCE(
                    attribution_last_touch->>'channel_type',
                    attribution_last_touch->>'source',
                    'Not Tracked'
                ) AS channel,
                SUM(COALESCE(total_in_reporting_currency, total)) AS revenue
            FROM orders
            WHERE workspace_id = ?
              AND occurred_at BETWEEN ? AND ?
            GROUP BY 1
            ORDER BY revenue DESC
            LIMIT 20
        ", [$workspaceId, $dayStart, $dayEnd]);

        // ── last_non_direct: existing attribution_source column ──
        $lastNonDirectRows = DB::select("
            SELECT
                COALESCE(attribution_source, 'Not Tracked') AS channel,
                SUM(COALESCE(total_in_reporting_currency, total)) AS revenue
            FROM orders
            WHERE workspace_id = ?
              AND occurred_at BETWEEN ? AND ?
            GROUP BY attribution_source
            ORDER BY revenue DESC
            LIMIT 20
        ", [$workspaceId, $dayStart, $dayEnd]);

        // ── Journey-based models: fetch orders with a populated journey ────────
        // Resolve connected paid platforms once so clicks_only can filter qualifying touches.
        $connectedPlatforms = $this->connectedPaidPlatforms($workspaceId);

        [$linearMap, $positionMap, $timeDecayMap, $clicksOnlyMap] = $this->computeJourneyModels(
            $workspaceId, $dayStart, $dayEnd, $connectedPlatforms
        );

        // Build lookup maps: channel → revenue.
        $firstMap  = $this->indexByChannel($firstTouchRows);
        $lastMap   = $this->indexByChannel($lastTouchRows);
        $lastNdMap = $this->indexByChannel($lastNonDirectRows);

        // Union all channel names from all models.
        $allChannels = array_unique(array_merge(
            array_keys($firstMap),
            array_keys($lastMap),
            array_keys($lastNdMap),
            array_keys($linearMap),
            array_keys($positionMap),
            array_keys($timeDecayMap),
            array_keys($clicksOnlyMap),
        ));

        // Sort by first_touch revenue (the most meaningful real-data model).
        // Fall back to last_non_direct when first_touch is absent for a channel.
        // Channels that only appear in last_non_direct (e.g. 'pys' source label)
        // are pushed to the back and filtered out below when they have no first/last touch.
        usort($allChannels, static function (string $a, string $b) use ($firstMap, $lastNdMap): int {
            $aScore = $firstMap[$a] ?? $lastNdMap[$a] ?? 0;
            $bScore = $firstMap[$b] ?? $lastNdMap[$b] ?? 0;
            return $bScore <=> $aScore;
        });

        // Exclude channels that have no data in any of the three real attribution models
        // (first_touch or last_touch). This removes source-label artefacts
        // (e.g. 'pys' from attribution_source) that are not real channel names.
        $allChannels = array_values(array_filter(
            $allChannels,
            static fn (string $ch): bool => isset($firstMap[$ch]) || isset($lastMap[$ch]),
        ));

        // Total revenue across all channels (for % of revenue column).
        // Use first_touch as the canonical denominator — it covers all orders and
        // produces stable channel totals. last_non_direct sums to the same total
        // but its 'Pys' bucket (when attribution_source is a source label rather
        // than a channel slug) is not a meaningful denominator.
        $totalRevenue = array_sum($firstMap) ?: array_sum($lastNdMap) ?: 1.0;

        return array_map(fn (string $channel): array => [
            'channel'         => $channel,
            'first_touch'     => isset($firstMap[$channel])      ? round($firstMap[$channel], 2)      : null,
            'last_touch'      => isset($lastMap[$channel])       ? round($lastMap[$channel], 2)       : null,
            'last_non_direct' => isset($lastNdMap[$channel])     ? round($lastNdMap[$channel], 2)     : null,
            'linear'          => isset($linearMap[$channel])     ? round($linearMap[$channel], 2)     : null,
            'position_based'  => isset($positionMap[$channel])   ? round($positionMap[$channel], 2)   : null,
            'time_decay'      => isset($timeDecayMap[$channel])  ? round($timeDecayMap[$channel], 2)  : null,
            'clicks_only'     => isset($clicksOnlyMap[$channel]) ? round($clicksOnlyMap[$channel], 2) : null,
            'clicks_modeled'  => null, // TODO v2: requires Site connector / CAPI
            'data_driven'     => null, // Live-path: not computed inline (background job only)
            // % of total — first_touch is the canonical denominator (covers all orders).
            // Falls back to last_non_direct when first_touch is absent for a channel.
            'revenue_pct'     => round(
                (($firstMap[$channel] ?? $lastNdMap[$channel] ?? 0) / $totalRevenue) * 100,
                1,
            ),
        ], $allChannels);
    }

    /**
     * Read model comparison data from daily_snapshot_attribution_models.
     *
     * Returns null when the snapshot table does not fully cover the requested
     * date range (e.g. today, or dates before the first snapshot run), so the
     * caller can fall back to live aggregation.
     *
     * Coverage check: every date in [from, to] that is strictly before today
     * must have at least one snapshot row. Today's date is always excluded from
     * snapshot coverage (the day is incomplete) and handled by the live path.
     *
     * Channel IDs are stored lowercase in the snapshot; we apply ucwords() on
     * read so the frontend receives the same capitalised strings it expects
     * (e.g. "paid_social" → "Paid Social").
     *
     * @return list<array<string, mixed>>|null  null means "snapshot not usable, fall back"
     */
    private function modelComparisonFromSnapshot(int $workspaceId, string $from, string $to): ?array
    {
        $today   = Carbon::today('UTC')->toDateString();
        $snapTo  = $to >= $today ? Carbon::yesterday('UTC')->toDateString() : $to;

        // If the entire range is today or future, snapshot can't help.
        if ($snapTo < $from) {
            return null;
        }

        // Count distinct dates covered by the snapshot in the historical portion.
        $coveredDates = (int) DB::table('daily_snapshot_attribution_models')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $snapTo])
            ->distinct()
            ->count('date');

        // Count how many calendar dates we need covered.
        $requiredDates = (int) Carbon::parse($from)->diffInDays(Carbon::parse($snapTo)) + 1;

        if ($coveredDates < $requiredDates) {
            // Snapshot gap — fall back to live aggregation for the whole range.
            return null;
        }

        // Pull all model × channel rows for the range from the snapshot table.
        $snapshotRows = DB::table('daily_snapshot_attribution_models')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $snapTo])
            ->select(['channel_id', 'model', DB::raw('SUM(revenue) AS revenue')])
            ->groupBy('channel_id', 'model')
            ->get();

        // If the range includes today, add live first_click/last_click/last_non_direct
        // for today only (journey models are skipped for the live portion to keep latency low).
        $liveFirstMap  = [];
        $liveLastMap   = [];
        $liveLastNdMap = [];

        if ($to >= $today) {
            $dayStart = $today . ' 00:00:00';
            $dayEnd   = $today . ' 23:59:59';

            foreach (DB::select("
                SELECT LOWER(COALESCE(attribution_first_touch->>'channel_type',
                       attribution_first_touch->>'source','not_tracked')) AS ch,
                       SUM(COALESCE(total_in_reporting_currency,total)) AS rev
                FROM orders WHERE workspace_id=? AND occurred_at BETWEEN ? AND ?
                GROUP BY 1
            ", [$workspaceId, $dayStart, $dayEnd]) as $r) {
                $liveFirstMap[(string) $r->ch] = (float) $r->rev;
            }

            foreach (DB::select("
                SELECT LOWER(COALESCE(attribution_last_touch->>'channel_type',
                       attribution_last_touch->>'source','not_tracked')) AS ch,
                       SUM(COALESCE(total_in_reporting_currency,total)) AS rev
                FROM orders WHERE workspace_id=? AND occurred_at BETWEEN ? AND ?
                GROUP BY 1
            ", [$workspaceId, $dayStart, $dayEnd]) as $r) {
                $liveLastMap[(string) $r->ch] = (float) $r->rev;
            }

            foreach (DB::select("
                SELECT LOWER(COALESCE(attribution_source,'not_tracked')) AS ch,
                       SUM(COALESCE(total_in_reporting_currency,total)) AS rev
                FROM orders WHERE workspace_id=? AND occurred_at BETWEEN ? AND ?
                GROUP BY 1
            ", [$workspaceId, $dayStart, $dayEnd]) as $r) {
                $liveLastNdMap[(string) $r->ch] = (float) $r->rev;
            }
        }

        // Build per-model channel maps from snapshot rows.
        $modelMaps = [];
        foreach ($snapshotRows as $r) {
            $ch    = (string) $r->channel_id;
            $model = (string) $r->model;
            $modelMaps[$model][$ch] = ($modelMaps[$model][$ch] ?? 0.0) + (float) $r->revenue;
        }

        // Merge today's live first/last/last_non_direct into the snapshot maps.
        foreach ($liveFirstMap as $ch => $rev) {
            $modelMaps['first_click'][$ch] = ($modelMaps['first_click'][$ch] ?? 0.0) + $rev;
        }
        foreach ($liveLastMap as $ch => $rev) {
            $modelMaps['last_click'][$ch] = ($modelMaps['last_click'][$ch] ?? 0.0) + $rev;
        }
        foreach ($liveLastNdMap as $ch => $rev) {
            $modelMaps['last_non_direct'][$ch] = ($modelMaps['last_non_direct'][$ch] ?? 0.0) + $rev;
        }

        $firstMap      = $modelMaps['first_click']      ?? [];
        $lastMap       = $modelMaps['last_click']       ?? [];
        $lastNdMap     = $modelMaps['last_non_direct']  ?? [];
        $linearMap     = $modelMaps['linear']           ?? [];
        $positionMap   = $modelMaps['position_based']   ?? [];
        $timeDecayMap  = $modelMaps['time_decay']       ?? [];
        $clicksOnlyMap = $modelMaps['clicks_only']      ?? [];
        $dataDrivenMap = $modelMaps['data_driven']      ?? [];

        if (empty($firstMap) && empty($lastMap)) {
            return null; // Nothing in snapshot for this workspace/range yet.
        }

        // Apply ucwords() so channel keys match the live path display format.
        $humanise = static fn (array $map): array => array_combine(
            array_map(static fn (string $k): string => ucwords(str_replace('_', ' ', $k)), array_keys($map)),
            array_values($map),
        );

        $firstMap      = $humanise($firstMap);
        $lastMap       = $humanise($lastMap);
        $lastNdMap     = $humanise($lastNdMap);
        $linearMap     = $humanise($linearMap);
        $positionMap   = $humanise($positionMap);
        $timeDecayMap  = $humanise($timeDecayMap);
        $clicksOnlyMap = $humanise($clicksOnlyMap);
        $dataDrivenMap = $humanise($dataDrivenMap);

        // Union channel names and sort/filter exactly as the live path does.
        $allChannels = array_unique(array_merge(
            array_keys($firstMap), array_keys($lastMap), array_keys($lastNdMap),
            array_keys($linearMap), array_keys($positionMap),
            array_keys($timeDecayMap), array_keys($clicksOnlyMap),
            array_keys($dataDrivenMap),
        ));

        usort($allChannels, static function (string $a, string $b) use ($firstMap, $lastNdMap): int {
            return ($firstMap[$b] ?? $lastNdMap[$b] ?? 0) <=> ($firstMap[$a] ?? $lastNdMap[$a] ?? 0);
        });

        $allChannels = array_values(array_filter(
            $allChannels,
            static fn (string $ch): bool => isset($firstMap[$ch]) || isset($lastMap[$ch]),
        ));

        $totalRevenue = array_sum($firstMap) ?: array_sum($lastNdMap) ?: 1.0;

        return array_map(fn (string $channel): array => [
            'channel'         => $channel,
            'first_touch'     => isset($firstMap[$channel])      ? round($firstMap[$channel], 2)      : null,
            'last_touch'      => isset($lastMap[$channel])       ? round($lastMap[$channel], 2)       : null,
            'last_non_direct' => isset($lastNdMap[$channel])     ? round($lastNdMap[$channel], 2)     : null,
            'linear'          => isset($linearMap[$channel])     ? round($linearMap[$channel], 2)     : null,
            'position_based'  => isset($positionMap[$channel])   ? round($positionMap[$channel], 2)   : null,
            'time_decay'      => isset($timeDecayMap[$channel])  ? round($timeDecayMap[$channel], 2)  : null,
            'clicks_only'     => isset($clicksOnlyMap[$channel]) ? round($clicksOnlyMap[$channel], 2) : null,
            'clicks_modeled'  => null, // TODO v2: requires Site connector / CAPI
            'data_driven'     => isset($dataDrivenMap[$channel]) ? round($dataDrivenMap[$channel], 2) : null,
            'revenue_pct'     => round(
                (($firstMap[$channel] ?? $lastNdMap[$channel] ?? 0) / $totalRevenue) * 100,
                1,
            ),
        ], $allChannels);
    }

    /**
     * Compute linear, position_based, time_decay, and clicks_only revenue by channel_type.
     *
     * All four models are computed in Postgres using LATERAL jsonb_array_elements unnesting,
     * avoiding the need to load attribution_journey JSON into PHP memory. This is critical for
     * large workspaces (100k+ orders) where the old PHP chunked-loop approach would exhaust
     * memory or exceed the 5-minute recompute SLA.
     *
     * Model implementations:
     *   linear        — credit = revenue / touch_count (each touch gets 1/N share)
     *   position_based — first 40% / last 40% / middle touches share 20% evenly
     *   time_decay    — exponential decay, 7-day half-life; normalised per order
     *   clicks_only   — last qualifying paid touch (paid_social / paid_search matching a
     *                   connected platform) gets 100%; no qualifying touch → "Not Tracked"
     *
     * Fallback: when attribution_journey IS NULL for an order (column not yet populated)
     * the order is silently excluded from these four models. The first/last/last_non_direct
     * models in modelComparison() still cover that order via the existing attribution_* columns.
     *
     * @param  list<string>  $connectedPlatforms  Platform slugs with connected ad accounts
     *                                            (e.g. ['facebook', 'google']). Used to restrict
     *                                            clicks_only to real paid data sources.
     * @return array{0: array<string,float>, 1: array<string,float>, 2: array<string,float>, 3: array<string,float>}
     *   [linearMap, positionMap, timeDecayMap, clicksOnlyMap]  — each maps channel_type → revenue
     *
     * @see docs/planning/backend.md §11 — attribution pipeline performance target (<5 min / 100k)
     */
    private function computeJourneyModels(
        int    $workspaceId,
        string $dayStart,
        string $dayEnd,
        array  $connectedPlatforms = [],
    ): array {
        // NOTE: All SQL strings use nowdoc (<<<'SQL') so that JSONB operators like ->>'key'
        // are passed through literally without PHP string interpolation treating -> as an
        // object-property access. Parameterised bindings (?  placeholders) are used for all
        // user-supplied values; no dynamic SQL injection is possible.

        // ── Linear model — SQL CTE ────────────────────────────────────────────
        // unnest attribution_journey, compute 1/N credit per touch, aggregate by channel.
        // NULLIF(touch_count, 0) guards against degenerate empty arrays that pass the outer
        // filter (e.g. from a race between the journey builder and this query).
        $linearSql = <<<'SQL'
            WITH touches AS (
                SELECT
                    o.id                                                         AS order_id,
                    COALESCE(o.total_in_reporting_currency, o.total)             AS revenue,
                    INITCAP(COALESCE(
                        t.value->>'channel_type',
                        t.value->>'channel',
                        t.value->>'source',
                        'Not Tracked'
                    ))                                                           AS channel_type,
                    COUNT(*) OVER (PARTITION BY o.id)                           AS touch_count
                FROM orders o
                CROSS JOIN LATERAL jsonb_array_elements(
                    CASE WHEN jsonb_typeof(o.attribution_journey) = 'array'
                         THEN o.attribution_journey
                         ELSE '[]'::jsonb
                    END
                ) AS t(value)
                WHERE o.workspace_id = ?
                  AND o.occurred_at BETWEEN ? AND ?
                  AND o.attribution_journey IS NOT NULL
                  AND jsonb_typeof(o.attribution_journey) = 'array'
            )
            SELECT channel_type,
                   SUM(revenue / NULLIF(touch_count, 0)) AS attributed_revenue
            FROM touches
            GROUP BY channel_type
            ORDER BY attributed_revenue DESC
        SQL;

        $linearRows = DB::select($linearSql, [$workspaceId, $dayStart, $dayEnd]);

        // ── Position-based model — SQL CTE ────────────────────────────────────
        // first touch → 40%, last touch → 40%, middle touches share 20% evenly.
        // Row number within order (1-based) and touch count drive the weight expression.
        // @see WS-A2 spec — position_based model
        $positionSql = <<<'SQL'
            WITH numbered AS (
                SELECT
                    o.id                                                         AS order_id,
                    COALESCE(o.total_in_reporting_currency, o.total)             AS revenue,
                    INITCAP(COALESCE(
                        t.value->>'channel_type',
                        t.value->>'channel',
                        t.value->>'source',
                        'Not Tracked'
                    ))                                                           AS channel_type,
                    ROW_NUMBER() OVER (
                        PARTITION BY o.id
                        ORDER BY t.value->>'timestamp_at'
                    )                                                            AS rn,
                    COUNT(*) OVER (PARTITION BY o.id)                           AS n
                FROM orders o
                CROSS JOIN LATERAL jsonb_array_elements(
                    CASE WHEN jsonb_typeof(o.attribution_journey) = 'array'
                         THEN o.attribution_journey
                         ELSE '[]'::jsonb
                    END
                ) AS t(value)
                WHERE o.workspace_id = ?
                  AND o.occurred_at BETWEEN ? AND ?
                  AND o.attribution_journey IS NOT NULL
                  AND jsonb_typeof(o.attribution_journey) = 'array'
            )
            SELECT channel_type,
                   SUM(revenue * CASE
                       WHEN n = 1  THEN 1.0
                       WHEN n = 2  THEN 0.5
                       WHEN rn = 1 THEN 0.4
                       WHEN rn = n THEN 0.4
                       ELSE 0.2 / NULLIF(n - 2, 0)
                   END) AS attributed_revenue
            FROM numbered
            GROUP BY channel_type
            ORDER BY attributed_revenue DESC
        SQL;

        $positionRows = DB::select($positionSql, [$workspaceId, $dayStart, $dayEnd]);

        // ── Time-decay model — SQL CTE ────────────────────────────────────────
        // Half-life = 7 days: weight = exp(-ln(2) * days_before_order / 7).
        // GREATEST(0, ...) clamps any touch timestamped after the order to 0 days.
        // The SUM-of-weights denominator normalises so all touches sum to 1.
        // Guard clause: only parse timestamp_at when non-null and non-empty (avoids
        // timestamptz cast errors on partial journey rows).
        // @see WS-A2 spec — time_decay model, 7-day half-life
        $decaySql = <<<'SQL'
            WITH raw_weights AS (
                SELECT
                    o.id                                                         AS order_id,
                    COALESCE(o.total_in_reporting_currency, o.total)             AS revenue,
                    INITCAP(COALESCE(
                        t.value->>'channel_type',
                        t.value->>'channel',
                        t.value->>'source',
                        'Not Tracked'
                    ))                                                           AS channel_type,
                    EXP(
                        -0.693147 *
                        GREATEST(0.0,
                            EXTRACT(EPOCH FROM (
                                o.occurred_at - (t.value->>'timestamp_at')::timestamptz
                            )) / 86400.0
                        ) / 7.0
                    )                                                            AS raw_weight,
                    SUM(EXP(
                        -0.693147 *
                        GREATEST(0.0,
                            EXTRACT(EPOCH FROM (
                                o.occurred_at - (t.value->>'timestamp_at')::timestamptz
                            )) / 86400.0
                        ) / 7.0
                    )) OVER (PARTITION BY o.id)                                  AS weight_sum
                FROM orders o
                CROSS JOIN LATERAL jsonb_array_elements(
                    CASE WHEN jsonb_typeof(o.attribution_journey) = 'array'
                         THEN o.attribution_journey
                         ELSE '[]'::jsonb
                    END
                ) AS t(value)
                WHERE o.workspace_id = ?
                  AND o.occurred_at BETWEEN ? AND ?
                  AND o.attribution_journey IS NOT NULL
                  AND jsonb_typeof(o.attribution_journey) = 'array'
                  AND (t.value->>'timestamp_at') IS NOT NULL
                  AND (t.value->>'timestamp_at') <> ''
            )
            SELECT channel_type,
                   SUM(revenue * raw_weight / NULLIF(weight_sum, 0)) AS attributed_revenue
            FROM raw_weights
            GROUP BY channel_type
            ORDER BY attributed_revenue DESC
        SQL;

        $decayRows = DB::select($decaySql, [$workspaceId, $dayStart, $dayEnd]);

        // ── Clicks-only model — SQL CTE ────────────────────────────────────────
        // Find the last touch per order where channel_type is paid_social or paid_search,
        // optionally restricted to sources matching a connected platform.
        // Orders with no qualifying touch → 'Not Tracked'.
        //
        // Source → platform normalisation mirrors ChannelMappingsSeeder patterns.
        // When $connectedPlatforms is empty, skip the platform filter (degraded mode:
        // channel_type-only filtering still surfaces paid traffic correctly).
        //
        // The platform filter is constructed as a static SQL snippet (not user input)
        // so string interpolation into the outer nowdoc is safe here. The snippet
        // contains only allowlisted literal values from $connectedPlatforms which is
        // itself sourced from ad_accounts.platform (a validated enum column).
        $platformFilter = '';
        if (! empty($connectedPlatforms)) {
            // Build a safe IN list from the validated platform slugs.
            // addslashes covers the unexpected case; the real guard is the enum constraint
            // on ad_accounts.platform (only 'facebook' and 'google' are valid values).
            $connectedSet   = implode("','", array_map('addslashes', $connectedPlatforms));
            $platformFilter = "
                AND CASE LOWER(t.value->>'source')
                    WHEN 'facebook'   THEN 'facebook'
                    WHEN 'meta'       THEN 'facebook'
                    WHEN 'fb'         THEN 'facebook'
                    WHEN 'instagram'  THEN 'facebook'
                    WHEN 'ig'         THEN 'facebook'
                    WHEN 'google'     THEN 'google'
                    WHEN 'googleads'  THEN 'google'
                    WHEN 'google_ads' THEN 'google'
                    ELSE NULL
                END IN ('{$connectedSet}')";
        }

        // Clicks-only uses a regular double-quoted string so $platformFilter can be
        // interpolated. The nowdoc-incompatible JSONB operators are written as literal
        // concatenated strings to avoid PHP misinterpreting them at parse time.
        $jChannel = "t.value->>'channel_type'";
        $jChName  = "t.value->>'channel'";
        $jSrc     = "t.value->>'source'";
        $jTs      = "t.value->>'timestamp_at'";

        $clicksOnlySql = "
            WITH paid_touches AS (
                SELECT
                    o.id                                                          AS order_id,
                    COALESCE(o.total_in_reporting_currency, o.total)              AS revenue,
                    INITCAP(COALESCE({$jChannel}, {$jChName}, {$jSrc}, 'Not Tracked'))
                                                                                  AS channel_type,
                    ROW_NUMBER() OVER (
                        PARTITION BY o.id
                        ORDER BY ({$jTs}) DESC NULLS LAST
                    )                                                             AS rn
                FROM orders o
                CROSS JOIN LATERAL jsonb_array_elements(
                    CASE WHEN jsonb_typeof(o.attribution_journey) = 'array'
                         THEN o.attribution_journey
                         ELSE '[]'::jsonb
                    END
                ) AS t(value)
                WHERE o.workspace_id = ?
                  AND o.occurred_at BETWEEN ? AND ?
                  AND o.attribution_journey IS NOT NULL
                  AND jsonb_typeof(o.attribution_journey) = 'array'
                  AND LOWER(COALESCE({$jChannel}, ''))
                      IN ('paid_social', 'paid_search')
                  {$platformFilter}
            ),
            last_paid AS (
                SELECT order_id, revenue, channel_type
                FROM paid_touches
                WHERE rn = 1
            ),
            all_orders AS (
                SELECT o.id AS order_id,
                       COALESCE(o.total_in_reporting_currency, o.total) AS revenue
                FROM orders o
                WHERE o.workspace_id = ?
                  AND o.occurred_at BETWEEN ? AND ?
                  AND o.attribution_journey IS NOT NULL
                  AND jsonb_typeof(o.attribution_journey) = 'array'
            )
            SELECT
                COALESCE(lp.channel_type, 'Not Tracked') AS channel_type,
                SUM(ao.revenue)                           AS attributed_revenue
            FROM all_orders ao
            LEFT JOIN last_paid lp ON lp.order_id = ao.order_id
            GROUP BY COALESCE(lp.channel_type, 'Not Tracked')
            ORDER BY attributed_revenue DESC
        ";

        $clicksOnlyRows = DB::select($clicksOnlySql, [
            $workspaceId, $dayStart, $dayEnd,
            $workspaceId, $dayStart, $dayEnd,
        ]);

        // Convert DB result rows → [channel => revenue] maps.
        $toMap = static function (array $rows): array {
            $map = [];
            foreach ($rows as $row) {
                $ch       = (string) ($row->channel_type ?? 'Not Tracked');
                $map[$ch] = (float)  ($row->attributed_revenue ?? 0.0);
            }
            return $map;
        };

        return [
            $toMap($linearRows),
            $toMap($positionRows),
            $toMap($decayRows),
            $toMap($clicksOnlyRows),
        ];
    }

    /**
     * Extract the channel_type label from a journey touch for model aggregation.
     *
     * Falls back through channel_type → channel → source → 'Not Tracked' so that
     * touches with partial ChannelClassifierService output still land in a bucket.
     */
    private function touchChannelType(array $touch): string
    {
        $raw = $touch['channel_type'] ?? $touch['channel'] ?? $touch['source'] ?? 'Not Tracked';
        return ucfirst((string) $raw);
    }

    /**
     * Build a channel → revenue map from a DB result set.
     *
     * @param  object[]  $rows  Each row must have `channel` and `revenue` properties.
     * @return array<string, float>
     */
    private function indexByChannel(array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $channel = ucfirst((string) $row->channel);
            $map[$channel] = (float) $row->revenue;
        }

        return $map;
    }

    /**
     * Daily store revenue + not-tracked series for the gap chart.
     *
     * The source lens selects which revenue column backs the primary chart line.
     * `not_tracked` always uses `store - real` so the gap is always visible
     * regardless of the active lens.
     *
     * @param string $source  Active source lens — 'real' (default) | 'store' |
     *                        'facebook' | 'google' | 'gsc' | 'ga4'
     * @return list<array<string, mixed>> Matches Props['gap_chart']
     */
    public function gapChart(int $workspaceId, string $from, string $to, string $source = 'real'): array
    {
        $revenueColumn = $this->sourceResolver->columnFor('revenue', $source);

        $rows = DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw("
                date::text AS date,
                COALESCE(SUM(revenue), 0)                                              AS store,
                COALESCE(SUM(revenue_facebook_attributed), 0)                          AS facebook,
                COALESCE(SUM(revenue_google_attributed), 0)                            AS google,
                COALESCE(SUM(revenue), 0) - COALESCE(SUM(revenue_real_attributed), 0) AS not_tracked,
                COALESCE(SUM({$revenueColumn}), 0)                                    AS lens_revenue
            ")
            ->groupByRaw('date')
            ->orderBy('date')
            ->get();

        return $rows->map(fn ($r): array => [
            'date'        => $r->date,
            'store'       => round((float) $r->store, 2),
            'facebook'     => ($fb = round((float) $r->facebook, 2)) > 0 ? $fb : null,
            'google'       => ($gg = round((float) $r->google, 2))   > 0 ? $gg : null,
            'not_tracked'  => round((float) $r->not_tracked, 2),
            'lens_revenue' => round((float) $r->lens_revenue, 2),
        ])->all();
    }

    /**
     * Top 50 orders by revenue with their touchpoint chain for the Customer Journey Timeline.
     *
     * Reads orders table scoped to the window — per-row reads with LIMIT 50 are fine
     * (CLAUDE.md gotcha: "Never aggregate raw orders in page requests").
     * Uses withoutGlobalScopes() + explicit workspace_id per CLAUDE.md queue-job pattern.
     *
     * Touchpoint shape: { source, label, campaign } — no credit field (multi-touch deferred).
     * Source classification uses the source field from JSONB with fuzzy matching:
     *   facebook/fb/meta/instagram → 'facebook'
     *   google/cpc/googleads       → 'google'
     *   gsc/organic/organic_search → 'gsc'
     *   ga4                        → 'ga4'
     *   else                       → 'store'
     *
     * @param string $model  Active attribution model (unused today — deferred for multi-touch).
     *                       Accepted for API compatibility with future journey model switching.
     * @return list<array<string, mixed>> Matches Props['journey_orders'] in Attribution/Index.tsx
     */
    public function journeyOrders(int $workspaceId, string $from, string $to, string $model = 'last_non_direct_click'): array
    {
        $orders = Order::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereIn('status', ['processing', 'completed'])
            ->orderByRaw('COALESCE(total_in_reporting_currency, total) DESC')
            ->limit(self::JOURNEY_LIMIT)
            ->get([
                'id', 'external_id', 'customer_email_hash',
                'total', 'total_in_reporting_currency', 'occurred_at',
                'attribution_source', 'utm_source', 'utm_medium', 'utm_campaign',
                'attribution_click_ids',
                'attribution_first_touch', 'attribution_last_touch',
                'attribution_last_non_direct',
            ]);

        return $orders->map(fn (Order $o): array => [
            'id'              => (string) $o->id,
            'external_id'     => (string) ($o->external_id ?? ''),
            'revenue'         => round((float) ($o->total_in_reporting_currency ?? $o->total), 2),
            'ordered_at'      => $o->occurred_at?->toIso8601String() ?? '',
            'channel'         => (string) ($o->attribution_source ?? ''),
            'utm_source'      => (string) ($o->utm_source ?? ''),
            'utm_medium'      => (string) ($o->utm_medium ?? ''),
            'utm_campaign'    => (string) ($o->utm_campaign ?? ''),
            'has_fbclid'      => isset($o->attribution_click_ids['fbclid']),
            'has_gclid'       => isset($o->attribution_click_ids['gclid']),
            'touchpoints'     => $this->journeyTouchpoints($o),
            'days_to_convert' => null, // deferred — requires multi-touch journey table (WS-A2)
        ])->all();
    }

    /**
     * Attribution gap as a percentage: (store - real) / store * 100.
     * Returns 0 when no store revenue — avoids NULLIF divide edge case.
     */
    public function attributionGapPct(int $workspaceId, string $from, string $to): float
    {
        $row = $this->aggregateDaily($workspaceId, $from, $to);
        $store = (float) $row->store;
        $real  = (float) $row->real;

        if ($store === 0.0) {
            return 0.0;
        }

        return round(($store - $real) / $store * 100, 2);
    }

    /**
     * Orders with no identified channel attribution — candidates for the
     * "Not Tracked" expand panel in Attribution/Index.tsx.
     *
     * An order is "Not Tracked" when its attribution_first_touch JSONB either
     * has no channel_type key or the channel_type is null/empty. This covers:
     *   - Orders added via REST API with no UTM params
     *   - Orders where ChannelClassifierService returned an unknown source
     *   - Orders imported before the attribution pipeline ran
     *
     * Capped at 50 rows — the panel is meant as a "why are these untracked?"
     * diagnostic, not a full order list (use /orders for that).
     *
     * @return list<array<string, mixed>>  Matches Props['not_tracked_orders'] in Attribution/Index.tsx
     */
    public function notTrackedOrders(int $workspaceId, string $from, string $to): array
    {
        $rows = Order::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where(static function ($q) {
                $q->whereNull('attribution_first_touch')
                  ->orWhereRaw("attribution_first_touch->>'channel_type' IS NULL OR attribution_first_touch->>'channel_type' = ''");
            })
            ->orderByRaw('COALESCE(total_in_reporting_currency, total) DESC')
            ->limit(50)
            ->get([
                'id', 'external_id', 'customer_email_hash',
                'total', 'total_in_reporting_currency', 'occurred_at',
                'status', 'attribution_first_touch', 'attribution_source',
            ]);

        return $rows->map(fn (Order $o): array => [
            'id'              => (string) $o->id,
            'external_id'     => (string) ($o->external_id ?? ''),
            'customer_masked' => '••••' . substr((string) $o->customer_email_hash, 0, 4),
            'revenue'         => round((float) ($o->total_in_reporting_currency ?? $o->total), 2),
            'date'            => $o->occurred_at?->toDateString() ?? '',
            'status'          => (string) ($o->status ?? ''),
            'raw_source'      => $o->attribution_first_touch['source'] ?? null,
        ])->all();
    }

    /**
     * Per-date disagreement matrix frames for the Attribution Time Machine.
     *
     * Queries daily_source_disagreements for every date in the range and returns
     * one frame per date, each containing a DisagreementRow[] shaped identically
     * to what disagreementMatrix() returns — so the same SourceDisagreementMatrix
     * component can consume it without modification.
     *
     * Returns an empty array when no rows exist for the window (new install / night
     * job not yet run). The frontend renders an empty-state message in that case.
     *
     * Does NOT aggregate raw orders — reads the pre-computed reconciliation table.
     * @see docs/planning/schema.md daily_source_disagreements
     * @see App\Jobs\ReconcileSourceDisagreementsJob (nightly writer)
     *
     * @return list<array{date: string, rows: list<array<string, mixed>>}>
     */
    public function timeMachineData(int $workspaceId, string $from, string $to): array
    {
        $rows = DB::select("
            SELECT
                date::text AS date,
                channel,
                COALESCE(store_claim, 0)   AS store_claim,
                platform_claim,
                COALESCE(real_revenue, 0)  AS real_revenue
            FROM daily_source_disagreements
            WHERE workspace_id = ?
              AND date BETWEEN ? AND ?
            ORDER BY date ASC, channel ASC
        ", [$workspaceId, $from, $to]);

        if (empty($rows)) {
            return [];
        }

        // Group rows by date, then transform each group into DisagreementRow[] shape.
        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row->date][] = $row;
        }

        $frames = [];
        foreach ($byDate as $date => $dateRows) {
            $frames[] = [
                'date' => $date,
                'rows' => $this->buildMatrixShape(
                    array_map(static fn (object $r): array => [
                        'channel'        => (string) $r->channel,
                        'store_claim'    => (float)  $r->store_claim,
                        'platform_claim' => $r->platform_claim !== null ? (float) $r->platform_claim : null,
                        'real_revenue'   => (float)  $r->real_revenue,
                        'delta_abs'      => null,
                        'delta_pct'      => null,
                    ], $dateRows)
                ),
            ];
        }

        return $frames;
    }

    /**
     * Post-purchase survey breakdown — "How did you hear about us?" responses
     * aggregated by response value for the given period.
     *
     * Reads from order_metafields where key = 'hdyhau_response' (Fairing/KnoCommerce
     * compatible field name). Returns an empty array when no survey integration is
     * connected.
     *
     * @return list<array{response: string, count: int, revenue: float|null, pct: float}>
     */
    public function surveyBreakdown(int $workspaceId, string $from, string $to): array
    {
        // Guard: table is created by the 2026_04_29 migration. Return empty array
        // gracefully if the migration hasn't run yet (e.g. fresh installs mid-deploy).
        if (! \Illuminate\Support\Facades\Schema::hasTable('order_metafields')) {
            return [];
        }

        $rows = DB::select("
            SELECT
                om.value                      AS response,
                COUNT(DISTINCT o.id)          AS response_count,
                SUM(o.total_in_reporting_currency) AS revenue
            FROM order_metafields om
            JOIN orders o ON o.id = om.order_id
            WHERE o.workspace_id = ?
              AND om.key = 'hdyhau_response'
              AND o.occurred_at BETWEEN ? AND ?
              AND om.value IS NOT NULL
              AND om.value <> ''
            GROUP BY om.value
            ORDER BY response_count DESC
            LIMIT 20
        ", [$workspaceId, $from . ' 00:00:00', $to . ' 23:59:59']);

        if (empty($rows)) {
            return [];
        }

        $total = array_sum(array_column($rows, 'response_count'));

        return array_map(static function (object $r) use ($total): array {
            $count = (int) $r->response_count;
            return [
                'response' => (string) $r->response,
                'count'    => $count,
                'revenue'  => $r->revenue !== null ? (float) $r->revenue : null,
                'pct'      => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }, $rows);
    }

    /**
     * Returns true when a RecomputeAttributionJob is currently queued or
     * in-flight for this workspace (via the ShouldBeUnique lock key).
     *
     * Uses the same cache key that Laravel's unique-job mechanism writes,
     * so this is zero-overhead — no extra queue queries.
     */
    public function isRecomputing(int $workspaceId): bool
    {
        $lockKey = 'laravel_unique_job:App\Jobs\RecomputeAttributionJob' . $workspaceId;
        return \Illuminate\Support\Facades\Cache::has($lockKey);
    }

    // ─── private helpers ──────────────────────────────────────────────────────

    /**
     * Return the distinct platform slugs for which the workspace has at least one
     * connected ad account. Used by the clicks_only model to determine whether a
     * paid touch's source platform is actually wired to real ad spend data.
     *
     * Returns an empty array when no ad accounts are connected; in that case
     * clicks_only falls back to channel_type-only filtering (no source check),
     * which is a sensible degraded mode that still surfaces paid traffic.
     *
     * Reads: ad_accounts (workspace-scoped)
     *
     * @return list<string>  e.g. ['facebook', 'google']
     */
    private function connectedPaidPlatforms(int $workspaceId): array
    {
        $platforms = DB::table('ad_accounts')
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('platform')
            ->distinct()
            ->pluck('platform')
            ->map(fn (string $p): string => strtolower(trim($p)))
            ->filter()
            ->values()
            ->all();

        return $platforms;
    }

    /**
     * Aggregate a single named revenue column from daily_snapshots.
     *
     * Used when `$source !== 'real'` in metrics() to compute the lens-specific
     * revenue total without re-running the full aggregateDaily() query.
     * Column is validated through MetricSourceResolver::columnFor() before calling — interpolation is safe.
     */
    private function aggregateDailyColumn(int $workspaceId, string $from, string $to, string $column): object
    {
        return DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw("COALESCE(SUM({$column}), 0) AS v")
            ->first() ?? (object) ['v' => 0];
    }

    /** Single-row aggregate across daily_snapshots for the given window. */
    private function aggregateDaily(int $workspaceId, string $from, string $to): object
    {
        return DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('
                COALESCE(SUM(revenue), 0)                     AS store,
                COALESCE(SUM(revenue_facebook_attributed), 0) AS facebook,
                COALESCE(SUM(revenue_google_attributed), 0)   AS google,
                COALESCE(SUM(revenue_gsc_attributed), 0)      AS gsc,
                COALESCE(SUM(revenue_direct_attributed), 0)
                    + COALESCE(SUM(revenue_organic_attributed), 0)
                    + COALESCE(SUM(revenue_email_attributed), 0) AS site,
                COALESCE(SUM(revenue_real_attributed), 0)     AS real
            ')
            ->first() ?? (object) [
                'store' => 0, 'facebook' => 0, 'google' => 0,
                'gsc' => 0, 'site' => 0, 'real' => 0,
            ];
    }

    /**
     * Build the simplified 2-touchpoint chain for the Customer Journey Timeline.
     *
     * Touchpoint shape: { source, label, campaign }
     *   source   — canonical badge key: facebook | google | gsc | ga4 | store
     *   label    — human-readable source string from the JSONB (e.g. "facebook", "google")
     *   campaign — utm campaign name, or null
     *
     * Logic:
     *   First touch  = attribution_first_touch JSONB (if present).
     *   Last touch   = attribution_last_non_direct JSONB if non-null, else attribution_last_touch.
     *   If first == last channel classification, deduplicate to a single touchpoint.
     *
     * When both JSONB columns are null (e.g. order pre-dates attribution pipeline)
     * returns a single Direct/store touchpoint so the UI always has something to show.
     *
     * @return list<array<string, mixed>>  Each element: { source, label, campaign }
     */
    private function journeyTouchpoints(Order $o): array
    {
        $first      = is_array($o->attribution_first_touch)        ? $o->attribution_first_touch        : null;
        $lastNonDir = is_array($o->attribution_last_non_direct)    ? $o->attribution_last_non_direct    : null;
        $lastTouch  = is_array($o->attribution_last_touch)         ? $o->attribution_last_touch         : null;

        // Prefer last_non_direct over last_touch per spec.
        $last = $lastNonDir ?? $lastTouch;

        if ($first === null && $last === null) {
            return [[
                'source'   => 'store',
                'label'    => 'Direct',
                'campaign' => null,
            ]];
        }

        $touchpoints = [];

        if ($first !== null) {
            $touchpoints[] = [
                'source'   => $this->classifyTouchpointSource($first),
                'label'    => (string) ($first['source'] ?? 'unknown'),
                'campaign' => ($first['campaign'] ?? null) ?: null,
            ];
        }

        if ($last !== null) {
            $lastSource = $this->classifyTouchpointSource($last);
            $lastLabel  = (string) ($last['source'] ?? 'unknown');

            // Deduplicate: skip last touch when it maps to the same classified source.
            // Comparison uses the classified source key (not raw label) to avoid showing
            // "facebook → facebook" when source labels are normalised differently.
            $firstSource = ! empty($touchpoints) ? $touchpoints[0]['source'] : null;

            if ($lastSource !== $firstSource) {
                $touchpoints[] = [
                    'source'   => $lastSource,
                    'label'    => $lastLabel,
                    'campaign' => ($last['campaign'] ?? null) ?: null,
                ];
            }
        }

        return $touchpoints;
    }

    /**
     * Map a raw attribution JSONB touch to a canonical source badge key.
     *
     * Classification ladder (matches SourceBadge MetricSource type):
     *   facebook — source contains "facebook", "fb", "meta", "instagram", "ig"
     *   google   — source contains "google", "cpc", "googleads", "google_ads"
     *   gsc      — source contains "organic", "gsc", "search_console"
     *   ga4      — source contains "ga4"
     *   store    — everything else (direct, email, referral, unknown)
     *
     * @param  array<string, mixed>  $touch  attribution_first_touch / attribution_last_touch JSONB
     * @return 'facebook'|'google'|'gsc'|'ga4'|'store'
     */
    private function classifyTouchpointSource(array $touch): string
    {
        $raw = strtolower((string) ($touch['source'] ?? $touch['medium'] ?? ''));

        if (str_contains($raw, 'facebook') || str_contains($raw, 'fb')
            || str_contains($raw, 'meta') || str_contains($raw, 'instagram')
            || str_contains($raw, 'ig')) {
            return 'facebook';
        }

        if (str_contains($raw, 'google') || str_contains($raw, 'cpc')
            || str_contains($raw, 'googleads') || str_contains($raw, 'google_ads')) {
            return 'google';
        }

        if (str_contains($raw, 'organic') || str_contains($raw, 'gsc')
            || str_contains($raw, 'search_console')) {
            return 'gsc';
        }

        if (str_contains($raw, 'ga4')) {
            return 'ga4';
        }

        return 'store';
    }

    /**
     * Immediately-preceding window of the same length.
     *
     * @return array{0:string, 1:string}
     */
    private function previousRange(string $from, string $to): array
    {
        $len    = (int) Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
        $prevTo = Carbon::parse($from)->subDay()->toDateString();
        $prevFrom = Carbon::parse($prevTo)->subDays($len - 1)->toDateString();
        return [$prevFrom, $prevTo];
    }
}
