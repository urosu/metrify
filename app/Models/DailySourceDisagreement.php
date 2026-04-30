<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (workspace, store, date, channel) — the reconciliation delta between
 * store-attributed revenue and platform-reported conversion_value.
 *
 * v1 reconciliation rule: real_revenue = store_claim (store is truth).
 * delta_abs = platform_claim - store_claim.
 * delta_pct = delta_abs / NULLIF(store_claim, 0) * 100.
 *
 * Written by: App\Services\Reconciliation\SourceReconciliationService
 * Read by:    App\Services\Attribution\AttributionDataService::disagreementMatrix()
 * Populated by: App\Jobs\ReconcileSourceDisagreementsJob (nightly 03:30 UTC)
 *
 * @see docs/planning/schema.md §1.X daily_source_disagreements
 * @see docs/planning/backend.md §WS-A2c
 */
#[ScopedBy([WorkspaceScope::class])]
class DailySourceDisagreement extends Model
{
    protected $fillable = [
        'workspace_id',
        'store_id',
        'date',
        'channel',
        'store_claim',
        'platform_claim',
        'real_revenue',
        'delta_abs',
        'delta_pct',
        'match_confidence',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'date'             => 'date',
            'store_claim'      => 'decimal:2',
            'platform_claim'   => 'decimal:2',
            'real_revenue'     => 'decimal:2',
            'delta_abs'        => 'decimal:2',
            'delta_pct'        => 'decimal:2',
            'synced_at'        => 'datetime',
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
