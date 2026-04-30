<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Eloquent model for historical_import_jobs — the canonical source of import progress
 * state for all four job types (shopify_orders, woocommerce_orders, ad_insights, gsc).
 *
 * Replaces the scattered historical_import_* columns that were dropped from stores,
 * ad_accounts, and search_console_properties in the L2 schema rebuild.
 *
 * One row per backfill attempt. Completed rows are kept for audit; failed rows can be
 * deleted (or marked cancelled) by resetImport() flows so the user can re-pick a range.
 *
 * Reads: OnboardingController (step detection), EnsureOnboardingComplete (middleware),
 *        ImportStatusController (polling), StoreSetupController (in-app add-store flow).
 * Writes: StartHistoricalImportAction (creates row), import jobs (update status/progress).
 *
 * @see docs/planning/schema.md §1.10 historical_import_jobs
 * @see app/Actions/StartHistoricalImportAction.php
 */
#[ScopedBy([WorkspaceScope::class])]
class HistoricalImportJob extends Model
{
    protected $fillable = [
        'workspace_id',
        'integrationable_type',
        'integrationable_id',
        'job_type',
        'status',
        'from_date',
        'to_date',
        'total_rows_estimated',
        'total_rows_imported',
        'progress_pct',
        'checkpoint',
        'started_at',
        'completed_at',
        'duration_seconds',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'from_date'    => 'date',
            'to_date'      => 'date',
            'checkpoint'   => 'array',
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function integrationable(): MorphTo
    {
        return $this->morphTo();
    }
}
