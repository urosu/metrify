<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates stores table — ecommerce store integration identity.
 *
 * Token/credential fields moved to integration_credentials.
 * Cost settings moved to store_cost_settings.
 * Historical import state moved to historical_import_jobs.
 * Target columns moved to workspace_targets.
 *
 * @see docs/planning/schema.md §1.2 stores
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255);
            // 'type' is an alias kept for backward compat; 'platform' is canonical.
            $table->string('type', 24)->nullable();
            // CHECK constraint below — woocommerce or shopify only for v1.
            $table->string('platform', 24)->default('woocommerce');
            // ISO-2. Three-tier fallback for ad-spend country attribution:
            // COALESCE(campaigns.parsed_convention->>'country', stores.primary_country_code, 'UNKNOWN')
            $table->char('primary_country_code', 2)->nullable();
            $table->string('domain', 255);
            $table->string('website_url', 512)->nullable();
            $table->char('currency', 3);
            $table->string('timezone', 64)->default('UTC');
            $table->jsonb('settings')->nullable();
            $table->string('platform_store_id', 255)->nullable();
            // CHECK constraint below.
            $table->string('status', 16)->default('connecting');
            $table->integer('consecutive_sync_failures')->default(0);
            // Encrypted OAuth / API credentials stored directly on the store record.
            $table->text('auth_key_encrypted')->nullable();
            $table->text('auth_secret_encrypted')->nullable();
            $table->text('access_token_encrypted')->nullable();
            $table->text('refresh_token_encrypted')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->text('webhook_secret_encrypted')->nullable();
            // Historical import state machine.
            $table->string('historical_import_status', 16)->nullable();
            $table->date('historical_import_from')->nullable();
            $table->jsonb('historical_import_checkpoint')->nullable();
            $table->unsignedTinyInteger('historical_import_progress')->nullable()->default(0);
            $table->unsignedInteger('historical_import_total_orders')->nullable();
            $table->timestamp('historical_import_started_at')->nullable();
            $table->timestamp('historical_import_completed_at')->nullable();
            $table->unsignedInteger('historical_import_duration_seconds')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            // Per-store performance targets (override workspace defaults).
            $table->decimal('target_roas', 8, 2)->nullable();
            $table->decimal('target_cpo', 10, 2)->nullable();
            $table->decimal('target_marketing_pct', 5, 2)->nullable();
            // Nexstage-owned cost config VO. No api_version.
            $table->jsonb('cost_settings')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'domain']);
            $table->unique(['workspace_id', 'slug']);
            $table->index('workspace_id');
            $table->index(['workspace_id', 'platform_store_id']);
        });

        DB::statement("ALTER TABLE stores ADD CONSTRAINT check_stores_platform CHECK (platform IN ('woocommerce','shopify'))");
        DB::statement("ALTER TABLE stores ADD CONSTRAINT check_stores_status CHECK (status IN ('connecting','active','error','disconnected'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
