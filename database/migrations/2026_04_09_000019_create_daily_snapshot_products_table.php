<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Replaces daily_snapshots.top_products JSONB — top 50 products per store per day by revenue.
        // Populated by: ComputeDailySnapshotJob (writes top 50 per store per day).
        // Read by: Products analytics page controller.
        // Related: app/Jobs/ComputeDailySnapshotJob.php
        Schema::create('daily_snapshot_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->string('product_external_id', 255);
            $table->string('product_name', 500);
            $table->decimal('revenue', 14, 4);
            $table->integer('units')->default(0);
            $table->smallInteger('rank');
            $table->timestamp('created_at')->nullable();

            $table->unique(['store_id', 'snapshot_date', 'product_external_id']);
            $table->index(['workspace_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_snapshot_products');
    }
};
