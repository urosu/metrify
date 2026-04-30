<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * UTM Tag Generator — lives under `/integrations` SubNavTabs per
 * docs/planning/frontend.md:53.
 *
 * Surfaces campaign names so users can pick them inside the builder form.
 * URL construction is entirely client-side — this controller only provides
 * the reference list. Frontend values must stay in sync with
 * `ChannelMappingsSeeder.php` (see CLAUDE.md "UTM source / medium sync").
 *
 * Reads: campaigns, ad_accounts.
 * Writes: nothing.
 * Called by: GET /{workspace:slug}/integrations/tag-generator
 *
 * @see docs/pages/integrations.md
 * @see docs/planning/backend.md §6 (ManageTagGeneratorController in split plan)
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

        return Inertia::render('Integrations/TagGenerator', [
            'campaigns' => $campaigns,
        ]);
    }
}
