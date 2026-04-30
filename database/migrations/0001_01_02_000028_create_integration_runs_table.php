<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates integration_runs table — replaces sync_logs.
 *
 * One row per scheduled sync invocation. Elevar-aligned naming.
 * payload is Nexstage-owned JSONB (job constructor args for debugging) — no api_version.
 *
 * @see docs/planning/schema.md §1.10 integration_runs
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('integrationable_type', 64)->nullable();
            $table->bigInteger('integrationable_id')->nullable();
            $table->string('job_type', 64)->nullable();
            // CHECK constraint below.
            $table->string('status', 16)->nullable();
            $table->integer('records_processed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('queue', 32)->nullable();
            $table->smallInteger('attempt')->default(1);
            // Used by orphaned-log sweeper.
            $table->unsignedSmallInteger('timeout_seconds')->nullable();
            $table->integer('duration_seconds')->nullable();
            // Nexstage-owned JSONB (job args for debugging) — no api_version.
            $table->jsonb('payload')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'integrationable_type', 'integrationable_id'], 'idx_ir_ws_type_id');
            $table->index(['status', 'created_at'], 'idx_ir_status_created');
            $table->index(['workspace_id', 'created_at'], 'idx_ir_ws_created');
        });

        DB::statement("ALTER TABLE integration_runs ADD CONSTRAINT check_integration_runs_status CHECK (status IN ('queued','running','completed','failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_runs');
    }
};
