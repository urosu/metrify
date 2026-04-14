<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Normalized coupon tracking per order. Populated by UpsertWooCommerceOrderAction.
// No WorkspaceScope: tenant isolation guaranteed through order_id → orders → workspace_id.
// Query via $order->coupons() — never directly via OrderCoupon::.
//
// Used for coupon usage aggregation and Phase 2 auto-promotion detection.
// Related: app/Actions/UpsertWooCommerceOrderAction.php, app/Jobs/SyncStoreOrdersJob.php
// See: PLANNING.md "order_coupons"
class OrderCoupon extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'coupon_code',
        'discount_amount',
        'discount_type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
