<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BuildDailySnapshotJob;
use App\Jobs\SyncShopifyAnalyticsJob;
use App\Models\Store;
use App\Services\WorkspaceContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-shot CLI tool to backfill shopify_daily_sessions and then rebuild the
 * affected daily_snapshots so sessions + sessions_source are populated.
 *
 * Runs SyncShopifyAnalyticsJob synchronously (dispatchSync) for each 30-day
 * chunk in the requested window, then rebuilds daily_snapshots for every date
 * in the window via BuildDailySnapshotJob.
 *
 * Usage:
 *   php artisan shopify:backfill-sessions {storeId}
 *   php artisan shopify:backfill-sessions {storeId} {days=90}
 *
 * Exit codes: 0 success, 1 error.
 *
 * Writes: shopify_daily_sessions (via SyncShopifyAnalyticsJob)
 *         daily_snapshots (via BuildDailySnapshotJob)
 *
 * @see app/Jobs/SyncShopifyAnalyticsJob.php
 * @see app/Jobs/BuildDailySnapshotJob.php
 */
class BackfillShopifySessionsCommand extends Command
{
    protected $signature = 'shopify:backfill-sessions
        {storeId : Numeric store ID}
        {days=90 : Number of days to look back (max 365)}';

    protected $description = 'Backfill Shopify session data and rebuild daily snapshots for the affected dates.';

    public function handle(): int
    {
        $storeId = (int) $this->argument('storeId');
        $days    = min((int) $this->argument('days'), 365);

        $store = Store::withoutGlobalScopes()->find($storeId);

        if ($store === null) {
            $this->error("Store #{$storeId} not found.");
            return self::FAILURE;
        }

        if ($store->platform !== 'shopify') {
            $this->error("Store #{$storeId} is not a Shopify store (platform = {$store->platform}).");
            return self::FAILURE;
        }

        $workspaceId = (int) $store->workspace_id;

        app(WorkspaceContext::class)->set($workspaceId);

        $end   = Carbon::yesterday();
        $start = Carbon::today()->subDays($days);

        $this->info("Backfilling sessions for store #{$storeId} ({$store->domain}) from {$start->toDateString()} to {$end->toDateString()} ({$days} days).");

        // Pull session data in 30-day chunks (Shopify Analytics API recommended window).
        // Chunking avoids excessively large ShopifyQL responses and keeps each sync
        // job within the 120 s timeout. The 7-day default in SyncShopifyAnalyticsJob
        // is relaxed here because we're explicitly backfilling history.
        $chunkStart = $start->copy();
        $fetched    = 0;

        while ($chunkStart->lte($end)) {
            $chunkEnd = $chunkStart->copy()->addDays(29)->min($end);

            $this->line("  Fetching {$chunkStart->toDateString()} → {$chunkEnd->toDateString()} …");

            SyncShopifyAnalyticsJob::dispatchSync(
                $storeId,
                $workspaceId,
                $chunkStart->toDateString(),
                $chunkEnd->toDateString(),
            );

            $fetched++;
            $chunkStart->addDays(30);
        }

        $this->info("Session fetch complete ({$fetched} chunk(s)). Rebuilding daily snapshots…");

        // Rebuild daily_snapshots for every day in the range so sessions + sessions_source
        // are written into existing snapshot rows (upsert is safe to re-run).
        $current = $start->copy();
        $rebuilt = 0;

        while ($current->lte($end)) {
            BuildDailySnapshotJob::dispatchSync($storeId, $workspaceId, $current->copy());

            $snap = DB::table('daily_snapshots')
                ->where('store_id', $storeId)
                ->where('date', $current->toDateString())
                ->select(['sessions', 'sessions_source'])
                ->first();

            $sessions = $snap?->sessions ?? 'null';
            $source   = $snap?->sessions_source ?? 'null';
            $this->line("  {$current->toDateString()} → sessions={$sessions} source={$source}");

            $rebuilt++;
            $current->addDay();
        }

        $this->info("Done. Rebuilt {$rebuilt} daily snapshot(s).");

        return self::SUCCESS;
    }
}
