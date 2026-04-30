<?php

declare(strict_types=1);

namespace App\Services\Products;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates all data required by the Products/Index Inertia page.
 *
 * Data sources (per docs/pages/products.md):
 *   - daily_snapshot_products — per-product revenue, units over the date range.
 *   - order_items JOIN orders    — real distinct order counts per product (for AOV).
 *   - products — name, thumbnail, SKU, external_id.
 *   - product_variants — SKU-level COGS (cogs_amount, cogs_currency), stock_quantity, velocity_28d.
 *
 * Lifecycle labels (Rockstar/Hot/Cold/At-Risk) are derived from recency + velocity:
 *   Rockstar — top-10% revenue AND margin_pct non-null AND margin_pct >= 40.
 *   Hot      — velocity current period >= comparison period × 1.25 (≥ 25% up).
 *   Cold     — velocity current period <= comparison period × 0.75 (≥ 25% down).
 *   At-Risk  — velocity down >= 15% AND margin_pct < 20 (bottom-quartile proxy).
 * Thresholds per docs/pages/products.md §"Product Labels".
 *
 * Stock alert filter: optional ?stock_alert=7|30 query param. Uses the variant with
 * the lowest days-of-cover per product: ROUND(stock_quantity / NULLIF(velocity_28d, 0), 1).
 * Out-of-stock variants (stock_quantity = 0) always count as 0 days-of-cover.
 *
 * Reads:  daily_snapshot_products, products, product_variants, ga4_product_page_views, ga4_order_attribution,
 *         order_items, orders (for product_journey and market_basket queries)
 * Writes: nothing
 * Called by: ProductsController::index()
 *
 * @see docs/pages/products.md
 * @see docs/planning/backend.md
 */
