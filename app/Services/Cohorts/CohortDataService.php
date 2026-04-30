<?php

declare(strict_types=1);

namespace App\Services\Cohorts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds the full cohort analysis dataset used by both:
 *   - CustomersController (tab=retention)
 *   - CohortController (legacy redirect target, kept for backward compat)
 *
 * Returns heatmap_rows, curve_series, pacing, summary, and available_channels
 * in the same shape originally computed inline in CohortController.
 *
 * Query strategy:
 *   1. Fast-path: when no channel filter is active, reads pre-computed rows
 *      from daily_snapshot_cohorts if the table exists and has data.
 *   2. Live-path: runs a CTE query over orders for channel-filtered requests.
 *
 * Reads: orders, daily_snapshot_cohorts (workspace-scoped by $workspaceId).
 * Writes: nothing.
 *
 * @see app/Http/Controllers/CohortController.php
 * @see app/Http/Controllers/CustomersController.php
 */
class CohortDataService
{
    // Minimum cohort size for "high confidence" display.
    private const LOW_CONFIDENCE_THRESHOLD = 10;

    /**
     * Build the full cohort analysis payload.
     *
     * @param  int     $workspaceId
     * @param  int     $period   1–36 months of acquisition history
     * @param  string  $metric   "revenue" | "orders" | "customers"
     * @param  string  $view     "heatmap" | "curves" | "pacing"
     * @param  string  $channel  attribution_source value, or "all"
     *
     * @return array{
     *   heatmap_rows: list<array<string, mixed>>,
     *   curve_series: list<array<string, mixed>>,
     *   pacing: array{current: list, average: list, current_label: string|null},
     *   max_offset: int,
     *   summary: array<string, mixed>,
     *   available_channels: list<array<string, mixed>>,
     *   low_confidence_threshold: int,
     *   cohort_filters: array<string, mixed>,
     * }
     */
    public function build(
        int    $workspaceId,
        int    $period  = 12,
        string $metric  = 'revenue',
        string $view    = 'heatmap',
        string $channel = 'all',
    ): array {
        $period = max(1, min(36, $period));

        if (! in_array($metric, ['revenue', 'orders', 'customers'], true)) {
            $metric = 'revenue';
        }
        if (! in_array($view, ['heatmap', 'curves', 'pacing'], true)) {
            $view = 'heatmap';
        }

        // ── Available channels ────────────────────────────────────────────────
        $channelCountRows = DB::select(
            <<<'SQL'
            SELECT
                attribution_source,
                COUNT(*) AS customer_count
            FROM orders
            WHERE workspace_id = :ws_id
              AND is_first_for_customer = true
              AND status NOT IN ('cancelled', 'refunded')
              AND attribution_source IS NOT NULL
            GROUP BY attribution_source
            ORDER BY attribution_source
            SQL,
            ['ws_id' => $workspaceId]
        );

        $availableChannels = [];
        foreach ($channelCountRows as $row) {
            $availableChannels[] = [
                'value'    => $row->attribution_source,
                'label'    => ucwords(str_replace('_', ' ', $row->attribution_source)),
                'low_data' => (int) $row->customer_count < 3,
            ];
        }

        // Sanitise channel against known values.
        $validChannelValues = array_column($availableChannels, 'value');
        if ($channel !== 'all' && ! in_array($channel, $validChannelValues, true)) {
            $channel = 'all';
        }

        // ── Channel customer-ID filter ────────────────────────────────────────
        $channelCustomerFilter = '';
        $channelBindings       = [];

        if ($channel !== 'all') {
            $channelCustomerFilter = 'AND customer_id IN (
                SELECT customer_id
                FROM orders
                WHERE workspace_id = :ch_ws_id
                  AND is_first_for_customer = true
                  AND attribution_source = :ch_value
            )';
            $channelBindings = [
                'ch_ws_id' => $workspaceId,
                'ch_value' => $channel,
            ];
        }

        // ── Snapshot fast-path (all-channels only) ────────────────────────────
        $rows = null;

        if (
            $channel === 'all'
            && Schema::hasTable('daily_snapshot_cohorts')
            && DB::table('daily_snapshot_cohorts')
                   ->where('workspace_id', $workspaceId)
                   ->exists()
        ) {
            $snapshotRows = DB::table('daily_snapshot_cohorts')
                ->where('workspace_id', $workspaceId)
                ->where('cohort_period', '>=', DB::raw(
                    "(DATE_TRUNC('month', NOW()) - INTERVAL '1 month' * {$period})"
                ))
                ->orderBy('cohort_period')
                ->orderBy('period_offset')
                ->get(['cohort_period', 'period_offset', 'revenue', 'orders_count', 'customers_active'])
                ->all();

            if (! empty($snapshotRows)) {
                $rows = array_map(static function (object $r): object {
                    return (object) [
                        'acquisition_month' => is_string($r->cohort_period)
                            ? substr($r->cohort_period, 0, 10)
                            : $r->cohort_period,
                        'order_month'       => null,
                        'month_offset'      => (int) $r->period_offset,
                        'revenue'           => $r->revenue,
                        'orders_count'      => $r->orders_count,
                        'customers_count'   => $r->customers_active,
                    ];
                }, $snapshotRows);
            }
        }

