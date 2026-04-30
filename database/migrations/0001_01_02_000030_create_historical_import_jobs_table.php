<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates historical_import_jobs table — replaces scattered historical_import_* columns.
 *
 * One row per in-flight or archived backfill (4 job types).
 * checkpoint is Nexstage-owned JSONB (resume state) — no api_version.
 *
 * @see docs/planning/schema.md §1.10 historical_import_jobs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('integrationable_type', 64);
            $table->bigInteger('integrationable_id');
            // CHECK constraint below.
            $table->string('job_type', 32);
            // CHECK constraint below.
            $table->string('status', 16);
            $table->date('from_date');
            $table->date('to_date');
            $table->integer('total_rows_estimated')->nullable();
            $table->integer('total_rows_imported')->default(0);
            $table->smallInteger('progress_pct')->default(0);
            // Resume state. Nexstage-owned JSONB — no api_version.
            $table->jsonb('checkpoint')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'job_type', 'created_at']);
        });

        DB::statement("ALTER TABLE historical_import_jobs ADD CONSTRAINT check_hij_job_type CHECK (job_type IN ('shopify_orders','woocommerce_orders','ad_insights','gsc','ga4'))");
        DB::statement("ALTER TABLE historical_import_jobs ADD CONSTRAINT check_hij_status CHECK (status IN ('pending','running','completed','failed','cancelled'))");

        // Fast "which imports are active" queries.
        DB::statement("CREATE INDEX idx_hij_active ON historical_import_jobs (workspace_id, status) WHERE status IN ('pending','running')");
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_import_jobs');
    }
};
