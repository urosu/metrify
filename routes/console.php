<?php

declare(strict_types=1);

use App\Jobs\CleanupOldSyncLogsJob;
use App\Jobs\WooCommerceHistoricalImportJob;
use App\Models\Alert;
use App\Models\SyncLog;
use App\Jobs\RefreshHolidaysJob;
use App\Jobs\CleanupOldWebhookLogsJob;
use App\Jobs\ComputeDailySnapshotJob;
use App\Jobs\ComputeHourlySnapshotsJob;
use App\Jobs\DispatchDailySnapshots;
use App\Jobs\DispatchHourlySnapshots;
use App\Jobs\GenerateAiSummaryJob;
use App\Jobs\PurgeDeletedWorkspaceJob;
use App\Jobs\RefreshOAuthTokenJob;
use App\Jobs\ReportMonthlyRevenueToStripeJob;
use App\Jobs\RetryMissingConversionJob;
use App\Jobs\RunLighthouseCheckJob;
use App\Jobs\SyncAdInsightsJob;
use App\Jobs\SyncProductsJob;
use App\Jobs\SyncSearchConsoleJob;
use App\Jobs\ReconcileStoreOrdersJob;
use App\Jobs\ComputeUtmCoverageJob;
use App\Jobs\SyncRecentRefundsJob;
use App\Jobs\SyncStoreOrdersJob;
use App\Jobs\UpdateFxRatesJob;
use App\Models\AdAccount;
use App\Models\SearchConsoleProperty;
use App\Models\Store;
use App\Models\StoreUrl;
use App\Models\Workspace;
use App\Scopes\WorkspaceScope;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ---------------------------------------------------------------------------
// Snapshot aggregation
// ---------------------------------------------------------------------------

Schedule::call(new DispatchDailySnapshots)
    ->dailyAt('00:30')
    ->name('dispatch-daily-snapshots')
    ->withoutOverlapping(10);

Schedule::call(new DispatchHourlySnapshots)
    ->dailyAt('00:45')
    ->name('dispatch-hourly-snapshots')
    ->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// AI summaries — staggered 01:00–02:00 UTC by (workspace_id % 60) minutes
// ---------------------------------------------------------------------------

Schedule::call(static function (): void {
    Workspace::withoutGlobalScope(WorkspaceScope::class)
        ->whereNull('deleted_at')
        // Why: skip frozen workspaces (trial expired + no paid plan). See PLANNING.md "14-day free trial".
        ->whereRaw('NOT (trial_ends_at < NOW() AND billing_plan IS NULL)')
        ->select(['id'])
        ->each(static function (Workspace $workspace): void {
            $delayMinutes = $workspace->id % 60;
            GenerateAiSummaryJob::dispatch($workspace->id)
                ->delay(now()->addMinutes($delayMinutes));
        });
})->dailyAt('01:00')->name('dispatch-ai-summaries')->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// Billing
// ---------------------------------------------------------------------------

Schedule::call(static function (): void {
    ReportMonthlyRevenueToStripeJob::dispatch();
})->monthlyOn(1, '06:00')->name('report-monthly-revenue-to-stripe')->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// Store syncs
// ---------------------------------------------------------------------------

// Products — nightly per active WooCommerce store
Schedule::call(static function (): void {
    Store::withoutGlobalScope(WorkspaceScope::class)
        ->join('workspaces', 'stores.workspace_id', '=', 'workspaces.id')
        ->where('stores.type', 'woocommerce')
        ->where('stores.status', 'active')
        ->whereNull('workspaces.deleted_at')
        ->whereRaw('NOT (workspaces.trial_ends_at < NOW() AND workspaces.billing_plan IS NULL)')
        ->select(['stores.id', 'stores.workspace_id'])
        ->each(static function (Store $store): void {
            SyncProductsJob::dispatch($store->id, (int) $store->workspace_id);
        });
})->dailyAt('02:00')->name('sync-products-dispatch')->withoutOverlapping(10);

// Refunds — nightly per active WooCommerce store (last 7 days)
Schedule::call(static function (): void {
    Store::withoutGlobalScope(WorkspaceScope::class)
        ->join('workspaces', 'stores.workspace_id', '=', 'workspaces.id')
        ->where('stores.type', 'woocommerce')
        ->where('stores.status', 'active')
        ->whereNull('workspaces.deleted_at')
        ->whereRaw('NOT (workspaces.trial_ends_at < NOW() AND workspaces.billing_plan IS NULL)')
        ->select(['stores.id', 'stores.workspace_id'])
        ->each(static function (Store $store): void {
            SyncRecentRefundsJob::dispatch($store->id, (int) $store->workspace_id);
        });
})->dailyAt('03:30')->name('sync-recent-refunds-dispatch')->withoutOverlapping(10);

