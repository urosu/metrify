<?php

declare(strict_types=1);

namespace App\Services\Metrics;

use App\Models\AdAccount;
use App\Models\Ga4Property;
use App\Models\SearchConsoleProperty;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the best available data source for each metric input, per workspace.
 *
 * Purpose: centralise the fallback ladder logic that was previously scattered
 * across controllers and data services. Each call checks actual workspace state
 * (connected integrations, available tables) and returns the winning source.
 *
 * Resolution is memoised per workspaceId for the lifetime of the request — no
 * Redis needed; a plain in-process array cache is sufficient because an HTTP
 * request is always for a single workspace.
 *
 * Inputs (v1):
 *   purchases             — always 'store'
 *   revenue               — always 'store'
 *   sessions              — shopify → ga4 → null
 *   first_touch_source    — shopify_journey → ga4 → pys → wc_native → referrer
 *   last_touch_source     — shopify_journey → ga4 → pys → landing_parse → direct
 *   ad_spend              — facebook | google (from ad_insights.platform)
 *   search_clicks         — gsc
 *   platform_claimed_revenue — facebook | google
 *
 * Computed metrics (multi-input source map):
 *   cvr     sessions + purchases
 *   aov     revenue + purchases
 *   roas    revenue + ad_spend
 *   cac     ad_spend + purchases (new_customers)
 *   mer     revenue + ad_spend
 *
 * forWorkspace() returns a flat prop bag used by HandleInertiaRequests.
 *
 * Called by: App\Http\Middleware\HandleInertiaRequests (shared prop)
 * Reads:     ga4_properties, search_console_properties, ad_accounts,
 *            shopify_daily_sessions (existence check only)
 * Writes:    nothing
 *
 * @see docs/planning/backend.md §WS-G
 * @see docs/UX.md §5.1 MetricCard (source badge row)
 * @see config/features.php ga4_source_lens_enabled
 */
final class MetricSourceResolver
{
    /**
     * Per-request memo: workspaceId → resolved flags.
     *
     * @var array<int, array{
     *   has_ga4: bool,
     *   has_gsc: bool,
     *   has_facebook: bool,
     *   has_google: bool,
     *   has_shopify_sessions: bool,
     *   store_platform: string|null
     * }>
     */
    private array $memo = [];

    // ─── Source → column map ────────────────────────────────────────────────

    /**
     * Maps a source lens slug to the daily_snapshots column it reads for revenue.
     *
     * This is the single authoritative copy.  All data services (DashboardDataService,
     * AttributionDataService, AdsQueryService, OrdersDataService, ProfitDataService)
     * call columnFor() instead of maintaining their own private copies.
     *
     * 'store'    → raw gross-sales total; revenue_store_attributed is not yet
     *              populated (Phase 6), so `revenue` (raw) is the safe proxy.
     * 'ga4'      → TODO WS-F ga4_order_attribution join; falls back to real in
     *              the meantime so no data is dropped.
     * 'real'     → Nexstage-reconciled total (default lens).
     *
     * @see docs/planning/schema.md §1.5 daily_snapshots columns
     */
    public const REVENUE_COLUMN = [
        'store'    => 'revenue',                    // TODO: Phase 6 revenue_store_attributed
        'facebook' => 'revenue_facebook_attributed',
        'google'   => 'revenue_google_attributed',
        'gsc'      => 'revenue_gsc_attributed',
        'ga4'      => 'revenue_real_attributed',    // TODO: WS-F ga4_order_attribution join
        'real'     => 'revenue_real_attributed',
    ];

    // ─── Source label map ────────────────────────────────────────────────────

    private const SOURCE_LABELS = [
        'store'            => 'Store',
        'shopify'          => 'Shopify',
        'shopify_journey'  => 'Shopify',
        'woocommerce'      => 'WooCommerce',
        'ga4'              => 'GA4',
        'pys'              => 'PixelYourSite',
        'wc_native'        => 'WooCommerce',
        'referrer'         => 'Referrer',
        'landing_parse'    => 'Landing Page',
        'direct'           => 'Direct',
        'facebook'         => 'Facebook',
        'google'           => 'Google Ads',
        'gsc'              => 'Search Console',
        'real'             => 'Real (Nexstage)',
    ];

    // ─── Metric → input map ──────────────────────────────────────────────────

