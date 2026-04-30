<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AdsController — unified /ads page controller.
 *
 * Consolidates all ad-hierarchy levels (campaigns, adsets, ads, creatives)
 * into a single Inertia page. Controller is a stub that returns rich mock data
 * matching the Northbeam-grade UI spec from docs/pages/ads.md.
 *
 * Real backend: query ad_insights (level filtered), campaigns, adsets, ads
 * tables; aggregate spend/impressions/attributed revenue per entity. See
 * the old CampaignsController / AdSetsController / AdsController for the
 * query patterns to port when backend is wired.
 *
 * Purpose: renders /ads with campaigns, adsets, ads, creatives, KPI strip,
 *          quadrant data, and chart series.
 * Reads:   ad_accounts, campaigns, adsets, ads, ad_insights (mock for now)
 * Writes:  nothing (read-only per Nexstage thesis)
 * Called by: GET /{workspace}/ads
 *
 * @see docs/pages/ads.md
 * @see docs/planning/backend.md#AdsController
 */
class AdsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $workspace   = Workspace::withoutGlobalScopes()->findOrFail($workspaceId);

        $v = $request->validate([
            'from'       => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to'         => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'platform'   => ['sometimes', 'nullable', 'in:all,facebook,google'],
            'status'     => ['sometimes', 'nullable', 'in:all,active,paused'],
            'tab'        => ['sometimes', 'nullable', 'in:campaigns,adsets,ads,creatives'],
            'attribution'=> ['sometimes', 'nullable', 'string'],
            'window'     => ['sometimes', 'nullable', 'string'],
        ]);

        $from     = $v['from']      ?? now()->subDays(29)->toDateString();
        $to       = $v['to']        ?? now()->toDateString();
        $platform = $v['platform']  ?? 'all';
        $status   = $v['status']    ?? 'all';
        $tab      = $v['tab']       ?? 'campaigns';
        $attribution = $v['attribution'] ?? 'last-non-direct-click';
        $window      = $v['window']      ?? '7d-click';

        // ─── Mock data — 25 campaigns × 2–4 adsets × 2–5 ads ──────────────────
        // Realistic names following parsed_convention pattern:
        //   [Season] — [Objective] — [Audience] — [Geo]
        // Source disagreement: FB claims 2–4× ROAS, Real ~50% of that.

        $campaigns = [
            [
                'id' => 'fb_camp_1', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Spring Sale 2026 — Prospecting — LAL 1% — US',
                'spend' => 4287.00, 'impressions' => 412000, 'clicks' => 8200,
                'ctr' => 1.99, 'cpc' => 0.52, 'cpm' => 10.40,
                'cpa_first_time' => 32.10, 'cvr' => 2.40,
                'attributed_revenue_real' => 8420.00,
                'attributed_revenue_platform' => 16830.00,
                'attributed_revenue_store' => 7980.00,
                'roas_real' => 1.96, 'roas_platform' => 3.92, 'roas_store' => 1.86,
                'sparkline_roas_14d' => [1.2,1.5,1.8,2.1,1.9,2.3,2.0,1.8,1.95,2.1,1.9,1.96,2.0,1.96],
                'grade' => 'B', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 320, 'orders_store' => 248,
            ],
            [
                'id' => 'fb_camp_2', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Spring Sale 2026 — Retargeting — 30d — US',
                'spend' => 1820.00, 'impressions' => 98000, 'clicks' => 3100,
                'ctr' => 3.16, 'cpc' => 0.59, 'cpm' => 18.57,
                'cpa_first_time' => 18.20, 'cvr' => 4.80,
                'attributed_revenue_real' => 7644.00,
                'attributed_revenue_platform' => 20020.00,
                'attributed_revenue_store' => 7200.00,
                'roas_real' => 4.20, 'roas_platform' => 11.00, 'roas_store' => 3.96,
                'sparkline_roas_14d' => [3.8,4.1,4.5,4.2,4.0,3.9,4.3,4.5,4.2,4.0,4.4,4.2,4.1,4.20],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 210, 'orders_store' => 190,
            ],
            [
                'id' => 'fb_camp_3', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Brand Awareness — Broad — WW',
                'spend' => 7940.00, 'impressions' => 1820000, 'clicks' => 12400,
                'ctr' => 0.68, 'cpc' => 0.64, 'cpm' => 4.36,
                'cpa_first_time' => 79.40, 'cvr' => 0.81,
                'attributed_revenue_real' => 4362.00,
                'attributed_revenue_platform' => 14720.00,
                'attributed_revenue_store' => 3900.00,
                'roas_real' => 0.55, 'roas_platform' => 1.85, 'roas_store' => 0.49,
                'sparkline_roas_14d' => [0.8,0.7,0.6,0.5,0.6,0.55,0.5,0.4,0.5,0.6,0.55,0.52,0.5,0.55],
                'grade' => 'D', 'confidence' => 'high', 'attributed_signal' => 'modeled',
                'purchases_platform' => 182, 'orders_store' => 87,
            ],
            [
                'id' => 'fb_camp_4', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'BFCM Remnant — DPA — Cart Abandoners — EU',
                'spend' => 960.00, 'impressions' => 44000, 'clicks' => 1980,
                'ctr' => 4.50, 'cpc' => 0.48, 'cpm' => 21.82,
                'cpa_first_time' => 14.77, 'cvr' => 5.30,
                'attributed_revenue_real' => 4992.00,
                'attributed_revenue_platform' => 9800.00,
                'attributed_revenue_store' => 4700.00,
                'roas_real' => 5.20, 'roas_platform' => 10.21, 'roas_store' => 4.90,
                'sparkline_roas_14d' => [4.8,5.0,5.3,5.1,5.4,5.2,5.0,5.3,5.2,5.1,5.4,5.2,5.1,5.20],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 98, 'orders_store' => 96,
            ],
            [
                'id' => 'fb_camp_5', 'platform' => 'facebook', 'status' => 'paused',
                'objective' => 'reach',
                'name' => 'Q1 Video — Prospecting — Interest — UK',
                'spend' => 3120.00, 'impressions' => 480000, 'clicks' => 4800,
                'ctr' => 1.00, 'cpc' => 0.65, 'cpm' => 6.50,
                'cpa_first_time' => 78.00, 'cvr' => 0.83,
                'attributed_revenue_real' => 3900.00,
                'attributed_revenue_platform' => 9450.00,
                'attributed_revenue_store' => 3600.00,
                'roas_real' => 1.25, 'roas_platform' => 3.03, 'roas_store' => 1.15,
                'sparkline_roas_14d' => [1.5,1.4,1.3,1.2,1.1,1.0,1.2,1.3,1.2,1.1,1.2,1.3,1.2,1.25],
                'grade' => 'C', 'confidence' => 'low', 'attributed_signal' => 'modeled',
                'purchases_platform' => 120, 'orders_store' => 80,
            ],
            [
                'id' => 'fb_camp_6', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Summer Preview — LAL 3% — DE',
                'spend' => 2100.00, 'impressions' => 220000, 'clicks' => 4600,
                'ctr' => 2.09, 'cpc' => 0.46, 'cpm' => 9.55,
                'cpa_first_time' => 28.00, 'cvr' => 3.26,
                'attributed_revenue_real' => 6510.00,
                'attributed_revenue_platform' => 12200.00,
                'attributed_revenue_store' => 6100.00,
                'roas_real' => 3.10, 'roas_platform' => 5.81, 'roas_store' => 2.90,
                'sparkline_roas_14d' => [2.8,2.9,3.1,3.0,3.2,3.1,2.9,3.0,3.1,3.2,3.0,3.1,3.2,3.10],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 150, 'orders_store' => 130,
            ],
            [
                'id' => 'fb_camp_7', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Flash Sale — Retargeting — 7d — AU',
                'spend' => 680.00, 'impressions' => 31000, 'clicks' => 1550,
                'ctr' => 5.00, 'cpc' => 0.44, 'cpm' => 21.94,
                'cpa_first_time' => 10.46, 'cvr' => 6.45,
                'attributed_revenue_real' => 4284.00,
                'attributed_revenue_platform' => 7480.00,
                'attributed_revenue_store' => 4100.00,
                'roas_real' => 6.30, 'roas_platform' => 11.00, 'roas_store' => 6.03,
                'sparkline_roas_14d' => [5.8,6.0,6.2,6.4,6.3,6.1,6.3,6.4,6.2,6.3,6.5,6.3,6.2,6.30],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 65, 'orders_store' => 64,
            ],
            [
                'id' => 'fb_camp_8', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Prospecting — LAL 5% — CA',
                'spend' => 1540.00, 'impressions' => 185000, 'clicks' => 3150,
                'ctr' => 1.70, 'cpc' => 0.49, 'cpm' => 8.32,
                'cpa_first_time' => 42.78, 'cvr' => 1.87,
                'attributed_revenue_real' => 2926.00,
                'attributed_revenue_platform' => 7700.00,
                'attributed_revenue_store' => 2700.00,
                'roas_real' => 1.90, 'roas_platform' => 5.00, 'roas_store' => 1.75,
                'sparkline_roas_14d' => [1.8,1.9,2.0,1.8,1.7,1.9,2.0,1.9,1.8,1.9,2.0,1.9,1.8,1.90],
                'grade' => 'B', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 110, 'orders_store' => 72,
            ],
            [
                'id' => 'goog_camp_1', 'platform' => 'google', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Brand Search — Exact — US',
                'spend' => 1260.00, 'impressions' => 42000, 'clicks' => 14700,
                'ctr' => 35.00, 'cpc' => 0.086, 'cpm' => 30.00,
                'cpa_first_time' => 9.69, 'cvr' => 8.84,
                'attributed_revenue_real' => 8946.00,
                'attributed_revenue_platform' => 9800.00,
                'attributed_revenue_store' => 8700.00,
                'roas_real' => 7.10, 'roas_platform' => 7.78, 'roas_store' => 6.90,
                'sparkline_roas_14d' => [6.8,7.0,7.2,7.1,7.0,7.2,7.3,7.1,7.0,7.2,7.1,7.0,7.2,7.10],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 130, 'orders_store' => 126,
            ],
            [
                'id' => 'goog_camp_2', 'platform' => 'google', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Non-Brand Search — Broad — US + EU',
                'spend' => 3840.00, 'impressions' => 210000, 'clicks' => 18900,
                'ctr' => 9.00, 'cpc' => 0.203, 'cpm' => 18.29,
                'cpa_first_time' => 34.91, 'cvr' => 5.82,
                'attributed_revenue_real' => 10944.00,
                'attributed_revenue_platform' => 12350.00,
                'attributed_revenue_store' => 10600.00,
                'roas_real' => 2.85, 'roas_platform' => 3.22, 'roas_store' => 2.76,
                'sparkline_roas_14d' => [2.6,2.7,2.9,2.8,2.9,3.0,2.8,2.7,2.9,3.0,2.8,2.9,2.8,2.85],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 310, 'orders_store' => 276,
            ],
            [
                'id' => 'goog_camp_3', 'platform' => 'google', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Shopping — All Products — US',
                'spend' => 6120.00, 'impressions' => 380000, 'clicks' => 22800,
                'ctr' => 6.00, 'cpc' => 0.268, 'cpm' => 16.11,
                'cpa_first_time' => 25.50, 'cvr' => 10.53,
                'attributed_revenue_real' => 18972.00,
                'attributed_revenue_platform' => 22000.00,
                'attributed_revenue_store' => 18200.00,
                'roas_real' => 3.10, 'roas_platform' => 3.59, 'roas_store' => 2.97,
                'sparkline_roas_14d' => [2.9,3.0,3.2,3.1,3.0,3.2,3.3,3.1,3.0,3.2,3.1,3.0,3.1,3.10],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 480, 'orders_store' => 440,
            ],
            [
                'id' => 'goog_camp_4', 'platform' => 'google', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'PMax — All Products — EU',
                'spend' => 4200.00, 'impressions' => 920000, 'clicks' => 9800,
                'ctr' => 1.065, 'cpc' => 0.429, 'cpm' => 4.57,
                'cpa_first_time' => 38.18, 'cvr' => 2.78,
                'attributed_revenue_real' => 11088.00,
                'attributed_revenue_platform' => 14700.00,
                'attributed_revenue_store' => 10500.00,
                'roas_real' => 2.64, 'roas_platform' => 3.50, 'roas_store' => 2.50,
                'sparkline_roas_14d' => [2.4,2.5,2.6,2.5,2.7,2.6,2.5,2.7,2.6,2.5,2.6,2.7,2.6,2.64],
                'grade' => 'B', 'confidence' => 'high', 'attributed_signal' => 'mixed',
                'purchases_platform' => 220, 'orders_store' => 196,
            ],
            [
                'id' => 'goog_camp_5', 'platform' => 'google', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Display Retargeting — 14d — WW',
                'spend' => 820.00, 'impressions' => 1100000, 'clicks' => 2200,
                'ctr' => 0.20, 'cpc' => 0.373, 'cpm' => 0.745,
                'cpa_first_time' => 54.67, 'cvr' => 0.68,
                'attributed_revenue_real' => 1476.00,
                'attributed_revenue_platform' => 3280.00,
                'attributed_revenue_store' => 1350.00,
                'roas_real' => 1.80, 'roas_platform' => 4.00, 'roas_store' => 1.65,
                'sparkline_roas_14d' => [2.0,1.9,1.8,1.7,1.8,1.9,1.8,1.7,1.8,1.9,1.8,1.7,1.8,1.80],
                'grade' => 'C', 'confidence' => 'low', 'attributed_signal' => 'modeled',
                'purchases_platform' => 60, 'orders_store' => 27,
            ],
            [
                'id' => 'fb_camp_9', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Competitor Conquest — Interests — US',
                'spend' => 2640.00, 'impressions' => 310000, 'clicks' => 5580,
                'ctr' => 1.80, 'cpc' => 0.47, 'cpm' => 8.52,
                'cpa_first_time' => 52.80, 'cvr' => 1.70,
                'attributed_revenue_real' => 3696.00,
                'attributed_revenue_platform' => 7920.00,
                'attributed_revenue_store' => 3400.00,
                'roas_real' => 1.40, 'roas_platform' => 3.00, 'roas_store' => 1.29,
                'sparkline_roas_14d' => [1.6,1.5,1.4,1.3,1.4,1.5,1.4,1.3,1.4,1.5,1.4,1.3,1.4,1.40],
                'grade' => 'C', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 100, 'orders_store' => 70,
            ],
            [
                'id' => 'fb_camp_10', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Email List LAL — 2% — US',
                'spend' => 1980.00, 'impressions' => 195000, 'clicks' => 4160,
                'ctr' => 2.13, 'cpc' => 0.48, 'cpm' => 10.15,
                'cpa_first_time' => 24.75, 'cvr' => 2.97,
                'attributed_revenue_real' => 5940.00,
                'attributed_revenue_platform' => 11800.00,
                'attributed_revenue_store' => 5600.00,
                'roas_real' => 3.00, 'roas_platform' => 5.96, 'roas_store' => 2.83,
                'sparkline_roas_14d' => [2.8,2.9,3.0,2.9,3.1,3.0,2.9,3.1,3.0,2.9,3.1,3.0,2.9,3.00],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 160, 'orders_store' => 140,
            ],
            [
                'id' => 'fb_camp_11', 'platform' => 'facebook', 'status' => 'paused',
                'objective' => 'traffic',
                'name' => 'Cold Traffic — Video — 18-34 — US',
                'spend' => 4800.00, 'impressions' => 820000, 'clicks' => 9800,
                'ctr' => 1.20, 'cpc' => 0.49, 'cpm' => 5.85,
                'cpa_first_time' => 96.00, 'cvr' => 0.51,
                'attributed_revenue_real' => 2400.00,
                'attributed_revenue_platform' => 9600.00,
                'attributed_revenue_store' => 2200.00,
                'roas_real' => 0.50, 'roas_platform' => 2.00, 'roas_store' => 0.46,
                'sparkline_roas_14d' => [0.8,0.7,0.6,0.5,0.6,0.5,0.4,0.5,0.6,0.5,0.4,0.5,0.5,0.50],
                'grade' => 'F', 'confidence' => 'high', 'attributed_signal' => 'modeled',
                'purchases_platform' => 100, 'orders_store' => 50,
            ],
            [
                'id' => 'goog_camp_6', 'platform' => 'google', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Brand Search — Phrase — DE',
                'spend' => 890.00, 'impressions' => 28000, 'clicks' => 9800,
                'ctr' => 35.00, 'cpc' => 0.091, 'cpm' => 31.79,
                'cpa_first_time' => 11.87, 'cvr' => 7.65,
                'attributed_revenue_real' => 6140.00,
                'attributed_revenue_platform' => 6700.00,
                'attributed_revenue_store' => 6000.00,
                'roas_real' => 6.90, 'roas_platform' => 7.53, 'roas_store' => 6.74,
                'sparkline_roas_14d' => [6.5,6.7,6.9,6.8,7.0,6.9,6.7,6.8,7.0,6.9,6.8,7.0,6.9,6.90],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 75, 'orders_store' => 73,
            ],
            [
                'id' => 'fb_camp_12', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'UGC Test — Static vs Video — US',
                'spend' => 720.00, 'impressions' => 68000, 'clicks' => 1700,
                'ctr' => 2.50, 'cpc' => 0.42, 'cpm' => 10.59,
                'cpa_first_time' => 36.00, 'cvr' => 2.94,
                'attributed_revenue_real' => 1872.00,
                'attributed_revenue_platform' => 3780.00,
                'attributed_revenue_store' => 1750.00,
                'roas_real' => 2.60, 'roas_platform' => 5.25, 'roas_store' => 2.43,
                'sparkline_roas_14d' => [2.3,2.4,2.5,2.6,2.5,2.7,2.6,2.5,2.6,2.7,2.5,2.6,2.7,2.60],
                'grade' => 'B', 'confidence' => 'low', 'attributed_signal' => 'paid',
                'purchases_platform' => 50, 'orders_store' => 40,
            ],
            [
                'id' => 'goog_camp_7', 'platform' => 'google', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Shopping — Bestsellers — UK',
                'spend' => 2200.00, 'impressions' => 140000, 'clicks' => 8400,
                'ctr' => 6.00, 'cpc' => 0.262, 'cpm' => 15.71,
                'cpa_first_time' => 22.00, 'cvr' => 11.90,
                'attributed_revenue_real' => 7040.00,
                'attributed_revenue_platform' => 7920.00,
                'attributed_revenue_store' => 6800.00,
                'roas_real' => 3.20, 'roas_platform' => 3.60, 'roas_store' => 3.09,
                'sparkline_roas_14d' => [3.0,3.1,3.2,3.1,3.3,3.2,3.1,3.2,3.3,3.2,3.1,3.2,3.3,3.20],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 200, 'orders_store' => 190,
            ],
            [
                'id' => 'fb_camp_13', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Offer — 20% Off — LAL 1% — FR',
                'spend' => 1640.00, 'impressions' => 160000, 'clicks' => 3440,
                'ctr' => 2.15, 'cpc' => 0.48, 'cpm' => 10.25,
                'cpa_first_time' => 28.27, 'cvr' => 2.41,
                'attributed_revenue_real' => 4756.00,
                'attributed_revenue_platform' => 9020.00,
                'attributed_revenue_store' => 4500.00,
                'roas_real' => 2.90, 'roas_platform' => 5.50, 'roas_store' => 2.74,
                'sparkline_roas_14d' => [2.7,2.8,2.9,2.8,3.0,2.9,2.8,3.0,2.9,2.8,2.9,3.0,2.9,2.90],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 116, 'orders_store' => 104,
            ],
            [
                'id' => 'fb_camp_14', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'High-Intent — Viewed Product 3x — US',
                'spend' => 460.00, 'impressions' => 18000, 'clicks' => 900,
                'ctr' => 5.00, 'cpc' => 0.51, 'cpm' => 25.56,
                'cpa_first_time' => 11.50, 'cvr' => 6.67,
                'attributed_revenue_real' => 3128.00,
                'attributed_revenue_platform' => 5750.00,
                'attributed_revenue_store' => 3000.00,
                'roas_real' => 6.80, 'roas_platform' => 12.50, 'roas_store' => 6.52,
                'sparkline_roas_14d' => [6.5,6.7,6.8,6.7,6.9,6.8,6.7,6.9,6.8,6.7,6.9,6.8,6.7,6.80],
                'grade' => 'A', 'confidence' => 'low', 'attributed_signal' => 'paid',
                'purchases_platform' => 40, 'orders_store' => 40,
            ],
            [
                'id' => 'goog_camp_8', 'platform' => 'google', 'status' => 'paused',
                'objective' => 'conversions',
                'name' => 'YouTube — Pre-Roll — 18-44 — US',
                'spend' => 2800.00, 'impressions' => 620000, 'clicks' => 3100,
                'ctr' => 0.50, 'cpc' => 0.903, 'cpm' => 4.52,
                'cpa_first_time' => 140.00, 'cvr' => 0.64,
                'attributed_revenue_real' => 1120.00,
                'attributed_revenue_platform' => 5040.00,
                'attributed_revenue_store' => 1000.00,
                'roas_real' => 0.40, 'roas_platform' => 1.80, 'roas_store' => 0.36,
                'sparkline_roas_14d' => [0.6,0.5,0.4,0.5,0.4,0.3,0.4,0.5,0.4,0.3,0.4,0.5,0.4,0.40],
                'grade' => 'F', 'confidence' => 'high', 'attributed_signal' => 'modeled',
                'purchases_platform' => 40, 'orders_store' => 20,
            ],
            [
                'id' => 'fb_camp_15', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Skincare Bundle — Offer — LAL 2% — US',
                'spend' => 3300.00, 'impressions' => 285000, 'clicks' => 6270,
                'ctr' => 2.20, 'cpc' => 0.53, 'cpm' => 11.58,
                'cpa_first_time' => 33.00, 'cvr' => 2.39,
                'attributed_revenue_real' => 9900.00,
                'attributed_revenue_platform' => 18150.00,
                'attributed_revenue_store' => 9400.00,
                'roas_real' => 3.00, 'roas_platform' => 5.50, 'roas_store' => 2.85,
                'sparkline_roas_14d' => [2.8,2.9,3.0,2.9,3.1,3.0,2.9,3.0,3.1,3.0,2.9,3.1,3.0,3.00],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 200, 'orders_store' => 180,
            ],
            [
                'id' => 'goog_camp_9', 'platform' => 'google', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Dynamic Search Ads — Category Pages — US',
                'spend' => 1100.00, 'impressions' => 65000, 'clicks' => 5200,
                'ctr' => 8.00, 'cpc' => 0.212, 'cpm' => 16.92,
                'cpa_first_time' => 27.50, 'cvr' => 7.69,
                'attributed_revenue_real' => 3960.00,
                'attributed_revenue_platform' => 4510.00,
                'attributed_revenue_store' => 3800.00,
                'roas_real' => 3.60, 'roas_platform' => 4.10, 'roas_store' => 3.45,
                'sparkline_roas_14d' => [3.3,3.4,3.6,3.5,3.7,3.6,3.5,3.6,3.7,3.6,3.5,3.6,3.7,3.60],
                'grade' => 'A', 'confidence' => 'high', 'attributed_signal' => 'paid',
                'purchases_platform' => 80, 'orders_store' => 76,
            ],
            [
                'id' => 'fb_camp_16', 'platform' => 'facebook', 'status' => 'active',
                'objective' => 'conversions',
                'name' => 'Mobile-Only — Story — 18-24 — US',
                'spend' => 840.00, 'impressions' => 92000, 'clicks' => 2300,
                'ctr' => 2.50, 'cpc' => 0.37, 'cpm' => 9.13,
                'cpa_first_time' => 42.00, 'cvr' => 1.74,
                'attributed_revenue_real' => 1428.00,
                'attributed_revenue_platform' => 4200.00,
                'attributed_revenue_store' => 1320.00,
                'roas_real' => 1.70, 'roas_platform' => 5.00, 'roas_store' => 1.57,
                'sparkline_roas_14d' => [2.0,1.9,1.8,1.7,1.6,1.7,1.8,1.7,1.6,1.7,1.8,1.7,1.7,1.70],
                'grade' => 'C', 'confidence' => 'low', 'attributed_signal' => 'modeled',
                'purchases_platform' => 40, 'orders_store' => 20,
            ],
        ];

        // Apply platform filter
        if ($platform !== 'all') {
            $campaigns = array_values(array_filter($campaigns, fn($c) => $c['platform'] === $platform));
        }
        if ($status !== 'all') {
            $campaigns = array_values(array_filter($campaigns, fn($c) => $c['status'] === $status));
        }

        // Aggregate KPIs from visible campaigns
        $totalSpend    = array_sum(array_column($campaigns, 'spend'));
        $totalRevenueR = array_sum(array_column($campaigns, 'attributed_revenue_real'));
        $totalRevenueP = array_sum(array_column($campaigns, 'attributed_revenue_platform'));
        $totalRevenueS = array_sum(array_column($campaigns, 'attributed_revenue_store'));
        $totalImpr     = array_sum(array_column($campaigns, 'impressions'));
        $totalClicks   = array_sum(array_column($campaigns, 'clicks'));
        $totalPurchP   = array_sum(array_column($campaigns, 'purchases_platform'));
        $totalOrdersS  = array_sum(array_column($campaigns, 'orders_store'));

        $blendedRoas   = $totalSpend > 0 ? round($totalRevenueR / $totalSpend, 2) : null;
        $blendedRoasP  = $totalSpend > 0 ? round($totalRevenueP / $totalSpend, 2) : null;
        $blendedRoasS  = $totalSpend > 0 ? round($totalRevenueS / $totalSpend, 2) : null;
        $cpm           = $totalImpr  > 0 ? round(($totalSpend / $totalImpr) * 1000, 2) : null;
        $cpc           = $totalClicks > 0 ? round($totalSpend / $totalClicks, 2) : null;
        $ctr           = $totalImpr  > 0 ? round(($totalClicks / $totalImpr) * 100, 2) : null;
        $cvr           = count($campaigns) > 0 ? round(array_sum(array_column($campaigns, 'cvr')) / count($campaigns), 2) : null;
        $notTracked    = $totalRevenueS > 0 ? round((($totalRevenueS - $totalRevenueR) / $totalRevenueS) * 100, 1) : 0;

        // Sample adsets: 2–3 per campaign (abbreviated for brevity — Wave 2 will wire real data)
        $adsets = [
            ['id' => 'fb_as_1', 'campaign_id' => 'fb_camp_1', 'platform' => 'facebook',
             'name' => 'LAL 1% — 18-44 — US — Mobile', 'status' => 'active',
             'spend' => 2140.00, 'impressions' => 205000, 'clicks' => 4100,
             'ctr' => 2.00, 'cpc' => 0.52, 'cpm' => 10.44,
             'cpa_first_time' => 32.42, 'cvr' => 2.38,
             'attributed_revenue_real' => 4173.00, 'attributed_revenue_platform' => 8350.00, 'attributed_revenue_store' => 3950.00,
             'roas_real' => 1.95, 'roas_platform' => 3.90, 'roas_store' => 1.85,
             'sparkline_roas_14d' => [1.8,1.9,2.0,1.9,2.0,2.1,1.9,1.8,1.9,2.0,1.9,1.8,1.9,1.95],
             'grade' => 'B', 'confidence' => 'high',
             'purchases_platform' => 160, 'orders_store' => 124],
            ['id' => 'fb_as_2', 'campaign_id' => 'fb_camp_1', 'platform' => 'facebook',
             'name' => 'LAL 1% — 25-54 — US — Desktop', 'status' => 'active',
             'spend' => 2147.00, 'impressions' => 207000, 'clicks' => 4100,
             'ctr' => 1.98, 'cpc' => 0.52, 'cpm' => 10.37,
             'cpa_first_time' => 31.57, 'cvr' => 2.41,
             'attributed_revenue_real' => 4247.00, 'attributed_revenue_platform' => 8480.00, 'attributed_revenue_store' => 4030.00,
             'roas_real' => 1.98, 'roas_platform' => 3.95, 'roas_store' => 1.88,
             'sparkline_roas_14d' => [1.8,1.9,2.0,2.0,2.1,2.0,1.9,2.0,2.1,2.0,1.9,2.0,2.1,1.98],
             'grade' => 'B', 'confidence' => 'high',
             'purchases_platform' => 160, 'orders_store' => 124],
            ['id' => 'goog_as_1', 'campaign_id' => 'goog_camp_1', 'platform' => 'google',
             'name' => 'Brand — Exact — Desktop', 'status' => 'active',
             'spend' => 840.00, 'impressions' => 28000, 'clicks' => 9800,
             'ctr' => 35.00, 'cpc' => 0.086, 'cpm' => 30.00,
             'cpa_first_time' => 9.33, 'cvr' => 9.18,
             'attributed_revenue_real' => 5964.00, 'attributed_revenue_platform' => 6534.00, 'attributed_revenue_store' => 5800.00,
             'roas_real' => 7.10, 'roas_platform' => 7.78, 'roas_store' => 6.90,
             'sparkline_roas_14d' => [6.8,7.0,7.2,7.1,7.0,7.2,7.3,7.1,7.0,7.2,7.1,7.0,7.2,7.10],
             'grade' => 'A', 'confidence' => 'high',
             'purchases_platform' => 90, 'orders_store' => 88],
        ];

        // Sample ads (abbreviated)
        $ads = [
            ['id' => 'fb_ad_1', 'adset_id' => 'fb_as_1', 'campaign_id' => 'fb_camp_1', 'platform' => 'facebook',
             'name' => 'Spring Sale 2026 — Hook A — Carousel', 'status' => 'active',
             'spend' => 1280.00, 'impressions' => 122000, 'clicks' => 2440,
             'ctr' => 2.00, 'cpc' => 0.52, 'cpm' => 10.49,
             'cpa_first_time' => 32.00, 'cvr' => 2.38,
             'attributed_revenue_real' => 2496.00, 'attributed_revenue_platform' => 4992.00, 'attributed_revenue_store' => 2350.00,
             'roas_real' => 1.95, 'roas_platform' => 3.90, 'roas_store' => 1.84,
             'sparkline_roas_14d' => [1.7,1.8,1.9,1.8,2.0,2.0,1.9,1.8,1.9,2.0,1.9,1.8,1.9,1.95],
             'grade' => 'B', 'confidence' => 'high', 'signal_type' => 'deterministic',
             'purchases_platform' => 95, 'orders_store' => 74,
             'thumbnail_url' => null],
            ['id' => 'fb_ad_2', 'adset_id' => 'fb_as_1', 'campaign_id' => 'fb_camp_1', 'platform' => 'facebook',
             'name' => 'Spring Sale 2026 — Hook B — Single Image', 'status' => 'active',
             'spend' => 860.00, 'impressions' => 83000, 'clicks' => 1660,
             'ctr' => 2.00, 'cpc' => 0.52, 'cpm' => 10.36,
             'cpa_first_time' => 32.31, 'cvr' => 2.35,
             'attributed_revenue_real' => 1677.00, 'attributed_revenue_platform' => 3354.00, 'attributed_revenue_store' => 1600.00,
             'roas_real' => 1.95, 'roas_platform' => 3.90, 'roas_store' => 1.86,
             'sparkline_roas_14d' => [1.8,1.9,2.0,1.9,2.0,2.1,1.9,1.8,1.9,2.0,1.9,1.8,1.9,1.95],
             'grade' => 'B', 'confidence' => 'high', 'signal_type' => 'deterministic',
             'purchases_platform' => 65, 'orders_store' => 50,
             'thumbnail_url' => null],
            ['id' => 'goog_ad_1', 'adset_id' => 'goog_as_1', 'campaign_id' => 'goog_camp_1', 'platform' => 'google',
             'name' => 'Brand Exact — Ad Variant A', 'status' => 'active',
             'spend' => 530.00, 'impressions' => 17000, 'clicks' => 5950,
             'ctr' => 35.00, 'cpc' => 0.089, 'cpm' => 31.18,
             'cpa_first_time' => 9.64, 'cvr' => 9.24,
             'attributed_revenue_real' => 3763.00, 'attributed_revenue_platform' => 4116.00, 'attributed_revenue_store' => 3660.00,
             'roas_real' => 7.10, 'roas_platform' => 7.77, 'roas_store' => 6.91,
             'sparkline_roas_14d' => [6.8,7.0,7.2,7.1,7.0,7.2,7.3,7.1,7.0,7.2,7.1,7.0,7.2,7.10],
             'grade' => 'A', 'confidence' => 'high', 'signal_type' => 'deterministic',
             'purchases_platform' => 55, 'orders_store' => 54,
             'thumbnail_url' => null],
        ];

        // Creatives (ads level with thumbnail)
        $creatives = $ads; // same rows, frontend shows thumbnail column

        // KPI strip with source-aware values
        $kpis = [
            'total_spend'       => $totalSpend,
            'revenue_real'      => $totalRevenueR,
            'revenue_platform'  => $totalRevenueP,
            'revenue_store'     => $totalRevenueS,
            'roas_real'         => $blendedRoas,
            'roas_platform'     => $blendedRoasP,
            'roas_store'        => $blendedRoasS,
            'cpm'               => $cpm,
            'cpc'               => $cpc,
            'ctr'               => $ctr,
            'cvr'               => $cvr,
            'cpa_first_time'    => $totalSpend > 0 ? round($totalSpend / max(1, $totalOrdersS), 2) : null,
            'purchases_platform'=> $totalPurchP,
            'orders_store'      => $totalOrdersS,
            'not_tracked_pct'   => $notTracked,
        ];

        // Spend vs Revenue chart series (last 14 days stub)
        $chartSeries = $this->buildChartSeries($from, $to);

        return Inertia::render('Ads/Index', [
            'filters'    => compact('from', 'to', 'platform', 'status', 'tab', 'attribution', 'window'),
            'kpis'       => $kpis,
            'campaigns'  => $campaigns,
            'adsets'     => $adsets,
            'ads'        => $ads,
            'creatives'  => $creatives,
            'chart_data' => $chartSeries,
            'roas_target'=> (float) ($workspace->target_roas ?? 2.0),
        ]);
    }

    /**
     * Build stub Spend vs Revenue chart series for the selected date range.
     * One point per day; oscillates realistically around a trend.
     *
     * @return array{spend: list<array{date:string,value:float}>, revenue_real: list<array{date:string,value:float}>, revenue_platform: list<array{date:string,value:float}>}
     */
    private function buildChartSeries(string $from, string $to): array
    {
        $spend      = [];
        $revenueR   = [];
        $revenueP   = [];

        $start = new \DateTime($from);
        $end   = new \DateTime($to);
        $i     = 0;

        while ($start <= $end) {
            $date     = $start->format('Y-m-d');
            $base     = 1800 + sin($i * 0.4) * 400 + ($i * 8);
            $s        = round($base + mt_rand(-120, 120), 2);
            $rR       = round($s * (1.8 + sin($i * 0.3) * 0.6), 2);
            $rP       = round($rR * (2.2 + sin($i * 0.2) * 0.4), 2);

            $spend[]    = ['date' => $date, 'value' => max(0, $s)];
            $revenueR[] = ['date' => $date, 'value' => max(0, $rR)];
            $revenueP[] = ['date' => $date, 'value' => max(0, $rP)];

            $start->modify('+1 day');
            $i++;
        }

        return compact('spend', 'revenueR', 'revenueP');
    }
}
