<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Related: app/Jobs/RunLighthouseCheckJob.php (Phase 1, reads active URLs to check)
// Related: app/Jobs/CleanupPerformanceDataJob.php (Phase 2, maintains uptime partitions)
#[ScopedBy([WorkspaceScope::class])]
class StoreUrl extends Model
{
    protected $fillable = [
        'workspace_id',
        'store_id',
        'url',
        'label',
        'is_homepage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_homepage' => 'boolean',
            'is_active' => 'boolean',
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

    public function lighthouseSnapshots(): HasMany
    {
        return $this->hasMany(LighthouseSnapshot::class);
    }

    public function uptimeChecks(): HasMany
    {
        return $this->hasMany(UptimeCheck::class);
    }

    public function uptimeDailySummaries(): HasMany
    {
        return $this->hasMany(UptimeDailySummary::class);
    }
}
