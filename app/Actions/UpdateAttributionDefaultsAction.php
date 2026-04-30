<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workspace;
use App\Services\Workspace\SettingsAuditService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Update workspace attribution defaults (window, model) and trigger retroactive recalculation.
 *
 * Attribution defaults live in `workspaces.workspace_settings` JSONB.
 * After an update, dispatches RecomputeAttributionJob so all historical orders
 * are re-attributed with the new defaults.
 * Writes settings_audit_log.
 *
 * Input:  Workspace, array of attribution defaults, actor user id
 * Writes: workspaces.workspace_settings, settings_audit_log;
 *         dispatches RecomputeAttributionJob
 *
 * @see docs/planning/backend.md §2.2 (action spec)
 * @see docs/planning/backend.md §0 (rule 5: attribution config changes trigger recalc)
 */
class UpdateAttributionDefaultsAction
{
    public function __construct(private readonly SettingsAuditService $audit) {}

    /**
     * @param  array{attribution_window_days?: int, attribution_model?: string}  $defaults
     */
    public function handle(Workspace $workspace, array $defaults, int $actorUserId): void
    {
        $settings = (array) ($workspace->workspace_settings ?? []);
        $changed  = [];

        foreach ($defaults as $key => $value) {
            $old = $settings[$key] ?? null;
            if ($old !== $value) {
                $settings[$key] = $value;
                $changed[$key] = ['from' => $old, 'to' => $value];
            }
        }

        if (empty($changed)) {
            return;
        }

        DB::table('workspaces')
            ->where('id', $workspace->id)
            ->update(['workspace_settings' => json_encode($settings), 'updated_at' => now()]);

        foreach ($changed as $field => $diff) {
            $this->audit->record(
                workspaceId:  $workspace->id,
                subPage:      'workspace',
                entityType:   'workspace_settings',
                entityId:     null,
                field:        $field,
                from:         $diff['from'],
                to:           $diff['to'],
                reversible:   true,
                actorUserId:  $actorUserId,
            );
        }

        // Invalidate workspace settings cache used by ConfidenceThresholdService.
        Cache::forget("workspace_settings.confidence.{$workspace->id}");

        // Retroactive recalculation: re-run attribution for all orders in the workspace.
        dispatch(new \App\Jobs\RecomputeAttributionJob($workspace->id));
    }
}
