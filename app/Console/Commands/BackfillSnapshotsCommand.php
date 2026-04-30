<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BuildDailySnapshotJob;
use App\Models\Store;
use App\Models\Workspace;
use App\Scopes\WorkspaceScope;
use App\Services\WorkspaceContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Backfill daily_snapshots (including all per-source attribution columns) for one
 * workspace or all workspaces across a configurable date range.
 *
 * Runs BuildDailySnapshotJob synchronously (dispatchSync) so rows are written
 * immediately — this is a one-shot CLI tool, not a queue job.
 *
 * Usage:
 *   php artisan snapshots:backfill
 *   php artisan snapshots:backfill demo-store
 *   php artisan snapshots:backfill demo-store --from=2026-01-01 --to=2026-04-30
 *
 * Exit codes: 0 success, 1 on first error.
 *
 * Writes: daily_snapshots, daily_snapshot_products (via BuildDailySnapshotJob)
 * Called by: CLI, DatabaseSeeder (post-seed hook)
 *
 * @see app/Jobs/BuildDailySnapshotJob.php
 * @see app/Services/Snapshots/SnapshotBuilderService.php
 */
class BackfillSnapshotsCommand extends Command
{
    protected $signature = 'snapshots:backfill
        {workspace? : Workspace slug; omit to backfill all workspaces}
        {--from= : Start date (Y-m-d); default = oldest order date in scope}
        {--to=   : End date   (Y-m-d); default = today}';

    protected $description = 'Backfill daily_snapshots with real per-source attribution for one or all workspaces.';

    public function handle(): int
    {
        $workspaces = $this->resolveWorkspaces();

        if ($workspaces->isEmpty()) {
            $this->error('No matching workspaces found.');
            return self::FAILURE;
        }

        foreach ($workspaces as $workspace) {
            $stores = Store::withoutGlobalScope(WorkspaceScope::class)
                ->where('workspace_id', $workspace->id)
                ->get();

            if ($stores->isEmpty()) {
                $this->line("[{$workspace->slug}] no stores — skipped.");
                continue;
            }

            [$from, $to] = $this->resolveDateRange($workspace->id);

            $this->line("[{$workspace->slug}] backfilling {$from->toDateString()} → {$to->toDateString()} across {$stores->count()} store(s)");

            app(WorkspaceContext::class)->set($workspace->id);

            $current = $from->copy();
            while ($current->lte($to)) {
                $dateStr = $current->toDateString();

                foreach ($stores as $store) {
                    BuildDailySnapshotJob::dispatchSync($store->id, $workspace->id, $current->copy());

                    // Print per-day progress with real vs fb revenue for the day.
                    $snap = DB::table('daily_snapshots')
                        ->where('workspace_id', $workspace->id)
                        ->where('store_id', $store->id)
                        ->where('date', $dateStr)
                        ->select([
                            'orders_count',
                            'revenue',
                            'revenue_facebook_attributed',
                            'revenue_google_attributed',
                            'revenue_real_attributed',
                        ])
                        ->first();

                    $orders = $snap?->orders_count ?? 0;
                    $real   = $snap ? number_format((float) $snap->revenue_real_attributed, 2) : '0.00';
                    $fb     = $snap ? number_format((float) $snap->revenue_facebook_attributed, 2) : '0.00';
                    $google = $snap ? number_format((float) $snap->revenue_google_attributed, 2) : '0.00';

                    $this->line("[{$workspace->slug}] backfilled {$dateStr} ({$orders} orders, real=\${$real}, fb=\${$fb}, google=\${$google})");
                }

                $current->addDay();
            }

            $this->info("[{$workspace->slug}] done.");
        }

        return self::SUCCESS;
    }

    /**
     * Resolve workspace(s) from the optional argument.
     *
     * @return \Illuminate\Support\Collection<int, Workspace>
     */
    private function resolveWorkspaces(): \Illuminate\Support\Collection
    {
        $slug = $this->argument('workspace');

        if ($slug !== null) {
            $ws = Workspace::withoutGlobalScopes()->where('slug', $slug)->get();
            return $ws;
        }

        return Workspace::withoutGlobalScopes()->whereNull('deleted_at')->get();
    }

    /**
     * Resolve the [from, to] Carbon date range.
     * Defaults: oldest order date in workspace → today.
     *
     * @return array{Carbon, Carbon}
     */
    private function resolveDateRange(int $workspaceId): array
    {
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : $this->oldestOrderDate($workspaceId);

        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))
            : Carbon::today();

        return [$from, $to];
    }

    private function oldestOrderDate(int $workspaceId): Carbon
    {
        $minDate = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereIn('status', ['completed', 'processing'])
            ->min('occurred_at');

        return $minDate
            ? Carbon::parse($minDate)->startOfDay()
            : Carbon::today();
    }
}
