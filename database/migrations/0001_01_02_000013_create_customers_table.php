<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates customers table — unified customer record per workspace.
 *
 * Supports /customers RFM, LTV, and cross-store stitching.
 * email_hash is SHA-256 of canonical lower-trimmed email — privacy-preserving.
 *
 * @see docs/planning/schema.md §1.4 customers
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->char('email_hash', 64);
            $table->string('platform_customer_id', 128)->nullable();
            // b***@gmail.com — computed at ingest, safe to render.
            $table->string('display_email_masked', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->timestamp('first_order_at')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->integer('orders_count')->default(0);
            $table->decimal('lifetime_value_native', 14, 2)->default(0);
            $table->decimal('lifetime_value_reporting', 14, 2)->default(0);
            $table->char('country', 2)->nullable();
            $table->string('acquisition_source', 32)->nullable();
            $table->bigInteger('acquisition_campaign_id')->nullable();
            $table->bigInteger('acquisition_product_id')->nullable();
            // User-entered tags (v2 write-back). Nexstage-owned JSONB.
            $table->jsonb('tags')->default('[]');
            $table->timestamps();

            $table->foreign('acquisition_campaign_id')->references('id')->on('campaigns')->nullOnDelete();
            $table->foreign('acquisition_product_id')->references('id')->on('products')->nullOnDelete();

            $table->unique(['workspace_id', 'email_hash']);
            $table->index(['workspace_id', 'first_order_at']);
            $table->index(['workspace_id', 'last_order_at']);
            $table->index(['workspace_id', 'acquisition_source', 'first_order_at']);
            $table->index(['workspace_id', 'store_id']);
        });

        // Partial index for repeat-rate calculation (CustomersDataService::buildMetrics WHERE orders_count > 1).
        DB::statement("CREATE INDEX idx_customers_ws_orders_count ON customers (workspace_id, orders_count) WHERE orders_count > 1");
        // /customers Gateway table: filter by acquisition_product_id + sort by first_order_at.
        DB::statement('CREATE INDEX idx_customers_ws_acq_product_date ON customers (workspace_id, acquisition_product_id, first_order_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
