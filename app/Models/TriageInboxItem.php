<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;


/**
 * Unified triage inbox for alerts, anomaly notifications, and actionable items.
 * Renamed from inbox_items; merges former alerts table.
 *
 * Reads: triage_inbox_items table (workspace-scoped).
 * Writes: AnomalyDetectionJob, IntegrationMonitorJob, alert triggers.
 * Called by: TriageController; TriageInbox primitive (UX §5.13).
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class TriageInboxItem extends Model
{
    protected $fillable = [
        'workspace_id',
        'itemable_type',
        'itemable_id',
        'severity',
        'title',
        'context_text',
        'primary_action_label',
        'deep_link_url',
        'status',
        'snoozed_until',
        'dismissed_at',
        'dismissed_by_user_id',
        'dismiss_reason',
    ];

    protected function casts(): array
    {
        return [
            'snoozed_until' => 'datetime',
            'dismissed_at'  => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
