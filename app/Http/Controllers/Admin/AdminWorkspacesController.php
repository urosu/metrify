<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BackfillAttributionDataJob;
use App\Jobs\SyncAdInsightsJob;
use App\Jobs\SyncProductsJob;
use App\Jobs\SyncRecentRefundsJob;
use App\Jobs\SyncSearchConsoleJob;
use App\Jobs\SyncStoreOrdersJob;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin workspace management — list, plan, sync, backfill.
 *
 * Purpose: Lists all workspaces with backfill/plan controls. Handles plan
 *          assignment, full sync triggers, and attribution backfill dispatch.
 *
 * Reads:  workspaces (withTrashed), Cache (backfill progress keys).
 * Writes: workspaces.billing_plan (setPlan); dispatches jobs (triggerSync,
 *         dispatchAttributionBackfill).
 * Callers: routes/web.php admin group (/admin/workspaces, /admin/workspaces/*).
 *
 * @see docs/planning/backend.md#6
 */
class AdminWorkspacesController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $query = Workspace::withoutGlobalScopes()
            ->withTrashed()
            ->withCount('stores')
            ->with('owner:id,name,email')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('slug', 'ilike', "%{$search}%");
            });
        }

        $page = $query->paginate(25);

        // Pre-fetch all backfill cache keys for this page in a single Redis MGET.
        $pageIds      = $page->getCollection()->pluck('id');
        $cacheKeys    = $pageIds->map(fn (int $id) => BackfillAttributionDataJob::cacheKey($id))->all();
        $backfillData = Cache::many($cacheKeys);

        $workspaces = $page->through(function ($w) use ($backfillData) {
            $backfill = $backfillData[BackfillAttributionDataJob::cacheKey($w->id)] ?? null;

            return [
                'id'            => $w->id,
                'name'          => $w->name,
                'slug'          => $w->slug,
                'billing_plan'  => $w->billing_plan,
                'trial_ends_at' => $w->trial_ends_at?->toISOString(),
                'stores_count'  => $w->stores_count,
                'owner'         => $w->owner ? [
                    'id'    => $w->owner->id,
                    'name'  => $w->owner->name,
                    'email' => $w->owner->email,
                ] : null,
                'created_at'    => $w->created_at->toISOString(),
                'deleted_at'    => $w->deleted_at?->toISOString(),
                // Backfill progress from Cache (null when never dispatched).
                'attribution_backfill' => $backfill,
            ];
        });

        return Inertia::render('Admin/Workspaces', [
            'workspaces' => $workspaces,
            'filters'    => ['search' => $search],
        ]);
    }

    /**
     * Dispatch BackfillAttributionDataJob for a single workspace.
     *
     * Idempotent — safe to dispatch multiple times. Each run re-parses all orders
     * and overwrites attribution_* columns. Typically run once per workspace during
     * beta onboarding after AttributionParserService is deployed.
     *
     * Progress is stored in Cache and surfaced on /admin/system-health (Step 15).
     */
    public function dispatchAttributionBackfill(Workspace $workspace): RedirectResponse
    {
        dispatch(new BackfillAttributionDataJob($workspace->id));

        Log::info('Admin dispatched attribution backfill', [
            'workspace_id' => $workspace->id,
            'admin_id'     => Auth::id(),
        ]);

        return back()->with('success', "Attribution backfill queued for {$workspace->name}.");
    }

    public function triggerSync(Workspace $workspace): RedirectResponse
    {
        $stores = $workspace->stores()->where('status', 'active')->get(['id', 'workspace_id']);

        foreach ($stores as $store) {
            dispatch(new SyncStoreOrdersJob($store->id, $store->workspace_id));
            dispatch(new SyncRecentRefundsJob($store->id, $store->workspace_id));
            dispatch(new SyncProductsJob($store->id, $store->workspace_id));
        }

        $adAccounts = $workspace->adAccounts()->where('status', 'active')->get(['id', 'workspace_id', 'platform']);

        foreach ($adAccounts as $account) {
            dispatch(new SyncAdInsightsJob($account->id, $account->workspace_id, $account->platform));
        }

        $gscProperties = $workspace->searchConsoleProperties()->where('status', 'active')->get(['id', 'workspace_id']);

        foreach ($gscProperties as $property) {
            dispatch(new SyncSearchConsoleJob($property->id, $property->workspace_id));
        }

        return back()->with('success', "Full sync triggered: {$stores->count()} store(s), {$adAccounts->count()} ad account(s), {$gscProperties->count()} GSC property(s).");
    }

    public function setPlan(Request $request, Workspace $workspace): RedirectResponse
    {
        $validated = $request->validate([
            'billing_plan' => 'required|string|in:standard,enterprise',
        ]);

        $workspace->update(['billing_plan' => $validated['billing_plan']]);

        return back()->with('success', 'Billing plan updated.');
    }
}
