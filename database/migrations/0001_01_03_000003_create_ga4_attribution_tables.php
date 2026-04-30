<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates ga4_daily_attribution, ga4_order_attribution, and ga4_product_page_views tables.
 *
 * GA4 is now a first-class attribution source equal to Facebook/Google/GSC.
 * ga4_daily_attribution holds per-dimension session + conversion aggregates
 * for channel-level analysis. ga4_order_attribution holds per-transaction GA4
 * attribution so GA4Source can enrich orders.attribution_first_touch / _last_touch
 * when store-side parsers have weaker data. ga4_product_page_views holds per-item
 * ecommerce event counts (view_item, add_to_cart, purchase) for the products
 * traffic-vs-conversion quadrant and the Dashboard funnel's middle step.
 *
 * row_signature on ga4_daily_attribution: SHA-256 hash of the composite key
 * dimensions, truncated to 64 chars. This avoids a very wide unique index over
 * 11 nullable text columns. The hash is built in SyncGA4AttributionJob before upsert.
 *
 * @see docs/planning/schema.md §1.9 (ga4 tables)
 * @see app/Jobs/SyncGA4AttributionJob.php
 * @see app/Jobs/SyncGA4OrderAttributionJob.php
 * @see app/Jobs/SyncGA4ProductViewsJob.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ga4_daily_attribution', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('ga4_property_id')->constrained('ga4_properties')->cascadeOnDelete();
            $table->date('date');

            // Session-level attribution dimensions
            $table->string('session_source', 255)->nullable();
            $table->string('session_medium', 255)->nullable();
            $table->string('session_campaign', 255)->nullable();
            $table->string('session_default_channel_group', 64)->nullable();

            // First-user dimensions (first-touch at the user level)
            $table->string('first_user_source', 255)->nullable();
            $table->string('first_user_medium', 255)->nullable();
            $table->string('first_user_campaign', 255)->nullable();

            $table->text('landing_page')->nullable();
            $table->string('device_category', 16)->nullable();
            $table->char('country_code', 2)->nullable();

            // Metrics
            $table->integer('sessions')->default(0);
            $table->integer('active_users')->default(0);
            $table->integer('engaged_sessions')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('total_revenue', 14, 4)->default(0);

            // SHA-256 hash of composite dimension key (see SyncGA4AttributionJob::rowSignature).
            // Replaces an 11-column unique index.
            $table->string('row_signature', 64);

            // CHECK constraint below.
            $table->string('data_state', 16)->default('final');
            $table->timestamp('synced_at');
            $table->timestamp('created_at')->nullable();

            $table->unique(['ga4_property_id', 'date', 'row_signature']);
            $table->index(['workspace_id', 'date']);
        });

        DB::statement("ALTER TABLE ga4_daily_attribution ADD CONSTRAINT check_ga4da_data_state CHECK (data_state IN ('final','provisional'))");

        // Composite index for channel-breakdown analytics queries.
        Schema::table('ga4_daily_attribution', function (Blueprint $table) {
            $table->index(
                ['workspace_id', 'date', 'session_default_channel_group'],
                'idx_ga4_daily_ws_date_channel'
            );
        });

        Schema::create('ga4_order_attribution', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('ga4_property_id')->constrained('ga4_properties')->cascadeOnDelete();

            // Matches orders.external_id (the platform's order/transaction ID sent to GA4).
            $table->string('transaction_id', 255);
            $table->date('date');

            // Session-level attribution dimensions
            $table->string('session_source', 255)->nullable();
            $table->string('session_medium', 255)->nullable();
            $table->string('session_campaign', 255)->nullable();
            $table->string('session_default_channel_group', 64)->nullable();

            // First-user dimensions
            $table->string('first_user_source', 255)->nullable();
            $table->string('first_user_medium', 255)->nullable();
            $table->string('first_user_campaign', 255)->nullable();

            $table->text('landing_page')->nullable();
            $table->decimal('conversion_value', 14, 4)->default(0);

            $table->timestamp('synced_at');

            // One row per property + transaction; no separate created_at needed.
            $table->unique(['ga4_property_id', 'transaction_id']);
            $table->index(['workspace_id', 'transaction_id']);
        });

        // Per-item ecommerce event counts from GA4 enhanced ecommerce (view_item,
        // add_to_cart, purchase events). item_id matches products.external_id so
        // the products page can join views → purchases per SKU without a GA4 property
        // lookup at query time. Powers the Dashboard funnel middle step and the
        // /products Traffic vs Conversion quadrant chart.
        Schema::create('ga4_product_page_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('ga4_property_id')->constrained('ga4_properties')->cascadeOnDelete();
            $table->date('date');

            // GA4 itemName / itemId dimensions from the items array.
            // item_id is expected to match products.external_id for Shopify/WooCommerce.
            $table->string('item_name', 500)->nullable();
            $table->string('item_id', 255)->nullable();

            // Ecommerce event counts
            $table->integer('item_views')->default(0);
            $table->integer('items_added_to_cart')->default(0);
            $table->integer('items_purchased')->default(0);

            // SHA-256 hash of (date + item_name + item_id) — same pattern as ga4_daily_attribution.
            $table->string('row_signature', 64);
            $table->string('data_state', 16)->default('final');
            $table->timestamp('synced_at');
            $table->timestamp('created_at')->nullable();

            $table->unique(['ga4_property_id', 'date', 'row_signature']);
            $table->index(['workspace_id', 'date']);
            $table->index(['workspace_id', 'item_id']);
        });

        DB::statement("ALTER TABLE ga4_product_page_views ADD CONSTRAINT check_ga4ppv_data_state CHECK (data_state IN ('final','provisional'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ga4_product_page_views');
        Schema::dropIfExists('ga4_order_attribution');
        Schema::dropIfExists('ga4_daily_attribution');
    }
};
