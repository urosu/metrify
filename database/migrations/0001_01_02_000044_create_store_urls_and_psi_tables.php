<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates store_urls, lighthouse_snapshots, uptime_checks (partitioned),
 * and uptime_daily_summaries tables.
 *
 * lighthouse_snapshots.raw_response is platform-owned JSONB (PSI API response).
 * Paired with raw_response_api_version per CLAUDE.md §JSONB api_version rule.
 *
 * uptime_checks uses PostgreSQL declarative partitioning by month.
 * IMPORTANT: CleanupPerformanceDataJob must create the next 2 months of partitions
 * each Sunday run or inserts will fail on the first day of a new month with no partition.
 *
 * @see docs/planning/schema.md §1.16 store_urls, lighthouse_snapshots, uptime_checks, uptime_daily_summaries
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('label', 255)->nullable();
            $table->boolean('is_homepage')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'url']);
            $table->index(['workspace_id', 'store_id']);
        });

        Schema::create('lighthouse_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('store_url_id')->constrained('store_urls')->cascadeOnDelete();
            $table->timestamp('checked_at');
            // mobile or desktop.
            $table->string('strategy', 10)->default('mobile');
            // CrUX source: 'url' | 'origin' | null (INSUFFICIENT_DATA).
            $table->string('crux_source', 10)->nullable();
            // Lighthouse lab scores.
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
            // CrUX field data (p75 from real Chrome users).
            $table->integer('crux_lcp_p75_ms')->nullable();
            $table->integer('crux_inp_p75_ms')->nullable();
            $table->decimal('crux_cls_p75', 6, 4)->nullable();
            $table->integer('crux_fcp_p75_ms')->nullable();
            // EXPERIMENTAL per Google — lower confidence.
            $table->integer('crux_ttfb_p75_ms')->nullable();
            // Platform-owned PSI API response. Paired with api_version per CLAUDE.md gotcha.
            $table->jsonb('raw_response')->nullable();
            $table->string('raw_response_api_version', 16)->nullable();
            // Good/NI/Poor distribution percentages for each CrUX metric (0.00–100.00).
            // Nullable: null = INSUFFICIENT_DATA.
            $table->decimal('crux_lcp_good_pct',  5, 2)->nullable();
            $table->decimal('crux_lcp_ni_pct',    5, 2)->nullable();
            $table->decimal('crux_lcp_poor_pct',  5, 2)->nullable();
            $table->decimal('crux_inp_good_pct',  5, 2)->nullable();
            $table->decimal('crux_inp_ni_pct',    5, 2)->nullable();
            $table->decimal('crux_inp_poor_pct',  5, 2)->nullable();
            $table->decimal('crux_cls_good_pct',  5, 2)->nullable();
            $table->decimal('crux_cls_ni_pct',    5, 2)->nullable();
            $table->decimal('crux_cls_poor_pct',  5, 2)->nullable();
            $table->decimal('crux_ttfb_good_pct', 5, 2)->nullable();
            $table->decimal('crux_ttfb_ni_pct',   5, 2)->nullable();
            $table->decimal('crux_ttfb_poor_pct', 5, 2)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['store_url_id', 'checked_at']);
            $table->index(['workspace_id', 'checked_at']);
        });

        // Partitioned table — PRIMARY KEY must include partition key (checked_at).
        DB::statement("
            CREATE TABLE uptime_checks (
                id BIGSERIAL NOT NULL,
                workspace_id BIGINT NOT NULL,
                store_id BIGINT NOT NULL,
                store_url_id BIGINT NOT NULL,
                probe_id VARCHAR(50) NOT NULL,
                checked_at TIMESTAMP NOT NULL,
                is_up BOOLEAN NOT NULL,
                status_code SMALLINT NULL,
                response_time_ms INT NULL,
                error_message VARCHAR(500) NULL,
                created_at TIMESTAMP NULL,
                CONSTRAINT uptime_checks_pkey PRIMARY KEY (id, checked_at)
            ) PARTITION BY RANGE (checked_at)
        ");

        DB::statement('ALTER TABLE uptime_checks ADD CONSTRAINT uptime_checks_workspace_id_fkey FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE uptime_checks ADD CONSTRAINT uptime_checks_store_id_fkey FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE uptime_checks ADD CONSTRAINT uptime_checks_store_url_id_fkey FOREIGN KEY (store_url_id) REFERENCES store_urls(id) ON DELETE CASCADE');

        // Create initial partitions: current month + next 2 months.
        $current = now()->startOfMonth();
        for ($i = 0; $i < 3; $i++) {
            $from = $current->copy()->addMonths($i);
            $to   = $from->copy()->addMonth();
            $name = $from->format('Y_m');
            DB::statement("CREATE TABLE uptime_checks_{$name} PARTITION OF uptime_checks FOR VALUES FROM ('{$from->format('Y-m-d')}') TO ('{$to->format('Y-m-d')}')");
        }

        DB::statement('CREATE INDEX uptime_checks_store_url_checked ON uptime_checks (store_url_id, checked_at)');
        DB::statement('CREATE INDEX uptime_checks_workspace_up_checked ON uptime_checks (workspace_id, is_up, checked_at)');

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
        DB::statement('DROP TABLE IF EXISTS uptime_checks CASCADE');
        Schema::dropIfExists('lighthouse_snapshots');
        Schema::dropIfExists('store_urls');
    }
};
