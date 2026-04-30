<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * UTM Tag Generator — renders at /tools/tag-generator.
 *
 * Surfaces campaign names so users can reference them inside the builder form.
 * URL construction is entirely client-side — this controller only provides the
 * reference list. Frontend values stay in sync with ChannelMappingsSeeder.php
 * (see CLAUDE.md "UTM source / medium sync").
 *
 * Pattern: Google Campaign URL Builder (template picker + live preview).
 * Renders: Tools/TagGenerator (re-uses Integrations/TagGenerator logic at new URL).
 *
 * Reads: campaigns, ad_accounts.
 * Writes: nothing.
 * Called by: GET /{workspace:slug}/tools/tag-generator
 *
 * @see docs/planning/backend.md §6
 * @see docs/competitors/_research_tools_utilities.md §1
 */
class TagGeneratorController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $campaigns = Campaign::withoutGlobalScopes()
            ->where('campaigns.workspace_id', $workspaceId)
            ->whereIn('campaigns.status', ['active', 'paused', 'archived'])
            ->join('ad_accounts', 'ad_accounts.id', '=', 'campaigns.ad_account_id')
            ->select(['campaigns.id', 'campaigns.name', 'ad_accounts.platform'])
            ->orderBy('campaigns.name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'platform' => $c->platform])
            ->all();

        return Inertia::render('Tools/TagGenerator', [
            'campaigns' => $campaigns,
        ]);
    }
}
