<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates settings_audit_log and google_algorithm_updates tables.
 *
 * settings_audit_log: UX §Settings audit panel — last 20 changes per sub-page.
 * google_algorithm_updates: seeded list of Google core algorithm updates for /seo chart annotations.
 *
 * @see docs/planning/schema.md §1.11, §1.8
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            // CHECK constraint below.
            $table->string('sub_page', 32);
            $table->bigInteger('actor_user_id')->nullable();
            $table->string('entity_type', 64);
            $table->bigInteger('entity_id')->nullable();
            $table->string('field_changed', 64);
            $table->text('value_from')->nullable();
            $table->text('value_to')->nullable();
            $table->boolean('is_reversible')->default(false);
            $table->timestamp('reverted_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['workspace_id', 'sub_page', 'created_at']);
            $table->index(['workspace_id', 'created_at']);
        });

        DB::statement("ALTER TABLE settings_audit_log ADD CONSTRAINT check_sal_sub_page CHECK (sub_page IN ('workspace','team','costs','billing','notifications','targets'))");

        Schema::create('google_algorithm_updates', function (Blueprint $table) {
            $table->id();
            $table->string('update_name', 128);
            $table->date('rolled_out_on');
            $table->date('rollout_ended_on')->nullable();
            // core, spam, helpful_content, product_reviews, etc.
            $table->string('update_type', 32);
            $table->string('description_url', 512);
            $table->timestamp('created_at')->nullable();

            $table->index('rolled_out_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_algorithm_updates');
        Schema::dropIfExists('settings_audit_log');
    }
};
