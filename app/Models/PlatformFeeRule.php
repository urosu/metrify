<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-store platform subscription and app fees (e.g. Shopify monthly fee, app costs).
 * Allocated to orders either per-order or amortised per-day.
 *
 * Reads: platform_fee_rules table (workspace-scoped).
 * Writes: PlatformFeeRuleService (Settings → Costs UI).
 * Called by: ProfitCalculator (platform overhead allocation per order/day).
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class PlatformFeeRule extends Model
{
    protected $fillable = [
        'workspace_id',
        'store_id',
        'item_label',
        'monthly_cost_native',
        'currency',
        'allocation_mode',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'monthly_cost_native' => 'decimal:2',
            'effective_from'      => 'date',
            'effective_to'        => 'date',
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
