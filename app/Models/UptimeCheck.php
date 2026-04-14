<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Partitioned by month — PostgreSQL declarative partitioning on checked_at.
// DB primary key is composite (id, checked_at) — required by PostgreSQL for partitioned tables.
// Eloquent uses $primaryKey = 'id' for single-column lookups; the composite PK is enforced at DB level only.
//
// Ingested via: POST /api/uptime/report from external probe scripts (Phase 2).
// Read by: EvaluateUptimeJob (Phase 2) for alert detection.
// Related: app/Jobs/CleanupPerformanceDataJob.php (drops old monthly partitions)
// See: PLANNING.md "uptime_checks — Partition management"
#[ScopedBy([WorkspaceScope::class])]
class UptimeCheck extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'store_id',
        'store_url_id',
        'probe_id',
        'checked_at',
        'is_up',
        'status_code',
        'response_time_ms',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'created_at' => 'datetime',
            'is_up' => 'boolean',
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

    public function storeUrl(): BelongsTo
    {
        return $this->belongsTo(StoreUrl::class);
    }
}
