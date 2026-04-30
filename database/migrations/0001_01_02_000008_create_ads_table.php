<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates ads table — ad creative snapshot.
 *
 * creative_data is platform-owned JSONB (Meta/Google ad snapshot). Paired with
 * creative_data_api_version per CLAUDE.md §JSONB api_version rule.
 *
 * @see docs/planning/schema.md §1.3 ads
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('adset_id')->constrained('adsets')->cascadeOnDelete();
            $table->string('external_id', 255);
            $table->string('name', 500)->nullable();
            $table->string('status', 100)->nullable();
            // Effective status reflects actual delivery accounting for campaign/adset pauses.
            $table->string('effective_status', 100)->nullable();
            $table->text('destination_url')->nullable();
            // Platform-owned: images, headlines, descriptions, video thumbnails, CTA.
            // Paired with api_version per CLAUDE.md gotcha.
            $table->jsonb('creative_data')->nullable();
            $table->string('creative_data_api_version', 16)->nullable();
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
