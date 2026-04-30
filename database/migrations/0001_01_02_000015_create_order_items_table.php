<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates order_items table — line items per order.
 *
 * Includes product_id and product_variant_id FKs added in the v1 refactor.
 * unit_cost is the COGS snapshot at order time — immutable after ingest.
 *
 * @see docs/planning/schema.md §1.4 order_items
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // FK to products/product_variants — nullable until backfilled from product_external_id.
            $table->bigInteger('product_id')->nullable();
            $table->bigInteger('product_variant_id')->nullable();
            $table->string('product_external_id', 255);
            $table->string('product_name', 500);
            $table->string('variant_name', 255)->nullable();
            $table->string('sku', 255)->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 4);
            // COGS snapshot at order time — immutable. NULL when no COGS source configured.
            $table->decimal('unit_cost', 12, 4)->nullable();
            $table->decimal('discount_amount', 12, 4)->nullable();
            $table->decimal('line_total', 12, 4);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();

            $table->index('order_id');
            $table->index('product_id', 'idx_order_items_product_id');
            $table->index('product_variant_id', 'idx_order_items_variant_id');
        });

        // Functional unique index for upserts: COALESCE normalizes NULL variant_name for uniqueness.
        DB::statement("CREATE UNIQUE INDEX order_items_upsert_key ON order_items (order_id, product_external_id, COALESCE(variant_name, ''))");

        // Analytics indexes.
        DB::statement('CREATE INDEX idx_order_items_product_external_id ON order_items (product_external_id)');
        DB::statement('CREATE INDEX idx_order_items_order_product ON order_items (order_id, product_external_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
