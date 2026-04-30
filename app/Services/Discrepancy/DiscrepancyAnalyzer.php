<?php

declare(strict_types=1);

namespace App\Services\Discrepancy;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Analyzes revenue discrepancies across sources for a workspace and date range.
 *
 * Reads daily_snapshots for per-source attributed revenue and daily_source_disagreements
 * for platform-vs-store deltas. Returns a DiscrepancyResult DTO.
 *
 * Called by: App\Http\Controllers\DiscrepancyController
 * Reads:     daily_snapshots, daily_source_disagreements
 * Writes:    nothing (pure read service)
 *
 * Detection rules (v1 simple thresholds):
 *   - iOS/ATT: if facebook_attributed > store_revenue * 0.60 and store_revenue > 1000
 *              → Facebook is likely over-reporting due to view-through or modeled conversions.
 *   - View-through: if facebook delta_pct > 30% → view-through window likely enabled.
 *   - Cross-device: if ga4_attributed and |ga4_attributed - store_revenue| / store_revenue > 0.25
 *                   → GA4 cross-device journey misalignment.
 *   - Store-only: if real_revenue > 0 and declared_total / real_revenue < 0.5
 *                 → Large unattributed share → POS / manual / subscription orders.
 */
final class DiscrepancyAnalyzer
{
    /**
     * Analyze discrepancies for the given workspace and date range.
     *
     * @param int    $workspaceId
     * @param Carbon $from
     * @param Carbon $to
     *
     * @return DiscrepancyResult
     */
    public function analyze(int $workspaceId, Carbon $from, Carbon $to): DiscrepancyResult
    {
        $fromStr = $from->toDateString();
        $toStr   = $to->toDateString();

        // ── 1. Daily breakdown from daily_snapshots ───────────────────────────
        $dailyRows = DB::select("
            SELECT
                date::text                                                           AS date,
                SUM(revenue)                                                         AS store_revenue,
                SUM(COALESCE(revenue_facebook_attributed, 0))                       AS facebook_revenue,
                SUM(COALESCE(revenue_google_attributed, 0))                         AS google_revenue,
                SUM(COALESCE(revenue_ga4_attributed, 0))                            AS ga4_revenue,
                SUM(COALESCE(revenue_gsc_attributed, 0))                            AS gsc_revenue,
                SUM(COALESCE(revenue_real_attributed, 0))                           AS real_revenue
            FROM daily_snapshots
            WHERE workspace_id = :workspace_id
              AND date BETWEEN :from AND :to
            GROUP BY date
            ORDER BY date ASC
        ", [
            'workspace_id' => $workspaceId,
            'from'         => $fromStr,
            'to'           => $toStr,
        ]);

