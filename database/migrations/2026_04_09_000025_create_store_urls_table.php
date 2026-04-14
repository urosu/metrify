<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Specific pages to monitor via PSI (Lighthouse) + uptime checks.
        // Auto-created: homepage on store connection.
        // User-managed: up to 9 additional URLs (10 total) via store settings.
        // See: PLANNING.md "store_urls" and "Performance Monitoring — URL management"
        Schema::create('store_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('label', 255)->nullable();
            $table->boolean('is_homepage')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'url']);
            $table->index(['workspace_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_urls');
    }
};
