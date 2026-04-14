<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Partitioned by month (PostgreSQL declarative partitioning) because at 10-min check
        // intervals across many stores, this table grows steadily. Monthly partitions keep
        // indexes small and make retention cleanup a partition DROP vs a slow DELETE.
        //
        // PRIMARY KEY must include the partition key (checked_at) — PostgreSQL requirement.
        // FKs are supported on partitioned tables in PostgreSQL 12+.
        //
        // IMPORTANT: CleanupPerformanceDataJob (Sunday 04:00 UTC) must create the next 2 months
        // of partitions each run. Without this, the first day of a new month with no partition
        // will hard-fail all inserts. See: PLANNING.md "uptime_checks — Partition management"
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

        DB::statement("ALTER TABLE uptime_checks ADD CONSTRAINT uptime_checks_workspace_id_fkey FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE");
        DB::statement("ALTER TABLE uptime_checks ADD CONSTRAINT uptime_checks_store_id_fkey FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE");
        DB::statement("ALTER TABLE uptime_checks ADD CONSTRAINT uptime_checks_store_url_id_fkey FOREIGN KEY (store_url_id) REFERENCES store_urls(id) ON DELETE CASCADE");

        // Create initial partitions: current month + next 2 months.
        // CleanupPerformanceDataJob will maintain the rolling 2-month forward window.
        $current = now()->startOfMonth();
        for ($i = 0; $i < 3; $i++) {
            $from = $current->copy()->addMonths($i);
            $to   = $from->copy()->addMonth();
            $name = $from->format('Y_m');
            DB::statement("CREATE TABLE uptime_checks_{$name} PARTITION OF uptime_checks FOR VALUES FROM ('{$from->format('Y-m-d')}') TO ('{$to->format('Y-m-d')}')");
        }

        // Indexes on the parent table cascade to all partitions.
        DB::statement('CREATE INDEX uptime_checks_store_url_checked ON uptime_checks (store_url_id, checked_at)');
        DB::statement('CREATE INDEX uptime_checks_workspace_up_checked ON uptime_checks (workspace_id, is_up, checked_at)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS uptime_checks CASCADE');
    }
};
