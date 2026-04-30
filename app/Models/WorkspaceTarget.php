<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ROAS, CAC, revenue, and other metric goals set per workspace.
 * Formerly stored as columns on the workspaces table (target_roas, target_cpo, target_marketing_pct).
 *
 * Reads: workspace_targets table (workspace-scoped).
 * Writes: WorkspaceTargetService (create/update/archive from Settings → Targets UI).
 * Called by: OverviewController, MetricCard primitives that render progress toward target.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class WorkspaceTarget extends Model
{
    protected $fillable = [
        'workspace_id',
        'metric',
        'period',
        'period_start',
        'period_end',
        'target_value_reporting',
        'currency',
        'owner_user_id',
        'visible_on_pages',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start'           => 'date',
            'period_end'             => 'date',
            'target_value_reporting' => 'decimal:2',
            'visible_on_pages'       => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