    /**
     * Maps a computed metric name to the list of inputs it depends on.
     * Used by resolveMetric() to build the multi-input source map.
     */
    private const METRIC_INPUTS = [
        'cvr'  => ['sessions', 'purchases'],
        'aov'  => ['revenue',  'purchases'],
        'roas' => ['revenue',  'ad_spend'],
        'cac'  => ['ad_spend', 'purchases'],
        'mer'  => ['revenue',  'ad_spend'],
    ];

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Returns the daily_snapshots column name to SELECT for a given metric and
     * source lens.
     *
     * Currently only 'revenue' is fully mapped (all 5 data services need it).
     * The method is intentionally open for extension: pass a different $metric
     * key once we add column maps for other metrics (e.g. 'sessions', 'orders').
     *
     * Unknown metric + source combinations fall back to the 'real' revenue column
     * so callers never receive an empty string that could corrupt a raw SQL query.
     *
     * Examples:
     *   columnFor('revenue', 'facebook')  → 'revenue_facebook_attributed'
     *   columnFor('revenue', 'gsc')       → 'revenue_gsc_attributed'
     *   columnFor('revenue', 'real')      → 'revenue_real_attributed'
     *   columnFor('revenue', 'ga4')       → 'revenue_real_attributed'  (fallback)
     *   columnFor('revenue', 'store')     → 'revenue'
     *
     * @param  string $metric  Metric key — currently only 'revenue' is mapped.
     * @param  string $source  Source lens slug: real|store|facebook|google|gsc|ga4.
     * @return string          Column name safe to interpolate into a trusted SQL query.
     */
    public function columnFor(string $metric, string $source): string
    {
        return match ($metric) {
            'revenue' => self::REVENUE_COLUMN[$source] ?? self::REVENUE_COLUMN['real'],
            default   => self::REVENUE_COLUMN['real'],
        };
    }

    /**
     * Returns the winning source slug for a given metric input, for a workspace.
     *
     * @param  string   $input    One of: purchases, revenue, sessions,
     *                            first_touch_source, last_touch_source,
     *                            ad_spend, search_clicks, platform_claimed_revenue
     * @param  int|null $storeId  When provided, sessions check is scoped to this store.
     * @return string|null        Source slug, or null when no source is available.
     */
    public function resolveInput(int $workspaceId, string $input, ?int $storeId = null): ?string
    {
        $flags = $this->flags($workspaceId);

        return match ($input) {
            'purchases', 'revenue', 'new_customers' => 'store',

            'sessions' => $this->resolveSessionsSource($flags, $storeId),

            // First-touch: Shopify journey has the richest data when available.
            // GA4 first_user_source is the next best thing. PYS (PixelYourSite)
            // covers WC stores that have installed the plugin. WC native falls
            // back to UTM columns promoted during order import. Referrer heuristic
            // is the last resort (parse from orders.referrer_url).
            'first_touch_source' => match (true) {
                $flags['store_platform'] === 'shopify' => 'shopify_journey',
                $flags['has_ga4']                      => 'ga4',
                $flags['store_platform'] === 'woocommerce' => 'wc_native',
                default                                => 'referrer',
            },

            // Last-touch: Shopify journey → GA4 session source → PYS last-click →
            // landing page parse → direct (all-else fallback).
            'last_touch_source' => match (true) {
                $flags['store_platform'] === 'shopify' => 'shopify_journey',
                $flags['has_ga4']                      => 'ga4',
                $flags['store_platform'] === 'woocommerce' => 'wc_native',
                default                                => 'direct',
            },

            'ad_spend', 'platform_claimed_revenue' => $this->resolveAdSource($flags),

            'search_clicks' => $flags['has_gsc'] ? 'gsc' : null,

            default => null,
        };
    }

    /**
     * Returns a multi-input source map for a computed metric.
     *
     * Example: resolveMetric($id, 'cvr')
     *   => ['sessions' => 'ga4', 'purchases' => 'store']
     *
     * @return array<string, string|null>
     */
    public function resolveMetric(int $workspaceId, string $metric, ?int $storeId = null): array
    {
        $inputs = self::METRIC_INPUTS[$metric] ?? [];

        $map = [];
        foreach ($inputs as $input) {
            $map[$input] = $this->resolveInput($workspaceId, $input, $storeId);
        }
        return $map;
    }

    /**
     * Returns a human-readable badge label for a source slug.
     * Falls back to ucfirst(slug) for any slug not in the label map.
     */
    public function sourceLabel(string $source): string
    {
        return self::SOURCE_LABELS[$source] ?? ucfirst($source);
    }

