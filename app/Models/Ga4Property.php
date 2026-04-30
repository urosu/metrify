<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GA4 property config + sync state.
 *
 * Credentials stored in `integration_credentials` polymorphic (same Google OAuth
 * flow as GoogleAdsClient; scope `analytics.readonly`).
 *
 * @see docs/planning/schema.md §1.9 (ga4_properties table)
 */
#[ScopedBy([WorkspaceScope::class])]
class Ga4Property extends Model
{
    protected $table = 'ga4_properties';

    protected $fillable = [
        'workspace_id',
        'property_id',
        'property_name',
        'measurement_id',
        'status',
        'consecutive_sync_failures',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at'            => 'datetime',
            'consecutive_sync_failures' => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
