<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates ga4_properties and ga4_daily_sessions tables.
 *
 * Narrow read-only connector — sole session source for WooCommerce stores.
 * Credentials stored in integration_credentials polymorphic.
 *
 * @see docs/planning/schema.md §1.9 ga4_properties, ga4_daily_sessions
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ga4_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            // "properties/123456789"
            $table->string('property_id', 32);
            $table->string('property_name', 255);
            // G-XXXXXXXX — display only.
            $table->string('measurement_id', 16)->nullable();
            // CHECK constraint below.
            $table->string('status', 16)->default('active');
            $table->smallInteger('consecutive_sync_failures')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('workspace_id');
        });

        DB::statement("ALTER TABLE ga4_properties ADD CONSTRAINT check_ga4_prop_status CHECK (status IN ('active','error','token_expired','disconnected'))");

        Schema::create('ga4_daily_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('ga4_property_id')->constrained('ga4_properties')->cascadeOnDelete();
            $table->date('date');
            $table->integer('sessions');
            $table->integer('users')->nullable();
            // NULL = all-countries aggregate row.
            $table->char('country_code', 2)->nullable();
            // desktop/mobile/tablet — NULL = aggregate row.
            $table->string('device_category', 16)->nullable();
            // CHECK constraint below. GA4 data < 3 days old may revise.
            $table->string('data_state', 16)->default('final');
            $table->timestamp('synced_at');
            $table->timestamp('created_at')->nullable();

            $table->unique(['ga4_property_id', 'date', 'country_code', 'device_category']);
            $table->index(['workspace_id', 'date']);
        });

        DB::statement("ALTER TABLE ga4_daily_sessions ADD CONSTRAINT check_ga4_data_state CHECK (data_state IN ('final','provisional'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ga4_daily_sessions');
        Schema::dropIfExists('ga4_properties');
    }
};
