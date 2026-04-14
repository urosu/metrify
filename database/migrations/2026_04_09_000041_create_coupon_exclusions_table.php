<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Coupons excluded from auto-promotion detection (Phase 2).
        // e.g., employee discounts, permanent loyalty codes that should not trigger
        // workspace_events auto-detection.
        // See: PLANNING.md "coupon_exclusions"
        Schema::create('coupon_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('coupon_code', 255);
            $table->string('reason', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['workspace_id', 'coupon_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_exclusions');
    }
};