        // ── Raw cohort query (fallback) ───────────────────────────────────────
        if ($rows === null) {
            $rows = DB::select(
                <<<SQL
                WITH acquisitions AS (
                    SELECT
                        customer_id,
                        DATE_TRUNC('month', occurred_at)::date AS acquisition_month
                    FROM orders
                    WHERE workspace_id = :ws_id
                      AND is_first_for_customer = true
                      AND status NOT IN ('cancelled', 'refunded')
                      AND occurred_at >= (DATE_TRUNC('month', NOW()) - INTERVAL '1 month' * :period)
                      {$channelCustomerFilter}
                ),
                cohort_data AS (
                    SELECT
                        a.acquisition_month,
                        DATE_TRUNC('month', o.occurred_at)::date AS order_month,
                        EXTRACT(EPOCH FROM (
                            DATE_TRUNC('month', o.occurred_at) - a.acquisition_month::timestamp
                        )) / (86400 * 30.44) AS month_offset_raw,
                        o.customer_id,
                        o.total_in_reporting_currency AS revenue
                    FROM orders o
                    JOIN acquisitions a ON a.customer_id = o.customer_id
                    WHERE o.workspace_id = :ws_id2
                      AND o.status NOT IN ('cancelled', 'refunded')
                )
                SELECT
                    acquisition_month,
                    order_month,
                    ROUND(month_offset_raw)::int AS month_offset,
                    SUM(revenue)                  AS revenue,
                    COUNT(*)                      AS orders_count,
                    COUNT(DISTINCT customer_id)   AS customers_count
                FROM cohort_data
                GROUP BY acquisition_month, order_month, ROUND(month_offset_raw)::int
                ORDER BY acquisition_month, month_offset
                SQL,
                array_merge(
                    [
                        'ws_id'  => $workspaceId,
                        'period' => $period,
                        'ws_id2' => $workspaceId,
                    ],
                    $channelBindings,
                )
            );
        }

        // ── Build indexed structures ───────────────────────────────────────────
        $cohortSizes = [];
        $cohortMap   = [];
        $allOffsets  = [];

        foreach ($rows as $row) {
            $acq    = $row->acquisition_month;
            $offset = (int) $row->month_offset;

            if ($offset < 0) {
                continue;
            }

            $allOffsets[$offset] = true;

            $cohortMap[$acq][$offset] = [
                'revenue'   => (float) $row->revenue,
                'orders'    => (int)   $row->orders_count,
                'customers' => (int)   $row->customers_count,
            ];

            if ($offset === 0) {
                $cohortSizes[$acq] = (int) $row->customers_count;
            }
        }

        ksort($cohortMap);
        ksort($allOffsets);
        $maxOffset = $allOffsets ? max(array_keys($allOffsets)) : 0;

        $acquisitionMonths = array_keys($cohortMap);

        // ── Heatmap rows ──────────────────────────────────────────────────────
        $nowMonth    = date('Y-m-01');
        $heatmapRows = [];

        foreach ($acquisitionMonths as $acqMonth) {
            $size  = $cohortSizes[$acqMonth] ?? 0;
            $cells = [];

            for ($offset = 0; $offset <= $maxOffset; $offset++) {
                $calMonth     = date('Y-m-01', strtotime("+{$offset} months", strtotime($acqMonth)));
                $isFuture     = $calMonth > $nowMonth;
                $isIncomplete = $calMonth === $nowMonth;
                $cell         = $cohortMap[$acqMonth][$offset] ?? null;

                $cells[] = [
                    'offset'        => $offset,
                    'revenue'       => $cell ? $cell['revenue']   : null,
                    'orders'        => $cell ? $cell['orders']    : null,
                    'customers'     => $cell ? $cell['customers'] : null,
                    'is_future'     => $isFuture,
                    'is_incomplete' => $isIncomplete,
                ];
            }

            $heatmapRows[] = [
                'acquisition_month' => $acqMonth,
                'label'             => date('M Y', strtotime($acqMonth)),
                'size'              => $size,
                'low_confidence'    => $size < self::LOW_CONFIDENCE_THRESHOLD,
                'cells'             => $cells,
            ];
        }

        // ── Curve series ──────────────────────────────────────────────────────
        $curveSeries = [];

