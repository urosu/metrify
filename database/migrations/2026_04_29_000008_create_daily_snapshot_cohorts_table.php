<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates daily_snapshot_cohorts — pre-computed cohort retention data.
 *
 * Eliminates the expensive raw orders CTE query in CohortController that scans all
 * orders per acquisition period × period offset on every page load.
 *
 * Each row represents one (workspace, cohort_period, period_offset) tuple and stores
 * the aggregated customers, revenue, and order count for that cohort in that period.
 *
 * cohort_period  — first-order month (DATE_TRUNC('month', first_order_at)), stored as
 *                  a date (always the 1st of the month, e.g. 2024-11-01).
 * period_offset  — months/weeks since cohort start (0 = acquisition period).
 *
 * Populated by: BuildCohortSnapshotJob (chained from the daily snapshot pipeline,
 *               or triggered by a nightly recalc job).
 * Read by:      CohortController::__invoke() (when date range is fully covered).
 *
 * @see app/Jobs/BuildCohortSnapshotJob.php
 * @see app/Http/Controllers/CohortController.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_snapshot_cohorts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');

            // First-order month (truncated to Y-m-01) — the cohort identifier.
            $table->date('cohort_period');

            // 0 = acquisition month, 1 = next month, etc.
            $table->unsignedSmallInteger('period_offset');

            // Aggregated metrics for this cohort × offset cell.
            $table->unsignedInteger('customers_active')->default(0);
            $table->decimal('revenue', 14, 4)->default(0);
            $table->unsignedInteger('orders_count')->default(0);

            $table->timestamps();

            // Unique: one row per (workspace, cohort_period, offset).
            $table->unique(
                ['workspace_id', 'cohort_period', 'period_offset'],
                'dsc_ws_cohort_offset_unique',
            );

            // Index for range queries (workspace + cohort_period range).
            $table->index(
                ['workspace_id', 'cohort_period', 'period_offset'],
                'dsc_workspace_cohort_period_index',
            );

            $table->foreign('workspace_id')
                ->references('id')->on('workspaces')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_snapshot_cohorts');
    }
};
