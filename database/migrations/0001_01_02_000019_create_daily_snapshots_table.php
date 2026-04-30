<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates daily_snapshots table — per-store per-day aggregate.
 *
 * The aggregation truth for all page queries. Never SUM raw orders on request path.
 * Includes per-source revenue columns for TrustBar, Source Disagreement Matrix, and
 * Attribution Time Machine. Includes profit components for ProfitWaterfallChart.
 *
 * @see docs/planning/schema.md §1.5 daily_snapshots
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('date');
            $table->integer('orders_count')->default(0);
            // reporting currency; inclusive of tax.
            $table->decimal('revenue', 14, 4)->default(0);
            $table->decimal('revenue_native', 14, 4)->default(0);
            $table->integer('items_sold')->default(0);
            $table->integer('new_customers')->default(0);
            $table->integer('returning_customers')->default(0);
            $table->decimal('new_customer_revenue', 14, 4)->default(0);
            $table->unsignedInteger('organic_orders_count')->default(0);
            // Per-source attributed revenue columns.
            // revenue_store_attributed: store-native attribution (WC Order Attribution / Shopify native).
            // Populated once WS-F ga4_order_attribution join lands for the GA4 source.
            $table->decimal('revenue_store_attributed', 14, 2)->nullable();
            $table->decimal('revenue_facebook_attributed', 14, 2)->default(0);
            $table->decimal('revenue_google_attributed', 14, 2)->default(0);
            $table->decimal('revenue_gsc_attributed', 14, 2)->default(0);
            $table->decimal('revenue_ga4_attributed', 14, 2)->nullable();
            $table->decimal('revenue_direct_attributed', 14, 2)->default(0);
            $table->decimal('revenue_organic_attributed', 14, 2)->default(0);
            $table->decimal('revenue_email_attributed', 14, 2)->default(0);
            $table->decimal('revenue_real_attributed', 14, 2)->default(0);
            // Profit/cost component columns.
            $table->decimal('discounts_total', 14, 2)->default(0);
            $table->decimal('refunds_total', 14, 2)->default(0);
            // NULL if any order_items row is missing COGS.
            $table->decimal('cogs_total', 14, 2)->nullable();
            $table->decimal('shipping_cost_total', 14, 2)->default(0);
            $table->decimal('shipping_revenue_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('transaction_fees_total', 14, 2)->default(0);
            $table->decimal('ad_spend', 14, 4)->default(0);
            // NULL when any COGS data is missing.
            $table->decimal('gross_profit', 14, 2)->nullable();
            // Shopify native → GA4 → null for Shopify stores; GA4 → null for WC stores.
            $table->integer('sessions')->nullable();
            // CHECK constraint below.
            $table->string('sessions_source', 16)->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'date']);
            $table->index(['workspace_id', 'date']);
            $table->index(['workspace_id', 'store_id', 'date']);
        });

        DB::statement("ALTER TABLE daily_snapshots ADD CONSTRAINT check_ds_sessions_source CHECK (sessions_source IN ('shopify','ga4'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_snapshots');
    }
};