// Reconciliation — nightly per active WooCommerce store (last 7 days vs. WC API)
// Why: catches any orders missed by webhook delivery failures or API outages.
// See: PLANNING.md "Webhook Reliability"
Schedule::call(static function (): void {
    Store::withoutGlobalScope(WorkspaceScope::class)
        ->join('workspaces', 'stores.workspace_id', '=', 'workspaces.id')
        ->where('stores.type', 'woocommerce')
        ->where('stores.status', 'active')
        ->whereNull('workspaces.deleted_at')
        ->whereRaw('NOT (workspaces.trial_ends_at < NOW() AND workspaces.billing_plan IS NULL)')
        ->select(['stores.id', 'stores.workspace_id'])
        ->each(static function (Store $store): void {
            ReconcileStoreOrdersJob::dispatch($store->id, (int) $store->workspace_id);
        });
})->dailyAt('01:30')->name('reconcile-store-orders-dispatch')->withoutOverlapping(10);

// Orders fallback — hourly per active WooCommerce store
Schedule::call(static function (): void {
    Store::withoutGlobalScope(WorkspaceScope::class)
        ->join('workspaces', 'stores.workspace_id', '=', 'workspaces.id')
        ->where('stores.type', 'woocommerce')
        ->where('stores.status', 'active')
        ->whereNull('workspaces.deleted_at')
        ->whereRaw('NOT (workspaces.trial_ends_at < NOW() AND workspaces.billing_plan IS NULL)')
        ->select(['stores.id', 'stores.workspace_id'])
        ->each(static function (Store $store): void {
            SyncStoreOrdersJob::dispatch($store->id, (int) $store->workspace_id);
        });
})->hourly()->name('sync-store-orders-dispatch')->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// OAuth token refresh
// ---------------------------------------------------------------------------

Schedule::call(static function (): void {
    RefreshOAuthTokenJob::dispatch();
})->dailyAt('05:00')->name('refresh-oauth-tokens')->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// FX rates
// ---------------------------------------------------------------------------

Schedule::job(new UpdateFxRatesJob)->dailyAt('06:00')->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// Missing FX conversion retry
// ---------------------------------------------------------------------------

Schedule::call(static function (): void {
    RetryMissingConversionJob::dispatch();
})->dailyAt('07:00')->name('retry-missing-conversions')->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// Ad insights — hourly per active ad account
//
// Why jitter: without it every account fires simultaneously, producing a burst
// that exhausts the per-account API quota within seconds. Spreading over 20 min
// keeps the request rate flat across the hourly window.
//
// Why hourly (not every 3h): structure sync (campaigns/ads) is gated to once/23h,
// so each hourly run is insights-only (~1 API call). Hourly cadence gives 3×
// fresher data at the same total API usage as a 3h cadence with 4+ calls/sync.
// ---------------------------------------------------------------------------

Schedule::call(static function (): void {
    AdAccount::withoutGlobalScope(WorkspaceScope::class)
        ->join('workspaces', 'ad_accounts.workspace_id', '=', 'workspaces.id')
        ->where('ad_accounts.status', 'active')
        ->whereNull('workspaces.deleted_at')
        ->whereRaw('NOT (workspaces.trial_ends_at < NOW() AND workspaces.billing_plan IS NULL)')
        ->select(['ad_accounts.id', 'ad_accounts.workspace_id'])
        ->each(static function (AdAccount $account): void {
            SyncAdInsightsJob::dispatch($account->id, (int) $account->workspace_id)
                ->delay(now()->addSeconds(random_int(0, 1_200)));
        });
})->hourly()->name('sync-ad-insights-dispatch')->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// Search Console — every 6 hours per active property
//
// Why jitter: same burst-prevention rationale as ad insights above.
// Spread over 30 min — GSC has a lower per-property quota than FB Ads.
// ---------------------------------------------------------------------------

