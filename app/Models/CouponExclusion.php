<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Phase 2: Coupons excluded from auto-promotion detection.
// e.g., employee discounts, permanent loyalty codes that should not trigger
// workspace_events auto-detection.
// Related: app/Jobs/DetectAnomaliesJob.php (Phase 2)
// See: PLANNING.md "coupon_exclusions"
#[ScopedBy([WorkspaceScope::class])]
class CouponExclusion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'coupon_code',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
