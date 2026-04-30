<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit log of every integration sync job run. Renamed from sync_logs.
 * One row per job attempt; retries increment the attempt counter.
 *
 * Reads: integration_runs table (workspace-scoped).
 * Writes: All sync jobs (FacebookAdsSyncJob, WooOrderSyncJob, etc.) write one row per run.
 * Called by: IntegrationsController (status page); AlertMonitorJob (detect stale integrations).
 *
 * Note: table uses integrationable_type/integrationable_id (not syncable_*).
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class IntegrationRun extends Model
{
    protected $table = 'integration_runs';

    protected $fillable = [
        'workspace_id',
        'integrationable_type',
        'integrationable_id',
        'job_type',
        'status',
        'records_processed',
        'error_message',
        'started_at',
        'completed_at',
        'scheduled_at',
        'queue',
        'attempt',
        'duration_seconds',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'records_processed' => 'integer',
            'started_at'        => 'datetime',
            'completed_at'      => 'datetime',
            'scheduled_at'      => 'datetime',
            'attempt'           => 'integer',
            'duration_seconds'  => 'integer',
            'payload'           => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function integrationable(): MorphTo
    {
        return $this->morphTo();
    }
}
