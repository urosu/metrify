<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates workspace_targets table — metric goals (UX §5.23 Target primitive).
 *
 * Consolidates targets formerly scattered across workspaces, stores, and campaigns.
 * visible_on_pages is Nexstage-owned JSONB — no api_version.
 *
 * @see docs/planning/schema.md §1.10 workspace_targets
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            // Matches _crosscut_metric_dictionary.md "Our pick" slug.
            $table->string('metric', 64);
            // CHECK constraint below.
            $table->string('period', 16);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('target_value_reporting', 14, 2);
            // NULL when metric is unitless (e.g. ROAS).
            $table->char('currency', 3)->nullable();
            $table->bigInteger('owner_user_id');
            // Array of page slugs for TargetLine/TargetProgress chrome. Nexstage-owned.
            $table->jsonb('visible_on_pages')->default('[]');
            // CHECK constraint below.
            $table->string('status', 16)->default('active');
            $table->bigInteger('created_by');
            $table->timestamps();

            $table->foreign('owner_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['workspace_id', 'status', 'period']);
            $table->index(['workspace_id', 'metric', 'period']);
        });

        DB::statement("ALTER TABLE workspace_targets ADD CONSTRAINT check_targets_period CHECK (period IN ('this_week','this_month','this_quarter','custom'))");
        DB::statement("ALTER TABLE workspace_targets ADD CONSTRAINT check_targets_status CHECK (status IN ('active','archived'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_targets');
    }
};
