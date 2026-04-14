<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ConnectStoreAction;
use App\Actions\CreateWorkspaceAction;
use App\Actions\StartHistoricalImportAction;
use App\Exceptions\WooCommerceConnectionException;
use App\Models\Store;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles the multi-step onboarding flow for new users.
 *
 * Routes (all require auth + verified, SetActiveWorkspace skips 'onboarding/*'):
 *   GET  /onboarding         → show()         — detects current step from DB state
 *   POST /onboarding/store   → connectStore() — connects WooCommerce store
 *   POST /onboarding/import  → startImport()  — records date range + dispatches import job
 *
 * Step detection logic in show():
 *   1 — connection tiles (Store / Ad Accounts / GSC) — shown until a store is connected
 *       or when only ads/GSC are connected (user can go to dashboard from here)
 *   2 — store connected, historical_import_status IS NULL (import not yet started)
 *   3 — historical_import_status IN (pending, running, failed)
 *   redirect /dashboard — historical_import_status = completed
 *
 * Workspace auto-creation: on the first visit we create a placeholder workspace so
 * OAuth flows (which require a workspace_id in their state) can proceed before any
 * store is connected. The workspace is renamed to the WooCommerce site title in connectStore().
 *
 * Invitation path: VerifyEmailController handles the invitation token and redirects
 * directly to /dashboard — the onboarding flow is never reached for invited users.
 */
