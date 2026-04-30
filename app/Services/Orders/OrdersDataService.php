<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\DailySnapshot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Metrics\MetricSourceResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds the Orders/Index page payload.
 *
 * One public method per top-level prop in resources/js/Pages/Orders/Index.tsx.
 * Order-list reads query the `orders` table directly (per-row detail is OK;
 * period aggregates use daily_snapshots per CLAUDE.md §Gotchas).
 *
 * Called by: App\Http\Controllers\OrdersController
 * Reads:     orders, order_items, daily_snapshots
 * Writes:    nothing (pure read service)
 *
 * @see docs/pages/orders.md
 * @see docs/planning/backend.md §8
 */
final class OrdersDataService
{
    private const PER_PAGE = 50;

    public function __construct(
        private readonly MetricSourceResolver $sourceResolver,
    ) {}

    /**
     * Paginated order list with per-row attribution and inline profit.
     *
     * Touchpoints are extracted from `attribution_first_touch` / `attribution_last_touch`
     * JSONB columns. `source_breakdown` stays null for list rows — only populated in
     * selectedOrder() (drawer detail). `winning_source` = `attribution_source`.
     *
     * The `$lensSource` parameter (distinct from the attribution-filter `$source`)
     * controls the `lens_total` field per row: when the lens is 'facebook', only
     * orders attributed to Facebook show their total; others show 0. This preserves
     * the full list while shifting headline numbers per the trust thesis.
     *
     * @param  string|null $source      Attribution-filter: only show orders from this source.
     * @param  string      $lensSource  Active source lens for the `lens_total` per-row field.
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function orders(
        int $workspaceId,
        string $from,
        string $to,
        ?string $source,
        ?string $status,
        ?string $country,
        int $page,
        string $lensSource = 'real',
    ): array {
        $query = Order::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->with([
                'items:id,order_id,product_name,sku,quantity,unit_price,unit_cost,line_total',
                'store' => fn ($q) => $q->withoutGlobalScopes()->select(['id', 'platform', 'website_url', 'domain']),
            ])
            ->orderByDesc('occurred_at');

        if ($source !== null && $source !== '') {
            $query->where('attribution_source', $source);
        }
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }
        if ($country !== null && $country !== '') {
            $query->where(function ($q) use ($country): void {
                $q->where('customer_country', $country)
                  ->orWhere('shipping_country', $country);
            });
        }

        $total   = $query->count();
        $offset  = ($page - 1) * self::PER_PAGE;
        $records = $query->offset($offset)->limit(self::PER_PAGE)->get();

        $rows = $records->map(function (Order $o) use ($lensSource): array {
            $cogs    = $this->cogsForOrder($o);
            $orderTotal = round((float) $o->total, 2);
            $profit  = $cogs !== null ? round($orderTotal - $cogs, 2) : null;
            $margin  = ($profit !== null && $orderTotal > 0)
                ? round($profit / $orderTotal * 100, 2)
                : null;

            // lens_total: for non-real lenses, only count revenue when the order's
            // winning attribution matches the lens; otherwise 0. For 'real' and 'store',
            // all orders count (store sees all, real is already reconciled to all).
            $lensTotal = match ($lensSource) {
                'real', 'store' => $orderTotal,
                default         => ($o->attribution_source === $lensSource ? $orderTotal : 0.0),
            };

            return [
                'id'                     => $o->id,
                'external_id'            => (string) $o->external_id,
                'external_number'        => $o->external_number,
                'occurred_at'            => $o->occurred_at?->toISOString(),
                'customer_email_masked'  => $this->maskEmail($o->customer_email_hash),
                'total'                  => $orderTotal,
                'lens_total'             => $lensTotal,
                'currency'               => $o->currency ?? 'EUR',
                'status'                 => $o->status ?? 'unknown',
                'winning_source'         => $o->attribution_source,
                'is_modeled'             => false, // no per-order modeled flag in schema yet
                'touchpoints'            => $this->touchpointsFromOrder($o),
                'country'                => $o->customer_country ?? $o->shipping_country,
                'cogs'                   => $cogs,
                'item_count'             => $o->items->count(),
                'is_new_customer'        => (bool) $o->is_first_for_customer,
                'fulfillment_status'     => $this->fulfillmentStatusFromOrder($o),
                'profit'                 => $profit,
                'margin_pct'             => $margin,
                'store_admin_url'        => $this->storeAdminUrlForOrder($o),
                // Attribution signal fields — used by the frontend gap-reason chip
                // to explain why an order has no/unknown attribution.
                'utm_source'             => $o->utm_source,
                'utm_medium'             => $o->utm_medium,
                'utm_campaign'           => $o->utm_campaign,
                'has_click_id'           => $this->hasClickId($o),
            ];
        })->all();

        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));

        return [
            'data' => $rows,
            'meta' => [
                'total'        => $total,
                'per_page'     => self::PER_PAGE,
                'current_page' => $page,
                'last_page'    => $lastPage,
            ],
        ];
    }

    /**
     * Period KPIs from daily_snapshots.
     *
     * `new_customer_pct` and `refund_rate` query the orders table directly —
     * these per-order flags don't aggregate onto snapshots yet.
     *
     * When $source is provided, revenue uses the lens-specific column from
     * daily_snapshots (MetricSourceResolver::REVENUE_COLUMN). AOV is re-computed from lens revenue
     * so "AOV under Facebook attribution" answers a meaningful question.
     *
     * @param  string  $source  Active source lens ('real' default).
     * @return array<string, float|int|null>
     */
    public function metrics(int $workspaceId, string $from, string $to, string $source = 'real'): array
    {
        $revenueColumn = $this->sourceResolver->columnFor('revenue', $source);

        $snap = DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw("
                COALESCE(SUM(orders_count), 0)             AS orders_count,
                COALESCE(SUM({$revenueColumn}), 0)         AS revenue,
                COALESCE(SUM(refunds_total), 0)            AS refunds_total
            ")
            ->first();

        $ordersCount   = (int) ($snap->orders_count ?? 0);
        $revenue       = (float) ($snap->revenue ?? 0);
        $refundsTotal  = (float) ($snap->refunds_total ?? 0);

        // AOV computed on the fly — NULLIF protects divide-by-zero.
        $aov = $ordersCount > 0
            ? round($revenue / $ordersCount, 2)
            : null;

        // new_customer_pct: count first-time orders in the window. Safe single-scan
        // on orders table scoped to the window (not an all-time aggregate).
        $newPct = $this->newCustomerPct($workspaceId, $from, $to);

        // refund_rate: refunds_total (revenue) / revenue — proxy for refund rate.
        $refundRate = $revenue > 0
            ? round($refundsTotal / $revenue * 100, 2)
            : null;

        return [
            'orders'          => $ordersCount,
            'aov'             => $aov,
            'new_customer_pct'=> $newPct,
            'refund_rate'     => $refundRate,
        ];
    }

    /**
     * Full detail for one order (drawer).
     *
     * Adds `line_items` and `source_breakdown` to the base OrderRow shape.
     * Queries order_items via the relationship — tenant isolation enforced
     * by resolving the Order through withoutGlobalScopes + where workspace_id.
     *
     * @return array<string, mixed>|null
     */
    public function selectedOrder(int $workspaceId, int $orderId): ?array
    {
        $o = Order::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $orderId)
            ->with([
                'items',
                'store' => fn ($q) => $q->withoutGlobalScopes()->select(['id', 'platform', 'website_url', 'domain']),
            ])
            ->first();

        if ($o === null) {
            return null;
        }

        $cogs   = $this->cogsForOrder($o);
        $profit = $cogs !== null ? round((float) $o->total - $cogs, 2) : null;
        $margin = ($profit !== null && (float) $o->total > 0)
            ? round($profit / (float) $o->total * 100, 2)
            : null;

        $lineItems = $o->items->map(fn (OrderItem $item): array => [
            'id'           => $item->id,
            'sku'          => $item->sku,
            'product_name' => $item->product_name,
            'quantity'     => (int) $item->quantity,
            'unit_price'   => round((float) $item->unit_price, 2),
            'cogs'         => $item->unit_cost !== null
                ? round((float) $item->unit_cost * $item->quantity, 2)
                : null,
            'line_total'   => round((float) $item->line_total, 2),
        ])->values()->all();

        return [
            'id'                     => $o->id,
            'external_id'            => (string) $o->external_id,
            'external_number'        => $o->external_number,
            'occurred_at'            => $o->occurred_at?->toISOString(),
            'customer_email_masked'  => $this->maskEmail($o->customer_email_hash),
            'total'                  => round((float) $o->total, 2),
            'currency'               => $o->currency ?? 'EUR',
            'status'                 => $o->status ?? 'unknown',
            'winning_source'         => $o->attribution_source,
            'is_modeled'             => false,
            'touchpoints'            => $this->touchpointsFromOrder($o),
            'country'                => $o->customer_country ?? $o->shipping_country,
            'cogs'                   => $cogs,
            'item_count'             => $o->items->count(),
            'is_new_customer'        => (bool) $o->is_first_for_customer,
            'fulfillment_status'     => $this->fulfillmentStatusFromOrder($o),
            'profit'                 => $profit,
            'margin_pct'             => $margin,
            'store_admin_url'        => $this->storeAdminUrlForOrder($o),
            'line_items'             => $lineItems,
            'source_breakdown'       => $this->sourceBreakdownForOrder($o),
            // Attribution signal fields — used by the frontend gap-reason chip.
            'utm_source'             => $o->utm_source,
            'utm_medium'             => $o->utm_medium,
            'utm_campaign'           => $o->utm_campaign,
            'has_click_id'           => $this->hasClickId($o),
        ];
    }

