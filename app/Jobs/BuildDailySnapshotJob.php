<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Snapshots\SnapshotBuilderService;
use App\Services\WorkspaceContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

/**
 * Thin wrapper over SnapshotBuilderService::buildDaily for a single store + date.
 *
 * Queue:     low
 * Schedule:  daily 00:30 per active store (DispatchDailySnapshots)
 * Timeout:   600 s
 * Tries:     3
 * Unique:    yes — (storeId, date) pair
 *
 * Dispatched by: DispatchDailySnapshots, UpdateCostConfigAction fan-out
 *
 * @see docs/planning/backend.md §3.3 (job spec)
 * @see app/Services/Snapshots/SnapshotBuilderService.php
 */
class BuildDailySnapshotJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 600;
    public int $tries     = 3;
    public int $uniqueFor = 660;

    public function __construct(
        public readonly int $storeId,
        public readonly int $workspaceId,
        public readonly Carbon $date,
    ) {
        $this->onQueue('low');
    }

    public function uniqueId(): string
    {
        return "{$this->storeId}:{$this->date->toDateString()}";
    }

    public function handle(SnapshotBuilderService $builder, WorkspaceContext $context): void
    {
        $context->set($this->workspaceId);
        $builder->buildDaily($this->storeId, $this->date);
        $builder->buildProducts($this->storeId, $this->date);
    }
}
