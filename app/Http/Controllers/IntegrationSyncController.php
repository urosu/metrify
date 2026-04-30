<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\StartHistoricalImportAction;
use App\Jobs\AdHistoricalImportJob;
use App\Jobs\GscHistoricalImportJob;
use App\Jobs\SyncAdInsightsJob;
use App\Jobs\SyncSearchConsoleJob;
use App\Jobs\SyncShopifyOrdersJob;
use App\Jobs\SyncStoreOrdersJob;
use App\Jobs\WooCommerceHistoricalImportJob;
use App\Models\AdAccount;
use App\Models\Ga4Property;
use App\Models\SearchConsoleProperty;
use App\Models\IntegrationRun;
use App\Models\Store;
use App\Models\Workspace;
use App\Services\Integrations\HistoricalImportOrchestrator;
use App\Services\Integrations\SearchConsole\GscPropertyFormatter;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Handles manual sync triggers and import operations for connected integrations.
 * Extracted from IntegrationsController to separate write verbs from the read-heavy show() action.
 * Reads: stores, ad_accounts, search_console_properties, sync_logs.
 * Writes: sync_logs, jobs.
 * Called by: settings/integrations routes.
 */
class IntegrationSyncController extends Controller
{
    /**
     * Manually trigger an order sync for a single store.
     * Routes to the platform-specific sync job; bypasses the webhook-active check.
     */
    public function syncStore(Request $request, string $storeSlug): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('slug', $storeSlug)
            ->firstOrFail();

        $this->authorize('delete', $store); // StorePolicy — owner or admin

        // Reset error state so the job doesn't silently exit on the status !== 'active' guard.
        if ($store->status === 'error') {
            $store->update([
                'status'                    => 'active',
                'consecutive_sync_failures' => 0,
            ]);
        }

        if ($store->platform === 'shopify') {
            dispatch(new SyncShopifyOrdersJob($store->id, $store->workspace_id, force: true));
        } else {
            dispatch(new SyncStoreOrdersJob($store->id, $store->workspace_id, force: true));
        }

        $key     = "sync_queued_stores_{$workspace->id}";
        $current = cache()->get($key, []);
        if (! in_array($store->id, $current)) {
            cache()->put($key, [...$current, $store->id], now()->addMinutes(5));
        }