    // ─── private helpers ──────────────────────────────────────────────────────

    /**
     * Build touchpoints from first-touch + last-touch JSONB columns.
     * The full multi-touch journey (phase 6) will replace this stub when the
     * attribution_journey column lands. For now we surface up to 2 nodes.
     *
     * @return list<array<string, mixed>>
     */
    private function touchpointsFromOrder(Order $o): array
    {
        $touchpoints = [];

        $first = is_array($o->attribution_first_touch) ? $o->attribution_first_touch : null;
        $last  = is_array($o->attribution_last_touch)  ? $o->attribution_last_touch  : null;

        if ($first !== null) {
            $touchpoints[] = [
                'source'           => $first['source']      ?? null,
                'medium'           => $first['medium']      ?? null,
                'campaign'         => $first['campaign']    ?? null,
                'landing_page'     => $first['landing_page']?? null,
                'timestamp'        => $first['timestamp']   ?? null,
                'channel'          => $first['channel']     ?? null,
                'fractional_credit'=> null,
            ];
        }

        // Avoid duplicating the node when first == last.
        if ($last !== null && $last !== $first) {
            $touchpoints[] = [
                'source'           => $last['source']      ?? null,
                'medium'           => $last['medium']      ?? null,
                'campaign'         => $last['campaign']    ?? null,
                'landing_page'     => $last['landing_page']?? null,
                'timestamp'        => $last['timestamp']   ?? null,
                'channel'          => $last['channel']     ?? null,
                'fractional_credit'=> null,
            ];
        }

        return $touchpoints;
    }

