<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SKU-level COGS, pricing, and stock data for a product variant.
 * Scoped to workspace via product's workspace_id; also carries explicit workspace_id for indexing.
 *
 * Reads: product_variants table (workspace-scoped).
 * Writes: ProductSyncJob (Shopify cost_per_item / WC meta); COGS CSV upload action.
 * Called by: ProfitCalculator for per-line-item COGS; ProductsController variant detail.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class ProductVariant extends Model
{
    protected $fillable = [
        'workspace_id',
        'store_id',
        'product_id',
        'external_id',
        'sku',
        'variant_name',
        'price',
        'cogs_amount',
        'cogs_source',
        'cogs_currency',
        'stock_status',
        'stock_quantity',
        'platform_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'price'               => 'decimal:2',
            'cogs_amount'         => 'decimal:2',
            'stock_quantity'      => 'integer',
            'platform_updated_at' => 'datetime',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
