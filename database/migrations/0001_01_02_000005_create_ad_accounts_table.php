<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates ad_accounts table — Facebook / Google ad account identity.
 *
 * Token fields and historical import state removed — moved to
 * integration_credentials and historical_import_jobs respectively.
 *
 * @see docs/planning/schema.md §1.3 ad_accounts
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            // CHECK constraint below.
            $table->string('platform', 16);
            $table->string('external_id', 255);
            $table->string('name', 255);
            $table->char('currency', 3);
            // Encrypted OAuth tokens stored directly on the account record.
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
            // Tracks when campaign/adset/ad structure was last synced.
            // Regular insight syncs skip structure sync if this is < 23 h ago.
            $table->timestamp('last_structure_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'platform', 'external_id']);
            $table->index('workspace_id');
        });

        DB::statement("ALTER TABLE ad_accounts ADD CONSTRAINT check_ad_accounts_platform CHECK (platform IN ('facebook','google'))");
        DB::statement("ALTER TABLE ad_accounts ADD CONSTRAINT check_ad_accounts_status CHECK (status IN ('active','error','token_expired','disconnected','disabled'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
};
