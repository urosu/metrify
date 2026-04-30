<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\StoreUrl;
use App\Services\WorkspaceContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Makes an HTTP HEAD probe to a single monitored URL and records the result.
 *
 * Triggered by: routes/console.php every 5 minutes (per active store_url).
 * Also dispatched immediately when a store_url is first created (Phase 2).
 *
 * Writes to:   uptime_checks (one row per probe, partitioned by month)
 * Side-effect: upserts uptime_daily_summaries for today after every insert
 *              so the Performance page has fresh uptime_pct without waiting for
 *              the cleanup job that normally drives the summaries table.
 *
 * A failed probe IS a data point — tries=1, no retry. Capturing "the site was
 * down at T" is more valuable than silently dropping a failure and retrying.
 *
 * Queue:   uptime
 * Timeout: 15 s (10 s HTTP + overhead)
 * Tries:   1 (intentional — see above)
 *
 * @see docs/pages/performance.md
 * @see docs/planning/backend.md#performance-monitoring
 * @see app/Models/UptimeCheck.php
 * @see app/Models/UptimeDailySummary.php
 */
class RunUptimeCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 15;
    public int $tries   = 1;

    public function __construct(
        private readonly int $storeUrlId,
    ) {
        $this->onQueue('uptime');
    }

    public function handle(): void
    {
        $storeUrl = StoreUrl::withoutGlobalScopes()
            ->with(['workspace', 'store'])
            ->find($this->storeUrlId);

        if ($storeUrl === null || ! $storeUrl->is_active) {
            return;
        }

        // WorkspaceScope is safe to set now that we have the workspace_id.
        app(WorkspaceContext::class)->set((int) $storeUrl->workspace_id);

        $isUp          = false;
        $statusCode    = null;
        $responseMs    = null;
        $errorMessage  = null;

        $startTime = microtime(true);

        try {
            $response = Http::timeout(10)->head($storeUrl->url);

            $responseMs = (int) round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();
            // 2xx and 3xx are "up"; 4xx/5xx indicate application-level errors.
            $isUp       = $statusCode >= 200 && $statusCode <= 399;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $responseMs   = (int) round((microtime(true) - $startTime) * 1000);
            $isUp         = false;
            $errorMessage = $e->getMessage();
        } catch (\Throwable $e) {
            $responseMs   = (int) round((microtime(true) - $startTime) * 1000);
            $isUp         = false;
            $errorMessage = $e->getMessage();

            Log::warning('RunUptimeCheckJob: unexpected error during probe', [
                'store_url_id' => $this->storeUrlId,
                'url'          => $storeUrl->url,
                'error'        => $e->getMessage(),
            ]);
        }

        $now = now();

        DB::table('uptime_checks')->insert([
            'workspace_id'    => (int) $storeUrl->workspace_id,
            'store_id'        => (int) $storeUrl->store_id,
            'store_url_id'    => $this->storeUrlId,
            'probe_id'        => 'nexstage-primary',
            'checked_at'      => $now,
            'is_up'           => $isUp,
            'status_code'     => $statusCode,
            'response_time_ms' => $responseMs,
            'error_message'   => $errorMessage,
        ]);

        $this->updateDailySummary((int) $storeUrl->workspace_id);

        Log::info('RunUptimeCheckJob: probe complete', [
            'store_url_id'  => $this->storeUrlId,
            'url'           => $storeUrl->url,
            'is_up'         => $isUp,
            'status_code'   => $statusCode,
            'response_ms'   => $responseMs,
        ]);
    }

    /**
     * Upsert today's aggregate row in uptime_daily_summaries from the raw checks table.
     *
     * Single raw-SQL upsert avoids an N-query loop and is safe to call after every
     * probe. PostgreSQL ON CONFLICT requires the target columns to match the unique
     * index on (store_url_id, date) defined in the migration.
     *
     * The uptime_pct is ROUND(…, 2) to match the numeric(5,2) column type.
     * avg_response_ms is cast to integer because the column is int4.
     */
    private function updateDailySummary(int $workspaceId): void
    {
        DB::statement(
            "INSERT INTO uptime_daily_summaries
                 (store_url_id, workspace_id, date, checks_total, checks_up, uptime_pct, avg_response_ms, created_at)
             SELECT
                 store_url_id,
                 workspace_id,
                 DATE(checked_at),
                 COUNT(*)::int,
                 SUM(is_up::int)::int,
                 ROUND(SUM(is_up::int)::numeric / COUNT(*) * 100, 2),
                 ROUND(AVG(response_time_ms))::int,
                 NOW()
             FROM uptime_checks
             WHERE store_url_id = ?
               AND DATE(checked_at) = CURRENT_DATE
             GROUP BY store_url_id, workspace_id, DATE(checked_at)
             ON CONFLICT (store_url_id, date) DO UPDATE SET
                 checks_total    = EXCLUDED.checks_total,
                 checks_up       = EXCLUDED.checks_up,
                 uptime_pct      = EXCLUDED.uptime_pct,
                 avg_response_ms = EXCLUDED.avg_response_ms",
            [$this->storeUrlId],
        );
    }
}
