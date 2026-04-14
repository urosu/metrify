<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ad set performance page — shows adset-level insights.
 *
 * Triggered by: GET /campaigns/adsets
 * Reads from:   adsets, campaigns, ad_accounts, ad_insights (level='adset')
 * Writes to:    nothing
 *
 * Query strategy: LEFT JOIN from adsets (structure) to ad_insights (metrics).
 * This shows all adsets — including those with zero spend in the period — rather
 * than only adsets that happen to have insight rows in the window. Campaign-level
 * insights are dense (high coverage) but adset/ad insights start sparse and grow
 * as more syncs accumulate.
 *
 * Optional URL params:
 *   ?campaign_id=N  — drill-through from the campaigns table
 *
 * Related: app/Http/Controllers/CampaignsController.php
 * Related: app/Http/Controllers/AdsController.php
 */
class AdSetsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $workspace   = Workspace::withoutGlobalScopes()->findOrFail($workspaceId);

        $params = $this->validateParams($request);

        $adAccounts = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->select(['id', 'platform', 'name', 'status', 'last_synced_at'])
            ->get();

        $adAccountList = $adAccounts->map(fn ($a) => [
            'id'             => $a->id,
            'platform'       => $a->platform,
            'name'           => $a->name,
            'status'         => $a->status,
            'last_synced_at' => $a->last_synced_at,
        ])->values()->all();

        if ($adAccounts->isEmpty()) {
            return Inertia::render('Campaigns/AdSets', [
                'has_ad_accounts'      => false,
                'ad_accounts'          => [],
                'adsets'               => [],
                'campaign_name'        => null,
                'workspace_target_roas' => $workspace->target_roas ? (float) $workspace->target_roas : null,
                ...$params,
            ]);
        }

        $filteredAccounts = $params['platform'] === 'all'
            ? $adAccounts
            : $adAccounts->where('platform', $params['platform']);

        $adAccountIds = $filteredAccounts->pluck('id')->all();

        $campaignName = null;
        if ($params['campaign_id'] !== null) {
            $campaign = DB::selectOne(
                "SELECT name FROM campaigns WHERE id = ? AND workspace_id = ?",
                [$params['campaign_id'], $workspaceId],
            );
            $campaignName = $campaign?->name;
        }

        $adsets = $this->computeAdSets(
            $workspaceId,
            $adAccountIds,
            $params['from'],
            $params['to'],
            $params['status'],
            $params['platform'],
            $params['campaign_id'],
        );

        // Sort in PHP — NULLs always last regardless of direction
        $sortKey   = $params['sort'];
        $direction = $params['direction'];
        usort($adsets, function (array $a, array $b) use ($sortKey, $direction): int {
            $aVal = $a[$sortKey];
            $bVal = $b[$sortKey];
            if ($aVal === null && $bVal === null) return 0;
            if ($aVal === null) return 1;
            if ($bVal === null) return -1;
            $cmp = $aVal <=> $bVal;
            return $direction === 'asc' ? $cmp : -$cmp;
        });

        return Inertia::render('Campaigns/AdSets', [
            'has_ad_accounts'      => true,
            'ad_accounts'          => $adAccountList,
            'adsets'               => $adsets,
            'campaign_name'        => $campaignName,
            'workspace_target_roas' => $workspace->target_roas ? (float) $workspace->target_roas : null,
            ...$params,
        ]);
    }

    // ─── Parameter validation ─────────────────────────────────────────────────

    /**
     * @return array{from:string,to:string,platform:string,status:string,sort:string,direction:string,campaign_id:int|null}
     */
    private function validateParams(Request $request): array
    {
        $v = $request->validate([
            'from'        => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to'          => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'platform'    => ['sometimes', 'nullable', 'in:all,facebook,google'],
            'status'      => ['sometimes', 'nullable', 'in:all,active,paused'],
            'sort'        => ['sometimes', 'nullable', 'in:spend,impressions,clicks,ctr,cpc,platform_roas'],
            'direction'   => ['sometimes', 'nullable', 'in:asc,desc'],
            'campaign_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'view'        => ['sometimes', 'nullable', 'in:table,quadrant'],
        ]);

        return [
            'from'        => $v['from']        ?? now()->subDays(29)->toDateString(),
            'to'          => $v['to']           ?? now()->toDateString(),
            'platform'    => $v['platform']     ?? 'all',
            'status'      => $v['status']       ?? 'all',
            'sort'        => $v['sort']         ?? 'spend',
            'direction'   => $v['direction']    ?? 'desc',
            'campaign_id' => isset($v['campaign_id']) ? (int) $v['campaign_id'] : null,
            'view'        => $v['view']         ?? 'table',
        ];
    }

    // ─── UTM attribution ──────────────────────────────────────────────────────

    /**
     * Build a map of adset internal ID → {revenue, orders} from UTM-tagged orders.
     *
     * Matches utm_content against adsets.external_id (platform ID, common case) or
     * adsets.name (name-based fallback). Same pattern as CampaignsController::buildUtmAttributionMap()
     * but one level deeper: utm_campaign → campaign, utm_content → adset.
     *
     * Why utm_content: Facebook/Google ad URL builders write the adset ID into utm_content
     * (e.g. {{adset.id}} → "120241558531060383"). Name matching handles manual UTM setups.
     *
     * @return array<int, array{revenue:float,orders:int}>  Keyed by adsets.id
     */
    private function buildUtmAttributionMap(int $workspaceId, string $from, string $to, string $platform): array
    {
        $sourceFilter = match ($platform) {
            'facebook' => "AND LOWER(o.utm_source) IN ('facebook','fb','ig','instagram')",
            'google'   => "AND LOWER(o.utm_source) IN ('google','cpc','google-ads','ppc')",
            default    => "AND LOWER(o.utm_source) IN ('facebook','fb','ig','instagram','google','cpc','google-ads','ppc')",
        };

        $rows = DB::select("
            SELECT
                ads.id                             AS adset_id,
                SUM(o.total_in_reporting_currency) AS attributed_revenue,
                COUNT(o.id)                        AS attributed_orders
            FROM orders o
            JOIN adsets ads
              ON  ads.workspace_id = o.workspace_id
              AND (
                    o.utm_content = ads.external_id
                 OR LOWER(o.utm_content) = LOWER(ads.name)
              )
            WHERE o.workspace_id = ?
              AND o.status IN ('completed', 'processing')
              AND o.total_in_reporting_currency IS NOT NULL
              AND o.utm_content IS NOT NULL
              AND o.utm_content <> ''
              AND o.occurred_at BETWEEN ? AND ?
              {$sourceFilter}
            GROUP BY ads.id
        ", [$workspaceId, $from . ' 00:00:00', $to . ' 23:59:59']);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->adset_id] = [
                'revenue' => (float) $row->attributed_revenue,
                'orders'  => (int)   $row->attributed_orders,
            ];
        }

        return $map;
    }

    // ─── Ad set rows ──────────────────────────────────────────────────────────

    /**
     * LEFT JOIN from adsets (structure) to ad_insights (metrics).
     *
     * Why LEFT JOIN: adset-level insight rows only exist for the last 3 days after the
     * first sync. Querying FROM ad_insights would hide all adsets with no spend in the
     * window. LEFT JOIN shows all adsets with zero spend where no rows exist.
     *
     * @param  int[]    $adAccountIds
     * @param  int|null $campaignId   Drill-through filter
     * @return array<int, array<string, mixed>>
     */
    private function computeAdSets(
        int $workspaceId,
        array $adAccountIds,
        string $from,
        string $to,
        string $status,
        string $platform,
        ?int $campaignId,
    ): array {
        if (empty($adAccountIds)) {
            return [];
        }

        $attributionMap = $this->buildUtmAttributionMap($workspaceId, $from, $to, $platform);

        $placeholders = implode(',', array_fill(0, count($adAccountIds), '?'));

        $statusFilter   = match ($status) {
            'active' => "AND LOWER(ads.status) IN ('active','enabled','delivering')",
            'paused' => "AND LOWER(ads.status) IN ('paused','inactive','disabled')",
            default  => '',
        };
        $campaignFilter = $campaignId !== null ? "AND c.id = ?" : '';

        $rows = DB::select("
            SELECT
                ads.id,
                ads.name,
                ads.status,
                c.id     AS campaign_id,
                c.name   AS campaign_name,
                aa.platform,
                COALESCE(SUM(ai.spend_in_reporting_currency), 0) AS total_spend,
                COALESCE(SUM(ai.impressions), 0)                 AS total_impressions,
                COALESCE(SUM(ai.clicks), 0)                      AS total_clicks,
                AVG(ai.platform_roas)                            AS avg_platform_roas
            FROM adsets ads
            JOIN campaigns c    ON c.id  = ads.campaign_id
            JOIN ad_accounts aa ON aa.id = c.ad_account_id
            LEFT JOIN ad_insights ai
                ON  ai.adset_id      = ads.id
                AND ai.level         = 'adset'
                AND ai.hour          IS NULL
                AND ai.date          BETWEEN ? AND ?
            WHERE ads.workspace_id = ?
              AND aa.id IN ({$placeholders})
              {$statusFilter}
              {$campaignFilter}
            GROUP BY ads.id, ads.name, ads.status, c.id, c.name, aa.platform
        ", array_merge([$from, $to, $workspaceId], $adAccountIds, $campaignId !== null ? [$campaignId] : []));

        return array_map(function (object $row) use ($attributionMap): array {
            $spend       = (float) $row->total_spend;
            $impressions = (int)   $row->total_impressions;
            $clicks      = (int)   $row->total_clicks;

            $attribution       = $attributionMap[(int) $row->id] ?? null;
            $attributedRevenue = $attribution ? $attribution['revenue'] : null;
            $attributedOrders  = $attribution ? $attribution['orders']  : 0;

            return [
                'id'                 => (int)    $row->id,
                'name'               => (string) ($row->name ?? ''),
                'status'             => $row->status,
                'platform'           => (string) $row->platform,
                'campaign_id'        => (int)    $row->campaign_id,
                'campaign_name'      => (string) ($row->campaign_name ?? ''),
                'spend'              => $spend,
                'impressions'        => $impressions,
                'clicks'             => $clicks,
                'ctr'                => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : null,
                'cpc'                => $clicks > 0 ? round($spend / $clicks, 4) : null,
                'platform_roas'      => $row->avg_platform_roas !== null
                    ? round((float) $row->avg_platform_roas, 2)
                    : null,
                'real_roas'          => ($spend > 0 && $attributedRevenue !== null && $attributedRevenue > 0)
                    ? round($attributedRevenue / $spend, 2)
                    : null,
                'attributed_revenue' => $attributedRevenue,
                'attributed_orders'  => $attributedOrders,
            ];
        }, $rows);
    }
}
