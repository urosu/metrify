<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Reconciliation\SourceReconciliationService;
use App\Services\WorkspaceContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Nightly rolling reconciliation of store-attributed revenue vs. platform claims.
 *
 * Reconciles the last 30 days for a single workspace so that the Source Disagreement
 * Matrix always reflects current attribution state (including any retroactive order
 * updates). Dispatched once per active workspace.
 *
 * Queue:    attribution
 * Schedule: daily 03:30 UTC — after BuildDailySnapshot (00:30) and BackfillAttributionDataJob (03:00)
 *           so orders.attribution_source is fresh before we read it.
 * Timeout:  300 s
 * Tries:    3
 * Unique:   yes — one reconcile per workspace at a time
 *
 * Also dispatched on-demand at the end of RecomputeAttributionJob so disagreement rows
 * are refreshed whenever attribution config changes.
 *
 * @see docs/planning/backend.md §WS-A2c
 * @see app/Services/Reconciliation/SourceReconciliationService.php
 */
class ReconcileSourceDisagreementsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 3;

    // Prevent double-dispatch within the same 10-min window.
    public int $uniqueFor = 660;

    /** Rolling window size — 30 days keeps disagreement data fresh for any UI range. */
    private const ROLLING_DAYS = 30;

    public function __construct(public readonly int $workspaceId)
    {
        $this->onQueue('attribution');
    }

    public function uniqueId(): string
    {
        return (string) $this->workspaceId;
    }

    public function handle(SourceReconciliationService $service, WorkspaceContext $context): void
    {
        // CLAUDE.md gotcha: jobs don't inherit request scope.
        $context->set($this->workspaceId);

        $to   = Carbon::yesterday()->toDateString();
        $from = Carbon::yesterday()->subDays(self::ROLLING_DAYS - 1)->toDateString();

        $service->reconcile($this->workspaceId, $from, $to);
    }
}
