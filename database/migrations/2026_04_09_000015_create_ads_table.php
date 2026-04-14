<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('adset_id')->constrained('adsets')->cascadeOnDelete();
            $table->string('external_id');
            $table->string('name', 500)->nullable();
            $table->string('status', 100)->nullable();

            // Separate from status: effective_status reflects the actual delivery state
            // accounting for campaign/adset pauses. Needed by the correlation engine.
            $table->string('effective_status', 100)->nullable();

            $table->text('destination_url')->nullable();

            // images, headlines, descriptions, video thumbnails, call-to-action.
            // See: PLANNING.md "Data Capture Strategy — What to JSONB"
            $table->jsonb('creative_data')->nullable();
            $table->string('creative_data_api_version', 20)->nullable();

            $table->timestamps();

            $table->unique(['adset_id', 'external_id']);
            $table->index('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
