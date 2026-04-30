<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates all data required by the Inventory/Index Inertia page.
 *
 * Velocity-based stock forecasting — no ML. Forecast = velocity_28d × horizon,
 * with ±15 % range as a simple confidence band. Days-of-cover is computed at
 * query time from stock_quantity / NULLIF(velocity_28d, 0); never stored.
 *
 * Reads:  product_variants, products, daily_snapshot_products
 * Writes: nothing
 * Called by: InventoryController::index()
 *
 * @see docs/pages/inventory.md
 * @see docs/planning/backend.md
 */
class InventoryDataService
{
    private const FORECAST_DAYS      = 30;
    private const FORECAST_RANGE_PCT = 0.15; // ±15 % band
    private const CHART_HISTORY_DAYS = 90;

    /**
     * KPI strip for the Inventory page header.
     *
     * - skus_at_risk      SKUs where 0 < days_of_cover < $daysThreshold (has stock but running low)
     * - out_of_stock      SKUs where stock_quantity = 0
     * - no_velocity_data  SKUs where velocity_28d IS NULL (insufficient sales history)
     * - revenue_at_risk   Sum of last-28d revenue for products that contain at least one at-risk variant
     *
     * @return array{skus_at_risk: int, out_of_stock: int, no_velocity_data: int, revenue_at_risk: float}
     */
    public function kpis(int $workspaceId, int $daysThreshold): array
    {
        $variantRows = DB::table('product_variants')
            ->select([
                'product_id',
                DB::raw('COUNT(*) AS total_skus'),
                DB::raw('COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) AS out_of_stock'),
                DB::raw('COUNT(CASE WHEN velocity_28d IS NULL THEN 1 END) AS no_velocity_data'),
                DB::raw("COUNT(CASE WHEN stock_quantity > 0
                    AND velocity_28d > 0
                    AND stock_quantity::numeric / velocity_28d < ? THEN 1 END) AS skus_at_risk"),
            ])
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('stock_quantity')
            ->groupBy('product_id')
            ->addBinding($daysThreshold, 'select')
            ->get();

        $skusAtRisk     = 0;
        $outOfStock     = 0;
        $noVelocityData = 0;
        $atRiskProductIds = [];

        foreach ($variantRows as $row) {
            $skusAtRisk     += (int) $row->skus_at_risk;
            $outOfStock     += (int) $row->out_of_stock;
            $noVelocityData += (int) $row->no_velocity_data;

            if ((int) $row->skus_at_risk > 0 || (int) $row->out_of_stock > 0) {
                $atRiskProductIds[] = (int) $row->product_id;
            }
        }

        // Revenue at risk: last 28 days of revenue for at-risk products.
        $revenueAtRisk = 0.0;
        if (!empty($atRiskProductIds)) {
            $from = Carbon::now()->subDays(27)->toDateString();
            $to   = Carbon::now()->toDateString();

            // Join products to get external_id, then match snapshot rows.
            $externalIds = DB::table('products')
                ->whereIn('id', $atRiskProductIds)
                ->where('workspace_id', $workspaceId)
                ->pluck('external_id')
                ->all();

            if (!empty($externalIds)) {
                $revenueAtRisk = (float) DB::table('daily_snapshot_products')
                    ->where('workspace_id', $workspaceId)
                    ->whereBetween('snapshot_date', [$from, $to])
                    ->whereIn('product_external_id', $externalIds)
                    ->sum('revenue');
            }
        }

        return [
            'skus_at_risk'          => $skusAtRisk,
            'out_of_stock'          => $outOfStock,
            'no_velocity_data'      => $noVelocityData,
            'revenue_at_risk'       => round($revenueAtRisk, 2),
            'stockout_lost_revenue' => $this->computeStockoutLostRevenue($workspaceId),
        ];
    }

