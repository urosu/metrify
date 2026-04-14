<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Global reference table — NOT tenant-scoped (no workspace_id, no WorkspaceScope).
        // Populated by RefreshHolidaysJob using azuyalabs/yasumi library.
        // One row per country per holiday per year — no per-workspace duplication.
        //
        // Refreshed: January 1st each year for all countries with active workspaces.
        // Also triggered: on workspace creation if workspace.country has no holidays for current year.
        //
        // Consumed by: DetectAnomaliesJob (skip detection on holiday dates),
        //              ComputeMetricBaselinesJob (exclude holiday dates from baseline window),
        //              chart event overlay on all time-series charts.
        // See: PLANNING.md "holidays" + "workspace_events integration"
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 2);
            $table->date('date');
            $table->string('name', 255);
            $table->smallInteger('year');
            $table->timestamp('created_at')->nullable();

            $table->unique(['country_code', 'date', 'name']);
            $table->index(['country_code', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
