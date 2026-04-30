<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-country/state tax rates. Global seeded rows have workspace_id=NULL (is_seeded=true);
 * workspace rows with matching country_code override the global default.
 *
 * Note: workspace_id is nullable; global rows (workspace_id=NULL) are used as fallbacks.
 * Query with ->withoutGlobalScopes() when reading global defaults or cross-workspace data.
 * The WorkspaceScope is still applied to ensure workspace rows are correctly scoped when
 * a workspace context is set.
 *
 * Reads: tax_rules table.
 * Writes: TaxRuleService (Settings → Costs UI); TaxRulesSeeder for global defaults.
 * Called by: ProfitCalculator (VAT/tax deduction); CostsController (Settings → Costs UI).
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class TaxRule extends Model
{
    protected $fillable = [
        'workspace_id',
        'country_code',
        'standard_rate_bps',
        'reduced_rate_bps',
        'is_included_in_price',
        'digital_goods_override_bps',
        'is_seeded',
    ];

    protected function casts(): array
    {
        return [
            // Stored as basis points; 2000 = 20% VAT
            'standard_rate_bps'           => 'integer',
            'reduced_rate_bps'            => 'integer',
            'digital_goods_override_bps'  => 'integer',
            'is_included_in_price'        => 'boolean',
            'is_seeded'                   => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