    /**
     * Six-source revenue breakdown for the order detail drawer.
     *
     * Revenue by source = `attribution_source` determines the winning slot.
     * Other slots are null — per-order cross-source splits require platform
     * click-ID matching (Phase 6); for now only `real` (= order total) is
     * always populated. `store` = order total (the store's own view).
     *
     * @return array<string, float|null>
     */
    private function sourceBreakdownForOrder(Order $o): array
    {
        $total = round((float) $o->total, 2);
        $src   = $o->attribution_source;

        return [
            'store'    => $total,
            'facebook' => $src === 'facebook' ? $total : null,
            'google'   => $src === 'google'   ? $total : null,
            'gsc'      => $src === 'gsc'       ? $total : null,
            'ga4'      => in_array($src, ['direct', 'organic', 'email'], true) ? $total : null,
            'real'     => $total,
        ];
    }

    /**
     * Map the WooCommerce / Shopify status string to the three TSX fulfillment states.
     *
     * WooCommerce statuses: pending, processing, on-hold, completed, cancelled, refunded, failed.
     * Shopify statuses:     pending, open, closed, cancelled.
     * No dedicated fulfillment_status column exists; we derive from order status.
     *
     * @return 'fulfilled'|'pending'|'cancelled'|null
     */
    private function fulfillmentStatusFromOrder(Order $o): ?string
    {
        return match (strtolower((string) ($o->status ?? ''))) {
            'completed', 'closed'                     => 'fulfilled',
            'processing', 'pending', 'on-hold', 'open' => 'pending',
            'cancelled', 'canceled', 'refunded'       => 'cancelled',
            default                                   => null,
        };
    }

