<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-store weight-tiered shipping cost rules, optionally scoped to a destination country.
 *
 * Reads: shipping_rules table (workspace-scoped).
 * Writes: ShippingRuleService (Settings → Costs UI).
 * Called by: ProfitCalculator (weight_tiered shipping mode lookup).
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class ShippingRule extends Model
{
    protected $fillable = [
        'workspace_id',
        'store_id',
        'min_weight_grams',
        'max_weight_grams',
        'destination_country',
        'cost_native',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'min_weight_grams' => 'integer',
            'max_weight_grams' => 'integer',
            'cost_native'      => 'decimal:2',
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
}
