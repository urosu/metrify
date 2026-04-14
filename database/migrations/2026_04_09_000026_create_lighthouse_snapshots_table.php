<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PSI (PageSpeed Insights) check results per URL.
        // Populated by: RunLighthouseCheckJob (Phase 1).
        // Read by: PerformanceController.
        // Staggered across 4-hour window: store_url_id % 240 minutes offset to respect PSI quota.
        // See: PLANNING.md "Performance Monitoring — PSI Rate Limit Planning"
        Schema::create('lighthouse_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('store_url_id')->constrained('store_urls')->cascadeOnDelete();
            $table->timestamp('checked_at');
            $table->string('strategy', 10)->default('mobile');  // mobile, desktop
            $table->smallInteger('performance_score')->nullable();
            $table->smallInteger('seo_score')->nullable();
            $table->smallInteger('accessibility_score')->nullable();
            $table->smallInteger('best_practices_score')->nullable();
            $table->integer('lcp_ms')->nullable();
            $table->integer('fcp_ms')->nullable();
            $table->decimal('cls_score', 6, 4)->nullable();
            $table->integer('inp_ms')->nullable();
            $table->integer('ttfb_ms')->nullable();
            $table->integer('tbt_ms')->nullable();
            $table->jsonb('raw_response')->nullable();
            $table->string('raw_response_api_version', 20)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['store_url_id', 'checked_at']);
            $table->index(['workspace_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lighthouse_snapshots');
    }
};
