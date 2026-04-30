<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-order GA4 attribution row matched via orders.external_id = transaction_id.
 *
 * GA4 receives the order's platform ID as the transactionId ecommerce event
 * parameter. This table stores what GA4 knows about the session that produced
 * the purchase, enabling GA4Source to enrich orders.attribution_first_touch /
 * attribution_last_touch when store-side parsers have weaker data.
 *
 * Unique key: (ga4_property_id, transaction_id) — one row per purchase per property.
 *
 * Reads by: GA4Source (attribution enrichment), SyncGA4OrderAttributionJob
 * Writes by: SyncGA4OrderAttributionJob
 *
 * @see docs/planning/schema.md §1.9 (ga4_order_attribution)
 * @see app/Services/Attribution/Sources/GA4Source.php
 * @see app/Jobs/SyncGA4OrderAttributionJob.php
 */
#[ScopedBy([WorkspaceScope::class])]
class Ga4OrderAttribution extends Model
{
    protected $table = 'ga4_order_attribution';

    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'ga4_property_id',
        'transaction_id',
        'date',
        'session_source',
        'session_medium',
        'session_campaign',
        'session_default_channel_group',
        'first_user_source',
        'first_user_medium',
        'first_user_campaign',
        'landing_page',
        'conversion_value',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'date'             => 'date',
            'synced_at'        => 'datetime',
            'conversion_value' => 'decimal:4',
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
