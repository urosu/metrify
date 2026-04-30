<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates daily_snapshot_products table — top 50 products per store per day.
 *
 * Populated by ComputeDailySnapshotJob. Read by /products page controller.
 *
 * @see docs/planning/schema.md §1.5 daily_snapshot_products
 */
return new class extends Migration
{
    public function up(): void
    {
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
            // COGS snapshot — for Shopify stores where unit cost is on InventoryItem.
            $table->decimal('unit_cost', 12, 4)->nullable();
            // Stock state at snapshot time.
            $table->string('stock_status', 20)->nullable();
            $table->integer('stock_quantity')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['store_id', 'snapshot_date', 'product_external_id']);
            $table->index(['workspace_id', 'snapshot_date']);
        });

        // Product analytics queries filter/group by product across date ranges.
        DB::statement('CREATE INDEX idx_dsp_ws_product_date ON daily_snapshot_products (workspace_id, product_external_id, snapshot_date)');

        // Covering index for revenue/units aggregate query (ProductsDataService::loadSnapshotAggregates).
        // INCLUDE avoids heap fetches during index-only scans.
        DB::statement("CREATE INDEX idx_dsp_ws_date_covering ON daily_snapshot_products (workspace_id, snapshot_date) INCLUDE (revenue, units)");
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_snapshot_products');
    }
};
