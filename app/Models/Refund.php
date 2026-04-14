<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Individual refund events. Populated by SyncRecentRefundsJob (last 7 days, nightly).
// After upsert here, orders.refund_amount and orders.last_refunded_at are updated.
//
// raw_meta: full refund response from platform, captured for Phase 2+ analysis.
//
// Related: app/Jobs/SyncRecentRefundsJob.php
// See: PLANNING.md "refunds"
#[ScopedBy([WorkspaceScope::class])]
class Refund extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'workspace_id',
        'platform_refund_id',
        'amount',
        'reason',
        'refunded_by_id',
        'refunded_at',
        'raw_meta',
        'raw_meta_api_version',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'refunded_at' => 'datetime',
            'created_at' => 'datetime',
            'amount' => 'decimal:2',
            'raw_meta' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
