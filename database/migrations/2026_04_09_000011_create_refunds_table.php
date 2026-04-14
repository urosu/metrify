<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Individual refund events. Populated by SyncRecentRefundsJob (last 7 days, nightly).
        // After upsert here, orders.refund_amount and orders.last_refunded_at are updated.
        // Related: app/Jobs/SyncRecentRefundsJob.php
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('platform_refund_id', 255);
            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();
            $table->string('refunded_by_id', 255)->nullable();
            $table->timestamp('refunded_at');

            // Full refund response for Phase 2+ analysis.
            $table->jsonb('raw_meta')->nullable();
            $table->string('raw_meta_api_version', 20)->nullable();

            $table->timestamp('created_at')->nullable();

            $table->unique(['order_id', 'platform_refund_id']);
            $table->index(['workspace_id', 'refunded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
