<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin queue inspector — running, pending, and failed jobs.
 *
 * Purpose: Shows currently executing jobs (via integration_runs status=running),
 *          pending jobs from the Laravel jobs table, and exhausted-retry jobs
 *          from failed_jobs. Read-only; no mutations.
 *
 * Reads:  integration_runs (status=running), jobs table, failed_jobs table.
 * Writes: nothing.
 * Callers: routes/web.php admin group (GET /admin/queue).
 *
 * @see docs/planning/backend.md#6
 */
class AdminQueueController extends Controller
{
    public function __invoke(): Response
    {
        // Currently executing jobs — tracked via sync_logs status='running'.
        // Sorted by started_at ascending so longest-running jobs appear first.
        $running = IntegrationRun::withoutGlobalScopes()
            ->with('workspace:id,name')
            ->where('status', 'running')
            ->orderBy('started_at')
            ->limit(100)
            ->get()
            ->map(fn ($l) => [
                'id'                => $l->id,
                'workspace'         => $l->workspace ? ['id' => $l->workspace->id, 'name' => $l->workspace->name] : null,
                'job_type'          => $l->job_type,
                'queue'             => $l->queue,
                'attempt'           => $l->attempt,
                'records_processed' => $l->records_processed,
                'started_at'        => $l->started_at?->toISOString(),
            ]);

        // Pending jobs waiting to be picked up by Horizon workers.
        // available_at is a Unix timestamp — jobs with available_at > now() are delayed.
        // Extract displayName directly in Postgres to avoid fetching and decoding full payload blobs.
        // replace() strips   null bytes that cause jsonb cast errors on some job payloads.
        $pending = DB::table('jobs')
            ->select([
                'id', 'queue', 'attempts', 'available_at', 'created_at',
                DB::raw("replace(payload, '\\u0000', '')::jsonb->>'displayName' AS display_name_fq"),
            ])
            ->orderBy('available_at')
            ->limit(200)
            ->get()
            ->map(function ($j) {
                $parts = $j->display_name_fq ? explode('\\', $j->display_name_fq) : [];
                return [
                    'id'           => $j->id,
                    'queue'        => $j->queue,
                    'display_name' => $parts ? end($parts) : '?',
                    'attempts'     => $j->attempts,
                    'available_at' => Carbon::createFromTimestamp($j->available_at)->toISOString(),
                    'created_at'   => Carbon::createFromTimestamp($j->created_at)->toISOString(),
                ];
            });

        // Jobs that have exhausted all retries and landed in failed_jobs.
        // replace(payload, ' ', '') strips null bytes that cause Postgres jsonb cast errors.
        $failedQueue = DB::table('failed_jobs')
            ->select([
                'id', 'uuid', 'queue', 'failed_at', 'exception',
                DB::raw("replace(payload, '\\u0000', '')::jsonb->>'displayName' AS display_name_fq"),
            ])
            ->orderByDesc('failed_at')
            ->limit(100)
            ->get()
            ->map(function ($j) {
                $parts = $j->display_name_fq ? explode('\\', $j->display_name_fq) : [];
                return [
                    'id'           => $j->id,
                    'uuid'         => $j->uuid,
                    'queue'        => $j->queue,
                    'display_name' => $parts ? end($parts) : '?',
                    'exception'    => mb_substr($j->exception, 0, 1000),
                    'failed_at'    => $j->failed_at,
                ];
            });

        return Inertia::render('Admin/Queue', [
            'running'      => $running,
            'pending'      => $pending,
            'failed_queue' => $failedQueue,
        ]);
    }
}
