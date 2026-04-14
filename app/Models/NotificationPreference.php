<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Per-workspace-per-user alert delivery configuration.
// channel: email | in_app
// severity: critical | high | medium | low
// delivery_mode: immediate | daily_digest | weekly_digest
//
// Sensible defaults (95% of users never change — see migration for default matrix).
// Quiet hours: critical severity overrides quiet hours.
// Related: app/Jobs/SendAlertNotificationsJob.php (Phase 1)
// See: PLANNING.md "notification_preferences"
#[ScopedBy([WorkspaceScope::class])]
class NotificationPreference extends Model
{
    protected $fillable = [
        'workspace_id',
        'user_id',
        'channel',
        'severity',
        'enabled',
        'delivery_mode',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
