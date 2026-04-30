<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\IntegrationCredential;
use App\Models\IntegrationRun;
use App\Models\Store;
use App\Models\Workspace;
use App\Services\Integrations\Shopify\ShopifyConnector;
use App\Services\WorkspaceContext;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pulls daily session counts from the Shopify Analytics API and upserts them
 * into shopify_daily_sessions.
 *
 * Queue:   sync-store
 * Timeout: 120 s
 * Tries:   3
 * Backoff: [60, 300, 900] s
 *
 * Window:
 *   Default: last 7 days. Shopify analytics data has a ~2-day revision window,
 *   so pulling 7 days ensures any late-arriving corrections are captured.
 *   BackfillShopifySessionsCommand passes a larger window for historical fills.
 *
 * Re-auth note:
 *   The read_analytics scope is required for session data. Stores connected
 *   before this scope was added will receive empty responses — ShopifyConnector
 *   logs a warning and the job exits cleanly with records_processed=0.
 *
 * Scope changes:
 *   Adding read_analytics to config/shopify.php 'scopes' is non-breaking for
 *   existing connections — the scope is only evaluated when the merchant
 *   re-authorises. Existing stores continue working; they just won't have sessions
 *   until re-auth.
 *
 * Dispatched by: routes/console.php schedule ('sync-shopify-analytics' at 04:00)
 * Reads from:    Shopify Analytics REST API (ShopifyQL endpoint)
 * Writes to:     shopify_daily_sessions
 * Called by:     BackfillShopifySessionsCommand (dispatchSync for one-shot CLI)
 *
 * @see app/Services/Integrations/Shopify/ShopifyConnector::fetchDailySessions()
 * @see app/Console/Commands/BackfillShopifySessionsCommand.php
 * @see docs/planning/backend.md §2 (Shopify analytics connector)
 */
class SyncShopifyAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        private readonly int    $storeId,
        private readonly int    $workspaceId,
        /** Start date Y-m-d; default is 7 days ago (set in handle()). */
        private readonly ?string $startDate = null,
        /** End date Y-m-d; default is yesterday (set in handle()). */
        private readonly ?string $endDate = null,
    ) {
        $this->onQueue('sync-store');
    }

    public function handle(): void
    {
        // WorkspaceScope requires context to be set before any scoped query.
        // See CLAUDE.md gotcha: "Queue jobs don't inherit request scope."
        app(WorkspaceContext::class)->set($this->workspaceId);

        if ($this->isWorkspaceFrozen()) {
            Log::info('SyncShopifyAnalyticsJob: skipped — workspace trial expired', [
                'workspace_id' => $this->workspaceId,
            ]);
            return;
        }

        $store = Store::find($this->storeId);

        if ($store === null || $store->status !== 'active') {
            Log::info('SyncShopifyAnalyticsJob: store not found or inactive', [
                'store_id' => $this->storeId,
            ]);
            return;
        }

        // Skip real API calls for seeded/demo credentials — data already populated by seeders.
        $credential = IntegrationCredential::withoutGlobalScopes()
            ->where('integrationable_type', Store::class)
            ->where('integrationable_id', $this->storeId)
            ->first();
        if ($credential?->is_seeded) {
            Log::info('SyncShopifyAnalyticsJob: skipping seeded credential', ['store_id' => $this->storeId]);
            return;
        }

        // Resolve date range — default window is 7 days to capture Shopify revision lag.
        $start = $this->startDate ?? Carbon::now()->subDays(7)->toDateString();
        $end   = $this->endDate   ?? Carbon::yesterday()->toDateString();

        $syncLog = IntegrationRun::create([
            'workspace_id'         => $this->workspaceId,
            'integrationable_type' => Store::class,
            'integrationable_id'   => $this->storeId,
            'job_type'             => self::class,
            'status'               => 'running',
            'records_processed'    => 0,
            'started_at'           => now(),
            'queue'                => $this->queue,
            'attempt'              => $this->attempts(),
            'timeout_seconds'      => $this->timeout,
        ]);

        try {
            $connector = new ShopifyConnector($store);
            $rows      = $connector->fetchDailySessions($start, $end);

            $upserted = $this->upsertRows($rows, $store->workspace_id);

            $syncLog->update([
                'status'            => 'completed',
                'records_processed' => $upserted,
                'completed_at'      => now(),
                'duration_seconds'  => (int) max(0, (int) now()->diffInSeconds($syncLog->started_at)),
            ]);

            Log::info('SyncShopifyAnalyticsJob: completed', [
                'store_id' => $this->storeId,
                'start'    => $start,
                'end'      => $end,
                'rows'     => $upserted,
            ]);
        } catch (\Throwable $e) {
            $syncLog->update([
                'status'           => 'failed',
                'error_message'    => mb_substr($e->getMessage(), 0, 500),
                'completed_at'     => now(),
                'duration_seconds' => (int) max(0, (int) now()->diffInSeconds($syncLog->started_at)),
            ]);

            Log::error('SyncShopifyAnalyticsJob: failed', [
                'store_id' => $this->storeId,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Upsert fetched session rows into shopify_daily_sessions.
     *
     * The source=NULL row is the store-total aggregate that SnapshotBuilderService reads.
     * Only visits and visitors are updated on conflict — synced_at and updated_at refresh.
     *
     * @param  array<int, array{date: string, visits: int, visitors: int|null}> $rows
     * @return int  Number of rows upserted.
     */
    private function upsertRows(array $rows, int $workspaceId): int
    {
        if (empty($rows)) {
            return 0;
        }

        $now        = now()->toDateTimeString();
        $upsertRows = [];

        foreach ($rows as $row) {
            $upsertRows[] = [
                'workspace_id' => $workspaceId,
                'store_id'     => $this->storeId,
                'date'         => $row['date'],
                'visits'       => $row['visits'],
                'visitors'     => $row['visitors'] ?? null,
                'source'       => null, // store-total aggregate; source breakdown not fetched here
                'synced_at'    => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        DB::table('shopify_daily_sessions')->upsert(
            $upsertRows,
            uniqueBy: ['store_id', 'date', 'source'],
            update: ['visits', 'visitors', 'synced_at', 'updated_at'],
        );

        return count($upsertRows);
    }

    private function isWorkspaceFrozen(): bool
    {
        $workspace = Workspace::withoutGlobalScopes()
            ->select(['id', 'trial_ends_at', 'billing_plan'])
            ->find($this->workspaceId);

        return $workspace !== null
            && $workspace->trial_ends_at !== null
            && $workspace->trial_ends_at->lt(now())
            && $workspace->billing_plan === null;
    }
}
