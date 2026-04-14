<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles the /manage section — tools for workspace owners to improve data quality.
 *
 * Routes:
 *   GET /manage/tag-generator   → Tag Generator (UTM builder for ad URLs)
 *
 * See: PLANNING.md "UTM Coverage Health Check + Tag Generator"
 * Related: resources/js/Pages/Manage/TagGenerator.tsx
 */
class ManageController extends Controller
{
    /**
     * UTM Tag Generator — surfaces campaign names and ad templates for building
     * properly tagged ad URLs.
     *
     * Passes campaign names so the user can copy-paste them into the UTM builder.
     * No server-side URL construction — all preview logic is in the frontend.
     */
    public function tagGenerator(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        // Surface connected campaign names so the user can pick them in the form.
        // platform lives on ad_accounts, not campaigns — join to fetch it.
        $campaigns = Campaign::withoutGlobalScopes()
            ->where('campaigns.workspace_id', $workspaceId)
            ->whereIn('campaigns.status', ['active', 'paused', 'archived'])
            ->join('ad_accounts', 'ad_accounts.id', '=', 'campaigns.ad_account_id')
            ->select(['campaigns.id', 'campaigns.name', 'ad_accounts.platform'])
            ->orderBy('campaigns.name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'platform' => $c->platform])
            ->all();

        return Inertia::render('Manage/TagGenerator', [
            'campaigns' => $campaigns,
        ]);
    }
}
