<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * UserFlowController — renders the /flow page (User Flow Funnel).
 *
 * Triggered by: GET /{workspace:slug}/flow
 * Reads from:   daily_snapshots, hourly_snapshots, orders (via utm_* columns),
 *               ad_insights (via campaigns.parsed_convention → source channel),
 *               products, order_line_items
 * Writes to:    nothing
 *
 * The funnel models five ordered steps of the ecommerce acquisition journey:
 *   1. Landing page (entry session from any source)
 *   2. Product page view (interest)
 *   3. Add to cart (intent)
 *   4. Checkout start (commitment)
 *   5. Purchase (conversion)
 *
 * Each step carries: count, step_cvr (vs previous step), funnel_cvr (vs entry),
 * and drop_off (absolute count lost at this step).
 *
 * Channel split: each funnel step is filterable by canonical source
 * (real / store / facebook / google / organic / direct / email).
 *
 * Product drill: for the "Product Page" and "Add to Cart" steps, the top 10
 * products are returned with add-to-cart rate, purchase rate, and a label
 * (winner / price_resistance / null) derived from those rates.
 *
 * Controllers are thin — mock data here mirrors the exact shape the frontend
 * expects. Replace with real DB queries when SnapshotBuilderService and
 * utm_* pipeline are wired for session-level funnel tracking.
 *
 * @see docs/competitors/_research_user_flow_funnel.md
 * @see docs/UX.md §5.1 MetricCard
 * @see docs/planning/backend.md #UserFlowController
 */
