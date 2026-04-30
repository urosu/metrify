<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per date × item_name × item_id combination for a GA4 property.
 *
 * Populated from GA4 enhanced ecommerce events: view_item, add_to_cart, purchase.
 * item_id is expected to match products.external_id so ProductsDataService can
 * join views → purchases per SKU without a runtime GA4 property lookup.
 *
 * Powers: Dashboard funnel middle step (product views), /products Traffic vs
 * Conversion quadrant chart (x: views, y: view_cvr).
 *
 * row_signature: SHA-256 hash of (date + item_name + item_id) — same pattern as
 * Ga4DailyAttribution. Avoids a wide unique index over nullable text columns.
 *
 * data_state 'provisional' = date is < 3 days old and GA4 may revise it.
 * SyncGA4ProductViewsJob re-syncs the rolling 3-day window nightly.
 *
 * Reads by: DashboardDataService::funnel() (Product Views step), ProductsDataService (views/CVR)
 * Writes by: SyncGA4ProductViewsJob
 *
 * @see docs/planning/schema.md §1.9 (ga4 tables)
 * @see app/Jobs/SyncGA4ProductViewsJob.php
 */
#[ScopedBy([WorkspaceScope::class])]
class Ga4ProductPageView extends Model
{
    protected $table = 'ga4_product_page_views';

    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'ga4_property_id',
        'date',
        'item_name',
        'item_id',
        'item_views',
        'items_added_to_cart',
        'items_purchased',
        'row_signature',
        'data_state',
        'synced_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'date'       => 'date',
            'synced_at'  => 'datetime',
            'created_at' => 'datetime',
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
