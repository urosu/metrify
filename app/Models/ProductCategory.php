<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Product category hierarchy. Populated by SyncProductsJob.
//
// parent_external_id references another category's external_id (NOT a FK) to avoid ordering
// issues during upsert. Resolve hierarchy in application code when needed.
//
// Related: app/Jobs/SyncProductsJob.php
// See: PLANNING.md "product_categories"
#[ScopedBy([WorkspaceScope::class])]
class ProductCategory extends Model
{
    protected $fillable = [
        'workspace_id',
        'store_id',
        'external_id',
        'name',
        'slug',
        'parent_external_id',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category_product', 'category_id', 'product_id');
    }
}
