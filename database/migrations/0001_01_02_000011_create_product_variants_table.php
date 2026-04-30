<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates product_variants table — SKU-level variant record.
 *
 * Carries canonical COGS used by order_items.unit_cost ingest default.
 * Received stock columns that were moved from the products table.
 *
 * @see docs/planning/schema.md §1.4 product_variants
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('external_id', 255);
            $table->string('sku', 255)->nullable();
            $table->string('variant_name', 255)->nullable();
            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('cogs_amount', 14, 2)->nullable();
            // CHECK constraint below.
            $table->string('cogs_source', 24)->nullable();
            $table->char('cogs_currency', 3)->nullable();
            $table->string('stock_status', 64)->nullable();
            $table->integer('stock_quantity')->nullable();
            // Avg units/day over the last 28 days, recomputed nightly by SnapshotBuilderService.
            // Days-of-cover = stock_quantity / NULLIF(velocity_28d, 0) — computed at query time, never stored.
            $table->decimal('velocity_28d', 10, 4)->nullable();
            $table->timestamp('platform_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'external_id']);
            $table->index(['workspace_id', 'product_id']);
        });

        DB::statement("ALTER TABLE product_variants ADD CONSTRAINT check_pv_cogs_source CHECK (cogs_source IN ('manual','shopify_cost_per_item','woo_meta','csv_upload'))");

        // Partial index for "Missing COGS" SavedView.
        DB::statement('CREATE INDEX idx_product_variants_missing_cogs ON product_variants (workspace_id) WHERE cogs_amount IS NULL');
        // Partial index for "Stockout Risk" SavedView — workspace scan with stock + velocity present.
        DB::statement('CREATE INDEX idx_product_variants_stockout ON product_variants (workspace_id, stock_quantity, velocity_28d) WHERE stock_quantity IS NOT NULL AND velocity_28d > 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
