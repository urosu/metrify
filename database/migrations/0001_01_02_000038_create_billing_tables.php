<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates billing_subscriptions, billing_subscription_items, and billing_revenue_share_usage tables.
 *
 * Renamed from subscriptions/subscription_items to avoid collision with ecommerce domain.
 * Cashier tables tied to workspaces (not users) because billing is workspace-level.
 *
 * @see docs/planning/schema.md §1.12
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('type');
            $table->string('stripe_id')->unique();
            $table->string('stripe_status');
            $table->string('stripe_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'stripe_status']);
        });

        Schema::create('billing_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('billing_subscriptions')->cascadeOnDelete();
            $table->string('stripe_id')->unique();
            $table->string('stripe_product')->nullable();
            $table->string('stripe_price');
            $table->integer('quantity')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'stripe_price']);
        });

        Schema::create('billing_revenue_share_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            // First-of-month date.
            $table->date('period_month');
            $table->char('reporting_currency', 3);
            $table->decimal('gross_revenue_reporting', 14, 2);
            $table->decimal('billable_revenue_reporting', 14, 2);
            // 0.4% = 40 basis points.
            $table->smallInteger('rate_bps')->default(40);
            $table->decimal('computed_amount_reporting', 14, 2);
            $table->timestamp('reported_to_stripe_at')->nullable();
            $table->string('stripe_usage_record_id', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['workspace_id', 'period_month']);
            $table->index(['workspace_id', 'reported_to_stripe_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_revenue_share_usage');
        Schema::dropIfExists('billing_subscription_items');
        Schema::dropIfExists('billing_subscriptions');
    }
};
