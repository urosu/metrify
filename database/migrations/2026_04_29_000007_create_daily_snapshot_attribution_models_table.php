<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates daily_snapshot_attribution_models — pre-computed per-channel per-model revenue.
 *
 * Eliminates the three raw orders SUM/GROUP queries that AttributionDataService::modelComparison()
 * runs on every Attribution page load. Each row stores one (workspace, date, channel, model) tuple.
 *
 * Populated by: BuildAttributionSnapshotJob (chained after BuildDailySnapshotJob).
 * Read by:      AttributionDataService::modelComparison() (when date range is fully covered).
 *
 * Model values:
 *   first_click | last_click | last_non_direct | linear |
 *   time_decay | position_based | data_driven | survey
 *
 * @see app/Jobs/BuildAttributionSnapshotJob.php
 * @see app/Services/Attribution/AttributionDataService.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_snapshot_attribution_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->date('date');
            $table->string('channel_id', 100);  // channel_type value e.g. 'paid_social', 'organic_search'
            $table->string('model', 50);         // e.g. 'first_click', 'last_click', 'linear', etc.
            $table->decimal('revenue', 14, 4)->default(0);
            $table->unsignedInteger('orders')->default(0);
            $table->unsignedInteger('customers')->default(0);
            $table->timestamps();

            // Unique constraint: one row per (workspace, date, channel, model).
            $table->unique(['workspace_id', 'date', 'channel_id', 'model'], 'dsam_ws_date_channel_model_unique');

            // Index for range queries by workspace + date range.
            $table->index(['workspace_id', 'date', 'model'], 'dsam_workspace_date_model_index');

            $table->foreign('workspace_id')
                ->references('id')->on('workspaces')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_snapshot_attribution_models');
    }
};
