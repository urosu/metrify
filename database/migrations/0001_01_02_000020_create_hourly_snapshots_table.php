<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates hourly_snapshots table — per-store per-date-per-hour aggregate.
 *
 * Used for /dashboard TodaySoFar intra-day widget and /ads DaypartHeatmap.
 * MVP only needs FB/Google/Real per-source columns — hourly is hotter.
 *
 * @see docs/planning/schema.md §1.5 hourly_snapshots
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hourly_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('date');
            $table->smallInteger('hour');
            $table->integer('orders_count')->default(0);
            $table->decimal('revenue', 14, 4)->default(0);
            $table->decimal('revenue_facebook_attributed', 14, 2)->default(0);
            $table->decimal('revenue_google_attributed', 14, 2)->default(0);
            $table->decimal('revenue_real_attributed', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'date', 'hour']);
            $table->index(['workspace_id', 'store_id', 'date']);
        });

        // (workspace_id, date) index for DashboardDataService::todaySoFar() workspace+date-only predicates.
        DB::statement(
            "CREATE INDEX idx_hourly_snapshots_ws_date
             ON hourly_snapshots (workspace_id, date)"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_snapshots');
    }
};