class ProductsDataService
{
    /**
     * Build the full products page payload.
     *
     * @return array{
     *   products: list<array<string, mixed>>,
     *   metrics: array<string, mixed>,
     *   pareto_data: list<array<string, mixed>>,
     *   filters: array<string, mixed>,
     *   cogs_configured_count: int,
     *   total_skus: int,
     *   product_journey: list<array<string, mixed>>,
     *   market_basket: list<array<string, mixed>>,
     * }
     */
    public function forIndex(int $workspaceId, array $params): array
    {
        $from        = $params['from'];
        $to          = $params['to'];
        $stockAlert  = $params['stock_alert'] ?? null; // int 7|30 or null

        // Comparison period (same length, immediately before) for velocity labels.
        $days          = max(1, (int) \Illuminate\Support\Carbon::parse($from)->diffInDays($to) + 1);
        $compareFrom   = \Illuminate\Support\Carbon::parse($from)->subDays($days)->toDateString();
        $compareTo     = \Illuminate\Support\Carbon::parse($from)->subDay()->toDateString();

        $snapRows    = $this->loadSnapshotAggregates($workspaceId, $from, $to);
        $compareRows = $this->loadSnapshotAggregates($workspaceId, $compareFrom, $compareTo);
        $productMeta = $this->loadProductMeta($workspaceId);
        $cogsMap     = $this->loadCogsMap($workspaceId);
        $sparklines  = $this->loadSparklines($workspaceId, $from, $to);
        $orderCounts = $this->loadOrderCounts($workspaceId, $from, $to);
        $repeatRates = $this->loadRepeatRates($workspaceId, $from, $to);
        // days_of_cover keyed by product.id — minimum cover variant per product.
        $coverMap    = $this->loadDaysOfCover($workspaceId);
        // GA4 ecommerce item views keyed by product_external_id — null when GA4 not connected.
        $viewsMap    = $this->loadProductViews($workspaceId, $from, $to);
        // Top 3 GA4 campaign names per product (via order attribution join).
        $campaignsMap = $this->loadTopCampaignsPerProduct($workspaceId, $from, $to);

        $totalRevenue = array_sum(array_column($snapRows, 'revenue'));

        // Sort by revenue DESC for ranking
        usort($snapRows, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        $topDecileThreshold = $this->percentileThreshold(array_column($snapRows, 'revenue'), 0.9);

        $products = [];
        foreach ($snapRows as $i => $snap) {
            $extId   = $snap['product_external_id'];
            $meta    = $productMeta[$extId] ?? null;
            $cogs    = isset($meta['id']) ? ($cogsMap[$meta['id']] ?? null) : null;
            $revenue     = (float) $snap['revenue'];
            $units       = (int) $snap['units'];
            $orderCount  = $orderCounts[$extId] ?? 0;

            $aov = $orderCount > 0 ? round($revenue / $orderCount, 2) : null;

            // Margin: (revenue - cogs×units) / revenue; null when cogs unknown.
            $marginPct = null;
            if ($cogs !== null && $revenue > 0) {
                $marginPct = round((($revenue - $cogs * $units) / $revenue) * 100, 2);
            }

            // Velocity: units per day over the period.
            $velocity = $days > 0 ? round($units / $days, 4) : null;

            // Comparison velocity for lifecycle labels.
            $compareUnits    = (int) ($compareRows[$extId]['units'] ?? 0);
            $compareVelocity = ($days > 0 && $compareUnits > 0) ? ($compareUnits / $days) : null;

            $label = $this->deriveLabel(
                rank: $i + 1,
                totalProducts: count($snapRows),
                revenue: $revenue,
                topDecileThreshold: $topDecileThreshold,
                marginPct: $marginPct,
                velocity: $velocity,
                compareVelocity: $compareVelocity,
            );

            $sparkline   = $sparklines[$extId] ?? [];
            $productId   = $meta['id'] ?? 0;
            $daysOfCover = $productId > 0 ? ($coverMap[$productId] ?? null) : null;

            $viewData = $viewsMap[$extId] ?? null;

            $products[] = [
                'id'                 => $productId,
                'external_id'        => $extId,
                'name'               => $snap['product_name'],
                'thumbnail_url'      => $meta['image_url'] ?? null,
                'sku'                => $meta['sku'] ?? null,
                'units'              => $units,
                'revenue'            => $revenue,
                'orders'             => $orderCount,
                'aov'                => $aov,
                'cogs'               => $cogs,
                'margin_pct'         => $marginPct,
                'velocity'           => $velocity,
                'velocity_sparkline' => $sparkline,
                'label'              => $label,
                'sources'            => ['store'],
                'days_of_cover'      => $daysOfCover,
                'views'              => $viewData ? $viewData['views'] : null,
                'view_cvr'           => $viewData ? $viewData['view_cvr'] : null,
                'add_to_cart_rate'   => $viewData ? $viewData['add_to_cart_rate'] : null,
                'top_campaigns'      => $campaignsMap[$extId] ?? [],
                'repeat_rate'        => $repeatRates[$extId] ?? null,
            ];
        }

        // Apply stock alert filter after building product rows (filter-after-aggregate pattern).
        // Uses the pre-loaded minimum days-of-cover per product.
        if ($stockAlert !== null) {
            $products = array_values(array_filter(
                $products,
                fn($p) => $p['days_of_cover'] !== null && $p['days_of_cover'] <= $stockAlert,
            ));
        }

        $metrics    = $this->buildMetrics($products);
        $paretoData = $this->buildParetoData($products, $totalRevenue);

        // COGS coverage counters — count distinct products (not variants) with any COGS configured.
        $cogsConfiguredCount = count(array_filter($cogsMap));
        $totalSkus = count($productMeta);

        $productJourney = $this->loadProductJourney($workspaceId, $from, $to);
        $marketBasket   = $this->loadMarketBasket($workspaceId, $from, $to);

        return [
            'products'             => $products,
            'metrics'              => $metrics,
            'pareto_data'          => $paretoData,
            'filters'              => [
                'from'        => $from,
                'to'          => $to,
                'country'     => $params['country'] ?? null,
                'channel'     => $params['channel'] ?? null,
                'stock_alert' => $stockAlert,
                'sort'        => $params['sort'] ?? 'revenue',
            ],
            'cogs_configured_count' => $cogsConfiguredCount,
            'total_skus'            => $totalSkus,
            'product_journey'       => $productJourney,
            'market_basket'         => $marketBasket,
        ];
    }

    /**
     * Aggregate revenue + units from daily_snapshot_products for a date range.
     * Returns array keyed by product_external_id.
     *
     * @return array<string, array<string, mixed>>
     */
    private function loadSnapshotAggregates(int $workspaceId, string $from, string $to): array
    {
        $rows = DB::table('daily_snapshot_products')
            ->select([
                'product_external_id',
                'product_name',
                DB::raw('SUM(revenue) AS revenue'),
                DB::raw('SUM(units)   AS units'),
            ])
            ->where('workspace_id', $workspaceId)
            ->whereBetween('snapshot_date', [$from, $to])
            ->groupBy('product_external_id', 'product_name')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->product_external_id] = [
                'product_external_id' => $row->product_external_id,
                'product_name'        => $row->product_name,
                'revenue'             => (float) $row->revenue,
                'units'               => (int) $row->units,
            ];
        }

