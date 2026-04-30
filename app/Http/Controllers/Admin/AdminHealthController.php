<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BackfillAttributionDataJob;
use App\Models\IntegrationRun;
use App\Models\Order;
use App\Models\Store;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin system health dashboard.
 *
 * Purpose: Per-queue depth + wait time, sync freshness per store, NULL FX
 *          rate counts, and attribution backfill progress per workspace.
 *          Also exposes API quota telemetry (shared with AdminOverviewController).
 *          All data is read-only; no caching here (low-traffic admin page).
 *
 * Reads:  jobs table, failed_jobs table, stores, orders, workspaces,
 *         historical_import_jobs, integration_runs, Cache (quota + backfill keys).
 * Writes: nothing.
 * Callers: routes/web.php admin group (GET /admin/system-health).
 *
 * @see docs/planning/backend.md#6
 * @see PLANNING.md section 22.5 (Observability)
 */
class AdminHealthController extends Controller
{
    public function __invoke(): Response
    {
        // ── Queue depth per named queue ────────────────────────────────────────
        // Read directly from the `jobs` table; Horizon API requires Redis access.
        // `available_at` <= now() = immediately ready; > now() = delayed/scheduled.
        $knownQueues = [
            'critical-webhooks',
            'sync-facebook',
            'sync-google-ads',
            'sync-google-search',
            'sync-store',
            'sync-psi',
            'imports-store',
            'imports-ads',
            'imports-gsc',
            'default',
            'low',
        ];

        $depthRows = DB::table('jobs')
            ->selectRaw('queue, COUNT(*) AS depth, MIN(available_at) AS oldest_available_at')
            ->groupBy('queue')
            ->get()
            ->keyBy('queue');

        $failedRows = DB::table('failed_jobs')
            ->selectRaw('queue, COUNT(*) AS failed_count')
            ->groupBy('queue')
            ->get()
            ->keyBy('queue');

        $now = now()->timestamp;
        $queues = array_map(function (string $queue) use ($depthRows, $failedRows, $now): array {
            $row    = $depthRows->get($queue);
            $failed = $failedRows->get($queue);
            $depth  = $row ? (int) $row->depth : 0;
            // Wait time: seconds since the oldest ready job was pushed.
            $waitSeconds = ($row && $row->oldest_available_at)
                ? max(0, $now - (int) $row->oldest_available_at)
                : 0;

            return [
                'queue'        => $queue,
                'depth'        => $depth,
                'wait_seconds' => $waitSeconds,
                'failed_count' => $failed ? (int) $failed->failed_count : 0,
            ];
        }, $knownQueues);

        // ── Sync freshness per store ────────────────────────────────────────────
        // A store is considered stale when its last_synced_at is > 2 hours ago (for active stores)
        // OR when consecutive_sync_failures > 0. Webhook health is derived from store_webhooks.
        // historical_import_status was dropped from stores in the L2 schema rebuild — derived from historical_import_jobs.
        $storesRaw = Store::withoutGlobalScopes()
            ->with('workspace:id,name,slug')
            ->select([
                'id', 'workspace_id', 'name', 'status',
                'last_synced_at', 'consecutive_sync_failures',
            ])
            ->orderBy('workspace_id')
            ->orderBy('name')
            ->get();

        $storeIds = $storesRaw->pluck('id');
        $storeImportStatuses = DB::table('historical_import_jobs')
            ->whereIn('integrationable_id', $storeIds)
            ->where('integrationable_type', Store::class)
            ->orderByDesc('created_at')
            ->get(['integrationable_id', 'status'])
            ->groupBy('integrationable_id')
            ->map(fn ($rows) => $rows->first()->status);

        $stores = $storesRaw->map(function ($s) use ($storeImportStatuses): array {
                $staleThreshold = now()->subHours(2);
                $isStale = $s->status === 'active'
                    && ($s->last_synced_at === null || $s->last_synced_at->lt($staleThreshold));

                return [
                    'id'                        => $s->id,
                    'workspace'                 => $s->workspace
                        ? ['id' => $s->workspace->id, 'name' => $s->workspace->name]
                        : null,
                    'name'                      => $s->name,
                    'status'                    => $s->status,
                    'last_synced_at'            => $s->last_synced_at?->toISOString(),
                    'consecutive_sync_failures' => $s->consecutive_sync_failures ?? 0,
                    'historical_import_status'  => $storeImportStatuses[$s->id] ?? null,
                    'is_stale'                  => $isStale,
                ];
            });

        // ── NULL FX rate counts ────────────────────────────────────────────────
        // Orders with total_in_reporting_currency = NULL failed FX conversion.
        // RetryMissingConversionJob handles these nightly. High counts indicate FX feed issues.
        $nullFxTotal = Order::withoutGlobalScopes()
            ->whereNull('total_in_reporting_currency')
            ->whereIn('status', ['completed', 'processing'])
            ->count();

        $nullFxByWorkspace = Order::withoutGlobalScopes()
            ->whereNull('total_in_reporting_currency')
            ->whereIn('status', ['completed', 'processing'])
            ->selectRaw('workspace_id, COUNT(*) AS null_count')
            ->groupBy('workspace_id')
            ->orderByDesc('null_count')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'workspace_id' => $r->workspace_id,
                'null_count'   => (int) $r->null_count,
            ]);

        // ── Attribution backfill progress per workspace ─────────────────────────
        // Fetch all progress keys in a single Redis MGET instead of N individual GETs.
        $workspaceIds = Workspace::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->pluck('id', 'name');

        $cacheKeys = $workspaceIds->map(
            fn (int $id) => BackfillAttributionDataJob::cacheKey($id)
        )->values()->all();

        $progressValues = Cache::many($cacheKeys);

        $backfillProgress = [];
        foreach ($workspaceIds as $name => $id) {
            $key = BackfillAttributionDataJob::cacheKey($id);
            $backfillProgress[] = [
                'workspace_id'   => $id,
                'workspace_name' => $name,
                'progress'       => $progressValues[$key] ?? null,
            ];
        }

        // ── Currently running jobs (from sync_logs) ────────────────────────────
        $runningCount = IntegrationRun::withoutGlobalScopes()
            ->where('status', 'running')
            ->count();

        return Inertia::render('Admin/SystemHealth', [
            'queues'             => $queues,
            'stores'             => $stores,
            'null_fx_total'      => $nullFxTotal,
            'null_fx_breakdown'  => $nullFxByWorkspace,
            'backfill_progress'  => $backfillProgress,
            'running_jobs'       => $runningCount,
            'api_quotas'         => $this->getApiQuotas(),
        ]);
    }

    /**
     * Read API quota snapshots from cache (written by platform API clients).
     *
     * @return array<string, mixed>
     */
    private function getApiQuotas(): array
    {
        $today = now()->toDateString();

        $fbUsage          = Cache::get('facebook_api_usage');
        $fbThrottledUntil = Cache::get('facebook_api_throttled_until');
        $fbLastThrottleAt = Cache::get('facebook_api_last_throttle_at');
        $fbHitsToday      = (int) Cache::get('facebook_api_rate_limit_hits_' . $today, 0);
        $fbCallsToday     = (int) Cache::get('facebook_api_calls_' . $today, 0);
        $fbLastSuccessAt  = Cache::get('facebook_api_last_success_at');

        $gadsThrottledUntil = Cache::get('google_ads_throttled_until');
        $gadsLastThrottleAt = Cache::get('google_ads_last_throttle_at');
        $gadsHitsToday      = (int) Cache::get('google_ads_rate_limit_hits_' . $today, 0);
        $gadsCallsToday     = (int) Cache::get('google_ads_calls_' . $today, 0);
        $gadsLastSuccessAt  = Cache::get('google_ads_last_success_at');

        $gscThrottledUntil = Cache::get('gsc_throttled_until');
        $gscLastThrottleAt = Cache::get('gsc_last_throttle_at');
        $gscHitsToday      = (int) Cache::get('gsc_rate_limit_hits_' . $today, 0);
        $gscCallsToday     = (int) Cache::get('gsc_calls_' . $today, 0);
        $gscLastSuccessAt  = Cache::get('gsc_last_success_at');

        $psiThrottledUntil = Cache::get('psi_throttled_until');
        $psiLastThrottleAt = Cache::get('psi_last_throttle_at');
        $psiHitsToday      = (int) Cache::get('psi_rate_limit_hits_' . $today, 0);
        $psiCallsToday     = (int) Cache::get('psi_calls_' . $today, 0);
        $psiLastSuccessAt  = Cache::get('psi_last_success_at');

        return [
            'facebook' => [
                'usage_pct'        => $fbUsage ? (int) $fbUsage['pct'] : null,
                'tier'             => $fbUsage ? (string) $fbUsage['tier'] : null,
                'threshold_pct'    => $fbUsage ? (int) $fbUsage['threshold'] : null,
                'hard_cap_pct'     => $fbUsage ? ($fbUsage['hard_cap'] ?? null) : null,
                'observed_at'      => $fbUsage ? (string) $fbUsage['observed_at'] : null,
                'throttled_until'  => $fbThrottledUntil ?? null,
                'last_throttle_at' => $fbLastThrottleAt ?? null,
                'hits_today'       => $fbHitsToday,
                'calls_today'      => $fbCallsToday,
                'last_success_at'  => $fbLastSuccessAt ?? null,
            ],
            'google_ads' => [
                'throttled_until'  => $gadsThrottledUntil ?? null,
                'last_throttle_at' => $gadsLastThrottleAt ?? null,
                'hits_today'       => $gadsHitsToday,
                'calls_today'      => $gadsCallsToday,
                'last_success_at'  => $gadsLastSuccessAt ?? null,
            ],
            'gsc' => [
                'throttled_until'  => $gscThrottledUntil ?? null,
                'last_throttle_at' => $gscLastThrottleAt ?? null,
                'hits_today'       => $gscHitsToday,
                'calls_today'      => $gscCallsToday,
                'last_success_at'  => $gscLastSuccessAt ?? null,
            ],
            'psi' => [
                'throttled_until'  => $psiThrottledUntil ?? null,
                'last_throttle_at' => $psiLastThrottleAt ?? null,
                'hits_today'       => $psiHitsToday,
                'calls_today'      => $psiCallsToday,
                'last_success_at'  => $psiLastSuccessAt ?? null,
            ],
        ];
    }
}