    /**
     * Chart data for the Inventory trend + forecast area chart.
     *
     * Returns two series:
     * - past: last 90 days of actual daily units (+ same-period last year for YoY ghost line)
     * - forecast: next 30 days projection based on avg workspace velocity, with ±15 % band
     *
     * Velocity for the forecast is the average daily units across all products over the
     * most-recent 28 days within the 90-day window — same denominator as velocity_28d.
     *
     * @return array{
     *   past: list<array{date: string, units: int, units_ly: int|null}>,
     *   forecast: list<array{date: string, forecast: float, forecast_low: float, forecast_high: float}>
     * }
     */
    public function chartData(int $workspaceId): array
    {
        $historyDays = self::CHART_HISTORY_DAYS;
        $to          = Carbon::yesterday()->toDateString();
        $from        = Carbon::yesterday()->subDays($historyDays - 1)->toDateString();

        // Current period: daily sum of units across all products.
        $currentRows = DB::table('daily_snapshot_products')
            ->select([DB::raw('snapshot_date::text AS date'), DB::raw('SUM(units) AS units')])
            ->where('workspace_id', $workspaceId)
            ->whereBetween('snapshot_date', [$from, $to])
            ->groupBy('snapshot_date')
            ->orderBy('snapshot_date')
            ->get()
            ->keyBy('date');

        // Same calendar dates one year ago for YoY ghost line.
        $lyFrom = Carbon::parse($from)->subYear()->toDateString();
        $lyTo   = Carbon::parse($to)->subYear()->toDateString();

        $lyRows = DB::table('daily_snapshot_products')
            ->select([
                DB::raw("(snapshot_date + INTERVAL '1 year')::date::text AS date"),
                DB::raw('SUM(units) AS units_ly'),
            ])
            ->where('workspace_id', $workspaceId)
            ->whereBetween('snapshot_date', [$lyFrom, $lyTo])
            ->groupBy('snapshot_date')
            ->get()
            ->keyBy('date');

        // Build past array.
        $past = [];
        $date = Carbon::parse($from);
        $end  = Carbon::parse($to);

        while ($date->lte($end)) {
            $dateStr = $date->toDateString();
            $past[]  = [
                'date'     => $dateStr,
                'units'    => isset($currentRows[$dateStr]) ? (int) $currentRows[$dateStr]->units : 0,
                'units_ly' => isset($lyRows[$dateStr]) ? (int) $lyRows[$dateStr]->units_ly : null,
            ];
            $date->addDay();
        }

        // Velocity: avg units/day over most recent 28 days in the window.
        $velocityFrom = Carbon::yesterday()->subDays(27)->toDateString();
        $recentUnits  = 0;
        foreach ($past as $row) {
            if ($row['date'] >= $velocityFrom) {
                $recentUnits += $row['units'];
            }
        }
        $velocity = $recentUnits / 28.0;

        // Forecast: next 30 days starting from today.
        $forecast    = [];
        $forecastStart = Carbon::today();

        for ($i = 0; $i < self::FORECAST_DAYS; $i++) {
            $forecastDate = $forecastStart->copy()->addDays($i)->toDateString();
            $forecast[]   = [
                'date'           => $forecastDate,
                'forecast'       => round($velocity, 2),
                'forecast_low'   => round($velocity * (1 - self::FORECAST_RANGE_PCT), 2),
                'forecast_high'  => round($velocity * (1 + self::FORECAST_RANGE_PCT), 2),
            ];
        }

        return [
            'past'     => $past,
            'forecast' => $forecast,
        ];
    }

