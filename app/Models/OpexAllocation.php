<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Operating expense allocation entries (staff, software, logistics overhead, etc.)
 * Allocated to orders either per-order or amortised per-day within the effective date range.
 *
 * Reads: opex_allocations table (workspace-scoped).
 * Writes: OpexAllocationService (Settings → Costs UI).
 * Called by: ProfitCalculator (OpEx overhead per order/day within effective_from/to range).
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class OpexAllocation extends Model
{
    protected $fillable = [
        'workspace_id',
        'category',
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
}
