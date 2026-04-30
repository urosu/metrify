<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates public_snapshot_tokens table — tokens for ShareSnapshotButton.
 *
 * Public read-only snapshot URLs at /public/snapshot/{token}.
 * url_state and snapshot_data are Nexstage-owned JSONB — no api_version.
 *
 * @see docs/planning/schema.md §1.10 public_snapshot_tokens
 * @see docs/UX.md §5.29
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_snapshot_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('page', 32);
            // Frozen filter state. Nexstage-owned JSONB.
            $table->jsonb('url_state');
            $table->boolean('date_range_locked')->default(true);
            // Optional materialised payload — otherwise re-reads daily_snapshots. Nexstage-owned.
            $table->jsonb('snapshot_data')->nullable();
            // NULL = never expires.
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->bigInteger('created_by');
            $table->timestamp('last_accessed_at')->nullable();
            $table->integer('access_count')->default(0);
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['workspace_id', 'created_at']);
        });

        // Partial index for active tokens (not revoked).
        DB::statement('CREATE INDEX idx_pst_active ON public_snapshot_tokens (workspace_id) WHERE revoked_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('public_snapshot_tokens');
    }
};