        return $result;
    }

    /**
     * Count distinct orders per product_external_id for a date range.
     * Joins order_items to orders so we filter by workspace and occurred_at date.
     *
     * @return array<string, int>  product_external_id → order_count
     */
    private function loadOrderCounts(int $workspaceId, string $from, string $to): array
    {
        $rows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->select([
                'oi.product_external_id',
                DB::raw('COUNT(DISTINCT oi.order_id) AS order_count'),
            ])
            ->where('o.workspace_id', $workspaceId)
            ->whereBetween('o.occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('oi.product_external_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->product_external_id] = (int) $row->order_count;
        }

        return $map;
    }

    /**
     * Load product metadata (id, image_url, sku) keyed by external_id.
     *
     * @return array<string, array<string, mixed>>
     */
    private function loadProductMeta(int $workspaceId): array
    {
        $rows = DB::table('products')
            ->select(['id', 'external_id', 'sku', 'image_url'])
            ->where('workspace_id', $workspaceId)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->external_id] = [
                'id'        => (int) $row->id,
                'sku'       => $row->sku,
                'image_url' => $row->image_url,
            ];
        }

        return $map;
    }

    /**
     * Load average COGS per product (across all variants), keyed by product.id.
     *
     * @return array<int, float|null>
     */
    private function loadCogsMap(int $workspaceId): array
    {
        $rows = DB::table('product_variants')
            ->select([
                'product_id',
                DB::raw('AVG(cogs_amount) AS avg_cogs'),
            ])
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('cogs_amount')
            ->groupBy('product_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->product_id] = round((float) $row->avg_cogs, 4);
        }

        return $map;
    }

    /**
     * Load daily unit counts for sparklines, keyed by product_external_id.
     * Returns last 14 data-points maximum to keep sparklines lightweight.
     *
     * The window is capped at 14 days back from `$to` regardless of the
     * requested date range, so that a 90-day filter doesn't stream thousands
     * of rows just to trim them down to 14 in PHP.
     *
     * @return array<string, list<int>>
     */
    private function loadSparklines(int $workspaceId, string $from, string $to): array
    {
        // Clamp the sparkline window to at most 14 days — the PHP trim below
        // would discard everything older anyway.
        $sparklineFrom = \Illuminate\Support\Carbon::parse($to)->subDays(13)->toDateString();
        $effectiveFrom = $sparklineFrom > $from ? $sparklineFrom : $from;

        $rows = DB::table('daily_snapshot_products')
            ->select(['product_external_id', 'snapshot_date', 'units'])
            ->where('workspace_id', $workspaceId)
            ->whereBetween('snapshot_date', [$effectiveFrom, $to])
            ->orderBy('snapshot_date')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->product_external_id][] = (int) $row->units;
        }

        // Trim to last 14 points
        foreach ($map as $extId => $values) {
            $map[$extId] = array_values(array_slice($values, -14));
        }

        return $map;
    }

    /**
     * Load the minimum days-of-cover per product, keyed by product.id.
     *
     * Uses the variant with the lowest cover (worst-case) to surface the most
     * urgent stockout risk. Out-of-stock variants (stock_quantity = 0) are
     * treated as 0 days-of-cover and will always trigger any active alert.
     *
     * Formula: ROUND(stock_quantity / NULLIF(velocity_28d, 0), 1)
     * The partial index idx_product_variants_stockout accelerates this scan.
     *
     * @return array<int, float|null>  product.id → days_of_cover (null = velocity unknown)
     */
    private function loadDaysOfCover(int $workspaceId): array
    {
        // Subquery picks the best (minimum) days-of-cover variant per product.
        // CASE: stock_quantity = 0 → 0.0 days (out of stock, no velocity needed).
        //       velocity_28d > 0   → computed cover.
        //       velocity_28d is null or 0 → NULL (velocity unknown, cannot compute).
        $rows = DB::table('product_variants')
            ->select([
                'product_id',
                DB::raw("
                    ROUND(
                        MIN(
                            CASE
                                WHEN stock_quantity = 0 THEN 0.0
                                ELSE stock_quantity::numeric / NULLIF(velocity_28d, 0)
                            END
                        ),
                        1
                    ) AS days_of_cover
                "),
            ])
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('stock_quantity')
            ->groupBy('product_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->product_id] = $row->days_of_cover !== null
                ? (float) $row->days_of_cover
                : null;
        }

        return $map;
    }

    /**
     * Load GA4 enhanced ecommerce item view counts keyed by product_external_id.
     *
     * Joins ga4_product_page_views to products via item_id = products.external_id.
     * Returns null for the whole map key when no GA4 product view data exists for
     * this workspace+range (GA4 not connected or enhanced ecommerce not set up).
     *
     * view_cvr = items_purchased / NULLIF(item_views, 0) — share of viewers who bought.
     * add_to_cart_rate = items_added_to_cart / NULLIF(item_views, 0).
     *
     * @return array<string, array{views: int, view_cvr: float|null, add_to_cart_rate: float|null}>
     */
    private function loadProductViews(int $workspaceId, string $from, string $to): array
    {
        $rows = DB::table('ga4_product_page_views as ppv')
            ->join('ga4_properties as gp', 'gp.id', '=', 'ppv.ga4_property_id')
            ->join('products as p', function ($join) use ($workspaceId): void {
                $join->on('p.external_id', '=', 'ppv.item_id')
                     ->where('p.workspace_id', $workspaceId);
            })
            ->select([
                'p.external_id',
                DB::raw('SUM(ppv.item_views) AS item_views'),
                DB::raw('SUM(ppv.items_added_to_cart) AS items_added_to_cart'),
                DB::raw('SUM(ppv.items_purchased) AS items_purchased'),
            ])
            ->where('gp.workspace_id', $workspaceId)
            ->whereBetween('ppv.date', [$from, $to])
            ->groupBy('p.external_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $views = (int) $row->item_views;
            $map[$row->external_id] = [
                'views'           => $views,
                'view_cvr'        => $views > 0 ? round((int) $row->items_purchased / $views, 4) : null,
                'add_to_cart_rate'=> $views > 0 ? round((int) $row->items_added_to_cart / $views, 4) : null,
            ];
        }

        return $map;
    }

    /**
     * Load top 3 GA4 campaign names per product, via ga4_order_attribution joined
     * to orders (transaction_id match) then to order_items (product join).
     *
     * Returns an empty array per product when no GA4 order attribution exists.
     *
     * @return array<string, list<string>>  product_external_id → up to 3 campaign names
     */
    private function loadTopCampaignsPerProduct(int $workspaceId, string $from, string $to): array
    {
        $rows = DB::table('ga4_order_attribution as goa')
            ->join('orders as o', function ($join) use ($workspaceId): void {
                $join->on('o.external_id', '=', 'goa.transaction_id')
                     ->where('o.workspace_id', $workspaceId);
            })
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->select([
                'oi.product_external_id',
                'goa.session_campaign',
                DB::raw('COUNT(*) AS conversions'),
            ])
            ->where('goa.workspace_id', $workspaceId)
            ->whereBetween('goa.date', [$from, $to])
            ->whereNotNull('goa.session_campaign')
            ->groupBy('oi.product_external_id', 'goa.session_campaign')
            ->orderBy('oi.product_external_id')
            ->orderByDesc('conversions')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $extId = $row->product_external_id;
            if (!isset($map[$extId])) {
                $map[$extId] = [];
            }
            // Keep top 3 per product.
            if (count($map[$extId]) < 3) {
                $map[$extId][] = $row->session_campaign;
            }
        }

        return $map;
    }

    /**
     * Gateway product analysis — which products customers buy as their 1st, 2nd, or 3rd purchase.
     *
     * Uses a CTE to rank each customer's orders by occurred_at, then counts how many distinct
     * customers bought each product at positions 1, 2, and 3.
     *
     * order_items.product_id is nullable (backfill pending), so we use product_external_id
     * as the stable identifier. product_name is denormalised on order_items at ingest.
     *
     * LIMIT 60 in the CTE caps the raw scan; the top-20 slice by as_first is applied in PHP.
     *
     * @return list<array{product_external_id: string, product_name: string, as_first: int, as_second: int, as_third: int}>
     */
    private function loadProductJourney(int $workspaceId, string $from, string $to): array
    {
        $fromTs = $from . ' 00:00:00';
        $toTs   = $to   . ' 23:59:59';

        $sql = <<<'SQL'
WITH ranked AS (
    SELECT
        oi.product_external_id,
        oi.product_name,
        o.customer_id,
        ROW_NUMBER() OVER (PARTITION BY o.customer_id ORDER BY o.occurred_at ASC) AS purchase_num
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.workspace_id = ?
      AND o.status IN ('processing', 'completed')
      AND o.occurred_at BETWEEN ? AND ?
      AND o.customer_id IS NOT NULL
)
SELECT
    product_external_id,
    product_name,
    purchase_num,
    COUNT(DISTINCT customer_id) AS customers
FROM ranked
WHERE purchase_num <= 3
GROUP BY product_external_id, product_name, purchase_num
ORDER BY customers DESC
SQL;

        $rows = DB::select($sql, [$workspaceId, $fromTs, $toTs]);

        // Pivot: product_external_id → {as_first, as_second, as_third}
        $pivot = [];
        foreach ($rows as $row) {
            $key = $row->product_external_id;
            if (!isset($pivot[$key])) {
                $pivot[$key] = [
                    'product_external_id' => $key,
                    'product_name'        => $row->product_name,
                    'as_first'            => 0,
                    'as_second'           => 0,
                    'as_third'            => 0,
                ];
            }
            match ((int) $row->purchase_num) {
                1 => $pivot[$key]['as_first']  = (int) $row->customers,
                2 => $pivot[$key]['as_second'] = (int) $row->customers,
                3 => $pivot[$key]['as_third']  = (int) $row->customers,
                default => null,
            };
        }

        // Sort by as_first DESC, return top 20.
        $result = array_values($pivot);
        usort($result, fn($a, $b) => $b['as_first'] <=> $a['as_first']);

        return array_values(array_slice($result, 0, 20));
    }

    /**
     * Market basket analysis — product pairs that appear together in the same order.
     *
     * Self-joins order_items on the same order_id, with the b.product_external_id > a.product_external_id
     * guard to produce unordered pairs without duplicates.
     *
     * lift_pct = co_purchase_count / total_orders_in_range * 100.
     * HAVING >= 3 filters noise from very sparse pairings.
     *
     * @return list<array{product_a: string, product_b: string, co_purchase_count: int, lift_pct: float}>
     */
    private function loadMarketBasket(int $workspaceId, string $from, string $to): array
    {
        $fromTs = $from . ' 00:00:00';
        $toTs   = $to   . ' 23:59:59';

        $sql = <<<'SQL'
SELECT
    a.product_external_id AS product_a_id,
    a.product_name        AS product_a,
    b.product_external_id AS product_b_id,
    b.product_name        AS product_b,
    COUNT(DISTINCT a.order_id) AS co_purchase_count,
    ROUND(
        COUNT(DISTINCT a.order_id)::numeric / NULLIF(
            (SELECT COUNT(DISTINCT o2.id)
             FROM orders o2
             WHERE o2.workspace_id = ?
               AND o2.status IN ('processing', 'completed')
               AND o2.occurred_at BETWEEN ? AND ?
            ), 0
        ) * 100,
        1
    ) AS lift_pct
FROM order_items a
JOIN order_items b ON b.order_id = a.order_id
                  AND b.product_external_id > a.product_external_id
JOIN orders o ON o.id = a.order_id
WHERE o.workspace_id = ?
  AND o.status IN ('processing', 'completed')
  AND o.occurred_at BETWEEN ? AND ?
GROUP BY a.product_external_id, a.product_name, b.product_external_id, b.product_name
HAVING COUNT(DISTINCT a.order_id) >= 3
ORDER BY co_purchase_count DESC
LIMIT 30
SQL;

        $rows = DB::select($sql, [
            $workspaceId, $fromTs, $toTs,  // subquery params
            $workspaceId, $fromTs, $toTs,  // outer WHERE params
        ]);

        return array_map(fn($row) => [
            'product_a_id'      => $row->product_a_id,
            'product_a'         => $row->product_a,
            'product_b_id'      => $row->product_b_id,
            'product_b'         => $row->product_b,
            'co_purchase_count' => (int) $row->co_purchase_count,
            'lift_pct'          => (float) $row->lift_pct,
        ], $rows);
    }

    /**
     * Compute per-product repeat rate within the selected date range.
     *
     * repeat_rate = customers who placed 2+ orders containing this product
     *               ÷ total distinct customers who ordered this product
     *
     * Products with fewer than 5 distinct customers are returned as null —
     * sample too small to be meaningful.
     *
     * @return array<string, float|null>  product_external_id → rate (0–1) or null
     */
    private function loadRepeatRates(int $workspaceId, string $from, string $to): array
    {
        $fromTs = $from . ' 00:00:00';
        $toTs   = $to   . ' 23:59:59';

        $sql = <<<'SQL'
WITH cpo AS (
    SELECT
        oi.product_external_id,
        o.customer_id,
        COUNT(DISTINCT o.id) AS orders_in_period
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.workspace_id = ?
      AND o.status IN ('processing', 'completed')
      AND o.customer_id IS NOT NULL
      AND o.occurred_at BETWEEN ? AND ?
    GROUP BY oi.product_external_id, o.customer_id
)
SELECT
    product_external_id,
    COUNT(DISTINCT customer_id) AS total_customers,
    COUNT(DISTINCT CASE WHEN orders_in_period > 1 THEN customer_id END) AS repeat_customers
FROM cpo
GROUP BY product_external_id
SQL;

        $rows = DB::select($sql, [$workspaceId, $fromTs, $toTs]);

        $map = [];
        foreach ($rows as $row) {
            $total = (int) $row->total_customers;
            // Suppress for small samples — 1/2 = 50% repeat is noise, not signal.
            if ($total < 5) {
                $map[$row->product_external_id] = null;
                continue;
            }
            $map[$row->product_external_id] = round((int) $row->repeat_customers / $total, 4);
        }

        return $map;
    }

    /**
     * Derive lifecycle label per docs/pages/products.md §"Product Labels".
     */
    private function deriveLabel(
        int $rank,
        int $totalProducts,
        float $revenue,
        float $topDecileThreshold,
        ?float $marginPct,
        ?float $velocity,
        ?float $compareVelocity,
    ): ?string {
        // Rockstar: top decile revenue AND margin >= 40%.
        if ($revenue >= $topDecileThreshold && $marginPct !== null && $marginPct >= 40.0) {
            return 'rockstar';
        }

        // Velocity-based labels require a comparison baseline.
        if ($velocity !== null && $compareVelocity !== null && $compareVelocity > 0) {
            $ratio = $velocity / $compareVelocity;

            // At-Risk: velocity down ≥15% AND margin in bottom quartile (< 20%).
            if ($ratio <= 0.85 && $marginPct !== null && $marginPct < 20.0) {
                return 'at_risk';
            }

            // Hot: velocity up ≥25%.
            if ($ratio >= 1.25) {
                return 'hot';
            }

            // Cold: velocity down ≥25%.
            if ($ratio <= 0.75) {
                return 'cold';
            }
        }

        return null;
    }

    /**
     * Compute summary metrics across all products.
     *
     * @param  list<array<string, mixed>>  $products
     * @return array<string, mixed>
     */
    private function buildMetrics(array $products): array
    {
        $productsSold = count(array_filter($products, fn($p) => $p['units'] > 0));

        $totalRevenue = array_sum(array_column($products, 'revenue'));

        // Top-10 concentration.
        $top10Revenue     = array_sum(array_column(array_slice($products, 0, 10), 'revenue'));
        $top10Concentration = $totalRevenue > 0 ? round(($top10Revenue / $totalRevenue) * 100, 2) : null;

        // Median gross margin — only products with cogs configured.
        $margins = array_filter(
            array_column($products, 'margin_pct'),
            fn($m) => $m !== null,
        );
        $medianGrossMargin = count($margins) > 0 ? $this->median(array_values($margins)) : null;

        // Velocity stats.
        $velocities = array_filter(
            array_column($products, 'velocity'),
            fn($v) => $v !== null,
        );
        $velocityMean   = count($velocities) > 0 ? round(array_sum($velocities) / count($velocities), 4) : null;
        $velocityMedian = count($velocities) > 0 ? $this->median(array_values($velocities)) : null;
        $velocityMode   = count($velocities) > 0 ? $this->mode(array_values($velocities)) : null;

        return [
            'products_sold'        => $productsSold,
            'top10_concentration'  => $top10Concentration,
            'median_gross_margin'  => $medianGrossMargin,
            'velocity_mean'        => $velocityMean,
            'velocity_median'      => $velocityMedian,
            'velocity_mode'        => $velocityMode,
        ];
    }

    /**
     * Build Pareto data (top 50 by revenue, with cumulative %).
     *
     * @param  list<array<string, mixed>>  $products  Already sorted revenue DESC.
     * @return list<array<string, mixed>>
     */
    private function buildParetoData(array $products, float $totalRevenue): array
    {
        $top50  = array_slice($products, 0, 50);
        $result = [];
        $cumulative = 0.0;

        foreach ($top50 as $i => $p) {
            $cumulative += (float) $p['revenue'];
            $cumulativePct = $totalRevenue > 0 ? round(($cumulative / $totalRevenue) * 100, 2) : 0.0;

            $result[] = [
                'rank'           => $i + 1,
                'revenue'        => (float) $p['revenue'],
                'cumulative_pct' => $cumulativePct,
                'name'           => $p['name'],
            ];
        }

        return $result;
    }

    /** Median of a sorted or unsorted array of floats/ints. */
    private function median(array $values): float
    {
        sort($values);
        $n = count($values);
        $mid = (int) floor($n / 2);

        return $n % 2 === 0
            ? round(($values[$mid - 1] + $values[$mid]) / 2, 4)
            : round((float) $values[$mid], 4);
    }

    /** Approximate mode — value rounded to 2dp most commonly occurring. */
    private function mode(array $values): float
    {
        $counts = [];
        foreach ($values as $v) {
            $key = (string) round($v, 2);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        arsort($counts);

        return (float) array_key_first($counts);
    }

    /** Return value at given percentile (0–1) from an array of numbers. */
    private function percentileThreshold(array $values, float $pct): float
    {
        if (empty($values)) {
            return 0.0;
        }
        sort($values);
        $index = max(0, (int) ceil($pct * count($values)) - 1);

        return (float) $values[$index];
    }
}
