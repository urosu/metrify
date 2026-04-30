<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User-defined anomaly detection rules with configurable thresholds per workspace.
 * One row per rule_type per workspace (unique constraint).
 *
 * Reads: anomaly_rules table (workspace-scoped).
 * Writes: AnomalyRuleService (Settings → Notifications UI); seeded defaults on workspace creation.
 * Called by: AnomalyDetectionJob; TriageInboxItem creation pipeline.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class AnomalyRule extends Model
{
    protected $fillable = [
        'workspace_id',
        'rule_type',
        'threshold_value',
        'threshold_unit',
        'enabled',
        'delivery_channels',
        'last_fired_at',
    ];

    protected function casts(): array
    {
        return [
            'threshold_value'  => 'decimal:4',
            'enabled'          => 'boolean',
            'delivery_channels' => 'array',
            'last_fired_at'    => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
