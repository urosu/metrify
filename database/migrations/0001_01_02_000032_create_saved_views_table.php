<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates saved_views table — named filter/sort/columns/date-range combinations.
 *
 * url_state is Nexstage-owned JSONB — no api_version.
 *
 * @see docs/planning/schema.md §1.10 saved_views
 * @see docs/UX.md §5.19
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            // NULL = workspace-shared view.
            $table->bigInteger('user_id')->nullable();
            // CHECK constraint below.
            $table->string('page', 32);
            $table->string('name', 128);
            // URL querystring serialised. Nexstage-owned JSONB — no api_version.
            $table->jsonb('url_state');
            $table->boolean('is_pinned')->default(false);
            $table->smallInteger('pin_order')->default(0);
            $table->bigInteger('created_by');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['workspace_id', 'page', 'is_pinned']);
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE saved_views ADD CONSTRAINT check_saved_views_page CHECK (page IN ('/dashboard','/orders','/ads','/attribution','/seo','/products','/profit','/customers'))");

        // Partial index for shared workspace views.
        DB::statement('CREATE INDEX idx_saved_views_workspace_shared ON saved_views (workspace_id, page) WHERE user_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_views');
    }
};