    /**
     * Main product list for the Inventory table.
     *
     * Primary source is daily_snapshot_products so velocity and forecasts work
     * even when the product catalog (products / product_variants) has not been
     * synced yet. The catalog is used only for name overrides and images.
     *
     * Stock data comes from the stock_quantity column in daily_snapshot_products
     * (populated by SyncShopifyInventorySnapshotJob); it is null until that job
     * runs, in which case stock columns show "—" but velocity/forecast still work.
     *
     * Forecast horizons: 7 / 14 / 30 days — matches weekly, bi-weekly, monthly
     * reorder cycles typical for SMB e-commerce.
     *
     * Returned in revenue DESC order; client sorts from there.
     *
     * @return list<array<string, mixed>>
     */
    public function products(
        int $workspaceId,
        string $from,
        string $to,
        int $leadTimeDays = 14,
    ): array {
        $vel28From  = Carbon::now()->subDays(27)->toDateString();
        $lyFrom     = Carbon::parse($from)->subYear()->toDateString();
        $lyTo       = Carbon::parse($to)->subYear()->toDateString();
        $adFrom     = Carbon::now()->subDays(29)->toDateString();
        $adTo       = Carbon::now()->toDateString();

        // One pass over the snapshot table: velocity (last 28d), period aggregates,
        // and YoY aggregates all computed in a single GROUP BY query.
        $rows = DB::select('
            SELECT
                product_external_id,
                MAX(product_name) AS product_name,
                ROUND(
                    SUM(CASE WHEN snapshot_date >= :vel_from THEN units ELSE 0 END) / 28.0,
                    2
                ) AS velocity_28d,
                SUM(CASE WHEN snapshot_date BETWEEN :p_from AND :p_to
                    THEN units   ELSE 0 END) AS units_current,
                SUM(CASE WHEN snapshot_date BETWEEN :p_from2 AND :p_to2
                    THEN revenue ELSE 0 END) AS revenue_current,
                SUM(CASE WHEN snapshot_date BETWEEN :ly_from AND :ly_to
                    THEN units   ELSE 0 END) AS units_ly
            FROM daily_snapshot_products
            WHERE workspace_id = :workspace_id
            GROUP BY product_external_id
            HAVING SUM(units) > 0
        ', [
            'vel_from'     => $vel28From,
            'p_from'       => $from,
            'p_to'         => $to,
            'p_from2'      => $from,
            'p_to2'        => $to,
            'ly_from'      => $lyFrom,
            'ly_to'        => $lyTo,
            'workspace_id' => $workspaceId,
        ]);

        // Most-recent stock per product (DISTINCT ON = one index scan).
        $stockRows = DB::select('
            SELECT DISTINCT ON (product_external_id)
                product_external_id, stock_quantity
            FROM daily_snapshot_products
            WHERE workspace_id = :workspace_id
              AND stock_quantity IS NOT NULL
            ORDER BY product_external_id, snapshot_date DESC
        ', ['workspace_id' => $workspaceId]);

        $stockMap = [];
        foreach ($stockRows as $sr) {
            $stockMap[$sr->product_external_id] = (int) $sr->stock_quantity;
        }

        // Optional catalog enrichment: name override, image, and primary SKU.
        // SKU is pulled from product_variants (first variant by id) so both Shopify
        // and WooCommerce surface a human-readable SKU code alongside the product name.
        $catalog = DB::table('products')
            ->where('products.workspace_id', $workspaceId)
            ->leftJoinSub(
                DB::table('product_variants')
                    ->select(['product_id', DB::raw('MIN(sku) AS sku')])
                    ->where('workspace_id', $workspaceId)
                    ->whereNotNull('sku')
                    ->where('sku', '!=', '')
                    ->groupBy('product_id'),
                'pv',
                'pv.product_id',
                '=',
                'products.id',
            )
            ->get(['products.external_id', 'products.name', 'products.image_url', 'pv.sku'])
            ->keyBy('external_id');

        // ── Ad-spend × stock cross-cut (Lebesgue pattern) ────────────────────
        // Gate: only run when ad_insights rows exist for this workspace in the window.
        // Join path: ad_insights (campaign level, 30d) → sum spend per workspace.
        // Per-product: orders attributed to paid channels containing that product_external_id.
        // We prorate workspace total ad spend by the product's share of paid-attributed orders.
        // This avoids a heavy per-product ad_insights join while remaining directionally accurate.
        $adSpendMap = $this->computeAdSpendByProduct($workspaceId, $adFrom, $adTo);

        $products = [];
        foreach ($rows as $row) {
            $extId    = $row->product_external_id;
            $cat      = $catalog[$extId] ?? null;
            $velocity = $row->velocity_28d !== null ? (float) $row->velocity_28d : null;
            $stockQty = $stockMap[$extId] ?? null;

            $daysOfCover        = null;
            $forecastedStockout = null;
            if ($stockQty !== null) {
                if ($stockQty === 0) {
                    $daysOfCover = 0.0;
                } elseif ($velocity !== null && $velocity > 0) {
                    $daysOfCover        = round($stockQty / $velocity, 1);
                    $forecastedStockout = Carbon::today()
                        ->addDays((int) round($daysOfCover))
                        ->toDateString();
                }
            }

            $unitsLy      = (int) $row->units_ly;
            $unitsCurrent = (int) $row->units_current;

            $next30d = $velocity !== null ? round($velocity * 30, 1) : null;

            // Reorder qty: units needed to cover next-30d demand PLUS lead-time buffer, minus current stock.
            // Formula: max(0, next_30d + velocity * lead_time_days - total_stock)
            $reorderQty = null;
            if ($next30d !== null && $stockQty !== null && $velocity !== null) {
                $leadBuffer = $velocity * $leadTimeDays;
                $shortfall  = (int) ceil($next30d + $leadBuffer - $stockQty);
                $reorderQty = $shortfall > 0 ? $shortfall : null;
            }

            // Sell-through rate: units sold / (units sold + current stock). Never stored — computed on the fly.
            $sellThroughRate = null;
            if ($stockQty !== null && ($unitsCurrent + $stockQty) > 0) {
                $sellThroughRate = round($unitsCurrent / ($unitsCurrent + $stockQty), 4);
            }

            // Ad-spend × stock risk: SKU is getting paid traffic while depleting (Lebesgue pattern).
            $adSpend30d = $adSpendMap[$extId] ?? null;
            $stockRisk  = $adSpend30d !== null
                && $adSpend30d > 0
                && $daysOfCover !== null
                && $daysOfCover < $leadTimeDays;

            $products[] = [
                'external_id'         => $extId,
                'name'                => $cat?->name ?? $row->product_name ?? "Product #{$extId}",
                'sku'                 => $cat?->sku ?? null,
                'image_url'           => $cat?->image_url ?? null,
                'velocity_28d'        => $velocity,
                'next_7d'             => $velocity !== null ? round($velocity * 7, 1)  : null,
                'next_14d'            => $velocity !== null ? round($velocity * 14, 1) : null,
                'next_30d'            => $next30d,
                'total_stock'         => $stockQty,
                'days_of_cover'       => $daysOfCover,
                'forecasted_stockout' => $forecastedStockout,
                'reorder_qty'         => $reorderQty,
                'sell_through_rate'   => $sellThroughRate,
                'ad_spend_30d'        => $adSpend30d !== null ? round($adSpend30d, 2) : null,
                'stock_risk'          => $stockRisk,
                'revenue_current'     => round((float) $row->revenue_current, 2),
                'units_current'       => $unitsCurrent,
                'units_ly'            => $unitsLy > 0 ? $unitsLy : null,
            ];
        }

        usort($products, static fn(array $a, array $b): int
            => $b['revenue_current'] <=> $a['revenue_current']);

        return $products;
    }

