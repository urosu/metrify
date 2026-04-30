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
use App\Services\WorkspaceContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

// Session key used by GeoDetectionService — referenced here to pass the hint to the React step.
const GEO_SESSION_KEY = 'ip_detected_country';

/**
 * Handles the multi-step onboarding flow for new users.
 *
 * Routes (all require auth + verified, SetActiveWorkspace skips 'onboarding/*'):
 *   GET  /onboarding              → show()           — detects current step from DB state
 *   POST /onboarding/workspace    → saveWorkspace()  — step 1: workspace name, currency, timezone
 *   POST /onboarding/store        → connectStore()   — step 2: connects WooCommerce store
 *   POST /onboarding/country      → saveCountry()    — step 3a: saves (or skips) primary_country_code
 *   POST /onboarding/costs        → saveCosts()      — step 5: COGS% / flat fee (optional, deferrable)
 *   POST /onboarding/import       → startImport()    — step 6: months window + dispatches import job
 *   POST /onboarding/import/reset → resetImport()    — reset failed import
 *   POST /onboarding/reset        → resetOnboarding()— full start-over
 *
 * Step detection logic in show():
 *   0 — welcome (workspace name, currency, timezone) — if workspace has default "…'s Workspace" name
 *       and session flag 'onboarding_workspace_done' is absent
 *   1 — connection tiles (Store / Ad Accounts / GSC) — until a store is connected
 *   2 — store connected, country prompt not yet completed (session flag absent) → step is '2-country'
 *   3 — country done, no import started → step 3 (ads/GSC connections still shown)
 *   4 — connections done, costs step (skippable)
 *   5 — import window picker
 *   6 — import progress (pending/running/failed)
 *   redirect /dashboard — historical_import_status = completed
 *
 * Country prompt tracking: session key `onboarding_country_seen_{store_id}`.
 * Workspace step tracking: session key `onboarding_workspace_done`.
 *
 * Workspace auto-creation: on the first visit we create a placeholder workspace so
 * OAuth flows (which require a workspace_id in their state) can proceed before any
 * store is connected. The workspace is renamed in saveWorkspace() or by connectStore().
 *
 * Invitation path: VerifyEmailController handles the invitation token and redirects
 * directly to /dashboard — the onboarding flow is never reached for invited users.
 *
 * @see docs/competitors/_research_onboarding_flow.md  API ceiling per connector
 * @see docs/competitors/_crosscut_onboarding_ux.md    Competitor onboarding patterns
 */
