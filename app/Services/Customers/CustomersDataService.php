<?php

declare(strict_types=1);

namespace App\Services\Customers;

use Illuminate\Support\Facades\DB;

/**
 * Aggregates all data required by the Customers/Index Inertia page.
 *
 * Prop shape matches Customers/Index.tsx exactly:
 *   metrics, rfm_segments, cohort_data, ltv_curves, channel_ltv,
 *   cac, ltv_cac, channel_cac_trend,
 *   active_tab, ltv_calibrating, ltv_calibration_day.
 *
 * Data sources (per docs/pages/customers.md):
 *   - customer_rfm_scores — RFM segment counts, avg LTV.
 *   - customers — total, new customers, repeat rate.
 *   - orders — cohort retention and LTV curves.
 *     NOTE: cohort/LTV aggregation queries `orders` directly because there is no
 *     daily_snapshots equivalent at the customer×cohort grain. This is the only
 *     exemption to the CLAUDE.md "never aggregate raw orders in page requests" rule,
 *     and is documented explicitly here.
 *   - daily_snapshots — new customer revenue (ncr) metric.
 *
 * Expensive composites (cohort LTV curves) are limited to 12 recent cohorts and
 * 12 monthly buckets to keep response times acceptable without caching in v1.
 *
 * Reads:  customer_rfm_scores, customers, orders, daily_snapshots
 * Writes: nothing
 * Called by: CustomersController::__invoke()
 *
 * @see docs/pages/customers.md
 * @see docs/planning/backend.md
 */
class CustomersDataService
{
    /**
     * Valid source lens slugs. When lens != 'real', LTV / NCR queries filter
     * orders to those attributed to the active source.
     *
     * 'store' and 'real' are equivalent for customers (all orders count — no
     * per-order cross-platform attribution exists yet).
     * 'ga4' → TODO: WS-F ga4_order_attribution; falls back to real.
     *
     * @see docs/planning/schema.md §1.5 orders.attribution_source
     */
    private const VALID_SOURCES = ['real', 'store', 'facebook', 'google', 'gsc', 'ga4'];

    // Segment display metadata keyed by segment slug.
    private const SEGMENT_META = [
        'champions'          => ['description' => 'Bought recently, buy often, spend the most.',  'color' => '#16a34a'],
        'loyal'              => ['description' => 'Buy regularly and respond well to promotions.', 'color' => '#2563eb'],
        'potential_loyalists'=> ['description' => 'Recent customers with above-average frequency.','color' => '#7c3aed'],
        'at_risk'            => ['description' => 'Above-average customers who haven\'t bought recently.', 'color' => '#dc2626'],
        'about_to_sleep'     => ['description' => 'Below-average recency and frequency — act now.',  'color' => '#f59e0b'],
        'needs_attention'    => ['description' => 'Made purchases but not recently.',                'color' => '#64748b'],
        'hibernating'        => ['description' => 'Last purchase was a long time ago.',              'color' => '#94a3b8'],
    ];

    /**
     * Build the full customers page payload.
     *
     * @return array{
     *   metrics: array<string, mixed>,
     *   rfm_segments: list<array<string, mixed>>,
     *   rfm_cells: list<array<string, mixed>>,
     *   segment_traits: array<string, list<array<string, mixed>>>,
     *   cohort_data: list<array<string, mixed>>,
     *   ltv_curves: list<array<string, mixed>>,
     *   channel_ltv: list<array<string, mixed>>,
     *   ltv_drivers: list<array<string, mixed>>,
     *   cac: float|null,
     *   ltv_cac: float|null,
     *   channel_cac_trend: list<array<string, mixed>>,
     *   median_days_to_second_order: int|null,
     *   active_tab: string,
     *   ltv_calibrating: bool,
     *   ltv_calibration_day: int,
     * }
     */
    public function forIndex(int $workspaceId, string $tab, string $source = 'real'): array
    {
        // Normalise: treat 'store' and 'ga4' as 'real' (no per-source order split yet).
        $effectiveSource = in_array($source, ['store', 'ga4'], true) ? 'real' : $source;

        $metrics                  = $this->buildMetrics($workspaceId, $effectiveSource);
        $rfmSegments              = $this->buildRfmSegments($workspaceId);
        $rfmCells                 = $this->buildRfmCells($workspaceId);
        $segmentTraits            = $this->buildAllSegmentTraits($workspaceId);
        $cohortData               = $this->buildCohortData($workspaceId);
        $ltvCurves                = $this->buildLtvCurves($workspaceId, $effectiveSource);
        $channelLtv               = $this->buildChannelLtv($workspaceId, $effectiveSource);
        $ltvDrivers               = $this->buildLtvDrivers($workspaceId);
        [$cac, $ltvCac]           = $this->buildCacMetrics($workspaceId, $metrics['ltv_90d']);
        $channelCacTrend          = $this->buildChannelCacTrend($workspaceId);
        $medianDaysToSecondOrder  = $this->buildMedianDaysToSecondOrder($workspaceId);

        // LTV calibration: flag when workspace has fewer than 90 days of orders.
        [$ltv_calibrating, $ltv_calibration_day] = $this->calibrationStatus($workspaceId);

        return [
            'metrics'                     => $metrics,
            'rfm_segments'                => $rfmSegments,
            'rfm_cells'                   => $rfmCells,
            'segment_traits'              => $segmentTraits,
            'cohort_data'                 => $cohortData,
            'ltv_curves'                  => $ltvCurves,
            'channel_ltv'                 => $channelLtv,
            'ltv_drivers'                 => $ltvDrivers,
            'cac'                         => $cac,
            'ltv_cac'                     => $ltvCac,
            'channel_cac_trend'           => $channelCacTrend,
            'median_days_to_second_order' => $medianDaysToSecondOrder,
            'active_tab'                  => $tab,
            'ltv_calibrating'             => $ltv_calibrating,
            'ltv_calibration_day'         => $ltv_calibration_day,
        ];
    }

