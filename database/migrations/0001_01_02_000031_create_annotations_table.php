<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates annotations table — generalises daily_notes + workspace_events.
 *
 * ChartAnnotationLayer source (UX §5.6.1). User-authored and system-authored
 * flags on time-series charts.
 * is_hidden_per_user and suppress_anomalies are Nexstage-owned JSONB — no api_version.
 *
 * @see docs/planning/schema.md §1.10 annotations
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            // CHECK constraint below.
            $table->string('author_type', 16);
            $table->bigInteger('author_id')->nullable();
            $table->string('title', 255);
            $table->text('body')->nullable();
            // CHECK constraint below.
            $table->string('annotation_type', 32);
            // CHECK constraint below.
            $table->string('scope_type', 16)->default('workspace');
            $table->bigInteger('scope_id')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            // Array of user_ids that hid a system annotation. Nexstage-owned.
            $table->jsonb('is_hidden_per_user')->default('[]');
            // When true, anomaly rules skip this range.
            $table->boolean('suppress_anomalies')->default(false);
            // Visual differentiation per UX §5 ChartAnnotationLayer.
            // color: hex or Tailwind token (e.g. '#ef4444'). NULL uses the annotation_type default.
            $table->string('color', 32)->nullable();
            // icon: lucide-react icon name (e.g. 'tag', 'zap', 'star'). NULL uses annotation_type default.
            $table->string('icon', 64)->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['workspace_id', 'starts_at', 'ends_at']);
            $table->index(['workspace_id', 'scope_type', 'scope_id']);
            $table->index(['workspace_id', 'annotation_type']);
        });

        DB::statement("ALTER TABLE annotations ADD CONSTRAINT check_annotations_author_type CHECK (author_type IN ('user','system'))");
        DB::statement("ALTER TABLE annotations ADD CONSTRAINT check_annotations_type CHECK (annotation_type IN ('user_note','promotion','expected_spike','expected_drop','integration_disconnect','integration_reconnect','attribution_model_change','cogs_update','algorithm_update','migration'))");
        DB::statement("ALTER TABLE annotations ADD CONSTRAINT check_annotations_scope_type CHECK (scope_type IN ('workspace','store','integration','product','campaign','page','query'))");

        // Partial index for ChartAnnotationLayer promotion marker queries.
        DB::statement("CREATE INDEX idx_annotations_ws_promotions ON annotations (workspace_id, starts_at) WHERE annotation_type = 'promotion'");
    }

    public function down(): void
    {
        Schema::dropIfExists('annotations');
    }
};
