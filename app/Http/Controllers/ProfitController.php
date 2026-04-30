<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Profit & Loss page — /profit
 *
 * Stub controller returning mock P&L data for the Profit/Index.tsx frontend.
 * Frontend-only implementation; no DB queries. Replace with real service calls
 * once ProfitDataService is wired.
 *
 * Props consumed by: resources/js/Pages/Profit/Index.tsx
 *
 * @see docs/pages/profit.md
 * @see docs/planning/backend.md §10 ProfitController
 */
class ProfitController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'from'            => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to'              => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'accounting_mode' => ['sometimes', 'nullable', 'in:accrual,cash'],
        ]);

        $from           = $validated['from']            ?? now()->subDays(29)->toDateString();
        $to             = $validated['to']              ?? now()->toDateString();
        $accountingMode = $validated['accounting_mode'] ?? 'cash';

        // ── Mock data — 90-day realistic ecommerce P&L ───────────────────────
        // Revenue ~$120k/mo · COGS 30% · Shipping 5% · Fees 3%
        // Ad spend ~$20k/mo · OPEX $8k/mo · Net margin 15–22%
        // FB highest spend but lower margin (over-attribution pattern)

        $sparklineProfit   = [14200, 15800, 13400, 16200, 17100, 14900, 18420];
        $sparklineRevenue  = [118000, 122000, 115000, 124000, 128000, 119000, 124530];
        $sparklineMargin   = [14.2, 14.8, 13.6, 15.1, 15.8, 14.3, 14.8];
        $sparklineProfitRoas = [3.8, 4.1, 3.5, 4.2, 4.4, 4.0, 4.20];
        $sparklineCac      = [28.0, 26.5, 29.0, 25.0, 24.0, 27.5, 26.5];
        $sparklineCm       = [42.8, 43.5, 41.9, 44.2, 44.8, 43.1, 63.1];

        $kpis = [
            [
                'name'      => 'Net Profit',
                'qualifier' => '30d',
                'value'     => 18420.00,
                'currency'  => 'USD',
                'delta_pct' => 4.2,
                'source'    => 'real',
                'sparkline' => $sparklineProfit,
            ],
            [
                'name'      => 'Gross Margin',
                'qualifier' => '30d',
                'value'     => 70.0,
                'unit'      => 'pct',
                'delta_pct' => 0.8,
                'source'    => 'store',
                'sparkline' => $sparklineMargin,
            ],
            [
                'name'      => 'Net Margin',
                'qualifier' => '30d',
                'value'     => 14.8,
                'unit'      => 'pct',
                'delta_pct' => -0.6,
                'source'    => 'real',
                'sparkline' => $sparklineMargin,
            ],
            [
                'name'      => 'Profit ROAS',
                'qualifier' => '30d',
                'value'     => 4.20,
                'unit'      => 'x',
                'delta_pct' => 2.1,
                'source'    => 'real',
                'sparkline' => $sparklineProfitRoas,
            ],
            [
                'name'      => 'Profit-CAC (1st Time)',
                'qualifier' => '30d',
                'value'     => 26.50,
                'currency'  => 'USD',
                'delta_pct' => -3.4,
                'source'    => 'real',
                'sparkline' => $sparklineCac,
            ],
            [
                'name'      => 'Contribution Margin',
                'qualifier' => '30d',
                'value'     => 63.1,
                'unit'      => 'pct',
                'delta_pct' => 1.2,
                'source'    => 'real',
                'sparkline' => $sparklineCm,
            ],
        ];

        $waterfall = [
            ['label' => 'Revenue',          'value' => 124530, 'pct' => 100.0, 'is_start' => true],
            ['label' => 'COGS',             'value' => -37359, 'pct' => -30.0],
            ['label' => 'Shipping',         'value' =>  -6226, 'pct' =>  -5.0],
            ['label' => 'Transaction Fees', 'value' =>  -2491, 'pct' =>  -2.0],
            ['label' => 'Platform Fees',    'value' =>  -1245, 'pct' =>  -1.0],
            ['label' => 'Tax (net)',         'value' =>      0, 'pct' =>   0.0],
            ['label' => 'Ad Spend',         'value' => -19800, 'pct' => -15.9],
            ['label' => 'OPEX',             'value' =>  -8000, 'pct' =>  -6.4],
            ['label' => 'Net Profit',       'value' =>  49409, 'pct' =>  39.7, 'is_end' => true],
        ];

        // Per-channel P&L breakdown — pre-aggregated server-side.
        // FB has highest ad spend but lower margin (over-attribution thesis).
        $channelSparklines = [
            'facebook' => [12100, 13200, 11800, 13800, 14100, 12600, 13281],
            'google'   => [8200,  8800,  7900,  9100,  9400,  8600,  9104],
            'organic'  => [5100,  5400,  5200,  5600,  5800,  5300,  5832],
            'email'    => [3600,  3800,  3500,  3900,  4000,  3700,  4100],
            'direct'   => [2800,  3000,  2700,  3100,  3200,  2900,  3200],
        ];

        $breakdownByChannel = [
            [
                'name'       => 'Facebook Ads',
                'revenue'    => 42100,
                'cogs'       => 12630,
                'shipping'   => 2105,
                'fees'       => 884,
                'ad_spend'   => 13200,
                'net_profit' => 13281,
                'margin_pct' => 31.5,
                'sparkline'  => $channelSparklines['facebook'],
            ],
            [
                'name'       => 'Google Ads',
                'revenue'    => 28600,
                'cogs'       => 8580,
                'shipping'   => 1430,
                'fees'       => 601,
                'ad_spend'   => 6600,
                'net_profit' => 11389,
                'margin_pct' => 39.8,
                'sparkline'  => $channelSparklines['google'],
            ],
            [
                'name'       => 'Organic Search',
                'revenue'    => 21000,
                'cogs'       => 6300,
                'shipping'   => 1050,
                'fees'       => 441,
                'ad_spend'   => 0,
                'net_profit' => 13209,
                'margin_pct' => 62.9,
                'sparkline'  => $channelSparklines['organic'],
            ],
            [
                'name'       => 'Email / SMS',
                'revenue'    => 18400,
                'cogs'       => 5520,
                'shipping'   => 920,
                'fees'       => 386,
                'ad_spend'   => 0,
                'net_profit' => 11574,
                'margin_pct' => 62.9,
                'sparkline'  => $channelSparklines['email'],
            ],
            [
                'name'       => 'Direct',
                'revenue'    => 14430,
                'cogs'       => 4329,
                'shipping'   => 721,
                'fees'       => 303,
                'ad_spend'   => 0,
                'net_profit' => 9077,
                'margin_pct' => 62.9,
                'sparkline'  => $channelSparklines['direct'],
            ],
        ];

        $breakdownByStore = [
            ['name' => 'Main Store (US)',  'revenue' => 89000, 'cogs' => 26700, 'shipping' => 4450, 'fees' => 1869, 'net_profit' => 37181, 'margin_pct' => 41.8, 'sparkline' => [34000, 36000, 33000, 37500, 38000, 36000, 37181]],
            ['name' => 'EU Store',         'revenue' => 24000, 'cogs' => 7200,  'shipping' => 1200, 'fees' =>  504, 'net_profit' => 10896, 'margin_pct' => 45.4, 'sparkline' => [9500, 10200, 9100, 10800, 11000, 10400, 10896]],
            ['name' => 'Canada Store',     'revenue' => 11530, 'cogs' => 3459,  'shipping' =>  576, 'fees' =>  242, 'net_profit' =>  4853, 'margin_pct' => 42.1, 'sparkline' => [4200, 4600, 4100, 5000, 5100, 4800, 4853]],
        ];

        $breakdownByProduct = [
            ['name' => 'Premium Widget A',  'revenue' => 38000, 'cogs' => 11400, 'shipping' => 1900, 'fees' => 798, 'net_profit' => 15202, 'margin_pct' => 40.0, 'sparkline' => [14000, 15000, 13500, 15800, 16200, 14900, 15202]],
            ['name' => 'Classic Widget B',  'revenue' => 27500, 'cogs' =>  8250, 'shipping' => 1375, 'fees' => 578, 'net_profit' => 10997, 'margin_pct' => 40.0, 'sparkline' => [10100, 10800, 9800, 11400, 11600, 10700, 10997]],
            ['name' => 'Bundle Pack',       'revenue' => 22000, 'cogs' =>  5500, 'shipping' =>  880, 'fees' => 462, 'net_profit' => 10958, 'margin_pct' => 49.8, 'sparkline' => [9800, 10500, 9400, 11200, 11400, 10600, 10958]],
            ['name' => 'Starter Kit',       'revenue' => 18000, 'cogs' =>  6300, 'shipping' => 1080, 'fees' => 378, 'net_profit' =>  6242, 'margin_pct' => 34.7, 'sparkline' => [5800, 6100, 5500, 6500, 6600, 6100, 6242]],
            ['name' => 'Accessories',       'revenue' => 19030, 'cogs' =>  5709, 'shipping' =>  991, 'fees' => 399, 'net_profit' =>  7731, 'margin_pct' => 40.6, 'sparkline' => [7100, 7600, 6900, 8100, 8200, 7600, 7731]],
        ];

        $breakdownByCategory = [
            ['name' => 'Electronics',   'revenue' => 56000, 'cogs' => 19600, 'shipping' => 2800, 'fees' => 1176, 'net_profit' => 19624, 'margin_pct' => 35.0, 'sparkline' => [18000, 19500, 17800, 20500, 20800, 19200, 19624]],
            ['name' => 'Accessories',   'revenue' => 38000, 'cogs' => 11400, 'shipping' => 1900, 'fees' =>  798, 'net_profit' => 16002, 'margin_pct' => 42.1, 'sparkline' => [14800, 15800, 14200, 16800, 17000, 15700, 16002]],
            ['name' => 'Bundles',       'revenue' => 22000, 'cogs' =>  5500, 'shipping' =>  880, 'fees' =>  462, 'net_profit' => 10958, 'margin_pct' => 49.8, 'sparkline' => [9800, 10500, 9400, 11200, 11400, 10600, 10958]],
            ['name' => 'Other',         'revenue' =>  8530, 'cogs' =>  2559, 'shipping' =>  646, 'fees' =>  179, 'net_profit' =>  2946, 'margin_pct' => 34.5, 'sparkline' => [2500, 2800, 2400, 3000, 3100, 2800, 2946]],
        ];

        $breakdownByCountry = [
            ['name' => 'United States',  'revenue' => 89000, 'cogs' => 26700, 'shipping' => 4450, 'fees' => 1869, 'net_profit' => 37181, 'margin_pct' => 41.8, 'sparkline' => [34000, 36000, 33000, 37500, 38000, 36000, 37181]],
            ['name' => 'Germany',        'revenue' => 14500, 'cogs' =>  4350, 'shipping' =>  725, 'fees' =>  305, 'net_profit' =>  6220, 'margin_pct' => 42.9, 'sparkline' => [5700, 6100, 5500, 6500, 6700, 6100, 6220]],
            ['name' => 'United Kingdom', 'revenue' => 10800, 'cogs' =>  3240, 'shipping' =>  540, 'fees' =>  227, 'net_profit' =>  4493, 'margin_pct' => 41.6, 'sparkline' => [4100, 4400, 4000, 4700, 4800, 4400, 4493]],
            ['name' => 'Canada',         'revenue' => 10230, 'cogs' =>  3069, 'shipping' =>  512, 'fees' =>  215, 'net_profit' =>  4234, 'margin_pct' => 41.4, 'sparkline' => [3900, 4100, 3700, 4400, 4500, 4100, 4234]],
        ];

        $breakdownByCustomerType = [
            ['name' => 'New Customers',       'revenue' => 68000, 'cogs' => 20400, 'shipping' => 3400, 'fees' => 1428, 'net_profit' => 24772, 'margin_pct' => 36.4, 'sparkline' => [22800, 24500, 22000, 25800, 26200, 24200, 24772]],
            ['name' => 'Returning Customers', 'revenue' => 56530, 'cogs' => 16959, 'shipping' => 2826, 'fees' => 1187, 'net_profit' => 24658, 'margin_pct' => 43.6, 'sparkline' => [22700, 24300, 21900, 25700, 26100, 24100, 24658]],
        ];

        // ── 90-day weekly trend ──────────────────────────────────────────────
        // Weeks from ~13 weeks ago; Net Profit + Revenue per week.
        $trend90d = array_map(function (int $weeksAgo) {
            $date      = now()->subWeeks($weeksAgo)->startOfWeek()->toDateString();
            $revenue   = 28000 + (mt_rand(-2000, 3000));
            $netProfit = (int) ($revenue * (0.14 + (mt_rand(0, 8) / 100)));
            return [
                'date'       => $date,
                'revenue'    => $revenue,
                'net_profit' => $netProfit,
            ];
        }, range(13, 0));

        $opexAllocations = [
            ['label' => 'Salaries', 'monthly' => 5000],
            ['label' => 'Rent',     'monthly' => 1500],
            ['label' => 'Software', 'monthly' =>  800],
            ['label' => 'Other',    'monthly' =>  700],
        ];

        $costCompleteness = [
            'cogs_coverage_pct'     => 94,
            'shipping_coverage_pct' => 88,
            'fees_coverage_pct'     => 100,
            'tax_coverage_pct'      => 96,
        ];

        return Inertia::render('Profit/Index', [
            'kpis'                       => $kpis,
            'waterfall'                  => $waterfall,
            'breakdown_by_channel'       => $breakdownByChannel,
            'breakdown_by_store'         => $breakdownByStore,
            'breakdown_by_product'       => $breakdownByProduct,
            'breakdown_by_category'      => $breakdownByCategory,
            'breakdown_by_country'       => $breakdownByCountry,
            'breakdown_by_customer_type' => $breakdownByCustomerType,
            'trend_90d'                  => $trend90d,
            'opex_allocations'           => $opexAllocations,
            'cost_completeness'          => $costCompleteness,
            'filters'                    => [
                'from'            => $from,
                'to'              => $to,
                'accounting_mode' => $accountingMode,
            ],
        ]);
    }
}
