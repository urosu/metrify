<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates integration_events table — Elevar-style per-event delivery log.
 *
 * One row per outbound destination call (Facebook CAPI, Google EC) or inbound webhook.
 * Merges responsibilities of the dropped webhook_logs table.
 *
 * payload is platform-owned JSONB — paired with payload_api_version per CLAUDE.md gotcha.
 *
 * @see docs/planning/schema.md §1.10 integration_events
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('integrationable_type', 64);
            $table->bigInteger('integrationable_id');
            // CHECK constraint below.
            $table->string('direction', 16);
            $table->string('event_type', 64);
            $table->string('external_ref', 255)->nullable();
            $table->string('destination_platform', 32);
            // CHECK constraint below.
            $table->string('status', 16);
            // Platform-native error code (e.g. #100).
            $table->string('error_code', 32)->nullable();
            // Nexstage-assigned bucket for Error Code Directory.
            $table->string('error_category', 64)->nullable();
            // 0-10, Elevar parity.
            $table->smallInteger('match_quality')->nullable();
            // Platform-owned JSONB. Paired with api_version per CLAUDE.md gotcha.
            $table->jsonb('payload');
            $table->string('payload_api_version', 16);
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['workspace_id', 'destination_platform', 'received_at'], 'idx_ie_ws_platform_date');
            $table->index(['workspace_id', 'status', 'received_at']);
            $table->index(['workspace_id', 'error_code', 'received_at']);
        });

        DB::statement("ALTER TABLE integration_events ADD CONSTRAINT check_integration_events_direction CHECK (direction IN ('inbound','outbound'))");
        DB::statement("ALTER TABLE integration_events ADD CONSTRAINT check_integration_events_status CHECK (status IN ('delivered','failed','pending'))");

        // Partial index for failed events — primary surface for /integrations Tracking Health.
        DB::statement("CREATE INDEX idx_ie_failed ON integration_events (workspace_id, received_at DESC) WHERE status = 'failed'");

        // 24 h event count used on every Integrations page load (IntegrationsDataService::build).
        DB::statement("CREATE INDEX idx_ie_ws_created_at ON integration_events (workspace_id, created_at DESC)");
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_events');
    }
};