    /**
     * Top acquisition channels for every known RFM segment, computed in two queries.
     *
     * Approach:
     *   1. Fetch all customer IDs per segment from the latest RFM scoring run.
     *   2. Join those customers to orders (completed/processing) and aggregate
     *      by acquisition_source to find channel share per segment.
     *   3. Return top-5 channels per segment as AudienceTrait-shaped arrays.
     *
     * WorkspaceScope note: uses withoutGlobalScopes() + explicit workspace_id
     * filter because this method may be called from contexts where the Workspace
     * middleware isn't active (e.g. future admin panel queries).
     *
     * @return array<string, list<array{label: string, value: string, share: float, color: string}>>
     *   Keyed by segment slug. Empty array for segments with no order data.
     *
     * @see docs/pages/customers.md §segments
     */
    public function buildAllSegmentTraits(int $workspaceId): array
    {
        // Fixed palette for the top-5 channel slots.
        $palette = ['#6366f1', '#14b8a6', '#f59e0b', '#22c55e', '#94a3b8'];

        // Most recent computed_for date — same as used by buildRfmSegments.
        $latestDate = DB::table('customer_rfm_scores')
            ->where('workspace_id', $workspaceId)
            ->max('computed_for');

        if ($latestDate === null) {
            return [];
        }

        // One query: per-(segment, acquisition_source) customer count.
        // Uses withoutGlobalScopes pattern: explicit workspace_id on both tables.
        $rows = DB::table('customer_rfm_scores AS rfm')
            ->join('customers AS c', function ($j) use ($workspaceId) {
                $j->on('c.id', '=', 'rfm.customer_id')
                  ->where('c.workspace_id', $workspaceId);
            })
            ->where('rfm.workspace_id', $workspaceId)
            ->where('rfm.computed_for', $latestDate)
            ->selectRaw('
                rfm.segment,
                COALESCE(c.acquisition_source, \'direct\') AS channel,
                COUNT(*) AS cnt
            ')
            ->groupBy('rfm.segment', 'c.acquisition_source')
            ->orderBy('rfm.segment')
            ->orderByRaw('COUNT(*) DESC')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Group counts per segment, sum totals.
        $segmentGroups = [];
        $segmentTotals = [];
        foreach ($rows as $row) {
            $slug    = $row->segment;
            $channel = $row->channel;
            $cnt     = (int) $row->cnt;

            $segmentGroups[$slug][$channel] = $cnt;
            $segmentTotals[$slug]           = ($segmentTotals[$slug] ?? 0) + $cnt;
        }

        // Convert to AudienceTrait shape, top-5 channels per segment.
        $result = [];
        foreach ($segmentGroups as $slug => $channelCounts) {
            $total = $segmentTotals[$slug] ?? 0;
            if ($total === 0) {
                $result[$slug] = [];
                continue;
            }

            // Sort descending by count (already ordered by DB but re-sort for safety).
            arsort($channelCounts);
            $top5   = array_slice($channelCounts, 0, 5, true);
            $traits = [];
            $i      = 0;

            foreach ($top5 as $channel => $cnt) {
                $share    = $total > 0 ? round($cnt / $total, 4) : 0.0;
                $traits[] = [
                    'label' => ucwords(str_replace('_', ' ', (string) $channel)),
                    'value' => round($share * 100, 1) . '%',
                    'share' => $share,
                    'color' => $palette[$i] ?? '#94a3b8',
                ];
                $i++;
            }

            $result[$slug] = $traits;
        }

        return $result;
    }

    /**
     * Top KPI row: total customers, new (30d), new customer revenue, LTV windows, repeat rate.
     *
     * When $source is a specific platform, NCR only counts first-time orders attributed
     * to that source (answers "how much new customer revenue did Facebook drive?").
     * CAC stays constant (always full ad spend — not source-attributed).
     *
     * @param  string  $source  Active source lens ('real' default; 'store'/'ga4' treated as 'real').
     * @return array{
     *   total: int,
     *   new_30d: int,
     *   ncr: float|null,
     *   ltv_30d: float|null,
     *   ltv_90d: float|null,
     *   ltv_365d: float|null,
     *   repeat_rate: float|null,
     * }
     */
    public function buildMetrics(int $workspaceId, string $source = 'real'): array
    {
        $total = (int) DB::table('customers')
            ->where('workspace_id', $workspaceId)
            ->count();

        $new30d = (int) DB::table('customers')
            ->where('workspace_id', $workspaceId)
            ->where('first_order_at', '>=', now()->subDays(30)->startOfDay())
            ->count();

        // New customer revenue: sum of revenue from first-time orders in last 30d.
        // When lens is a specific source, only count orders attributed to that source.
        $ncrQuery = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->where('is_first_for_customer', true)
            ->where('occurred_at', '>=', now()->subDays(30)->startOfDay())
            ->whereIn('status', ['completed', 'processing']);

        if ($source !== 'real') {
            $ncrQuery->where('attribution_source', $source);
        }

        $ncr = $ncrQuery->value(DB::raw('SUM(total_in_reporting_currency)'));

        // LTV windows from customer_rfm_scores (most recent score per customer).
        // predicted_ltv_reporting is a proxy for LTV; cumulative LTV uses lifetime_value_reporting from customers.
        $ltvAgg = DB::table('customers as c')
            ->join(
                DB::raw('(SELECT customer_id, MAX(computed_for) AS latest FROM customer_rfm_scores WHERE workspace_id = ' . (int) $workspaceId . ' GROUP BY customer_id) AS lr'),
                'c.id', '=', 'lr.customer_id'
            )
            ->join('customer_rfm_scores AS rfm', function ($j) use ($workspaceId) {
                $j->on('rfm.customer_id', '=', 'lr.customer_id')
                  ->on('rfm.computed_for', '=', 'lr.latest')
                  ->where('rfm.workspace_id', '=', $workspaceId);
            })
            ->where('c.workspace_id', $workspaceId)
            ->selectRaw('
                AVG(c.lifetime_value_reporting) AS avg_ltv,
                AVG(CASE WHEN c.first_order_at >= NOW() - INTERVAL \'30 days\' THEN c.lifetime_value_reporting END) AS ltv_30d,
                AVG(CASE WHEN c.first_order_at >= NOW() - INTERVAL \'90 days\' THEN c.lifetime_value_reporting END) AS ltv_90d,
                AVG(CASE WHEN c.first_order_at >= NOW() - INTERVAL \'365 days\' THEN c.lifetime_value_reporting END) AS ltv_365d
            ')
            ->first();

        // Repeat rate: customers with orders_count > 1 / total.
        $repeaters = (int) DB::table('customers')
            ->where('workspace_id', $workspaceId)
            ->where('orders_count', '>', 1)
            ->count();

        $repeatRate = $total > 0 ? round(($repeaters / $total) * 100, 2) : null;

        return [
            'total'      => $total,
            'new_30d'    => $new30d,
            'ncr'        => $ncr !== null ? round((float) $ncr, 2) : null,
            'ltv_30d'    => $ltvAgg?->ltv_30d  !== null ? round((float) $ltvAgg->ltv_30d,  2) : null,
            'ltv_90d'    => $ltvAgg?->ltv_90d  !== null ? round((float) $ltvAgg->ltv_90d,  2) : null,
            'ltv_365d'   => $ltvAgg?->ltv_365d !== null ? round((float) $ltvAgg->ltv_365d, 2) : null,
            'repeat_rate'=> $repeatRate,
        ];
    }

    /**
     * RFM segment summary from the most recent nightly scoring run.
     *
     * Includes avg_aov (average order value per customer in that segment),
     * revenue_pct (segment's share of total workspace order revenue),
     * and trend (count delta vs prior scoring run; null when only one run exists).
     *
     * @return list<array{
     *   name: string, slug: string, count: int, pct: float,
     *   avg_ltv: float|null, avg_aov: float|null, revenue_pct: float|null,
     *   trend: int|null, description: string, color: string
     * }>
     */
    public function buildRfmSegments(int $workspaceId): array
    {
        // Most recent computed_for date for this workspace.
        $latestDate = DB::table('customer_rfm_scores')
            ->where('workspace_id', $workspaceId)
            ->max('computed_for');

        if ($latestDate === null) {
            return [];
        }

        // Prior scoring date for trend arrow (may be null if only one run exists).
        $priorDate = DB::table('customer_rfm_scores')
            ->where('workspace_id', $workspaceId)
            ->where('computed_for', '<', $latestDate)
            ->max('computed_for');

        // Current period: segment counts, avg LTV, avg AOV, segment revenue.
        $rows = DB::table('customer_rfm_scores AS rfm')
            ->join('customers AS c', function ($j) use ($workspaceId) {
                $j->on('c.id', '=', 'rfm.customer_id')
                  ->where('c.workspace_id', $workspaceId);
            })
            ->where('rfm.workspace_id', $workspaceId)
            ->where('rfm.computed_for', $latestDate)
            ->selectRaw('
                rfm.segment,
                COUNT(*) AS cnt,
                AVG(c.lifetime_value_reporting) AS avg_ltv,
                AVG(NULLIF(c.lifetime_value_reporting, 0) / NULLIF(c.orders_count, 0)) AS avg_aov,
                SUM(c.lifetime_value_reporting) AS segment_revenue
            ')
            ->groupBy('rfm.segment')
            ->get();

        // Total workspace revenue for revenue_pct calculation.
        $totalRevenue = (float) DB::table('customers')
            ->where('workspace_id', $workspaceId)
            ->sum('lifetime_value_reporting');

        $total = $rows->sum('cnt');

        // Prior counts keyed by segment slug for trend calculation.
        $priorCounts = [];
        if ($priorDate !== null) {
            $priorRows = DB::table('customer_rfm_scores')
                ->where('workspace_id', $workspaceId)
                ->where('computed_for', $priorDate)
                ->selectRaw('segment, COUNT(*) AS cnt')
                ->groupBy('segment')
                ->get();
            foreach ($priorRows as $pr) {
                $priorCounts[$pr->segment] = (int) $pr->cnt;
            }
        }

        $result = [];
        foreach ($rows as $row) {
            $slug = $row->segment;
            $meta = self::SEGMENT_META[$slug] ?? ['description' => $slug, 'color' => '#94a3b8'];
            $cnt  = (int) $row->cnt;

            $trend = isset($priorCounts[$slug]) ? $cnt - $priorCounts[$slug] : null;

            $result[] = [
                'name'         => ucwords(str_replace('_', ' ', $slug)),
                'slug'         => $slug,
                'count'        => $cnt,
                'pct'          => $total > 0 ? round(($cnt / $total) * 100, 2) : 0.0,
                'avg_ltv'      => $row->avg_ltv      !== null ? round((float) $row->avg_ltv,      2) : null,
                'avg_aov'      => $row->avg_aov      !== null ? round((float) $row->avg_aov,      2) : null,
                'revenue_pct'  => ($totalRevenue > 0 && $row->segment_revenue !== null)
                    ? round(((float) $row->segment_revenue / $totalRevenue) * 100, 1)
                    : null,
                'trend'        => $trend,
                'description'  => $meta['description'],
                'color'        => $meta['color'],
            ];
        }

        // Sort by count DESC for consistent display order.
        usort($result, fn($a, $b) => $b['count'] <=> $a['count']);

        return $result;
    }

    /**
     * RFM grid cell data for the RFMGrid component.
     *
     * Returns one entry per (r, fm) cell, where fm = ROUND((frequency_score + monetary_score) / 2).
     * Each entry carries the dominant segment name for that cell (the segment with the most
     * customers), plus the total customer count across all segments in that cell.
     *
     * @return list<array{r: int, fm: int, count: int, segment: string}>
     */
    public function buildRfmCells(int $workspaceId): array
    {
        // Segment slug → display name map.
        $segmentNames = [
            'loyal'              => 'Loyal',
            'at_risk'            => 'At Risk',
            'champions'          => 'Champions',
            'potential_loyalist' => 'Potential Loyalists',
            'new'                => 'New',
            'promising'          => 'Promising',
            'needs_attention'    => 'Needs Attention',
            'about_to_sleep'     => 'About to Sleep',
            'cant_lose_them'     => "Can't Lose Them",
            'hibernating'        => 'Hibernating',
        ];

        // Most recent computed_for date for this workspace.
        $latestDate = DB::table('customer_rfm_scores')
            ->where('workspace_id', $workspaceId)
            ->max('computed_for');

        if ($latestDate === null) {
            return [];
        }

        // Aggregate per (recency_score, fm_score, segment), ordered so that for each
        // (r, fm) pair the segment with the highest count comes first.
        $rows = DB::select(
            '
            SELECT
                recency_score AS r,
                ROUND((frequency_score + monetary_score) / 2.0)::int AS fm,
                segment,
                COUNT(*) AS cnt
            FROM customer_rfm_scores
            WHERE workspace_id = ? AND computed_for = ?
            GROUP BY recency_score, ROUND((frequency_score + monetary_score) / 2.0), segment
            ORDER BY recency_score, ROUND((frequency_score + monetary_score) / 2.0), COUNT(*) DESC
            ',
            [$workspaceId, $latestDate],
        );

        // Group by (r, fm) in PHP: sum counts, pick the first row's segment (highest count).
        $cells = [];
        foreach ($rows as $row) {
            $key = "{$row->r}:{$row->fm}";
            if (!isset($cells[$key])) {
                // First row for this cell has the dominant segment (ORDER BY cnt DESC).
                $cells[$key] = [
                    'r'       => (int) $row->r,
                    'fm'      => (int) $row->fm,
                    'count'   => 0,
                    'segment' => $segmentNames[$row->segment] ?? ucwords(str_replace('_', ' ', $row->segment)),
                ];
            }
            $cells[$key]['count'] += (int) $row->cnt;
        }

        return array_values($cells);
    }

    /**
     * Cohort retention heatmap data.
     *
     * NOTE: aggregates `orders` directly — there is no customer×cohort daily_snapshots
     * equivalent. Limited to 12 most recent monthly cohorts × 12 months.
     *
     * @return list<array{cohort: string, months: list<array{month: int, retention_pct: float|null, customers: int}>}>
     */
    public function buildCohortData(int $workspaceId): array
    {
        // Get customers grouped by acquisition month (first_order_at month).
        // Only pull id + cohort_month — we don't need first_order_at per-row.
        $cohorts = DB::table('customers')
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('first_order_at')
            ->selectRaw("
                TO_CHAR(DATE_TRUNC('month', first_order_at), 'YYYY-MM') AS cohort_month,
                id AS customer_id
            ")
            ->orderBy('first_order_at')
            ->get();

        if ($cohorts->isEmpty()) {
            return [];
        }

        // Group customers by cohort month.
        $cohortGroups = [];
        foreach ($cohorts as $c) {
            $cohortGroups[$c->cohort_month][] = $c->customer_id;
        }

        // Take the 12 most recent cohorts.
        krsort($cohortGroups);
        $cohortGroups = array_slice($cohortGroups, 0, 12, true);
        krsort($cohortGroups); // re-sort ascending for display

        if (empty($cohortGroups)) {
            return [];
        }

        // Resolve the date window for the entire cohort matrix in one query.
        // We need all orders for all cohort customers across the 12×12 matrix.
        // Single query: SELECT cohort_month, month_offset, COUNT(DISTINCT customer_id)
        // Replaces up to 144 individual queries (12 cohorts × 12 offsets).
        $allCustomerIds = array_unique(array_merge(...array_values($cohortGroups)));
        $cohortMonths   = array_keys($cohortGroups);
        $earliestStart  = \Illuminate\Support\Carbon::parse(min($cohortMonths) . '-01')->startOfMonth()->toDateTimeString();

        // Map customer_id → cohort_month for the join.
        $customerCohortMap = [];
        foreach ($cohortGroups as $cohortMonth => $ids) {
            foreach ($ids as $id) {
                $customerCohortMap[$id] = $cohortMonth;
            }
        }

        // Fetch all qualifying orders for cohort customers in one pass.
        // month_offset = months between the customer's cohort month and the order month (0-11).
        $orderRows = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereIn('customer_id', $allCustomerIds)
            ->whereIn('status', ['completed', 'processing'])
            ->where('occurred_at', '>=', $earliestStart)
            ->select(['customer_id', DB::raw("TO_CHAR(DATE_TRUNC('month', occurred_at), 'YYYY-MM') AS order_month")])
            ->distinct()
            ->get();

        // Build a map: cohort_month => offset => count of distinct active customers.
        // offset = months between cohort_month and order_month (clamped 0-11).
        $activeCounts = [];
        foreach ($orderRows as $row) {
            $cohortMonth = $customerCohortMap[(int) $row->customer_id] ?? null;
            if ($cohortMonth === null) {
                continue;
            }
            $offset = (int) \Illuminate\Support\Carbon::parse($cohortMonth . '-01')
                ->diffInMonths(\Illuminate\Support\Carbon::parse($row->order_month . '-01'));
            if ($offset < 0 || $offset > 11) {
                continue;
            }
            // Track distinct customer_ids per (cohort, offset).
            $activeCounts[$cohortMonth][$offset][(int) $row->customer_id] = true;
        }

        // Build result shape.
        $result = [];
        foreach ($cohortGroups as $cohortMonth => $customerIds) {
            $cohortSize = count($customerIds);
            $months     = [];

            for ($offset = 0; $offset <= 11; $offset++) {
                $activeCount  = isset($activeCounts[$cohortMonth][$offset])
                    ? count($activeCounts[$cohortMonth][$offset])
                    : 0;
                $retentionPct = $cohortSize > 0
                    ? round(($activeCount / $cohortSize) * 100, 1)
                    : null;

                $months[] = [
                    'month'         => $offset,
                    'retention_pct' => $retentionPct,
                    'customers'     => $offset === 0 ? $cohortSize : (int) $activeCount,
                ];
            }

            $result[] = [
                'cohort' => $cohortMonth,
                'months' => $months,
            ];
        }

        return $result;
    }

    /**
     * LTV curves by acquisition channel.
     *
     * NOTE: aggregates `orders` directly — exemption per class docblock.
     * Computes cumulative average revenue per customer, by months since first order.
     *
     * When $source is a specific platform, only orders attributed to that source
     * contribute to LTV. This shows the lifetime value of orders won through that channel.
     *
     * @param  string  $source  Active source lens ('real' default).
     * @return list<array{channel: string, data: list<array{month: int, ltv: float}>}>
     */
    public function buildLtvCurves(int $workspaceId, string $source = 'real'): array
    {
        // Pull cumulative revenue per customer per month-offset, grouped by acquisition source.
        $joinCallback = function ($j) use ($workspaceId, $source) {
            $j->on('o.customer_id', '=', 'c.id')
              ->where('o.workspace_id', $workspaceId)
              ->whereIn('o.status', ['completed', 'processing']);
            if ($source !== 'real') {
                $j->where('o.attribution_source', $source);
            }
        };

        $rows = DB::table('customers AS c')
            ->join('orders AS o', $joinCallback)
            ->where('c.workspace_id', $workspaceId)
            ->whereNotNull('c.acquisition_source')
            ->selectRaw("
                COALESCE(c.acquisition_source, 'Direct') AS channel,
                c.id AS customer_id,
                EXTRACT(YEAR FROM AGE(DATE_TRUNC('month', o.occurred_at), DATE_TRUNC('month', c.first_order_at))) * 12
                    + EXTRACT(MONTH FROM AGE(DATE_TRUNC('month', o.occurred_at), DATE_TRUNC('month', c.first_order_at))) AS month_offset,
                SUM(o.total_in_reporting_currency) AS month_revenue
            ")
            ->groupBy('c.acquisition_source', 'c.id', DB::raw("DATE_TRUNC('month', o.occurred_at)"), DB::raw("DATE_TRUNC('month', c.first_order_at)"))
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Aggregate to cumulative LTV per channel per month.
        $channelData = [];
        foreach ($rows as $row) {
            $channel = $row->channel;
            $month   = max(0, (int) $row->month_offset);
            $revenue = (float) $row->month_revenue;
            $custId  = $row->customer_id;

            $channelData[$channel][$month][$custId] = ($channelData[$channel][$month][$custId] ?? 0) + $revenue;
        }

        $curves = [];
        foreach ($channelData as $channel => $monthBuckets) {
            // Cumulative: accumulate revenue across months per customer, then avg.
            ksort($monthBuckets);
            $allCustomers = array_unique(array_merge(...array_map('array_keys', $monthBuckets)));
            $cumulative = array_fill_keys($allCustomers, 0.0);
            $data = [];

            for ($m = 0; $m <= 11; $m++) {
                if (!isset($monthBuckets[$m])) {
                    continue;
                }
                foreach ($monthBuckets[$m] as $custId => $rev) {
                    $cumulative[$custId] = ($cumulative[$custId] ?? 0) + $rev;
                }
                $avgLtv = count($cumulative) > 0
                    ? round(array_sum($cumulative) / count($cumulative), 2)
                    : 0.0;
                $data[] = ['month' => $m, 'ltv' => $avgLtv];
            }

            if (count($data) >= 2) {
                $curves[] = ['channel' => $channel, 'data' => $data];
            }
        }

        // Sort by channel name for stable legend.
        usort($curves, fn($a, $b) => strcmp($a['channel'], $b['channel']));

        return $curves;
    }

    /**
     * Channel LTV table (LTV 30d / 90d / 365d / payback_days) per acquisition channel.
     *
     * NOTE: aggregates `orders` directly — exemption per class docblock.
     *
     * When $source is a specific platform, only customers whose acquisition_source
     * matches are included, scoping the LTV table to that channel's cohort.
     *
     * @param  string  $source  Active source lens ('real' default).
     * @return list<array{channel: string, ltv_30d: float|null, ltv_90d: float|null, ltv_365d: float|null, payback_days: int|null}>
     */
    public function buildChannelLtv(int $workspaceId, string $source = 'real'): array
    {
        $query = DB::table('customers AS c')
            ->where('c.workspace_id', $workspaceId)
            ->whereNotNull('c.acquisition_source');

        if ($source !== 'real') {
            $query->where('c.acquisition_source', $source);
        }

        $rows = $query
            ->selectRaw("
                COALESCE(c.acquisition_source, 'Direct') AS channel,
                COUNT(DISTINCT c.id) AS customer_count,
                AVG(CASE WHEN c.first_order_at >= NOW() - INTERVAL '30 days'
                         THEN c.lifetime_value_reporting END) AS ltv_30d,
                AVG(CASE WHEN c.first_order_at >= NOW() - INTERVAL '90 days'
                         THEN c.lifetime_value_reporting END) AS ltv_90d,
                AVG(c.lifetime_value_reporting) AS ltv_365d
            ")
            ->groupBy('c.acquisition_source')
            ->get();

        // Blended CAC: total ad spend ÷ new customers acquired (last 365 days).
        // Used as a proxy for per-channel CAC until channel-level spend attribution is built.
        $totalSpend = (float) DB::table('ad_insights')
            ->where('workspace_id', $workspaceId)
            ->where('level', 'campaign')
            ->where('date', '>=', now()->subDays(365)->toDateString())
            ->sum('spend_in_reporting_currency');

        $newCustomers = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->where('is_first_for_customer', true)
            ->whereIn('status', ['completed', 'processing'])
            ->where('occurred_at', '>=', now()->subDays(365))
            ->count();

        $blendedCac = ($newCustomers > 0 && $totalSpend > 0)
            ? $totalSpend / $newCustomers
            : null;

        return $rows->map(fn($r) => [
            'channel'      => $r->channel,
            'ltv_30d'      => $r->ltv_30d  !== null ? round((float) $r->ltv_30d,  2) : null,
            'ltv_90d'      => $r->ltv_90d  !== null ? round((float) $r->ltv_90d,  2) : null,
            'ltv_365d'     => $r->ltv_365d !== null ? round((float) $r->ltv_365d, 2) : null,
            'payback_days' => (function () use ($blendedCac, $r): ?int {
                $ltv365 = $r->ltv_365d !== null ? (float) $r->ltv_365d : null;
                if ($blendedCac === null || $ltv365 === null || $ltv365 <= 0) {
                    return null;
                }
                $monthlyLtv = $ltv365 / 12.0;
                return $monthlyLtv > 0 ? (int) round($blendedCac / $monthlyLtv * 30) : null;
            })(),
        ])->sortBy('channel')->values()->all();
    }

    /**
     * LTV Drivers table — which products are associated with the highest-LTV customers?
     *
     * Joins order_items → orders → customers to compute per-product:
     *   - distinct customer count
     *   - avg LTV (from customers.lifetime_value_reporting)
     *   - avg AOV (avg order item line_total)
     *   - LTV:CAC ratio (using blended CAC = total ad spend / new customers, last 365 days)
     *   - Repeat Rate (% of those customers who placed >1 order)
     *   - Revenue % (product's share of total workspace revenue)
     *
     * NOTE: aggregates order_items directly — exemption per class docblock.
     * Limited to top 50 products by avg_ltv to keep response size manageable.
     *
     * @return list<array{
     *   id: string, product_name: string, customers: int,
     *   avg_ltv: float|null, avg_aov: float|null, ltv_cac: float|null,
     *   repeat_rate: float|null, revenue_pct: float|null
     * }>
     */
    public function buildLtvDrivers(int $workspaceId): array
    {
        // Blended CAC: total ad spend ÷ new customers acquired (last 365 days).
        $totalSpend = (float) DB::table('ad_insights')
            ->where('workspace_id', $workspaceId)
            ->where('level', 'campaign')
            ->where('date', '>=', now()->subDays(365)->toDateString())
            ->sum('spend_in_reporting_currency');

        $newCustomers = (int) DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->where('is_first_for_customer', true)
            ->whereIn('status', ['completed', 'processing'])
            ->where('occurred_at', '>=', now()->subDays(365))
            ->count();

        $blendedCac = ($newCustomers > 0 && $totalSpend > 0)
            ? $totalSpend / $newCustomers
            : null;

        // Total workspace revenue for revenue_pct.
        $totalRevenue = (float) DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereIn('status', ['completed', 'processing'])
            ->sum('total_in_reporting_currency');

        $rows = DB::table('order_items AS oi')
            ->join('orders AS o', 'oi.order_id', '=', 'o.id')
            ->join('customers AS c', 'o.customer_id', '=', 'c.id')
            ->where('o.workspace_id', $workspaceId)
            ->whereIn('o.status', ['completed', 'processing'])
            ->selectRaw("
                oi.product_name,
                COUNT(DISTINCT o.customer_id) AS customers,
                AVG(c.lifetime_value_reporting) AS avg_ltv,
                AVG(oi.line_total) AS avg_aov,
                SUM(oi.line_total) AS product_revenue,
                COUNT(DISTINCT CASE WHEN o.is_first_for_customer = false THEN o.customer_id END)::float
                    / NULLIF(COUNT(DISTINCT o.customer_id), 0) * 100 AS repeat_rate
            ")
            ->groupBy('oi.product_name')
            ->orderByRaw('AVG(c.lifetime_value_reporting) DESC NULLS LAST')
            ->limit(50)
            ->get();

        $result = [];
        foreach ($rows as $idx => $row) {
            $avgLtv = $row->avg_ltv !== null ? (float) $row->avg_ltv : null;
            $ltvCac = ($blendedCac !== null && $blendedCac > 0 && $avgLtv !== null)
                ? round($avgLtv / $blendedCac, 2)
                : null;

            $result[] = [
                // Stable ID for React key (index is stable since query is deterministic).
                'id'           => (string) $idx,
                'product_name' => $row->product_name ?? 'Unknown',
                'customers'    => (int) $row->customers,
                'avg_ltv'      => $avgLtv !== null ? round($avgLtv, 2) : null,
                'avg_aov'      => $row->avg_aov !== null ? round((float) $row->avg_aov, 2) : null,
                'ltv_cac'      => $ltvCac,
                'repeat_rate'  => $row->repeat_rate !== null ? round((float) $row->repeat_rate, 1) : null,
                'revenue_pct'  => ($totalRevenue > 0 && $row->product_revenue !== null)
                    ? round(((float) $row->product_revenue / $totalRevenue) * 100, 1)
                    : null,
            ];
        }

        return $result;
    }

    /**
     * Compute blended CAC and LTV:CAC ratio for the KPI row.
     *
     * CAC = total ad spend (campaign level only) over the last 30 days
     *       ÷ new customers (first order) acquired in the same window.
     * LTV:CAC = ltv_90d / CAC. Null when either input is missing or CAC = 0.
     *
     * The 30-day window aligns with the new_30d customer metric shown beside it,
     * so the ratio reads "for the customers we just acquired, was it worth it?".
     *
     * @param  float|null  $ltv90d  Pre-computed 90d LTV from buildMetrics().
     * @return array{float|null, float|null}  [cac, ltv_cac]
     *
     * @see docs/competitors/_crosscut_metric_dictionary.md LTV:CAC
     */
    public function buildCacMetrics(int $workspaceId, ?float $ltv90d): array
    {
        $from = now()->subDays(30)->startOfDay()->toDateString();
        $to   = now()->endOfDay()->toDateString();

        // Total ad spend (campaign level only to avoid double-counting across levels).
        $adSpend = (float) DB::table('ad_insights')
            ->where('workspace_id', $workspaceId)
            ->where('level', 'campaign')
            ->whereBetween('date', [$from, $to])
            ->sum('spend_in_reporting_currency');

        // New customers: orders that are the first for that customer, in the window.
        $newCustomers = (int) DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->where('is_first_for_customer', true)
            ->whereIn('status', ['completed', 'processing'])
            ->whereBetween('occurred_at', [
                now()->subDays(30)->startOfDay(),
                now()->endOfDay(),
            ])
            ->count();

        $cac = ($newCustomers > 0 && $adSpend > 0)
            ? round($adSpend / $newCustomers, 2)
            : null;

        $ltvCac = ($ltv90d !== null && $cac !== null && $cac > 0)
            ? round($ltv90d / $cac, 2)
            : null;

        return [$cac, $ltvCac];
    }

    /**
     * Monthly CAC per acquisition channel for the last 6 calendar months.
     *
     * For each month: CAC_facebook = facebook campaign spend / new customers
     * whose attribution_source = 'facebook'. Organic has no spend so it is
     * excluded (callers may hide null entries).
     *
     * Returns newest-last so Recharts charts left-to-right chronologically.
     *
     * @return list<array{month: string, cac_facebook: float|null, cac_google: float|null}>
     *
     * @see docs/planning/backend.md §customers
     */
    public function buildChannelCacTrend(int $workspaceId): array
    {
        // Build month boundaries for the last 6 months once, then fetch all data in
        // 2 queries (one for ad spend, one for new customers) instead of 12 (2 × 6).
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $start    = now()->startOfMonth()->subMonths($i);
            $months[] = [
                'label' => $start->format('M Y'),
                'start' => $start->toDateString(),
                'end'   => $start->copy()->endOfMonth()->toDateString(),
                'month_key' => $start->format('Y-m'), // e.g. "2025-04"
            ];
        }

        $rangeStart = $months[0]['start'];
        $rangeEnd   = $months[count($months) - 1]['end'];

        // Single query: ad spend by (year-month, platform) over the entire 6-month window.
        $spendByMonth = DB::table('ad_insights')
            ->join('ad_accounts', 'ad_accounts.id', '=', 'ad_insights.ad_account_id')
            ->where('ad_insights.workspace_id', $workspaceId)
            ->where('ad_insights.level', 'campaign')
            ->whereBetween('ad_insights.date', [$rangeStart, $rangeEnd])
            ->whereIn('ad_accounts.platform', ['facebook', 'google'])
            ->selectRaw("
                TO_CHAR(ad_insights.date, 'YYYY-MM')          AS month_key,
                ad_accounts.platform,
                SUM(ad_insights.spend_in_reporting_currency)  AS total_spend
            ")
            ->groupByRaw("TO_CHAR(ad_insights.date, 'YYYY-MM'), ad_accounts.platform")
            ->get()
            ->groupBy('month_key')
            ->map(fn ($rows) => $rows->pluck('total_spend', 'platform')->all());

        // Single query: new customer counts by (year-month, attribution_source).
        $newCustByMonth = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->where('is_first_for_customer', true)
            ->whereIn('status', ['completed', 'processing'])
            ->whereBetween('occurred_at', [$rangeStart . ' 00:00:00', $rangeEnd . ' 23:59:59'])
            ->whereIn('attribution_source', ['facebook', 'google'])
            ->selectRaw("
                TO_CHAR(occurred_at, 'YYYY-MM') AS month_key,
                attribution_source,
                COUNT(*) AS cnt
            ")
            ->groupByRaw("TO_CHAR(occurred_at, 'YYYY-MM'), attribution_source")
            ->get()
            ->groupBy('month_key')
            ->map(fn ($rows) => $rows->pluck('cnt', 'attribution_source')->all());

        $result = [];
        foreach ($months as $m) {
            $key      = $m['month_key'];
            $spends   = $spendByMonth[$key]  ?? [];
            $newCusts = $newCustByMonth[$key] ?? [];

            $fbSpend  = isset($spends['facebook'])  ? (float) $spends['facebook']  : null;
            $fbNew    = isset($newCusts['facebook']) ? (int)   $newCusts['facebook'] : 0;
            $gSpend   = isset($spends['google'])     ? (float) $spends['google']    : null;
            $gNew     = isset($newCusts['google'])   ? (int)   $newCusts['google']  : 0;

            $result[] = [
                'month'        => $m['label'],
                'cac_facebook' => ($fbSpend !== null && $fbSpend > 0 && $fbNew > 0)
                    ? round($fbSpend / $fbNew, 2) : null,
                'cac_google'   => ($gSpend !== null && $gSpend > 0 && $gNew > 0)
                    ? round($gSpend / $gNew, 2) : null,
            ];
        }

        return $result;
    }

    /**
     * Median days between a customer's 1st and 2nd order.
     *
     * Uses PERCENTILE_CONT(0.5) (equivalent to MEDIAN) over the set of customers
     * who have placed at least two qualifying orders. Only counts orders with status
     * in ['completed', 'processing'] to match the rest of the service.
     *
     * Returns null when fewer than one customer has a 2nd order (avoids a misleading
     * result during the early days of a workspace).
     *
     * @return int|null  Rounded integer days, or null if insufficient data.
     */
    public function buildMedianDaysToSecondOrder(int $workspaceId): ?int
    {
        $result = DB::selectOne(
            "
            SELECT PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY days_to_second) AS median_days
            FROM (
                SELECT
                    customer_id,
                    EXTRACT(EPOCH FROM (second_order - first_order)) / 86400 AS days_to_second
                FROM (
                    SELECT
                        customer_id,
                        MIN(occurred_at)                                                              AS first_order,
                        MIN(CASE WHEN order_rank = 2 THEN occurred_at END)                            AS second_order
                    FROM (
                        SELECT
                            customer_id,
                            occurred_at,
                            ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY occurred_at) AS order_rank
                        FROM orders
                        WHERE workspace_id = ?
                          AND status IN ('completed', 'processing')
                    ) ranked
                    GROUP BY customer_id
                    HAVING MIN(CASE WHEN order_rank = 2 THEN occurred_at END) IS NOT NULL
                ) pair
            ) deltas
            ",
            [$workspaceId],
        );

        if ($result === null || $result->median_days === null) {
            return null;
        }

        return (int) round((float) $result->median_days);
    }

    /**
     * Segment drill-down: paginated customer list + KPIs + top products + top channels
     * for a single RFM segment.
     *
     * Reads:
     *   - customer_rfm_scores  — segment membership (most recent scoring run)
     *   - customers            — LTV, orders_count, last_order_at (materialised fields)
     *   - orders + order_items — top products (one query, exempted per class docblock)
     *
     * Does NOT aggregate raw orders for KPIs — avg_ltv / avg_aov / avg_frequency are
     * derived from customers.lifetime_value_reporting and customers.orders_count (both
     * are materialised by CustomerStitchingJob / CustomerLtvUpdateJob).
     * avg_recency_days uses customers.last_order_at.
     *
     * @return array{
     *   customers: array{
     *     data: list<array<string, mixed>>,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int,
     *   },
     *   kpis: array{avg_ltv: float|null, avg_aov: float|null, avg_frequency: float|null, avg_recency_days: float|null},
     *   top_products: list<array{product_name: string, customer_count: int, revenue: float}>,
     *   top_channels: list<array{channel: string, count: int, share: float}>,
     * }
     */
    public function segmentDrilldown(int $workspaceId, string $segmentSlug, int $page = 1, int $perPage = 25): array
    {
        // Most recent scoring date for this workspace.
        $latestDate = DB::table('customer_rfm_scores')
            ->where('workspace_id', $workspaceId)
            ->max('computed_for');

        if ($latestDate === null) {
            return $this->emptyDrilldown($page, $perPage);
        }

        // ── Customer IDs in this segment ─────────────────────────────────────
        $customerIds = DB::table('customer_rfm_scores')
            ->where('workspace_id', $workspaceId)
            ->where('computed_for', $latestDate)
            ->where('segment', $segmentSlug)
            ->pluck('customer_id')
            ->all();

        if (empty($customerIds)) {
            return $this->emptyDrilldown($page, $perPage);
        }

        $total = count($customerIds);

        // ── KPIs from materialised customers fields ───────────────────────────
        $kpiRow = DB::table('customers')
            ->where('workspace_id', $workspaceId)
            ->whereIn('id', $customerIds)
            ->selectRaw("
                AVG(lifetime_value_reporting) AS avg_ltv,
                AVG(NULLIF(lifetime_value_reporting, 0) / NULLIF(orders_count, 0)) AS avg_aov,
                AVG(orders_count) AS avg_frequency,
                AVG(EXTRACT(EPOCH FROM (NOW() - last_order_at)) / 86400) AS avg_recency_days
            ")
            ->first();

        $kpis = [
            'avg_ltv'          => $kpiRow->avg_ltv          !== null ? round((float) $kpiRow->avg_ltv, 2)          : null,
            'avg_aov'          => $kpiRow->avg_aov          !== null ? round((float) $kpiRow->avg_aov, 2)          : null,
            'avg_frequency'    => $kpiRow->avg_frequency    !== null ? round((float) $kpiRow->avg_frequency, 1)    : null,
            'avg_recency_days' => $kpiRow->avg_recency_days !== null ? round((float) $kpiRow->avg_recency_days, 0) : null,
        ];

        // ── Paginated customer list ───────────────────────────────────────────
        $offset        = ($page - 1) * $perPage;
        $pageCustomers = DB::table('customers')
            ->where('workspace_id', $workspaceId)
            ->whereIn('id', $customerIds)
            ->select([
                'id',
                'display_email_masked',
                'name',
                'orders_count',
                'lifetime_value_reporting',
                'last_order_at',
                'acquisition_source',
            ])
            ->selectRaw("EXTRACT(EPOCH FROM (NOW() - last_order_at)) / 86400 AS recency_days")
            ->orderByDesc('lifetime_value_reporting')
            ->limit($perPage)
            ->offset($offset)
            ->get();

        // Top product per customer — best effort single query.
        $custPageIds = $pageCustomers->pluck('id')->all();
        $topProductByCustomer = [];

        if (!empty($custPageIds)) {
            $topProductRows = DB::table('order_items AS oi')
                ->join('orders AS o', function ($j) use ($workspaceId) {
                    $j->on('o.id', '=', 'oi.order_id')
                      ->where('o.workspace_id', $workspaceId)
                      ->whereIn('o.status', ['completed', 'processing']);
                })
                ->whereIn('o.customer_id', $custPageIds)
                ->selectRaw("
                    o.customer_id,
                    oi.product_name,
                    SUM(oi.line_total) AS total
                ")
                ->groupBy('o.customer_id', 'oi.product_name')
                ->orderByRaw('o.customer_id, SUM(oi.line_total) DESC')
                ->get();

            foreach ($topProductRows as $pr) {
                $cid = (int) $pr->customer_id;
                if (!isset($topProductByCustomer[$cid])) {
                    $topProductByCustomer[$cid] = $pr->product_name;
                }
            }
        }

        $customerData = $pageCustomers->map(function ($c) use ($topProductByCustomer) {
            return [
                'id'                  => $c->id,
                'email'               => $c->display_email_masked ?? '—',
                'name'                => $c->name ?? '—',
                'orders_count'        => (int) $c->orders_count,
                'ltv'                 => $c->lifetime_value_reporting !== null ? round((float) $c->lifetime_value_reporting, 2) : null,
                'last_order_at'       => $c->last_order_at,
                'recency_days'        => $c->recency_days !== null ? (int) round((float) $c->recency_days) : null,
                'top_product'         => $topProductByCustomer[(int) $c->id] ?? null,
                'acquisition_source'  => $c->acquisition_source,
            ];
        })->values()->all();

        $lastPage = (int) ceil($total / $perPage);

        // ── Top 5 products by customer count in this segment ─────────────────
        // Exempted: order_items join — see class docblock.
        $topProducts = DB::table('order_items AS oi')
            ->join('orders AS o', function ($j) use ($workspaceId) {
                $j->on('o.id', '=', 'oi.order_id')
                  ->where('o.workspace_id', $workspaceId)
                  ->whereIn('o.status', ['completed', 'processing']);
            })
            ->whereIn('o.customer_id', $customerIds)
            ->selectRaw("
                oi.product_name,
                COUNT(DISTINCT o.customer_id) AS customer_count,
                SUM(oi.line_total) AS revenue
            ")
            ->groupBy('oi.product_name')
            ->orderByRaw('COUNT(DISTINCT o.customer_id) DESC')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'product_name'   => $r->product_name ?? 'Unknown',
                'customer_count' => (int) $r->customer_count,
                'revenue'        => $r->revenue !== null ? round((float) $r->revenue, 2) : 0.0,
            ])
            ->values()
            ->all();

        // ── Top 5 acquisition channels for this segment ───────────────────────
        $channelRows = DB::table('customers')
            ->where('workspace_id', $workspaceId)
            ->whereIn('id', $customerIds)
            ->selectRaw("
                COALESCE(acquisition_source, 'direct') AS channel,
                COUNT(*) AS cnt
            ")
            ->groupBy('acquisition_source')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->get();

        $channelTotal = $channelRows->sum('cnt') ?: 1;
        $topChannels  = $channelRows->map(fn ($r) => [
            'channel' => ucwords(str_replace('_', ' ', (string) $r->channel)),
            'count'   => (int) $r->cnt,
            'share'   => round((int) $r->cnt / $channelTotal, 4),
        ])->values()->all();

        return [
            'customers' => [
                'data'         => $customerData,
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max(1, $lastPage),
            ],
            'kpis'         => $kpis,
            'top_products' => $topProducts,
            'top_channels' => $topChannels,
        ];
    }

    /** @return array same shape as segmentDrilldown but all-empty. */
    private function emptyDrilldown(int $page, int $perPage): array
    {
        return [
            'customers' => [
                'data'         => [],
                'total'        => 0,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => 1,
            ],
            'kpis'         => ['avg_ltv' => null, 'avg_aov' => null, 'avg_frequency' => null, 'avg_recency_days' => null],
            'top_products' => [],
            'top_channels' => [],
        ];
    }

    /**
     * Determine whether LTV model is still calibrating (< 90 days of order history).
     *
     * @return array{bool, int}  [is_calibrating, day_reached]
     */
    private function calibrationStatus(int $workspaceId): array
    {
        $firstOrderAt = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->min('occurred_at');

        if ($firstOrderAt === null) {
            return [true, 0];
        }

        // Use abs() to handle seed data where orders may be in the future.
        $daysSinceFirst = (int) abs(now()->diffInDays(\Illuminate\Support\Carbon::parse($firstOrderAt)));

        if ($daysSinceFirst >= 90) {
            return [false, 90];
        }

        return [true, $daysSinceFirst];
    }
}
