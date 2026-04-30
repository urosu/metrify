<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates notification_preferences, digest_schedules, slack_webhooks, and anomaly_rules tables.
 *
 * @see docs/planning/schema.md §1.10 notification_preferences, digest_schedules, slack_webhooks, anomaly_rules
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // CHECK constraint below.
            $table->string('channel', 20);
            // CHECK constraint below.
            $table->string('severity', 20);
            $table->boolean('enabled')->default(true);
            // CHECK constraint below.
            $table->string('delivery_mode', 20)->default('immediate');
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id', 'channel', 'severity']);
        });

        DB::statement("ALTER TABLE notification_preferences ADD CONSTRAINT check_np_channel CHECK (channel IN ('email','in_app'))");
        DB::statement("ALTER TABLE notification_preferences ADD CONSTRAINT check_np_severity CHECK (severity IN ('critical','high','medium','low'))");
        DB::statement("ALTER TABLE notification_preferences ADD CONSTRAINT check_np_delivery_mode CHECK (delivery_mode IN ('immediate','daily_digest','weekly_digest'))");

        Schema::create('digest_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            // CHECK constraint below.
            $table->string('frequency', 16);
            // 0-6 for weekly.
            $table->smallInteger('day_of_week')->nullable();
            // 1-28 for monthly.
            $table->smallInteger('day_of_month')->nullable();
            // 0-23, workspace timezone.
            $table->smallInteger('send_at_hour');
            // Array of emails. Nexstage-owned JSONB.
            $table->jsonb('recipients')->default('[]');
            // Array of page slugs. Nexstage-owned JSONB.
            $table->jsonb('content_pages')->default('[]');
            $table->timestamp('last_sent_at')->nullable();
            $table->string('last_sent_status', 16)->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'frequency']);
        });

        DB::statement("ALTER TABLE digest_schedules ADD CONSTRAINT check_digest_frequency CHECK (frequency IN ('off','daily','weekly','monthly'))");

        // Partial index for active (non-off) digest schedules.
        DB::statement("CREATE INDEX idx_digest_schedules_active ON digest_schedules (workspace_id) WHERE frequency != 'off'");

        Schema::create('slack_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('slack_team_id', 32);
            $table->string('slack_team_name', 128);
            $table->text('webhook_url_encrypted');
            $table->string('default_channel', 64)->nullable();
            $table->bigInteger('connected_by');
            // CHECK constraint below.
            $table->string('status', 16)->default('active');
            $table->timestamps();

            $table->foreign('connected_by')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['workspace_id', 'slack_team_id']);
        });

        DB::statement("ALTER TABLE slack_webhooks ADD CONSTRAINT check_slack_status CHECK (status IN ('active','disconnected'))");

        Schema::create('anomaly_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            // CHECK constraint below.
            $table->string('rule_type', 48);
            $table->decimal('threshold_value', 10, 4);
            // 'percent' or 'hours'.
            $table->string('threshold_unit', 16);
            $table->boolean('enabled')->default(true);
            // Array of 'email', 'triage_inbox', 'slack'. Nexstage-owned JSONB.
            $table->jsonb('delivery_channels')->default('[]');
            $table->timestamp('last_fired_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'rule_type']);
            $table->index(['workspace_id', 'enabled']);
        });

        DB::statement("ALTER TABLE anomaly_rules ADD CONSTRAINT check_anomaly_rule_type CHECK (rule_type IN ('real_vs_store_delta','platform_overreport','ad_spend_dod','integration_down'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('anomaly_rules');
        Schema::dropIfExists('slack_webhooks');
        Schema::dropIfExists('digest_schedules');
        Schema::dropIfExists('notification_preferences');
    }
};
