<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('coupon_code', 255);
            $table->decimal('discount_amount', 12, 2);
            $table->string('discount_type', 50)->nullable();  // percent, fixed_cart, fixed_product
            $table->timestamp('created_at')->nullable();

            $table->index('order_id');
            // For coupon usage aggregation queries (how many uses, total discount per coupon).
            $table->index(['coupon_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_coupons');
    }
};
