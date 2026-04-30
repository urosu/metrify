<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RemoveAdAccountAction;
use App\Actions\RemoveGscPropertyAction;
use App\Actions\RemoveStoreAction;
use App\Actions\StartHistoricalImportAction;
use App\Jobs\AdHistoricalImportJob;
use App\Jobs\GscHistoricalImportJob;
use App\Jobs\SyncAdInsightsJob;
use App\Jobs\SyncSearchConsoleJob;
use App\Jobs\SyncShopifyOrdersJob;
use App\Jobs\SyncStoreOrdersJob;
use App\Jobs\WooCommerceHistoricalImportJob;
use App\Models\AdAccount;
use App\Models\SearchConsoleProperty;
use App\Models\Store;
use App\Models\SyncLog;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\Integrations\SearchConsole\GscPropertyFormatter;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsController extends Controller
{
    /**
     * /integrations — top-level Tracking Health dashboard.
     *
     * Serves mock data until the real aggregation layer lands (see docs/planning/backend.md
     * §IntegrationsController). Six sources: store, facebook, google, gsc, ga4, real.
     *
     * Shape IS the final contract — no DB changes needed to ship v1.
     * Real aggregation wires into daily_snapshots / integration_runs once those tables
     * carry per-source accuracy rows.
     *
     * Sync feed: 50 events (Vercel deployment list pattern — enough to diagnose a pattern
     * without overwhelming). Row click → DrawerSidePanel with raw JSON payload.
     * GA4 card: labeled "GA4" and shows "Connect GA4" CTA — first-class source per memory.
     *
     * @see docs/pages/integrations.md
     * @see docs/competitors/elevar.md#key-screens Channel Accuracy Report
     * @see docs/competitors/_research_integrations_page.md
     */
    public function index(Request $request): Response
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        // 30-day sparkline helper — realistic health trend 72–100
        $healthSparkline = [82,84,81,85,87,88,86,89,90,88,87,85,86,88,89,91,90,88,87,86,87,88,89,90,89,88,87,86,87,87];

        $syncSparkline = fn(int $base) => array_map(
            fn($_) => max(0, $base + random_int(-8, 8)),
            range(0, 29),
        );

        // 50-event sync feed (Vercel deployment-list analog).
        // Each event carries a minimal payload for the Elevar-style payload inspector drawer.
        $syncEvents = [
            // ── Most-recent 15 ────────────────────────────────────────────────
            ['id' => 1,  'ts' => now()->subMinutes(3)->toISOString(),  'integration' => 'shopify',  'action' => 'orders.poll',      'records' => 14,  'errors' => 0, 'duration_ms' => 842,  'status' => 'success', 'payload' => ['event' => 'orders.poll', 'source' => 'shopify', 'records_written' => 14, 'errors' => []]],
            ['id' => 2,  'ts' => now()->subMinutes(18)->toISOString(), 'integration' => 'google',   'action' => 'ad_insights.sync', 'records' => 220, 'errors' => 0, 'duration_ms' => 1240, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'google', 'records_written' => 220, 'date_range' => '2026-04-29', 'errors' => []]],
            ['id' => 3,  'ts' => now()->subMinutes(47)->toISOString(), 'integration' => 'facebook', 'action' => 'ad_insights.sync', 'records' => 185, 'errors' => 1, 'duration_ms' => 2100, 'status' => 'partial', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'facebook', 'records_written' => 185, 'errors' => [['code' => '#100', 'message' => 'Missing user_data.em']]]],
            ['id' => 4,  'ts' => now()->subMinutes(62)->toISOString(), 'integration' => 'shopify',  'action' => 'orders.poll',      'records' => 8,   'errors' => 0, 'duration_ms' => 710,  'status' => 'success', 'payload' => ['event' => 'orders.poll', 'source' => 'shopify', 'records_written' => 8,   'errors' => []]],
            ['id' => 5,  'ts' => now()->subHours(2)->toISOString(),    'integration' => 'google',   'action' => 'ad_insights.sync', 'records' => 190, 'errors' => 0, 'duration_ms' => 980,  'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'google', 'records_written' => 190, 'errors' => []]],
            ['id' => 6,  'ts' => now()->subHours(3)->toISOString(),    'integration' => 'facebook', 'action' => 'capi.delivery',    'records' => 42,  'errors' => 2, 'duration_ms' => 580,  'status' => 'partial', 'payload' => ['event' => 'capi.delivery', 'source' => 'facebook', 'records_written' => 42, 'errors' => [['code' => '#100', 'event' => 'purchase'], ['code' => '#200', 'event' => 'add_to_cart']]]],
            ['id' => 7,  'ts' => now()->subHours(4)->toISOString(),    'integration' => 'shopify',  'action' => 'refunds.poll',     'records' => 3,   'errors' => 0, 'duration_ms' => 320,  'status' => 'success', 'payload' => ['event' => 'refunds.poll', 'source' => 'shopify', 'records_written' => 3, 'errors' => []]],
            ['id' => 8,  'ts' => now()->subHours(5)->toISOString(),    'integration' => 'gsc',      'action' => 'queries.sync',     'records' => 840, 'errors' => 0, 'duration_ms' => 4200, 'status' => 'success', 'payload' => ['event' => 'queries.sync', 'source' => 'gsc', 'date_range' => '2026-04-27', 'records_written' => 840, 'errors' => []]],
            ['id' => 9,  'ts' => now()->subHours(6)->toISOString(),    'integration' => 'ga4',      'action' => 'events.poll',      'records' => 0,   'errors' => 1, 'duration_ms' => 120,  'status' => 'error',   'payload' => ['event' => 'events.poll', 'source' => 'ga4', 'records_written' => 0, 'errors' => [['code' => 'DATA_NOT_FOUND', 'message' => 'No events received for property']]]],
            ['id' => 10, 'ts' => now()->subHours(7)->toISOString(),    'integration' => 'shopify',  'action' => 'orders.poll',      'records' => 22,  'errors' => 0, 'duration_ms' => 920,  'status' => 'success', 'payload' => ['event' => 'orders.poll', 'source' => 'shopify', 'records_written' => 22, 'errors' => []]],
            ['id' => 11, 'ts' => now()->subHours(8)->toISOString(),    'integration' => 'google',   'action' => 'ad_insights.sync', 'records' => 200, 'errors' => 0, 'duration_ms' => 1100, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'google', 'records_written' => 200, 'errors' => []]],
            ['id' => 12, 'ts' => now()->subHours(9)->toISOString(),    'integration' => 'facebook', 'action' => 'ad_insights.sync', 'records' => 178, 'errors' => 0, 'duration_ms' => 1900, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'facebook', 'records_written' => 178, 'errors' => []]],
            ['id' => 13, 'ts' => now()->subHours(10)->toISOString(),   'integration' => 'shopify',  'action' => 'orders.webhook',   'records' => 1,   'errors' => 0, 'duration_ms' => 45,   'status' => 'success', 'payload' => ['event' => 'orders.webhook', 'source' => 'shopify', 'order_id' => 'SHP-10421', 'errors' => []]],
            ['id' => 14, 'ts' => now()->subHours(11)->toISOString(),   'integration' => 'gsc',      'action' => 'pages.sync',       'records' => 210, 'errors' => 0, 'duration_ms' => 3100, 'status' => 'success', 'payload' => ['event' => 'pages.sync', 'source' => 'gsc', 'records_written' => 210, 'errors' => []]],
            ['id' => 15, 'ts' => now()->subHours(12)->toISOString(),   'integration' => 'ga4',      'action' => 'events.poll',      'records' => 0,   'errors' => 1, 'duration_ms' => 100,  'status' => 'error',   'payload' => ['event' => 'events.poll', 'source' => 'ga4', 'records_written' => 0, 'errors' => [['code' => 'DATA_NOT_FOUND']]]],
            // ── Older 35 ─────────────────────────────────────────────────────
            ['id' => 16, 'ts' => now()->subHours(13)->toISOString(),   'integration' => 'shopify',  'action' => 'orders.poll',      'records' => 31,  'errors' => 0, 'duration_ms' => 860,  'status' => 'success', 'payload' => ['event' => 'orders.poll', 'source' => 'shopify', 'records_written' => 31]],
            ['id' => 17, 'ts' => now()->subHours(14)->toISOString(),   'integration' => 'google',   'action' => 'ad_insights.sync', 'records' => 215, 'errors' => 0, 'duration_ms' => 1050, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'google', 'records_written' => 215]],
            ['id' => 18, 'ts' => now()->subHours(15)->toISOString(),   'integration' => 'facebook', 'action' => 'ad_insights.sync', 'records' => 162, 'errors' => 0, 'duration_ms' => 1780, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'facebook', 'records_written' => 162]],
            ['id' => 19, 'ts' => now()->subHours(16)->toISOString(),   'integration' => 'shopify',  'action' => 'orders.poll',      'records' => 19,  'errors' => 0, 'duration_ms' => 730,  'status' => 'success', 'payload' => ['event' => 'orders.poll', 'source' => 'shopify', 'records_written' => 19]],
            ['id' => 20, 'ts' => now()->subHours(17)->toISOString(),   'integration' => 'ga4',      'action' => 'events.poll',      'records' => 0,   'errors' => 1, 'duration_ms' => 105,  'status' => 'error',   'payload' => ['event' => 'events.poll', 'source' => 'ga4', 'records_written' => 0, 'errors' => [['code' => 'DATA_NOT_FOUND']]]],
            ['id' => 21, 'ts' => now()->subHours(18)->toISOString(),   'integration' => 'gsc',      'action' => 'queries.sync',     'records' => 780, 'errors' => 0, 'duration_ms' => 3900, 'status' => 'success', 'payload' => ['event' => 'queries.sync', 'source' => 'gsc', 'records_written' => 780]],
            ['id' => 22, 'ts' => now()->subHours(19)->toISOString(),   'integration' => 'shopify',  'action' => 'orders.poll',      'records' => 11,  'errors' => 0, 'duration_ms' => 680,  'status' => 'success', 'payload' => ['event' => 'orders.poll', 'source' => 'shopify', 'records_written' => 11]],
            ['id' => 23, 'ts' => now()->subHours(20)->toISOString(),   'integration' => 'facebook', 'action' => 'capi.delivery',    'records' => 38,  'errors' => 1, 'duration_ms' => 540,  'status' => 'partial', 'payload' => ['event' => 'capi.delivery', 'source' => 'facebook', 'records_written' => 38, 'errors' => [['code' => '#100', 'event' => 'purchase']]]],
            ['id' => 24, 'ts' => now()->subHours(21)->toISOString(),   'integration' => 'google',   'action' => 'ad_insights.sync', 'records' => 198, 'errors' => 0, 'duration_ms' => 1020, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'google', 'records_written' => 198]],
            ['id' => 25, 'ts' => now()->subHours(22)->toISOString(),   'integration' => 'shopify',  'action' => 'refunds.poll',     'records' => 1,   'errors' => 0, 'duration_ms' => 290,  'status' => 'success', 'payload' => ['event' => 'refunds.poll', 'source' => 'shopify', 'records_written' => 1]],
            ['id' => 26, 'ts' => now()->subHours(23)->toISOString(),   'integration' => 'ga4',      'action' => 'events.poll',      'records' => 0,   'errors' => 1, 'duration_ms' => 95,   'status' => 'error',   'payload' => ['event' => 'events.poll', 'source' => 'ga4', 'records_written' => 0]],
            ['id' => 27, 'ts' => now()->subHours(24)->toISOString(),   'integration' => 'gsc',      'action' => 'pages.sync',       'records' => 195, 'errors' => 0, 'duration_ms' => 2900, 'status' => 'success', 'payload' => ['event' => 'pages.sync', 'source' => 'gsc', 'records_written' => 195]],
            ['id' => 28, 'ts' => now()->subHours(25)->toISOString(),   'integration' => 'shopify',  'action' => 'orders.poll',      'records' => 27,  'errors' => 0, 'duration_ms' => 810,  'status' => 'success', 'payload' => ['event' => 'orders.poll', 'source' => 'shopify', 'records_written' => 27]],
            ['id' => 29, 'ts' => now()->subHours(26)->toISOString(),   'integration' => 'google',   'action' => 'ad_insights.sync', 'records' => 204, 'errors' => 0, 'duration_ms' => 1080, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'google', 'records_written' => 204]],
            ['id' => 30, 'ts' => now()->subHours(27)->toISOString(),   'integration' => 'facebook', 'action' => 'ad_insights.sync', 'records' => 170, 'errors' => 0, 'duration_ms' => 1850, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'facebook', 'records_written' => 170]],
            ['id' => 31, 'ts' => now()->subHours(28)->toISOString(),   'integration' => 'shopify',  'action' => 'orders.webhook',   'records' => 2,   'errors' => 0, 'duration_ms' => 50,   'status' => 'success', 'payload' => ['event' => 'orders.webhook', 'source' => 'shopify', 'records_written' => 2]],
            ['id' => 32, 'ts' => now()->subHours(29)->toISOString(),   'integration' => 'ga4',      'action' => 'events.poll',      'records' => 0,   'errors' => 1, 'duration_ms' => 110,  'status' => 'error',   'payload' => ['event' => 'events.poll', 'source' => 'ga4', 'records_written' => 0]],
            ['id' => 33, 'ts' => now()->subHours(30)->toISOString(),   'integration' => 'shopify',  'action' => 'orders.poll',      'records' => 15,  'errors' => 0, 'duration_ms' => 770,  'status' => 'success', 'payload' => ['event' => 'orders.poll', 'source' => 'shopify', 'records_written' => 15]],
            ['id' => 34, 'ts' => now()->subHours(31)->toISOString(),   'integration' => 'gsc',      'action' => 'queries.sync',     'records' => 810, 'errors' => 0, 'duration_ms' => 4050, 'status' => 'success', 'payload' => ['event' => 'queries.sync', 'source' => 'gsc', 'records_written' => 810]],
            ['id' => 35, 'ts' => now()->subHours(32)->toISOString(),   'integration' => 'google',   'action' => 'ad_insights.sync', 'records' => 212, 'errors' => 0, 'duration_ms' => 1120, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'google', 'records_written' => 212]],
            ['id' => 36, 'ts' => now()->subHours(33)->toISOString(),   'integration' => 'facebook', 'action' => 'capi.delivery',    'records' => 45,  'errors' => 1, 'duration_ms' => 600,  'status' => 'partial', 'payload' => ['event' => 'capi.delivery', 'source' => 'facebook', 'records_written' => 45]],
            ['id' => 37, 'ts' => now()->subHours(34)->toISOString(),   'integration' => 'shopify',  'action' => 'orders.poll',      'records' => 9,   'errors' => 0, 'duration_ms' => 690,  'status' => 'success', 'payload' => ['event' => 'orders.poll', 'source' => 'shopify', 'records_written' => 9]],
            ['id' => 38, 'ts' => now()->subHours(35)->toISOString(),   'integration' => 'ga4',      'action' => 'events.poll',      'records' => 0,   'errors' => 1, 'duration_ms' => 98,   'status' => 'error',   'payload' => ['event' => 'events.poll', 'source' => 'ga4', 'records_written' => 0]],
            ['id' => 39, 'ts' => now()->subHours(36)->toISOString(),   'integration' => 'shopify',  'action' => 'refunds.poll',     'records' => 2,   'errors' => 0, 'duration_ms' => 310,  'status' => 'success', 'payload' => ['event' => 'refunds.poll', 'source' => 'shopify', 'records_written' => 2]],
            ['id' => 40, 'ts' => now()->subHours(37)->toISOString(),   'integration' => 'google',   'action' => 'ad_insights.sync', 'records' => 208, 'errors' => 0, 'duration_ms' => 1010, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'google', 'records_written' => 208]],
            ['id' => 41, 'ts' => now()->subHours(38)->toISOString(),   'integration' => 'gsc',      'action' => 'pages.sync',       'records' => 220, 'errors' => 0, 'duration_ms' => 3200, 'status' => 'success', 'payload' => ['event' => 'pages.sync', 'source' => 'gsc', 'records_written' => 220]],
            ['id' => 42, 'ts' => now()->subHours(39)->toISOString(),   'integration' => 'shopify',  'action' => 'orders.poll',      'records' => 24,  'errors' => 0, 'duration_ms' => 830,  'status' => 'success', 'payload' => ['event' => 'orders.poll', 'source' => 'shopify', 'records_written' => 24]],
            ['id' => 43, 'ts' => now()->subHours(40)->toISOString(),   'integration' => 'facebook', 'action' => 'ad_insights.sync', 'records' => 172, 'errors' => 0, 'duration_ms' => 1820, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'facebook', 'records_written' => 172]],
            ['id' => 44, 'ts' => now()->subHours(41)->toISOString(),   'integration' => 'ga4',      'action' => 'events.poll',      'records' => 0,   'errors' => 1, 'duration_ms' => 115,  'status' => 'error',   'payload' => ['event' => 'events.poll', 'source' => 'ga4', 'records_written' => 0]],
            ['id' => 45, 'ts' => now()->subHours(42)->toISOString(),   'integration' => 'shopify',  'action' => 'orders.webhook',   'records' => 1,   'errors' => 0, 'duration_ms' => 40,   'status' => 'success', 'payload' => ['event' => 'orders.webhook', 'source' => 'shopify', 'records_written' => 1]],
            ['id' => 46, 'ts' => now()->subHours(43)->toISOString(),   'integration' => 'google',   'action' => 'ad_insights.sync', 'records' => 196, 'errors' => 0, 'duration_ms' => 1030, 'status' => 'success', 'payload' => ['event' => 'ad_insights.sync', 'source' => 'google', 'records_written' => 196]],
            ['id' => 47, 'ts' => now()->subHours(44)->toISOString(),   'integration' => 'gsc',      'action' => 'queries.sync',     'records' => 820, 'errors' => 0, 'duration_ms' => 4100, 'status' => 'success', 'payload' => ['event' => 'queries.sync', 'source' => 'gsc', 'records_written' => 820]],
            ['id' => 48, 'ts' => now()->subHours(45)->toISOString(),   'integration' => 'shopify',  'action' => 'orders.poll',      'records' => 18,  'errors' => 0, 'duration_ms' => 750,  'status' => 'success', 'payload' => ['event' => 'orders.poll', 'source' => 'shopify', 'records_written' => 18]],
            ['id' => 49, 'ts' => now()->subHours(46)->toISOString(),   'integration' => 'facebook', 'action' => 'capi.delivery',    'records' => 40,  'errors' => 0, 'duration_ms' => 560,  'status' => 'success', 'payload' => ['event' => 'capi.delivery', 'source' => 'facebook', 'records_written' => 40]],
            ['id' => 50, 'ts' => now()->subHours(47)->toISOString(),   'integration' => 'shopify',  'action' => 'refunds.poll',     'records' => 4,   'errors' => 0, 'duration_ms' => 340,  'status' => 'success', 'payload' => ['event' => 'refunds.poll', 'source' => 'shopify', 'records_written' => 4]],
        ];

        return Inertia::render('Integrations/Index', [
            'active_tab' => $request->query('tab', 'connected'),

            // ── Tracking Health gauge (Elevar Channel Accuracy Report pattern) ─
            'tracking_health' => [
                'score'         => 87,
                'grade'         => 'B+',
                'sparkline_30d' => $healthSparkline,
                'breakdown'     => [
                    ['name' => 'Order attribution coverage', 'score' => 92, 'weight' => 30],
                    ['name' => 'Ad conversion data',         'score' => 85, 'weight' => 25],
                    ['name' => 'UTM coverage',               'score' => 78, 'weight' => 20],
                    ['name' => 'Sync recency',               'score' => 95, 'weight' => 15],
                    ['name' => 'Tag coverage',               'score' => 80, 'weight' => 10],
                ],
            ],

            // ── KPI strip ─────────────────────────────────────────────────────
            'summary' => [
                'connected_count'   => 5,
                'total_count'       => 6,
                'last_sync_at'      => now()->subMinutes(3)->toISOString(),
                'sync_errors_24h'   => 2,
                'orders_attributed' => 91.4,
                'tag_coverage'      => 80.0,
            ],

            // ── Six integration cards (all six sources, GA4 = first-class) ────
            'integrations' => [
                [
                    'id'                => 'shopify_store_1',
                    'type'              => 'shopify',
                    'source_token'      => 'store',
                    'name'              => 'Shopify — main-store.myshopify.com',
                    'status'            => 'healthy',
                    'connected_at'      => '2025-12-08T09:00:00Z',
                    'last_sync_at'      => now()->subMinutes(3)->toISOString(),
                    'token_expires_at'  => null,
                    'sync_sparkline_30d'=> $syncSparkline(40),
                    'health_checks'     => [
                        ['label' => 'API token valid',       'pass' => true],
                        ['label' => 'Order webhook firing',  'pass' => true],
                        ['label' => 'Refund webhook firing', 'pass' => true],
                        ['label' => 'Inventory sync',        'pass' => true],
                    ],
                    'account_info'  => 'Shopify Plus · USD · 4,200 orders/mo',
                    'oauth_scope'   => 'read_orders, read_products, read_inventory, write_fulfillments',
                ],
                [
                    'id'                => 'facebook_ads',
                    'type'              => 'facebook',
                    'source_token'      => 'facebook',
                    'name'              => 'Facebook Ads — Acme LLC',
                    'status'            => 'warning',
                    'connected_at'      => '2026-01-12T14:30:00Z',
                    'last_sync_at'      => now()->subMinutes(47)->toISOString(),
                    'token_expires_at'  => now()->addDays(5)->toISOString(),
                    'sync_sparkline_30d'=> $syncSparkline(55),
                    'health_checks'     => [
                        ['label' => 'Pixel firing',            'pass' => true],
                        ['label' => 'Conversions API sending', 'pass' => true],
                        ['label' => 'Server-side match rate',  'pass' => false, 'note' => '62% — recommend ≥80%'],
                        ['label' => 'Access token valid',      'pass' => true,  'note' => 'Expires in 5 days'],
                    ],
                    'account_info'  => 'Ad Account #ACT_29481…744 · USD',
                    'oauth_scope'   => 'ads_management, ads_read, business_management',
                ],
                [
                    'id'                => 'google_ads',
                    'type'              => 'google',
                    'source_token'      => 'google',
                    'name'              => 'Google Ads — Acme Brand',
                    'status'            => 'healthy',
                    'connected_at'      => '2026-01-15T11:20:00Z',
                    'last_sync_at'      => now()->subMinutes(18)->toISOString(),
                    'token_expires_at'  => null,
                    'sync_sparkline_30d'=> $syncSparkline(48),
                    'health_checks'     => [
                        ['label' => 'OAuth token valid',           'pass' => true],
                        ['label' => 'Enhanced Conversions active', 'pass' => true],
                        ['label' => 'Conversion tag firing',       'pass' => true],
                        ['label' => 'Customer match rate',         'pass' => true, 'note' => '88%'],
                    ],
                    'account_info'  => 'Customer #123-456-7890 · EUR',
                    'oauth_scope'   => 'adwords',
                ],
                [
                    'id'                => 'gsc',
                    'type'              => 'gsc',
                    'source_token'      => 'gsc',
                    'name'              => 'Google Search Console — acme.com',
                    'status'            => 'warning',
                    'connected_at'      => '2026-02-01T08:00:00Z',
                    'last_sync_at'      => now()->subHours(26)->toISOString(),
                    'token_expires_at'  => null,
                    'sync_sparkline_30d'=> $syncSparkline(30),
                    'health_checks'     => [
                        ['label' => 'Property verified',    'pass' => true],
                        ['label' => 'Data delivery',        'pass' => true, 'note' => 'GSC-native 48h lag — not an error'],
                        ['label' => 'Search type: web',     'pass' => true],
                        ['label' => 'Impressions > 0 (7d)', 'pass' => true],
                    ],
                    'account_info'  => 'sc-domain:acme.com · verified',
                    'oauth_scope'   => 'webmasters.readonly',
                ],
                [
                    // GA4 = first-class source, labeled "GA4" not "Site" or "Google Analytics".
                    // Connect CTA = "Connect GA4" (per memory: GA4 is a first-class source).
                    // sibling agent (3B-6) owns the OAuth controller; this card wires the UI.
                    'id'                => 'ga4',
                    'type'              => 'ga4',
                    'source_token'      => 'ga4',
                    'name'              => 'GA4 — Acme Store',
                    'status'            => 'error',
                    'connected_at'      => '2026-01-20T16:45:00Z',
                    'last_sync_at'      => now()->subHours(28)->toISOString(),
                    'token_expires_at'  => null,
                    'sync_sparkline_30d'=> $syncSparkline(20),
                    'health_checks'     => [
                        ['label' => 'Property accessible',   'pass' => false, 'note' => 'No events received in 28h'],
                        ['label' => 'purchase event firing', 'pass' => false, 'note' => '0 events in last 24h'],
                        ['label' => 'session_start event',   'pass' => true],
                        ['label' => 'Data stream active',    'pass' => true],
                    ],
                    'account_info'  => 'Property 311…984 · G-XXXXXXX',
                    'oauth_scope'   => 'analytics.readonly',
                ],
                [
                    'id'                => 'woocommerce',
                    'type'              => 'woocommerce',
                    'source_token'      => 'store',
                    'name'              => 'WooCommerce',
                    'status'            => 'not_connected',
                    'connected_at'      => null,
                    'last_sync_at'      => null,
                    'token_expires_at'  => null,
                    'sync_sparkline_30d'=> [],
                    'health_checks'     => [],
                    'account_info'      => null,
                    'oauth_scope'       => null,
                ],
            ],

            // ── Sync activity feed (50 events — Vercel deployment list pattern) ─
            'sync_events' => $syncEvents,

            // ── Missing data warnings (critical → warning → info sorted in component) ─
            'missing_data_warnings' => [
                ['severity' => 'critical', 'message' => 'GA4: no purchase events received in 28 hours — tag may have stopped firing.',                                    'action_label' => 'View details', 'action_type' => 'details',   'integration_id' => 'ga4'],
                ['severity' => 'warning',  'message' => 'Facebook Ads: access token expires in 5 days — reconnect to avoid data gaps.',                                  'action_label' => 'Reconnect',    'action_type' => 'reconnect', 'integration_id' => 'facebook_ads'],
                ['severity' => 'warning',  'message' => 'Facebook CAPI match rate is 62% — below the recommended 80%. Check customer email hashing.',                    'action_label' => 'Learn more',   'action_type' => 'docs',      'integration_id' => 'facebook_ads'],
                ['severity' => 'info',     'message' => 'GSC data is delayed 48h by Google — last available data: 2026-04-27. This is normal and not a sync failure.',  'action_label' => null,           'action_type' => null,         'integration_id' => 'gsc'],
            ],

            // ── Tracking Health tab — Elevar Channel Accuracy Report ───────────
            'accuracy' => [
                'facebook' => 85.2,
                'google'   => 97.8,
                'gsc'      => 100.0,
                'ga4'      => null,
            ],

            // ── Error code directory (Elevar pattern with sample payloads) ─────
            'error_codes' => [
                [
                    'id'          => 1,
                    'code'        => '#100',
                    'destination' => 'facebook',
                    'event'       => 'purchase',
                    'first_seen'  => now()->subDays(3)->toISOString(),
                    'last_seen'   => now()->subHours(3)->toISOString(),
                    'count'       => 28,
                    'explanation' => 'Missing user_data.em — customer email hash did not reach CAPI. Verify checkout extensibility installs the pixel on thank-you page.',
                    'sample_payloads' => [
                        ['error_code' => '#100', 'event_name' => 'purchase', 'user_data' => ['em' => null, 'ph' => 'abc123'], 'custom_data' => ['value' => 89.99, 'currency' => 'USD'], 'event_id' => 'SHP-10421-purchase'],
                        ['error_code' => '#100', 'event_name' => 'purchase', 'user_data' => ['em' => null, 'ph' => null],     'custom_data' => ['value' => 45.00, 'currency' => 'USD'], 'event_id' => 'SHP-10438-purchase'],
                    ],
                ],
                [
                    'id'          => 2,
                    'code'        => '#200',
                    'destination' => 'facebook',
                    'event'       => 'add_to_cart',
                    'first_seen'  => now()->subDays(7)->toISOString(),
                    'last_seen'   => now()->subHours(6)->toISOString(),
                    'count'       => 14,
                    'explanation' => 'Duplicate event_id — server-side and browser events are double-firing. Check deduplication key setup in Conversions API.',
                    'sample_payloads' => [
                        ['error_code' => '#200', 'event_name' => 'add_to_cart', 'event_id' => 'ATC-dupe-001', 'message' => 'Duplicate event received'],
                    ],
                ],
                [
                    'id'          => 3,
                    'code'        => 'DATA_NOT_FOUND',
                    'destination' => 'ga4',
                    'event'       => 'purchase',
                    'first_seen'  => now()->subDays(2)->toISOString(),
                    'last_seen'   => now()->subHours(1)->toISOString(),
                    'count'       => 89,
                    'explanation' => 'GA4 Measurement Protocol returned DATA_NOT_FOUND. The purchase event is not reaching the property — tag likely broken after recent theme update.',
                    'sample_payloads' => [
                        ['error_code' => 'DATA_NOT_FOUND', 'measurement_id' => 'G-XXXXXXX', 'event_name' => 'purchase', 'response' => ['error' => ['code' => 404, 'message' => 'DATA_NOT_FOUND']]],
                    ],
                ],
            ],

            // ── Channel mapping (rendered via Channel Mapping tab) ────────────
            'channel_mappings' => [],

            // ── Historical imports ────────────────────────────────────────────
            'import_jobs' => [],

            // ── Phased unlock (Northbeam day-0/7/30/90 pattern) ──────────────
            'phased_unlock' => [
                'current_day' => (int) now()->diffInDays($workspace->created_at ?? now()),
                'unlocks'     => [
                    ['day' => 0,  'feature' => 'Dashboard & Orders',              'unlocked' => true],
                    ['day' => 7,  'feature' => 'Attribution (Last-non-direct)',   'unlocked' => true],
                    ['day' => 30, 'feature' => 'Cohort analysis',                 'unlocked' => false],
                    ['day' => 90, 'feature' => 'LTV curves & predictive signals', 'unlocked' => false],
                ],
            ],
        ]);
    }

    public function show(Request $request): Response
    {
        $workspace   = Workspace::findOrFail(app(WorkspaceContext::class)->id());
        $workspaceId = $workspace->id;

        $this->authorize('viewSettings', $workspace);

        // One query for all running sync logs in the workspace. Used below to show
        // sync-in-progress state on each row without requiring per-item queries.
        $runningSyncIds = DB::table('sync_logs')
            ->where('workspace_id', $workspaceId)
            ->where('status', 'running')
            ->select(['syncable_type', 'syncable_id'])
            ->get()
            ->groupBy('syncable_type')
            ->map(fn ($group) => $group->pluck('syncable_id')->unique()->flip()->toArray());

        // Cache keys set at dispatch time (syncStore/syncAdAccount/syncGsc below).
        // Why: there is a gap between job dispatch and the job's handle() creating a
        // sync_log row. During that window, sync_running would wrongly flip back to false
        // on page refresh. A 5-minute cache key bridges the gap reliably.
        $queuedStoreIds     = cache()->get("sync_queued_stores_{$workspaceId}",      []);
        $queuedAdAccountIds = cache()->get("sync_queued_ad_accounts_{$workspaceId}", []);
        $queuedGscIds       = cache()->get("sync_queued_gsc_{$workspaceId}",         []);

        $storeRows = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->select([
                'id', 'slug', 'name', 'domain', 'type', 'status', 'currency',
                'last_synced_at', 'historical_import_status', 'historical_import_progress',
                'historical_import_from', 'consecutive_sync_failures',
            ])
            ->orderBy('created_at')
            ->get();

        $latestWebhooks = DB::table('webhook_logs')
            ->whereIn('store_id', $storeRows->pluck('id'))
            ->selectRaw('store_id, MAX(created_at) as last_webhook_at')
            ->groupBy('store_id')
            ->pluck('last_webhook_at', 'store_id');

        $runningStoreIds      = $runningSyncIds[Store::class] ?? [];
        $runningAdAccountIds  = $runningSyncIds[AdAccount::class] ?? [];
        $runningGscIds        = $runningSyncIds[\App\Models\SearchConsoleProperty::class] ?? [];

        $stores = $storeRows->map(function (Store $s) use ($latestWebhooks, $runningStoreIds, $queuedStoreIds) {
            $lastWebhookAt = isset($latestWebhooks[$s->id])
                ? \Carbon\Carbon::parse($latestWebhooks[$s->id])
                : null;

            // Sync method: real_time if a webhook arrived in the last 90 minutes.
            $syncMethod = ($lastWebhookAt !== null && $lastWebhookAt->gte(now()->subMinutes(90)))
                ? 'real_time'
                : 'polling';

            // Data freshness: based on the most recent of last_synced_at and last_webhook_at.
            $lastSyncedAt = $s->last_synced_at;
            $mostRecentAt = match (true) {
                $lastSyncedAt !== null && $lastWebhookAt !== null => $lastSyncedAt->max($lastWebhookAt),
                $lastSyncedAt !== null                            => $lastSyncedAt,
                $lastWebhookAt !== null                           => $lastWebhookAt,
                default                                           => null,
            };

            $freshness = match (true) {
                $mostRecentAt === null                         => 'red',
                $mostRecentAt->gte(now()->subHours(2))        => 'green',
                $mostRecentAt->gte(now()->subHours(24))       => 'amber',
                default                                        => 'red',
            };

            return [
                'id'                         => $s->id,
                'slug'                       => $s->slug,
                'name'                       => $s->name,
                'domain'                     => $s->domain,
                'type'                       => $s->type,
                'status'                     => $s->status,
                'currency'                   => $s->currency,
                'last_synced_at'             => $s->last_synced_at?->toISOString(),
                'last_webhook_at'            => $lastWebhookAt?->toISOString(),
                'historical_import_status'   => $s->historical_import_status,
                'historical_import_progress' => $s->historical_import_progress,
                'historical_import_from'     => $s->historical_import_from?->toDateString(),
                'consecutive_sync_failures'  => $s->consecutive_sync_failures,
                'sync_running'               => isset($runningStoreIds[$s->id]) || in_array($s->id, $queuedStoreIds),
                // Webhook health fields — used for sync method + freshness badge on Integrations page.
                // See: PLANNING.md "Webhook health surfacing (user-facing)"
                'sync_method'                => $syncMethod,
                'freshness'                  => $freshness,
            ];
        });

        $adAccounts = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->select([
                'id', 'platform', 'name', 'external_id', 'currency',
                'status', 'last_synced_at', 'consecutive_sync_failures',
                'historical_import_status', 'historical_import_progress', 'historical_import_from',
            ])
            ->orderBy('created_at')
            ->get()
            ->map(fn (AdAccount $a) => [
                'id'                         => $a->id,
                'platform'                   => $a->platform,
                'name'                       => $a->name,
                'external_id'                => $a->external_id,
                'currency'                   => $a->currency,
                'status'                     => $a->status,
                'last_synced_at'             => $a->last_synced_at?->toISOString(),
                'consecutive_sync_failures'  => $a->consecutive_sync_failures,
                'historical_import_status'   => $a->historical_import_status,
                'historical_import_progress' => $a->historical_import_progress,
                'historical_import_from'     => $a->historical_import_from?->toDateString(),
                'sync_running'               => isset($runningAdAccountIds[$a->id]) || in_array($a->id, $queuedAdAccountIds),
            ]);

        $gscProperties = SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->select(['id', 'property_url', 'status', 'last_synced_at', 'consecutive_sync_failures',
                      'historical_import_status', 'historical_import_progress', 'historical_import_from'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (SearchConsoleProperty $p) => [
                'id'                         => $p->id,
                'property_url'               => $p->property_url,
                'status'                     => $p->status,
                'last_synced_at'             => $p->last_synced_at?->toISOString(),
                'consecutive_sync_failures'  => $p->consecutive_sync_failures,
                'historical_import_status'   => $p->historical_import_status,
                'historical_import_progress' => $p->historical_import_progress,
                'historical_import_from'     => $p->historical_import_from?->toDateString(),
                'sync_running'               => isset($runningGscIds[$p->id]) || in_array($p->id, $queuedGscIds),
            ]);

        $userRole = WorkspaceUser::where('workspace_id', $workspaceId)
            ->where('user_id', $request->user()->id)
            ->value('role') ?? 'member';

        // Pending pickers — tokens live in cache so they survive the cross-domain OAuth redirect.
        $gscPending  = $this->resolvePending($request->query('gsc_pending'),  $workspaceId, 'properties');
        $fbPending   = $this->resolvePending($request->query('fb_pending'),   $workspaceId, 'accounts');
        $gadsPending = $this->resolvePending($request->query('gads_pending'), $workspaceId, 'accounts');

        // oauth_error + oauth_platform are passed as query params because cross-domain
        // OAuth redirects (ngrok → app domain) do not carry the session cookie.
        // We pass them straight to Inertia so the frontend renders them inline — no flash,
        // no toast, so the message doesn't persist across page refreshes.
        $oauthError    = $request->query('oauth_error');
        $oauthPlatform = $request->query('oauth_platform');

        return Inertia::render('Settings/Integrations', [
            'stores'         => $stores,
            'ad_accounts'    => $adAccounts,
            'gsc_properties' => $gscProperties,
            'user_role'      => $userRole,
            'gsc_pending'    => $gscPending,
            'fb_pending'     => $fbPending,
            'gads_pending'   => $gadsPending,
            'oauth_error'    => is_string($oauthError) && $oauthError !== '' ? $oauthError : null,
            'oauth_platform' => is_string($oauthPlatform) && $oauthPlatform !== '' ? $oauthPlatform : null,
        ]);
    }

    /**
     * Read a pending OAuth cache entry and return the key + payload field for the frontend.
     *
     * @return array{key: string, items: mixed}|null
     */
    private function resolvePending(mixed $key, int $workspaceId, string $field): ?array
    {
        if (! is_string($key) || $key === '') {
            return null;
        }

        $cached = cache()->get($key);

        if ($cached === null || (int) ($cached['workspace_id'] ?? 0) !== $workspaceId) {
            return null;
        }

        return ['key' => $key, 'items' => $cached[$field]];
    }

    /**
     * Permanently remove a store and all its data.
     */
    public function removeStore(Request $request, string $storeSlug): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('slug', $storeSlug)
            ->firstOrFail();

        $this->authorize('delete', $store); // StorePolicy::delete = owner or admin

        $name = $store->name;
        (new RemoveStoreAction)->handle($store);

        return redirect()->route('settings.integrations', ['workspace' => $workspace->slug])
            ->with('success', "{$name} removed.");
    }

    /**
     * Permanently remove a Facebook or Google Ads ad account and all its data.
     */
    public function removeAdAccount(Request $request, string $adAccountId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $adAccount = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $adAccountId)
            ->firstOrFail();

        $this->authorize('update', $workspace); // owner or admin

        $name     = ucfirst($adAccount->platform) . ' — ' . $adAccount->name;
        (new RemoveAdAccountAction)->handle($adAccount);

        return redirect()->route('settings.integrations')
            ->with('success', "{$name} removed.");
    }

    /**
     * Permanently remove a Google Search Console property and all its data.
     */
    public function removeGsc(Request $request, string $propertyId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $property = SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $propertyId)
            ->firstOrFail();

        $this->authorize('update', $workspace); // owner or admin

        $url = $property->property_url;
        (new RemoveGscPropertyAction)->handle($property);

        return redirect()->route('settings.integrations')
            ->with('success', "Search Console property {$url} removed.");
    }

    /**
     * Retry a failed historical import for a store.
     * Preserves the existing checkpoint so the job resumes from where it failed.
     */
    public function retryImportStore(Request $request, string $storeSlug): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('slug', $storeSlug)
            ->firstOrFail();

        $this->authorize('delete', $store);

        abort_unless($store->historical_import_status === 'failed', 422, 'Import is not in a failed state.');

        $store->update(['historical_import_status' => 'pending']);

        $syncLog = SyncLog::create([
            'workspace_id'  => $workspace->id,
            'syncable_type' => Store::class,
            'syncable_id'   => $store->id,
            'job_type'      => WooCommerceHistoricalImportJob::class,
            'status'        => 'queued',
        ]);

        WooCommerceHistoricalImportJob::dispatch($store->id, $workspace->id, $syncLog->id);

        return back()->with('success', "Import retry queued for {$store->name}.");
    }

    /**
     * Retry a failed historical import for a Facebook or Google Ads account.
     * Preserves the existing checkpoint so the job resumes from where it failed.
     */
    public function retryImportAdAccount(Request $request, string $adAccountId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $adAccount = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $adAccountId)
            ->firstOrFail();

        $this->authorize('update', $workspace);

        abort_unless($adAccount->historical_import_status === 'failed', 422, 'Import is not in a failed state.');

        $adAccount->update(['historical_import_status' => 'pending']);

        $syncLog = SyncLog::create([
            'workspace_id'  => $workspace->id,
            'syncable_type' => AdAccount::class,
            'syncable_id'   => $adAccount->id,
            'job_type'      => AdHistoricalImportJob::class,
            'status'        => 'queued',
        ]);

        AdHistoricalImportJob::dispatch($adAccount->id, $workspace->id, $syncLog->id);

        return back()->with('success', "Import retry queued for {$adAccount->name}.");
    }

    /**
     * Retry a failed historical import for a Search Console property.
     * Preserves the existing checkpoint so the job resumes from where it failed.
     */
    public function retryImportGsc(Request $request, string $propertyId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $property = SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $propertyId)
            ->firstOrFail();

        $this->authorize('update', $workspace);

        abort_unless($property->historical_import_status === 'failed', 422, 'Import is not in a failed state.');

        $property->update(['historical_import_status' => 'pending']);

        $syncLog = SyncLog::create([
            'workspace_id'  => $workspace->id,
            'syncable_type' => SearchConsoleProperty::class,
            'syncable_id'   => $property->id,
            'job_type'      => GscHistoricalImportJob::class,
            'status'        => 'queued',
        ]);

        GscHistoricalImportJob::dispatch($property->id, $workspace->id, $syncLog->id);

        return back()->with('success', "Import retry queued for " . GscPropertyFormatter::format($property->property_url) . ".");
    }

    /**
     * Re-import a store's history from a user-chosen date, discarding any existing
     * checkpoint so the job starts fresh from that date.
     */
    public function reimportStore(Request $request, string $storeSlug, StartHistoricalImportAction $importAction): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('slug', $storeSlug)
            ->firstOrFail();

        $this->authorize('delete', $store);

        $validated = $request->validate([
            'from_date' => ['nullable', 'date', 'before:today'],
        ]);

        // Reset import state before re-dispatching.
        $store->update([
            'status'                         => 'active',
            'consecutive_sync_failures'      => 0,
            'historical_import_checkpoint'   => null,
            'historical_import_completed_at' => null,
        ]);

        $fromDate = isset($validated['from_date'])
            ? \Carbon\Carbon::parse($validated['from_date'])
            : \Carbon\Carbon::createFromDate(2010, 1, 1);

        $importAction->handle($store, $fromDate);

        $fromLabel = $validated['from_date'] ?? 'the beginning';

        return back()->with('success', "Re-import queued for {$store->name} from {$fromLabel}.");
    }

    /**
     * Re-import an ad account's history from a user-chosen date.
     */
    public function reimportAdAccount(Request $request, string $adAccountId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $adAccount = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $adAccountId)
            ->firstOrFail();

        $this->authorize('update', $workspace);

        // Why: Facebook API limits historical data to 37 months back.
        // Requests beyond that return error #3018.
        $earliestAllowed = now()->subMonths(37)->toDateString();

        $validated = $request->validate([
            'from_date' => ['nullable', 'date', 'before:today', "after_or_equal:{$earliestAllowed}"],
        ]);

        $adAccount->update([
            'historical_import_status'       => 'pending',
            'historical_import_from'         => $validated['from_date'] ?? null,
            'historical_import_checkpoint'   => null,
            'historical_import_progress'     => null,
        ]);

        $syncLog = SyncLog::create([
            'workspace_id'  => $workspace->id,
            'syncable_type' => AdAccount::class,
            'syncable_id'   => $adAccount->id,
            'job_type'      => AdHistoricalImportJob::class,
            'status'        => 'queued',
        ]);

        AdHistoricalImportJob::dispatch($adAccount->id, $workspace->id, $syncLog->id);

        $fromLabel = $validated['from_date'] ?? 'the beginning';

        return back()->with('success', "Re-import queued for {$adAccount->name} from {$fromLabel}.");
    }

    /**
     * Re-import a Search Console property's history from a user-chosen date.
     */
    public function reimportGsc(Request $request, string $propertyId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $property = SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $propertyId)
            ->firstOrFail();

        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'from_date' => ['nullable', 'date', 'before:today'],
        ]);

        $property->update([
            'historical_import_status'     => 'pending',
            'historical_import_from'       => $validated['from_date'] ?? null,
            'historical_import_checkpoint' => null,
            'historical_import_progress'   => null,
        ]);

        $syncLog = SyncLog::create([
            'workspace_id'  => $workspace->id,
            'syncable_type' => SearchConsoleProperty::class,
            'syncable_id'   => $property->id,
            'job_type'      => GscHistoricalImportJob::class,
            'status'        => 'queued',
        ]);

        GscHistoricalImportJob::dispatch($property->id, $workspace->id, $syncLog->id);

        $fromLabel = $validated['from_date'] ?? 'the beginning';

        return back()->with('success', "Re-import queued for " . GscPropertyFormatter::format($property->property_url) . " from {$fromLabel}.");
    }

    /**
     * Manually trigger an order sync for a single store.
     * Routes to the platform-specific sync job; bypasses the webhook-active check.
     */
    public function syncStore(Request $request, string $storeSlug): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('slug', $storeSlug)
            ->firstOrFail();

        $this->authorize('delete', $store); // StorePolicy — owner or admin

        // Reset error state so the job doesn't silently exit on the status !== 'active' guard.
        if ($store->status === 'error') {
            $store->update([
                'status'                    => 'active',
                'consecutive_sync_failures' => 0,
            ]);
        }

        if ($store->platform === 'shopify') {
            dispatch(new SyncShopifyOrdersJob($store->id, $store->workspace_id, force: true));
        } else {
            dispatch(new SyncStoreOrdersJob($store->id, $store->workspace_id, force: true));
        }

        $key     = "sync_queued_stores_{$workspace->id}";
        $current = cache()->get($key, []);
        if (! in_array($store->id, $current)) {
            cache()->put($key, [...$current, $store->id], now()->addMinutes(5));
        }

        return back()->with('success', "Sync queued for {$store->name}.");
    }

    /**
     * Manually trigger an ad insights sync for a single ad account.
     */
    public function syncAdAccount(Request $request, string $adAccountId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $adAccount = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $adAccountId)
            ->firstOrFail();

        $this->authorize('update', $workspace); // owner or admin

        // A manual sync is an explicit user-initiated retry. Reset error state so the job
        // doesn't silently exit on the status !== 'active' guard in SyncAdInsightsJob.
        if ($adAccount->status === 'error') {
            $adAccount->update([
                'status'                    => 'active',
                'consecutive_sync_failures' => 0,
            ]);
        }

        dispatch(new SyncAdInsightsJob($adAccount->id, $workspace->id, $adAccount->platform));

        $key     = "sync_queued_ad_accounts_{$workspace->id}";
        $current = cache()->get($key, []);
        if (! in_array($adAccount->id, $current)) {
            cache()->put($key, [...$current, $adAccount->id], now()->addMinutes(5));
        }

        return back()->with('success', "Sync queued for {$adAccount->name}.");
    }

    /**
     * Manually trigger a Search Console sync for a single property.
     */
    public function syncGsc(Request $request, string $propertyId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $property = SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $propertyId)
            ->firstOrFail();

        $this->authorize('update', $workspace); // owner or admin

        dispatch(new SyncSearchConsoleJob($property->id, $workspace->id));

        $key     = "sync_queued_gsc_{$workspace->id}";
        $current = cache()->get($key, []);
        if (! in_array($property->id, $current)) {
            cache()->put($key, [...$current, $property->id], now()->addMinutes(5));
        }

        return back()->with('success', "Sync queued for " . GscPropertyFormatter::format($property->property_url) . ".");
    }
}
