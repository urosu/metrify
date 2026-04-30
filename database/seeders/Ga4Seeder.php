<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\IntegrationCredential;
use App\Models\Ga4Property;
use App\Models\Store;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Seeds ga4_properties, ga4_daily_sessions, ga4_daily_attribution, and
 * ga4_order_attribution tables for the demo workspace.
 *
 * One GA4 property per store (3 total). 90 days of daily sessions + attribution.
 * ~30% of orders get a ga4_order_attribution row (realistic GA4 coverage rate).
 *
 * Writes: ga4_properties, ga4_daily_sessions, ga4_daily_attribution,
 *         ga4_order_attribution, integration_credentials
 *
 * @see docs/planning/schema.md §1.9 (ga4 tables)
 */
class Ga4Seeder extends Seeder
{
    // Source/medium combos that appear in GA4 attribution.
    // Dimensions mirror what SyncGA4AttributionJob would write.
    private const CHANNEL_GROUPS = [
        ['source' => 'google',     'medium' => 'cpc',     'channel' => 'Paid Search',    'weight' => 0.25],
        ['source' => 'facebook',   'medium' => 'cpc',     'channel' => 'Paid Social',    'weight' => 0.20],
        ['source' => '(direct)',   'medium' => '(none)',  'channel' => 'Direct',         'weight' => 0.20],
        ['source' => 'google',     'medium' => 'organic', 'channel' => 'Organic Search', 'weight' => 0.15],
        ['source' => 'newsletter', 'medium' => 'email',   'channel' => 'Email',          'weight' => 0.10],
        ['source' => 'instagram',  'medium' => 'social',  'channel' => 'Organic Social', 'weight' => 0.05],
        ['source' => 'referral',   'medium' => 'referral', 'channel' => 'Referral',      'weight' => 0.05],
    ];

    private const DEVICES = ['desktop', 'mobile', 'tablet'];

    public function run(): void
    {
        $workspace = Workspace::where('slug', 'demo-store')->first();
        $stores    = Store::where('workspace_id', $workspace->id)
            ->orderBy('id')
            ->get()
            ->keyBy('currency');

        $deStore = $stores['EUR'] ?? null;
        $gbStore = $stores['GBP'] ?? null;
        $usStore = $stores['USD'] ?? null;

        $propertyConfigs = [
            ['store' => $deStore, 'property_id' => 'properties/123456789',  'name' => 'DE Flagship — GA4',  'mid' => 'G-DE123456', 'base_sessions' => [800, 1800]],
            ['store' => $gbStore, 'property_id' => 'properties/234567890',  'name' => 'UK Lifestyle — GA4', 'mid' => 'G-UK234567', 'base_sessions' => [600, 1200]],
            ['store' => $usStore, 'property_id' => 'properties/345678901',  'name' => 'US Gadgets — GA4',   'mid' => 'G-US345678', 'base_sessions' => [500, 1000]],
        ];

        foreach ($propertyConfigs as $cfg) {
            if (! $cfg['store']) {
                continue;
            }

            $property = Ga4Property::create([
                'workspace_id'             => $workspace->id,
                'property_id'              => $cfg['property_id'],
                'property_name'            => $cfg['name'],
                'measurement_id'           => $cfg['mid'],
                'status'                   => 'active',
                'consecutive_sync_failures' => 0,
                'last_synced_at'           => now()->subHours(rand(3, 8)),
            ]);

            IntegrationCredential::create([
                'workspace_id'            => $workspace->id,
                'integrationable_type'    => Ga4Property::class,
                'integrationable_id'      => $property->id,
                'access_token_encrypted'  => Crypt::encryptString('ga4_demo_access_token_' . $property->id),
                'refresh_token_encrypted' => Crypt::encryptString('ga4_demo_refresh_token_' . $property->id),
                'token_expires_at'        => now()->addHour(),
                'is_seeded'               => true,
            ]);

            $this->seedDailySessions($workspace->id, $property->id, $cfg['base_sessions']);
            $this->seedDailyAttribution($workspace->id, $property->id, $cfg['base_sessions']);
            $this->seedOrderAttribution($workspace->id, $property->id, $cfg['store']->id);
        }
    }

