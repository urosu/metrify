<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\WorkspaceContext;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Re-derive orders.attribution_* for all orders in a workspace after a config change.
 *
 * Fan-out strategy (targets 100k orders in <5 min per docs/planning/backend.md §11):
 *   1. Sets WorkspaceContext so any incidental scope checks don't throw.
 *   2. Uses withoutGlobalScopes() + explicit workspace_id filter on ALL DB reads.
 *      (WorkspaceScope throws when WorkspaceContext::id() is null in queue context.)
 *   3. Nulls attribution_journey in chunked UPDATE batches of 500 rows each —
 *      avoids a single enormous table scan inside the orchestrator job.
 *   4. Dispatches BackfillAttributionDataJob (handles attribution_* column rewrite with
 *      its own chunk(200) + bulk UPDATE … FROM (VALUES …) pattern).
 *   5. Fans out BuildDailySnapshotJob per active store.
 *   6. Dispatches WS-A2d derivative writers after attribution columns are fresh.
 *   7. Dispatches BuildAttributionJourneyJob after the null-out.
 *   8. Dispatches ReconcileSourceDisagreementsJob.
 *
 * Queue:     low
 * Timeout:   900 s (fan-out is cheap; heavy work runs in child jobs)
 * Tries:     3
 * Unique:    yes — one recompute per workspace at a time (ShouldBeUnique + uniqueFor 1h)
 *
 * Dispatched by: UpdateCostConfigAction, UpdateAttributionDefaultsAction,
 *               UpdateChannelMappingAction
 *
 * @see docs/planning/backend.md §3.3 (job spec)
 * @see docs/planning/backend.md §0 (rule 5: config changes trigger recalc)
 * @see docs/planning/backend.md §11 (attribution pipeline performance target)
 */
class RecomputeAttributionJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 900;
    public int $tries     = 3;
    public int $uniqueFor = 3600;

    /**
     * Rows per attribution_journey NULL-out batch.
     * Large enough to reduce round-trips but small enough to avoid lock contention.
     * @see docs/planning/backend.md §11 — "chunked UPDATE 10k rows/batch"
     */
    private const JOURNEY_CHUNK = 500;

    public function __construct(public readonly int $workspaceId)
    {
        $this->onQueue('low');
    }

    public function uniqueId(): string
    {
        return (string) $this->workspaceId;
    }

    public function handle(): void
    {
        // Set workspace context so any incidental WorkspaceScope checks don't throw.
        // All DB queries in this job ALSO use withoutGlobalScopes() + explicit workspace_id
        // per CLAUDE.md gotcha: "Queue jobs don't inherit request scope."
        app(WorkspaceContext::class)->set($this->workspaceId);

        Log::info('RecomputeAttributionJob: starting', ['workspace_id' => $this->workspaceId]);

        // ── Step 1: Delegate attribution column rewrite to BackfillAttributionDataJob ──
        // That job owns chunk(200) + bulk UPDATE … FROM (VALUES …) for attribution_* columns.
        BackfillAttributionDataJob::dispatch($this->workspaceId);

        // ── Step 2: Fan-out snapshot rebuilds per store+date ─────────────────────
        // Iterates existing daily_snapshots rows so only dates with data get rebuilt.
        DB::table('daily_snapshots')
            ->where('workspace_id', $this->workspaceId)
            ->select(['store_id', 'date'])
            ->orderBy('store_id')
            ->orderBy('date')
            ->chunk(1000, function ($chunk): void {
                foreach ($chunk as $row) {
                    BuildDailySnapshotJob::dispatch(
                        (int) $row->store_id,
                        $this->workspaceId,
                        Carbon::parse($row->date),
                    );
                }
            });

        // ── Step 3: WS-A2d derivative writers ────────────────────────────────────
        // Idempotent; safe to re-dispatch on every config change.
        BuildAttributionLastNonDirectJob::dispatch($this->workspaceId);
        MarkModeledOrdersJob::dispatch($this->workspaceId);
        ComputePaybackDaysJob::dispatch($this->workspaceId);

        // ── Step 4: Null out attribution_journey in chunks ────────────────────────
        // Chunked to avoid holding an exclusive table lock for the duration of a single
        // massive UPDATE on a 100k-row table. chunkById() iterates via cursor on `id`
        // so each batch is a bounded UPDATE with a PK-range WHERE clause.
        //
        // We null the column so BuildAttributionJourneyJob (dispatched next) processes
        // every order from scratch rather than skipping already-populated rows.
        DB::table('orders')
            ->where('workspace_id', $this->workspaceId)
            ->whereNotNull('attribution_journey')
            ->orderBy('id')
            ->chunkById(self::JOURNEY_CHUNK, function ($chunk): void {
                DB::table('orders')
                    ->whereIn('id', $chunk->pluck('id'))
                    ->update(['attribution_journey' => null]);
            }, 'id');

        BuildAttributionJourneyJob::dispatch($this->workspaceId);

        // ── Step 5: Refresh source disagreement cache ─────────────────────────────
        ReconcileSourceDisagreementsJob::dispatch($this->workspaceId);

        Log::info('RecomputeAttributionJob: dispatched all downstream jobs', [
            'workspace_id' => $this->workspaceId,
        ]);
    }
}