        // ── 2. Summary aggregates ─────────────────────────────────────────────
        $summary = DB::selectOne("
            SELECT
                SUM(revenue)                                                         AS store_revenue,
                SUM(COALESCE(revenue_facebook_attributed, 0))                       AS facebook_revenue,
                SUM(COALESCE(revenue_google_attributed, 0))                         AS google_revenue,
                SUM(COALESCE(revenue_ga4_attributed, 0))                            AS ga4_revenue,
                SUM(COALESCE(revenue_gsc_attributed, 0))                            AS gsc_revenue,
                SUM(COALESCE(revenue_real_attributed, 0))                           AS real_revenue,
                SUM(COALESCE(revenue_direct_attributed, 0))                         AS direct_revenue,
                SUM(COALESCE(revenue_organic_attributed, 0))                        AS organic_revenue,
                SUM(COALESCE(revenue_email_attributed, 0))                          AS email_revenue
            FROM daily_snapshots
            WHERE workspace_id = :workspace_id
              AND date BETWEEN :from AND :to
        ", [
            'workspace_id' => $workspaceId,
            'from'         => $fromStr,
            'to'           => $toStr,
        ]);

        // ── 3. Platform disagreement data ─────────────────────────────────────
        $disagreements = DB::select("
            SELECT
                channel,
                SUM(store_claim)                 AS store_claim,
                SUM(platform_claim)              AS platform_claim,
                SUM(real_revenue)                AS real_revenue,
                SUM(delta_abs)                   AS delta_abs,
                CASE
                    WHEN SUM(store_claim) = 0 THEN NULL
                    ELSE ROUND(
                        (SUM(COALESCE(platform_claim, 0)) - SUM(store_claim))
                        / NULLIF(SUM(store_claim), 0) * 100,
                        2
                    )
                END                              AS delta_pct
            FROM daily_source_disagreements
            WHERE workspace_id = :workspace_id
              AND date BETWEEN :from AND :to
            GROUP BY channel
            ORDER BY SUM(store_claim) DESC
        ", [
            'workspace_id' => $workspaceId,
            'from'         => $fromStr,
            'to'           => $toStr,
        ]);

        // ── 4. Build daily rows ───────────────────────────────────────────────
        $daily = array_map(function (object $row): array {
            $store       = (float) $row->store_revenue;
            $facebook    = (float) $row->facebook_revenue;
            $google      = (float) $row->google_revenue;
            $ga4         = (float) $row->ga4_revenue;
            $gsc         = (float) $row->gsc_revenue;
            $real        = (float) $row->real_revenue;

            $declared    = $facebook + $google + $ga4 + $gsc;
            $unattributed = $store > 0 ? round($store - $declared, 2) : null;
            $discrepancyPct = $store > 0 ? round(($declared - $store) / $store * 100, 2) : null;

            return [
                'date'             => $row->date,
                'store'            => round($store, 2),
                'facebook'         => round($facebook, 2),
                'google'           => round($google, 2),
                'ga4'              => round($ga4, 2),
                'gsc'              => round($gsc, 2),
                'real'             => round($real, 2),
                'declared_total'   => round($declared, 2),
                'unattributed'     => $unattributed,
                'discrepancy_pct'  => $discrepancyPct,
            ];
        }, $dailyRows);

        // ── 5. Build summary ──────────────────────────────────────────────────
        $storeRev    = (float) ($summary->store_revenue ?? 0);
        $fbRev       = (float) ($summary->facebook_revenue ?? 0);
        $googleRev   = (float) ($summary->google_revenue ?? 0);
        $ga4Rev      = (float) ($summary->ga4_revenue ?? 0);
        $gscRev      = (float) ($summary->gsc_revenue ?? 0);
        $realRev     = (float) ($summary->real_revenue ?? 0);
        $directRev   = (float) ($summary->direct_revenue ?? 0);
        $organicRev  = (float) ($summary->organic_revenue ?? 0);
        $emailRev    = (float) ($summary->email_revenue ?? 0);

        $declaredTotal  = $fbRev + $googleRev + $ga4Rev + $gscRev;
        $unattributed   = $storeRev > 0 ? round($storeRev - $declaredTotal, 2) : 0.0;
        $discrepancyPct = $storeRev > 0
            ? round(($declaredTotal - $storeRev) / $storeRev * 100, 2)
            : null;

        $summaryData = [
            'store'           => round($storeRev, 2),
            'facebook'        => round($fbRev, 2),
            'google'          => round($googleRev, 2),
            'ga4'             => round($ga4Rev, 2),
            'gsc'             => round($gscRev, 2),
            'real'            => round($realRev, 2),
            'direct'          => round($directRev, 2),
            'organic'         => round($organicRev, 2),
            'email'           => round($emailRev, 2),
            'declared_total'  => round($declaredTotal, 2),
            'unattributed'    => round($unattributed, 2),
            'discrepancy_pct' => $discrepancyPct,
        ];

        // ── 6. Detect contributing factors ───────────────────────────────────
        $factors = $this->detectFactors(
            storeRev: $storeRev,
            fbRev: $fbRev,
            ga4Rev: $ga4Rev,
            declaredTotal: $declaredTotal,
            disagreements: $disagreements,
        );

        // ── 7. Platform disagreement rows ─────────────────────────────────────
        $disagreementRows = array_map(fn (object $row): array => [
            'channel'        => $row->channel,
            'store_claim'    => round((float) $row->store_claim, 2),
            'platform_claim' => $row->platform_claim !== null ? round((float) $row->platform_claim, 2) : null,
            'real_revenue'   => round((float) $row->real_revenue, 2),
            'delta_abs'      => $row->delta_abs !== null ? round((float) $row->delta_abs, 2) : null,
            'delta_pct'      => $row->delta_pct !== null ? round((float) $row->delta_pct, 2) : null,
        ], $disagreements);

        return new DiscrepancyResult(
            daily: $daily,
            summary: $summaryData,
            factors: $factors,
            disagreement_rows: $disagreementRows,
        );
    }

    /**
     * Apply detection rules to identify likely contributing factors.
     *
     * @param  float     $storeRev
     * @param  float     $fbRev
     * @param  float     $ga4Rev
     * @param  float     $declaredTotal
     * @param  object[]  $disagreements
     *
     * @return list<array{id: string, title: string, description: string, severity: string, detected: bool}>
     */
    private function detectFactors(
        float $storeRev,
        float $fbRev,
        float $ga4Rev,
        float $declaredTotal,
        array $disagreements,
    ): array {
        // Index disagreements by channel for lookup.
        $dByChannel = [];
        foreach ($disagreements as $row) {
            $dByChannel[$row->channel] = $row;
        }

        $fbDeltaPct = isset($dByChannel['facebook'])
            ? (float) ($dByChannel['facebook']->delta_pct ?? 0)
            : 0.0;

        $factors = [];

        // ── iOS 14.5 / ATT ───────────────────────────────────────────────────
        $iosDetected = $storeRev > 1000 && $fbRev > 0 && $storeRev > 0
            && ($fbRev / $storeRev) > 0.60;

        $factors[] = [
            'id'          => 'ios_att',
            'title'       => 'iOS 14.5 ATT & Signal Loss',
            'description' => 'Apple\'s App Tracking Transparency framework (iOS 14.5+) prevents Facebook from tracking many mobile conversions. Meta compensates with statistical modelling (Aggregated Event Measurement), which can produce over- or under-reporting compared to your actual store orders.',
            'severity'    => 'warning',
            'detected'    => $iosDetected,
            'cta'         => $iosDetected ? ['label' => 'Check attribution settings', 'href' => '/attribution'] : null,
        ];

        // ── View-through attribution window ──────────────────────────────────
        $viewThroughDetected = $fbDeltaPct > 30.0;

        $factors[] = [
            'id'          => 'view_through',
            'title'       => 'View-Through Attribution Window',
            'description' => 'Facebook counts a conversion up to 1 day after an ad impression — even if the customer never clicked. If the "1-day view" window is enabled in your Facebook Ads account events manager, Facebook will claim conversions your store never linked to an ad click, inflating Facebook-attributed revenue vs store revenue.',
            'severity'    => 'warning',
            'detected'    => $viewThroughDetected,
            'cta'         => $viewThroughDetected ? ['label' => 'Review attribution settings', 'href' => '/attribution'] : null,
        ];

        // ── Cross-device journeys (GA4) ───────────────────────────────────────
        $crossDeviceDetected = $ga4Rev > 0 && $storeRev > 0
            && (abs($ga4Rev - $storeRev) / $storeRev) > 0.25;

        $factors[] = [
            'id'          => 'cross_device',
            'title'       => 'Cross-Device Journeys',
            'description' => 'GA4 ties conversions to sessions via client_id (cookie-based), while your store records the order on whichever device completed checkout. When a customer browses on mobile and buys on desktop, GA4 may attribute the sale to a different session or channel than the store recorded.',
            'severity'    => 'info',
            'detected'    => $crossDeviceDetected,
            'cta'         => $crossDeviceDetected ? ['label' => 'Review GA4 integration', 'href' => '/settings/integrations'] : null,
        ];

        // ── Bot & invalid traffic ─────────────────────────────────────────────
        // Always shown as a possible factor — no automated detection signal.
        $factors[] = [
            'id'          => 'bot_traffic',
            'title'       => 'Bot & Invalid Traffic',
            'description' => 'Web analytics tools (GA4, GSC) may count sessions or clicks triggered by bots, crawlers, or click-fraud. This inflates session-based metrics without generating real orders, causing session count → revenue correlation to break.',
            'severity'    => 'info',
            'detected'    => false, // No automated signal — always show as potential.
            'cta'         => null,
        ];

        // ── Store-only / unattributed orders ─────────────────────────────────
        $storeOnlyDetected = $storeRev > 0 && $declaredTotal > 0
            && ($declaredTotal / $storeRev) < 0.50;

        $factors[] = [
            'id'          => 'store_only',
            'title'       => 'Store-Only Orders (POS, Manual, Subscriptions)',
            'description' => 'Point-of-sale orders, manually created orders, and subscription renewal charges are recorded by your store but have no UTM or click data — so they appear in store revenue but not in any ad platform or analytics attribution. This drives up "Unattributed" revenue.',
            'severity'    => 'info',
            'detected'    => $storeOnlyDetected,
            'cta'         => $storeOnlyDetected ? ['label' => 'View channel mappings', 'href' => '/settings/channel-mappings'] : null,
        ];

        // ── Attribution window mismatch ───────────────────────────────────────
        $factors[] = [
            'id'          => 'attribution_window',
            'title'       => 'Attribution Window Mismatch',
            'description' => 'Facebook defaults to a 7-day click + 1-day view window. Google Ads defaults to 30-day click. Your store records the order the moment it occurs. A customer who clicked a Facebook ad 8 days ago will appear in your store revenue but not in Facebook\'s 7-day window — or vice versa for recent orders and long windows.',
            'severity'    => 'info',
            'detected'    => false,
            'cta'         => null,
        ];

        return $factors;
    }
}