class UserFlowController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'from'    => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to'      => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'channel' => ['sometimes', 'nullable', 'string', 'in:all,facebook,google,organic,direct,email,store'],
        ]);

        $from    = $validated['from']    ?? now()->subDays(29)->toDateString();
        $to      = $validated['to']      ?? now()->toDateString();
        $channel = $validated['channel'] ?? 'all';

        // ── Funnel steps (aggregate, or per-channel when channel != 'all') ────────
        //
        // Realistic ecom session/conversion rates from research:
        //   100,000 sessions land → 60,000 view product → 8,400 add to cart
        //   → 4,200 checkout start → 2,100 purchase
        //
        // Per-channel scaling factors applied when a channel filter is active.
        // Facebook: more sessions, lower CVR. Google: fewer sessions, higher CVR.
        // Organic: middle-ground.

        $channelScalars = [
            'all'      => ['sessions' => 1.00, 'cvr' => 1.00],
            'facebook' => ['sessions' => 0.42, 'cvr' => 0.72],  // large volume, lower intent
            'google'   => ['sessions' => 0.28, 'cvr' => 1.35],  // smaller volume, high intent
            'organic'  => ['sessions' => 0.18, 'cvr' => 1.10],
            'direct'   => ['sessions' => 0.07, 'cvr' => 1.20],
            'email'    => ['sessions' => 0.05, 'cvr' => 1.55],  // best intent
            'store'    => ['sessions' => 1.00, 'cvr' => 1.00],
        ];

        $scalar       = $channelScalars[$channel] ?? $channelScalars['all'];
        $sessionScale = $scalar['sessions'];
        $cvrScale     = $scalar['cvr'];

        // Base funnel counts (all-channels, 30-day window)
        $entry     = (int) round(100_000 * $sessionScale);
        $pdpViews  = (int) round($entry   * 0.60 * min($cvrScale, 1.40));
        $addToCart = (int) round($pdpViews * 0.14 * $cvrScale);
        $checkout  = (int) round($addToCart * 0.50 * $cvrScale);
        $purchase  = (int) round($checkout  * 0.50 * $cvrScale);

        $funnelSteps = [
            [
                'key'        => 'landing',
                'label'      => 'Landing Page',
                'sublabel'   => 'Session entry from any source',
                'count'      => $entry,
                'step_cvr'   => null,          // no previous step
                'funnel_cvr' => 1.0,
                'drop_off'   => 0,
            ],
            [
                'key'        => 'product_view',
                'label'      => 'Product Page',
                'sublabel'   => 'Viewed at least one product',
                'count'      => $pdpViews,
                'step_cvr'   => $entry > 0 ? $pdpViews / $entry : 0,
                'funnel_cvr' => $entry > 0 ? $pdpViews / $entry : 0,
                'drop_off'   => $entry - $pdpViews,
            ],
            [
                'key'        => 'add_to_cart',
                'label'      => 'Add to Cart',
                'sublabel'   => 'Added at least one item',
                'count'      => $addToCart,
                'step_cvr'   => $pdpViews > 0 ? $addToCart / $pdpViews : 0,
                'funnel_cvr' => $entry > 0 ? $addToCart / $entry : 0,
                'drop_off'   => $pdpViews - $addToCart,
            ],
            [
                'key'        => 'checkout',
                'label'      => 'Checkout Start',
                'sublabel'   => 'Reached checkout page',
                'count'      => $checkout,
                'step_cvr'   => $addToCart > 0 ? $checkout / $addToCart : 0,
                'funnel_cvr' => $entry > 0 ? $checkout / $entry : 0,
                'drop_off'   => $addToCart - $checkout,
            ],
            [
                'key'        => 'purchase',
                'label'      => 'Purchase',
                'sublabel'   => 'Completed order',
                'count'      => $purchase,
                'step_cvr'   => $checkout > 0 ? $purchase / $checkout : 0,
                'funnel_cvr' => $entry > 0 ? $purchase / $entry : 0,
                'drop_off'   => $checkout - $purchase,
            ],
        ];

        // ── Per-channel breakdown for stacked view ────────────────────────────────
        $channelBreakdown = $this->buildChannelBreakdown($entry, $channel);

        // ── Top products for product-level drill ──────────────────────────────────
        // 10 products; mix of winners, overpriced, and average performers.
        // Label thresholds: winner = cart_rate ≥ 0.20 AND purchase_rate ≥ 0.40
        //                   price_resistance = cart_rate ≥ 0.20 AND purchase_rate < 0.15
        $topProducts = $this->buildTopProducts($pdpViews, $addToCart, $purchase, $channel);

        // ── KPI cards (summary above funnel) ─────────────────────────────────────
        $overallCvr = $entry > 0 ? $purchase / $entry : 0;
        $cartAbRate = $addToCart > 0 ? ($addToCart - $purchase) / $addToCart : 0;

        $kpis = [
            [
                'key'    => 'sessions',
                'label'  => 'Total Sessions',
                'value'  => $entry,
                'format' => 'number',
                'delta'  => 0.08,  // +8% vs prior period (mock)
            ],
            [
                'key'    => 'cvr',
                'label'  => 'Funnel CVR',
                'value'  => $overallCvr,
                'format' => 'percent',
                'delta'  => 0.12,  // +12%
            ],
            [
                'key'    => 'purchases',
                'label'  => 'Purchases',
                'value'  => $purchase,
                'format' => 'number',
                'delta'  => 0.15,
            ],
            [
                'key'    => 'cart_abandon',
                'label'  => 'Cart Abandon Rate',
                'value'  => $cartAbRate,
                'format' => 'percent',
                'delta'  => -0.04,  // -4% = improvement
            ],
        ];

        return Inertia::render('Flow/Index', [
            'from'             => $from,
            'to'               => $to,
            'channel'          => $channel,
            'funnel_steps'     => $funnelSteps,
            'channel_breakdown'=> $channelBreakdown,
            'top_products'     => $topProducts,
            'kpis'             => $kpis,
        ]);
    }

    /**
     * Build per-channel session/purchase counts for the stacked overview bar.
     * Returns channel rows ordered by session count desc.
     *
     * @return array<int, array{channel: string, label: string, sessions: int, purchases: int, cvr: float}>
     */
    private function buildChannelBreakdown(int $totalSessions, string $activeChannel): array
    {
        // Raw channel shares (must sum to ~1.0 for 'all' view)
        $channels = [
            ['channel' => 'facebook', 'label' => 'Facebook',       'share' => 0.42, 'cvr_factor' => 0.72],
            ['channel' => 'google',   'label' => 'Google',          'share' => 0.28, 'cvr_factor' => 1.35],
            ['channel' => 'organic',  'label' => 'Organic Search',  'share' => 0.18, 'cvr_factor' => 1.10],
            ['channel' => 'direct',   'label' => 'Direct',          'share' => 0.07, 'cvr_factor' => 1.20],
            ['channel' => 'email',    'label' => 'Email',           'share' => 0.05, 'cvr_factor' => 1.55],
        ];

        // Overall funnel CVR = 2.1%
        $baseCvr = 0.021;

        return array_map(function (array $ch) use ($totalSessions, $baseCvr): array {
            $sessions  = (int) round($totalSessions * $ch['share']);
            $channelCvr = $baseCvr * $ch['cvr_factor'];
            $purchases = (int) round($sessions * $channelCvr);
            return [
                'channel'   => $ch['channel'],
                'label'     => $ch['label'],
                'sessions'  => $sessions,
                'purchases' => $purchases,
                'cvr'       => $channelCvr,
            ];
        }, $channels);
    }

    /**
     * Build the top-10 product drill rows for a given funnel state.
     *
     * Labels:
     *   'winner'           — cart_rate ≥ 0.20 AND purchase_rate ≥ 0.40
     *   'price_resistance' — cart_rate ≥ 0.20 AND purchase_rate < 0.15
     *   null               — everything else
     *
     * Thresholds are heuristic for v1. v2: configurable per workspace.
     *
     * @return array<int, array{id: int, name: string, views: int, cart_rate: float, purchase_rate: float, cart_no_purchase_rate: float, label: string|null}>
     */
    private function buildTopProducts(int $pdpViews, int $addToCart, int $purchase, string $channel): array
    {
        // 10 mock products with varied CVR profiles
        // 5 winners, 3 price-resistance, 2 average
        $products = [
            // Winners (high cart, high purchase)
            ['id' => 1,  'name' => 'Classic Leather Sneakers',     'view_share' => 0.14, 'cart_rate' => 0.28, 'purchase_rate' => 0.52],
            ['id' => 2,  'name' => 'Performance Running Shoes',    'view_share' => 0.11, 'cart_rate' => 0.24, 'purchase_rate' => 0.48],
            ['id' => 3,  'name' => 'Waterproof Trail Boots',       'view_share' => 0.09, 'cart_rate' => 0.22, 'purchase_rate' => 0.44],
            ['id' => 4,  'name' => 'Minimalist Canvas Slip-ons',   'view_share' => 0.08, 'cart_rate' => 0.26, 'purchase_rate' => 0.41],
            ['id' => 5,  'name' => 'Premium Leather Oxford',       'view_share' => 0.07, 'cart_rate' => 0.21, 'purchase_rate' => 0.43],
            // Price-resistance (high cart, low purchase = possible overpriced)
            ['id' => 6,  'name' => 'Designer High-Top Sneakers',   'view_share' => 0.12, 'cart_rate' => 0.31, 'purchase_rate' => 0.09],
            ['id' => 7,  'name' => 'Limited Edition Collaboration','view_share' => 0.10, 'cart_rate' => 0.27, 'purchase_rate' => 0.11],
            ['id' => 8,  'name' => 'Luxury Suede Loafers',         'view_share' => 0.06, 'cart_rate' => 0.23, 'purchase_rate' => 0.12],
            // Average performers
            ['id' => 9,  'name' => 'Casual Slip-on Mules',        'view_share' => 0.08, 'cart_rate' => 0.12, 'purchase_rate' => 0.28],
            ['id' => 10, 'name' => 'Sport Sandals',               'view_share' => 0.05, 'cart_rate' => 0.09, 'purchase_rate' => 0.31],
        ];

        return array_map(function (array $p) use ($pdpViews): array {
            $views            = (int) round($pdpViews * $p['view_share']);
            $cartNoPurchase   = $p['cart_rate'] * (1 - $p['purchase_rate']);

            // Derive label from thresholds
            $label = null;
            if ($p['cart_rate'] >= 0.20 && $p['purchase_rate'] >= 0.40) {
                $label = 'winner';
            } elseif ($p['cart_rate'] >= 0.20 && $p['purchase_rate'] < 0.15) {
                $label = 'price_resistance';
            }

            return [
                'id'                  => $p['id'],
                'name'                => $p['name'],
                'views'               => $views,
                'cart_rate'           => $p['cart_rate'],
                'purchase_rate'       => $p['purchase_rate'],
                'cart_no_purchase_rate' => $cartNoPurchase,
                'label'               => $label,
            ];
        }, $products);
    }
}
