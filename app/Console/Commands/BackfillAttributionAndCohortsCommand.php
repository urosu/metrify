<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BuildAttributionSnapshotJob;
use App\Jobs\BuildCohortSnapshotJob;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Backfill daily_snapshot_attribution_models and daily_snapshot_cohorts.
 *
 * Loops over every date in the requested range and dispatches (or runs
 * synchronously) one BuildAttributionSnapshotJob per date per workspace.
 * Dispatches one BuildCohortSnapshotJob per workspace at the end (it rebuilds
 * the full matrix in a single pass, so running it once is enough).
 *
 * Usage:
 *   php artisan snapshots:backfill-attribution-and-cohorts
 *   php artisan snapshots:backfill-attribution-and-cohorts demo-store
 *   php artisan snapshots:backfill-attribution-and-cohorts demo-store --from=2026-01-01
 *   php artisan snapshots:backfill-attribution-and-cohorts demo-store --from=2026-01-01 --sync
 *
 * @see app/Jobs/BuildAttributionSnapshotJob.php
 * @see app/Jobs/BuildCohortSnapshotJob.php
 */
class BackfillAttributionAndCohortsCommand extends Command
{
    protected $signature = 'snapshots:backfill-attribution-and-cohorts
        {workspace?  : Workspace slug; omit to backfill all workspaces}
        {--from=     : Start date (Y-m-d); default = 36 months ago}
        {--to=       : End date   (Y-m-d); default = yesterday}
        {--sync      : Run synchronously rather than queuing (slow but no worker needed)}
        {--skip-cohorts : Skip the cohort rebuild (useful when only attribution needs backfilling)}';

    protected $description = 'Backfill attribution model snapshots and cohort snapshots.';

    public function handle(): int
    {
        $workspaces = $this->resolveWorkspaces();

        if ($workspaces->isEmpty()) {
            $this->error('No matching workspaces found.');
            return self::FAILURE;
        }

        $sync        = (bool) $this->option('sync');
        $skipCohorts = (bool) $this->option('skip-cohorts');

        foreach ($workspaces as $workspace) {
            [$from, $to] = $this->resolveDateRange($workspace->id);

            $this->line("[{$workspace->slug}] attribution backfill {$from->toDateString()} → {$to->toDateString()}");

            $current = $from->copy();
            while ($current->lte($to)) {
                $date = $current->copy();

                if ($sync) {
                    BuildAttributionSnapshotJob::dispatchSync($workspace->id, $date);
                    $this->line("[{$workspace->slug}] {$date->toDateString()} done");
                } else {
                    BuildAttributionSnapshotJob::dispatch($workspace->id, $date);
                    $this->line("[{$workspace->slug}] {$date->toDateString()} queued");
                }

                $current->addDay();
            }

            if (! $skipCohorts) {
                $asOf = $to->copy();
                if ($sync) {
                    BuildCohortSnapshotJob::dispatchSync($workspace->id, $asOf);
                    $this->info("[{$workspace->slug}] cohort rebuild done");
                } else {
                    BuildCohortSnapshotJob::dispatch($workspace->id, $asOf);
                    $this->info("[{$workspace->slug}] cohort rebuild queued");
                }
            }

            $this->info("[{$workspace->slug}] complete.");
        }

        return self::SUCCESS;
    }

    /** @return \Illuminate\Support\Collection<int, Workspace> */
    private function resolveWorkspaces(): \Illuminate\Support\Collection
    {
        $slug = $this->argument('workspace');

        if ($slug !== null) {
            return Workspace::withoutGlobalScopes()->where('slug', $slug)->get();
        }

        return Workspace::withoutGlobalScopes()->whereNull('deleted_at')->get();
    }

    /** @return array{Carbon, Carbon} */
    private function resolveDateRange(int $workspaceId): array
    {
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : $this->defaultFromDate($workspaceId);

        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))
            : Carbon::yesterday('UTC');

        return [$from, $to];
    }

    /**
     * Default start = oldest order date in the workspace, capped at 36 months ago.
     * This ensures the attribution snapshot aligns with the cohort matrix depth.
     */
    private function defaultFromDate(int $workspaceId): Carbon
    {
        $cap     = Carbon::now()->subMonths(36)->startOfMonth();
        $minDate = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereIn('status', ['completed', 'processing'])
            ->min('occurred_at');

        $oldest = $minDate ? Carbon::parse($minDate)->startOfDay() : $cap;

        return $oldest->lt($cap) ? $cap : $oldest;
    }
}
