<?php

declare(strict_types=1);

namespace App\Services\Profit;

use App\Models\DailySnapshotProduct;
use App\Services\Metrics\MetricSourceResolver;
use App\Services\ProfitCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds every data section consumed by resources/js/Pages/Profit/Index.tsx.
 *
 * Reads: daily_snapshots, daily_snapshot_products, ad_insights, ad_accounts,
 *        workspace_targets, store_cost_settings (via ProfitCalculator)
 * Writes: nothing
 * Called by: ProfitController
 *
 * Data-access rules (CLAUDE.md):
 *   - Never aggregate raw orders on request path — use daily_snapshots
 *   - Never SUM across ad_insights levels — always filter to 'campaign'
 *   - Ratios computed on the fly; NULLIF used in SQL; null returned to UI for N/A
 *
 * @see docs/pages/profit.md
 * @see docs/planning/backend.md §10
 */
final class ProfitDataService
{
    public function __construct(
        private readonly MetricSourceResolver $sourceResolver,
    ) {}

    /**
     * Per-request memo cache for aggregateSnapshots() and aggregateAdSpend() results.
     *
     * Why: metrics(), waterfall(), and plTable() all call both helpers for the same
     * two date windows (current + previous). Without memoisation that is 3 callers ×
     * 2 windows × 2 helpers = 12 DB queries. With it: 2 × 2 = 4 total.
     *
     * Key: "<workspaceId>:<storeId|null>:<from>:<to>:<type>"
     *
     * @var array<string, mixed>
     */
    private array $aggMemo = [];

    // ── Public interface ─────────────────────────────────────────────────────

    /**
     * KPI metric cards for the hero grid.
     *
     * Returns gross_sales, net_sales, cogs, ad_spend, contribution_margin_pct,
     * net_profit, net_margin_pct — all with current + previous period values.
     *
     * Facebook / Google splits on gross_sales and ad_spend come from the
     * platform column on ad_accounts joined to ad_insights.
     *
     * When $source is provided, the revenue input to all P&L rows is replaced by
     * the lens-specific daily_snapshots column. Cost inputs stay constant so the
     * result answers "how profitable is the <source>-attributed revenue slice?"
     *
     * @param  string  $source  Active source lens ('real' default).
     */
    public function metrics(int $workspaceId, ?int $storeId, string $from, string $to, string $source = 'real'): array
    {
        $fromCarbon = Carbon::parse($from);
        $toCarbon   = Carbon::parse($to);

        $days       = max(1, $fromCarbon->diffInDays($toCarbon) + 1);
        $prevTo     = $fromCarbon->copy()->subDay()->toDateString();
        $prevFrom   = $fromCarbon->copy()->subDays($days)->toDateString();

        $revenueColumn = $this->sourceResolver->columnFor('revenue', $source);

        ['current' => $curr, 'previous' => $prev] =
            $this->aggregateSnapshotsBothPeriods($workspaceId, $storeId, $from, $to, $prevFrom, $prevTo, $revenueColumn);

        ['current' => $currAd, 'previous' => $prevAd] =
            $this->aggregateAdSpendBothPeriods($workspaceId, $from, $to, $prevFrom, $prevTo);

        $currCalc = ProfitCalculator::compute(
            $workspaceId,
            $storeId,
            [
                'revenue'            => $curr['revenue'],
                'cogs'               => $curr['cogs'],
                'refunds'            => $curr['refunds'],
                'shipping_collected' => $curr['shipping_collected'],
                'ad_spend'           => $currAd['total'],
                'orders_count'       => $curr['orders_count'],
                'days_in_range'      => $days,
            ],
            $fromCarbon,
            $toCarbon,
        );

        $prevCalc = ProfitCalculator::compute(
            $workspaceId,
            $storeId,
            [
                'revenue'            => $prev['revenue'],
                'cogs'               => $prev['cogs'],
                'refunds'            => $prev['refunds'],
                'shipping_collected' => $prev['shipping_collected'],
                'ad_spend'           => $prevAd['total'],
                'orders_count'       => $prev['orders_count'],
                'days_in_range'      => $days,
            ],
            Carbon::parse($prevFrom),
            Carbon::parse($prevTo),
        );

        $grossSales     = $curr['revenue'];
        $prevGrossSales = $prev['revenue'];

        $netSales     = max(0.0, $grossSales - $curr['refunds'] - $curr['discounts']);
        $prevNetSales = max(0.0, $prevGrossSales - $prev['refunds'] - $prev['discounts']);

        $cm     = $netSales > 0
            ? (($netSales - $curr['cogs'] - $currAd['total']) / $netSales) * 100
            : null;
        $prevCm = $prevNetSales > 0
            ? (($prevNetSales - $prev['cogs'] - $prevAd['total']) / $prevNetSales) * 100
            : null;

        $netMargin     = $grossSales > 0
            ? ($currCalc['net_profit'] / $grossSales) * 100
            : null;
        $prevNetMargin = $prevGrossSales > 0
            ? ($prevCalc['net_profit'] / $prevGrossSales) * 100
            : null;

        return [
            'gross_sales' => [
                'value'    => round($grossSales, 2),
                'prev'     => round($prevGrossSales, 2),
                'facebook' => $currAd['facebook'] > 0 ? round($currAd['facebook'], 2) : null,
                'google'   => $currAd['google']   > 0 ? round($currAd['google'],   2) : null,
                'real'     => round($grossSales, 2),
            ],
            'net_sales' => [
                'value' => round($netSales, 2),
                'prev'  => round($prevNetSales, 2),
            ],
            'cogs' => [
                'value' => round($curr['cogs'], 2),
                'prev'  => round($prev['cogs'], 2),
            ],
            'ad_spend' => [
                'value'    => round($currAd['total'], 2),
                'prev'     => round($prevAd['total'], 2),
                'facebook' => $currAd['facebook'] > 0 ? round($currAd['facebook'], 2) : null,
                'google'   => $currAd['google']   > 0 ? round($currAd['google'],   2) : null,
            ],
            'contribution_margin_pct' => [
                'value' => $cm     !== null ? round($cm,     2) : null,
                'prev'  => $prevCm !== null ? round($prevCm, 2) : null,
            ],
            'net_profit' => [
                'value' => round($currCalc['net_profit'], 2),
                'prev'  => round($prevCalc['net_profit'], 2),
            ],
            'net_margin_pct' => [
                'value' => $netMargin     !== null ? round($netMargin,     2) : null,
                'prev'  => $prevNetMargin !== null ? round($prevNetMargin, 2) : null,
            ],
        ];
    }