    /**
     * Seed ga4_daily_sessions: 90 days × device categories + aggregate rows.
     * NULL device_category = store-total aggregate read by SnapshotBuilderService.
     *
     * @param  array{0: int, 1: int}  $baseRange  [min, max] daily sessions
     */
    private function seedDailySessions(int $workspaceId, int $propertyId, array $baseRange): void
    {
        $rows = [];
        $now  = now()->toDateTimeString();

        for ($d = 90; $d >= 0; $d--) {
            $date      = now()->subDays($d)->toDateString();
            $variance  = mt_rand(75, 130) / 100;
            $totalSess = (int) (mt_rand($baseRange[0], $baseRange[1]) * $variance);
            $totalUsers = (int) ($totalSess * mt_rand(65, 85) / 100);

            // Store-total aggregate row (source = NULL)
            $rows[] = [
                'workspace_id'    => $workspaceId,
                'ga4_property_id' => $propertyId,
                'date'            => $date,
                'sessions'        => $totalSess,
                'users'           => $totalUsers,
                'country_code'    => null,
                'device_category' => null,
                'data_state'      => $d <= 2 ? 'provisional' : 'final',
                'synced_at'       => now(),
                'created_at'      => $now,
            ];

            // Per-device breakdown rows (no country dimension on device rows)
            $deviceSplit = [0.55, 0.38, 0.07]; // desktop, mobile, tablet
            foreach (self::DEVICES as $i => $device) {
                $devSess  = (int) ($totalSess * $deviceSplit[$i] * mt_rand(90, 110) / 100);
                $devUsers = (int) ($devSess * mt_rand(65, 85) / 100);
                $rows[] = [
                    'workspace_id'    => $workspaceId,
                    'ga4_property_id' => $propertyId,
                    'date'            => $date,
                    'sessions'        => $devSess,
                    'users'           => $devUsers,
                    'country_code'    => null,
                    'device_category' => $device,
                    'data_state'      => $d <= 2 ? 'provisional' : 'final',
                    'synced_at'       => now(),
                    'created_at'      => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('ga4_daily_sessions')->insertOrIgnore($chunk);
        }
    }

    /**
     * Seed ga4_daily_attribution: 90 days × channel combos × 2 devices.
     * row_signature mirrors SyncGA4AttributionJob::rowSignature() logic.
     */
    private function seedDailyAttribution(int $workspaceId, int $propertyId, array $baseRange): void
    {
        $rows = [];
        $now  = now()->toDateTimeString();

        for ($d = 90; $d >= 0; $d--) {
            $date          = now()->subDays($d)->toDateString();
            $totalSessions = (int) (mt_rand($baseRange[0], $baseRange[1]) * (mt_rand(75, 130) / 100));
            $devices       = ['desktop', 'mobile'];

            foreach (self::CHANNEL_GROUPS as $ch) {
                foreach ($devices as $device) {
                    $share = $ch['weight'] * ($device === 'desktop' ? 0.55 : 0.45);
                    $sess  = max(1, (int) ($totalSessions * $share * mt_rand(80, 120) / 100));
                    $users = (int) ($sess * mt_rand(70, 90) / 100);
                    $convs = (int) ($sess * mt_rand(2, 8) / 100);
                    $rev   = round($convs * mt_rand(120, 350), 4);

                    // Compute row_signature matching SyncGA4AttributionJob::rowSignature()
                    $sig = substr(hash('sha256', implode('|', [
                        $ch['source'], $ch['medium'], '', $ch['channel'],
                        $ch['source'], $ch['medium'], '',
                        '', $device, '',
                    ])), 0, 64);

                    $rows[] = [
                        'workspace_id'                  => $workspaceId,
                        'ga4_property_id'               => $propertyId,
                        'date'                          => $date,
                        'session_source'                => $ch['source'],
                        'session_medium'                => $ch['medium'],
                        'session_campaign'              => null,
                        'session_default_channel_group' => $ch['channel'],
                        'first_user_source'             => $ch['source'],
                        'first_user_medium'             => $ch['medium'],
                        'first_user_campaign'           => null,
                        'landing_page'                  => null,
                        'device_category'               => $device,
                        'country_code'                  => null,
                        'sessions'                      => $sess,
                        'active_users'                  => $users,
                        'engaged_sessions'              => (int) ($sess * mt_rand(40, 70) / 100),
                        'conversions'                   => $convs,
                        'total_revenue'                 => $rev,
                        'row_signature'                 => $sig,
                        'data_state'                    => $d <= 2 ? 'provisional' : 'final',
                        'synced_at'                     => now(),
                        'created_at'                    => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('ga4_daily_attribution')->insertOrIgnore($chunk);
        }
    }

    /**
     * Seed ga4_order_attribution: ~30% of completed orders for this store get a GA4 row.
     * transaction_id matches orders.external_id.
     *
     * GA4 attribution coverage is realistic at ~30% because:
     * - GA4 uses cookie-based tracking (blocked by ITP/ad blockers ~20-30%)
     * - Some orders come via server-to-server checkout flows without a session
     */
    private function seedOrderAttribution(int $workspaceId, int $propertyId, int $storeId): void
    {
        $orders = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->where('store_id', $storeId)
            ->whereIn('status', ['completed', 'processing'])
            ->select('external_id', 'occurred_at', 'total_in_reporting_currency', 'utm_source', 'utm_medium', 'utm_campaign')
            ->get();

        $rows = [];
        $now  = now()->toDateTimeString();

        foreach ($orders as $order) {
            // ~30% coverage
            if (mt_rand(0, 100) >= 30) {
                continue;
            }

            // GA4 may report a different attribution than the order's UTM
            $ch = self::CHANNEL_GROUPS[array_rand(self::CHANNEL_GROUPS)];

            $rows[] = [
                'workspace_id'                  => $workspaceId,
                'ga4_property_id'               => $propertyId,
                'transaction_id'                => $order->external_id,
                'date'                          => substr($order->occurred_at, 0, 10),
                'session_source'                => $ch['source'],
                'session_medium'                => $ch['medium'],
                'session_campaign'              => $order->utm_campaign,
                'session_default_channel_group' => $ch['channel'],
                'first_user_source'             => $order->utm_source ?? $ch['source'],
                'first_user_medium'             => $order->utm_medium ?? $ch['medium'],
                'first_user_campaign'           => $order->utm_campaign,
                'landing_page'                  => null,
                'conversion_value'              => $order->total_in_reporting_currency ?? 0,
                'synced_at'                     => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('ga4_order_attribution')->insertOrIgnore($chunk);
        }
    }
}
