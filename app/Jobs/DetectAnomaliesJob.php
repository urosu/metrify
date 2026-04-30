<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Trust\AnomalyDetectionService;
use App\Services\WorkspaceContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Evaluate all anomaly rules for a workspace and write breaches to triage_inbox_items.
 *
 * Queue:     low
 * Schedule:  hourly per active workspace
 * Timeout:   120 s
 * Tries:     3
 * Unique:    yes — one evaluation per workspace per hour
 *
 * Dispatched by: schedule (Kernel → per active workspace)
 *
 * @see docs/planning/backend.md §3.3 (job spec)
 * @see app/Services/Trust/AnomalyDetectionService.php
 */
class DetectAnomaliesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 120;
    public int $tries     = 3;
    public int $uniqueFor = 3600;

    public function __construct(public readonly int $workspaceId)
    {
        $this->onQueue('low');
    }

    public function uniqueId(): string
    {
        return (string) $this->workspaceId;
    }

    public function handle(AnomalyDetectionService $service, WorkspaceContext $context): void
    {
        // WorkspaceScope requires an active workspace context — set it before any
        // scoped Eloquent query or service call. See CLAUDE.md Gotchas.
        $context->set($this->workspaceId);

        $service->evaluate($this->workspaceId);
    }
}
