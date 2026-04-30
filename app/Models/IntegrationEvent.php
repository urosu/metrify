<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Log of inbound and outbound integration events (webhooks, callbacks, push notifications).
 * Replaces the former webhook_logs table. payload is external API data; paired with payload_api_version.
 *
 * Reads: integration_events table (workspace-scoped).
 * Writes: WebhookReceiverController (inbound); outbound delivery jobs.
 * Called by: IntegrationsController (event log tab); TriageInboxItem creation on failed events.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class IntegrationEvent extends Model
{
    protected $fillable = [
        'workspace_id',
        'integrationable_type',
        'integrationable_id',
        'direction',
        'event_type',
        'external_ref',
        'destination_platform',
        'status',
        'error_code',
        'error_category',
        'match_quality',
        'payload',
        'payload_api_version',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'match_quality' => 'integer',
            'payload'       => 'array',
            'received_at'   => 'datetime',
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
