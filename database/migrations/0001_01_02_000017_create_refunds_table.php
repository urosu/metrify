<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates refunds table — individual refund events.
 *
 * raw_meta is platform-owned JSONB — paired with raw_meta_api_version
 * per CLAUDE.md §JSONB api_version rule.
 *
 * @see docs/planning/schema.md §1.4 refunds
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('platform_refund_id', 255);
            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();
            $table->string('refunded_by_id', 255)->nullable();
            $table->timestamp('refunded_at');
            // Platform-owned JSONB. Paired with api_version per CLAUDE.md gotcha.
            $table->jsonb('raw_meta')->nullable();
            $table->string('raw_meta_api_version', 16)->nullable();
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
