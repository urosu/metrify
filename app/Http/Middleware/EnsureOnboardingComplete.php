<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to content routes until onboarding is complete.
 *
 * Onboarding is considered complete when:
 *   1. The workspace has a store with historical_import_status = 'completed', OR
 *   2. The workspace has no store but has ads or GSC connected (ads-only path).
 *
 * This mirrors the redirect-to-dashboard condition in OnboardingController::show().
 * Apply to all content routes (dashboard, analytics, campaigns, etc.) but NOT
 * to settings/integrations/oauth/onboarding routes — users need those during setup.
 *
 * Related: app/Http/Controllers/OnboardingController.php (step detection logic)
 */
class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        // No workspace context — SetActiveWorkspace already handles redirecting to /onboarding.
        if ($workspaceId === null) {
            return $next($request);
        }

        // Path 1: workspace has a store with a completed import.
        $hasCompletedStore = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('historical_import_status', 'completed')
            ->exists();

        if ($hasCompletedStore) {
            return $next($request);
        }

        // Path 2: no store at all, but at least one integration connected (ads/GSC only path).
        $hasAnyStore = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->exists();

        if (! $hasAnyStore) {
            $workspace = Workspace::select(['id', 'has_ads', 'has_gsc'])->find($workspaceId);

            if ($workspace && ($workspace->has_ads || $workspace->has_gsc)) {
                return $next($request);
            }
        }

        return redirect()->route('onboarding');
    }
}
