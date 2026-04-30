<?php

declare(strict_types=1);

namespace App\Services\Snapshots;

use App\Models\Store;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single authoritative builder for daily_snapshots, hourly_snapshots, and daily_snapshot_products.
 *
 * Extracts the logic from ComputeDailySnapshotJob, ComputeHourlySnapshotsJob, and
 * related snapshot-building code into a reusable service. Emits the 13 new profit/per-source
 * revenue columns.
 *
 * Public methods:
 *  - buildDaily(storeId, date) — builds a daily snapshot for store + date
 *  - buildHourly(storeId, date) — builds hourly snapshots for store + date (24 rows)
 *  - buildProducts(storeId, date, limit=100) — builds top-N product snapshots for store + date
 *  - recomputeVelocity28d(workspaceId) — updates velocity_28d on all product_variants
 *
 * Reads: orders, order_items, ad_insights, refunds, product_variants, cost tables
 * Writes: daily_snapshots, hourly_snapshots, daily_snapshot_products, product_variants
 *
 * Called by: BuildDailySnapshotJob, BuildHourlySnapshotJob, UpdateCostConfigAction (fan-out),
 *            ComputeHourlySnapshotsJob, ComputeDailySnapshotJob (via refactor),
 *            ComputeVelocityJob (nightly)
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/planning/schema.md §1.5 (table specs)
 * @see docs/decisions/004-snapshot-based-aggregation.md
 */
class SnapshotBuilderService
{
    /**
     * Rolling look-back window (in days) for the 28-day velocity calculation.
     *
     * Used in recomputeVelocity28d() and referenced by ComputeVelocityJob.
     * 28 days is the standard velocity window per docs/UX.md §F10.
     */
    public const VELOCITY_WINDOW_DAYS = 28;

    /**
     * Minimum distinct sale days required before a variant earns a non-null velocity.
     *
     * Prevents one-hit-wonder variants from distorting the velocity score.
     * @see recomputeVelocity28d()
     */
    private const VELOCITY_MIN_SALE_DAYS = 3;

