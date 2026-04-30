<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\RunLighthouseCheckJob;
use App\Models\Store;
use App\Models\StoreUrl;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manages the set of URLs monitored per store for PSI / Lighthouse checks.
 *
 * Reads:  store_urls (workspace-scoped via withoutGlobalScopes + explicit workspace_id check)
 * Writes: store_urls.is_active (soft-deactivation), dispatches RunLighthouseCheckJob
 * Called by: routes/web.php → settings.integrations.stores.urls.*
 *
 * @see docs/planning/backend.md#StoreUrlController
 */
class StoreUrlController extends Controller
{
    /**
     * Add a URL to the monitored set for a store.
     *
     * Uses firstOrCreate so re-adding a previously deactivated URL is safe.
     * Immediately queues mobile + desktop Lighthouse checks.
     */
    public function store(Request $request, string $storeSlug): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('slug', $storeSlug)
            ->firstOrFail();

        // Validate that the submitted URL belongs to the store's domain.
        $host            = parse_url($request->url, PHP_URL_HOST);
        $normalizedHost  = ltrim($host ?? '', 'www.');
        $normalizedDomain = ltrim($store->domain ?? '', 'www.');

        if ($normalizedHost !== $normalizedDomain) {
            return redirect()->back()->withErrors(['url' => 'URL must belong to this store\'s domain (' . $store->domain . ')']);
        }

        // Cap at 10 active monitored URLs per store.
        $activeCount = StoreUrl::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->count();

        if ($activeCount >= 10) {
            return redirect()->back()->withErrors(['url' => 'Maximum 10 URLs per store.']);
        }

        $url = rtrim($request->input('url'), '/');

        $storeUrl = StoreUrl::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $store->id, 'url' => $url],
            [
                'workspace_id' => $workspaceId,
                'label'        => null,
                'is_homepage'  => false,
                'is_active'    => true,
            ],
        );

        // Restore if previously soft-deactivated.
        if (! $storeUrl->is_active) {
            $storeUrl->update(['is_active' => true]);
        }

        // Immediately kick off checks for both strategies.
        RunLighthouseCheckJob::dispatch($storeUrl->id, $store->id, $workspaceId, 'mobile');
        RunLighthouseCheckJob::dispatch($storeUrl->id, $store->id, $workspaceId, 'desktop');

        return redirect()->back()->with('success', 'URL added — checks will run shortly.');
    }

    /**
     * Remove a URL from the monitored set.
     *
     * Soft-deactivates (is_active = false) rather than hard-deleting so that
     * historical lighthouse_snapshots remain queryable. Homepage URLs cannot
     * be deactivated — they are managed exclusively by ConnectStoreAction.
     */
    public function destroy(Request $request, string $storeSlug, int $urlId): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $storeUrl = StoreUrl::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('id', $urlId)
            ->whereHas('store', fn ($q) => $q->where('slug', $storeSlug))
            ->firstOrFail();

        // Homepage URLs cannot be deactivated — they're managed by ConnectStoreAction.
        if ($storeUrl->is_homepage) {
            return redirect()->back()->withErrors(['url' => 'The homepage URL cannot be removed.']);
        }

        $storeUrl->update(['is_active' => false]);

        return redirect()->back()->with('success', 'URL removed from monitoring.');
    }
}
