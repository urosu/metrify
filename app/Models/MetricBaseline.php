<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Phase 2: Precomputed rolling statistics for anomaly detection.
// Populated by: ComputeMetricBaselinesJob (historical backfill on first run, then nightly).
// Read by: DetectAnomaliesJob.
//
// store_id is nullable: NULL = workspace-level metric (e.g. blended_roas, total_revenue).
// Two partial unique indexes enforce uniqueness correctly for nullable store_id.
//
// stability_score = MAD/median ratio — NULL when median = 0 (zero-value weekdays are legitimate).
// When NULL, DetectAnomaliesJob falls back to data_point_count tier only for confidence scoring.
//
// Related: app/Jobs/ComputeMetricBaselinesJob.php, app/Jobs/DetectAnomaliesJob.php
// See: PLANNING.md "metric_baselines"
#[ScopedBy([WorkspaceScope::class])]
class MetricBaseline extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'store_id',
        'metric',
        'weekday',
        'median',
        'mad',
        'data_point_count',
        'stability_score',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
            'median' => 'decimal:4',
            'mad' => 'decimal:4',
            'stability_score' => 'decimal:4',
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
