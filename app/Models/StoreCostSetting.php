<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-store cost configuration: shipping mode, flat rates, currency, and completeness score.
 * Replaces the former cost_settings JSONB column on the stores table.
 *
 * Reads: store_cost_settings table (workspace-scoped).
 * Writes: UpdateCostConfigAction; triggers RecomputeAttributionJob + snapshot rebuilds on change.
 * Called by: ProfitCalculator; CostsController; UpdateCostConfigAction.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class StoreCostSetting extends Model
{
    protected $fillable = [
        'workspace_id',
        'store_id',
        'shipping_mode',
        'shipping_flat_rate_native',
        'shipping_per_order_native',
        'default_currency',
        'completeness_score',
        'last_recalculated_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_flat_rate_native'  => 'decimal:2',
            'shipping_per_order_native'  => 'decimal:2',
            'completeness_score'         => 'integer',
            'last_recalculated_at'       => 'datetime',
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
