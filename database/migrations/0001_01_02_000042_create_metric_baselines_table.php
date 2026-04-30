<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates metric_baselines table — precomputed rolling median/MAD per metric per weekday.
 *
 * Feeds statistical anomaly detection at the data layer.
 * Evaluated by anomaly_rules.
 *
 * @see docs/planning/schema.md §1.14 metric_baselines
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metric_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->cascadeOnDelete();
            $table->string('metric', 100);
            // 0=Mon..6=Sun (ISO).
            $table->smallInteger('weekday');
            $table->decimal('median', 14, 4);
            // Median Absolute Deviation — robust against outliers.
            $table->decimal('mad', 14, 4);
            $table->integer('data_point_count');
            // NULL when median = 0 (legitimate for zero-value weekdays).
            $table->decimal('stability_score', 5, 4)->nullable();
            $table->timestamp('updated_at');
        });

        // Two partial indexes to handle nullable store_id correctly.
        DB::statement('CREATE UNIQUE INDEX metric_baselines_store_unique ON metric_baselines (workspace_id, store_id, metric, weekday) WHERE store_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX metric_baselines_workspace_unique ON metric_baselines (workspace_id, metric, weekday) WHERE store_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_baselines');
    }
};
