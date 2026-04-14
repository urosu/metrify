<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            // Resolve hierarchy in application code when needed.
            $table->string('parent_external_id', 255)->nullable();

            $table->timestamps();

            $table->unique(['store_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
