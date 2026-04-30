<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates orders table — per-order record with multi-touch attribution.
 *
 * raw_meta is platform-owned JSONB (fee_lines, customer_note, PYS data).
 * Paired with raw_meta_api_version per CLAUDE.md §JSONB api_version rule.
 * platform_data is Nexstage-owned (Shopify-specific wrapper) — no api_version.
 * attribution_* columns are Nexstage-owned — no api_version.
 *
 * @see docs/planning/schema.md §1.4 orders
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            // External platform customer ID (WooCommerce customer_id integer, Shopify customer GID).
            // NOT a FK to customers.id — analytics uses customer_email_hash for stitching.
            $table->bigInteger('customer_id')->nullable();
            $table->string('external_id', 255);
            $table->string('external_number', 64)->nullable();
            // CHECK constraint below.
            $table->string('status', 16);
            $table->char('currency', 3);
            $table->decimal('total', 14, 2)->default(0);
            // FX-cached at ingest.
            $table->decimal('total_in_reporting_currency', 14, 2)->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('shipping', 14, 2)->default(0);
            // NEW: shipping cost owed by store, resolved via shipping_rules at ingest.
            $table->decimal('shipping_cost_snapshot', 14, 2)->nullable();
            $table->decimal('payment_fee', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            // Denormalised from refunds table.
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->timestamp('last_refunded_at')->nullable();
            // Precomputed flag for NC-ROAS, New Customer Revenue calculations.
            $table->boolean('is_first_for_customer')->default(false);
            // SHA-256 — privacy-preserving stitching + masked-email display.
            $table->char('customer_email_hash', 64)->nullable();
            $table->char('customer_country', 2)->nullable();
            $table->char('shipping_country', 2)->nullable();
            $table->string('payment_method', 64)->nullable();
            $table->string('payment_method_title', 255)->nullable();
            // UTM fields promoted from raw_meta on ingest.
            $table->string('utm_source', 128)->nullable();
            $table->string('utm_medium', 128)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('utm_term', 255)->nullable();
            // WooCommerce 8.5+ Order Attribution source type.
            $table->string('source_type', 64)->nullable();
            // RevenueAttributionService output: computed resolution (facebook/google/organic/direct/…)
            $table->string('attribution_source', 32)->nullable();
            // Multi-touch attribution columns. Nexstage-owned JSONB — no api_version.
            $table->jsonb('attribution_first_touch')->nullable();
            $table->jsonb('attribution_last_touch')->nullable();
            $table->jsonb('attribution_last_non_direct')->nullable();
            // {fbc, fbp, fbclid, gclid, msclkid}
            $table->jsonb('attribution_click_ids')->nullable();
            // Reserved for Fairing-style post-purchase survey (out of scope v1).
            $table->jsonb('attribution_source_survey')->nullable();
            $table->timestamp('attribution_parsed_at')->nullable();
            // Full multi-touch journey array. Each element: {source, medium, campaign, channel,
            // channel_type, landing_page, timestamp_at, fractional_credit}.
            // Populated by future BuildAttributionJourneyJob (WS-A2).
            // @see docs/pages/orders.md touchpoints spec.
            $table->jsonb('attribution_journey')->nullable();
            // false = deterministic (click_id matched an ad_insights record),
            // true = modeled (UTM-only / heuristic / GA4-imputed).
            // Populated by future MarkModeledOrdersJob.
            $table->boolean('is_modeled')->default(false);
            // Granular signal type for is_modeled drill-down.
            // Values: deterministic_click | deterministic_utm | modeled_ga4 |
            //         modeled_referrer | modeled_pys | unattributed
            $table->string('attribution_modeled_signal_type', 32)->nullable();
            // Platform-owned JSONB. Paired with api_version per CLAUDE.md gotcha.
            // Nullable: Shopify orders have no WC raw_meta; {} is the WC empty default.
            $table->jsonb('raw_meta')->nullable()->default('{}');
            $table->string('raw_meta_api_version', 16)->nullable();
            // Nexstage-owned Shopify-specific wrapper — no api_version.
            $table->jsonb('platform_data')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'external_id']);
            $table->index(['workspace_id', 'occurred_at']);
            $table->index(['workspace_id', 'store_id', 'occurred_at']);
            $table->index(['workspace_id', 'status', 'occurred_at']);
            $table->index(['workspace_id', 'customer_country', 'occurred_at']);
            $table->index(['workspace_id', 'shipping_country']);
            $table->index(['store_id', 'synced_at']);
            // /customers tab queries and Customer Journey Timeline.
            $table->index(['workspace_id', 'customer_id', 'occurred_at'], 'idx_orders_customer_date');
        });

        DB::statement("ALTER TABLE orders ADD CONSTRAINT check_orders_status CHECK (status IN ('pending','processing','completed','cancelled','refunded','other'))");

        // Attribution channel filtering.
        DB::statement('CREATE INDEX idx_orders_attribution_source ON orders (workspace_id, attribution_source)');

        // Functional indexes for JSONB attribution fields.
        DB::statement("CREATE INDEX idx_orders_attr_lt_source ON orders ((LOWER(attribution_last_touch->>'source')), workspace_id) WHERE attribution_last_touch IS NOT NULL");
        DB::statement("CREATE INDEX idx_orders_attr_lt_campaign ON orders ((attribution_last_touch->>'campaign'), workspace_id) WHERE attribution_last_touch IS NOT NULL");

        // Partial indexes for hot analytics queries.
        DB::statement("CREATE INDEX idx_orders_ws_occurred_real ON orders (workspace_id, occurred_at) WHERE status IN ('completed','processing')");
        DB::statement("CREATE INDEX idx_orders_attribution_occurred_real ON orders (workspace_id, attribution_source, occurred_at) WHERE status IN ('completed','processing') AND total_in_reporting_currency IS NOT NULL");
        DB::statement('CREATE INDEX idx_orders_first_customer ON orders (workspace_id, is_first_for_customer, occurred_at) WHERE is_first_for_customer = true');
        DB::statement('CREATE INDEX idx_orders_customer_hash_occurred ON orders (workspace_id, customer_email_hash, occurred_at) WHERE customer_email_hash IS NOT NULL');

        // Attribution Time Machine drill.
        DB::statement("CREATE INDEX idx_orders_fbclid ON orders ((attribution_click_ids->>'fbclid'), workspace_id) WHERE attribution_click_ids IS NOT NULL");
        DB::statement("CREATE INDEX idx_orders_gclid ON orders ((attribution_click_ids->>'gclid'), workspace_id) WHERE attribution_click_ids IS NOT NULL");

        // Partial index on customer_email_hash + occurred_at with status partial for Day-30 ROAS self-join.
        DB::statement("CREATE INDEX idx_orders_email_hash_status_occurred ON orders (workspace_id, customer_email_hash, occurred_at) WHERE status IN ('completed','processing') AND customer_email_hash IS NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
