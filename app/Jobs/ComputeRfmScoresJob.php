<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Customers\RfmScoringService;
use App\Services\WorkspaceContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

/**
 * Nightly RFM scoring for all customers in a workspace.
 *
 * Queue:     low
 * Schedule:  nightly 02:15 per active workspace (depends on BuildDailySnapshotJob completing)
 * Timeout:   900 s
 * Tries:     3
 * Unique:    yes — (workspaceId, date) pair (one run per workspace per night)
 *
 * Dispatched by: schedule (Kernel → per active workspace)
 *
 * @see docs/planning/backend.md §3.3 (job spec)
 * @see app/Services/Customers/RfmScoringService.php
 */
class ComputeRfmScoresJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 900;
    public int $tries     = 3;
    public int $uniqueFor = 1800;

    public function __construct(
        public readonly int $workspaceId,
        public readonly Carbon $date,
    ) {
        $this->onQueue('low');
    }

    public function uniqueId(): string
    {
        return "{$this->workspaceId}:{$this->date->toDateString()}";
    }

    public function handle(RfmScoringService $rfm, WorkspaceContext $context): void
    {
        $context->set($this->workspaceId);
        $rfm->scoreWorkspace($this->workspaceId, $this->date);
    }
}
