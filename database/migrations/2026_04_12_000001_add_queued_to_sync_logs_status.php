<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE sync_logs DROP CONSTRAINT sync_logs_status_check');
        DB::statement("ALTER TABLE sync_logs ADD CONSTRAINT sync_logs_status_check CHECK (status IN ('queued','running','completed','failed'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sync_logs DROP CONSTRAINT sync_logs_status_check');
        DB::statement("ALTER TABLE sync_logs ADD CONSTRAINT sync_logs_status_check CHECK (status IN ('running','completed','failed'))");
    }
};
