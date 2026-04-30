<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates search_console_properties table — GSC OAuth property state.
 *
 * Token/credential fields removed — moved to integration_credentials.
 * Historical import state removed — moved to historical_import_jobs.
 *
 * @see docs/planning/schema.md §1.8 search_console_properties
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_console_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('property_url', 500);
            // Encrypted OAuth tokens stored directly on the property record.
            $table->text('access_token_encrypted')->nullable();
            $table->text('refresh_token_encrypted')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            // Historical import state machine.
            $table->string('historical_import_status', 16)->nullable();
            $table->date('historical_import_from')->nullable();
            $table->jsonb('historical_import_checkpoint')->nullable();
            $table->unsignedTinyInteger('historical_import_progress')->nullable()->default(0);
            $table->unsignedInteger('historical_import_total_orders')->nullable();
            $table->timestamp('historical_import_started_at')->nullable();
            $table->timestamp('historical_import_completed_at')->nullable();
            $table->unsignedInteger('historical_import_duration_seconds')->nullable();
            // CHECK constraint below.
            $table->string('status', 16)->default('active');
            $table->smallInteger('consecutive_sync_failures')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'property_url']);
            $table->index('workspace_id');
        });

        DB::statement("ALTER TABLE search_console_properties ADD CONSTRAINT check_scp_status CHECK (status IN ('active','error','token_expired','disconnected'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('search_console_properties');
    }
};
