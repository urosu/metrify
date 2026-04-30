<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\WorkspaceContext;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DashboardController — Wave 1 rebuild.
 *
 * Renders the cross-channel command center at GET /{workspace:slug}/.
 * Serves realistic mock data shaped for the new six-source thesis layout:
 *   TrustBar · KpiGrid (8 MetricCardDetail) · TodaySoFar · Revenue trend
 *   · Targets row · ActivityFeed · AlertBanners
 *
 * All values are mock — no DB queries in this stub.
 * Real data wiring follows in the backend layer (L3 of PLANNING.md).
 *
 * @see docs/pages/dashboard.md
 * @see docs/UX.md §5.14 TrustBar
 * @see docs/UX.md §5.1 MetricCard
 * @see docs/planning/backend.md
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $workspace   = Workspace::withoutGlobalScopes()->findOrFail($workspaceId);

        $now = Carbon::now();

        // ── Comparison mode detection
        // URL params: compare_from, compare_to (set by DateRangePicker → useDateRange)
        $cmpFrom = $request->query('compare_from');
        $cmpTo   = $request->query('compare_to');
        $hasComparison = !empty($cmpFrom) && !empty($cmpTo);

        // Derive human-readable comparison label
        $comparisonLabel = null;
        if ($hasComparison) {
            $primaryFrom = $request->query('from', $now->copy()->subDays(6)->format('Y-m-d'));
            $primaryTo   = $request->query('to', $now->format('Y-m-d'));
            $pf = Carbon::parse($primaryFrom);
            $pt = Carbon::parse($primaryTo);
            $cf = Carbon::parse($cmpFrom);

            $primaryDays = $pf->diffInDays($pt) + 1;
            // YoY: compare start is exactly one year before primary start
            if ($cf->year === $pf->year - 1 && $cf->month === $pf->month && $cf->day === $pf->day) {
                $comparisonLabel = 'vs prior year';
            } elseif ($cf->diffInDays($pf) === $primaryDays) {
                $comparisonLabel = 'vs prior period';
            } else {
                $comparisonLabel = 'vs ' . Carbon::parse($cmpFrom)->format('M j') . '–' . Carbon::parse($cmpTo)->format('M j');
            }
        }

        // ── Trend chart: 14 daily points (Real vs Store vs Platforms blended)
        $trend = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->format('Y-m-d');
            $base = 15000 + rand(-2000, 4000);
            $trend[] = [
                'date'      => $date,
                'real'      => $base,
                'store'     => (int) ($base * 0.95),
                'facebook'  => (int) ($base * 1.18),
                'google'    => (int) ($base * 0.72),
            ];
        }

        // ── Comparison trend: prior 14 days aligned by index (index 0 = 14 days before primary start)
        // Revenue comparison values ~13% lower to reflect the mock "prior period" baseline.
        $comparisonTrend = null;
        if ($hasComparison) {
            $comparisonTrend = [];
            for ($i = 13; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i + 14)->format('Y-m-d');
                $base = 13000 + rand(-1500, 3000);  // prior period is ~13% lower on average
                $comparisonTrend[] = [
                    'date' => $date,
                    'real' => $base,
                ];
            }
        }

        // ── Today hourly data (0 – current hour)
        $currentHour = (int) $now->format('G');
        $hourly = [];
        for ($h = 0; $h <= $currentHour; $h++) {
            $hourly[] = [
                'hour'    => $h,
                'revenue' => rand(400, 2800),
            ];
        }
        $todayRevenue = array_sum(array_column($hourly, 'revenue'));

        // ── Comparison sparklines for KPI cards (prior-period mock values ~13% lower)
        // Populated only when comparison mode is active. Aligned index-for-index with primary sparklines.
        $cmpSparklines = $hasComparison ? [
            'revenue' => array_map(fn($v) => ['value' => $v], [88000, 94500, 99800, 103000, 105800, 107000, 108210]),
            'profit'  => array_map(fn($v) => ['value' => $v], [27000, 29000, 31000, 32000, 32800, 33400, 33600]),
            'orders'  => array_map(fn($v) => ['value' => $v], [820, 890, 960, 1020, 1060, 1080, 1096]),
            'aov'     => array_map(fn($v) => ['value' => $v], [88, 90, 91, 92, 93, 94, 95]),
            'roas'    => array_map(fn($v) => ['value' => $v], [4.2, 4.1, 4.05, 4.0, 3.97, 3.95, 3.92]),
            'mer'     => array_map(fn($v) => ['value' => $v], [4.5, 4.35, 4.2, 4.1, 4.05, 4.0, 3.97]),
            'cac'     => array_map(fn($v) => ['value' => $v], [36, 37, 38, 38, 39, 40, 40]),
            'cvr'     => array_map(fn($v) => ['value' => $v], [2.3, 2.4, 2.5, 2.55, 2.58, 2.62, 2.65]),
        ] : null;

        return Inertia::render('Dashboard', [
            'trust_bar' => [
                'revenue' => [
                    ['source' => 'real',     'value' => 124530.42, 'formatted' => '$124,530', 'available' => true],
                    ['source' => 'store',    'value' => 118200.10, 'formatted' => '$118,200', 'available' => true],
                    ['source' => 'facebook', 'value' => 148900.00, 'formatted' => '$148,900', 'available' => true],
                    ['source' => 'google',   'value' => 89400.55,  'formatted' => '$89,401',  'available' => true],
                    ['source' => 'gsc',      'value' => null,      'formatted' => 'N/A',      'available' => false],
                    ['source' => 'ga4',      'value' => 117800.00, 'formatted' => '$117,800', 'available' => true],
                ],
                'real_revenue'   => ['value' => 124530.42, 'formatted' => '$124,530'],
                'not_tracked'    => ['value' => -6369.58,  'formatted' => '-$6,370'],
                'orders'         => 1249,
                'confidence'     => 'high',
            ],

            'kpis' => [
                [
                    'name'         => 'Revenue',
                    'qualifier'    => '7d',
                    'value'        => '$124,530',
                    'delta_pct'    => 12.4,
                    'delta_period' => $comparisonLabel ?? 'vs prior 7d',
                    'sparkline'    => array_map(fn($v) => ['value' => $v],
                        [102000, 108500, 115200, 119800, 123100, 124000, 124530]),
                    'comparison_sparkline' => $cmpSparklines['revenue'] ?? null,
                    'comparison_value'     => $hasComparison ? 108210.10 : null,
                    'comparison_label'     => $comparisonLabel,
                    'sources' => [
                        ['source' => 'real',     'value' => 124530.42, 'available' => true],
                        ['source' => 'store',    'value' => 118200.10, 'available' => true],
                        ['source' => 'facebook', 'value' => 148900.00, 'available' => true],
                        ['source' => 'google',   'value' => 89400.55,  'available' => true],
                        ['source' => 'gsc',      'value' => null,      'available' => false],
                        ['source' => 'ga4',      'value' => 117800.00, 'available' => true],
                    ],
                    'confidence'     => 'high',
                    'disagreement_pct' => 5.4,
                ],
                [
                    'name'         => 'Profit',
                    'qualifier'    => '7d',
                    'value'        => '$38,204',
                    'delta_pct'    => 8.1,
                    'delta_period' => $comparisonLabel ?? 'vs prior 7d',
                    'sparkline'    => array_map(fn($v) => ['value' => $v],
                        [31000, 33200, 35800, 36900, 37200, 38000, 38204]),
                    'comparison_sparkline' => $cmpSparklines['profit'] ?? null,
                    'comparison_value'     => $hasComparison ? 33600.00 : null,
                    'comparison_label'     => $comparisonLabel,
                    'sources' => [
                        ['source' => 'real',     'value' => 38204.00, 'available' => true],
                        ['source' => 'store',    'value' => 36100.00, 'available' => true],
                        ['source' => 'facebook', 'value' => null,     'available' => false],
                        ['source' => 'google',   'value' => null,     'available' => false],
                        ['source' => 'gsc',      'value' => null,     'available' => false],
                        ['source' => 'ga4',      'value' => null,     'available' => false],
                    ],
                    'confidence'     => 'high',
                    'disagreement_pct' => null,
                ],
                [
                    'name'         => 'Orders',
                    'qualifier'    => '7d',
                    'value'        => '1,249',
                    'delta_pct'    => 6.2,
                    'delta_period' => $comparisonLabel ?? 'vs prior 7d',
                    'sparkline'    => array_map(fn($v) => ['value' => $v],
                        [940, 1020, 1100, 1180, 1220, 1240, 1249]),
                    'comparison_sparkline' => $cmpSparklines['orders'] ?? null,
                    'comparison_value'     => $hasComparison ? 1096 : null,
                    'comparison_label'     => $comparisonLabel,
                    'sources' => [
                        ['source' => 'real',     'value' => 1249, 'available' => true],
                        ['source' => 'store',    'value' => 1249, 'available' => true],
                        ['source' => 'facebook', 'value' => 1198, 'available' => true],
                        ['source' => 'google',   'value' => 1221, 'available' => true],
                        ['source' => 'gsc',      'value' => null, 'available' => false],
                        ['source' => 'ga4',      'value' => 1239, 'available' => true],
                    ],
                    'confidence'     => 'high',
                    'disagreement_pct' => null,
                ],
                [
                    'name'         => 'AOV',
                    'qualifier'    => '7d',
                    'value'        => '$99.70',
                    'delta_pct'    => 3.1,
                    'delta_period' => $comparisonLabel ?? 'vs prior 7d',
                    'sparkline'    => array_map(fn($v) => ['value' => $v],
                        [92, 94, 96, 97, 98, 99, 100]),
                    'comparison_sparkline' => $cmpSparklines['aov'] ?? null,
                    'comparison_value'     => $hasComparison ? 95.00 : null,
                    'comparison_label'     => $comparisonLabel,
                    'sources' => [
                        ['source' => 'real',     'value' => 99.70, 'available' => true],
                        ['source' => 'store',    'value' => 94.61, 'available' => true],
                        ['source' => 'facebook', 'value' => null,  'available' => false],
                        ['source' => 'google',   'value' => null,  'available' => false],
                        ['source' => 'gsc',      'value' => null,  'available' => false],
                        ['source' => 'ga4',      'value' => 95.08, 'available' => true],
                    ],
                    'confidence'     => 'high',
                    'disagreement_pct' => 5.4,
                ],
                [
                    'name'         => 'ROAS',
                    'qualifier'    => '7d, blended',
                    'value'        => '3.84x',
                    'delta_pct'    => -4.2,
                    'delta_period' => $comparisonLabel ?? 'vs prior 7d',
                    'sparkline'    => array_map(fn($v) => ['value' => $v],
                        [4.1, 4.0, 3.95, 3.9, 3.88, 3.85, 3.84]),
                    'comparison_sparkline' => $cmpSparklines['roas'] ?? null,
                    'comparison_value'     => $hasComparison ? 3.92 : null,
                    'comparison_label'     => $comparisonLabel,
                    'sources' => [
                        ['source' => 'real',     'value' => 3.84, 'available' => true],
                        ['source' => 'store',    'value' => null, 'available' => false],
                        ['source' => 'facebook', 'value' => 4.61, 'available' => true],
                        ['source' => 'google',   'value' => 2.70, 'available' => true],
                        ['source' => 'gsc',      'value' => null, 'available' => false],
                        ['source' => 'ga4',      'value' => null, 'available' => false],
                    ],
                    'confidence'     => 'high',
                    'disagreement_pct' => null,
                    'invert_trend'   => false,
                ],
                [
                    'name'         => 'MER',
                    'qualifier'    => '7d',
                    'value'        => '3.84x',
                    'delta_pct'    => -2.8,
                    'delta_period' => $comparisonLabel ?? 'vs prior 7d',
                    'sparkline'    => array_map(fn($v) => ['value' => $v],
                        [4.2, 4.1, 4.0, 3.96, 3.92, 3.87, 3.84]),
                    'comparison_sparkline' => $cmpSparklines['mer'] ?? null,
                    'comparison_value'     => $hasComparison ? 3.97 : null,
                    'comparison_label'     => $comparisonLabel,
                    'sources' => [
                        ['source' => 'real',     'value' => 3.84, 'available' => true],
                        ['source' => 'store',    'value' => 3.65, 'available' => true],
                        ['source' => 'facebook', 'value' => null, 'available' => false],
                        ['source' => 'google',   'value' => null, 'available' => false],
                        ['source' => 'gsc',      'value' => null, 'available' => false],
                        ['source' => 'ga4',      'value' => null, 'available' => false],
                    ],
                    'confidence'     => 'high',
                    'disagreement_pct' => null,
                    'expanded_label' => 'MER — Marketing Efficiency Ratio',
                ],
                [
                    'name'         => 'CAC',
                    'qualifier'    => '7d, 1st Time',
                    'value'        => '$42.18',
                    'delta_pct'    => 3.9,
                    'delta_period' => $comparisonLabel ?? 'vs prior 7d',
                    'sparkline'    => array_map(fn($v) => ['value' => $v],
                        [38, 39, 40, 41, 41, 42, 42]),
                    'comparison_sparkline' => $cmpSparklines['cac'] ?? null,
                    'comparison_value'     => $hasComparison ? 40.00 : null,
                    'comparison_label'     => $comparisonLabel,
                    'sources' => [
                        ['source' => 'real',     'value' => 42.18, 'available' => true],
                        ['source' => 'store',    'value' => null,  'available' => false],
                        ['source' => 'facebook', 'value' => 51.20, 'available' => true],
                        ['source' => 'google',   'value' => 38.80, 'available' => true],
                        ['source' => 'gsc',      'value' => null,  'available' => false],
                        ['source' => 'ga4',      'value' => null,  'available' => false],
                    ],
                    'confidence'     => 'high',
                    'disagreement_pct' => null,
                    'invert_trend'   => true,
                ],
                [
                    'name'         => 'CVR',
                    'qualifier'    => '7d',
                    'value'        => '2.84%',
                    'delta_pct'    => 0.9,
                    'delta_period' => $comparisonLabel ?? 'vs prior 7d',
                    'sparkline'    => array_map(fn($v) => ['value' => $v],
                        [2.5, 2.6, 2.7, 2.75, 2.78, 2.82, 2.84]),
                    'comparison_sparkline' => $cmpSparklines['cvr'] ?? null,
                    'comparison_value'     => $hasComparison ? 2.65 : null,
                    'comparison_label'     => $comparisonLabel,
                    'sources' => [
                        ['source' => 'real',     'value' => 2.84, 'available' => true],
                        ['source' => 'store',    'value' => 2.68, 'available' => true],
                        ['source' => 'facebook', 'value' => null, 'available' => false],
                        ['source' => 'google',   'value' => null, 'available' => false],
                        ['source' => 'gsc',      'value' => null, 'available' => false],
                        ['source' => 'ga4',      'value' => 2.71, 'available' => true],
                    ],
                    'confidence'     => 'medium',
                    'disagreement_pct' => null,
                ],
            ],

            'today_so_far' => [
                'revenue'           => $todayRevenue,
                'revenue_formatted' => '$' . number_format($todayRevenue),
                'orders'            => rand(82, 140),
                'projected_revenue'           => (int) ($todayRevenue * (24 / max($currentHour, 1))),
                'projected_revenue_formatted' => '$' . number_format((int) ($todayRevenue * (24 / max($currentHour, 1)))),
                'hourly_data'       => $hourly,
                'baseline_revenue'  => 21400,
            ],

            'trend'            => $trend,
            'comparison_trend' => $comparisonTrend,
            'comparison_label' => $comparisonLabel,

            'targets' => [
                [
                    'label'    => 'Monthly Revenue',
                    'metric'   => 'revenue',
                    'current'  => 387200,
                    'target'   => 500000,
                    'unit'     => 'USD',
                    'deadline' => $now->copy()->endOfMonth()->format('Y-m-d'),
                    'status'   => 'at_risk',
                ],
                [
                    'label'    => 'Blended ROAS',
                    'metric'   => 'roas',
                    'current'  => 3.84,
                    'target'   => 4.00,
                    'unit'     => 'x',
                    'deadline' => $now->copy()->endOfMonth()->format('Y-m-d'),
                    'status'   => 'at_risk',
                ],
                [
                    'label'    => 'New Customers',
                    'metric'   => 'new_customers',
                    'current'  => 428,
                    'target'   => 600,
                    'unit'     => '',
                    'deadline' => $now->copy()->endOfMonth()->format('Y-m-d'),
                    'status'   => 'at_risk',
                ],
            ],

            'activity' => [
                [
                    'id'         => 1,
                    'type'       => 'order',
                    'title'      => 'New order #10482',
                    'subtitle'   => 'j***@gmail.com · Facebook',
                    'value'      => '$148.00',
                    'timestamp'  => $now->copy()->subSeconds(42)->toISOString(),
                ],
                [
                    'id'         => 2,
                    'type'       => 'order',
                    'title'      => 'New order #10481',
                    'subtitle'   => 's***@hotmail.com · Google',
                    'value'      => '$89.95',
                    'timestamp'  => $now->copy()->subMinutes(3)->toISOString(),
                ],
                [
                    'id'         => 3,
                    'type'       => 'order',
                    'title'      => 'New order #10480',
                    'subtitle'   => 'm***@outlook.com · Direct',
                    'value'      => '$224.50',
                    'timestamp'  => $now->copy()->subMinutes(7)->toISOString(),
                ],
                [
                    'id'         => 4,
                    'type'       => 'refund',
                    'title'      => 'Refund processed #10471',
                    'subtitle'   => 'a***@gmail.com',
                    'value'      => '-$89.95',
                    'timestamp'  => $now->copy()->subMinutes(18)->toISOString(),
                ],
                [
                    'id'         => 5,
                    'type'       => 'order',
                    'title'      => 'New order #10479',
                    'subtitle'   => 'k***@yahoo.com · Facebook',
                    'value'      => '$312.00',
                    'timestamp'  => $now->copy()->subMinutes(22)->toISOString(),
                ],
                [
                    'id'         => 6,
                    'type'       => 'sync',
                    'title'      => 'Facebook Ads synced',
                    'subtitle'   => 'Ad insights updated — last 3 days refreshed',
                    'value'      => null,
                    'timestamp'  => $now->copy()->subMinutes(30)->toISOString(),
                ],
                [
                    'id'         => 7,
                    'type'       => 'order',
                    'title'      => 'New order #10478',
                    'subtitle'   => 'p***@gmail.com · Google',
                    'value'      => '$67.00',
                    'timestamp'  => $now->copy()->subMinutes(41)->toISOString(),
                ],
                [
                    'id'         => 8,
                    'type'       => 'alert',
                    'title'      => 'CAC up 12% vs 7-day avg',
                    'subtitle'   => 'Facebook CPA crossed threshold',
                    'value'      => null,
                    'timestamp'  => $now->copy()->subMinutes(55)->toISOString(),
                ],
                [
                    'id'         => 9,
                    'type'       => 'order',
                    'title'      => 'New order #10477',
                    'subtitle'   => 'r***@gmail.com · Direct',
                    'value'      => '$441.00',
                    'timestamp'  => $now->copy()->subHours(1)->toISOString(),
                ],
                [
                    'id'         => 10,
                    'type'       => 'order',
                    'title'      => 'New order #10476',
                    'subtitle'   => 't***@gmail.com · GA4',
                    'value'      => '$55.00',
                    'timestamp'  => $now->copy()->subHours(1)->subMinutes(12)->toISOString(),
                ],
            ],

            'alerts' => [
                [
                    'id'           => 1,
                    'type'         => 'anomaly',
                    'title'        => 'Unattributed orders: $6,370',
                    'description'  => '$6,370 in sales this period has no tracked source. Connect ads integrations or check UTM coverage.',
                    'severity'     => 'info',
                    'created_at'   => $now->copy()->subHours(2)->toISOString(),
                    'action_href'  => '/attribution',
                    'action_label' => 'View attribution',
                ],
                [
                    'id'           => 2,
                    'type'         => 'anomaly',
                    'title'        => 'CAC rose 12% vs 7-day average',
                    'description'  => 'Facebook CPA is $51.20 today vs $45.70 average. Check creative fatigue.',
                    'severity'     => 'warning',
                    'created_at'   => $now->copy()->subHours(3)->toISOString(),
                    'action_href'  => '/ads',
                    'action_label' => 'View campaigns',
                ],
                [
                    'id'           => 3,
                    'type'         => 'sync_failure',
                    'title'        => 'GSC not connected',
                    'description'  => 'Search Console data is unavailable. Connect GSC to see organic traffic attribution.',
                    'severity'     => 'info',
                    'created_at'   => $now->copy()->subDays(1)->toISOString(),
                    'action_href'  => '/integrations',
                    'action_label' => 'Connect GSC',
                ],
            ],
        ]);
    }

    /**
     * Stub kept so the legacy route registered in web.php doesn't 500.
     * Wave 1 stub — the new Dashboard no longer uses the inflation banner.
     */
    public function dismissNotTrackedBanner(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['ok' => true]);
    }
}
