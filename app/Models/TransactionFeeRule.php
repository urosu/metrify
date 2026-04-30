<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-store payment processor fee rules (percentage in basis points + fixed fee).
 * Global seeded defaults (is_seeded=true) are overridden by workspace rows with the same processor.
 *
 * Reads: transaction_fee_rules table (workspace-scoped).
 * Writes: TransactionFeeRuleService (Settings → Costs UI); seeder provides defaults.
 * Called by: ProfitCalculator (payment fee deduction per order).
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class TransactionFeeRule extends Model
{
    protected $fillable = [
        'workspace_id',
        'store_id',
        'processor',
        'percentage_bps',
        'fixed_fee_native',
        'currency',
        'applies_to_payment_method',
        'is_seeded',
    ];

    protected function casts(): array
    {
        return [
            // 290 = 2.9% — stored as basis points per schema note
            'percentage_bps'   => 'integer',
            'fixed_fee_native' => 'decimal:4',
            'is_seeded'        => 'boolean',
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
