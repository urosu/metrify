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
 * Ad-level performance page — shows individual ad insights.
 *
 * Triggered by: GET /campaigns/ads
 * Reads from:   ads, adsets, campaigns, ad_accounts, ad_insights (level='ad')
 * Writes to:    nothing
 *
 * Query strategy: LEFT JOIN from ads (structure) to ad_insights (metrics).
 * This shows all ads — including those with zero spend in the period. See AdSetsController
 * for the rationale; the same applies here.
 *
 * Optional URL params:
 *   ?campaign_id=N  — drill-through from campaigns page
 *   ?adset_id=N     — drill-through from ad sets page
 *
 * Related: app/Http/Controllers/CampaignsController.php
 * Related: app/Http/Controllers/AdSetsController.php
 */
class AdsController extends Controller
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
            return Inertia::render('Campaigns/Ads', [
                'has_ad_accounts'      => false,
                'ad_accounts'          => [],
                'ads'                  => [],
                'campaign_name'        => null,
                'adset_name'           => null,
                'workspace_target_roas' => $workspace->target_roas ? (float) $workspace->target_roas : null,
                ...$params,
            ]);
        }

        $filteredAccounts = $params['platform'] === 'all'
            ? $adAccounts
            : $adAccounts->where('platform', $params['platform']);

        $adAccountIds = $filteredAccounts->pluck('id')->all();

        $campaignName = null;
        $adsetName    = null;

        if ($params['campaign_id'] !== null) {
            $row = DB::selectOne(
                "SELECT name FROM campaigns WHERE id = ? AND workspace_id = ?",
                [$params['campaign_id'], $workspaceId],
            );
            $campaignName = $row?->name;
        }

        if ($params['adset_id'] !== null) {
            $row = DB::selectOne(
                "SELECT ads.name, c.name AS campaign_name
                 FROM adsets ads
                 JOIN campaigns c ON c.id = ads.campaign_id
                 WHERE ads.id = ? AND ads.workspace_id = ?",
                [$params['adset_id'], $workspaceId],
            );
            $adsetName    = $row?->name;
            $campaignName = $campaignName ?? $row?->campaign_name;
        }

        $ads = $this->computeAds(
            $workspaceId,
            $adAccountIds,
            $params['from'],
            $params['to'],
            $params['status'],
            $params['platform'],
            $params['campaign_id'],
            $params['adset_id'],
        );

        // Sort in PHP — NULLs always last
        $sortKey   = $params['sort'];
        $direction = $params['direction'];
        usort($ads, function (array $a, array $b) use ($sortKey, $direction): int {
            $aVal = $a[$sortKey];
            $bVal = $b[$sortKey];
            if ($aVal === null && $bVal === null) return 0;
            if ($aVal === null) return 1;
            if ($bVal === null) return -1;
            $cmp = $aVal <=> $bVal;
            return $direction === 'asc' ? $cmp : -$cmp;
        });

        return Inertia::render('Campaigns/Ads', [
            'has_ad_accounts'      => true,
            'ad_accounts'          => $adAccountList,
            'ads'                  => $ads,
            'campaign_name'        => $campaignName,
            'adset_name'           => $adsetName,
            'workspace_target_roas' => $workspace->target_roas ? (float) $workspace->target_roas : null,
            ...$params,
        ]);
    }

    // ─── Parameter validation ─────────────────────────────────────────────────

    /**
     * @return array{from:string,to:string,platform:string,status:string,sort:string,direction:string,campaign_id:int|null,adset_id:int|null}
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
            'adset_id'    => ['sometimes', 'nullable', 'integer', 'min:1'],
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
            'adset_id'    => isset($v['adset_id'])    ? (int) $v['adset_id']    : null,
            'view'        => $v['view']         ?? 'table',
        ];
    }

    // ─── UTM attribution ──────────────────────────────────────────────────────

    /**
     * Build a map of ad internal ID → {revenue, orders} from UTM-tagged orders.
     *
     * Matches utm_term against ads.external_id (platform ID) or ads.name (name fallback).
     * Same pattern as CampaignsController and AdSetsController, one level deeper:
     * utm_campaign → campaign, utm_content → adset, utm_term → ad.
     *
     * Why utm_term: Facebook ad URL builders write the ad ID into utm_term
     * (e.g. {{ad.id}}). Name matching handles manual UTM setups.
     *
     * @return array<int, array{revenue:float,orders:int}>  Keyed by ads.id
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
                a.id                               AS ad_id,
                SUM(o.total_in_reporting_currency) AS attributed_revenue,
                COUNT(o.id)                        AS attributed_orders
            FROM orders o
            JOIN ads a
              ON  a.workspace_id = o.workspace_id
              AND (
                    o.utm_term = a.external_id
                 OR LOWER(o.utm_term) = LOWER(a.name)
              )
            WHERE o.workspace_id = ?
              AND o.status IN ('completed', 'processing')
              AND o.total_in_reporting_currency IS NOT NULL
              AND o.utm_term IS NOT NULL
              AND o.utm_term <> ''
              AND o.occurred_at BETWEEN ? AND ?
              {$sourceFilter}
            GROUP BY a.id
        ", [$workspaceId, $from . ' 00:00:00', $to . ' 23:59:59']);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->ad_id] = [
                'revenue' => (float) $row->attributed_revenue,
                'orders'  => (int)   $row->attributed_orders,
            ];
        }

        return $map;
    }

    // ─── Ad rows ──────────────────────────────────────────────────────────────

    /**
     * LEFT JOIN from ads (structure) to ad_insights (metrics).
     *
     * @param  int[]    $adAccountIds
     * @param  int|null $campaignId   Drill-through from campaigns page
     * @param  int|null $adsetId      Drill-through from ad sets page
     * @return array<int, array<string, mixed>>
     */
    private function computeAds(
        int $workspaceId,
        array $adAccountIds,
        string $from,
        string $to,
        string $status,
        string $platform,
        ?int $campaignId,
        ?int $adsetId,
    ): array {
        if (empty($adAccountIds)) {
            return [];
        }

        $attributionMap = $this->buildUtmAttributionMap($workspaceId, $from, $to, $platform);

        $placeholders = implode(',', array_fill(0, count($adAccountIds), '?'));

        $statusFilter   = match ($status) {
            'active' => "AND LOWER(a.status) IN ('active','enabled','delivering')",
            'paused' => "AND LOWER(a.status) IN ('paused','inactive','disabled')",
            default  => '',
        };
        $campaignFilter = $campaignId !== null ? "AND c.id = ?"   : '';
        $adsetFilter    = $adsetId    !== null ? "AND ads.id = ?" : '';

        $rows = DB::select("
            SELECT
                a.id,
                a.name,
                a.status,
                ads.id   AS adset_id,
                ads.name AS adset_name,
                c.id     AS campaign_id,
                c.name   AS campaign_name,
                aa.platform,
                COALESCE(SUM(ai.spend_in_reporting_currency), 0) AS total_spend,
                COALESCE(SUM(ai.impressions), 0)                 AS total_impressions,
                COALESCE(SUM(ai.clicks), 0)                      AS total_clicks,
                AVG(ai.platform_roas)                            AS avg_platform_roas
            FROM ads a
            JOIN adsets ads     ON ads.id = a.adset_id
            JOIN campaigns c    ON c.id   = ads.campaign_id
            JOIN ad_accounts aa ON aa.id  = c.ad_account_id
            LEFT JOIN ad_insights ai
                ON  ai.ad_id = a.id
                AND ai.level = 'ad'
                AND ai.hour  IS NULL
                AND ai.date  BETWEEN ? AND ?
            WHERE a.workspace_id = ?
              AND aa.id IN ({$placeholders})
              {$statusFilter}
              {$campaignFilter}
              {$adsetFilter}
            GROUP BY a.id, a.name, a.status, ads.id, ads.name, c.id, c.name, aa.platform
        ", array_merge(
            [$from, $to, $workspaceId],
            $adAccountIds,
            $campaignId !== null ? [$campaignId] : [],
            $adsetId    !== null ? [$adsetId]    : [],
        ));

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
                'adset_id'           => (int)    $row->adset_id,
                'adset_name'         => (string) ($row->adset_name ?? ''),
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
