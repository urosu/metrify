<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

/**
 * OrdersController — per-order ground truth with six-source attribution.
 *
 * index(): Paginated order list (50/page) with KPI strip, FilterChipSentence,
 *          and source attribution per row. Powers /orders.
 *
 * show():  Full attribution journey for a single order. Powers /orders/{order}.
 *          Reads orders.attribution_* JSONB set by AttributionParserService.
 *
 * Mock data is shipped for Wave 1 redesign verification. Real query logic
 * replaces the mock arrays once DB-layer work ships (see docs/planning/backend.md).
 *
 * @see docs/pages/orders.md
 * @see docs/UX.md §5.5 DataTable
 * @see docs/UX.md §5.10 DrawerSidePanel
 * @see docs/UX.md §5.4 FilterChipSentence
 */
class OrdersController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────────────────

    public function index(): Response
    {
        $orders = $this->mockOrders();
        $kpis   = $this->mockKpis();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'kpis'   => $kpis,
            'pagination' => [
                'total'        => 12847,
                'per_page'     => 50,
                'current_page' => 1,
                'last_page'    => 257,
                'from'         => 1,
                'to'           => 50,
            ],
            'filters' => [
                'from'          => now()->subDays(14)->toDateString(),
                'to'            => now()->toDateString(),
                'status'        => request('status'),
                'source'        => request('source'),
                'customer_type' => request('customer_type'),
                'min_value'     => request('min_value'),
                'max_value'     => request('max_value'),
                'store'         => request('store'),
                'order'         => request('order'),   // drawer state
            ],
            'available_filters' => [
                'statuses'       => ['completed', 'refunded', 'disputed', 'cancelled'],
                'sources'        => ['real', 'store', 'facebook', 'google', 'gsc', 'ga4'],
                'customer_types' => ['first_time', 'repeat'],
            ],
        ]);
    }

    // ── Show ───────────────────────────────────────────────────────────────────

    public function show(Order $order): Response
    {
        $order->load(['store:id,name,slug', 'items', 'refunds']);

        return Inertia::render('Orders/Show', [
            'order' => [
                'id'                   => $order->id,
                'external_id'          => $order->external_id,
                'external_number'      => $order->external_number,
                'status'               => $order->status,
                'currency'             => $order->currency,
                'total'                => (float) $order->total,
                'subtotal'             => (float) $order->subtotal,
                'tax'                  => (float) $order->tax,
                'shipping'             => (float) $order->shipping,
                'discount'             => (float) $order->discount,
                'refund_amount'        => (float) ($order->refund_amount ?? 0),
                'customer_country'     => $order->customer_country,
                'shipping_country'     => $order->shipping_country,
                'payment_method_title' => $order->payment_method_title,
                'occurred_at'          => $order->occurred_at?->toISOString(),
                'store'                => $order->store ? [
                    'id'   => $order->store->id,
                    'name' => $order->store->name,
                    'slug' => $order->store->slug,
                ] : null,
                'utm_source'              => $order->utm_source,
                'utm_medium'              => $order->utm_medium,
                'utm_campaign'            => $order->utm_campaign,
                'utm_content'             => $order->utm_content,
                'utm_term'                => $order->utm_term,
                'attribution_source'      => $order->attribution_source,
                'attribution_first_touch' => $order->attribution_first_touch,
                'attribution_last_touch'  => $order->attribution_last_touch,
                'attribution_click_ids'   => $order->attribution_click_ids,
                'attribution_parsed_at'   => $order->attribution_parsed_at?->toISOString(),
                'cogs_note'               => is_array($order->platform_data)
                    ? ($order->platform_data['cogs_note'] ?? null)
                    : null,
            ],
            'items'   => $order->items->map(fn ($item) => [
                'id'              => $item->id,
                'product_name'    => $item->product_name,
                'variant_name'    => $item->variant_name,
                'sku'             => $item->sku,
                'quantity'        => (int) $item->quantity,
                'unit_price'      => (float) $item->unit_price,
                'unit_cost'       => $item->unit_cost !== null ? (float) $item->unit_cost : null,
                'discount_amount' => (float) $item->discount_amount,
                'line_total'      => (float) $item->line_total,
            ])->values(),
            'refunds' => $order->refunds->map(fn ($refund) => [
                'id'          => $refund->id,
                'amount'      => (float) $refund->amount,
                'reason'      => $refund->reason,
                'refunded_at' => $refund->refunded_at?->toISOString(),
            ])->values(),
        ]);
    }

    // ── Mock data (Wave 1 — replace with real queries once DB layer ships) ─────

    /** @return array<int, array<string, mixed>> */
    private function mockOrders(): array
    {
        $base = now()->subDays(14);

        return [
            // 1 — FB claims, Real says Direct — source disagreement demo
            [
                'id'           => 'ord_01HWXK2A4B',
                'order_number' => '#10247',
                'created_at'   => $base->copy()->addDays(13)->addHours(14)->addMinutes(23)->toISOString(),
                'customer'     => ['email' => 'j***@example.com', 'name' => 'J. Smith', 'is_first_time' => false, 'order_count' => 3],
                'items_count'  => 2,
                'subtotal'     => 124.00, 'tax' => 9.92, 'shipping' => 6.00, 'discount' => 0.00, 'total' => 139.92,
                'currency'     => 'USD',
                'status'       => 'completed',
                'country'      => 'US',
                'cogs'         => 58.00,
                'primary_source' => 'real',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 139.92],
                    ['source' => 'store',    'attributed' => true,  'value' => 139.92],
                    ['source' => 'facebook', 'attributed' => true,  'value' => 139.92, 'campaign' => 'Spring Sale — Prospecting'],
                    ['source' => 'google',   'attributed' => false, 'value' => 0],
                    ['source' => 'ga4',      'attributed' => true,  'value' => 139.92, 'medium' => 'cpc'],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [
                    ['source' => 'facebook', 'ts' => $base->copy()->addDays(7)->toISOString(), 'campaign' => 'Spring Sale — Prospecting', 'credit' => 0.4],
                    ['source' => 'google',   'ts' => $base->copy()->addDays(11)->toISOString(), 'campaign' => 'Brand — Exact Match', 'credit' => 0.3],
                    ['source' => 'real',     'ts' => $base->copy()->addDays(13)->toISOString(), 'campaign' => null, 'credit' => 0.3],
                ],
                'confidence' => 'high',
                'is_modeled'  => false,
            ],
            // 2 — Google claims, Real says Organic (GSC)
            [
                'id'           => 'ord_01HWXK2B5C',
                'order_number' => '#10248',
                'created_at'   => $base->copy()->addDays(13)->addHours(10)->addMinutes(5)->toISOString(),
                'customer'     => ['email' => 'm***@gmail.com', 'name' => 'M. Johnson', 'is_first_time' => true, 'order_count' => 1],
                'items_count'  => 1,
                'subtotal'     => 289.00, 'tax' => 23.12, 'shipping' => 0.00, 'discount' => 20.00, 'total' => 292.12,
                'currency'     => 'USD',
                'status'       => 'completed',
                'country'      => 'CA',
                'cogs'         => 130.00,
                'primary_source' => 'gsc',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 292.12],
                    ['source' => 'store',    'attributed' => true,  'value' => 292.12],
                    ['source' => 'facebook', 'attributed' => false, 'value' => 0],
                    ['source' => 'google',   'attributed' => true,  'value' => 292.12, 'campaign' => 'Shopping — Generic'],
                    ['source' => 'ga4',      'attributed' => true,  'value' => 292.12, 'medium' => 'organic'],
                    ['source' => 'gsc',      'attributed' => true,  'value' => 292.12, 'query' => 'best running shoes'],
                ],
                'touchpoints' => [
                    ['source' => 'gsc',    'ts' => $base->copy()->addDays(12)->toISOString(), 'campaign' => null, 'credit' => 1.0],
                ],
                'confidence' => 'high',
                'is_modeled'  => false,
            ],
            // 3 — Refunded order
            [
                'id'           => 'ord_01HWXK2C6D',
                'order_number' => '#10249',
                'created_at'   => $base->copy()->addDays(12)->addHours(18)->toISOString(),
                'customer'     => ['email' => 'k***@yahoo.com', 'name' => 'K. Lee', 'is_first_time' => false, 'order_count' => 5],
                'items_count'  => 3,
                'subtotal'     => 198.00, 'tax' => 15.84, 'shipping' => 8.99, 'discount' => 0.00, 'total' => 222.83,
                'currency'     => 'USD',
                'status'       => 'refunded',
                'country'      => 'US',
                'cogs'         => null,
                'primary_source' => 'facebook',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 222.83],
                    ['source' => 'store',    'attributed' => true,  'value' => 222.83],
                    ['source' => 'facebook', 'attributed' => true,  'value' => 222.83, 'campaign' => 'Retargeting — 30d Visitors'],
                    ['source' => 'google',   'attributed' => false, 'value' => 0],
                    ['source' => 'ga4',      'attributed' => true,  'value' => 222.83, 'medium' => 'cpc'],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [
                    ['source' => 'facebook', 'ts' => $base->copy()->addDays(11)->toISOString(), 'campaign' => 'Retargeting — 30d Visitors', 'credit' => 0.7],
                    ['source' => 'facebook', 'ts' => $base->copy()->addDays(12)->toISOString(), 'campaign' => 'Retargeting — 7d Visitors', 'credit' => 0.3],
                ],
                'confidence' => 'medium',
                'is_modeled'  => false,
            ],
            // 4 — Not tracked
            [
                'id'           => 'ord_01HWXK2D7E',
                'order_number' => '#10250',
                'created_at'   => $base->copy()->addDays(12)->addHours(9)->toISOString(),
                'customer'     => ['email' => 'a***@hotmail.com', 'name' => 'A. Davis', 'is_first_time' => true, 'order_count' => 1],
                'items_count'  => 1,
                'subtotal'     => 45.00, 'tax' => 3.60, 'shipping' => 5.99, 'discount' => 5.00, 'total' => 49.59,
                'currency'     => 'USD',
                'status'       => 'completed',
                'country'      => 'US',
                'cogs'         => null,
                'primary_source' => null,
                'sources' => [
                    ['source' => 'real',     'attributed' => false, 'value' => 49.59],
                    ['source' => 'store',    'attributed' => true,  'value' => 49.59],
                    ['source' => 'facebook', 'attributed' => false, 'value' => 0],
                    ['source' => 'google',   'attributed' => false, 'value' => 0],
                    ['source' => 'ga4',      'attributed' => false, 'value' => 0],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [],
                'confidence' => 'low',
                'is_modeled'  => false,
            ],
            // 5 — Modeled attribution (iOS14 impact)
            [
                'id'           => 'ord_01HWXK2E8F',
                'order_number' => '#10251',
                'created_at'   => $base->copy()->addDays(11)->addHours(16)->addMinutes(44)->toISOString(),
                'customer'     => ['email' => 'r***@icloud.com', 'name' => 'R. Wilson', 'is_first_time' => true, 'order_count' => 1],
                'items_count'  => 4,
                'subtotal'     => 312.00, 'tax' => 24.96, 'shipping' => 0.00, 'discount' => 31.20, 'total' => 305.76,
                'currency'     => 'USD',
                'status'       => 'completed',
                'country'      => 'UK',
                'cogs'         => 140.00,
                'primary_source' => 'facebook',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 305.76],
                    ['source' => 'store',    'attributed' => true,  'value' => 305.76],
                    ['source' => 'facebook', 'attributed' => true,  'value' => 305.76, 'campaign' => 'DABA — Product Catalog', 'modeled' => true],
                    ['source' => 'google',   'attributed' => false, 'value' => 0],
                    ['source' => 'ga4',      'attributed' => false, 'value' => 0],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [
                    ['source' => 'facebook', 'ts' => $base->copy()->addDays(10)->toISOString(), 'campaign' => 'DABA — Product Catalog', 'credit' => 1.0, 'modeled' => true],
                ],
                'confidence' => 'medium',
                'is_modeled'  => true,
            ],
            // 6 — GA4 attribution (organic search)
            [
                'id'           => 'ord_01HWXK2F9G',
                'order_number' => '#10252',
                'created_at'   => $base->copy()->addDays(11)->addHours(11)->toISOString(),
                'customer'     => ['email' => 'e***@gmail.com', 'name' => 'E. Martinez', 'is_first_time' => false, 'order_count' => 2],
                'items_count'  => 2,
                'subtotal'     => 78.50, 'tax' => 6.28, 'shipping' => 6.00, 'discount' => 0.00, 'total' => 90.78,
                'currency'     => 'USD',
                'status'       => 'completed',
                'country'      => 'US',
                'cogs'         => null,
                'primary_source' => 'ga4',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 90.78],
                    ['source' => 'store',    'attributed' => true,  'value' => 90.78],
                    ['source' => 'facebook', 'attributed' => false, 'value' => 0],
                    ['source' => 'google',   'attributed' => false, 'value' => 0],
                    ['source' => 'ga4',      'attributed' => true,  'value' => 90.78, 'medium' => 'organic'],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [
                    ['source' => 'ga4', 'ts' => $base->copy()->addDays(11)->toISOString(), 'campaign' => null, 'credit' => 1.0],
                ],
                'confidence' => 'high',
                'is_modeled'  => false,
            ],
            // 7 — High-value order, multi-touch
            [
                'id'           => 'ord_01HWXK2G0H',
                'order_number' => '#10253',
                'created_at'   => $base->copy()->addDays(10)->addHours(19)->addMinutes(7)->toISOString(),
                'customer'     => ['email' => 't***@protonmail.com', 'name' => 'T. Anderson', 'is_first_time' => false, 'order_count' => 7],
                'items_count'  => 6,
                'subtotal'     => 425.00, 'tax' => 34.00, 'shipping' => 0.00, 'discount' => 42.50, 'total' => 416.50,
                'currency'     => 'USD',
                'status'       => 'completed',
                'country'      => 'US',
                'cogs'         => 190.00,
                'primary_source' => 'google',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 416.50],
                    ['source' => 'store',    'attributed' => true,  'value' => 416.50],
                    ['source' => 'facebook', 'attributed' => true,  'value' => 416.50, 'campaign' => 'Top of Funnel — Lookalike'],
                    ['source' => 'google',   'attributed' => true,  'value' => 416.50, 'campaign' => 'Branded — Exact'],
                    ['source' => 'ga4',      'attributed' => true,  'value' => 416.50, 'medium' => 'cpc'],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [
                    ['source' => 'facebook', 'ts' => $base->copy()->addDays(4)->toISOString(), 'campaign' => 'Top of Funnel — Lookalike', 'credit' => 0.3],
                    ['source' => 'ga4',      'ts' => $base->copy()->addDays(8)->toISOString(), 'campaign' => null, 'credit' => 0.2],
                    ['source' => 'google',   'ts' => $base->copy()->addDays(10)->toISOString(), 'campaign' => 'Branded — Exact', 'credit' => 0.5],
                ],
                'confidence' => 'high',
                'is_modeled'  => false,
            ],
            // 8 — Disputed order
            [
                'id'           => 'ord_01HWXK2H1I',
                'order_number' => '#10254',
                'created_at'   => $base->copy()->addDays(10)->addHours(8)->toISOString(),
                'customer'     => ['email' => 'c***@outlook.com', 'name' => 'C. Thompson', 'is_first_time' => true, 'order_count' => 1],
                'items_count'  => 2,
                'subtotal'     => 159.00, 'tax' => 12.72, 'shipping' => 9.99, 'discount' => 0.00, 'total' => 181.71,
                'currency'     => 'USD',
                'status'       => 'disputed',
                'country'      => 'AU',
                'cogs'         => null,
                'primary_source' => 'store',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 181.71],
                    ['source' => 'store',    'attributed' => true,  'value' => 181.71],
                    ['source' => 'facebook', 'attributed' => false, 'value' => 0],
                    ['source' => 'google',   'attributed' => false, 'value' => 0],
                    ['source' => 'ga4',      'attributed' => false, 'value' => 0],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [],
                'confidence' => 'low',
                'is_modeled'  => false,
            ],
            // 9 — FB + GSC multi-source disagreement
            [
                'id'           => 'ord_01HWXK2I2J',
                'order_number' => '#10255',
                'created_at'   => $base->copy()->addDays(9)->addHours(15)->addMinutes(30)->toISOString(),
                'customer'     => ['email' => 'p***@gmail.com', 'name' => 'P. Brown', 'is_first_time' => true, 'order_count' => 1],
                'items_count'  => 1,
                'subtotal'     => 79.99, 'tax' => 6.40, 'shipping' => 5.99, 'discount' => 0.00, 'total' => 92.38,
                'currency'     => 'USD',
                'status'       => 'completed',
                'country'      => 'DE',
                'cogs'         => 35.00,
                'primary_source' => 'real',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 92.38],
                    ['source' => 'store',    'attributed' => true,  'value' => 92.38],
                    ['source' => 'facebook', 'attributed' => true,  'value' => 92.38, 'campaign' => 'EU — Prospecting'],
                    ['source' => 'google',   'attributed' => false, 'value' => 0],
                    ['source' => 'ga4',      'attributed' => false, 'value' => 0],
                    ['source' => 'gsc',      'attributed' => true,  'value' => 92.38, 'query' => 'product review'],
                ],
                'touchpoints' => [
                    ['source' => 'gsc',      'ts' => $base->copy()->addDays(8)->toISOString(), 'campaign' => null, 'credit' => 0.5],
                    ['source' => 'facebook', 'ts' => $base->copy()->addDays(9)->toISOString(), 'campaign' => 'EU — Prospecting', 'credit' => 0.5],
                ],
                'confidence' => 'medium',
                'is_modeled'  => false,
            ],
            // 10 — Low AOV, direct
            [
                'id'           => 'ord_01HWXK2J3K',
                'order_number' => '#10256',
                'created_at'   => $base->copy()->addDays(9)->addHours(12)->toISOString(),
                'customer'     => ['email' => 'n***@aol.com', 'name' => 'N. Taylor', 'is_first_time' => false, 'order_count' => 4],
                'items_count'  => 1,
                'subtotal'     => 32.00, 'tax' => 2.56, 'shipping' => 4.99, 'discount' => 0.00, 'total' => 39.55,
                'currency'     => 'USD',
                'status'       => 'completed',
                'country'      => 'US',
                'cogs'         => null,
                'primary_source' => 'store',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 39.55],
                    ['source' => 'store',    'attributed' => true,  'value' => 39.55],
                    ['source' => 'facebook', 'attributed' => false, 'value' => 0],
                    ['source' => 'google',   'attributed' => false, 'value' => 0],
                    ['source' => 'ga4',      'attributed' => true,  'value' => 39.55, 'medium' => 'direct'],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [
                    ['source' => 'ga4', 'ts' => $base->copy()->addDays(9)->toISOString(), 'campaign' => null, 'credit' => 1.0],
                ],
                'confidence' => 'high',
                'is_modeled'  => false,
            ],
            // 11 — Email campaign (GA4 attributed)
            [
                'id'           => 'ord_01HWXK2K4L',
                'order_number' => '#10257',
                'created_at'   => $base->copy()->addDays(8)->addHours(20)->addMinutes(15)->toISOString(),
                'customer'     => ['email' => 's***@gmail.com', 'name' => 'S. Garcia', 'is_first_time' => false, 'order_count' => 2],
                'items_count'  => 3,
                'subtotal'     => 185.00, 'tax' => 14.80, 'shipping' => 0.00, 'discount' => 18.50, 'total' => 181.30,
                'currency'     => 'USD',
                'status'       => 'completed',
                'country'      => 'US',
                'cogs'         => 82.00,
                'primary_source' => 'ga4',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 181.30],
                    ['source' => 'store',    'attributed' => true,  'value' => 181.30],
                    ['source' => 'facebook', 'attributed' => false, 'value' => 0],
                    ['source' => 'google',   'attributed' => false, 'value' => 0],
                    ['source' => 'ga4',      'attributed' => true,  'value' => 181.30, 'medium' => 'email'],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [
                    ['source' => 'ga4', 'ts' => $base->copy()->addDays(8)->toISOString(), 'campaign' => 'Klaviyo — Post-Purchase Flow', 'credit' => 1.0],
                ],
                'confidence' => 'high',
                'is_modeled'  => false,
            ],
            // 12 — Cancelled order
            [
                'id'           => 'ord_01HWXK2L5M',
                'order_number' => '#10258',
                'created_at'   => $base->copy()->addDays(8)->addHours(7)->toISOString(),
                'customer'     => ['email' => 'w***@live.com', 'name' => 'W. Martinez', 'is_first_time' => true, 'order_count' => 1],
                'items_count'  => 2,
                'subtotal'     => 92.00, 'tax' => 7.36, 'shipping' => 6.99, 'discount' => 0.00, 'total' => 106.35,
                'currency'     => 'USD',
                'status'       => 'cancelled',
                'country'      => 'US',
                'cogs'         => null,
                'primary_source' => 'google',
                'sources' => [
                    ['source' => 'real',     'attributed' => false, 'value' => 0],
                    ['source' => 'store',    'attributed' => true,  'value' => 106.35],
                    ['source' => 'facebook', 'attributed' => false, 'value' => 0],
                    ['source' => 'google',   'attributed' => true,  'value' => 106.35, 'campaign' => 'Shopping — PMax'],
                    ['source' => 'ga4',      'attributed' => true,  'value' => 106.35, 'medium' => 'cpc'],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [
                    ['source' => 'google', 'ts' => $base->copy()->addDays(7)->toISOString(), 'campaign' => 'Shopping — PMax', 'credit' => 1.0],
                ],
                'confidence' => 'high',
                'is_modeled'  => false,
            ],
            // 13 — Over-reporting demo: FB + Google both claim
            [
                'id'           => 'ord_01HWXK2M6N',
                'order_number' => '#10259',
                'created_at'   => $base->copy()->addDays(7)->addHours(17)->addMinutes(42)->toISOString(),
                'customer'     => ['email' => 'l***@email.com', 'name' => 'L. Robinson', 'is_first_time' => false, 'order_count' => 6],
                'items_count'  => 5,
                'subtotal'     => 398.00, 'tax' => 31.84, 'shipping' => 0.00, 'discount' => 39.80, 'total' => 390.04,
                'currency'     => 'USD',
                'status'       => 'completed',
                'country'      => 'US',
                'cogs'         => 175.00,
                'primary_source' => 'real',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 390.04],
                    ['source' => 'store',    'attributed' => true,  'value' => 390.04],
                    ['source' => 'facebook', 'attributed' => true,  'value' => 390.04, 'campaign' => 'BFCM — Retargeting'],
                    ['source' => 'google',   'attributed' => true,  'value' => 390.04, 'campaign' => 'BFCM — Branded'],
                    ['source' => 'ga4',      'attributed' => true,  'value' => 390.04, 'medium' => 'cpc'],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [
                    ['source' => 'facebook', 'ts' => $base->copy()->addDays(3)->toISOString(), 'campaign' => 'BFCM — Retargeting', 'credit' => 0.2],
                    ['source' => 'google',   'ts' => $base->copy()->addDays(5)->toISOString(), 'campaign' => 'BFCM — Shopping', 'credit' => 0.3],
                    ['source' => 'ga4',      'ts' => $base->copy()->addDays(6)->toISOString(), 'campaign' => null, 'credit' => 0.2],
                    ['source' => 'google',   'ts' => $base->copy()->addDays(7)->toISOString(), 'campaign' => 'BFCM — Branded', 'credit' => 0.3],
                ],
                'confidence' => 'high',
                'is_modeled'  => false,
            ],
            // 14 — Low value, second refund
            [
                'id'           => 'ord_01HWXK2N7O',
                'order_number' => '#10260',
                'created_at'   => $base->copy()->addDays(7)->addHours(10)->toISOString(),
                'customer'     => ['email' => 'h***@gmail.com', 'name' => 'H. Clark', 'is_first_time' => false, 'order_count' => 8],
                'items_count'  => 1,
                'subtotal'     => 29.99, 'tax' => 2.40, 'shipping' => 3.99, 'discount' => 0.00, 'total' => 36.38,
                'currency'     => 'USD',
                'status'       => 'refunded',
                'country'      => 'US',
                'cogs'         => null,
                'primary_source' => 'ga4',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 36.38],
                    ['source' => 'store',    'attributed' => true,  'value' => 36.38],
                    ['source' => 'facebook', 'attributed' => false, 'value' => 0],
                    ['source' => 'google',   'attributed' => false, 'value' => 0],
                    ['source' => 'ga4',      'attributed' => true,  'value' => 36.38, 'medium' => 'email'],
                    ['source' => 'gsc',      'attributed' => false, 'value' => 0],
                ],
                'touchpoints' => [
                    ['source' => 'ga4', 'ts' => $base->copy()->addDays(7)->toISOString(), 'campaign' => 'Win-back Sequence', 'credit' => 1.0],
                ],
                'confidence' => 'high',
                'is_modeled'  => false,
            ],
            // 15 — High value, repeat customer, GSC organic
            [
                'id'           => 'ord_01HWXK2O8P',
                'order_number' => '#10261',
                'created_at'   => $base->copy()->addDays(6)->addHours(14)->addMinutes(20)->toISOString(),
                'customer'     => ['email' => 'b***@company.com', 'name' => 'B. Lewis', 'is_first_time' => false, 'order_count' => 12],
                'items_count'  => 8,
                'subtotal'     => 620.00, 'tax' => 49.60, 'shipping' => 0.00, 'discount' => 62.00, 'total' => 607.60,
                'currency'     => 'USD',
                'status'       => 'completed',
                'country'      => 'US',
                'cogs'         => 280.00,
                'primary_source' => 'gsc',
                'sources' => [
                    ['source' => 'real',     'attributed' => true,  'value' => 607.60],
                    ['source' => 'store',    'attributed' => true,  'value' => 607.60],
                    ['source' => 'facebook', 'attributed' => false, 'value' => 0],
                    ['source' => 'google',   'attributed' => false, 'value' => 0],
                    ['source' => 'ga4',      'attributed' => true,  'value' => 607.60, 'medium' => 'organic'],
                    ['source' => 'gsc',      'attributed' => true,  'value' => 607.60, 'query' => 'buy [product] online'],
                ],
                'touchpoints' => [
                    ['source' => 'gsc', 'ts' => $base->copy()->addDays(6)->toISOString(), 'campaign' => null, 'credit' => 1.0],
                ],
                'confidence' => 'high',
                'is_modeled'  => false,
            ],
            // 16-50: mix of orders with various sources and statuses
            ...$this->generateRemainingOrders($base, 16, 35),
        ];
    }

    /**
     * Generate orders 16-50 with varied sources and statuses.
     *
     * @return array<int, array<string, mixed>>
     */
    private function generateRemainingOrders(\Illuminate\Support\Carbon $base, int $from, int $count): array
    {
        $orders    = [];
        $statuses  = ['completed', 'completed', 'completed', 'completed', 'completed', 'completed', 'refunded', 'cancelled', 'disputed'];
        $sources   = ['real', 'facebook', 'google', 'gsc', 'ga4', 'store', null];
        $countries = ['US', 'US', 'US', 'CA', 'UK', 'DE', 'AU', 'FR'];
        $campaigns = [
            'Spring Sale — Prospecting',
            'Retargeting — 30d Visitors',
            'Shopping — PMax',
            'Branded — Exact',
            'Top of Funnel — Lookalike',
            'EU — Prospecting',
            'DABA — Product Catalog',
            null,
        ];

        for ($i = 0; $i < $count; $i++) {
            $num       = $from + $i;
            $orderNum  = 10247 + $num;
            $status    = $statuses[$i % count($statuses)];
            $srcKey    = $sources[$i % count($sources)];
            $country   = $countries[$i % count($countries)];
            $campaign  = $campaigns[$i % count($campaigns)];
            $daysAgo   = (int) (($i * 14) / $count);
            $total     = round(29.99 + ($i * 17.83), 2);
            $subtotal  = round($total * 0.88, 2);
            $tax       = round($total * 0.08, 2);
            $shipping  = $total > 100 ? 0.00 : 5.99;
            $discount  = $i % 4 === 0 ? round($subtotal * 0.1, 2) : 0.00;
            $isFirst   = $i % 3 === 0;
            $isModeled = $srcKey === 'facebook' && $i % 5 === 0;
            $hasCogs   = $i % 3 !== 0;

            $sourceRow = fn (string $s, bool $attr, float $val, ?string $c = null) => [
                'source'     => $s,
                'attributed' => $attr,
                'value'      => $val,
                'campaign'   => $c,
            ];

            $orders[] = [
                'id'           => "ord_01HWXK3{$num}XX",
                'order_number' => "#{$orderNum}",
                'created_at'   => $base->copy()->addDays(14 - $daysAgo)->addHours(($i * 3) % 23)->toISOString(),
                'customer'     => [
                    'email'        => chr(97 + ($i % 26)) . "***@mail.com",
                    'name'         => "Customer {$num}",
                    'is_first_time'=> $isFirst,
                    'order_count'  => $isFirst ? 1 : ($i % 8) + 2,
                ],
                'items_count'  => ($i % 5) + 1,
                'subtotal'     => $subtotal,
                'tax'          => $tax,
                'shipping'     => $shipping,
                'discount'     => $discount,
                'total'        => $total,
                'currency'     => 'USD',
                'status'       => $status,
                'country'      => $country,
                'cogs'         => $hasCogs ? round($total * 0.45, 2) : null,
                'primary_source'=> $srcKey,
                'sources'      => [
                    $sourceRow('real',     $srcKey !== null, $total),
                    $sourceRow('store',    true,             $total),
                    $sourceRow('facebook', $srcKey === 'facebook', $srcKey === 'facebook' ? $total : 0, $campaign),
                    $sourceRow('google',   $srcKey === 'google',   $srcKey === 'google'   ? $total : 0, $campaign),
                    $sourceRow('ga4',      in_array($srcKey, ['ga4', 'store']), in_array($srcKey, ['ga4', 'store']) ? $total : 0),
                    $sourceRow('gsc',      $srcKey === 'gsc',   $srcKey === 'gsc'   ? $total : 0),
                ],
                'touchpoints'  => $srcKey !== null ? [
                    ['source' => $srcKey, 'ts' => $base->copy()->addDays(14 - $daysAgo)->toISOString(), 'campaign' => $campaign, 'credit' => 1.0],
                ] : [],
                'confidence'   => $srcKey === null ? 'low' : ($i % 3 === 0 ? 'medium' : 'high'),
                'is_modeled'   => $isModeled,
            ];
        }

        return $orders;
    }

    /** @return array<string, mixed> */
    private function mockKpis(): array
    {
        return [
            'orders' => [
                'value'       => 12847,
                'delta_pct'   => 8.3,
                'sources'     => [
                    'store'    => 12847,
                    'facebook' => 11203,
                    'google'   => 10984,
                    'real'     => 12847,
                ],
            ],
            'revenue' => [
                'value'      => 2847392.00,
                'delta_pct'  => 11.2,
                'currency'   => 'USD',
                'sources'    => [
                    'store'    => 2847392.00,
                    'facebook' => 2621804.00,
                    'google'   => 2503211.00,
                    'real'     => 2847392.00,
                ],
            ],
            'aov' => [
                'value'     => 221.63,
                'delta_pct' => 2.7,
                'currency'  => 'USD',
            ],
            'refund_rate' => [
                'value'     => 4.8,
                'delta_pct' => -0.6,
            ],
            'pct_tracked' => [
                'value'    => 87.3,
                'delta_pct'=> 1.4,
            ],
            'top_source' => [
                'source' => 'facebook',
                'pct'    => 38.2,
            ],
        ];
    }
}
