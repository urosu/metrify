<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Aggregated from uptime_checks before raw checks are deleted during monthly partition cleanup.
// Populated by: CleanupPerformanceDataJob (Phase 2) before dropping each monthly partition.
// Read by: PerformanceController uptime panel.
// Related: app/Jobs/CleanupPerformanceDataJob.php
#[ScopedBy([WorkspaceScope::class])]
class UptimeDailySummary extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'store_url_id',
        'workspace_id',
        'date',
        'checks_total',
        'checks_up',
        'uptime_pct',
        'avg_response_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'created_at' => 'datetime',
            'uptime_pct' => 'decimal:2',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function storeUrl(): BelongsTo
    {
        return $this->belongsTo(StoreUrl::class);
    }
}
