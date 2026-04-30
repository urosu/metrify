<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\AdInsight;
use App\Models\Campaign;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Database\Query\JoinClause;

/**
 * Naming Convention explainer — read-only page under /integrations SubNavTabs.
 *
 * Shows the fixed `|`-delimited template, parse-status buckets, and a 30-day
 * coverage badge. Users fix campaign names inside Facebook/Google Ads; the
 * `parsed_convention` JSONB column refreshes on the next sync via
 * {@see \App\Services\CampaignNameParserService}.
 *
 * Coverage denominator = campaigns with spend in the last 30 days.
 * Numerator             = same, filtered to parsed_convention.parse_status = 'clean'.
 *
 * Reads: campaigns, ad_accounts, ad_insights.
 * Writes: nothing.
 * Called by: GET /{workspace:slug}/integrations/naming-convention
 *
 * @see docs/planning/backend.md §6 (ManageNamingConventionController in split plan)
 */
class NamingConventionController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $since       = now()->subDays(30)->toDateString();

        // Scoped to level=campaign / hourly excluded per CLAUDE.md "never SUM across ad_insights levels".
        $spendSub = AdInsight::withoutGlobalScopes()
            ->select('campaign_id', DB::raw('COALESCE(SUM(spend_in_reporting_currency), 0) AS spend_30d'))
            ->where('workspace_id', $workspaceId)
            ->where('level', 'campaign')
            ->whereNull('hour')
            ->where('date', '>=', $since)
            ->groupBy('campaign_id');

        // LOWER() match — platforms return mixed casing ('ACTIVE', 'active', 'enabled').
        $rows = Campaign::withoutGlobalScopes()
            ->where('campaigns.workspace_id', $workspaceId)
            ->whereRaw("LOWER(campaigns.status) IN ('active','enabled','delivering','paused','inactive','disabled','archived')")
            ->join('ad_accounts', 'ad_accounts.id', '=', 'campaigns.ad_account_id')
            ->leftJoinSub(
                $spendSub,
                'spend',
                fn (JoinClause $j) => $j->on('spend.campaign_id', '=', 'campaigns.id'),
            )
            ->select([
                'campaigns.id',
                'campaigns.name',
                'campaigns.parsed_convention',
                'ad_accounts.platform',
                DB::raw('COALESCE(spend.spend_30d, 0) AS spend_30d'),
            ])
            ->orderBy('campaigns.name')
            ->get();

        $clean   = [];
        $partial = [];
        $minimal = [];

        $coverageDenom = 0;
        $coverageNum   = 0;

        foreach ($rows as $row) {
            $pc     = is_array($row->parsed_convention) ? $row->parsed_convention : [];
            $status = $pc['parse_status'] ?? 'minimal';
            $spend  = (float) $row->spend_30d;

            $item = [
                'id'          => (int) $row->id,
                'name'        => (string) $row->name,
                'platform'    => (string) $row->platform,
                'spend_30d'   => $spend,
                'country'     => $pc['country']     ?? null,
                'campaign'    => $pc['campaign']    ?? null,
                'raw_target'  => $pc['raw_target']  ?? null,
                'target_type' => $pc['target_type'] ?? null,
                'target_slug' => $pc['target_slug'] ?? null,
                'shape'       => $pc['shape']       ?? null,
            ];

            if ($spend > 0) {
                $coverageDenom++;
                if ($status === 'clean') {
                    $coverageNum++;
                }
            }

            match ($status) {
                'clean'   => $clean[]   = $item,
                'partial' => $partial[] = $item,
                default   => $minimal[] = $item,
            };
        }

        $coveragePct = $coverageDenom > 0
            ? (int) round($coverageNum / $coverageDenom * 100)
            : null;

        return Inertia::render('Integrations/NamingConvention', [
            'buckets' => [
                'clean'   => $clean,
                'partial' => $partial,
                'minimal' => $minimal,
            ],
            'coverage' => [
                'percent'     => $coveragePct,
                'numerator'   => $coverageNum,
                'denominator' => $coverageDenom,
            ],
        ]);
    }
}