Schedule::call(static function (): void {
    SearchConsoleProperty::withoutGlobalScope(WorkspaceScope::class)
        ->join('workspaces', 'search_console_properties.workspace_id', '=', 'workspaces.id')
        ->where('search_console_properties.status', 'active')
        ->whereNull('workspaces.deleted_at')
        ->whereRaw('NOT (workspaces.trial_ends_at < NOW() AND workspaces.billing_plan IS NULL)')
        ->select(['search_console_properties.id', 'search_console_properties.workspace_id'])
        ->each(static function (SearchConsoleProperty $property): void {
            SyncSearchConsoleJob::dispatch($property->id, (int) $property->workspace_id)
                ->delay(now()->addSeconds(random_int(0, 1_800)));
        });
})->everySixHours()->name('sync-search-console-dispatch')->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// Holidays — January 1st, regenerate for all countries with active workspaces
// ---------------------------------------------------------------------------

Schedule::call(static function (): void {
    $year = (int) now()->format('Y');

    // Collect distinct, non-null country codes from all non-deleted workspaces.
    Workspace::withoutGlobalScope(WorkspaceScope::class)
        ->whereNull('deleted_at')
        ->whereNotNull('country')
        ->distinct()
        ->pluck('country')
        ->each(static function (string $countryCode) use ($year): void {
            RefreshHolidaysJob::dispatch($countryCode, $year)->onQueue('low');
        });
})->yearlyOn(1, 1, '00:15')->name('refresh-holidays')->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// Stuck import detection — every 10 minutes
//
// Why: WooCommerceHistoricalImportJob writes updates after every page, so if
// a store's import_status has been "pending" >15 min or "running" >60 min
// without any DB update, the job was lost (Horizon restart, OOM, etc.).
// We mark it "failed" so the onboarding UI shows the "Try again" button
// instead of spinning forever.
// ---------------------------------------------------------------------------

Schedule::call(static function (): void {
    $now = now();

    // Pending: job was dispatched but Horizon never picked it up (or was lost).
    $pendingStuck = Store::withoutGlobalScope(WorkspaceScope::class)
        ->where('historical_import_status', 'pending')
        ->where('updated_at', '<', $now->copy()->subMinutes(15))
        ->get(['id', 'workspace_id']);

    // Running: job started but has not written a progress update in >60 minutes.
    $runningStuck = Store::withoutGlobalScope(WorkspaceScope::class)
        ->where('historical_import_status', 'running')
        ->where('updated_at', '<', $now->copy()->subMinutes(60))
        ->get(['id', 'workspace_id']);

    foreach ($pendingStuck->merge($runningStuck) as $store) {
        $errorMsg = 'Import timed out — it may have been interrupted by a server restart. Click "Try again" to resume.';

        $store->update(['historical_import_status' => 'failed']);

        SyncLog::create([
            'workspace_id'      => $store->workspace_id,
            'syncable_type'     => Store::class,
            'syncable_id'       => $store->id,
            'job_type'          => WooCommerceHistoricalImportJob::class,
            'status'            => 'failed',
            'records_processed' => 0,
            'error_message'     => $errorMsg,
            'started_at'        => $now,
            'completed_at'      => $now,
            'duration_seconds'  => 0,
        ]);

        Alert::create([
            'workspace_id' => $store->workspace_id,
            'store_id'     => $store->id,
            'type'         => 'import_failed',
            'severity'     => 'warning',
            'data'         => ['job' => WooCommerceHistoricalImportJob::class, 'error' => $errorMsg],
        ]);

        \Illuminate\Support\Facades\Log::warning('Stuck import detected and marked failed', [
            'store_id'     => $store->id,
            'workspace_id' => $store->workspace_id,
        ]);
    }
})->everyTenMinutes()->name('detect-stuck-imports')->withoutOverlapping(5);

// ---------------------------------------------------------------------------
// Lighthouse / PageSpeed Insights — daily per active store_url
// ---------------------------------------------------------------------------
// Staggered across a 4-hour window (04:00–08:00 UTC) using store_url_id % 240.
// Why: PSI quota is 25,000 req/day, and each check takes ~15–30 s.
//   Spreading checks avoids bursting the quota and keeps API latency manageable.
// Strategy: mobile only by default (see PLANNING.md "PSI Rate Limit Planning").
// ---------------------------------------------------------------------------