    /**
     * Flat prop bag for HandleInertiaRequests shared props.
     *
     * Returns a flat array of resolved sources + labels for all v1 inputs,
     * plus availability flags used by SourceBadge disabled state logic.
     *
     * Shape:
     *   sessions           → 'shopify'|'ga4'|null
     *   sessions_label     → 'Shopify Analytics'|'GA4'|null
     *   first_touch        → 'shopify_journey'|'ga4'|'wc_native'|'referrer'|null
     *   first_touch_label  → 'Shopify'|'GA4'|…
     *   last_touch         → same ladder
     *   last_touch_label   → …
     *   ad_spend           → 'facebook'|'google'|null
     *   ad_spend_label     → 'Facebook'|'Google Ads'|null
     *   search_clicks      → 'gsc'|null
     *   search_clicks_label→ 'Search Console'|null
     *   has_ga4            → bool  (used to mute/enable the GA4 badge)
     *   has_gsc            → bool
     *   has_facebook       → bool
     *   has_google         → bool
     *
     * @return array<string, mixed>
     */
    public function forWorkspace(int $workspaceId): array
    {
        $flags = $this->flags($workspaceId);

        $inputs = [
            'sessions'              => $this->resolveInput($workspaceId, 'sessions'),
            'first_touch'           => $this->resolveInput($workspaceId, 'first_touch_source'),
            'last_touch'            => $this->resolveInput($workspaceId, 'last_touch_source'),
            'ad_spend'              => $this->resolveInput($workspaceId, 'ad_spend'),
            'search_clicks'         => $this->resolveInput($workspaceId, 'search_clicks'),
            'platform_claimed_revenue' => $this->resolveInput($workspaceId, 'platform_claimed_revenue'),
        ];

        $out = [];
        foreach ($inputs as $key => $source) {
            $out[$key]          = $source;
            $out[$key . '_label'] = $source !== null ? $this->sourceLabel($source) : null;
        }

        // Availability flags — consumed by SourceBadge disabled state logic.
        $out['has_ga4']      = $flags['has_ga4'];
        $out['has_gsc']      = $flags['has_gsc'];
        $out['has_facebook'] = $flags['has_facebook'];
        $out['has_google']   = $flags['has_google'];

        return $out;
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Workspace integration flags, memoised per workspace per request.
     *
     * @return array{
     *   has_ga4: bool,
     *   has_gsc: bool,
     *   has_facebook: bool,
     *   has_google: bool,
     *   has_shopify_sessions: bool,
     *   store_platform: string|null
     * }
     */
    private function flags(int $workspaceId): array
    {
        if (isset($this->memo[$workspaceId])) {
            return $this->memo[$workspaceId];
        }

        // GA4 — at least one active property present.
        $hasGa4 = Ga4Property::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('status', '!=', 'inactive')
            ->exists();

        // GSC — at least one active property present.
        $hasGsc = SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('status', '!=', 'inactive')
            ->exists();

        // Ad accounts by platform.
        $platforms = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('status', '!=', 'inactive')
            ->distinct()
            ->pluck('platform')
            ->all();

        $hasFacebook = in_array('facebook', $platforms, true);
        $hasGoogle   = in_array('google',   $platforms, true);

        // Store platform (first connected store wins; Shopify takes precedence
        // since it provides the richest session + journey data).
        // Note: stores table has no soft-deletes column.
        $storePlatform = DB::table('stores')
            ->where('workspace_id', $workspaceId)
            ->orderByRaw("CASE WHEN platform = 'shopify' THEN 0 ELSE 1 END")
            ->value('platform');

        // Shopify daily sessions — WS-shop agent adds this table.
        // Guard with table_exists so it doesn't blow up if the migration hasn't run.
        $hasShopifySessions = false;
        if ($storePlatform === 'shopify' && $this->tableExists('shopify_daily_sessions')) {
            $hasShopifySessions = DB::table('shopify_daily_sessions')
                ->where('workspace_id', $workspaceId)
                ->exists();
        }

        $this->memo[$workspaceId] = [
            'has_ga4'              => $hasGa4,
            'has_gsc'              => $hasGsc,
            'has_facebook'         => $hasFacebook,
            'has_google'           => $hasGoogle,
            'has_shopify_sessions' => $hasShopifySessions,
            'store_platform'       => $storePlatform,
        ];

        return $this->memo[$workspaceId];
    }

    /**
     * Resolve the sessions source for the given workspace flags.
     *
     * Ladder: Shopify Analytics (shopify_daily_sessions) → GA4 → null
     *
     * If the WS-shop agent has not yet landed (table absent or empty), falls
     * through to GA4. If GA4 is also absent, returns null.
     *
     * @param  array<string, mixed> $flags
     */
    private function resolveSessionsSource(array $flags, ?int $storeId): ?string
    {
        if ($flags['has_shopify_sessions']) {
            // TODO: when storeId is provided, check shopify_daily_sessions scoped
            // to that store_id (WS-shop integration adds per-store granularity).
            return 'shopify';
        }

        if ($flags['has_ga4']) {
            return 'ga4';
        }

        return null;
    }

    /**
     * Resolve ad source — Facebook takes precedence; returns null when neither
     * platform has an active ad account.
     *
     * @param  array<string, mixed> $flags
     */
    private function resolveAdSource(array $flags): ?string
    {
        if ($flags['has_facebook']) {
            return 'facebook';
        }

        if ($flags['has_google']) {
            return 'google';
        }

        return null;
    }

    /**
     * Lightweight table existence check — avoids crashing when parallel agents
     * haven't run their migrations yet (WS-F ga4_order_attribution, WS-shop
     * shopify_daily_sessions). Postgres information_schema is cheap for this.
     */
    private function tableExists(string $table): bool
    {
        return (bool) DB::selectOne(
            "SELECT 1 FROM information_schema.tables
             WHERE table_schema = 'public' AND table_name = ?",
            [$table],
        );
    }
}
