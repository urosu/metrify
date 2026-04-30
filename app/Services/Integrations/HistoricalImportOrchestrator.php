<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Standardises the 5 historical import jobs (WC orders, Shopify orders, ad insights, GSC, GA4).
 *
 * Creates a `historical_import_jobs` row on `start()`, dispatches the appropriate platform
 * job, and provides progress + lifecycle management (resume, cancel).
 *
 * Each chunk completion calls an UPDATE on the job row (progress_pct, total_rows_imported,
 * checkpoint) from within the job; this service only manages the row lifecycle and dispatch.
 *
 * Frontend polls /integrations/imports/{id}/status at 5s via Inertia partial reload.
 *
 * Reads:  historical_import_jobs
 * Writes: historical_import_jobs
 * Called by: StartHistoricalImportAction, IntegrationsController retry flows,
 *            TriggerReactivationBackfillJob
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/planning/backend.md §13 (HistoricalImportOrchestrator detail)
 * @see docs/planning/schema.md §1.7 (historical_import_jobs table)
 */
class HistoricalImportOrchestrator
{
    private const JOB_CLASS_MAP = [
        'shopify_orders'     => \App\Jobs\ShopifyHistoricalImportJob::class,
        'woocommerce_orders' => \App\Jobs\WooCommerceHistoricalImportJob::class,
        'ad_insights'        => \App\Jobs\AdHistoricalImportJob::class,
        'gsc'                => \App\Jobs\GscHistoricalImportJob::class,
        'ga4'                => \App\Jobs\Ga4HistoricalImportJob::class,
    ];

    /**
     * Create a new import job row and dispatch the platform-specific job.
     *
     * @param  object  $integration  Any of: Store, AdAccount, SearchConsoleProperty.
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return int  The `historical_import_jobs.id` of the created row.
     *
     * @throws \InvalidArgumentException for unsupported job types.
     */
    public function start(object $integration, Carbon $from, Carbon $to): int
    {
        $jobType = $this->resolveJobType($integration);

        $id = DB::table('historical_import_jobs')->insertGetId([
            'workspace_id'          => $integration->workspace_id,
            'integrationable_type'  => get_class($integration),
            'integrationable_id'    => $integration->id,
            'job_type'              => $jobType,
            'status'                => 'pending',
            'from_date'             => $from->toDateString(),
            'to_date'               => $to->toDateString(),
            'total_rows_estimated'  => null,
            'total_rows_imported'   => 0,
            'progress_pct'          => 0,
            'checkpoint'            => json_encode([]),
            'started_at'            => null,
            'completed_at'          => null,
            'duration_seconds'      => null,
            'error_message'         => null,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $jobClass = self::JOB_CLASS_MAP[$jobType];
        dispatch(new $jobClass($id, $integration->workspace_id));

        return $id;
    }

    /**
     * Return current progress snapshot for a job.
     *
     * @return object|null  The `historical_import_jobs` row, or null if not found.
     */
    public function progressFor(int $jobId): ?object
    {
        return DB::table('historical_import_jobs')
            ->where('id', $jobId)
            ->first([
                'id', 'status', 'job_type', 'from_date', 'to_date',
                'total_rows_estimated', 'total_rows_imported', 'progress_pct',
                'started_at', 'completed_at', 'error_message',
            ]);
    }

    /**
     * Resume a failed or cancelled import from its last checkpoint.
     *
     * Re-dispatches the same job class; the job reads `checkpoint` to continue.
     *
     * @throws \RuntimeException if the job is not in a resumable state.
     */
    public function resume(int $jobId): void
    {
        $row = DB::table('historical_import_jobs')->where('id', $jobId)->first();

        if ($row === null) {
            return;
        }

        if (!in_array($row->status, ['failed', 'cancelled'], true)) {
            throw new \RuntimeException("Job {$jobId} is not in a resumable state (status: {$row->status}).");
        }

        DB::table('historical_import_jobs')->where('id', $jobId)->update([
            'status'        => 'pending',
            'error_message' => null,
            'updated_at'    => now(),
        ]);

        $jobClass = self::JOB_CLASS_MAP[$row->job_type] ?? null;

        if ($jobClass !== null) {
            dispatch(new $jobClass($jobId, (int) $row->workspace_id));
        }
    }

    /**
     * Cancel a pending or running import.
     *
     * Sets status to 'cancelled'. The running job will check this flag before
     * each chunk and abort cleanly.
     */
    public function cancel(int $jobId): void
    {
        DB::table('historical_import_jobs')
            ->where('id', $jobId)
            ->whereIn('status', ['pending', 'running'])
            ->update([
                'status'     => 'cancelled',
                'updated_at' => now(),
            ]);
    }

    /**
     * Resolve the job_type slug from an integration model instance.
     */
    private function resolveJobType(object $integration): string
    {
        $class = get_class($integration);

        return match (true) {
            str_ends_with($class, 'Store') && ($integration->platform ?? '') === 'shopify' => 'shopify_orders',
            str_ends_with($class, 'Store')                                                  => 'woocommerce_orders',
            str_ends_with($class, 'AdAccount')                                              => 'ad_insights',
            str_ends_with($class, 'SearchConsoleProperty')                                  => 'gsc',
            str_ends_with($class, 'Ga4Property')                                            => 'ga4',
            default => throw new \InvalidArgumentException("Cannot resolve job type for " . $class),
        };
    }
}
