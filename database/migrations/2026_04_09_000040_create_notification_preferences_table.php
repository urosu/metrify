<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-workspace-per-user alert delivery configuration.
        // Sensible defaults (95% of users never change):
        //   critical: email immediate + in-app immediate
        //   high: email daily_digest + in-app immediate
        //   medium: in-app only, email daily_digest
        //   low: in-app only
        // Quiet hours default: 22:00-08:00 in workspace timezone. Critical overrides quiet hours.
        // See: PLANNING.md "notification_preferences"
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel', 20);     // email, in_app
            $table->string('severity', 20);    // critical, high, medium, low
            $table->boolean('enabled')->default(true);
            $table->string('delivery_mode', 20)->default('immediate');  // immediate, daily_digest, weekly_digest
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id', 'channel', 'severity']);
        });

        DB::statement("ALTER TABLE notification_preferences ADD CONSTRAINT notification_preferences_channel_check CHECK (channel IN ('email','in_app'))");
        DB::statement("ALTER TABLE notification_preferences ADD CONSTRAINT notification_preferences_severity_check CHECK (severity IN ('critical','high','medium','low'))");
        DB::statement("ALTER TABLE notification_preferences ADD CONSTRAINT notification_preferences_delivery_mode_check CHECK (delivery_mode IN ('immediate','daily_digest','weekly_digest'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