Schedule::call(static function (): void {
    StoreUrl::withoutGlobalScope(WorkspaceScope::class)
        ->join('workspaces', 'store_urls.workspace_id', '=', 'workspaces.id')
        ->where('store_urls.is_active', true)
        ->whereNull('workspaces.deleted_at')
        ->whereRaw('NOT (workspaces.trial_ends_at < NOW() AND workspaces.billing_plan IS NULL)')
        ->select(['store_urls.id', 'store_urls.store_id', 'store_urls.workspace_id'])
        ->each(static function (StoreUrl $storeUrl): void {
            // Stagger by store_url_id % 240 minutes within the 4-hour window.
            // Desktop is offset by 35 s so both strategies don't hit PSI simultaneously.
            $delayMinutes = $storeUrl->id % 240;
            RunLighthouseCheckJob::dispatch(
                $storeUrl->id,
                (int) $storeUrl->store_id,
                (int) $storeUrl->workspace_id,
                'mobile',
            )->delay(now()->addMinutes($delayMinutes));
            RunLighthouseCheckJob::dispatch(
                $storeUrl->id,
                (int) $storeUrl->store_id,
                (int) $storeUrl->workspace_id,
                'desktop',
            )->delay(now()->addMinutes($delayMinutes)->addSeconds(35));
        });
})->dailyAt('04:00')->name('dispatch-lighthouse-checks')->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// Lighthouse catch-up — hourly, for URLs that missed their check
// ---------------------------------------------------------------------------
// Why: the initial dispatch in ConnectStoreAction can be lost if Horizon
// restarts while the job is reserved (in-flight). The daily schedule above
// staggered with a delay can also be lost the same way. This catch-up finds
// any active URL with no snapshot in the last 25 hours and re-dispatches
// immediately (no stagger delay — catch-up jobs are already overdue).
//
// The 25-hour threshold is intentionally wider than the 24-hour daily window
// to avoid double-dispatching during the 04:00–08:00 UTC stagger window.
// ---------------------------------------------------------------------------

foreach (['mobile', 'desktop'] as $catchupStrategy) {
    Schedule::call(static function () use ($catchupStrategy): void {
        $threshold = now()->subHours(25);

        StoreUrl::withoutGlobalScope(WorkspaceScope::class)
            ->join('workspaces', 'store_urls.workspace_id', '=', 'workspaces.id')
            ->where('store_urls.is_active', true)
            ->whereNull('workspaces.deleted_at')
            ->whereRaw('NOT (workspaces.trial_ends_at < NOW() AND workspaces.billing_plan IS NULL)')
            ->whereNotExists(static function ($query) use ($threshold, $catchupStrategy): void {
                $query->selectRaw('1')
                    ->from('lighthouse_snapshots')
                    ->whereColumn('lighthouse_snapshots.store_url_id', 'store_urls.id')
                    ->where('lighthouse_snapshots.strategy', $catchupStrategy)
                    ->where('lighthouse_snapshots.checked_at', '>=', $threshold);
            })
            ->select(['store_urls.id', 'store_urls.store_id', 'store_urls.workspace_id'])
            ->each(static function (StoreUrl $storeUrl) use ($catchupStrategy): void {
                RunLighthouseCheckJob::dispatch(
                    $storeUrl->id,
                    (int) $storeUrl->store_id,
                    (int) $storeUrl->workspace_id,
                    $catchupStrategy,
                );
            });
    })->hourly()->name("lighthouse-catchup-{$catchupStrategy}")->withoutOverlapping(10);
}

// ---------------------------------------------------------------------------
// UTM coverage — nightly per workspace with both store + ads (03:45 UTC, low queue)
// ---------------------------------------------------------------------------

Schedule::call(static function (): void {
    Workspace::withoutGlobalScopes()
        ->where('has_store', true)
        ->where('has_ads', true)
        ->whereNull('deleted_at')
        ->select('id')
        ->each(static function (Workspace $workspace): void {
            ComputeUtmCoverageJob::dispatch($workspace->id)->onQueue('low');
        });
})->dailyAt('03:45')->name('compute-utm-coverage-dispatch')->withoutOverlapping(10);

// ---------------------------------------------------------------------------
// Weekly cleanup (Sunday)
// ---------------------------------------------------------------------------

Schedule::call(static function (): void {
    CleanupOldSyncLogsJob::dispatch();
})->weeklyOn(0, '03:00')->name('cleanup-old-sync-logs')->withoutOverlapping(10);

Schedule::call(static function (): void {
    CleanupOldWebhookLogsJob::dispatch();
})->weeklyOn(0, '03:15')->name('cleanup-old-webhook-logs')->withoutOverlapping(10);

Schedule::call(static function (): void {
    PurgeDeletedWorkspaceJob::dispatch();
})->weeklyOn(0, '05:00')->name('purge-deleted-workspaces')->withoutOverlapping(10);
