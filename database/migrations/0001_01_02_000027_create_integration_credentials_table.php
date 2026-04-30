<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates integration_credentials table — centralised OAuth / API-key material.
 *
 * Polymorphic to stores / ad_accounts / search_console_properties / ga4_properties.
 * Enables per-integration rotation independent of identity rows.
 *
 * @see docs/planning/schema.md §1.10 integration_credentials
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('integrationable_type', 64);
            $table->bigInteger('integrationable_id');
            $table->text('auth_key_encrypted')->nullable();
            $table->text('auth_secret_encrypted')->nullable();
            $table->text('access_token_encrypted')->nullable();
            $table->text('refresh_token_encrypted')->nullable();
            $table->text('webhook_secret_encrypted')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            // Granted OAuth scope list. Nexstage-owned JSONB — no api_version.
            $table->jsonb('scopes')->default('[]');
            // Set to true for seeded/demo credentials so Sync* jobs skip real API calls.
            // @see database/seeders/ — all demo seeders set this to true.
            $table->boolean('is_seeded')->default(false);
            $table->timestamps();

            $table->unique(['integrationable_type', 'integrationable_id']);
            $table->index('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_credentials');
    }
};
