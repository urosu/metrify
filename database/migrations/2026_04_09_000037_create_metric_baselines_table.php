<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 2: Precomputed rolling statistics used by DetectAnomaliesJob.
        // Populated by: ComputeMetricBaselinesJob (historical backfill on first run, then nightly).
        // Related: app/Jobs/ComputeMetricBaselinesJob.php, app/Jobs/DetectAnomaliesJob.php
        // See: PLANNING.md "metric_baselines"
        Schema::create('metric_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->cascadeOnDelete();
            $table->string('metric', 100);
            $table->smallInteger('weekday');  // 0=Mon..6=Sun (ISO)
            $table->decimal('median', 14, 4);
            $table->decimal('mad', 14, 4);    // Median Absolute Deviation — robust against outliers
            $table->integer('data_point_count');

            // MAD/median ratio: lower = more stable = higher confidence.
            // NULL when median = 0 (legitimate for zero-value weekdays like Sunday sales).
            // When NULL, fall back to data_point_count tier only for confidence scoring.
            $table->decimal('stability_score', 5, 4)->nullable();

            $table->timestamp('updated_at');
        });

        // Why partial unique indexes instead of UNIQUE(workspace_id, store_id, metric, weekday):
        // PostgreSQL treats NULL as distinct in unique constraints, so nullable store_id
        // would allow duplicate workspace-level rows. Two partial indexes solve this.
        // See: PLANNING.md "metric_baselines"
        DB::statement('CREATE UNIQUE INDEX metric_baselines_store_unique ON metric_baselines (workspace_id, store_id, metric, weekday) WHERE store_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX metric_baselines_workspace_unique ON metric_baselines (workspace_id, metric, weekday) WHERE store_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_baselines');
    }
};
