<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Cohorts\CohortDataService;
use App\Services\Customers\CustomersDataService;
use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Customers page — RFM segments, cohort retention, LTV curves, and audiences.
 *
 * Thin controller: all aggregation is delegated to service classes.
 *
 * Tabs:
 *   segments  — RFM grid + segment cards (CustomersDataService)
 *   retention — Full cohort analysis: heatmap, curves, pacing, CSV export (CohortDataService)
 *   ltv       — LTV curves, channel table, LTV drivers (CustomersDataService)
 *
 * Prop shape matches Customers/Index.tsx exactly.
 *
 * Reads: customer_rfm_scores, customers, orders, daily_snapshots (via services).
 * Writes: nothing.
 * Called by: GET /{workspace:slug}/customers
 *
 * @see docs/pages/customers.md
 * @see app/Services/Customers/CustomersDataService.php
 * @see app/Services/Cohorts/CohortDataService.php
 */
class CustomersController extends Controller
{
    public function __construct(
        private readonly CustomersDataService $service,
        private readonly CohortDataService    $cohortService,
    ) {}

    public function __invoke(Request $request): Response|StreamedResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $tab = in_array($request->query('tab'), ['segments', 'retention', 'ltv'], true)
            ? $request->query('tab')
            : 'segments';

        // Source lens: defaults to 'real'; unknown values silently default (no 422).
        $rawSource = $request->query('source', 'real');
        $source    = in_array($rawSource, ['real', 'store', 'facebook', 'google', 'gsc', 'ga4'], true)
            ? $rawSource
            : 'real';

        // ── Retention tab: cohort analysis with optional CSV export ───────────
        if ($tab === 'retention') {
            $period  = max(1, min(36, (int) ($request->query('cohort_period', 12))));
            $metric  = in_array($request->query('cohort_metric', 'revenue'), ['revenue', 'orders', 'customers'], true)
                ? $request->query('cohort_metric', 'revenue')
                : 'revenue';
            $view    = in_array($request->query('cohort_view', 'heatmap'), ['heatmap', 'curves', 'pacing'], true)
                ? $request->query('cohort_view', 'heatmap')
                : 'heatmap';
            $channel = $request->query('cohort_channel', 'all');

            $cohortData = $this->cohortService->build($workspaceId, $period, $metric, $view, $channel);

            // CSV export — mirrors the old /cohorts?export=csv behaviour
            if ($request->query('export') === 'csv') {
                $heatmapRows = $cohortData['heatmap_rows'];
                $maxOffset   = $cohortData['max_offset'];

                return response()->streamDownload(
                    function () use ($heatmapRows, $metric, $maxOffset): void {
                        $handle  = fopen('php://output', 'w');
                        $headers = ['Acquisition Month', 'Cohort Size'];
                        for ($i = 0; $i <= $maxOffset; $i++) {
                            $headers[] = "M{$i}";
                        }
                        fputcsv($handle, $headers);

                        foreach ($heatmapRows as $row) {
                            $line = [$row['label'], $row['size']];
                            foreach ($row['cells'] as $cell) {
                                $line[] = $cell[$metric] ?? '';
                            }
                            fputcsv($handle, $line);
                        }
                        fclose($handle);
                    },
                    "cohorts-{$metric}.csv",
                    ['Content-Type' => 'text/csv'],
                );
            }

            $data = $this->service->forIndex($workspaceId, $tab, $source);

            // Merge cohort-specific props into the shared payload.
            $data['heatmap_rows']             = $cohortData['heatmap_rows'];
            $data['curve_series']             = $cohortData['curve_series'];
            $data['pacing']                   = $cohortData['pacing'];
            $data['max_offset']               = $cohortData['max_offset'];
            $data['cohort_summary']           = $cohortData['summary'];
            $data['available_channels']       = $cohortData['available_channels'];
            $data['low_confidence_threshold'] = $cohortData['low_confidence_threshold'];

            $data['filters'] = [
                'source'         => $source,
                'tab'            => $tab,
                'cohort_period'  => $period,
                'cohort_metric'  => $metric,
                'cohort_view'    => $view,
                'cohort_channel' => $channel,
            ];

            return Inertia::render('Customers/Index', $data);
        }

        // ── Segments / LTV tabs ───────────────────────────────────────────────
        $data = $this->service->forIndex($workspaceId, $tab, $source);