class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        $workspaceUser = WorkspaceUser::where('user_id', $user->id)
            ->whereHas('workspace', fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('created_at')
            ->first();

        if ($workspaceUser === null) {
            // First visit — auto-create workspace so OAuth can proceed before any store is connected.
            // The workspace is renamed to the WooCommerce site title once a store connects.
            $workspace = $this->autoCreateWorkspace($user);
            session(['active_workspace_id' => $workspace->id]);
        } else {
            $workspace = Workspace::find($workspaceUser->workspace_id);
            // Ensure session is set so SetActiveWorkspace middleware finds the workspace
            // on subsequent OAuth redirect requests.
            session(['active_workspace_id' => $workspace->id]);
        }

        // Check for a store
        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->orderBy('created_at')
            ->first();

        if ($store !== null) {
            $importStatus = $store->historical_import_status;

            if ($importStatus === 'completed' && ! $request->boolean('add_store')) {
                return redirect("/{$workspace->slug}/dashboard");
            }

            // add_store=true: show tiles again so user can connect another store
            if ($importStatus !== 'completed' && $importStatus !== null) {
                // pending | running | failed → show progress screen
                return Inertia::render('Onboarding/Index', [
                    'step'       => 3,
                    'store_id'   => $store->id,
                    'store_slug' => $store->slug,
                ]);
            }

            if ($importStatus === null) {
                return Inertia::render('Onboarding/Index', [
                    'step'       => 2,
                    'store_id'   => $store->id,
                    'store_name' => $store->name,
                ]);
            }

            // importStatus === 'completed' AND add_store=true → fall through to step 1
        }

        // Step 1 — connection tiles
        $workspaceId = $workspace->id;
        $fbPending   = $this->resolvePending($request->query('fb_pending'),   $workspaceId, 'accounts');
        $gadsPending = $this->resolvePending($request->query('gads_pending'), $workspaceId, 'accounts');
        $gscPending  = $this->resolvePending($request->query('gsc_pending'),  $workspaceId, 'properties');
        $oauthError  = $request->query('oauth_error');
        $oauthPlatform = $request->query('oauth_platform');

        return Inertia::render('Onboarding/Index', [
            'step'           => 1,
            'has_ads'        => (bool) $workspace->has_ads,
            'has_gsc'        => (bool) $workspace->has_gsc,
            'fb_pending'     => $fbPending,
            'gads_pending'   => $gadsPending,
            'gsc_pending'    => $gscPending,
            'oauth_error'    => is_string($oauthError) && $oauthError !== '' ? $oauthError : null,
            'oauth_platform' => is_string($oauthPlatform) && $oauthPlatform !== '' ? $oauthPlatform : null,
        ]);
    }

    /**
     * Validate WooCommerce credentials, auto-create workspace if needed, connect store.
     */
    public function connectStore(
        Request $request,
        CreateWorkspaceAction $create,
        ConnectStoreAction $connect,
    ): RedirectResponse {
        $validated = $request->validate([
            'domain'          => 'required|string|max:255',
            'consumer_key'    => 'required|string|max:500',
            'consumer_secret' => 'required|string|max:500',
        ]);

        $user = $request->user();

        // Reuse existing workspace (auto-created on first onboarding visit).
        // Fallback: create one now in case show() was never called (edge case).
        $existingWorkspaceUser = WorkspaceUser::where('user_id', $user->id)
            ->whereHas('workspace', fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('created_at')
            ->first();

        $workspace = $existingWorkspaceUser !== null
            ? Workspace::find($existingWorkspaceUser->workspace_id)
            : $create->handle($user, $validated['domain']);

        try {
            $store = $connect->handle($workspace, $validated);
        } catch (WooCommerceConnectionException $e) {
            return back()->withErrors(['domain' => $e->getMessage()]);
        }

        // Rename the workspace to the WooCommerce site title now that we have it.
        // This also fixes the case where a previous failed attempt left a stale domain name.
        // Slug is intentionally not updated here — it was randomised at workspace creation
        // and must remain stable so any bookmarked links keep working.
        $workspace->update(['name' => $store->name]);

        // Set active workspace in session so subsequent polling requests work
        session(['active_workspace_id' => $workspace->id]);

        return redirect()->route('onboarding');
    }

    /**
     * Full start-over: delete the store (and clear session), return to step 1.
     *
     * Safe to call at any point during onboarding (step 2 or 3).
     * The workspace is kept — connectStore() reuses it on the next attempt.
     */
    public function resetOnboarding(Request $request): RedirectResponse
    {
        $user = $request->user();

        $store = Store::withoutGlobalScopes()
            ->whereHas('workspace', fn ($q) => $q
                ->whereNull('deleted_at')
                ->whereHas('workspaceUsers', fn ($q) => $q->where('user_id', $user->id))
            )
            ->orderBy('created_at')
            ->first();

        if ($store !== null) {
            $store->delete();
        }

        return redirect()->route('onboarding');
    }

    /**
     * Reset a failed import so the user can choose a date range again.
     */
    public function resetImport(Request $request): RedirectResponse
    {
        $user = $request->user();

        $store = Store::withoutGlobalScopes()
            ->whereHas('workspace', fn ($q) => $q
                ->whereNull('deleted_at')
                ->whereHas('workspaceUsers', fn ($q) => $q->where('user_id', $user->id))
            )
            ->where('historical_import_status', 'failed')
            ->orderBy('created_at')
            ->firstOrFail();

        $store->update([
            'historical_import_status'     => null,
            'historical_import_checkpoint' => null,
            'historical_import_progress'   => null,
        ]);

        return redirect()->route('onboarding');
    }

    /**
     * Record the chosen import date range and dispatch the historical import job.
     */
    public function startImport(
        Request $request,
        StartHistoricalImportAction $action,
    ): RedirectResponse {
        $validated = $request->validate([
            'store_id' => 'required|integer',
            'period'   => 'required|in:30days,90days,1year,all',
        ]);

        $user = $request->user();

        // Verify the store belongs to the authenticated user — without WorkspaceScope
        $store = Store::withoutGlobalScopes()
            ->where('id', $validated['store_id'])
            ->whereHas('workspace', fn ($q) => $q
                ->whereNull('deleted_at')
                ->whereHas('workspaceUsers', fn ($q) => $q->where('user_id', $user->id))
            )
            ->firstOrFail();

        $fromDate = match ($validated['period']) {
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '1year'  => now()->subYear(),
            'all'    => Carbon::createFromDate(2010, 1, 1),
        };

        $action->handle($store, $fromDate);

        return redirect()->route('onboarding');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Auto-create a placeholder workspace for a brand-new user on their first onboarding visit.
     *
     * The workspace is named "{user's name}'s Workspace" and renamed to the WooCommerce site
     * title once a store is connected in connectStore(). This allows OAuth flows (Facebook,
     * Google) to proceed before any store is connected — they require a workspace_id in state.
     */
    private function autoCreateWorkspace(User $user): Workspace
    {
        $create = new CreateWorkspaceAction();
        $name   = trim($user->name) !== '' ? trim($user->name) . "'s Workspace" : 'My Workspace';
        $slug   = $create->generateUniqueSlug($name);

        return DB::transaction(function () use ($user, $name, $slug): Workspace {
            $workspace = Workspace::create([
                'name'               => $name,
                'slug'               => $slug,
                'owner_id'           => $user->id,
                'reporting_currency' => 'EUR',
                'reporting_timezone' => 'Europe/Berlin',
                'trial_ends_at'      => now()->addDays(14),
            ]);

            WorkspaceUser::create([
                'workspace_id' => $workspace->id,
                'user_id'      => $user->id,
                'role'         => 'owner',
            ]);

            return $workspace;
        });
    }

    /**
     * Read a pending OAuth cache entry and return the key + payload field for the frontend.
     * Identical to IntegrationsController::resolvePending().
     *
     * @return array{key: string, items: mixed}|null
     */
    private function resolvePending(mixed $key, int $workspaceId, string $field): ?array
    {
        if (! is_string($key) || $key === '') {
            return null;
        }

        $cached = cache()->get($key);

        if ($cached === null || (int) ($cached['workspace_id'] ?? 0) !== $workspaceId) {
            return null;
        }

        return ['key' => $key, 'items' => $cached[$field]];
    }
}
