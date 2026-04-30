<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates channel_mappings table — UTM source/medium → named channel mapping.
 *
 * Global seeds (workspace_id=NULL) + workspace overrides.
 * priority: first match wins, lowest number = highest priority.
 *
 * IMPORTANT: utm_source_pattern/utm_medium_pattern values must stay in sync
 * with resources/js/Components/tools/TagGenerator.tsx suggestions.
 *
 * @see docs/planning/schema.md §1.13 channel_mappings
 * @see CLAUDE.md §UTM source/medium sync
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_mappings', function (Blueprint $table) {
            $table->id();
            // NULL = global seed row. Cascade deletes workspace overrides.
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->string('utm_source_pattern', 255);
            $table->string('utm_medium_pattern', 255)->nullable();
            $table->string('channel_name', 120);
            // CHECK constraint below.
            $table->string('channel_type', 32);
            $table->boolean('is_global')->default(false);
            $table->boolean('is_regex')->default(false);
            // First match wins, lowest = highest priority. NEW.
            $table->smallInteger('priority')->default(100);
            // NULL on seed rows. NEW.
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['workspace_id', 'utm_source_pattern']);
            $table->index(['workspace_id', 'priority']);
        });

        DB::statement("ALTER TABLE channel_mappings ADD CONSTRAINT check_channel_mappings_type CHECK (channel_type IN ('email','paid_social','paid_search','organic_search','organic_social','direct','referral','affiliate','sms','other'))");

        // Workspace rows: one mapping per (workspace, source, medium) pair.
        DB::statement("CREATE UNIQUE INDEX channel_mappings_workspace_unique ON channel_mappings (workspace_id, utm_source_pattern, COALESCE(utm_medium_pattern, '')) WHERE workspace_id IS NOT NULL");

        // Global rows: one global mapping per (source, medium) pair.
        DB::statement("CREATE UNIQUE INDEX channel_mappings_global_unique ON channel_mappings (utm_source_pattern, COALESCE(utm_medium_pattern, '')) WHERE workspace_id IS NULL");

        // Fast fetch of all global seed rows.
        DB::statement('CREATE INDEX idx_channel_mappings_global ON channel_mappings (utm_source_pattern) WHERE workspace_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_mappings');
    }
};
