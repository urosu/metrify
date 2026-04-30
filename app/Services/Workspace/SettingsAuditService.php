<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Records settings mutations to `settings_audit_log` and exposes "Revert".
 *
 * Called on every Settings sub-page write (workspace config, team membership,
 * costs, billing, notifications, targets). Reversible changes (e.g. currency,
 * ROAS target, COGS value) expose a "Revert" button in the audit panel that
 * restores `value_from` via a callback registered per entity+field.
 *
 * Reads:  settings_audit_log
 * Writes: settings_audit_log (record); entity tables (revert)
 * Called by: Settings controllers, WorkspaceObserver hooks
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/planning/schema.md §1.10 (settings_audit_log table)
 * @see docs/UX.md §Settings "Audit log panel"
 */
class SettingsAuditService
{
    /**
     * Write an audit log entry for a settings mutation.
     *
     * @param  string       $subPage       One of: workspace, team, costs, billing, notifications, targets
     * @param  string       $entityType    Entity class slug, e.g. 'workspace_settings', 'store_cost_settings'
     * @param  int|null     $entityId      PK of the entity, or null for workspace-level fields
     * @param  string       $field         Field name that changed
     * @param  mixed        $from          Old value (will be cast to string for storage)
     * @param  mixed        $to            New value
     * @param  bool         $reversible    Whether "Revert" is exposed in the UI
     * @param  int|null     $actorUserId   User who made the change (null for system)
     * @return int  The new log row id.
     */
    public function record(
        int $workspaceId,
        string $subPage,
        string $entityType,
        ?int $entityId,
        string $field,
        mixed $from,
        mixed $to,
        bool $reversible = false,
        ?int $actorUserId = null,
    ): int {
        return DB::table('settings_audit_log')->insertGetId([
            'workspace_id'  => $workspaceId,
            'sub_page'      => $subPage,
            'actor_user_id' => $actorUserId,
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'field_changed' => $field,
            'value_from'    => $from !== null ? (string) $from : null,
            'value_to'      => $to !== null ? (string) $to : null,
            'is_reversible' => $reversible,
            'reverted_at'   => null,
            'created_at'    => now(),
        ]);
    }

    /**
     * Revert a reversible settings change by restoring `value_from` on the entity.
     *
     * Marks `reverted_at` on the log row. The actual entity update is dispatched
     * to a registered revert handler per `entity_type + field_changed`.
     *
     * @throws \RuntimeException if the log entry is not found, not reversible, or already reverted.
     */
    public function revert(int $logId, int $actorUserId): void
    {
        $log = DB::table('settings_audit_log')->where('id', $logId)->first();

        if ($log === null) {
            throw new \RuntimeException("Audit log entry {$logId} not found.");
        }

        if (!$log->is_reversible) {
            throw new \RuntimeException("Audit log entry {$logId} is not reversible.");
        }

        if ($log->reverted_at !== null) {
            throw new \RuntimeException("Audit log entry {$logId} has already been reverted.");
        }

        $this->applyRevert($log);

        DB::table('settings_audit_log')->where('id', $logId)->update([
            'reverted_at' => now(),
        ]);

        // Write a new audit entry for the revert itself (not reversible to avoid loops).
        $this->record(
            workspaceId:  (int) $log->workspace_id,
            subPage:      $log->sub_page,
            entityType:   $log->entity_type,
            entityId:     $log->entity_id !== null ? (int) $log->entity_id : null,
            field:        $log->field_changed,
            from:         $log->value_to,
            to:           $log->value_from,
            reversible:   false,
            actorUserId:  $actorUserId,
        );
    }

    /**
     * Return the last N audit entries for a sub-page (for the collapsible panel).
     *
     * @return Collection<int, object>
     */
    public function forSubPage(int $workspaceId, string $subPage, int $limit = 20): Collection
    {
        return DB::table('settings_audit_log')
            ->where('workspace_id', $workspaceId)
            ->where('sub_page', $subPage)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Apply the revert to the underlying entity table.
     *
     * Supports workspace-level settings and cost config tables.
     * Extend this switch as new reversible entity types are added.
     */
    private function applyRevert(object $log): void
    {
        $entityId = $log->entity_id !== null ? (int) $log->entity_id : null;

        match ($log->entity_type) {
            'workspace_settings' => DB::table('workspaces')
                ->where('id', (int) $log->workspace_id)
                ->update([$log->field_changed => $log->value_from]),

            'store_cost_settings' => $entityId !== null
                ? DB::table('store_cost_settings')
                    ->where('id', $entityId)
                    ->update([$log->field_changed => $log->value_from])
                : null,

            default => throw new \RuntimeException("No revert handler for entity_type '{$log->entity_type}'."),
        };
    }
}
