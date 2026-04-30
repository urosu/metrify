<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Retain only the last 30 days of integration_events (PII in payload JSONB).
 *
 * Deletes rows where received_at < NOW() - 30 days in chunks to avoid
 * locking the table during the delete.
 *
 * Queue:     low
 * Schedule:  weekly Sun 03:15 UTC
 * Timeout:   300 s
 * Tries:     3
 *
 * Dispatched by: schedule (global — touches all workspaces)
 *
 * @see docs/planning/backend.md §3.3 (job spec)
 * @see docs/planning/schema.md §1.7 (integration_events table)
 */
class CleanupOldIntegrationEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 3;

    private const RETENTION_DAYS = 30;
    private const CHUNK_SIZE = 5000;

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        $cutoff = now()->subDays(self::RETENTION_DAYS);

        do {
            $deleted = DB::table('integration_events')
                ->where('received_at', '<', $cutoff)
                ->limit(self::CHUNK_SIZE)
                ->delete();
        } while ($deleted === self::CHUNK_SIZE);
    }
}