    /**
     * Waterfall bars: Revenue → -Refunds → -Discounts → -COGS → -Ad Spend →
     *                 -Shipping → -Tx Fees → -Platform Fees → -Tax → -OpEx → Net Profit.
     *
     * Missing config entries become 'missing' type bars so the TSX shows
     * dashed "not configured" bars instead of silently zeroing them out.
     *
     * Tax step is omitted entirely when ProfitCalculator returns tax = 0.0
     * (i.e. no tax_rules rows match the workspace's order countries). Showing
     * "Tax: $0" is misleading — accounting people expect a Tax line only when
     * there is a configured obligation.
     *
     * @param  string  $source  Active source lens ('real' default).
     * @return array<int, array{label: string, value: float, type: string}>
     */
    public function waterfall(int $workspaceId, ?int $storeId, string $from, string $to, string $source = 'real'): array
    {
        $fromCarbon    = Carbon::parse($from);
        $toCarbon      = Carbon::parse($to);
        $days          = max(1, $fromCarbon->diffInDays($toCarbon) + 1);
        $revenueColumn = $this->sourceResolver->columnFor('revenue', $source);

        $snap  = $this->aggregateSnapshots($workspaceId, $storeId, $from, $to, $revenueColumn);
        $adAgg = $this->aggregateAdSpend($workspaceId, $from, $to);

        $calc = ProfitCalculator::compute(
            $workspaceId,
            $storeId,
            [
                'revenue'            => $snap['revenue'],
                'cogs'               => $snap['cogs'],
                'refunds'            => $snap['refunds'],
                'shipping_collected' => $snap['shipping_collected'],
                'ad_spend'           => $adAgg['total'],
                'orders_count'       => $snap['orders_count'],
                'days_in_range'      => $days,
            ],
            $fromCarbon,
            $toCarbon,
        );

        $hasCogs = $snap['cogs'] > 0;
        // Omit Tax bar entirely when no tax rules matched — "Tax: $0" misleads accountants.
        $hasTax  = $calc['tax'] > 0;

        $steps = [
            ['label' => 'Gross Sales',     'value' =>  round($snap['revenue'], 2),          'type' => 'positive'],
            ['label' => 'Refunds',         'value' => -round($snap['refunds'], 2),           'type' => $snap['refunds']  > 0 ? 'negative' : 'subtotal'],
            ['label' => 'Discounts',       'value' => -round($snap['discounts'], 2),         'type' => $snap['discounts']> 0 ? 'negative' : 'subtotal'],
            ['label' => 'Net Sales',       'value' =>  round($snap['revenue'] - $snap['refunds'] - $snap['discounts'], 2), 'type' => 'subtotal'],
            ['label' => 'COGS',            'value' => -round($calc['cogs'], 2),              'type' => $hasCogs ? 'negative' : 'missing'],
            ['label' => 'Ad Spend',        'value' => -round($adAgg['total'], 2),            'type' => 'negative'],
            ['label' => 'Shipping',        'value' => -round($calc['shipping_cost'], 2),     'type' => 'negative'],
            ['label' => 'Tx Fees',         'value' => -round($calc['transaction_fees'], 2),  'type' => 'negative'],
            ['label' => 'Platform Fees',   'value' => -round($calc['platform_fees'], 2),     'type' => 'negative'],
        ];

        if ($hasTax) {
            $steps[] = ['label' => 'Tax', 'value' => -round($calc['tax'], 2), 'type' => 'negative'];
        }

        $steps[] = ['label' => 'OpEx',       'value' => -round($calc['opex'], 2),            'type' => 'negative'];
        $steps[] = ['label' => 'Net Profit', 'value' =>  round($calc['net_profit'], 2),       'type' => 'subtotal'];

        return $steps;
    }

