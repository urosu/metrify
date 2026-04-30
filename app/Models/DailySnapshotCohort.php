<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([WorkspaceScope::class])]
class DailySnapshotCohort extends Model
{
    protected $table = 'daily_snapshot_cohorts';

    protected $fillable = [
        'workspace_id',
        'cohort_period',
        'period_offset',
        'customers_active',
        'revenue',
        'orders_count',
    ];

    protected function casts(): array
    {
        return [
            'cohort_period'    => 'date',
            'period_offset'    => 'integer',
            'customers_active' => 'integer',
            'revenue'          => 'decimal:4',
            'orders_count'     => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
