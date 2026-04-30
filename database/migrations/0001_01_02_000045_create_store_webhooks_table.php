<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates store_webhooks table — per-store webhook registration audit trail.
 *
 * Tracks which topics we've subscribed to on the platform (not a delivery log —
 * that's integration_events). Soft-deleted for audit trail.
 *
 * @see docs/planning/schema.md §1.10 store_webhooks
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('platform_webhook_id', 255);
            $table->string('topic', 255);
            // Updated on every successfully processed delivery.
            // PollStoreOrdersJob reads this to skip polling if a webhook arrived recently.
            $table->timestamp('last_successful_delivery_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->unique(['store_id', 'platform_webhook_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_webhooks');
    }
};
