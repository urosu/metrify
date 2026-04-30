<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BackfillSnapshotColumnsJob;
use App\Models\Workspace;
use Illuminate\Console\Command;

/**
 * Backfill new_customer_revenue and organic_orders_count on existing daily_snapshot rows.
 *
 * Dispatches BackfillSnapshotColumnsJob (queueable) per workspace so the work runs
 * asynchronously and doesn't block deploys. Safe to re-run (the job uses an UPDATE
 * so re-running over already-backfilled rows is a no-op).
 *
 * Usage:
 *   php artisan snapshots:backfill-columns
 *   php artisan snapshots:backfill-columns demo-store     # single workspace
 *   php artisan snapshots:backfill-columns --sync         # run synchronously (dev)
 *
 * @see app/Jobs/BackfillSnapshotColumnsJob.php
 */
class BackfillSnapshotColumnsCommand extends Command
{
    protected $signature = 'snapshots:backfill-columns
        {workspace? : Workspace slug; omit to queue all workspaces}
        {--sync     : Run synchronously rather than queuing}';

    protected $description = 'Backfill new_customer_revenue and organic_orders_count on daily_snapshots.';

    public function handle(): int
    {
        $slug = $this->argument('workspace');
        $sync = (bool) $this->option('sync');

        if ($slug !== null) {
            $workspaces = Workspace::withoutGlobalScopes()->where('slug', $slug)->get();
        } else {
            $workspaces = Workspace::withoutGlobalScopes()->whereNull('deleted_at')->get();
        }

        if ($workspaces->isEmpty()) {
            $this->error('No matching workspaces found.');
            return self::FAILURE;
        }

        foreach ($workspaces as $ws) {
            if ($sync) {
                BackfillSnapshotColumnsJob::dispatchSync($ws->id);
                $this->info("[{$ws->slug}] backfill completed synchronously.");
            } else {
                BackfillSnapshotColumnsJob::dispatch($ws->id);
                $this->info("[{$ws->slug}] backfill queued.");
            }
        }

        return self::SUCCESS;
    }
}
