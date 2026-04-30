<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BuildDailySnapshotJob;
use App\Models\Store;
use App\Scopes\WorkspaceScope;
use App\Services\WorkspaceContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild daily_snapshots for a single store across a configurable date range.
 *
 * Unlike snapshots:backfill (workspace-scoped), this command accepts a numeric
 * store ID — useful for targeting one specific store after a cost rule change,
 * a GA4 resync, or a data quality fix.
 *
 * Runs BuildDailySnapshotJob synchronously (dispatchSync) so rows are written
 * immediately without queuing. Intended for one-shot CLI use only.
 *
 * Usage:
 *   php artisan snapshots:rebuild {storeId}
 *   php artisan snapshots:rebuild {storeId} --from=2026-01-01 --to=2026-04-30
 *
 * After the run, revenue_store_attributed and revenue_ga4_attributed are populated
 * for every day in the range (SnapshotBuilderService::buildDaily was extended in WS-A2d).
 *
 * Exit codes: 0 success, 1 on first error.
 *
 * Writes: daily_snapshots, daily_snapshot_products (via BuildDailySnapshotJob)
 * Called by: CLI
 *
 * @see app/Services/Snapshots/SnapshotBuilderService.php
 * @see app/Jobs/BuildDailySnapshotJob.php
 * @see app/Console/Commands/BackfillSnapshotsCommand.php (workspace-scoped sibling)
 */
class RebuildSnapshotsCommand extends Command
{
    protected $signature = 'snapshots:rebuild
        {storeId : Numeric store ID to rebuild}
        {--from= : Start date (Y-m-d); default = oldest order date for this store}
        {--to=   : End date   (Y-m-d); default = today}';

    protected $description = 'Rebuild daily_snapshots for a single store (including revenue_store_attributed + revenue_ga4_attributed).';

    public function handle(): int
    {
        $storeId = (int) $this->argument('storeId');

        // Resolve store without WorkspaceScope (CLI context).
        $store = Store::withoutGlobalScope(WorkspaceScope::class)
            ->where('id', $storeId)
            ->select(['id', 'workspace_id', 'platform'])
            ->first();

        if ($store === null) {
            $this->error("Store {$storeId} not found.");
            return self::FAILURE;
        }

        $workspaceId = (int) $store->workspace_id;
        app(WorkspaceContext::class)->set($workspaceId);

        [$from, $to] = $this->resolveDateRange($storeId);

        $this->line("Rebuilding store {$storeId} (workspace {$workspaceId}) from {$from->toDateString()} → {$to->toDateString()}");

        $current = $from->copy();
        while ($current->lte($to)) {
            $dateStr = $current->toDateString();

            BuildDailySnapshotJob::dispatchSync($storeId, $workspaceId, $current->copy());

            // Show per-day progress including the two new columns.
            $snap = DB::table('daily_snapshots')
                ->where('workspace_id', $workspaceId)
                ->where('store_id', $storeId)
                ->where('date', $dateStr)
                ->select([
                    'orders_count',
                    'revenue',
                    'revenue_store_attributed',
                    'revenue_ga4_attributed',
                    'revenue_real_attributed',
                ])
                ->first();

            $orders = $snap?->orders_count ?? 0;
            $store_ = $snap ? number_format((float) $snap->revenue_store_attributed, 2) : '0.00';
            $ga4    = $snap ? number_format((float) $snap->revenue_ga4_attributed, 2) : '0.00';
            $real   = $snap ? number_format((float) $snap->revenue_real_attributed, 2) : '0.00';

            $this->line("[{$dateStr}] {$orders} orders | store=\${$store_} | ga4=\${$ga4} | real=\${$real}");

            $current->addDay();
        }

        $this->info("Done. Store {$storeId} snapshots rebuilt.");
        return self::SUCCESS;
    }

    /**
     * Resolve [from, to] Carbon dates for the store.
     *
     * @return array{Carbon, Carbon}
     */
    private function resolveDateRange(int $storeId): array
    {
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : $this->oldestOrderDate($storeId);

        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))
            : Carbon::today();

        return [$from, $to];
    }

    private function oldestOrderDate(int $storeId): Carbon
    {
        $minDate = DB::table('orders')
            ->where('store_id', $storeId)
            ->whereIn('status', ['completed', 'processing'])
            ->min('occurred_at');

        return $minDate
            ? Carbon::parse($minDate)->startOfDay()
            : Carbon::today();
    }
}