    /**
     * Build the daily snapshot for a store + date.
     *
     * Computes the row for daily_snapshots, including:
     *  - Core metrics (orders_count, revenue, AOV, items)
     *  - Customer metrics (new_customers, returning_customers)
     *  - Per-source revenue (store, facebook, google, gsc, ga4, direct, organic, email, real)
     *  - Profit components (discounts, refunds, cogs, shipping, transaction_fees)
     *  - Sessions (from Shopify analytics)
     *
     * Uses upsert (INSERT ... ON CONFLICT DO UPDATE) so it is safe to
     * call multiple times for the same store + date.
     *
     * @param int    $storeId
     * @param Carbon $date
     * @return void
     */
    public function buildDaily(int $storeId, Carbon $date): void
    {
        $workspaceId = $this->resolveWorkspaceId($storeId, 'buildDaily');
        if ($workspaceId === null) {
            return;
        }

        $dateStr = $date->toDateString();

        // Skip if workspace trial expired.
        $ws = Workspace::withoutGlobalScopes()
            ->select(['id', 'trial_ends_at', 'billing_plan'])
            ->find($workspaceId);
        if ($ws && $ws->trial_ends_at !== null && $ws->trial_ends_at->lt(now()) && $ws->billing_plan === null) {
            Log::info('SnapshotBuilderService::buildDaily: skipped — workspace trial expired', ['workspace_id' => $workspaceId]);
            return;
        }

        $dayStart = $dateStr . ' 00:00:00';
        $dayEnd = $dateStr . ' 23:59:59';

        // A. Core order metrics
        // EXPLAIN: Index Scan on orders_workspace_id_store_id_occurred_at_index
        //          (workspace_id, store_id, occurred_at) — filters to store × day in one step.
        // Expected: <10ms for a single store/day; partial index idx_orders_ws_occurred_real
        //           (WHERE status IN ('completed','processing')) may be chosen instead when
        //           the status filter is very selective (>90% of orders have those statuses).
        $core = DB::table('orders')
            ->selectRaw('
                COUNT(*)::int                    AS orders_count,
                SUM(total_in_reporting_currency) AS revenue,
                SUM(total)                       AS revenue_native
            ')
            ->where('workspace_id', $workspaceId)
            ->where('store_id', $storeId)
            ->whereBetween('occurred_at', [$dayStart, $dayEnd])
            ->whereIn('status', ['completed', 'processing'])
            ->first();

        $ordersCount = (int) ($core->orders_count ?? 0);
        $revenue = $core->revenue !== null ? (float) $core->revenue : null;
        $revenueNative = (float) ($core->revenue_native ?? 0);

        // B. Items sold
        $items = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->selectRaw('SUM(oi.quantity)::int AS items_sold')
            ->where('o.workspace_id', $workspaceId)
            ->where('o.store_id', $storeId)
            ->whereBetween('o.occurred_at', [$dayStart, $dayEnd])
            ->whereIn('o.status', ['completed', 'processing'])
            ->first();

        $itemsSold = (int) ($items->items_sold ?? 0);
        // items_per_order: column dropped from daily_snapshots — compute at call-site when needed.

        // C. New vs returning customers
        // EXPLAIN: Nested-loop on daily_snapshots_workspace_id_store_id_date_index
        //          driving into orders_workspace_id_store_id_occurred_at_index.
        //          The CTE is materialised once; the self-join on customer_email_hash benefits
        //          from idx_orders_customer_hash_occurred (workspace, hash, occurred_at).
        // Expected: <50ms for a store with 10k customers in the 90d window;
        //           <200ms at 100k customers per day (unusual single-store spike).
        $customerStats = DB::selectOne("
            WITH day_customers AS (
                SELECT DISTINCT customer_email_hash
                FROM orders
                WHERE workspace_id = ?
                  AND store_id = ?
                  AND occurred_at BETWEEN ? AND ?
                  AND status IN ('completed','processing')
                  AND customer_email_hash IS NOT NULL
            ),
            first_appearances AS (
                SELECT dc.customer_email_hash, MIN(o.occurred_at::date) AS first_date
                FROM day_customers dc
                JOIN orders o
                  ON o.customer_email_hash = dc.customer_email_hash
                 AND o.workspace_id = ?
                 AND o.store_id = ?
                 AND o.status IN ('completed','processing')
                GROUP BY dc.customer_email_hash
            )
            SELECT
                SUM(CASE WHEN first_date = ? THEN 1 ELSE 0 END)::int AS new_customers,
                SUM(CASE WHEN first_date  < ? THEN 1 ELSE 0 END)::int AS returning_customers
            FROM first_appearances
        ", [$workspaceId, $storeId, $dayStart, $dayEnd, $workspaceId, $storeId, $dateStr, $dateStr]);

        $newCustomers = (int) ($customerStats->new_customers ?? 0);
        $returningCustomers = (int) ($customerStats->returning_customers ?? 0);

        // C2. New-customer revenue: SUM of total_in_reporting_currency for orders that are
        // each customer's first-ever order for this workspace (identified by MIN(id) per
        // customer_id across all time, then filtered to orders that fall on this date).
        // Using MIN(id) rather than MIN(occurred_at) avoids ties on the same timestamp.
        // EXPLAIN: the subquery builds a small set of first-order IDs workspace-wide, then
        //          the outer filter narrows to the day. Two passes but each is index-backed.
        $newCustomerRevenueRow = DB::selectOne("
            WITH first_orders AS (
                SELECT MIN(id) AS id
                FROM orders
                WHERE workspace_id = ?
                  AND status IN ('completed', 'processing')
                GROUP BY customer_id
            )
            SELECT COALESCE(SUM(o.total_in_reporting_currency), 0) AS new_customer_revenue
            FROM orders o
            JOIN first_orders fo ON fo.id = o.id
            WHERE o.workspace_id = ?
              AND o.store_id = ?
              AND o.occurred_at BETWEEN ? AND ?
        ", [$workspaceId, $workspaceId, $storeId, $dayStart, $dayEnd]);

        $newCustomerRevenue = (float) ($newCustomerRevenueRow->new_customer_revenue ?? 0);

        // C3. Organic orders count: COUNT of orders attributed to organic_search channel.
        // Mirrors the WHERE clause used to compute revenue_gsc_attributed (section D below)
        // so the two metrics stay in sync.
        $organicOrdersRow = DB::table('orders')
            ->selectRaw("COUNT(*)::int AS organic_orders_count")
            ->where('workspace_id', $workspaceId)
            ->where('store_id', $storeId)
            ->whereBetween('occurred_at', [$dayStart, $dayEnd])
            ->whereIn('status', ['completed', 'processing'])
            ->whereRaw("attribution_last_touch->>'channel_type' = 'organic_search'")
            ->first();

        $organicOrdersCount = (int) ($organicOrdersRow->organic_orders_count ?? 0);

        // D. Per-source revenue (from attribution_last_touch->>'channel_type' JSONB field)
        // EXPLAIN: single pass on orders_workspace_id_store_id_occurred_at_index
        //          with conditional SUM per channel_type value extracted from JSONB.
        //          All 6 source buckets computed in one scan — no per-source round-trips.
        // Expected: <20ms for a store/day with <5k orders.
        // channel_type values: paid_social, paid_search, organic_search, direct,
        //                      organic_social, referral, email
        // @see docs/planning/schema.md §Orders (attribution_last_touch JSONB)
        $sourceRevenues = DB::table('orders')
            ->selectRaw("
                SUM(CASE WHEN attribution_last_touch->>'channel_type' = 'paid_social'    THEN total_in_reporting_currency ELSE 0 END) AS revenue_facebook_attributed,
                SUM(CASE WHEN attribution_last_touch->>'channel_type' = 'paid_search'    THEN total_in_reporting_currency ELSE 0 END) AS revenue_google_attributed,
                SUM(CASE WHEN attribution_last_touch->>'channel_type' = 'organic_search' THEN total_in_reporting_currency ELSE 0 END) AS revenue_gsc_attributed,
                SUM(CASE WHEN attribution_last_touch->>'channel_type' = 'direct'         THEN total_in_reporting_currency ELSE 0 END) AS revenue_direct_attributed,
                SUM(CASE WHEN attribution_last_touch->>'channel_type' IN ('organic_social','referral') THEN total_in_reporting_currency ELSE 0 END) AS revenue_organic_attributed,
                SUM(CASE WHEN attribution_last_touch->>'channel_type' = 'email'          THEN total_in_reporting_currency ELSE 0 END) AS revenue_email_attributed,
                SUM(total_in_reporting_currency) AS revenue_store_attributed_total
            ")
            ->where('workspace_id', $workspaceId)
            ->where('store_id', $storeId)
            ->whereBetween('occurred_at', [$dayStart, $dayEnd])
            ->whereIn('status', ['completed', 'processing'])
            ->whereNotNull('total_in_reporting_currency')
            ->first();

        $revenueFacebookAttributed = (float) ($sourceRevenues->revenue_facebook_attributed ?? 0);
        $revenueGoogleAttributed = (float) ($sourceRevenues->revenue_google_attributed ?? 0);
        $revenueGscAttributed = (float) ($sourceRevenues->revenue_gsc_attributed ?? 0);
        $revenueDirectAttributed = (float) ($sourceRevenues->revenue_direct_attributed ?? 0);
        $revenueOrganicAttributed = (float) ($sourceRevenues->revenue_organic_attributed ?? 0);
        $revenueEmailAttributed = (float) ($sourceRevenues->revenue_email_attributed ?? 0);

        // revenue_store_attributed = total revenue in reporting currency from all orders for this store.
        // This is the "Store lens" baseline: every order is store-attributed in the trivial sense.
        // Used by the source-switcher on /dashboard and /analytics when the Store badge is selected.
        // @see docs/UX.md §7 (trust thesis — source badges)
        $revenueStoreAttributed = (float) ($sourceRevenues->revenue_store_attributed_total ?? 0);

        // revenue_ga4_attributed = revenue from orders that have a matching ga4_order_attribution row.
        // Orders are matched via orders.external_id = ga4_order_attribution.transaction_id.
        // Only populated when a GA4 property is connected and SyncGA4OrderAttributionJob has run.
        // @see app/Jobs/SyncGA4OrderAttributionJob.php
        $revenueGa4Attributed = (float) DB::table('orders as o')
            ->join('ga4_order_attribution as g', function ($join) use ($workspaceId) {
                $join->on('g.transaction_id', '=', 'o.external_id')
                     ->where('g.workspace_id', $workspaceId);
            })
            ->where('o.workspace_id', $workspaceId)
            ->where('o.store_id', $storeId)
            ->whereBetween('o.occurred_at', [$dayStart, $dayEnd])
            ->whereIn('o.status', ['completed', 'processing'])
            ->whereNotNull('o.total_in_reporting_currency')
            ->sum('o.total_in_reporting_currency');

        // Real = Store truth: the authoritative total of what was actually paid.
        // Per the Source Disagreement thesis, Real IS the store total. When platform-reported
        // conversion values exceed this figure, "Not Tracked" goes negative — that delta is
        // computed at query time (dashboard TrustBar, attribution drill-in), not stored here.
        // Storing the sum-of-source-attributions here would undercount (unattributed orders
        // would vanish from "Real") and violates the thesis that Real >= every platform claim.
        // @see docs/decisions/001-source-disagreement-as-thesis.md
        $revenueRealAttributed = $revenueStoreAttributed;

        // E. Profit components — consolidated into one orders pass + one refunds pass.
        // Discounts, shipping_cost_snapshot, and payment_fee are all on the orders table;
        // pulling them in a single query avoids 3 redundant full-table scans.
        $profitRow = DB::table('orders')
            ->selectRaw('
                COALESCE(SUM(discount), 0)                AS discounts_total,
                COALESCE(SUM(shipping_cost_snapshot), 0)  AS shipping_cost_total,
                COALESCE(SUM(payment_fee), 0)             AS transaction_fees_total
            ')
            ->where('workspace_id', $workspaceId)
            ->where('store_id', $storeId)
            ->whereBetween('occurred_at', [$dayStart, $dayEnd])
            ->whereIn('status', ['completed', 'processing'])
            ->first();

        $discountsTotal       = (float) ($profitRow->discounts_total        ?? 0);
        $shippingCostTotal    = (float) ($profitRow->shipping_cost_total    ?? 0);
        $transactionFeesTotal = (float) ($profitRow->transaction_fees_total ?? 0);

        // Refunds — separate table, separate pass (cannot be merged with orders scan).
        $refundsRow = DB::table('refunds')
            ->join('orders', 'orders.id', '=', 'refunds.order_id')
            ->selectRaw('COALESCE(SUM(refunds.amount), 0) AS total')
            ->where('orders.workspace_id', $workspaceId)
            ->where('orders.store_id', $storeId)
            ->whereBetween('refunds.refunded_at', [$dayStart, $dayEnd])
            ->first();
        $refundsTotal = (float) ($refundsRow->total ?? 0);

        // COGS (sum of unit_cost * quantity from order_items, separate join table).
        // When unit_cost IS NULL and a workspace-level fallback % is configured, estimate
        // COGS as unit_price * qty * fallback_pct / 100 for those items.
        $workspace       = Workspace::withoutGlobalScopes()->find($workspaceId);
        $defaultCogsPct  = $workspace?->workspace_settings?->defaultCogsPct;
        $fallbackFactor  = $defaultCogsPct !== null ? ($defaultCogsPct / 100.0) : 0.0;

        $cogsRow = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->selectRaw(
                'COALESCE(SUM(oi.unit_cost * oi.quantity), 0)
                 + COALESCE(SUM(CASE WHEN oi.unit_cost IS NULL THEN oi.unit_price * oi.quantity * ? ELSE 0 END), 0)
                 AS total',
                [$fallbackFactor],
            )
            ->where('o.workspace_id', $workspaceId)
            ->where('o.store_id', $storeId)
            ->whereBetween('o.occurred_at', [$dayStart, $dayEnd])
            ->whereIn('o.status', ['completed', 'processing'])
            ->first();
        $cogsTotal = (float) ($cogsRow->total ?? 0);

        // F. Sessions — resolver priority:
        //   1. Shopify store + shopify_daily_sessions row exists → sessions_source = 'shopify'
        //   2. TODO: GA4 fallback (WS-F) — when GA4 property is connected, read ga4_daily_sessions
        //   3. null + sessions_source = null
        [$sessions, $sessionsSource] = $this->resolveSessions($storeId, $workspaceId, $dateStr);

        // G. Upsert into daily_snapshots
        $now = now()->toDateTimeString();

        DB::table('daily_snapshots')->upsert(
            [[
                'workspace_id'              => $workspaceId,
                'store_id'                  => $storeId,
                'date'                      => $dateStr,
                'orders_count'              => $ordersCount,
                'revenue'                   => $revenue ?? 0,
                'revenue_native'            => $revenueNative,
                'items_sold'                => $itemsSold,
                // items_per_order: column dropped.
                'new_customers'             => $newCustomers,
                'returning_customers'       => $returningCustomers,
                'new_customer_revenue'      => $newCustomerRevenue,
                'organic_orders_count'      => $organicOrdersCount,
                'revenue_store_attributed'  => $revenueStoreAttributed,
                'revenue_facebook_attributed' => $revenueFacebookAttributed,
                'revenue_google_attributed' => $revenueGoogleAttributed,
                'revenue_gsc_attributed'    => $revenueGscAttributed,
                'revenue_ga4_attributed'    => $revenueGa4Attributed,
                'revenue_direct_attributed' => $revenueDirectAttributed,
                'revenue_organic_attributed' => $revenueOrganicAttributed,
                'revenue_email_attributed'  => $revenueEmailAttributed,
                'revenue_real_attributed'   => $revenueRealAttributed,
                'discounts_total'           => $discountsTotal,
                'refunds_total'             => $refundsTotal,
                'cogs_total'                => $cogsTotal,
                'shipping_cost_total'       => $shippingCostTotal,
                'transaction_fees_total'    => $transactionFeesTotal,
                'sessions'                  => $sessions,
                'sessions_source'           => $sessionsSource,
                'created_at'                => $now,
                'updated_at'                => $now,
            ]],
            ['store_id', 'date'],
            [
                'orders_count', 'revenue', 'revenue_native',
                'items_sold', 'new_customers', 'returning_customers',
                'new_customer_revenue', 'organic_orders_count',
                'revenue_store_attributed',
                'revenue_facebook_attributed', 'revenue_google_attributed', 'revenue_gsc_attributed',
                'revenue_ga4_attributed',
                'revenue_direct_attributed', 'revenue_organic_attributed', 'revenue_email_attributed',
                'revenue_real_attributed', 'discounts_total', 'refunds_total', 'cogs_total',
                'shipping_cost_total', 'transaction_fees_total', 'sessions', 'sessions_source',
                'updated_at',
            ],
        );

        Log::info('SnapshotBuilderService::buildDaily completed', [
            'store_id' => $storeId,
            'date' => $dateStr,
            'orders_count' => $ordersCount,
        ]);
    }

    /**
     * Build hourly snapshots for a store + date (24 rows, one per hour).
     *
     * Computes hourly rollups for:
     *  - orders_count, revenue (reporting currency)
     *  - Per-source revenue (facebook, google, real)
     *
     * @param int    $storeId
     * @param Carbon $date
     * @return void
     */
    public function buildHourly(int $storeId, Carbon $date): void
    {
        $workspaceId = $this->resolveWorkspaceId($storeId, 'buildHourly');
        if ($workspaceId === null) {
            return;
        }

        $dateStr = $date->toDateString();

        $dayStart = $dateStr . ' 00:00:00';
        $dayEnd = $dateStr . ' 23:59:59';

        // Query hourly data in one go, bucketing by attribution_last_touch->>'channel_type'.
        // paid_social → facebook bucket; paid_search → google bucket.
        // @see docs/planning/schema.md §Orders (attribution_last_touch JSONB)
        $hourlyData = DB::table('orders')
            ->selectRaw("
                EXTRACT(HOUR FROM occurred_at)::int AS hour,
                COUNT(*)::int AS orders_count,
                SUM(total_in_reporting_currency) AS revenue,
                SUM(CASE WHEN attribution_last_touch->>'channel_type' = 'paid_social'  THEN total_in_reporting_currency ELSE 0 END) AS revenue_facebook_attributed,
                SUM(CASE WHEN attribution_last_touch->>'channel_type' = 'paid_search'  THEN total_in_reporting_currency ELSE 0 END) AS revenue_google_attributed
            ")
            ->where('workspace_id', $workspaceId)
            ->where('store_id', $storeId)
            ->whereBetween('occurred_at', [$dayStart, $dayEnd])
            ->whereIn('status', ['completed', 'processing'])
            ->whereNotNull('total_in_reporting_currency')
            ->groupBy(DB::raw('EXTRACT(HOUR FROM occurred_at)'))
            ->orderBy('hour')
            ->get();

        if ($hourlyData->isEmpty()) {
            Log::info('SnapshotBuilderService::buildHourly: no data for date', [
                'store_id' => $storeId,
                'date' => $dateStr,
            ]);
            return;
        }

        $now = now()->toDateTimeString();
        $upsertRows = [];

        foreach ($hourlyData as $row) {
            $revenueFacebook = (float) ($row->revenue_facebook_attributed ?? 0);
            $revenueGoogle = (float) ($row->revenue_google_attributed ?? 0);
            $revenueReal = $revenueFacebook + $revenueGoogle;

            $upsertRows[] = [
                'workspace_id' => $workspaceId,
                'store_id' => $storeId,
                'date' => $dateStr,
                'hour' => (int) $row->hour,
                'orders_count' => (int) $row->orders_count,
                'revenue' => (float) ($row->revenue ?? 0),
                'revenue_facebook_attributed' => $revenueFacebook,
                'revenue_google_attributed' => $revenueGoogle,
                'revenue_real_attributed' => $revenueReal,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($upsertRows)) {
            DB::table('hourly_snapshots')->upsert(
                $upsertRows,
                ['store_id', 'date', 'hour'],
                [
                    'orders_count', 'revenue', 'revenue_facebook_attributed',
                    'revenue_google_attributed', 'revenue_real_attributed', 'updated_at',
                ],
            );
        }

        Log::info('SnapshotBuilderService::buildHourly completed', [
            'store_id' => $storeId,
            'date' => $dateStr,
            'hours' => count($upsertRows),
        ]);
    }

    /**
     * Build top-N product snapshots for a store + date.
     *
     * Computes daily_snapshot_products (top products by revenue).
     * Includes product metadata and stock state.
     *
     * @param int    $storeId
     * @param Carbon $date
     * @param int    $limit
     * @return void
     */
    public function buildProducts(int $storeId, Carbon $date, int $limit = 100): void
    {
        $workspaceId = $this->resolveWorkspaceId($storeId, 'buildProducts');
        if ($workspaceId === null) {
            return;
        }

        $dateStr = $date->toDateString();

        $dayStart = $dateStr . ' 00:00:00';
        $dayEnd = $dateStr . ' 23:59:59';

        // Get top products by revenue
        $productRows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->selectRaw("
                oi.product_external_id,
                MAX(oi.product_name) AS product_name,
                SUM(oi.quantity)::int AS units,
                SUM(oi.line_total * (o.total_in_reporting_currency / NULLIF(o.total, 0))) AS revenue
            ")
            ->where('o.workspace_id', $workspaceId)
            ->where('o.store_id', $storeId)
            ->whereBetween('o.occurred_at', [$dayStart, $dayEnd])
            ->whereIn('o.status', ['completed', 'processing'])
            ->whereNotNull('o.total_in_reporting_currency')
            ->groupBy('oi.product_external_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        if ($productRows->isEmpty()) {
            Log::info('SnapshotBuilderService::buildProducts: no products for date', [
                'store_id' => $storeId,
                'date' => $dateStr,
            ]);
            return;
        }

        $now = now()->toDateTimeString();
        $productUpsertRows = [];

        foreach ($productRows as $idx => $row) {
            $productUpsertRows[] = [
                'workspace_id' => $workspaceId,
                'store_id' => $storeId,
                'snapshot_date' => $dateStr,
                'product_external_id' => $row->product_external_id,
                'product_name' => mb_substr((string) $row->product_name, 0, 500),
                'revenue' => round((float) $row->revenue, 4),
                'units' => (int) $row->units,
                'rank' => $idx + 1,
                'created_at' => $now,
            ];
        }

        DB::table('daily_snapshot_products')->upsert(
            $productUpsertRows,
            ['store_id', 'snapshot_date', 'product_external_id'],
            ['product_name', 'revenue', 'units', 'rank'],
        );

        // Populate stock state from product_variants (stock columns live on the variant, not the product).
        // Aggregates across all variants per product (SUM qty, worst-case status).
        DB::statement("
            UPDATE daily_snapshot_products dsp
            SET stock_status   = agg.stock_status,
                stock_quantity = agg.stock_quantity
            FROM (
                SELECT
                    p.external_id                                                  AS product_external_id,
                    SUM(pv.stock_quantity)                                         AS stock_quantity,
                    MAX(pv.stock_status)                                           AS stock_status
                FROM product_variants pv
                JOIN products p ON p.id = pv.product_id
                WHERE pv.store_id = ?
                GROUP BY p.external_id
            ) agg
            WHERE agg.product_external_id = dsp.product_external_id
              AND dsp.store_id            = ?
              AND dsp.snapshot_date       = ?
        ", [$storeId, $storeId, $dateStr]);

        Log::info('SnapshotBuilderService::buildProducts completed', [
            'store_id' => $storeId,
            'date' => $dateStr,
            'products' => count($productUpsertRows),
        ]);
    }

    /**
     * Recompute velocity_28d for all product_variants in a workspace.
     *
     * velocity_28d = total units sold in last 28 days / 28
     *
     * Only set when the variant has sales on at least 3 distinct days in the window —
     * fewer distinct days means insufficient data, so velocity_28d is left NULL.
     * This guards days-of-cover calculations from noise on slow-moving SKUs.
     *
     * Uses a single CTE + UPDATE JOIN so the operation scales without per-variant loops.
     * Variants with no qualifying sales are NULLed out (idempotent, safe to re-run daily).
     *
     * Join path: order_items.product_external_id → products.external_id → product_variants
     * (product_variant_id FK on order_items is nullable until backfilled, so we go via products).
     *
     * @param int $workspaceId
     * @return int count of variants updated (velocity set or NULLed)
     *
     * @see docs/planning/schema.md §1.3 (product_variants.velocity_28d)
     */
    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Resolve sessions count and source for the given store + date.
     *
     * Priority:
     *   1. Shopify store → shopify_daily_sessions (source=NULL aggregate row) → sessions_source='shopify'
     *   2. TODO: GA4 fallback (WS-F) — add GA4 branch here once SyncGA4SessionsJob lands
     *   3. null, null
     *
     * Returns a two-element tuple: [sessions|null, sessions_source|null].
     *
     * SyncShopifyAnalyticsJob must have run before this builder for the Shopify
     * branch to find data. The scheduler runs analytics sync at 04:00, after
     * DispatchDailySnapshots at 00:30, so the first snapshot of the day may
     * have sessions=null until the 04:00 run completes. The nightly backfill
     * window (7 days) ensures the previous day's snapshot is corrected.
     *
     * @return array{int|null, string|null}
     */
    private function resolveSessions(int $storeId, int $workspaceId, string $dateStr): array
    {
        // Determine store platform without triggering WorkspaceScope on the stores table.
        $platform = DB::table('stores')
            ->where('id', $storeId)
            ->value('platform');

        if ($platform === 'shopify') {
            $row = DB::table('shopify_daily_sessions')
                ->where('store_id', $storeId)
                ->where('date', $dateStr)
                ->whereNull('source') // aggregate row; source=NULL = store total
                ->select(['visits'])
                ->first();

            if ($row !== null) {
                return [(int) $row->visits, 'shopify'];
            }
        }

        // GA4 fallback: sum sessions across all per-country/per-device rows for the date.
        // GA4 returns one row per (country, device) dimension — summing gives the store total.
        // Covers WooCommerce stores and any Shopify store without Shopify Analytics data.
        $ga4Sessions = (int) DB::table('ga4_daily_sessions as s')
            ->join('ga4_properties as p', 'p.id', '=', 's.ga4_property_id')
            ->where('p.workspace_id', $workspaceId)
            ->where('p.status', 'active')
            ->where('s.date', $dateStr)
            ->sum('s.sessions');

        if ($ga4Sessions > 0) {
            return [$ga4Sessions, 'ga4'];
        }

        return [null, null];
    }

    /**
     * Resolve workspace_id for a store and initialise WorkspaceContext.
     *
     * Extracted from the identical preamble in buildDaily, buildHourly, and
     * buildProducts — each used to open with the same 5-line block.
     *
     * Returns null (with a warning log) when the store row is missing.
     */
    private function resolveWorkspaceId(int $storeId, string $caller): ?int
    {
        $store = DB::table('stores')
            ->where('id', $storeId)
            ->select(['id', 'workspace_id'])
            ->first();

        if ($store === null) {
            Log::warning("SnapshotBuilderService::{$caller}: store not found", ['store_id' => $storeId]);
            return null;
        }

        $workspaceId = (int) $store->workspace_id;
        app(WorkspaceContext::class)->set($workspaceId);

        return $workspaceId;
    }

    public function recomputeVelocity28d(int $workspaceId): int
    {
        $windowStart = now()->subDays(self::VELOCITY_WINDOW_DAYS)->toDateTimeString();

        // Single SQL pass: aggregate order_items → products → product_variants.
        // Variants below VELOCITY_MIN_SALE_DAYS distinct days are NULLed.
        DB::statement("
            WITH sales AS (
                SELECT
                    pv.id                              AS variant_id,
                    SUM(oi.quantity)                   AS total_units,
                    COUNT(DISTINCT o.occurred_at::date) AS distinct_days
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                JOIN products p ON p.external_id = oi.product_external_id
                                AND p.workspace_id = o.workspace_id
                JOIN product_variants pv ON pv.product_id = p.id
                                        AND pv.workspace_id = p.workspace_id
                WHERE o.workspace_id = ?
                  AND o.status IN ('completed', 'processing')
                  AND o.occurred_at >= ?
                GROUP BY pv.id
            )
            UPDATE product_variants pv
            SET velocity_28d = CASE
                WHEN s.distinct_days >= " . self::VELOCITY_MIN_SALE_DAYS . "
                THEN ROUND((s.total_units / " . self::VELOCITY_WINDOW_DAYS . ".0)::numeric, 4)
                ELSE NULL
            END
            FROM sales s
            WHERE s.variant_id = pv.id
              AND pv.workspace_id = ?
        ", [$workspaceId, $windowStart, $workspaceId]);

        // NULL out variants that had velocity set but now have no recent sales.
        DB::statement("
            UPDATE product_variants
            SET velocity_28d = NULL
            WHERE workspace_id = ?
              AND velocity_28d IS NOT NULL
              AND id NOT IN (
                SELECT DISTINCT pv2.id
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                JOIN products p ON p.external_id = oi.product_external_id
                               AND p.workspace_id = o.workspace_id
                JOIN product_variants pv2 ON pv2.product_id = p.id
                                         AND pv2.workspace_id = p.workspace_id
                WHERE o.workspace_id = ?
                  AND o.status IN ('completed', 'processing')
                  AND o.occurred_at >= ?
              )
        ", [$workspaceId, $workspaceId, $windowStart]);

        $updated = DB::table('product_variants')
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('velocity_28d')
            ->count();

        Log::info('SnapshotBuilderService::recomputeVelocity28d completed', [
            'workspace_id' => $workspaceId,
            'variants_with_velocity' => $updated,
        ]);

        return $updated;
    }
}
