<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aggregated uptime stats per URL per day. Computed from uptime_checks before
        // raw checks are deleted during cleanup. Used by the Performance page uptime panel.
        // Related: app/Jobs/CleanupPerformanceDataJob.php (Phase 2)
        Schema::create('uptime_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_url_id')->constrained('store_urls')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->date('date');
            $table->integer('checks_total');
            $table->integer('checks_up');
            $table->decimal('uptime_pct', 5, 2);
            $table->integer('avg_response_ms')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['store_url_id', 'date']);
            $table->index(['workspace_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uptime_daily_summaries');
    }
};
