<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Raw GA4 session counts by date, country, and device category.
 * NULL country_code = all-countries aggregate row; NULL device_category = device aggregate.
 * data_state='provisional' for rows < 3 days old — GA4 may revise these.
 *
 * Reads: ga4_daily_sessions table (workspace-scoped).
 * Writes: Ga4SessionSyncJob (daily); rows are upserted on unique (ga4_property_id, date, country_code, device_category).
 * Called by: DailySnapshot builder for sessions_source='ga4'; SessionsController.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class Ga4DailySession extends Model
{
    // Append-only sync table; no updated_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'workspace_id',
        'ga4_property_id',
        'date',
        'sessions',
        'users',
        'country_code',
        'device_category',
        'data_state',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'date'      => 'date',
            'sessions'  => 'integer',
            'users'     => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function ga4Property(): BelongsTo
    {
        return $this->belongsTo(Ga4Property::class);
    }
}