        $data['filters'] = [
            'source'  => $source,
            'tab'     => $tab,
            'segment' => $request->query('segment'),
            'page'    => max(1, (int) $request->query('page', 1)),
        ];

        // ── Segment drill-down (partial reload when segment= is present) ──────
        $segmentSlug = $request->query('segment');
        if ($tab === 'segments' && $segmentSlug !== null && $segmentSlug !== '') {
            $page    = max(1, (int) $request->query('page', 1));
            $perPage = 25;

            $data['segment_drilldown'] = $this->service->segmentDrilldown(
                $workspaceId,
                $segmentSlug,
                $page,
                $perPage,
            );
        } else {
            $data['segment_drilldown'] = null;
        }

        return Inertia::render('Customers/Index', $data);
    }

    /**
     * Return the customer list for a specific cohort × period-offset cell.
     * Used by the cohort heatmap cell-click drawer (Peel pattern).
     *
     * Route: GET /{workspace}/api/customers/cohort-cell
     * Query params:
     *   acquisition_month  YYYY-MM-DD   Cohort acquisition month (first day of month)
     *   offset             int          Months since first purchase (0 = acquisition month)
     *   channel            string       "all" or attribution_source value
     *
     * Returns up to 50 customers with: email (masked), orders in period, revenue in period,
     * top product purchased in this cohort×offset window.
     */
    public function cohortCell(Request $request): JsonResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $acquisitionMonth = $request->query('acquisition_month', '');
        $offset           = max(0, (int) $request->query('offset', 0));
        $channel          = $request->query('channel', 'all');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $acquisitionMonth)) {
            return response()->json(['error' => 'Invalid acquisition_month'], 422);
        }

        // Build channel restriction sub-filter.
        $channelJoin = '';
        $bindings    = [
            'ws_id'  => $workspaceId,
            'ws_id2' => $workspaceId,
            'ws_id3' => $workspaceId,
            'acq'    => $acquisitionMonth,
            'acq2'   => $acquisitionMonth,
            'offset' => $offset,
        ];

        if ($channel !== 'all') {
            $channelJoin    = "AND c.customer_id IN (
                SELECT customer_id FROM orders
                WHERE workspace_id = :ws_ch AND is_first_for_customer = true AND attribution_source = :ch_val
            )";
            $bindings['ws_ch']  = $workspaceId;
            $bindings['ch_val'] = $channel;
        }

        $rows = DB::select(<<<SQL
            WITH acquisitions AS (
                SELECT customer_id
                FROM orders
                WHERE workspace_id = :ws_id
                  AND is_first_for_customer = true
                  AND status NOT IN ('cancelled', 'refunded')
                  AND DATE_TRUNC('month', occurred_at)::date = :acq::date
            ),
            cohort_orders AS (
                SELECT
                    o.customer_id,
                    o.total_in_reporting_currency AS revenue
                FROM orders o
                JOIN acquisitions c ON c.customer_id = o.customer_id
                WHERE o.workspace_id = :ws_id2
                  AND o.status NOT IN ('cancelled', 'refunded')
                  AND ROUND(
                      EXTRACT(EPOCH FROM (
                          DATE_TRUNC('month', o.occurred_at) - DATE_TRUNC('month', :acq2::date)
                      )) / (86400 * 30.44)
                  )::int = :offset
                  {$channelJoin}
            )
            SELECT
                cu.email,
                COUNT(co.customer_id)           AS orders_in_period,
                SUM(co.revenue)                 AS revenue_in_period
            FROM cohort_orders co
            JOIN customers cu ON cu.id = co.customer_id AND cu.workspace_id = :ws_id3
            GROUP BY cu.email
            ORDER BY revenue_in_period DESC
            LIMIT 50
        SQL, $bindings);

        $customers = array_map(static function (object $row): array {
            // Mask email: keep first char + domain.
            $email  = (string) $row->email;
            $at     = strpos($email, '@');
            $masked = $at !== false
                ? substr($email, 0, 1) . '***' . substr($email, $at)
                : '***';

            return [
                'email'             => $masked,
                'orders_in_period'  => (int) $row->orders_in_period,
                'revenue_in_period' => round((float) $row->revenue_in_period, 2),
            ];
        }, $rows);

        return response()->json([
            'acquisition_month' => $acquisitionMonth,
            'offset'            => $offset,
            'customers'         => $customers,
            'total'             => count($customers),
        ]);
    }
}
