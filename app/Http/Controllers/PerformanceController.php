<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Performance page — Core Web Vitals (CrUX field data) + Lighthouse lab data.
 *
 * Triggered by: GET /performance
 * Reads from:   lighthouse_snapshots, store_urls, ad_insights (via campaigns.parsed_convention),
 *               orders (via utm_* columns), holidays, workspace_events
 * Writes to:    nothing
 *
 * Data priority:
 *   1. CrUX field data (28-day rolling real-user aggregate) when sample ≥ 75 origins.
 *   2. Lighthouse lab data as fallback when CrUX sample is insufficient.
 *   Each row carries a `source` flag ('crux' | 'lighthouse') so the UI can chip accordingly.
 *
 * Controllers are thin — all mock data here mirrors the exact shape the frontend
 * expects. Replace with real DB queries when services are wired.
 *
 * @see docs/pages/performance.md
 * @see docs/planning/backend.md #PerformanceController
 * @see app/Jobs/SyncCruxJob.php
 * @see app/Models/LighthouseSnapshot.php
 */
class PerformanceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'from'        => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to'          => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'device'      => ['sometimes', 'nullable', 'in:mobile,desktop'],
            'page_type'   => ['sometimes', 'nullable', 'string'],
            'score_band'  => ['sometimes', 'nullable', 'in:good,needs-improvement,poor'],
            'has_ad_spend'=> ['sometimes', 'nullable', 'boolean'],
            'has_crux'    => ['sometimes', 'nullable', 'boolean'],
            'url_search'  => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $from   = $validated['from']   ?? now()->subDays(29)->toDateString();
        $to     = $validated['to']     ?? now()->toDateString();
        $device = $validated['device'] ?? 'mobile';

        // ── Mock data ─────────────────────────────────────────────────────────────
        // Realistic 40-URL ecommerce site: 28 with CrUX, 12 Lighthouse-only.
        // Score distribution: ~30% Good (≥90), ~50% Needs Improvement (50-89), ~20% Poor (<50).
        // LCP 1200–4800ms. CLS 0.01–0.35. INP 80–800ms. TTFB 200–1500ms.

        $urlRows = $this->buildMockUrlRows($device);

        // ── Aggregate KPI cards (workspace-wide) ───────────────────────────────
        $cruxRows = array_filter($urlRows, fn ($r) => $r['source'] === 'crux');

        $goodLcp   = count(array_filter($cruxRows, fn ($r) => $r['lcp_ms'] !== null && $r['lcp_ms'] <= 2500));
        $totalLcp  = count(array_filter($cruxRows, fn ($r) => $r['lcp_ms'] !== null));
        $goodInp   = count(array_filter($cruxRows, fn ($r) => $r['inp_ms'] !== null && $r['inp_ms'] <= 200));
        $totalInp  = count(array_filter($cruxRows, fn ($r) => $r['inp_ms'] !== null));
        $goodCls   = count(array_filter($cruxRows, fn ($r) => $r['cls'] !== null && $r['cls'] <= 0.1));
        $totalCls  = count(array_filter($cruxRows, fn ($r) => $r['cls'] !== null));

        $kpis = [
            [
                'label'      => 'Good LCP URLs',
                'qualifier'  => '28d CrUX',
                'value'      => $totalLcp > 0 ? round(($goodLcp / $totalLcp) * 100, 1) : null,
                'unit'       => 'pct',
                'delta_pct'  => 4.2,
                'sparkline'  => $this->sparklinePct(28, 62, 30),
                'threshold'  => 2500,
                'source'     => 'gsc',
            ],
            [
                'label'      => 'Good INP URLs',
                'qualifier'  => '28d CrUX',
                'value'      => $totalInp > 0 ? round(($goodInp / $totalInp) * 100, 1) : null,
                'unit'       => 'pct',
                'delta_pct'  => -2.1,
                'sparkline'  => $this->sparklinePct(55, 68, 30),
                'threshold'  => 200,
                'source'     => 'gsc',
            ],
            [
                'label'      => 'Good CLS URLs',
                'qualifier'  => '28d CrUX',
                'value'      => $totalCls > 0 ? round(($goodCls / $totalCls) * 100, 1) : null,
                'unit'       => 'pct',
                'delta_pct'  => 1.8,
                'sparkline'  => $this->sparklinePct(72, 79, 30),
                'threshold'  => 0.1,
                'source'     => 'gsc',
            ],
            [
                'label'      => 'Shopify Speed Score',
                'qualifier'  => 'weekly',
                'value'      => 67,
                'unit'       => null,
                'delta_pct'  => 3.0,
                'sparkline'  => $this->sparklineInt(58, 72, 30),
                'threshold'  => null,
                'source'     => 'store',
            ],
        ];

        // ── Trend chart data (weekly, 12 weeks) ──────────────────────────────
        $trend = $this->buildTrendData(12);

        // ── Ad-spend + ROAS for QuadrantChart ─────────────────────────────────
        $quadrantPoints = $this->buildQuadrantPoints($urlRows);

        // ── Workspace event overlays (deploy annotations) ─────────────────────
        $annotations = [
            ['date' => now()->subDays(22)->toDateString(), 'name' => 'Theme update v3.2', 'event_type' => 'deploy'],
            ['date' => now()->subDays(8)->toDateString(),  'name' => 'Image optimisation rollout', 'event_type' => 'deploy'],
        ];

        return Inertia::render('Performance/Index', [
            'from'             => $from,
            'to'               => $to,
            'device'           => $device,
            'kpis'             => $kpis,
            'trend'            => $trend,
            'url_rows'         => $urlRows,
            'quadrant_points'  => $quadrantPoints,
            'annotations'      => $annotations,
            'psi_connected'    => true,
            'total_urls'       => count($urlRows),
            'crux_url_count'   => count($cruxRows),
        ]);
    }

    // ── Mock builders ──────────────────────────────────────────────────────────

    /**
     * Build 40 mock URL rows for the performance table.
     *
     * Shape matches LighthouseSnapshot + ad_insights join:
     *   url, page_type, speed_score, lcp_ms, inp_ms, cls, ttfb_ms,
     *   source ('crux'|'lighthouse'), sample_size, last_checked_at,
     *   ad_spend_28d, score_history (30 points).
     *
     * ~70% CrUX-sourced (high-traffic), ~30% Lighthouse-only (low-traffic).
     * Score bands: ~30% Good (≥90), ~50% Needs Improvement (50-89), ~20% Poor (<50).
     */
    private function buildMockUrlRows(string $device): array
    {
        $base = [
            // ── Homepage + key landing pages ──────────────────────────────────
            ['url' => 'https://shop.example.com/',                        'type' => 'homepage',   'score' => 84, 'lcp' => 2100, 'inp' => 180, 'cls' => 0.08, 'ttfb' => 480,  'spend' => 0,     'crux' => true,  'sample' => 48200],
            ['url' => 'https://shop.example.com/collections/all',          'type' => 'collection', 'score' => 71, 'lcp' => 2900, 'inp' => 240, 'cls' => 0.12, 'ttfb' => 610,  'spend' => 1240,  'crux' => true,  'sample' => 22100],
            ['url' => 'https://shop.example.com/collections/best-sellers', 'type' => 'collection', 'score' => 67, 'lcp' => 3100, 'inp' => 310, 'cls' => 0.15, 'ttfb' => 590,  'spend' => 3820,  'crux' => true,  'sample' => 18300],
            ['url' => 'https://shop.example.com/collections/sale',          'type' => 'collection', 'score' => 55, 'lcp' => 3600, 'inp' => 420, 'cls' => 0.22, 'ttfb' => 880,  'spend' => 5210,  'crux' => true,  'sample' => 14700],
            ['url' => 'https://shop.example.com/collections/new-arrivals',  'type' => 'collection', 'score' => 72, 'lcp' => 2800, 'inp' => 195, 'cls' => 0.09, 'ttfb' => 540,  'spend' => 2150,  'crux' => true,  'sample' => 11200],
            ['url' => 'https://shop.example.com/collections/gifts',         'type' => 'collection', 'score' => 48, 'lcp' => 4100, 'inp' => 580, 'cls' => 0.28, 'ttfb' => 1120, 'spend' => 4680,  'crux' => true,  'sample' => 9800],
            ['url' => 'https://shop.example.com/collections/accessories',   'type' => 'collection', 'score' => 61, 'lcp' => 3300, 'inp' => 370, 'cls' => 0.18, 'ttfb' => 720,  'spend' => 1890,  'crux' => true,  'sample' => 8100],
            ['url' => 'https://shop.example.com/collections/featured',      'type' => 'collection', 'score' => 38, 'lcp' => 4600, 'inp' => 720, 'cls' => 0.34, 'ttfb' => 1380, 'spend' => 6420,  'crux' => true,  'sample' => 7200],

            // ── Product pages ──────────────────────────────────────────────────
            ['url' => 'https://shop.example.com/products/classic-tee',           'type' => 'product', 'score' => 92, 'lcp' => 1300, 'inp' => 90,  'cls' => 0.02, 'ttfb' => 220,  'spend' => 8940,  'crux' => true,  'sample' => 31400],
            ['url' => 'https://shop.example.com/products/logo-hoodie',           'type' => 'product', 'score' => 88, 'lcp' => 1800, 'inp' => 145, 'cls' => 0.05, 'ttfb' => 310,  'spend' => 12300, 'crux' => true,  'sample' => 28600],
            ['url' => 'https://shop.example.com/products/premium-joggers',       'type' => 'product', 'score' => 76, 'lcp' => 2600, 'inp' => 210, 'cls' => 0.11, 'ttfb' => 450,  'spend' => 5620,  'crux' => true,  'sample' => 21700],
            ['url' => 'https://shop.example.com/products/retro-snapback',        'type' => 'product', 'score' => 64, 'lcp' => 3200, 'inp' => 340, 'cls' => 0.19, 'ttfb' => 680,  'spend' => 3140,  'crux' => true,  'sample' => 16800],
            ['url' => 'https://shop.example.com/products/canvas-tote',           'type' => 'product', 'score' => 91, 'lcp' => 1400, 'inp' => 100, 'cls' => 0.03, 'ttfb' => 260,  'spend' => 2840,  'crux' => true,  'sample' => 14300],
            ['url' => 'https://shop.example.com/products/crew-sweatshirt',       'type' => 'product', 'score' => 58, 'lcp' => 3500, 'inp' => 460, 'cls' => 0.23, 'ttfb' => 910,  'spend' => 7180,  'crux' => true,  'sample' => 12900],
            ['url' => 'https://shop.example.com/products/zip-up-fleece',         'type' => 'product', 'score' => 44, 'lcp' => 4400, 'inp' => 650, 'cls' => 0.31, 'ttfb' => 1290, 'spend' => 9240,  'crux' => true,  'sample' => 10800],
            ['url' => 'https://shop.example.com/products/graphic-tank',          'type' => 'product', 'score' => 79, 'lcp' => 2400, 'inp' => 175, 'cls' => 0.07, 'ttfb' => 410,  'spend' => 1940,  'crux' => true,  'sample' => 9700],
            ['url' => 'https://shop.example.com/products/dad-hat',               'type' => 'product', 'score' => 85, 'lcp' => 1950, 'inp' => 130, 'cls' => 0.04, 'ttfb' => 340,  'spend' => 2640,  'crux' => true,  'sample' => 8400],
            ['url' => 'https://shop.example.com/products/woven-shorts',          'type' => 'product', 'score' => 51, 'lcp' => 3800, 'inp' => 510, 'cls' => 0.27, 'ttfb' => 1050, 'spend' => 4820,  'crux' => true,  'sample' => 7200],
            ['url' => 'https://shop.example.com/products/pullover-hoodie',       'type' => 'product', 'score' => 93, 'lcp' => 1250, 'inp' => 82,  'cls' => 0.01, 'ttfb' => 210,  'spend' => 11680, 'crux' => true,  'sample' => 6900],
            ['url' => 'https://shop.example.com/products/long-sleeve-tee',       'type' => 'product', 'score' => 69, 'lcp' => 3000, 'inp' => 290, 'cls' => 0.14, 'ttfb' => 620,  'spend' => 3360,  'crux' => true,  'sample' => 5600],
            ['url' => 'https://shop.example.com/products/bomber-jacket',         'type' => 'product', 'score' => 42, 'lcp' => 4700, 'inp' => 780, 'cls' => 0.33, 'ttfb' => 1450, 'spend' => 0,     'crux' => true,  'sample' => 4800],
            ['url' => 'https://shop.example.com/products/windbreaker',           'type' => 'product', 'score' => 74, 'lcp' => 2700, 'inp' => 225, 'cls' => 0.10, 'ttfb' => 490,  'spend' => 1720,  'crux' => true,  'sample' => 4100],
            ['url' => 'https://shop.example.com/products/muscle-tank',           'type' => 'product', 'score' => 82, 'lcp' => 2050, 'inp' => 155, 'cls' => 0.06, 'ttfb' => 360,  'spend' => 2180,  'crux' => true,  'sample' => 3700],
            ['url' => 'https://shop.example.com/products/track-pants',           'type' => 'product', 'score' => 57, 'lcp' => 3550, 'inp' => 480, 'cls' => 0.25, 'ttfb' => 940,  'spend' => 6840,  'crux' => true,  'sample' => 3300],
            ['url' => 'https://shop.example.com/products/bucket-hat',            'type' => 'product', 'score' => 95, 'lcp' => 1200, 'inp' => 80,  'cls' => 0.01, 'ttfb' => 205,  'spend' => 4120,  'crux' => true,  'sample' => 2900],
            ['url' => 'https://shop.example.com/products/five-panel-cap',        'type' => 'product', 'score' => 66, 'lcp' => 3150, 'inp' => 355, 'cls' => 0.17, 'ttfb' => 700,  'spend' => 0,     'crux' => true,  'sample' => 2500],
            ['url' => 'https://shop.example.com/products/vest',                  'type' => 'product', 'score' => 47, 'lcp' => 4200, 'inp' => 620, 'cls' => 0.30, 'ttfb' => 1240, 'spend' => 0,     'crux' => true,  'sample' => 2100],
            ['url' => 'https://shop.example.com/products/jersey',                'type' => 'product', 'score' => 78, 'lcp' => 2450, 'inp' => 185, 'cls' => 0.08, 'ttfb' => 420,  'spend' => 1480,  'crux' => true,  'sample' => 1800],

            // ── Cart + Checkout (CrUX typically insufficient) ─────────────────
            ['url' => 'https://shop.example.com/cart',                    'type' => 'cart',       'score' => 88, 'lcp' => 1700, 'inp' => 140, 'cls' => 0.04, 'ttfb' => 290,  'spend' => 0,     'crux' => false, 'sample' => null],
            ['url' => 'https://shop.example.com/checkout',                'type' => 'checkout',   'score' => 90, 'lcp' => 1500, 'inp' => 110, 'cls' => 0.02, 'ttfb' => 240,  'spend' => 0,     'crux' => false, 'sample' => null],

            // ── Blog posts (Lighthouse-only — low traffic) ─────────────────────
            ['url' => 'https://shop.example.com/blogs/news/style-guide-2026',     'type' => 'blog', 'score' => 86, 'lcp' => 1900, 'inp' => 148, 'cls' => 0.05, 'ttfb' => 330,  'spend' => 810,   'crux' => false, 'sample' => null],
            ['url' => 'https://shop.example.com/blogs/news/care-tips',            'type' => 'blog', 'score' => 73, 'lcp' => 2750, 'inp' => 220, 'cls' => 0.12, 'ttfb' => 560,  'spend' => 0,     'crux' => false, 'sample' => null],
            ['url' => 'https://shop.example.com/blogs/news/sustainable-fashion',  'type' => 'blog', 'score' => 62, 'lcp' => 3250, 'inp' => 350, 'cls' => 0.20, 'ttfb' => 740,  'spend' => 640,   'crux' => false, 'sample' => null],
            ['url' => 'https://shop.example.com/blogs/news/bfcm-2025-recap',      'type' => 'blog', 'score' => 79, 'lcp' => 2350, 'inp' => 175, 'cls' => 0.07, 'ttfb' => 400,  'spend' => 1280,  'crux' => false, 'sample' => null],
            ['url' => 'https://shop.example.com/blogs/news/sizing-guide',         'type' => 'blog', 'score' => 91, 'lcp' => 1380, 'inp' => 95,  'cls' => 0.02, 'ttfb' => 215,  'spend' => 0,     'crux' => false, 'sample' => null],
            ['url' => 'https://shop.example.com/blogs/news/gift-ideas',           'type' => 'blog', 'score' => 54, 'lcp' => 3700, 'inp' => 490, 'cls' => 0.26, 'ttfb' => 980,  'spend' => 1920,  'crux' => false, 'sample' => null],
            ['url' => 'https://shop.example.com/blogs/news/spring-lookbook',      'type' => 'blog', 'score' => 45, 'lcp' => 4300, 'inp' => 640, 'cls' => 0.32, 'ttfb' => 1310, 'spend' => 0,     'crux' => false, 'sample' => null],
            ['url' => 'https://shop.example.com/blogs/news/collaboration-drop',   'type' => 'blog', 'score' => 68, 'lcp' => 3050, 'inp' => 305, 'cls' => 0.16, 'ttfb' => 660,  'spend' => 540,   'crux' => false, 'sample' => null],

            // ── Other ──────────────────────────────────────────────────────────
            ['url' => 'https://shop.example.com/pages/about',             'type' => 'other',      'score' => 94, 'lcp' => 1280, 'inp' => 85,  'cls' => 0.01, 'ttfb' => 220,  'spend' => 0,     'crux' => false, 'sample' => null],
            ['url' => 'https://shop.example.com/pages/contact',           'type' => 'other',      'score' => 96, 'lcp' => 1220, 'inp' => 80,  'cls' => 0.01, 'ttfb' => 210,  'spend' => 0,     'crux' => false, 'sample' => null],
            ['url' => 'https://shop.example.com/pages/faq',               'type' => 'other',      'score' => 83, 'lcp' => 2100, 'inp' => 165, 'cls' => 0.07, 'ttfb' => 370,  'spend' => 0,     'crux' => false, 'sample' => null],
            ['url' => 'https://shop.example.com/pages/returns',           'type' => 'other',      'score' => 89, 'lcp' => 1750, 'inp' => 135, 'cls' => 0.03, 'ttfb' => 300,  'spend' => 0,     'crux' => false, 'sample' => null],
        ];

        // Adjust scores slightly for desktop (desktop tends to be higher)
        $desktopBoost = $device === 'desktop' ? 8 : 0;

        $now = now();
        $rows = [];

        foreach ($base as $i => $r) {
            $score = min(100, $r['score'] + $desktopBoost);
            $band  = $score >= 90 ? 'good' : ($score >= 50 ? 'needs-improvement' : 'poor');

            // CWV band (always uses canonical thresholds regardless of device)
            $lcpBand = $r['lcp'] <= 2500 ? 'good' : ($r['lcp'] <= 4000 ? 'needs-improvement' : 'poor');
            $inpBand = $r['inp'] <= 200 ? 'good' : ($r['inp'] <= 500 ? 'needs-improvement' : 'poor');
            $clsBand = $r['cls'] <= 0.1 ? 'good' : ($r['cls'] <= 0.25 ? 'needs-improvement' : 'poor');

            // Lighthouse audit opportunities (shown in drawer)
            $audits = $this->buildAuditsForScore($score);

            // 30-day score history (used for sparkline column)
            $history = $this->scoreHistory($score, 30);

            $rows[] = [
                'id'              => $i + 1,
                'url'             => $r['url'],
                'page_type'       => $r['type'],
                'speed_score'     => $score,
                'score_band'      => $band,
                'lcp_ms'          => $r['lcp'],
                'lcp_band'        => $lcpBand,
                'inp_ms'          => $r['inp'],
                'inp_band'        => $inpBand,
                'cls'             => $r['cls'],
                'cls_band'        => $clsBand,
                'ttfb_ms'         => $r['ttfb'],
                // Lighthouse audit scores (0-100) for the drawer dials
                'lighthouse_performance'    => $score,
                'lighthouse_accessibility'  => rand(82, 98),
                'lighthouse_best_practices' => rand(75, 96),
                'lighthouse_seo'            => rand(88, 100),
                'source'          => $r['crux'] ? 'crux' : 'lighthouse',
                'sample_size'     => $r['sample'],
                'last_checked_at' => $now->copy()->subHours(rand(1, 18))->toISOString(),
                'ad_spend_28d'    => $r['spend'] > 0 ? (float) $r['spend'] : null,
                'score_history'   => $history,
                'audits'          => $audits,
            ];
        }

        return $rows;
    }

    /**
     * Build top failing Lighthouse audit opportunities for a given score.
     * Lower score = more/worse audits (realistic distribution).
     *
     * @return array<int, array{id: string, title: string, savings_ms: int|null, score: float}>
     */
    private function buildAuditsForScore(int $score): array
    {
        $allAudits = [
            ['id' => 'render-blocking-resources', 'title' => 'Eliminate render-blocking resources', 'savings_ms' => 840],
            ['id' => 'unused-javascript',         'title' => 'Remove unused JavaScript',           'savings_ms' => 620],
            ['id' => 'unused-css-rules',          'title' => 'Remove unused CSS',                  'savings_ms' => 310],
            ['id' => 'uses-optimized-images',     'title' => 'Efficiently encode images',          'savings_ms' => 1200],
            ['id' => 'uses-text-compression',     'title' => 'Enable text compression',            'savings_ms' => 280],
            ['id' => 'uses-responsive-images',    'title' => 'Properly size images',               'savings_ms' => 540],
            ['id' => 'largest-contentful-paint',  'title' => 'Largest Contentful Paint element',   'savings_ms' => null],
            ['id' => 'total-blocking-time',       'title' => 'Reduce Total Blocking Time',         'savings_ms' => 390],
            ['id' => 'server-response-time',      'title' => 'Reduce initial server response time','savings_ms' => 220],
            ['id' => 'uses-long-cache-ttl',       'title' => 'Serve static assets with efficient cache policy', 'savings_ms' => null],
            ['id' => 'dom-size',                  'title' => 'Avoid an excessive DOM size',        'savings_ms' => null],
            ['id' => 'third-party-summary',       'title' => 'Reduce the impact of third-party code', 'savings_ms' => 480],
        ];

        // More failing audits for lower scores
        $count = $score >= 90 ? 1 : ($score >= 70 ? 3 : ($score >= 50 ? 6 : 9));
        $items = array_slice($allAudits, 0, $count);

        return array_map(fn ($a) => [
            ...$a,
            'score' => round(max(0.0, ($score / 100) - 0.1 + (mt_rand(0, 20) / 100)), 2),
        ], $items);
    }

    /**
     * Generate a 30-point score history with realistic drift, ± noise per day.
     * Used for the sparkline column in the table.
     *
     * @return int[]
     */
    private function scoreHistory(int $current, int $days): array
    {
        $pts   = [];
        $value = max(10, $current - rand(0, 12));
        for ($i = 0; $i < $days; $i++) {
            $value = max(5, min(100, $value + rand(-4, 5)));
            $pts[] = $value;
        }
        $pts[$days - 1] = $current;
        return $pts;
    }

    /**
     * Build weekly CWV trend data for the LineChart (12 weeks × 3 metrics).
     *
     * @return array<int, array{date: string, lcp_p75: float, inp_p75: float, cls_p75: float, is_partial: bool}>
     */
    private function buildTrendData(int $weeks): array
    {
        $rows  = [];
        $lcp   = 3200.0;
        $inp   = 360.0;
        $cls   = 0.18;
        $today = now()->startOfWeek();

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = $today->copy()->subWeeks($i);
            $lcp       = max(1200, $lcp + rand(-180, 120));
            $inp       = max(80, $inp + rand(-30, 20));
            $cls       = max(0.01, round($cls + (rand(-3, 2) / 100), 3));

            $rows[] = [
                'date'       => $weekStart->toDateString(),
                'lcp_p75'    => $lcp,
                'inp_p75'    => $inp,
                'cls_p75'    => $cls,
                'is_partial' => $i === 0,
            ];
        }

        return $rows;
    }

    /**
     * Build QuadrantChart points: X = speed score, Y = ROAS, size = ad_spend.
     * Only URLs with ad spend appear in the quadrant.
     *
     * @param  array<int, array<string,mixed>> $urlRows
     * @return array<int, array{id: int, label: string, x: float, y: float|null, size: float|null, meta: array<string,mixed>}>
     */
    private function buildQuadrantPoints(array $urlRows): array
    {
        $points = [];
        $roasMap = [
            // homepage → no direct ROAS
            8 => 3.8, 9 => 4.2, 10 => 2.9, 11 => 1.4, 12 => 5.1,
            13 => 2.1, 14 => 0.8, 15 => 6.2, 16 => 3.6, 18 => 4.8,
            19 => 1.9, 20 => 1.2, 21 => 7.1, 22 => 2.5, 23 => 4.0,
            25 => 3.3, 3 => 1.5, 4 => 2.8, 5 => 3.2, 6 => 0.9, 7 => 2.4,
        ];

        foreach ($urlRows as $row) {
            if ($row['ad_spend_28d'] === null || $row['ad_spend_28d'] <= 0) {
                continue;
            }
            $roas    = $roasMap[$row['id']] ?? round(0.6 + ($row['speed_score'] / 40), 2);
            $points[] = [
                'id'    => $row['id'],
                'label' => parse_url($row['url'], PHP_URL_PATH) ?: '/',
                'x'     => (float) $row['speed_score'],
                'y'     => $roas,
                'size'  => $row['ad_spend_28d'],
                'meta'  => [
                    'LCP'      => round($row['lcp_ms'] / 1000, 2) . 's',
                    'Spend'    => '$' . number_format($row['ad_spend_28d']),
                    'ROAS'     => $roas . '×',
                    'Source'   => $row['source'],
                ],
            ];
        }

        return $points;
    }

    /**
     * Generate a smooth pct sparkline (values between $min and $max, 30 points).
     *
     * @return float[]
     */
    private function sparklinePct(float $min, float $max, int $n): array
    {
        $pts   = [];
        $value = $min + ($max - $min) * 0.3;
        for ($i = 0; $i < $n; $i++) {
            $value = max($min, min($max, $value + (rand(-3, 4) / 10) * ($max - $min) / 20));
            $pts[] = round($value, 1);
        }
        return $pts;
    }

    /**
     * Generate a smooth integer sparkline (values between $min and $max, n points).
     *
     * @return int[]
     */
    private function sparklineInt(int $min, int $max, int $n): array
    {
        $pts   = [];
        $value = (int) ($min + ($max - $min) * 0.4);
        for ($i = 0; $i < $n; $i++) {
            $value = max($min, min($max, $value + rand(-2, 3)));
            $pts[] = $value;
        }
        return $pts;
    }
}
