<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard banner dismissal — persists per-user per-workspace banner flags.
 *
 * Reads: users.view_preferences
 * Writes: users.view_preferences
 * Called by: POST /dashboard/dismiss-banner
 *
 * @see docs/planning/backend.md §3
 * @see docs/UX.md §7 (Not Tracked banner trigger)
 */
class DashboardBannerController extends Controller
{
    public function dismiss(Request $request): JsonResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $user = $request->user();
        $prefs = $user->view_preferences ?? [];
        $prefs["not_tracked_banner_dismissed_{$workspaceId}"] = true;
        $user->update(['view_preferences' => $prefs]);

        return response()->json(['ok' => true]);
    }
}
