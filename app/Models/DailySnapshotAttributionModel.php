<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([WorkspaceScope::class])]
class DailySnapshotAttributionModel extends Model
{
    protected $table = 'daily_snapshot_attribution_models';

    protected $fillable = [
        'workspace_id',
        'date',
        'channel_id',
        'model',
        'revenue',
        'orders',
        'customers',
    ];

    protected function casts(): array
    {
        return [
            'date'     => 'date',
            'revenue'  => 'decimal:4',
            'orders'   => 'integer',
            'customers' => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
