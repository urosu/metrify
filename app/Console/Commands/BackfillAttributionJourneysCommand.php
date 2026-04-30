<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BuildAttributionJourneyJob;
use App\Models\Order;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Dispatch BuildAttributionJourneyJob for workspaces that have orders with a
 * missing attribution_journey, scoped to the requested lookback window.
 *
 * This is the queue-dispatch variant of the backfill. It dispatches one
 * BuildAttributionJourneyJob per workspace (which chunks internally in batches
 * of 500). Use this for large datasets — the job runs async on the attribution
 * queue and respects the ShouldBeUnique lock (one run per workspace at a time).
 *
 * For a synchronous CLI backfill, use attribution:backfill-journey instead.
 *
 * Usage:
 *   php artisan attribution:backfill-journeys
 *   php artisan attribution:backfill-journeys --workspace=my-store
 *   php artisan attribution:backfill-journeys --workspace=42 --days=30
 *
 * Options:
 *   --workspace   Workspace slug or numeric ID. Omit to process all workspaces.
 *   --days        Lookback window in days (default 90). Only orders where
 *                 attribution_journey IS NULL AND created_at >= NOW() - INTERVAL
 *                 '{days} days' are eligible.
 *
 * Exit codes: 0 success, 1 no eligible workspaces found.
 *
 * Reads:    orders, workspaces
 * Writes:   dispatches BuildAttributionJourneyJob (writes orders.attribution_journey)
 * Called by: CLI
 *
 * @see app/Jobs/BuildAttributionJourneyJob.php
 * @see app/Console/Commands/BackfillAttributionJourneyCommand.php (sync variant)
 * @see docs/planning/backend.md §7 (attribution pipeline)
 */
class BackfillAttributionJourneysCommand extends Command
{
    protected $signature = 'attribution:backfill-journeys
        {--workspace= : Workspace slug or numeric ID; omit to process all workspaces}
        {--days=90    : How many days back to include (orders with attribution_journey IS NULL)}';

    protected $description = 'Dispatch BuildAttributionJourneyJob per workspace for orders missing an attribution journey.';

    public function handle(): int
    {
        $workspaceOption = $this->option('workspace');
        $days            = max(1, (int) ($this->option('days') ?? 90));

        $workspaces = $this->resolveWorkspaces($workspaceOption);

        if ($workspaces->isEmpty()) {
            $this->error('No matching workspaces found.');
            return self::FAILURE;
        }

        $cutoff     = now()->subDays($days)->toDateTimeString();
        $dispatched = 0;

        foreach ($workspaces as $workspace) {
            // Count eligible orders so we can print a meaningful progress line.
            // Uses withoutGlobalScopes + explicit workspace_id to avoid WorkspaceScope needing context.
            $count = DB::table('orders')
                ->where('workspace_id', $workspace->id)
                ->whereNull('attribution_journey')
                ->where('created_at', '>=', $cutoff)
                ->count();

            if ($count === 0) {
                $this->line("Processing workspace {$workspace->slug} (0 orders) — skipped.");
                continue;
            }

            $this->line("Processing workspace {$workspace->slug} ({$count} orders)...");

            // One job per workspace; the job chunks internally (500 orders per batch).
            // ShouldBeUnique ensures at most one run per workspace is queued at a time.
            BuildAttributionJourneyJob::dispatch($workspace->id);

            $dispatched++;
        }

        $this->info("Done. Dispatched BuildAttributionJourneyJob for {$dispatched} workspace(s).");

        return self::SUCCESS;
    }

    /**
     * Resolve the set of workspaces to process.
     *
     * When --workspace is a numeric string it is treated as an ID;
     * otherwise it is matched against the slug column.
     *
     * @return \Illuminate\Support\Collection<int, Workspace>
     */
    private function resolveWorkspaces(?string $workspaceOption): \Illuminate\Support\Collection
    {
        if ($workspaceOption !== null) {
            $query = Workspace::withoutGlobalScopes()->whereNull('deleted_at');

            if (ctype_digit($workspaceOption)) {
                $query->where('id', (int) $workspaceOption);
            } else {
                $query->where('slug', $workspaceOption);
            }

            return $query->select(['id', 'slug'])->get();
        }

        return Workspace::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->select(['id', 'slug'])
            ->get();
    }
}
