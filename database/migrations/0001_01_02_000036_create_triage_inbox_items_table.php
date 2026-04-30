<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates triage_inbox_items table — UX §5.22 TriageInbox.
 *
 * Renamed from inbox_items. Merges responsibilities of dropped alerts table.
 * Focused list of items needing a human decision.
 *
 * @see docs/planning/schema.md §1.10 triage_inbox_items
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triage_inbox_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('itemable_type', 64)->nullable();
            $table->bigInteger('itemable_id')->nullable();
            // CHECK constraint below.
            $table->string('severity', 16)->default('info');
            $table->string('title', 255);
            $table->string('context_text', 512)->nullable();
            $table->string('primary_action_label', 64)->nullable();
            $table->string('deep_link_url', 512)->nullable();
            // CHECK constraint below.
            $table->string('status', 16)->default('open');
            $table->timestamp('snoozed_until')->nullable();
            // Dismiss audit columns — populated when status transitions to 'dismissed'.
            $table->timestamp('dismissed_at')->nullable();
            $table->bigInteger('dismissed_by_user_id')->nullable();
            $table->string('dismiss_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status', 'snoozed_until', 'created_at']);
            $table->index(['itemable_type', 'itemable_id']);
            $table->unique(['workspace_id', 'itemable_type', 'itemable_id']);
        });

        DB::statement("ALTER TABLE triage_inbox_items ADD CONSTRAINT check_triage_severity CHECK (severity IN ('info','warning','high','critical'))");
        DB::statement("ALTER TABLE triage_inbox_items ADD CONSTRAINT check_triage_status CHECK (status IN ('open','done','dismissed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('triage_inbox_items');
    }
};