        foreach ($acquisitionMonths as $acqMonth) {
            $points         = [];
            $cumulRevenue   = 0.0;
            $cumulOrders    = 0;
            $cumulCustomers = 0;

            for ($offset = 0; $offset <= $maxOffset; $offset++) {
                $cell = $cohortMap[$acqMonth][$offset] ?? null;

                if ($cell) {
                    $cumulRevenue   += $cell['revenue'];
                    $cumulOrders    += $cell['orders'];
                    $cumulCustomers  = max($cumulCustomers, $cell['customers']);
                }

                $calMonth = date('Y-m-01', strtotime("+{$offset} months", strtotime($acqMonth)));
                $isFuture = $calMonth > $nowMonth;

                if ($isFuture && ! $cell) {
                    break;
                }

                $points[] = [
                    'offset' => $offset,
                    'value'  => match ($metric) {
                        'orders'    => $cumulOrders,
                        'customers' => $cumulCustomers,
                        default     => round($cumulRevenue, 2),
                    },
                ];
            }

            if (count($points) > 0) {
                $curveSeries[] = [
                    'acquisition_month' => $acqMonth,
                    'label'             => date('M Y', strtotime($acqMonth)),
                    'size'              => $cohortSizes[$acqMonth] ?? 0,
                    'points'            => $points,
                ];
            }
        }

        // ── Pacing ────────────────────────────────────────────────────────────
        $pacing = $this->buildPacing($acquisitionMonths, $cohortMap, $metric, $nowMonth, $maxOffset);

        // ── Summary KPIs ──────────────────────────────────────────────────────
        $avgCohortSize = count($cohortSizes) > 0
            ? array_sum($cohortSizes) / count($cohortSizes)
            : 0;

        $bestCohort = null;
        $bestM1Rate = 0.0;
        foreach ($acquisitionMonths as $acqMonth) {
            $size = $cohortSizes[$acqMonth] ?? 0;
            $m1   = $cohortMap[$acqMonth][1]['customers'] ?? 0;
            if ($size > 0 && ($m1 / $size) > $bestM1Rate) {
                $bestM1Rate = $m1 / $size;
                $bestCohort = date('M Y', strtotime($acqMonth));
            }
        }

        return [
            'heatmap_rows'             => $heatmapRows,
            'curve_series'             => $curveSeries,
            'pacing'                   => $pacing,
            'max_offset'               => $maxOffset,
            'summary'                  => [
                'cohort_count'     => count($acquisitionMonths),
                'avg_cohort_size'  => round($avgCohortSize, 0),
                'best_m1_cohort'   => $bestCohort,
                'best_m1_rate_pct' => round($bestM1Rate * 100, 1),
            ],
            'available_channels'       => $availableChannels,
            'low_confidence_threshold' => self::LOW_CONFIDENCE_THRESHOLD,
            'cohort_filters'           => [
                'period'  => $period,
                'metric'  => $metric,
                'view'    => $view,
                'channel' => $channel,
            ],
        ];
    }

    /**
     * Build the pacing comparison: newest cohort vs average of last 6 cohorts.
     *
     * @param  string[]  $acquisitionMonths  sorted ascending
     * @param  array     $cohortMap
     * @param  string    $metric
     * @param  string    $nowMonth
     * @param  int       $maxOffset
     * @return array{current: list, average: list, current_label: string|null}
     */
    private function buildPacing(
        array  $acquisitionMonths,
        array  $cohortMap,
        string $metric,
        string $nowMonth,
        int    $maxOffset,
    ): array {
        if (count($acquisitionMonths) < 2) {
            return ['current' => [], 'average' => [], 'current_label' => null];
        }

        $currentAcq   = end($acquisitionMonths);
        $currentLabel = date('M Y', strtotime($currentAcq));
        $baselineAcqs = array_slice($acquisitionMonths, max(0, count($acquisitionMonths) - 7), 6);

        $current = [];
        $cumul   = 0.0;
        for ($offset = 0; $offset <= $maxOffset; $offset++) {
            $calMonth = date('Y-m-01', strtotime("+{$offset} months", strtotime($currentAcq)));
            if ($calMonth > $nowMonth) {
                break;
            }
            $cell   = $cohortMap[$currentAcq][$offset] ?? null;
            $cumul += $cell ? $this->metricValue($cell, $metric) : 0;
            $current[] = ['offset' => $offset, 'value' => round($cumul, 2)];
        }

        $average = [];
        for ($offset = 0; $offset <= $maxOffset; $offset++) {
            $sum   = 0.0;
            $count = 0;

            foreach ($baselineAcqs as $baseAcq) {
                $cumulBase = 0.0;
                for ($i = 0; $i <= $offset; $i++) {
                    $cell = $cohortMap[$baseAcq][$i] ?? null;
                    $cumulBase += $cell ? $this->metricValue($cell, $metric) : 0;
                }
                if (isset($cohortMap[$baseAcq][$offset])) {
                    $sum += $cumulBase;
                    $count++;
                }
            }

            if ($count === 0) {
                break;
            }
            $average[] = ['offset' => $offset, 'value' => round($sum / $count, 2)];
        }

        return [
            'current'       => $current,
            'average'       => $average,
            'current_label' => $currentLabel,
        ];
    }

    private function metricValue(array $cell, string $metric): float
    {
        return match ($metric) {
            'orders'    => (float) ($cell['orders']    ?? 0),
            'customers' => (float) ($cell['customers'] ?? 0),
            default     => (float) ($cell['revenue']   ?? 0),
        };
    }
}
