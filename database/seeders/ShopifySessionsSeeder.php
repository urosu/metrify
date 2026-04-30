<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds shopify_daily_sessions for Shopify stores in the demo workspace.
 *
 * In the demo workspace all stores are WooCommerce. This seeder patches in
 * a Shopify store variant by adding sessions rows for the DE store acting as
 * a Shopify proxy — this lets the Shopify sessions code path show data.
 *
 * Written by SyncShopifyAnalyticsJob in production. NULL source = aggregate row
 * used by SnapshotBuilderService when sessions_source='shopify'.
 *
 * @see docs/planning/schema.md §1.5 (shopify_daily_sessions)
 * @see app/Jobs/SyncShopifyAnalyticsJob.php
 */
class ShopifySessionsSeeder extends Seeder
{
    private const TRAFFIC_SOURCES = ['direct', 'search', 'social', 'email', 'paid_search'];
    private const SOURCE_WEIGHTS  = [0.30, 0.25, 0.18, 0.12, 0.15];

    public function run(): void
    {
        $workspace = Workspace::where('slug', 'demo-store')->first();
        // Use the DE flagship store (EUR) as the Shopify store proxy
        $store = Store::where('workspace_id', $workspace->id)
            ->where('currency', 'EUR')
            ->first();

        if (! $store) {
            return;
        }

        $rows = [];
        $now  = now()->toDateTimeString();

        for ($d = 90; $d >= 0; $d--) {
            $date        = now()->subDays($d)->toDateString();
            $isWeekend   = in_array(now()->subDays($d)->dayOfWeek, [0, 6]);
            $totalVisits = $isWeekend
                ? mt_rand(1200, 2200)
                : mt_rand(600, 1600);
            $totalVisitors = (int) ($totalVisits * mt_rand(75, 90) / 100);

            // Store-total aggregate row — NULL source
            $rows[] = [
                'workspace_id' => $workspace->id,
                'store_id'     => $store->id,
                'date'         => $date,
                'visits'       => $totalVisits,
                'visitors'     => $totalVisitors,
                'source'       => null,
                'synced_at'    => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];

            // Per-source breakdown rows
            $remaining = $totalVisits;
            foreach (self::TRAFFIC_SOURCES as $i => $source) {
                $weight  = self::SOURCE_WEIGHTS[$i];
                $isLast  = ($i === count(self::TRAFFIC_SOURCES) - 1);
                $visits  = $isLast
                    ? max(0, $remaining)
                    : (int) ($totalVisits * $weight * mt_rand(85, 115) / 100);
                $remaining -= $visits;

                $rows[] = [
                    'workspace_id' => $workspace->id,
                    'store_id'     => $store->id,
                    'date'         => $date,
                    'visits'       => max(0, $visits),
                    'visitors'     => (int) (max(0, $visits) * mt_rand(75, 90) / 100),
                    'source'       => $source,
                    'synced_at'    => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('shopify_daily_sessions')->insertOrIgnore($chunk);
        }
    }
}
