<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stitched customer identity, deduplicated by email_hash within a workspace.
 * email_hash is SHA-256(lowercase(email)) — PII never stored.
 *
 * Reads: customers table (workspace-scoped).
 * Writes: CustomerStitchingJob (on order ingest); CustomerLtvUpdateJob (nightly).
 * Called by: CustomersController, CustomerRfmScore scoring jobs, Order model via customer_id FK.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class Customer extends Model
{
    protected $fillable = [
        'workspace_id',
        'store_id',
        'email_hash',
        'platform_customer_id',
        'display_email_masked',
        'name',
        'first_order_at',
        'last_order_at',
        'orders_count',
        'lifetime_value_native',
        'lifetime_value_reporting',
        'country',
        'acquisition_source',
        'acquisition_campaign_id',
        'acquisition_product_id',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'first_order_at'           => 'datetime',
            'last_order_at'            => 'datetime',
            'orders_count'             => 'integer',
            'lifetime_value_native'    => 'decimal:2',
            'lifetime_value_reporting' => 'decimal:2',
            'tags'                     => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function acquisitionCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'acquisition_campaign_id');
    }

    public function acquisitionProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'acquisition_product_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function rfmScores(): HasMany
    {
        return $this->hasMany(CustomerRfmScore::class);
    }
}
