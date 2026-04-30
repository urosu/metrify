<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per date × attribution-dimension combination for a GA4 property.
 *
 * Dimensions: session source/medium/campaign, session channel group,
 * first-user source/medium/campaign, landing page, device category, country.
 *
 * The composite dimension key is hashed into `row_signature` (SHA-256, 64 chars)
 * so the unique index remains narrow — see SyncGA4AttributionJob::rowSignature().
 *
 * data_state 'provisional' means the date is < 3 days old and GA4 may revise it.
 * SyncGA4AttributionJob re-syncs the 3-day rolling window nightly.
 *
 * Reads by: AttributionDataService (channel model comparison)
 * Writes by: SyncGA4AttributionJob
 *
 * @see docs/planning/schema.md §1.9 (ga4_daily_attribution)
 * @see app/Jobs/SyncGA4AttributionJob.php
 */
#[ScopedBy([WorkspaceScope::class])]
class Ga4DailyAttribution extends Model
{
    protected $table = 'ga4_daily_attribution';

    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'ga4_property_id',
        'date',
        'session_source',
        'session_medium',
        'session_campaign',
        'session_default_channel_group',
        'first_user_source',
        'first_user_medium',
        'first_user_campaign',
        'landing_page',
        'device_category',
        'country_code',
        'sessions',
        'active_users',
        'engaged_sessions',
        'conversions',
        'total_revenue',
        'row_signature',
        'data_state',
        'synced_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'synced_at'     => 'datetime',
            'created_at'    => 'datetime',
            'total_revenue' => 'decimal:4',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function ga4Property(): BelongsTo
    {
        return $this->belongsTo(Ga4Property::class, 'ga4_property_id');
    }
}