    /**
     * Build a deep-link URL to the order in the store's admin panel.
     *
     * Shopify:    https://{domain}/admin/orders/{external_id}
     * WooCommerce: {website_url}/wp-admin/post.php?post={external_id}&action=edit
     *
     * Returns null when the store relationship is not loaded, has no URL, or
     * platform is unknown — never fabricates a broken link.
     */
    private function storeAdminUrlForOrder(Order $o): ?string
    {
        $store = $o->relationLoaded('store') ? $o->store : null;
        if ($store === null || $o->external_id === null) {
            return null;
        }

        $platform = strtolower((string) $store->platform);
        $extId    = (string) $o->external_id;

        if ($platform === 'shopify') {
            $domain = $store->domain ?? $store->website_url;
            if (! $domain) {
                return null;
            }
            // Normalise: strip scheme + trailing slash.
            $host = rtrim(preg_replace('#^https?://#', '', $domain), '/');
            return "https://{$host}/admin/orders/{$extId}";
        }

        if ($platform === 'woocommerce') {
            $base = rtrim((string) ($store->website_url ?? ''), '/');
            if (! $base) {
                return null;
            }
            return "{$base}/wp-admin/post.php?post={$extId}&action=edit";
        }

        return null;
    }

    /**
     * Total COGS for an order: sum of unit_cost * quantity for all line items.
     * Returns null when no unit costs are on file.
     */
    private function cogsForOrder(Order $o): ?float
    {
        if ($o->items->isEmpty()) {
            return null;
        }

        $total = 0.0;
        $hasAny = false;

        foreach ($o->items as $item) {
            if ($item->unit_cost !== null) {
                $total  += (float) $item->unit_cost * (int) $item->quantity;
                $hasAny = true;
            }
        }

        return $hasAny ? round($total, 2) : null;
    }

    /**
     * Percentage of orders in the window that are from first-time customers.
     * Reads orders table for the window only — not an all-time aggregate.
     * Returns null when no orders exist.
     */
    private function newCustomerPct(int $workspaceId, string $from, string $to): ?float
    {
        $row = DB::selectOne('
            SELECT
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE is_first_for_customer = true) AS new_count
            FROM orders
            WHERE workspace_id = ?
              AND occurred_at BETWEEN ? AND ?
        ', [
            $workspaceId,
            $from . ' 00:00:00',
            $to . ' 23:59:59',
        ]);

        $total = (int) ($row->total ?? 0);
        if ($total === 0) {
            return null;
        }

        return round((int) $row->new_count / $total * 100, 2);
    }

    /**
     * Returns true when any platform click ID (fbclid, gclid, ttclid, etc.) was
     * captured for the order. Reads `attribution_click_ids` JSONB — non-empty array
     * with at least one non-null value. Used by the frontend gap-reason chip.
     */
    private function hasClickId(Order $o): bool
    {
        $ids = is_array($o->attribution_click_ids) ? $o->attribution_click_ids : [];
        foreach ($ids as $val) {
            if ($val !== null && $val !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * Derive a display-safe masked email from the hash column.
     * The hash is the actual stored value — we show a placeholder mask.
     * Full email is never stored per privacy design.
     */
    private function maskEmail(?string $hash): ?string
    {
        if ($hash === null) {
            return null;
        }
        // Show the first 4 chars of the hash as a proxy identifier.
        return '••••' . substr($hash, 0, 4) . '@••••';
    }
}