        return back()->with('success', "Sync queued for {$store->name}.");
    }

    /**
     * Manually trigger an ad insights sync for a single ad account.
     */
    public function syncAdAccount(Request $request, string $adAccountId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $adAccount = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $adAccountId)
            ->firstOrFail();

        $this->authorize('update', $workspace); // owner or admin

        // A manual sync is an explicit user-initiated retry. Reset error state so the job
        // doesn't silently exit on the status !== 'active' guard in SyncAdInsightsJob.
        if ($adAccount->status === 'error') {
            $adAccount->update([
                'status'                    => 'active',
                'consecutive_sync_failures' => 0,
            ]);
        }

        dispatch(new SyncAdInsightsJob($adAccount->id, $workspace->id, $adAccount->platform));

        $key     = "sync_queued_ad_accounts_{$workspace->id}";
        $current = cache()->get($key, []);
        if (! in_array($adAccount->id, $current)) {
            cache()->put($key, [...$current, $adAccount->id], now()->addMinutes(5));
        }

        return back()->with('success', "Sync queued for {$adAccount->name}.");
    }

    /**
     * Manually trigger a Search Console sync for a single property.
     */
    public function syncGsc(Request $request, string $propertyId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $property = SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $propertyId)
            ->firstOrFail();

        $this->authorize('update', $workspace); // owner or admin

        dispatch(new SyncSearchConsoleJob($property->id, $workspace->id));

        $key     = "sync_queued_gsc_{$workspace->id}";
        $current = cache()->get($key, []);
        if (! in_array($property->id, $current)) {
            cache()->put($key, [...$current, $property->id], now()->addMinutes(5));
        }

        return back()->with('success', "Sync queued for " . GscPropertyFormatter::format($property->property_url) . ".");
    }

    /**
     * Retry a failed historical import for a store.
     * Preserves the existing checkpoint so the job resumes from where it failed.
     */
    public function retryImportStore(Request $request, string $storeSlug): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('slug', $storeSlug)
            ->firstOrFail();

        $this->authorize('delete', $store);

        // historical_import_status was dropped from stores in the L2 schema rebuild.
        // Import state is now in historical_import_jobs.
        $failedImportJob = DB::table('historical_import_jobs')
            ->where('workspace_id', $workspace->id)
            ->where('integrationable_type', Store::class)
            ->where('integrationable_id', $store->id)
            ->where('status', 'failed')
            ->orderByDesc('created_at')
            ->first();

        abort_unless($failedImportJob !== null, 422, 'Import is not in a failed state.');

        DB::table('historical_import_jobs')
            ->where('id', $failedImportJob->id)
            ->update(['status' => 'pending', 'updated_at' => now()]);

        $syncLog = IntegrationRun::create([
            'workspace_id'  => $workspace->id,
            'integrationable_type' => Store::class,
            'integrationable_id'   => $store->id,
            'job_type'      => WooCommerceHistoricalImportJob::class,
            'status'        => 'queued',
        ]);

        WooCommerceHistoricalImportJob::dispatch($store->id, $workspace->id, $syncLog->id);

        return back()->with('success', "Import retry queued for {$store->name}.");
    }

    /**
     * Retry a failed historical import for a Facebook or Google Ads account.
     * Preserves the existing checkpoint so the job resumes from where it failed.
     */
    public function retryImportAdAccount(Request $request, string $adAccountId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $adAccount = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $adAccountId)
            ->firstOrFail();

        $this->authorize('update', $workspace);

        // historical_import_status was dropped from ad_accounts in the L2 schema rebuild.
        $failedAdImportJob = DB::table('historical_import_jobs')
            ->where('workspace_id', $workspace->id)
            ->where('integrationable_type', AdAccount::class)
            ->where('integrationable_id', $adAccount->id)
            ->where('status', 'failed')
            ->orderByDesc('created_at')
            ->first();

        abort_unless($failedAdImportJob !== null, 422, 'Import is not in a failed state.');

        DB::table('historical_import_jobs')
            ->where('id', $failedAdImportJob->id)
            ->update(['status' => 'pending', 'updated_at' => now()]);

        $syncLog = IntegrationRun::create([
            'workspace_id'  => $workspace->id,
            'integrationable_type' => AdAccount::class,
            'integrationable_id'   => $adAccount->id,
            'job_type'      => AdHistoricalImportJob::class,
            'status'        => 'queued',
        ]);

        AdHistoricalImportJob::dispatch($adAccount->id, $workspace->id, $syncLog->id);

        return back()->with('success', "Import retry queued for {$adAccount->name}.");
    }

    /**
     * Retry a failed historical import for a Search Console property.
     * Preserves the existing checkpoint so the job resumes from where it failed.
     */
    public function retryImportGsc(Request $request, string $propertyId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $property = SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $propertyId)
            ->firstOrFail();

        $this->authorize('update', $workspace);

        // historical_import_status was dropped from search_console_properties in the L2 schema rebuild.
        $failedGscImportJob = DB::table('historical_import_jobs')
            ->where('workspace_id', $workspace->id)
            ->where('integrationable_type', SearchConsoleProperty::class)
            ->where('integrationable_id', $property->id)
            ->where('status', 'failed')
            ->orderByDesc('created_at')
            ->first();

        abort_unless($failedGscImportJob !== null, 422, 'Import is not in a failed state.');

        DB::table('historical_import_jobs')
            ->where('id', $failedGscImportJob->id)
            ->update(['status' => 'pending', 'updated_at' => now()]);

        $syncLog = IntegrationRun::create([
            'workspace_id'  => $workspace->id,
            'integrationable_type' => SearchConsoleProperty::class,
            'integrationable_id'   => $property->id,
            'job_type'      => GscHistoricalImportJob::class,
            'status'        => 'queued',
        ]);

        GscHistoricalImportJob::dispatch($property->id, $workspace->id, $syncLog->id);

        return back()->with('success', "Import retry queued for " . GscPropertyFormatter::format($property->property_url) . ".");
    }

    /**
     * Re-import a store's history from a user-chosen date, discarding any existing
     * checkpoint so the job starts fresh from that date.
     */
    public function reimportStore(Request $request, string $storeSlug, StartHistoricalImportAction $importAction): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('slug', $storeSlug)
            ->firstOrFail();

        $this->authorize('delete', $store);

        $validated = $request->validate([
            'from_date' => ['nullable', 'date', 'before:today'],
        ]);

        // Reset store error state before re-dispatching.
        // historical_import_* columns were dropped from stores in the L2 schema rebuild.
        // The StartHistoricalImportAction writes a new historical_import_jobs row.
        $store->update([
            'status'                    => 'active',
            'consecutive_sync_failures' => 0,
        ]);

        $fromDate = isset($validated['from_date'])
            ? \Carbon\Carbon::parse($validated['from_date'])
            : \Carbon\Carbon::createFromDate(2010, 1, 1);

        $importAction->handle($store, $fromDate);

        $fromLabel = $validated['from_date'] ?? 'the beginning';

        return back()->with('success', "Re-import queued for {$store->name} from {$fromLabel}.");
    }

    /**
     * Re-import an ad account's history from a user-chosen date.
     */
    public function reimportAdAccount(Request $request, string $adAccountId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $adAccount = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $adAccountId)
            ->firstOrFail();

        $this->authorize('update', $workspace);

        // Why: Facebook API limits historical data to 37 months back.
        // Requests beyond that return error #3018.
        $earliestAllowed = now()->subMonths(37)->toDateString();

        $validated = $request->validate([
            'from_date' => ['nullable', 'date', 'before:today', "after_or_equal:{$earliestAllowed}"],
        ]);

        // historical_import_* columns were dropped from ad_accounts in the L2 schema rebuild.
        // Write a new pending row in historical_import_jobs and dispatch the job.
        DB::table('historical_import_jobs')->insert([
            'workspace_id'         => $workspace->id,
            'integrationable_type' => AdAccount::class,
            'integrationable_id'   => $adAccount->id,
            'job_type'             => 'facebook_ads',
            'status'               => 'pending',
            'from_date'            => $validated['from_date'] ?? null,
            'to_date'              => null,
            'progress_pct'         => 0,
            'checkpoint'           => json_encode([]),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $syncLog = IntegrationRun::create([
            'workspace_id'  => $workspace->id,
            'integrationable_type' => AdAccount::class,
            'integrationable_id'   => $adAccount->id,
            'job_type'      => AdHistoricalImportJob::class,
            'status'        => 'queued',
        ]);

        AdHistoricalImportJob::dispatch($adAccount->id, $workspace->id, $syncLog->id);

        $fromLabel = $validated['from_date'] ?? 'the beginning';

        return back()->with('success', "Re-import queued for {$adAccount->name} from {$fromLabel}.");
    }

    /**
     * Re-import a Search Console property's history from a user-chosen date.
     */
    public function reimportGsc(Request $request, string $propertyId): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $property = SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $propertyId)
            ->firstOrFail();

        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'from_date' => ['nullable', 'date', 'before:today'],
        ]);

        // historical_import_* columns were dropped from search_console_properties in the L2 schema rebuild.
        // Write a new pending row in historical_import_jobs and dispatch the job.
        DB::table('historical_import_jobs')->insert([
            'workspace_id'         => $workspace->id,
            'integrationable_type' => SearchConsoleProperty::class,
            'integrationable_id'   => $property->id,
            'job_type'             => 'google_search_console',
            'status'               => 'pending',
            'from_date'            => $validated['from_date'] ?? null,
            'to_date'              => null,
            'progress_pct'         => 0,
            'checkpoint'           => json_encode([]),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $syncLog = IntegrationRun::create([
            'workspace_id'  => $workspace->id,
            'integrationable_type' => SearchConsoleProperty::class,
            'integrationable_id'   => $property->id,
            'job_type'      => GscHistoricalImportJob::class,
            'status'        => 'queued',
        ]);

        GscHistoricalImportJob::dispatch($property->id, $workspace->id, $syncLog->id);

        $fromLabel = $validated['from_date'] ?? 'the beginning';

        return back()->with('success', "Re-import queued for " . GscPropertyFormatter::format($property->property_url) . " from {$fromLabel}.");
    }

    /**
     * Re-import a GA4 property's full attribution history from a user-chosen date.
     */
    public function reimportGa4(Request $request, int $propertyId, HistoricalImportOrchestrator $orchestrator): RedirectResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $property = Ga4Property::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('id', $propertyId)
            ->firstOrFail();

        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'from_date' => ['nullable', 'date', 'before:today'],
        ]);

        $from = isset($validated['from_date'])
            ? \Carbon\Carbon::parse($validated['from_date'])
            : now()->subDays(1095);

        $orchestrator->start($property, $from, now()->yesterday());

        $fromLabel = $validated['from_date'] ?? 'the beginning';

        return back()->with('success', "GA4 re-import queued for {$property->property_name} from {$fromLabel}.");
    }
}