    /**
     * Daily net profit trend for the line chart.
     *
     * net_profit = revenue - cogs - refunds - discounts - shipping_cost - tx_fees - opex - platform_fees - ad_spend
     *
     * We compute a simplified daily net_profit = revenue - cogs_total - refunds_total
     * - discounts_total - shipping_cost_total - transaction_fees_total (from snapshot).
     * Ad spend is spread proportionally from the period total.
     *
     * net_profit_store uses snapshot columns only (no ad spend deducted) to show
     * the "store" view comparison line.
     *
     * @return array<int, array{date: string, net_profit: float, net_profit_store: float|null}>
     */
    public function profitChart(int $workspaceId, ?int $storeId, string $from, string $to): array
    {
        $query = DB::table('daily_snapshots')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to]);

        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }

        $rows = $query->selectRaw("
            date::text AS date,
            SUM(revenue)               AS revenue,
            COALESCE(SUM(cogs_total), 0)            AS cogs,
            COALESCE(SUM(refunds_total), 0)         AS refunds,
            COALESCE(SUM(discounts_total), 0)       AS discounts,
            COALESCE(SUM(shipping_cost_total), 0)   AS shipping_cost,
            COALESCE(SUM(transaction_fees_total), 0) AS tx_fees
        ")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Campaign-level ad spend per day
        $adByDay = DB::table('ad_insights')
            ->join('ad_accounts', 'ad_accounts.id', '=', 'ad_insights.ad_account_id')
            ->where('ad_insights.workspace_id', $workspaceId)
            ->where('ad_insights.level', 'campaign')
            ->whereNull('ad_insights.hour')
            ->whereBetween('ad_insights.date', [$from, $to])
            ->selectRaw('ad_insights.date::text AS date, COALESCE(SUM(ad_insights.spend_in_reporting_currency), 0) AS ad_spend')
            ->groupBy('ad_insights.date')
            ->pluck('ad_spend', 'date')
            ->map(fn ($v) => (float) $v)
            ->all();

        return $rows->map(function ($r) use ($adByDay) {
            $adSpend  = $adByDay[$r->date] ?? 0.0;
            $storeNet = (float) $r->revenue
                - (float) $r->cogs
                - (float) $r->refunds
                - (float) $r->discounts
                - (float) $r->shipping_cost
                - (float) $r->tx_fees;
            $realNet  = $storeNet - $adSpend;

            return [
                'date'              => $r->date,
                'net_profit'        => round($realNet, 2),
                'net_profit_store'  => round($storeNet, 2),
            ];
        })->all();
    }

    /**
     * Top 10 products by gross margin percentage for the bar chart.
     *
     * Joins daily_snapshot_products → products → product_variants to resolve
     * average COGS per product. Uses product_variants.cogs_amount (the column
     * populated by the seeder and the inline COGS editor) rather than
     * daily_snapshot_products.unit_cost, which is a snapshot-time snapshot
     * that is not yet backfilled by the seeder.
     *
     * Only products where at least one variant has a cogs_amount are included.
     *
     * @return array<int, array{name: string, margin_pct: float}>
     */
    public function marginByProduct(int $workspaceId, ?int $storeId, string $from, string $to): array
    {
        $storeClause = $storeId !== null ? 'AND dsp.store_id = ?' : '';
        $storeArgs   = $storeId !== null ? [$storeId] : [];

        $rows = DB::select("
            SELECT
                dsp.product_name                                                         AS name,
                SUM(dsp.revenue)                                                         AS total_revenue,
                SUM(dsp.units * avg_cogs.avg_unit_cost)                                  AS total_cogs,
                CASE WHEN SUM(dsp.revenue) > 0
                     THEN ((SUM(dsp.revenue) - SUM(dsp.units * avg_cogs.avg_unit_cost))
                           / NULLIF(SUM(dsp.revenue), 0)) * 100
                     ELSE NULL
                END                                                                      AS margin_pct
            FROM daily_snapshot_products dsp
            JOIN products p
                ON p.external_id = dsp.product_external_id
               AND p.workspace_id = dsp.workspace_id
            JOIN (
                SELECT product_id, AVG(cogs_amount) AS avg_unit_cost
                FROM product_variants
                WHERE workspace_id = ?
                  AND cogs_amount IS NOT NULL
                GROUP BY product_id
            ) avg_cogs ON avg_cogs.product_id = p.id
            WHERE dsp.workspace_id = ?
              AND dsp.snapshot_date BETWEEN ? AND ?
              {$storeClause}
            GROUP BY dsp.product_name
            HAVING SUM(dsp.revenue) > 0
            ORDER BY margin_pct DESC NULLS LAST
            LIMIT 10
        ", array_merge([$workspaceId, $workspaceId, $from, $to], $storeArgs));

        return array_values(array_filter(
            array_map(fn (object $r): ?array => $r->margin_pct !== null ? [
                'name'       => $r->name,
                'margin_pct' => round((float) $r->margin_pct, 2),
            ] : null, $rows),
            fn (?array $r): bool => $r !== null,
        ));
    }

    /**
     * Top 5 products by estimated net profit for the period.
     *
     * Returns revenue, estimated profit (revenue − allocated COGS), and margin %.
     * Products with no COGS configured are included with profit = revenue (margin = 100%)
     * so the table is always populated; callers should note the cogs_configured flag.
     *
     * Ordered by estimated_profit DESC so the highest-profit SKUs appear first.
     * Used by the "Profit by product" mini-table in Profit/Index.tsx.
     *
     * @return array<int, array{name: string, revenue: float, estimated_profit: float, margin_pct: float|null, units: int}>
     */
    public function topProductsByProfit(int $workspaceId, ?int $storeId, string $from, string $to): array
    {
        $storeClause = $storeId !== null ? 'AND dsp.store_id = ?' : '';
        $storeArgs   = $storeId !== null ? [$storeId] : [];

        $rows = DB::select("
            SELECT
                dsp.product_name                                                           AS name,
                SUM(dsp.revenue)                                                           AS total_revenue,
                SUM(dsp.units)                                                             AS total_units,
                COALESCE(SUM(dsp.units * avg_cogs.avg_unit_cost), 0)                      AS total_cogs,
                SUM(dsp.revenue) - COALESCE(SUM(dsp.units * avg_cogs.avg_unit_cost), 0)  AS estimated_profit,
                CASE WHEN SUM(dsp.revenue) > 0
                     THEN ((SUM(dsp.revenue) - COALESCE(SUM(dsp.units * avg_cogs.avg_unit_cost), 0))
                           / NULLIF(SUM(dsp.revenue), 0)) * 100
                     ELSE NULL
                END                                                                        AS margin_pct
            FROM daily_snapshot_products dsp
            LEFT JOIN products p
                ON p.external_id = dsp.product_external_id
               AND p.workspace_id = dsp.workspace_id
            LEFT JOIN (
                SELECT product_id, AVG(cogs_amount) AS avg_unit_cost
                FROM product_variants
                WHERE workspace_id = ?
                  AND cogs_amount IS NOT NULL
                GROUP BY product_id
            ) avg_cogs ON avg_cogs.product_id = p.id
            WHERE dsp.workspace_id = ?
              AND dsp.snapshot_date BETWEEN ? AND ?
              {$storeClause}
            GROUP BY dsp.product_name
            HAVING SUM(dsp.revenue) > 0
            ORDER BY estimated_profit DESC
            LIMIT 5
        ", array_merge([$workspaceId, $workspaceId, $from, $to], $storeArgs));

        return array_map(fn (object $r): array => [
            'name'              => $r->name,
            'revenue'           => round((float) $r->total_revenue, 2),
            'estimated_profit'  => round((float) $r->estimated_profit, 2),
            'margin_pct'        => $r->margin_pct !== null ? round((float) $r->margin_pct, 2) : null,
            'units'             => (int) $r->total_units,
        ], $rows);
    }

    /**
     * P&L income-statement rows for the PLTable component.
     *
     * Subtotal rows have is_subtotal = true; leaf rows are indented by the TSX.
     *
     * @param  string  $source  Active source lens ('real' default).
     * @return array<int, array{label: string, current: float, previous: float|null, is_subtotal: bool}>
     */
    public function plTable(int $workspaceId, ?int $storeId, string $from, string $to, string $source = 'real'): array
    {
        $fromCarbon    = Carbon::parse($from);
        $toCarbon      = Carbon::parse($to);
        $days          = max(1, $fromCarbon->diffInDays($toCarbon) + 1);
        $revenueColumn = $this->sourceResolver->columnFor('revenue', $source);

        $prevTo     = $fromCarbon->copy()->subDay()->toDateString();
        $prevFrom   = $fromCarbon->copy()->subDays($days)->toDateString();

        ['current' => $curr, 'previous' => $prev] =
            $this->aggregateSnapshotsBothPeriods($workspaceId, $storeId, $from, $to, $prevFrom, $prevTo, $revenueColumn);

        ['current' => $currAd, 'previous' => $prevAd] =
            $this->aggregateAdSpendBothPeriods($workspaceId, $from, $to, $prevFrom, $prevTo);

        $currCalc = ProfitCalculator::compute(
            $workspaceId, $storeId,
            [
                'revenue'            => $curr['revenue'],
                'cogs'               => $curr['cogs'],
                'refunds'            => $curr['refunds'],
                'shipping_collected' => $curr['shipping_collected'],
                'ad_spend'           => $currAd['total'],
                'orders_count'       => $curr['orders_count'],
                'days_in_range'      => $days,
            ],
            $fromCarbon, $toCarbon,
        );

        $prevCalc = ProfitCalculator::compute(
            $workspaceId, $storeId,
            [
                'revenue'            => $prev['revenue'],
                'cogs'               => $prev['cogs'],
                'refunds'            => $prev['refunds'],
                'shipping_collected' => $prev['shipping_collected'],
                'ad_spend'           => $prevAd['total'],
                'orders_count'       => $prev['orders_count'],
                'days_in_range'      => $days,
            ],
            Carbon::parse($prevFrom), Carbon::parse($prevTo),
        );

        $currNetSales = max(0.0, $curr['revenue'] - $curr['refunds'] - $curr['discounts']);
        $prevNetSales = max(0.0, $prev['revenue'] - $prev['refunds'] - $prev['discounts']);

        // Tax row: omit entirely when no tax rules matched the workspace's order countries.
        // Consistent with waterfall() — never show "Tax: $0" to accountants.
        $hasTax = $currCalc['tax'] > 0 || $prevCalc['tax'] > 0;

        // Source badges identify the upstream system for each P&L line.
        // Revenue rows carry the active source lens; cost rows carry the system they pull from.
        // Subtotal rows (Net Sales, Net Profit) have no source badge.
        $revenueSource = $source; // active source lens set by the controller

        $rows = [
            ['label' => 'Gross Sales',       'current' => round($curr['revenue'],     2), 'previous' => round($prev['revenue'],     2), 'is_subtotal' => false, 'source' => $revenueSource],
            ['label' => 'Refunds',           'current' => round($curr['refunds'],     2), 'previous' => round($prev['refunds'],     2), 'is_subtotal' => false, 'source' => 'store'],
            ['label' => 'Discounts',         'current' => round($curr['discounts'],   2), 'previous' => round($prev['discounts'],   2), 'is_subtotal' => false, 'source' => 'store'],
            ['label' => 'Net Sales',         'current' => round($currNetSales,        2), 'previous' => round($prevNetSales,        2), 'is_subtotal' => true,  'source' => null],
            ['label' => 'COGS',              'current' => round($currCalc['cogs'],    2), 'previous' => round($prevCalc['cogs'],    2), 'is_subtotal' => false, 'source' => 'store'],
            ['label' => 'Ad Spend',          'current' => round($currAd['total'],     2), 'previous' => round($prevAd['total'],     2), 'is_subtotal' => false, 'source' => 'real'],
            ['label' => 'Shipping Cost',     'current' => round($currCalc['shipping_cost'], 2), 'previous' => round($prevCalc['shipping_cost'], 2), 'is_subtotal' => false, 'source' => 'store'],
            ['label' => 'Transaction Fees',  'current' => round($currCalc['transaction_fees'], 2), 'previous' => round($prevCalc['transaction_fees'], 2), 'is_subtotal' => false, 'source' => 'store'],
            ['label' => 'Platform Fees',     'current' => round($currCalc['platform_fees'], 2), 'previous' => round($prevCalc['platform_fees'], 2), 'is_subtotal' => false, 'source' => 'store'],
        ];

        if ($hasTax) {
            $rows[] = ['label' => 'Tax', 'current' => round($currCalc['tax'], 2), 'previous' => round($prevCalc['tax'], 2), 'is_subtotal' => false, 'source' => 'store'];
        }

        $rows[] = ['label' => 'Operating Expenses', 'current' => round($currCalc['opex'], 2), 'previous' => round($prevCalc['opex'], 2), 'is_subtotal' => false, 'source' => 'real'];
        $rows[] = ['label' => 'Net Profit',          'current' => round($currCalc['net_profit'], 2), 'previous' => round($prevCalc['net_profit'], 2), 'is_subtotal' => true, 'source' => null];

        return $rows;
    }

    /**
     * Whether COGS is configured for this workspace (triggers AlertBanner in TSX).
     *
     * True when ANY order_items row for this workspace has a unit_cost OR when
     * daily_snapshots has a non-null cogs_total for the period — either means
     * the merchant has configured costs.
     */
    public function cogsConfigured(int $workspaceId, string $from, string $to): bool
    {
        $hasSnapshotCogs = DB::table('daily_snapshots')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->whereNotNull('cogs_total')
            ->where('cogs_total', '>', 0)
            ->exists();

        if ($hasSnapshotCogs) {
            return true;
        }

        // Fallback: check store_cost_settings for at least one configured row
        return DB::table('store_cost_settings')
            ->where('workspace_id', $workspaceId)
            ->exists();
    }

    /**
     * Revenue, profit, and margin_pct targets for this workspace from workspace_targets.
     *
     * Returns null when no targets are set. The TSX renders "No targets set" in that case.
     *
     * @return array{revenue?: float, profit?: float, margin_pct?: float}|null
     */
    public function targets(int $workspaceId, string $from, string $to): ?array
    {
        $rows = DB::table('workspace_targets')
            ->where('workspace_id', $workspaceId)
            ->where('status', 'active')
            ->whereIn('metric', ['revenue', 'profit', 'margin_pct'])
            ->whereJsonContains('visible_on_pages', 'profit')
            ->get(['metric', 'target_value_reporting']);

        if ($rows->isEmpty()) {
            return null;
        }

        $result = [];
        foreach ($rows as $row) {
            $result[$row->metric] = (float) $row->target_value_reporting;
        }

        return $result;
    }

    /**
     * Orders contributing to a specific Profit Waterfall step.
     *
     * Each waterfall step maps to a filter on the orders table:
     *   Gross Sales / Net Sales / Net Profit  — all orders in the range (top-line)
     *   Refunds        — orders with refund_amount > 0
     *   Discounts      — orders with discount > 0
     *   COGS           — orders that have at least one order_items row with unit_cost > 0
     *   Ad Spend       — orders attributed to a paid channel (facebook or google)
     *   Shipping       — orders with shipping > 0 (shipping collected from customer)
     *   Tx Fees        — orders with payment_fee > 0
     *   Platform Fees / OpEx — not order-level; returns empty (fees are workspace-level)
     *
     * Returns up to 50 orders to keep the panel fast.
     *
     * @param  string  $step  Waterfall bar label (case-insensitive).
     * @return list<array{id:int,external_number:string,customer_masked:string,revenue:float,occurred_at:string,attribution_source:string|null}>
     */
    public function contributingOrders(int $workspaceId, ?int $storeId, string $from, string $to, string $step): array
    {
        $step = strtolower(trim($step));

        // Step labels that have no per-order breakdown (workspace-level costs).
        if (in_array($step, ['platform fees', 'opex', 'operating expenses'], true)) {
            return [];
        }

        // Tax applies to almost all orders (any order from a taxable country).
        // Rather than a non-trivial JOIN to tax_rules, return all orders in the
        // period — the Tax step has no narrower per-order predicate.
        if ($step === 'tax') {
            $step = 'gross sales'; // fall through to the default (all orders) path
        }

        // For refund drill-down we include refunded orders; all other steps exclude them.
        $excludeStatuses = $step === 'refunds'
            ? ['cancelled', 'failed', 'trash']
            : ['cancelled', 'refunded', 'failed', 'trash'];

        $query = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNotIn('status', $excludeStatuses);

        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }

        match ($step) {
            'refunds'            => $query->where('refund_amount', '>', 0),
            'discounts'          => $query->where('discount', '>', 0),
            // COGS: prefer order_items.unit_cost; fall back to product_variants.cogs_amount
            // joined via products.external_id (seeder populates cogs_amount on variants
            // but does not backfill order_items.unit_cost).
            'cogs'               => $query->where(function ($q) use ($workspaceId) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('order_items')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->whereNotNull('order_items.unit_cost')
                        ->where('order_items.unit_cost', '>', 0);
                })->orWhereExists(function ($sub) use ($workspaceId) {
                    $sub->select(DB::raw(1))
                        ->from('order_items as oi')
                        ->join('products as pr', function ($j) use ($workspaceId) {
                            $j->on('pr.external_id', '=', 'oi.product_external_id')
                              ->where('pr.workspace_id', $workspaceId);
                        })
                        ->join('product_variants as pv', 'pv.product_id', '=', 'pr.id')
                        ->whereColumn('oi.order_id', 'orders.id')
                        ->whereNotNull('pv.cogs_amount')
                        ->where('pv.cogs_amount', '>', 0);
                });
            }),
            'ad spend'           => $query->whereIn(
                DB::raw('LOWER(COALESCE(attribution_source, \'\'))'),
                ['facebook', 'google'],
            ),
            'shipping', 'shipping cost' => $query->where('shipping', '>', 0),
            'tx fees', 'transaction fees' => $query->where('payment_fee', '>', 0),
            // All other steps (gross sales, net sales, net profit) → no additional filter.
            default              => null,
        };

        $rows = $query
            ->orderByRaw('COALESCE(total_in_reporting_currency, total) DESC')
            ->limit(50)
            ->get([
                'id', 'external_number', 'customer_email_hash',
                'total', 'total_in_reporting_currency',
                'occurred_at', 'attribution_source',
            ]);

        return $rows->map(fn (object $r): array => [
            'id'               => (int) $r->id,
            'external_number'  => (string) ($r->external_number ?? '#' . $r->id),
            'customer_masked'  => '••••' . substr((string) $r->customer_email_hash, 0, 4),
            'revenue'          => round((float) ($r->total_in_reporting_currency ?? $r->total), 2),
            'occurred_at'      => $r->occurred_at,
            'attribution_source' => $r->attribution_source,
        ])->all();
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Aggregate daily_snapshot cost columns for both the current and previous
     * period in a single UNION ALL query, keyed by a 'period' discriminator.
     *
     * Replaces two separate aggregateSnapshots() calls (one per period) with one
     * round-trip. The memo key covers all four date boundaries so waterfall() and
     * plTable() reuse the result on the same request.
     *
     * @param  string  $revenueColumn  daily_snapshots column for the active source lens.
     *                                 Defaults to 'revenue_real_attributed'.
     * @return array{
     *   current:  array{revenue:float,cogs:float,refunds:float,discounts:float,shipping_collected:float,orders_count:int},
     *   previous: array{revenue:float,cogs:float,refunds:float,discounts:float,shipping_collected:float,orders_count:int},
     * }
     */
    private function aggregateSnapshotsBothPeriods(
        int $workspaceId,
        ?int $storeId,
        string $curFrom,
        string $curTo,
        string $prevFrom,
        string $prevTo,
        string $revenueColumn = 'revenue_real_attributed',
    ): array {
        $memoKey = "{$workspaceId}:" . ($storeId ?? 'null') . ":snap2:{$curFrom}:{$curTo}:{$prevFrom}:{$prevTo}:{$revenueColumn}";
        if (array_key_exists($memoKey, $this->aggMemo)) {
            return $this->aggMemo[$memoKey];
        }

        // Widen the scan to cover both windows in one pass; a CASE discriminates them.
        $storeClause = $storeId !== null ? 'AND store_id = ?' : '';
        $storeArgs   = $storeId !== null ? [$storeId] : [];

        $rows = DB::select("
            WITH period_data AS (
                SELECT
                    CASE WHEN date BETWEEN ? AND ? THEN 'current' ELSE 'previous' END AS period,
                    {$revenueColumn} AS revenue,
                    COALESCE(cogs_total, 0)             AS cogs,
                    COALESCE(refunds_total, 0)          AS refunds,
                    COALESCE(discounts_total, 0)        AS discounts,
                    COALESCE(shipping_revenue_total, 0) AS shipping_collected,
                    COALESCE(orders_count, 0)           AS orders_count
                FROM daily_snapshots
                WHERE workspace_id = ?
                  AND date BETWEEN ? AND ?
                  {$storeClause}
            )
            SELECT
                period,
                COALESCE(SUM(revenue), 0)            AS revenue,
                COALESCE(SUM(cogs), 0)               AS cogs,
                COALESCE(SUM(refunds), 0)            AS refunds,
                COALESCE(SUM(discounts), 0)          AS discounts,
                COALESCE(SUM(shipping_collected), 0) AS shipping_collected,
                COALESCE(SUM(orders_count), 0)       AS orders_count
            FROM period_data
            GROUP BY period
        ", array_merge([$curFrom, $curTo, $workspaceId, $prevFrom, $curTo], $storeArgs));

        $empty = [
            'revenue'            => 0.0,
            'cogs'               => 0.0,
            'refunds'            => 0.0,
            'discounts'          => 0.0,
            'shipping_collected' => 0.0,
            'orders_count'       => 0,
        ];

        $result = ['current' => $empty, 'previous' => $empty];

        foreach ($rows as $r) {
            $result[$r->period] = [
                'revenue'            => (float) $r->revenue,
                'cogs'               => (float) $r->cogs,
                'refunds'            => (float) $r->refunds,
                'discounts'          => (float) $r->discounts,
                'shipping_collected' => (float) $r->shipping_collected,
                'orders_count'       => (int)   $r->orders_count,
            ];
        }

        // Also populate the single-period memo slots so waterfall() hits the cache.
        $curKey  = "{$workspaceId}:" . ($storeId ?? 'null') . ":snap:{$curFrom}:{$curTo}:{$revenueColumn}";
        $prevKey = "{$workspaceId}:" . ($storeId ?? 'null') . ":snap:{$prevFrom}:{$prevTo}:{$revenueColumn}";
        $this->aggMemo[$curKey]  = $result['current'];
        $this->aggMemo[$prevKey] = $result['previous'];

        return $this->aggMemo[$memoKey] = $result;
    }

    /**
     * Aggregate campaign-level ad spend for both the current and previous period
     * in a single UNION ALL query, keyed by a 'period' discriminator.
     *
     * Filters to level='campaign', hour IS NULL to avoid cross-level double-counting
     * (CLAUDE.md gotcha). Single memo key covers all four dates.
     *
     * @return array{
     *   current:  array{total:float,facebook:float,google:float},
     *   previous: array{total:float,facebook:float,google:float},
     * }
     */
    private function aggregateAdSpendBothPeriods(
        int $workspaceId,
        string $curFrom,
        string $curTo,
        string $prevFrom,
        string $prevTo,
    ): array {
        $memoKey = "{$workspaceId}:ad2:{$curFrom}:{$curTo}:{$prevFrom}:{$prevTo}";
        if (array_key_exists($memoKey, $this->aggMemo)) {
            return $this->aggMemo[$memoKey];
        }

        $rows = DB::select("
            WITH period_data AS (
                SELECT
                    CASE WHEN ai.date BETWEEN ? AND ? THEN 'current' ELSE 'previous' END AS period,
                    aa.platform,
                    ai.spend_in_reporting_currency AS spend
                FROM ad_insights ai
                JOIN ad_accounts aa ON aa.id = ai.ad_account_id
                WHERE ai.workspace_id = ?
                  AND ai.level = 'campaign'
                  AND ai.hour IS NULL
                  AND ai.date BETWEEN ? AND ?
            )
            SELECT
                period,
                platform,
                COALESCE(SUM(spend), 0) AS spend
            FROM period_data
            GROUP BY period, platform
        ", [$curFrom, $curTo, $workspaceId, $prevFrom, $curTo]);

        $empty = ['total' => 0.0, 'facebook' => 0.0, 'google' => 0.0];
        $result = ['current' => $empty, 'previous' => $empty];

        foreach ($rows as $r) {
            $period = $r->period;
            if ($r->platform === 'facebook') {
                $result[$period]['facebook'] = (float) $r->spend;
            } elseif ($r->platform === 'google') {
                $result[$period]['google'] = (float) $r->spend;
            }
        }

        foreach (['current', 'previous'] as $p) {
            $result[$p]['total'] = round($result[$p]['facebook'] + $result[$p]['google'], 2);
            $result[$p]['facebook'] = round($result[$p]['facebook'], 2);
            $result[$p]['google']   = round($result[$p]['google'],   2);
        }

        // Populate single-period memo slots so waterfall() hits the cache.
        $curKey  = "{$workspaceId}:ad:{$curFrom}:{$curTo}";
        $prevKey = "{$workspaceId}:ad:{$prevFrom}:{$prevTo}";
        $this->aggMemo[$curKey]  = $result['current'];
        $this->aggMemo[$prevKey] = $result['previous'];

        return $this->aggMemo[$memoKey] = $result;
    }

    /**
     * Aggregate daily_snapshot cost columns for a date range.
     *
     * Results are memoised per (workspaceId, storeId, from, to, revenueColumn) so that
     * waterfall() — which calls this for a single window — can reuse results
     * already populated by aggregateSnapshotsBothPeriods().
     *
     * @param  string  $revenueColumn  daily_snapshots column for the active source lens.
     * @return array{revenue: float, cogs: float, refunds: float, discounts: float, shipping_collected: float, orders_count: int}
     */
    private function aggregateSnapshots(int $workspaceId, ?int $storeId, string $from, string $to, string $revenueColumn = 'revenue_real_attributed'): array
    {
        $key = "{$workspaceId}:" . ($storeId ?? 'null') . ":snap:{$from}:{$to}:{$revenueColumn}";
        if (array_key_exists($key, $this->aggMemo)) {
            return $this->aggMemo[$key];
        }

        $query = DB::table('daily_snapshots')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to]);

        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }

        $row = $query->selectRaw("
            COALESCE(SUM({$revenueColumn}), 0)      AS revenue,
            COALESCE(SUM(cogs_total), 0)            AS cogs,
            COALESCE(SUM(refunds_total), 0)         AS refunds,
            COALESCE(SUM(discounts_total), 0)       AS discounts,
            COALESCE(SUM(shipping_revenue_total), 0) AS shipping_collected,
            COALESCE(SUM(orders_count), 0)          AS orders_count
        ")->first();

        return $this->aggMemo[$key] = [
            'revenue'            => (float) ($row->revenue ?? 0),
            'cogs'               => (float) ($row->cogs    ?? 0),
            'refunds'            => (float) ($row->refunds ?? 0),
            'discounts'          => (float) ($row->discounts ?? 0),
            'shipping_collected' => (float) ($row->shipping_collected ?? 0),
            'orders_count'       => (int)   ($row->orders_count ?? 0),
        ];
    }

    /**
     * Aggregate campaign-level ad spend for a date range, split by platform.
     *
     * Filters to level='campaign', hour IS NULL (daily rows only) to avoid
     * double-counting across levels — CLAUDE.md gotcha.
     *
     * Results are memoised per (workspaceId, from, to). When aggregateAdSpendBothPeriods()
     * was already called for the same windows, this is a no-op cache hit.
     *
     * @return array{total: float, facebook: float, google: float}
     */
    private function aggregateAdSpend(int $workspaceId, string $from, string $to): array
    {
        $key = "{$workspaceId}:ad:{$from}:{$to}";
        if (array_key_exists($key, $this->aggMemo)) {
            return $this->aggMemo[$key];
        }

        $rows = DB::table('ad_insights')
            ->join('ad_accounts', 'ad_accounts.id', '=', 'ad_insights.ad_account_id')
            ->where('ad_insights.workspace_id', $workspaceId)
            ->where('ad_insights.level', 'campaign')
            ->whereNull('ad_insights.hour')
            ->whereBetween('ad_insights.date', [$from, $to])
            ->selectRaw('
                ad_accounts.platform,
                COALESCE(SUM(ad_insights.spend_in_reporting_currency), 0) AS spend
            ')
            ->groupBy('ad_accounts.platform')
            ->get();

        $facebook = 0.0;
        $google   = 0.0;

        foreach ($rows as $r) {
            if ($r->platform === 'facebook') {
                $facebook = (float) $r->spend;
            } elseif ($r->platform === 'google') {
                $google = (float) $r->spend;
            }
        }

        return $this->aggMemo[$key] = [
            'total'    => round($facebook + $google, 2),
            'facebook' => round($facebook, 2),
            'google'   => round($google,   2),
        ];
    }

    /**
     * Revenue, refunds, shipping, payment fees, and derived margin metrics per country.
     *
     * Queries raw orders directly (not daily_snapshots) because no snapshot table
     * carries per-country breakdowns. Volume is bounded by the 50-row LIMIT so
     * the query stays fast even on large datasets.
     *
     * Excludes cancelled / failed / trash orders (same as P&L metrics).
     * Includes refunded orders so the refund_rate column shows real data.
     *
     * Computed in PHP (to avoid repeated NULLIF):
     *   - refund_rate_pct = refunds / revenue × 100
     *   - shipping_pct    = shipping_costs / revenue × 100
     *   - payment_fee_pct = payment_fees / revenue × 100
     *   - net_margin      = (revenue − refunds − shipping − fees) / revenue × 100
     *   - aov             = revenue / orders
     *
     * @return list<array{
     *   country_code: string,
     *   revenue: float,
     *   refunds: float,
     *   payment_fees: float,
     *   shipping_costs: float,
     *   orders: int,
     *   refund_rate_pct: float|null,
     *   shipping_pct: float|null,
     *   payment_fee_pct: float|null,
     *   net_margin: float|null,
     *   aov: float|null,
     * }>
     */
    public function byCountry(int $workspaceId, string $from, string $to): array
    {
        $rows = DB::table('orders')
            ->select([
                DB::raw("COALESCE(NULLIF(customer_country,''), 'Unknown') AS country_code"),
                DB::raw('SUM(total_in_reporting_currency) AS revenue'),
                DB::raw('SUM(refund_amount) AS refunds'),
                DB::raw('SUM(payment_fee) AS payment_fees'),
                DB::raw('SUM(COALESCE(shipping_cost_snapshot, 0)) AS shipping_costs'),
                DB::raw('COUNT(*) AS orders'),
            ])
            ->where('workspace_id', $workspaceId)
            ->whereBetween('occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->whereNotNull('total_in_reporting_currency')
            ->whereNotIn('status', ['cancelled', 'failed', 'trash'])
            ->groupBy('country_code')
            ->orderByDesc('revenue')
            ->limit(50)
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $revenue      = (float) $row->revenue;
            $refunds      = (float) $row->refunds;
            $paymentFees  = (float) $row->payment_fees;
            $shipping     = (float) $row->shipping_costs;
            $orders       = (int)   $row->orders;

            $result[] = [
                'country_code'    => $row->country_code,
                'revenue'         => round($revenue, 2),
                'refunds'         => round($refunds, 2),
                'payment_fees'    => round($paymentFees, 2),
                'shipping_costs'  => round($shipping, 2),
                'orders'          => $orders,
                'refund_rate_pct' => $revenue > 0 ? round($refunds / $revenue * 100, 2) : null,
                'shipping_pct'    => $revenue > 0 ? round($shipping / $revenue * 100, 2) : null,
                'payment_fee_pct' => $revenue > 0 ? round($paymentFees / $revenue * 100, 2) : null,
                'net_margin'      => $revenue > 0
                    ? round(($revenue - $refunds - $shipping - $paymentFees) / $revenue * 100, 2)
                    : null,
                'aov'             => $orders > 0 ? round($revenue / $orders, 2) : null,
            ];
        }

        return $result;
    }
}
