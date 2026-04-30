<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Email digest configuration for a workspace — frequency, schedule, recipients, and pages included.
 * One row per workspace.
 *
 * Reads: digest_schedules table (workspace-scoped).
 * Writes: DigestScheduleService (Settings → Notifications UI).
 * Called by: SendDigestJob (scheduled via Horizon cron); DigestController.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class DigestSchedule extends Model
{
    protected $fillable = [
        'workspace_id',
        'frequency',
        'day_of_week',
        'day_of_month',
        'send_at_hour',
        'recipients',
        'content_pages',
        'last_sent_at',
        'last_sent_status',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week'   => 'integer',
            'day_of_month'  => 'integer',
            'send_at_hour'  => 'integer',
            'recipients'    => 'array',
            'content_pages' => 'array',
            'last_sent_at'  => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
