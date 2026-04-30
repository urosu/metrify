<?php

declare(strict_types=1);

namespace App\Services\PerformanceMonitoring;

use App\Models\StoreUrl;
use Illuminate\Support\Facades\DB;

/**
 * Assembles the full Inertia props array for PerformanceController.
 *
 * Reads from: store_urls, lighthouse_snapshots, uptime_checks, uptime_daily_summaries,
 *             orders, search_console_properties,
 *             search_console_pages (via PerformanceRevenueService), stores.
 * Writes to:  nothing.
 *
 * Called by: PerformanceController::__invoke()
 *
 * @see docs/pages/performance.md
 * @see docs/planning/backend.md#performance-monitoring
 */
class PerformanceDataService
{
    public function __construct(
        private readonly PerformanceRevenueService $revenue,
    ) {}

    /**
     * Build the full props array for the Performance/Index Inertia page.
     *
     * NOTE: windowDays is passed through and reflected in `filters` but all queries
     * currently use a fixed 30-day / 12-week window. The filter UI works visually;
     * backend windowing is a v2 enhancement.
     *
     * @param  string  $strategy   'mobile' | 'desktop'
     * @param  string  $pageType   'all' | 'home' | 'product' | 'checkout' | 'other'
     * @param  int     $windowDays 30 | 60 | 90 (passed through, not yet applied to queries)
     *
     * @return array{
     *   summary: array,
     *   trend: list<array>,
     *   revenue_at_risk: float,
     *   urls: list<array>,
     *   filters: array,
     *   has_store_urls: bool,
     *   has_crux_data: bool,
     * }
     */
    public function forIndex(int $workspaceId, string $strategy, string $pageType, int $windowDays): array
    {
        // ── 1. Load active StoreUrls ─────────────────────────────────────────
        $storeUrls = StoreUrl::where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->get();

        if ($storeUrls->isEmpty()) {
            return $this->emptyResponse($strategy, $pageType, $windowDays);
        }

        $urlIds      = $storeUrls->pluck('id')->all();
        $storeUrlArr = $storeUrls->map(fn ($u) => ['id' => $u->id, 'url' => $u->url])->all();

        // ── 2. Latest snapshot per URL (DISTINCT ON) ─────────────────────────
        // DISTINCT ON is Postgres-specific: picks the first row per store_url_id
        // after ORDER BY store_url_id, checked_at DESC — giving us the most recent snapshot.
        $placeholders = implode(',', array_fill(0, count($urlIds), '?'));
        $latestRows   = DB::select(
            "SELECT DISTINCT ON (store_url_id) *
             FROM lighthouse_snapshots
             WHERE workspace_id = ?
               AND strategy = ?
               AND store_url_id IN ({$placeholders})
             ORDER BY store_url_id, checked_at DESC",
            [$workspaceId, $strategy, ...$urlIds],
        );

        // Index latest snapshots by store_url_id for O(1) lookup.
        $latestByUrl = [];
        foreach ($latestRows as $row) {
            $latestByUrl[(int) $row->store_url_id] = $row;
        }

        // ── 3. 12-week CrUX trend (workspace-wide median) ────────────────────
        $trendRows = DB::select(
            "SELECT
                 date_trunc('week', checked_at) AS week,
                 PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY crux_lcp_p75_ms) AS lcp_p75,
                 PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY crux_inp_p75_ms) AS inp_p75,
                 PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY crux_cls_p75)    AS cls_p75
             FROM lighthouse_snapshots
             WHERE workspace_id = ?
               AND strategy = ?
               AND checked_at >= NOW() - INTERVAL '12 weeks'
               AND (crux_lcp_p75_ms IS NOT NULL OR crux_inp_p75_ms IS NOT NULL)
             GROUP BY 1
             ORDER BY 1",
            [$workspaceId, $strategy],
        );

        $trend = array_map(fn ($r) => [
            'week'    => date('Y-m-d', strtotime((string) $r->week)),
            'lcp_p75' => $r->lcp_p75 !== null ? (int) round((float) $r->lcp_p75) : null,
            'inp_p75' => $r->inp_p75 !== null ? (int) round((float) $r->inp_p75) : null,
            'cls_p75' => $r->cls_p75 !== null ? round((float) $r->cls_p75, 3)    : null,
        ], $trendRows);

        // ── 4. Revenue data ──────────────────────────────────────────────────
        $monthlyOrders  = $this->revenue->monthlyOrdersPerUrl($workspaceId, $storeUrlArr);
        $riskData       = $this->revenue->revenueAtRisk($workspaceId, $storeUrlArr);
        $cvrCorrelation = $this->revenue->weeklyCvrCorrelation($workspaceId, $windowDays);

        // ── 4b. Uptime data — two batch queries, no N+1 ─────────────────────
        // Query 1: 30-day average uptime_pct per URL from uptime_daily_summaries.
        // Returns null columns when no data exists (new workspace, first check not run).
        $placeholders30d = implode(',', array_fill(0, count($urlIds), '?'));
        $uptimeSummaryRows = DB::select(
            "SELECT store_url_id, AVG(uptime_pct) AS avg_30d_pct
             FROM uptime_daily_summaries
             WHERE workspace_id = ?
               AND store_url_id IN ({$placeholders30d})
               AND date >= CURRENT_DATE - INTERVAL '30 days'
             GROUP BY store_url_id",
            [$workspaceId, ...$urlIds],
        );
        $uptime30dByUrl = [];
        foreach ($uptimeSummaryRows as $row) {
            $uptime30dByUrl[(int) $row->store_url_id] = $row->avg_30d_pct !== null
                ? round((float) $row->avg_30d_pct, 2)
                : null;
        }

        // Query 2: most recent uptime_checks row per URL (is_up + checked_at).
        // DISTINCT ON is Postgres-specific: picks the first row per store_url_id
        // after ORDER BY store_url_id, checked_at DESC.
        $latestUptimeRows = DB::select(
            "SELECT DISTINCT ON (store_url_id) store_url_id, is_up, checked_at
             FROM uptime_checks
             WHERE workspace_id = ?
               AND store_url_id IN ({$placeholders30d})
             ORDER BY store_url_id, checked_at DESC",
            [$workspaceId, ...$urlIds],
        );
        $latestUptimeByUrl = [];
        foreach ($latestUptimeRows as $row) {
            $latestUptimeByUrl[(int) $row->store_url_id] = $row;
        }

        // ── 5. Build per-URL rows ────────────────────────────────────────────
        $allUrlRows     = [];
        $cruxLcpValues  = [];
        $cruxInpValues  = [];
        $cruxClsValues  = [];
        $cruxTtfbValues = [];
        $labScores      = [];

        foreach ($storeUrls as $su) {
            $snap      = $latestByUrl[$su->id] ?? null;
            $pageType_ = $this->classifyPageType($su->url);

            // CrUX values
            $cruxLcp  = $snap?->crux_lcp_p75_ms  !== null ? (int) $snap->crux_lcp_p75_ms  : null;
            $cruxInp  = $snap?->crux_inp_p75_ms  !== null ? (int) $snap->crux_inp_p75_ms  : null;
            $cruxCls  = $snap?->crux_cls_p75      !== null ? round((float) $snap->crux_cls_p75, 3) : null;
            $cruxTtfb = $snap?->crux_ttfb_p75_ms !== null ? (int) $snap->crux_ttfb_p75_ms : null;

            // crux_source: use snap value if present, otherwise null
            $cruxSource = $snap?->crux_source ?? null;

            if ($cruxLcp !== null) {
                $cruxLcpValues[] = $cruxLcp;
            }
            if ($cruxInp !== null) {
                $cruxInpValues[] = $cruxInp;
            }
            if ($cruxCls !== null) {
                $cruxClsValues[] = $cruxCls;
            }
            if ($cruxTtfb !== null) {
                $cruxTtfbValues[] = $cruxTtfb;
            }

            $labScore = $snap?->performance_score !== null ? (int) $snap->performance_score : null;
            if ($labScore !== null) {
                $labScores[] = $labScore;
            }

            // CrUX distributions (Good/NI/Poor %) — read from dedicated columns parsed
            // at write time by PsiClient. Only populated when real CrUX data exists.
            $cruxDistributions = $snap?->crux_source !== null ? [
                'lcp'  => $snap->crux_lcp_good_pct  !== null ? ['good' => (float) $snap->crux_lcp_good_pct,  'ni' => (float) $snap->crux_lcp_ni_pct,  'poor' => (float) $snap->crux_lcp_poor_pct]  : null,
                'inp'  => $snap->crux_inp_good_pct  !== null ? ['good' => (float) $snap->crux_inp_good_pct,  'ni' => (float) $snap->crux_inp_ni_pct,  'poor' => (float) $snap->crux_inp_poor_pct]  : null,
                'cls'  => $snap->crux_cls_good_pct  !== null ? ['good' => (float) $snap->crux_cls_good_pct,  'ni' => (float) $snap->crux_cls_ni_pct,  'poor' => (float) $snap->crux_cls_poor_pct]  : null,
                'ttfb' => $snap->crux_ttfb_good_pct !== null ? ['good' => (float) $snap->crux_ttfb_good_pct, 'ni' => (float) $snap->crux_ttfb_ni_pct, 'poor' => (float) $snap->crux_ttfb_poor_pct] : null,
            ] : null;

            $latestUptime = $latestUptimeByUrl[$su->id] ?? null;

            $allUrlRows[] = [
                'id'              => $su->id,
                'url'             => $su->url,
                'label'           => $su->label,
                'page_type'       => $pageType_,
                'is_homepage'     => (bool) $su->is_homepage,
                'monthly_orders'  => $monthlyOrders[$su->id] ?? 0,
                'revenue_at_risk' => $riskData['per_url'][$su->id] ?? 0.0,
                'crux_lcp_p75'    => $cruxLcp,
                'crux_inp_p75'    => $cruxInp,
                'crux_cls_p75'    => $cruxCls,
                'crux_ttfb_p75_ms' => $cruxTtfb,
                'crux_source'     => $cruxSource,
                'crux_lcp_status' => $this->lcpStatus($cruxLcp),
                'crux_inp_status' => $this->inpStatus($cruxInp),
                'crux_cls_status' => $this->clsStatus($cruxCls),
                'crux_ttfb_status' => $this->ttfbStatus($cruxTtfb),
                'crux_distributions' => $cruxDistributions,
                'lab_performance_score' => $labScore,
                'lab_lcp_ms'      => $snap?->lcp_ms    !== null ? (int) $snap->lcp_ms    : null,
                'lab_inp_ms'      => $snap?->inp_ms    !== null ? (int) $snap->inp_ms    : null,
                'lab_fcp_ms'      => $snap?->fcp_ms    !== null ? (int) $snap->fcp_ms    : null,
                'lab_ttfb_ms'     => $snap?->ttfb_ms   !== null ? (int) $snap->ttfb_ms   : null,
                'lab_tbt_ms'      => $snap?->tbt_ms    !== null ? (int) $snap->tbt_ms    : null,
                'lab_cls_score'   => $snap?->cls_score !== null ? round((float) $snap->cls_score, 3) : null,
                'last_checked_at' => $snap?->checked_at ? (string) $snap->checked_at : null,
                // Uptime fields — null when no probes have run yet for this URL.
                'uptime_30d_pct'         => $uptime30dByUrl[$su->id] ?? null,
                'uptime_last_checked_at' => $latestUptime?->checked_at ? (string) $latestUptime->checked_at : null,
                'uptime_is_up'           => $latestUptime !== null ? (bool) $latestUptime->is_up : null,
            ];
        }

        // Sort all URL rows by revenue_at_risk DESC before applying the page-type filter.
        usort($allUrlRows, fn ($a, $b) => $b['revenue_at_risk'] <=> $a['revenue_at_risk']);

        // ── 6. Site-wide CrUX summary: median of URL-level p75 values ────────
        $summaryLcp      = $this->median($cruxLcpValues);
        $summaryInpFloat = count($cruxInpValues) > 0 ? $this->medianFloat($cruxInpValues) : null;
        $summaryCls      = $this->medianFloat($cruxClsValues);
        $summaryTtfb     = count($cruxTtfbValues) > 0 ? $this->medianFloat($cruxTtfbValues) : null;
        $summaryLabScore = count($labScores) > 0 ? (int) round($this->medianFloat($labScores)) : null;

        $hasCruxData = count($cruxLcpValues) > 0 || count($cruxInpValues) > 0;

        // Determine aggregate crux_source:
        // 'url' if all URLs with CrUX data have source='url', 'origin' if any is 'origin', null if none.
        $summarySource = $this->aggregateCruxSource($allUrlRows);

        $summaryTtfbInt = $summaryTtfb !== null ? (int) round($summaryTtfb) : null;

        $summary = [
            'lcp' => [
                'p75'         => $summaryLcp !== null ? (int) round($summaryLcp) : null,
                'status'      => $this->lcpStatus($summaryLcp !== null ? (int) round($summaryLcp) : null),
                'crux_source' => $summarySource,
            ],
            'inp' => [
                'p75'         => $summaryInpFloat !== null ? (int) round($summaryInpFloat) : null,
                'status'      => $this->inpStatus($summaryInpFloat !== null ? (int) round($summaryInpFloat) : null),
                'crux_source' => $summarySource,
            ],
            'cls' => [
                'p75'         => $summaryCls,
                'status'      => $this->clsStatus($summaryCls),
                'crux_source' => $summarySource,
            ],
            'ttfb' => [
                'p75'         => $summaryTtfbInt,
                'status'      => $this->ttfbStatus($summaryTtfbInt),
                'crux_source' => $summarySource,
            ],
            'lab_performance_score' => $summaryLabScore,
        ];

        // ── 7. Apply pageType filter ─────────────────────────────────────────
        $filteredUrls = $pageType === 'all'
            ? $allUrlRows
            : array_values(array_filter($allUrlRows, fn ($row) => $row['page_type'] === $pageType));

        // ── 8. Suggested URLs (only when fewer than 5 monitored URLs) ────────
        $suggestedUrls = count($urlIds) < 5
            ? $this->suggestedUrls($workspaceId, $storeUrls->pluck('url')->all())
            : [];

        // ── 9. Workspace-wide uptime summary ────────────────────────────────
        // Median 30d uptime across all monitored URLs (nulls excluded from median).
        // any_down: true if any URL's most recent probe returned is_up=false.
        $uptime30dValues  = array_values(array_filter($uptime30dByUrl, fn ($v) => $v !== null));
        $workspaceUptime30d = count($uptime30dValues) > 0
            ? round((float) $this->medianFloat($uptime30dValues), 2)
            : null;

        $anyDown = false;
        foreach ($latestUptimeByUrl as $row) {
            if (! (bool) $row->is_up) {
                $anyDown = true;
                break;
            }
        }

        $uptimeSummary = [
            'avg_30d_pct' => $workspaceUptime30d,
            'any_down'    => count($latestUptimeByUrl) > 0 ? $anyDown : false,
        ];

        return [
            'summary'                     => $summary,
            'trend'                       => array_values($trend),
            'revenue_at_risk'             => $riskData['total'],
            'urls'                        => $filteredUrls,
            'filters'                     => [
                'strategy'    => $strategy,
                'page_type'   => $pageType,
                'window_days' => $windowDays,
            ],
            'has_store_urls'              => true,
            'has_crux_data'               => $hasCruxData,
            'suggested_urls'              => $suggestedUrls,
            'cvr_correlation'             => $cvrCorrelation,
            'cvr_correlation_weeks_needed' => 6,
            'uptime'                      => $uptimeSummary,
        ];
    }

    // ─── Status classifiers ────────────────────────────────────────────────────

    /**
     * CWV LCP status: Good ≤ 2500 ms, Needs Improvement 2500–4000 ms, Poor > 4000 ms.
     * Thresholds from web.dev/lcp; hardcoded per spec.
     */
    private function lcpStatus(?int $ms): ?string
    {
        if ($ms === null) {
            return null;
        }
        if ($ms <= 2500) {
            return 'good';
        }
        if ($ms <= 4000) {
            return 'needs_improvement';
        }

        return 'poor';
    }

    /**
     * CWV INP status: Good ≤ 200 ms, Needs Improvement 200–500 ms, Poor > 500 ms.
     * Thresholds from web.dev/inp; hardcoded per spec.
     */
    private function inpStatus(?int $ms): ?string
    {
        if ($ms === null) {
            return null;
        }
        if ($ms <= 200) {
            return 'good';
        }
        if ($ms <= 500) {
            return 'needs_improvement';
        }

        return 'poor';
    }

    /**
     * CWV CLS status: Good ≤ 0.10, Needs Improvement 0.10–0.25, Poor > 0.25.
     * Thresholds from web.dev/cls; hardcoded per spec.
     */
    private function clsStatus(?float $cls): ?string
    {
        if ($cls === null) {
            return null;
        }
        if ($cls <= 0.10) {
            return 'good';
        }
        if ($cls <= 0.25) {
            return 'needs_improvement';
        }

        return 'poor';
    }

    /**
     * CrUX TTFB status: Good ≤ 800 ms, Needs Improvement 800–1800 ms, Poor > 1800 ms.
     * Thresholds from web.dev/ttfb (EXPERIMENTAL_TIME_TO_FIRST_BYTE); hardcoded per spec.
     */
    private function ttfbStatus(?int $ms): ?string
    {
        if ($ms === null) {
            return null;
        }
        if ($ms <= 800) {
            return 'good';
        }
        if ($ms <= 1800) {
            return 'needs_improvement';
        }

        return 'poor';
    }

    // ─── Page type classification ──────────────────────────────────────────────

    /**
     * Classify a URL into one of four page-type buckets for filter tabs.
     *
     * Order of precedence: home → product → checkout → other.
     * Path matching is case-insensitive substring — not regex — for speed.
     */
    private function classifyPageType(string $url): string
    {
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '/');

        if ($path === '/' || $path === '') {
            return 'home';
        }
        if (str_contains($path, '/product') || str_contains($path, '/shop') || str_contains($path, '/item')) {
            return 'product';
        }
        if (str_contains($path, '/cart') || str_contains($path, '/checkout') || str_contains($path, '/order')) {
            return 'checkout';
        }

        return 'other';
    }

    // ─── Statistics helpers ────────────────────────────────────────────────────

    /**
     * Compute the median of an integer array. Returns null for empty input.
     * Used for site-wide CrUX summary (median of URL-level p75 values).
     */
    private function median(array $values): ?float
    {
        if (empty($values)) {
            return null;
        }

        return $this->medianFloat($values);
    }

    /** Compute the median of a numeric array. Returns null for empty input. */
    private function medianFloat(array $values): ?float
    {
        if (empty($values)) {
            return null;
        }

        sort($values);
        $count = count($values);
        $mid   = (int) floor($count / 2);

        return $count % 2 === 0
            ? ($values[$mid - 1] + $values[$mid]) / 2.0
            : (float) $values[$mid];
    }

    /**
     * Derive the aggregate crux_source across all URL rows.
     *
     * Returns 'url' only if every row with CrUX data has source='url'.
     * Returns 'origin' if any row has source='origin'.
     * Returns null if no URL has CrUX data (all sources are null).
     */
    private function aggregateCruxSource(array $urlRows): ?string
    {
        $sources = array_filter(array_column($urlRows, 'crux_source'));
        if (empty($sources)) {
            return null;
        }
        if (in_array('origin', $sources, true)) {
            return 'origin';
        }

        return 'url';
    }

    // ─── Suggested URLs ────────────────────────────────────────────────────────

    /**
     * Return up to 5 URL suggestions for workspaces with fewer than 5 monitored URLs.
     *
     * Priority order:
     *   1. Top GSC pages by clicks (last 30 days) — source = 'gsc'
     *   2. Store homepage (if no GSC data) — source = 'homepage'
     *
     * Already-monitored URLs are excluded.
     *
     * @param  string[]  $monitoredUrls  URLs already in store_urls for this workspace
     * @return list<array{url: string, source: 'gsc'|'homepage'}>
     */
    public function suggestedUrls(int $workspaceId, array $monitoredUrls): array
    {
        $suggestions = [];
        $needed      = 5 - count($monitoredUrls);

        if ($needed <= 0) {
            return [];
        }

        // ── 1. GSC top pages ─────────────────────────────────────────────────
        // search_console_pages has workspace_id directly, so no property join required.
        // Aggregate by page URL over the last 30 days, exclude already-monitored URLs.
        $gscRows = DB::select(
            "SELECT page, SUM(clicks) AS total_clicks
             FROM search_console_pages
             WHERE workspace_id = ?
               AND date >= NOW() - INTERVAL '30 days'
             GROUP BY page
             ORDER BY total_clicks DESC
             LIMIT ?",
            [$workspaceId, $needed * 3], // fetch extra to filter monitored URLs client-side
        );

        foreach ($gscRows as $row) {
            if (count($suggestions) >= $needed) {
                break;
            }
            $url = (string) $row->page;
            if (! $this->isAlreadyMonitored($url, $monitoredUrls)) {
                $suggestions[] = ['url' => $url, 'source' => 'gsc'];
            }
        }

        // ── 2. Store homepage fallback ────────────────────────────────────────
        // If we still need suggestions, add the store homepage from stores table.
        if (count($suggestions) < $needed) {
            $stores = DB::select(
                "SELECT domain FROM stores WHERE workspace_id = ? AND domain IS NOT NULL LIMIT 5",
                [$workspaceId],
            );

            foreach ($stores as $store) {
                if (count($suggestions) >= $needed) {
                    break;
                }
                $homepage = 'https://' . ltrim((string) $store->domain, '/');
                if (! $this->isAlreadyMonitored($homepage, $monitoredUrls)) {
                    $suggestions[] = ['url' => $homepage, 'source' => 'homepage'];
                }
            }
        }

        return array_values($suggestions);
    }

    /**
     * Check whether a candidate URL is already in the monitored set.
     * Normalises trailing slashes before comparing.
     */
    private function isAlreadyMonitored(string $candidate, array $monitored): bool
    {
        $normalise = fn (string $u) => rtrim(strtolower($u), '/');
        $needle    = $normalise($candidate);

        foreach ($monitored as $m) {
            if ($normalise($m) === $needle) {
                return true;
            }
        }

        return false;
    }

    // ─── Empty-state helper ────────────────────────────────────────────────────

    /** Return the empty-state props when no active store URLs exist. */
    private function emptyResponse(string $strategy, string $pageType, int $windowDays): array
    {
        return [
            'summary'         => [
                'lcp'  => ['p75' => null, 'status' => null, 'crux_source' => null],
                'inp'  => ['p75' => null, 'status' => null, 'crux_source' => null],
                'cls'  => ['p75' => null, 'status' => null, 'crux_source' => null],
                'ttfb' => ['p75' => null, 'status' => null, 'crux_source' => null],
                'lab_performance_score' => null,
            ],
            'trend'                       => [],
            'revenue_at_risk'             => 0.0,
            'urls'                        => [],
            'filters'                     => [
                'strategy'    => $strategy,
                'page_type'   => $pageType,
                'window_days' => $windowDays,
            ],
            'has_store_urls'               => false,
            'has_crux_data'                => false,
            'suggested_urls'               => [],
            'cvr_correlation'              => [],
            'cvr_correlation_weeks_needed' => 6,
            'uptime'                       => ['avg_30d_pct' => null, 'any_down' => false],
        ];
    }
}