    /**
     * Ad-spend × stock cross-cut (Lebesgue pattern).
     *
     * For each product_external_id, returns the prorated share of total workspace
     * ad spend (facebook + google, campaign-level, last 30 days) attributed to orders
     * that contained that product.
     *
     * Join path:
     *   ad_insights (campaign, 30d) → total workspace spend
     *   orders (paid attribution, 30d) → order_items → product_external_id
     *   proration: product's paid-order count / total paid-order count × total spend
     *
     * Returns [] (empty) if no ad_insights rows exist for the workspace — zero cost
     * on workspaces without ad integrations.
     *
     * @return array<string, float>  product_external_id → prorated ad spend
     */
    private function computeAdSpendByProduct(
        int $workspaceId,
        string $from,
        string $to,
    ): array {
        // Guard: skip entirely if no ad data in window (avoids join cost on stores-only workspaces).
        $hasAdData = DB::table('ad_insights')
            ->where('workspace_id', $workspaceId)
            ->where('level', 'campaign')
            ->whereNull('hour')
            ->whereBetween('date', [$from, $to])
            ->exists();

        if (!$hasAdData) {
            return [];
        }

        // Total workspace ad spend (campaign level only — never sum across levels).
        $totalSpend = (float) DB::table('ad_insights')
            ->where('workspace_id', $workspaceId)
            ->where('level', 'campaign')
            ->whereNull('hour')
            ->whereBetween('date', [$from, $to])
            ->sum('spend_in_reporting_currency');

        if ($totalSpend <= 0) {
            return [];
        }

        // Paid-attributed orders containing each product in the window.
        // attribution_source IN ('facebook','google') is the paid channel signal.
        // Joins order_items → orders; order_items has an index on product_external_id.
        $productOrderCounts = DB::select("
            SELECT
                oi.product_external_id,
                COUNT(DISTINCT o.id) AS paid_order_count
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            WHERE o.workspace_id = ?
              AND o.attribution_source IN ('facebook', 'google')
              AND o.occurred_at BETWEEN ? AND ?
              AND o.status IN ('completed', 'processing')
            GROUP BY oi.product_external_id
        ", [$workspaceId, $from . ' 00:00:00', $to . ' 23:59:59']);

        if (empty($productOrderCounts)) {
            return [];
        }

        $totalPaidOrders = array_sum(array_column(
            array_map(fn($r) => ['c' => (int) $r->paid_order_count], $productOrderCounts),
            'c',
        ));

        if ($totalPaidOrders === 0) {
            return [];
        }

        $result = [];
        foreach ($productOrderCounts as $row) {
            $share = (int) $row->paid_order_count / $totalPaidOrders;
            $result[$row->product_external_id] = $totalSpend * $share;
        }

        return $result;
    }

    /**
     * Estimate revenue already lost to active stockouts.
     *
     * For each product whose most recent stock snapshot is 0, computes:
     *   days_out × velocity_28d × avg_unit_revenue
     *
     * where days_out = today − last snapshot date where stock_quantity > 0,
     * and avg_unit_revenue = last-28d revenue ÷ last-28d units.
     *
     * Returns 0.0 when no products are currently out of stock.
     */
    private function computeStockoutLostRevenue(int $workspaceId): float
    {
        // Products currently OOS and how many days since they last had stock.
        $oosRows = DB::select("
            WITH latest_stock AS (
                SELECT DISTINCT ON (product_external_id)
                    product_external_id, stock_quantity
                FROM daily_snapshot_products
                WHERE workspace_id = ?
                  AND stock_quantity IS NOT NULL
                ORDER BY product_external_id, snapshot_date DESC
            ),
            oos AS (
                SELECT product_external_id FROM latest_stock WHERE stock_quantity = 0
            )
            SELECT DISTINCT ON (dsp.product_external_id)
                dsp.product_external_id,
                (CURRENT_DATE - dsp.snapshot_date) AS days_out
            FROM oos
            JOIN daily_snapshot_products dsp
                ON dsp.product_external_id = oos.product_external_id
               AND dsp.workspace_id = ?
               AND dsp.stock_quantity > 0
            ORDER BY dsp.product_external_id, dsp.snapshot_date DESC
        ", [$workspaceId, $workspaceId]);

        if (empty($oosRows)) {
            return 0.0;
        }

        $daysOutMap = [];
        foreach ($oosRows as $row) {
            $daysOutMap[$row->product_external_id] = (int) $row->days_out;
        }

        // Velocity + avg unit price from last 28 days for each OOS product.
        $extIds = array_keys($daysOutMap);
        $placeholders = implode(',', array_fill(0, count($extIds), '?'));

        $velRows = DB::select("
            SELECT
                product_external_id,
                SUM(revenue)                AS rev_28d,
                SUM(units)                  AS units_28d,
                ROUND(SUM(units) / 28.0, 4) AS velocity_28d
            FROM daily_snapshot_products
            WHERE workspace_id = ?
              AND snapshot_date >= CURRENT_DATE - INTERVAL '27 days'
              AND product_external_id IN ({$placeholders})
            GROUP BY product_external_id
            HAVING SUM(units) > 0
        ", array_merge([$workspaceId], $extIds));

        $lost = 0.0;
        foreach ($velRows as $v) {
            $daysOut  = $daysOutMap[$v->product_external_id] ?? 0;
            $velocity = (float) $v->velocity_28d;
            $units28d = (float) $v->units_28d;
            $rev28d   = (float) $v->rev_28d;

            if ($velocity <= 0 || $units28d <= 0 || $daysOut <= 0) {
                continue;
            }

            $avgUnitPrice = $rev28d / $units28d;
            $lost += $daysOut * $velocity * $avgUnitPrice;
        }

        return round($lost, 2);
    }
}
