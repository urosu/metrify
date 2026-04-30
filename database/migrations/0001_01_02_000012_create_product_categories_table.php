<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates product_categories and product_category_product tables.
 *
 * @see docs/planning/schema.md §1.4 product_categories
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('external_id', 255);
            $table->string('name', 500);
            $table->string('slug', 500)->nullable();
            // Self-referencing via external ID (not FK) to avoid ordering issues during upsert.
            $table->string('parent_external_id', 255)->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'external_id']);
        });

        // Many-to-many pivot: products ↔ product_categories.
        // No workspace_id: tenant isolation guaranteed via product_id → products → workspace_id.
        Schema::create('product_category_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();

            $table->primary(['product_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category_product');
        Schema::dropIfExists('product_categories');
    }
};
