<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_logs', function (Blueprint $table) {
            // When the job was dispatched — equals created_at for immediate jobs,
            // or a future timestamp for delayed dispatches (e.g. rate-limit backoff).
            $table->timestamp('scheduled_at')->nullable()->after('completed_at');

            // Which Horizon queue: critical | high | default | low
            $table->string('queue', 50)->nullable()->after('scheduled_at');

            // Retry attempt number. 1 = first run, 2+ = retries.
            $table->smallInteger('attempt')->default(1)->after('queue');

            // Job constructor arguments for debugging (store_id, date_range, etc.).
            // Stored as JSONB so it can be queried if needed.
            $table->jsonb('payload')->nullable()->after('attempt');
        });
    }

    public function down(): void
    {
        Schema::table('sync_logs', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'queue', 'attempt', 'payload']);
        });
    }
};
