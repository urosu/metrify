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

/**
 * Nightly recompute of product_variants.velocity_28d for a single workspace.
 *
 * Queue:    low
 * Schedule: nightly 01:00, once per active workspace (after BuildDailySnapshotJob passes)
 * Timeout:  300 s
 * Tries:    3
 * Unique:   yes — one run per workspace per night
 *
 * velocity_28d drives days-of-cover = stock_quantity / NULLIF(velocity_28d, 0).
 * Variants with fewer than 3 distinct sale days in the 28-day window are NULLed
 * to signal insufficient data.
 *
 * Dispatched by: DispatchDailySnapshots (after snapshot pass), routes/console.php schedule
 *
 * @see app/Services/Snapshots/SnapshotBuilderService.php::recomputeVelocity28d
 * @see docs/planning/schema.md §1.3 (product_variants.velocity_28d)
 */
class ComputeVelocityJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 300;
    public int $tries     = 3;
    public int $uniqueFor = 3600; // one per workspace per hour

    public function __construct(
        public readonly int $workspaceId,
    ) {
        $this->onQueue('low');
    }

    public function uniqueId(): string
    {
        return "velocity:{$this->workspaceId}";
    }

    public function handle(SnapshotBuilderService $builder, WorkspaceContext $context): void
    {
        $context->set($this->workspaceId);
        $builder->recomputeVelocity28d($this->workspaceId);
    }
}
