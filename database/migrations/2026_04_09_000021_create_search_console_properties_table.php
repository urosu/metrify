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
        Schema::create('search_console_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('property_url', 500);
            $table->text('access_token_encrypted')->nullable();
            $table->text('refresh_token_encrypted')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status', 50)->default('active');
            $table->smallInteger('consecutive_sync_failures')->default(0);

            $table->string('historical_import_status', 50)->nullable();
            $table->date('historical_import_from')->nullable();
            $table->jsonb('historical_import_checkpoint')->nullable();
            $table->smallInteger('historical_import_progress')->nullable();
            $table->timestamp('historical_import_started_at')->nullable();
            $table->timestamp('historical_import_completed_at')->nullable();
            $table->integer('historical_import_duration_seconds')->nullable();

            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'property_url']);
            $table->index('workspace_id');
        });

        DB::statement("ALTER TABLE search_console_properties ADD CONSTRAINT search_console_properties_status_check CHECK (status IN ('active','error','token_expired','disconnected'))");
        DB::statement("ALTER TABLE search_console_properties ADD CONSTRAINT search_console_properties_historical_import_status_check CHECK (historical_import_status IN ('pending','running','completed','failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('search_console_properties');
    }
};
