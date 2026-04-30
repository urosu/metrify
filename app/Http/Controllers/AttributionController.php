<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Attribution page controller.
 *
 * Renders Pages/Attribution/Index with all data required to show:
 *   - Per-platform revenue overview (Store, Facebook, Google, GA4, GSC cards)
 *   - Per-platform revenue line chart (30-day, 5 lines)
 *   - View as of (historical weekly snapshot scrubber)
 *   - Cross-platform overlap table (secondary, collapsible)
 *   - Tracking Health strip (per-source signal quality)
 *
 * Called by: GET /{workspace}/attribution
 * Reads via: (stub) — wire to RevenueAttributionService once backend services ready.
 * Returns:   Inertia::render('Attribution/Index', <props>)
 *
 * @see resources/js/Pages/Attribution/Index.tsx for the authoritative prop contract
 * @see docs/pages/attribution.md
 * @see docs/UX.md §5.14 TrustBar, §7 Attribution model behavior
 */
class AttributionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        app(WorkspaceContext::class)->id(); // ensures tenant scope is set

        $validated = $request->validate([
            'model'     => ['sometimes', 'string', 'in:last-click,first-click,last-non-direct,linear,data-driven'],
            'window'    => ['sometimes', 'string', 'in:1d,7d,28d,ltv'],
            'mode'      => ['sometimes', 'string', 'in:cash,accrual'],
            'from'      => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to'        => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'breakdown' => ['sometimes', 'nullable', 'string'],
            'source'    => ['sometimes', 'nullable', 'string', 'in:store,facebook,google,gsc,ga4,real'],
        ]);

        // ─── Mock data — stub until backend services are wired ────────────────
        // Production: wire to RevenueAttributionService::reconcile() + SnapshotBuilderService.

        $trustBar = [
            'real_revenue'                  => 124530.42,
            'currency'                      => 'USD',
            'period'                        => $validated['window'] ?? '7d',
            'disagreement_vs_store_pct'     => 5.4,
            'disagreement_vs_platforms_pct' => 19.6,
            // Unattributed bucket: negative means platform total exceeds store total
            'not_tracked_bucket'            => 6369.58,
            'confidence'                    => 'high',
        ];

        $sources = [
            ['source' => 'real',     'value' => 124530.42, 'orders' => 412, 'cac' => 38.20, 'is_reconciled' => true,  'note' => null],
            ['source' => 'store',    'value' => 118200.10, 'orders' => 401, 'cac' => null,  'is_reconciled' => false, 'note' => null],
            ['source' => 'facebook', 'value' => 148900.00, 'orders' => 380, 'cac' => 28.10, 'is_reconciled' => false, 'note' => null],
            ['source' => 'google',   'value' =>  89400.00, 'orders' => 280, 'cac' => 41.50, 'is_reconciled' => false, 'note' => null],
            ['source' => 'ga4',      'value' => 117800.00, 'orders' => 395, 'cac' => null,  'is_reconciled' => false, 'note' => null],
            ['source' => 'gsc',      'value' => null,      'orders' => null,'cac' => null,  'is_reconciled' => false, 'note' => 'Organic search; no revenue attribution'],
        ];

        // 6×6 overlap grid — cell[A][B] = % of orders both source A and source B claimed credit for.
        // Diagonal = 100%. GSC = 0% everywhere (organic; never claims conversion credit).
        // Facebook×Google overlap 22% — different last-click winner for most orders.
        $disagreementMatrix = [
            'real'     => ['real' => 100, 'store' => 94, 'facebook' => 68, 'google' => 52, 'ga4' => 89, 'gsc' => 0],
            'store'    => ['real' => 94,  'store' => 100,'facebook' => 61, 'google' => 49, 'ga4' => 87, 'gsc' => 0],
            'facebook' => ['real' => 68,  'store' => 61, 'facebook' => 100,'google' => 22, 'ga4' => 58, 'gsc' => 0],
            'google'   => ['real' => 52,  'store' => 49, 'facebook' => 22, 'google' => 100,'ga4' => 47, 'gsc' => 0],
            'ga4'      => ['real' => 89,  'store' => 87, 'facebook' => 58, 'google' => 47, 'ga4' => 100,'gsc' => 0],
            'gsc'      => ['real' => 0,   'store' => 0,  'facebook' => 0,  'google' => 0,  'ga4' => 0,  'gsc' => 100],
        ];

        // 30-day daily revenue by source. Real (gold) reconciles between Store (lower) and Facebook (higher).
        $trendSeries = [];
        for ($d = 29; $d >= 0; $d--) {
            $date = now()->subDays($d)->format('Y-m-d');
            $base = 4000 + (int)(sin($d / 3.0) * 300);
            $trendSeries[] = [
                'date'     => $date,
                'real'     => round($base * 1.05 + ($d % 5) * 50, 2),
                'store'    => round($base + ($d % 7) * 20, 2),
                'facebook' => round($base * 1.25 + ($d % 4) * 80, 2),
                'google'   => round($base * 0.72 + ($d % 6) * 30, 2),
                'ga4'      => round($base * 0.98 + ($d % 5) * 25, 2),
                'gsc'      => null,
            ];
        }

        // Time Machine — weekly snapshots, last 12 weeks.
        // Reconstructed from daily_snapshots without re-running RevenueAttributionService.
        $timeMachine = [];
        for ($w = 11; $w >= 0; $w--) {
            $weekDate = now()->subWeeks($w)->startOfWeek()->format('Y-m-d');
            $baseRev  = 115000 + (int)(cos($w / 2.0) * 6000);
            $timeMachine[] = [
                'date'              => $weekDate,
                'real'              => round($baseRev * 1.04, 2),
                'store'             => round($baseRev, 2),
                'facebook'          => round($baseRev * 1.18, 2),
                'google'            => round($baseRev * 0.70, 2),
                'ga4'               => round($baseRev * 0.96, 2),
                'not_tracked'       => round(-($baseRev * 0.09), 2),
                'attribution_model' => $validated['model'] ?? 'last-click',
            ];
        }

        // Top 20 discrepancy orders — sorted by absolute gap (Facebook claim − Store) desc.
        $gapReasons = ['Outside attribution window', 'No click ID', 'Multi-platform attribution', 'Unmatched UTM'];
        $channels   = ['facebook', 'google', 'direct', 'email'];
        $utmSources = ['facebook', 'google', null, 'klaviyo'];
        $topDiscrepancies = [];
        for ($i = 0; $i < 20; $i++) {
            $ordId    = 10001 + $i;
            $storeRev = round(80 + ($i * 18.7) + 0.42, 2);
            $fbClaim  = round($storeRev * (1.15 + ($i % 7) * 0.05), 2);
            $topDiscrepancies[] = [
                'id'               => $ordId,
                'order_number'     => '#' . $ordId,
                'customer'         => 'c***@example.com',
                'revenue_store'    => $storeRev,
                'revenue_real'     => round($storeRev * 1.04, 2),
                'revenue_facebook' => $fbClaim,
                'revenue_google'   => round($storeRev * (0.40 + ($i % 4) * 0.10), 2),
                'revenue_ga4'      => round($storeRev * (0.85 + ($i % 3) * 0.05), 2),
                'gap_abs'          => round($fbClaim - $storeRev, 2),
                'gap_pct'          => round(($fbClaim / $storeRev - 1) * 100, 1),
                'gap_reason'       => $gapReasons[$i % 4],
                'store_channel'    => $channels[$i % 4],
                'utm_source'       => $utmSources[$i % 4],
                'touchpoints'      => 1 + ($i % 6),
                'order_date'       => now()->subDays($i + 1)->format('Y-m-d'),
            ];
        }
        usort($topDiscrepancies, fn ($a, $b) => $b['gap_abs'] <=> $a['gap_abs']);

        // Tracking Health strip — per-source match quality, mirroring Meta EMQ score 0–10.
        $trackingHealth = [
            ['source' => 'store',    'match_quality' => 10.0, 'events_sent' => 401,  'events_matched' => 401,  'consent_denied_pct' => 0.0,  'status' => 'healthy'],
            ['source' => 'facebook', 'match_quality' => 7.2,  'events_sent' => 412,  'events_matched' => 296,  'consent_denied_pct' => 3.1,  'status' => 'warning'],
            ['source' => 'google',   'match_quality' => 8.1,  'events_sent' => 412,  'events_matched' => 334,  'consent_denied_pct' => 2.8,  'status' => 'healthy'],
            ['source' => 'ga4',      'match_quality' => 9.0,  'events_sent' => 412,  'events_matched' => 371,  'consent_denied_pct' => 1.2,  'status' => 'healthy'],
            ['source' => 'gsc',      'match_quality' => null, 'events_sent' => null, 'events_matched' => null, 'consent_denied_pct' => null, 'status' => 'organic'],
        ];

        return Inertia::render('Attribution/Index', [
            'trust_bar'           => $trustBar,
            'sources'             => $sources,
            'source_keys'         => ['real', 'store', 'facebook', 'google', 'ga4', 'gsc'],
            'disagreement_matrix' => $disagreementMatrix,
            'trend_series'        => $trendSeries,
            'time_machine'        => $timeMachine,
            'top_discrepancies'   => [], // dropped from UI
            'tracking_health'     => $trackingHealth,
            'attribution_models'  => ['last-click', 'first-click', 'last-non-direct', 'linear', 'data-driven'],
            'windows'             => ['1d', '7d', '28d', 'ltv'],
            'is_recomputing'      => false,
            'filters'             => [
                'model'     => $validated['model']     ?? 'last-click',
                'window'    => $validated['window']    ?? '7d',
                'mode'      => $validated['mode']      ?? 'cash',
                'from'      => $validated['from']      ?? now()->subDays(6)->toDateString(),
                'to'        => $validated['to']        ?? now()->toDateString(),
                'breakdown' => $validated['breakdown'] ?? null,
                'source'    => $validated['source']    ?? 'real',
            ],
        ]);
    }
}
