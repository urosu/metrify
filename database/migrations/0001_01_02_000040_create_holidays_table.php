<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates holidays table — global holiday / commercial event reference.
 *
 * Not tenant-scoped (no workspace_id, no WorkspaceScope).
 * Used by anomaly suppression and chart annotation overlay.
 *
 * @see docs/planning/schema.md §1.14 holidays
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 2);
            $table->date('date');
            $table->string('name', 255);
            $table->smallInteger('year');
            // CHECK constraint below.
            $table->string('type', 16)->default('public');
            // shopping, gifting, seasonal, cultural
            $table->string('category', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['country_code', 'date', 'name']);
            $table->index(['country_code', 'year']);
            $table->index(['country_code', 'year', 'type'], 'holidays_country_year_type_index');
        });

        DB::statement("ALTER TABLE holidays ADD CONSTRAINT check_holidays_type CHECK (type IN ('public','commercial'))");

        // Fast country+date lookup for chart overlays.
        DB::statement('CREATE INDEX idx_holidays_country_date ON holidays (country_code, date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
