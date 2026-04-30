<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workspace;
use App\Services\Workspace\SettingsAuditService;
use App\Services\Workspace\TargetService;

/**
 * Create or update a workspace metric target (goal).
 *
 * Upserts on (workspace_id, metric, period) — re-running with the same metric
 * and period updates the existing target rather than creating a duplicate.
 * Writes a settings_audit_log entry on every upsert.
 *
 * Input:  Workspace, metric, period, target_value_reporting, visible_on_pages, …
 * Output: int  workspace_targets.id
 * Writes: workspace_targets, settings_audit_log
 *
 * @see docs/planning/backend.md §2.2 (action spec)
 * @see app/Services/Workspace/TargetService.php
 */
class SetWorkspaceTargetAction
{
    public function __construct(
        private readonly TargetService $targets,
        private readonly SettingsAuditService $audit,
    ) {}

    /**
     * @param  array{metric: string, period: string, period_start?: string|null, period_end?: string|null, target_value_reporting: float, currency?: string|null, owner_user_id?: int|null, visible_on_pages?: string[]}  $data
     */
    public function handle(Workspace $workspace, int $createdBy, array $data): int
    {
        $id = $this->targets->create($workspace->id, $createdBy, $data);

        $this->audit->record(
            workspaceId:  $workspace->id,
            subPage:      'targets',
            entityType:   'workspace_targets',
            entityId:     $id,
            field:        'target_value_reporting',
            from:         null,
            to:           $data['target_value_reporting'],
            reversible:   false,
            actorUserId:  $createdBy,
        );

        return $id;
    }
}
