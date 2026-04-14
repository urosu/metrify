<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Many-to-many pivot between products and product_categories.
        // No workspace_id: tenant isolation is guaranteed via product_id → products → workspace_id.
        // No timestamps: pivot rows are replaced wholesale on each sync.
        Schema::create('product_category_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();

            $table->primary(['product_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category_product');
    }
};