class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Prefer the session's active workspace — this ensures that when a user
        // creates a second workspace from the switcher, onboarding operates on the
        // new workspace and not their oldest one.
        $workspaceUser = $this->resolveWorkspaceUser($user, (int) session('active_workspace_id'));

        if ($workspaceUser === null) {
            // First visit — auto-create workspace so OAuth can proceed before any store is connected.
            $workspace = $this->autoCreateWorkspace($user);
            session(['active_workspace_id' => $workspace->id]);
        } else {
            $workspace = Workspace::find($workspaceUser->workspace_id);
            session(['active_workspace_id' => $workspace->id]);
        }

        // Set WorkspaceContext so HandleInertiaRequests shares the workspace prop.
        app(WorkspaceContext::class)->set($workspace->id, $workspace->slug);

        // Determine whether the workspace is on a trial.
        $isTrial = $workspace->trial_ends_at !== null && $workspace->trial_ends_at->gt(now());

        // Step 0 — welcome: workspace name / currency / timezone
        // Shown if session flag absent AND workspace still has auto-generated name.
        $workspaceDone = session()->has('onboarding_workspace_done');
        if (! $workspaceDone) {
            return Inertia::render('Onboarding/Index', [
                'step'              => 0,
                'workspace_name'    => $workspace->name,
                'workspace_currency'=> $workspace->reporting_currency ?? 'EUR',
                'workspace_timezone'=> $workspace->reporting_timezone ?? 'Europe/Berlin',
                'is_trial'          => $isTrial,
                'has_other_workspaces' => $this->hasOtherWorkspaces($user, $workspace->id),
                'is_workspace_owner'   => $workspace->owner_id === $user->id,
                'current_workspace_id' => $workspace->id,
            ]);
        }

        // Check for a store
        $store = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->orderBy('created_at')
            ->first();

        if ($store !== null) {
            $importStatus   = $store->historical_import_status;
            $countrySeenKey = 'onboarding_country_seen_' . $store->id;
            $countryDone    = session()->has($countrySeenKey);
            $isReimport     = $store->historical_import_completed_at !== null;

            if ($importStatus === 'completed' && ! $request->boolean('add_store')) {
                return redirect("/{$workspace->slug}");
            }

            // Re-import: store already completed onboarding once — send to dashboard.
            if ($isReimport && in_array($importStatus, ['pending', 'running'], true)) {
                return redirect("/{$workspace->slug}");
            }

            if ($importStatus === 'completed') {
                // Fall through to step 1 (add_store path)
            } elseif ($importStatus !== null) {
                // Step 6 — pending | running | failed → progress screen
                return Inertia::render('Onboarding/Index', [
                    'step'           => 6,
                    'store_id'       => $store->id,
                    'store_slug'     => $store->slug,
                    'workspace_slug' => $workspace->slug,
                    'is_trial'       => $isTrial,
                ]);
            } elseif (! $countryDone) {
                // Step 2 — country prompt
                return Inertia::render('Onboarding/Index', [
                    'step'                => 2,
                    'store_id'            => $store->id,
                    'store_name'          => $store->name,
                    'website_url'         => $store->website_url,
                    'country'             => $store->primary_country_code,
                    'ip_detected_country' => session(GEO_SESSION_KEY),
                    'is_trial'            => $isTrial,
                ]);
            } else {
                // Step 5 — import window picker (after country + optional costs step)
                $costsDone = session()->has('onboarding_costs_done');
                if (! $costsDone) {
                    // Step 4 — costs (skippable)
                    return Inertia::render('Onboarding/Index', [
                        'step'       => 4,
                        'store_id'   => $store->id,
                        'store_name' => $store->name,
                        'is_trial'   => $isTrial,
                    ]);
                }

                return Inertia::render('Onboarding/Index', [
                    'step'       => 5,
                    'store_id'   => $store->id,
                    'store_name' => $store->name,
                    'is_trial'   => $isTrial,
                ]);
            }
        }

        // Step 1 — connection tiles (store + optional ad accounts + optional GSC/GA4)
        $workspaceId = $workspace->id;
        $fbPending   = $this->resolvePending($request->query('fb_pending'),   $workspaceId, 'accounts');
        $gadsPending = $this->resolvePending($request->query('gads_pending'), $workspaceId, 'accounts');
        $gscPending  = $this->resolvePending($request->query('gsc_pending'),  $workspaceId, 'properties');
        $oauthError  = $request->query('oauth_error');
        $oauthPlatform = $request->query('oauth_platform');

        return Inertia::render('Onboarding/Index', [
            'step'                  => 1,
            'has_ads'               => (bool) $workspace->has_ads,
            'has_gsc'               => (bool) $workspace->has_gsc,
            'fb_pending'            => $fbPending,
            'gads_pending'          => $gadsPending,
            'gsc_pending'           => $gscPending,
            'oauth_error'           => is_string($oauthError) && $oauthError !== '' ? $oauthError : null,
            'oauth_platform'        => is_string($oauthPlatform) && $oauthPlatform !== '' ? $oauthPlatform : null,
            'has_other_workspaces'  => $this->hasOtherWorkspaces($user, $workspaceId),
            'is_workspace_owner'    => $workspace->owner_id === $user->id,
            'current_workspace_id'  => $workspace->id,
            'is_trial'              => $isTrial,
            'workspace_name'        => $workspace->name,
        ]);
    }

    /**
     * Step 0 — save workspace name, reporting currency, and timezone.
     *
     * Called from the welcome step. Marks the step complete via session flag
     * so show() moves to step 1 on next visit.
     */
    public function saveWorkspace(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:80',
            'reporting_currency' => 'required|string|size:3|alpha',
            'reporting_timezone' => 'required|string|max:64',
        ]);

        $user      = $request->user();
        $workspace = $this->resolveWorkspaceFromSession($user);

        if ($workspace !== null) {
            $workspace->update([
                'name'               => $validated['name'],
                'reporting_currency' => strtoupper($validated['reporting_currency']),
                'reporting_timezone' => $validated['reporting_timezone'],
            ]);
        }

        session(['onboarding_workspace_done' => true]);

        return redirect()->route('onboarding');
    }

    /**
     * Step 2 — validate WooCommerce credentials, auto-create workspace if needed, connect store.
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

        $existingWorkspaceUser = $this->resolveWorkspaceUser($user, (int) session('active_workspace_id'));

        $workspace = $existingWorkspaceUser !== null
            ? Workspace::find($existingWorkspaceUser->workspace_id)
            : $create->handle($user, $validated['domain']);

        try {
            $store = $connect->handle($workspace, $validated);
        } catch (WooCommerceConnectionException $e) {
            return back()->withErrors(['domain' => $e->getMessage()]);
        }

        // Rename the workspace to the WooCommerce site title if user didn't set one already.
        if (! session()->has('onboarding_workspace_done')) {
            $workspace->update(['name' => $store->name]);
        }

        session(['active_workspace_id' => $workspace->id]);

        return redirect()->route('onboarding');
    }

    /**
     * Step 2 — save (or skip) the store's primary country after store connection.
     *
     * @see docs/planning/schema.md Stores
     */
    public function saveCountry(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id'     => 'required|integer',
            'country_code' => 'nullable|string|size:2|alpha',
        ]);

        $user = $request->user();

        $store = Store::withoutGlobalScopes()
            ->where('id', $validated['store_id'])
            ->whereHas('workspace', fn ($q) => $q
                ->whereNull('deleted_at')
                ->whereHas('workspaceUsers', fn ($q) => $q->where('user_id', $user->id))
            )
            ->firstOrFail();

        $store->update([
            'primary_country_code' => isset($validated['country_code']) && $validated['country_code'] !== ''
                ? strtoupper($validated['country_code'])
                : null,
        ]);

        session(['onboarding_country_seen_' . $store->id => true]);

        return redirect()->route('onboarding');
    }

    /**
     * Step 4 — save (or skip) COGS and default margin settings.
     *
     * Writes to workspace_settings JSONB. Cost changes in production trigger
     * RecomputeAttributionJob; here we just persist and mark step done.
     * See StoreCostSettings ValueObject for the full schema.
     *
     * @see docs/planning/schema.md Workspaces
     */
    public function saveCosts(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_cogs_pct'    => 'nullable|numeric|min:0|max:100',
            'default_margin_pct'  => 'nullable|numeric|min:0|max:100',
        ]);

        $user      = $request->user();
        $workspace = $this->resolveWorkspaceFromSession($user);

        if ($workspace !== null) {
            $settings = $workspace->workspace_settings ?? [];
            $settings['default_cogs_pct']   = isset($validated['default_cogs_pct'])
                ? (float) $validated['default_cogs_pct']
                : null;
            $settings['default_margin_pct'] = isset($validated['default_margin_pct'])
                ? (float) $validated['default_margin_pct']
                : null;
            $workspace->update(['workspace_settings' => $settings]);
        }

        session(['onboarding_costs_done' => true]);

        return redirect()->route('onboarding');
    }

    /**
     * Step 5 — record the chosen import window and dispatch the historical import job.
     *
     * Import window (months) is capped per connector by API ceilings:
     *   Facebook / Google Ads / Shopify / WooCommerce: up to 36 months
     *   GSC:  16 months max
     *   GA4:  14 months max (free tier)
     *
     * Trial workspaces are capped at 6 months regardless of selection.
     *
     * @see docs/competitors/_research_onboarding_flow.md §2 for API ceiling sources
     */
    public function startImport(
        Request $request,
        StartHistoricalImportAction $action,
    ): RedirectResponse {
        $validated = $request->validate([
            'store_id' => 'required|integer',
            'months'   => 'required|integer|min:1|max:36',
        ]);

        $user = $request->user();

        $store = Store::withoutGlobalScopes()
            ->where('id', $validated['store_id'])
            ->whereHas('workspace', fn ($q) => $q
                ->whereNull('deleted_at')
                ->whereHas('workspaceUsers', fn ($q) => $q->where('user_id', $user->id))
            )
            ->firstOrFail();

        $workspace = Workspace::withoutGlobalScopes()->findOrFail($store->workspace_id);

        // Cap trial workspaces at 6 months — paid users get up to 36.
        // @see docs/competitors/_research_onboarding_flow.md §3 Trial vs paid scope
        $isTrial   = $workspace->trial_ends_at !== null && $workspace->trial_ends_at->gt(now());
        $months    = $isTrial ? min((int) $validated['months'], 6) : (int) $validated['months'];
        $fromDate  = now()->subMonths($months)->startOfDay();

        $action->handle($store, $fromDate);

        return redirect()->route('onboarding');
    }

    /**
     * Full start-over: delete the store (and clear session), return to step 1.
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

        // Clear country + costs flags so the prompts appear again for the new store.
        session()->forget([
            'onboarding_country_seen_' . ($store?->id ?? 0),
            'onboarding_costs_done',
        ]);

        return redirect()->route('onboarding');
    }

    /**
     * Reset a failed import so the user can choose a window again.
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

        // Back to costs step so user can re-confirm the window.
        session()->forget('onboarding_costs_done');

        return redirect()->route('onboarding');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve which workspace to operate on during onboarding.
     *
     * Prefers the session's active workspace so that when a user creates a second
     * workspace from the switcher (WorkspaceController::create()), onboarding uses
     * the new workspace instead of their oldest one.
     */
    private function resolveWorkspaceUser(User $user, int $activeWorkspaceId): ?WorkspaceUser
    {
        if ($activeWorkspaceId > 0) {
            $fromSession = WorkspaceUser::where('user_id', $user->id)
                ->where('workspace_id', $activeWorkspaceId)
                ->whereHas('workspace', fn ($q) => $q->whereNull('deleted_at'))
                ->first();

            if ($fromSession !== null) {
                return $fromSession;
            }
        }

        return WorkspaceUser::where('user_id', $user->id)
            ->whereHas('workspace', fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('created_at')
            ->first();
    }

    /**
     * Resolve the active workspace from the session (used by POST actions).
     */
    private function resolveWorkspaceFromSession(User $user): ?Workspace
    {
        $wu = $this->resolveWorkspaceUser($user, (int) session('active_workspace_id'));
        return $wu !== null ? Workspace::find($wu->workspace_id) : null;
    }

    /**
     * Auto-create a placeholder workspace for a brand-new user on their first onboarding visit.
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
     * Check whether this user has other active workspaces (used to show/hide Cancel button).
     */
    private function hasOtherWorkspaces(User $user, int $currentWorkspaceId): bool
    {
        return WorkspaceUser::where('user_id', $user->id)
            ->where('workspace_id', '!=', $currentWorkspaceId)
            ->whereHas('workspace', fn ($q) => $q->whereNull('deleted_at'))
            ->exists();
    }

    /**
     * Read a pending OAuth cache entry and return the key + payload field for the frontend.
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
