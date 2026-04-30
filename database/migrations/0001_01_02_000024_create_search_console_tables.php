<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates search_console_daily_rollups, search_console_queries, and search_console_pages tables.
 *
 * Renamed from gsc_daily_stats, gsc_queries, gsc_pages. Added data_state column.
 *
 * @see docs/planning/schema.md §1.8 search_console_daily_rollups, search_console_queries, search_console_pages
 */
return new class extends Migration
{
    public function up(): void
    {
        // Daily totals by device + country.
        Schema::create('search_console_daily_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('search_console_properties')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->date('date');
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->decimal('ctr', 8, 6)->nullable();
            $table->decimal('position', 6, 2)->nullable();
            // NOT NULL with sentinel values for correct unique constraint behaviour.
            $table->string('device', 10)->default('all');
            $table->char('country', 3)->default('ZZ');
            // CHECK constraint below. 'final' or 'provisional'.
            $table->string('data_state', 16)->default('final');
            $table->timestamps();

            $table->unique(['property_id', 'date', 'device', 'country', 'data_state']);
            $table->index(['workspace_id', 'date']);
        });

        // Per-query daily stats.
        Schema::create('search_console_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('search_console_properties')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->date('date');
            $table->string('query', 500);
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->decimal('ctr', 8, 6)->nullable();
            $table->decimal('position', 6, 2)->nullable();
            $table->string('device', 10)->default('all');
            $table->char('country', 3)->default('ZZ');
            $table->string('data_state', 16)->default('final');
            $table->timestamps();

            $table->unique(['property_id', 'date', 'query', 'device', 'country', 'data_state']);
            $table->index(['workspace_id', 'date']);
        });

        // Per-page daily stats.
        Schema::create('search_console_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('search_console_properties')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->date('date');
            // TEXT: URLs can exceed 2000 chars.
            $table->text('page');
            // SHA-256 of URL — used in unique constraint to avoid massive indexes.
            $table->char('page_hash', 64);
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->decimal('ctr', 8, 6)->nullable();
            $table->decimal('position', 6, 2)->nullable();
            $table->string('device', 10)->default('all');
            $table->char('country', 3)->default('ZZ');
            $table->string('data_state', 16)->default('final');
            $table->timestamps();

            $table->index(['property_id', 'date']);
            $table->index(['workspace_id', 'date']);
        });

        DB::statement("ALTER TABLE search_console_daily_rollups ADD CONSTRAINT check_gsc_daily_data_state CHECK (data_state IN ('final','provisional'))");
        DB::statement("ALTER TABLE search_console_queries ADD CONSTRAINT check_gsc_queries_data_state CHECK (data_state IN ('final','provisional'))");
        DB::statement("ALTER TABLE search_console_pages ADD CONSTRAINT check_gsc_pages_data_state CHECK (data_state IN ('final','provisional'))");

        // Functional unique index for pages — avoids massive URL index.
        DB::statement('CREATE UNIQUE INDEX gsc_pages_upsert_key ON search_console_pages (property_id, date, page_hash, device, country, data_state)');

        // Partial index on search_console_queries for the dominant SeoController query path
        // (device='all' AND country='ZZ' date-range scans by property_id).
        DB::statement(
            "CREATE INDEX idx_scq_property_date_all
             ON search_console_queries (property_id, date)
             WHERE device = 'all' AND country = 'ZZ'"
        );

        // Partial index on search_console_pages — mirrors idx_scq_property_date_all.
        DB::statement(
            "CREATE INDEX idx_scp_property_date_all
             ON search_console_pages (property_id, date)
             WHERE device = 'all' AND country = 'ZZ'"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('search_console_pages');
        Schema::dropIfExists('search_console_queries');
        Schema::dropIfExists('search_console_daily_rollups');
    }
};
