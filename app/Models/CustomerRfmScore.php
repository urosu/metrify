<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nightly RFM (Recency, Frequency, Monetary) scores and segment assignments per customer.
 * One row per (workspace_id, customer_id, computed_for) date — historical scores are kept.
 *
 * Reads: customer_rfm_scores table (workspace-scoped).
 * Writes: ComputeRfmScoresJob (nightly batch); ComputePaybackDaysJob writes
 *         cac_at_acquisition + payback_days on the latest row per customer.
 * Called by: CustomersController for segment breakdowns; Customer model relationship.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 * @see app/Jobs/ComputeRfmScoresJob.php
 * @see app/Jobs/ComputePaybackDaysJob.php
 */
#[ScopedBy([WorkspaceScope::class])]
class CustomerRfmScore extends Model
{
    // No updated_at — append-only scoring table; only created_at is used.
    const UPDATED_AT = null;

    protected $fillable = [
        'workspace_id',
        'customer_id',
        'computed_for',
        'recency_score',
        'frequency_score',
        'monetary_score',
        'segment',
        'churn_risk',
        'predicted_next_order_at',
        'predicted_ltv_reporting',
        'predicted_ltv_confidence',
        'model_version',
        // Written by ComputePaybackDaysJob (nightly, after RFM scoring).
        'cac_at_acquisition',
        'payback_days',
    ];

    protected function casts(): array
    {
        return [
            'computed_for'             => 'date',
            'recency_score'            => 'integer',
            'frequency_score'          => 'integer',
            'monetary_score'           => 'integer',
            'churn_risk'               => 'integer',
            'predicted_next_order_at'  => 'datetime',
            'predicted_ltv_reporting'  => 'decimal:2',
            'predicted_ltv_confidence' => 'integer',
            'cac_at_acquisition'       => 'decimal:2',
            'payback_days'             => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
