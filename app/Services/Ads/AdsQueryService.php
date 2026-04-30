<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\Workspace;
use App\Services\Metrics\MetricSourceResolver;
use App\Services\RevenueAttributionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Heavy query + math layer behind the /ads page (Table / Creative Gallery / Triage views).
 *
 * Extracted from the former monolithic CampaignsController (1679 LoC) so the
 * controller stays thin per docs/planning/backend.md §0 rule 1.
 *
 * Responsibilities:
 *   - Row builders for the Campaign / Adset / Ad hierarchy toggle.
 *   - Creative-grid builder (top-N ad-level cards with Motion Score).
 *   - Pacing-tab budget burn-rate rollup.
 *   - UTM attribution map (campaign id → {revenue, orders}).
 *   - First-order ROAS (§F7) and Day-30 ROAS (§F8) map helpers.
 *   - Motion Score (§F11) + Winners/Losers classifier.
 *   - Summary metrics + spend-over-time chart.
 *
 * Reads: ad_insights, campaigns, adsets, ads, ad_accounts, orders, daily_snapshots
 * Writes: nothing (pure computation / query).
 * Called by: AdsController, CreativeGalleryController.
 *
 * Section map (search for the ─── markers):
 *   L041  Campaign rows       L203  Adset rows          L294  Ad rows
 *   L404  Creative grid       L542  TSX-contract rows   L830  Private query helpers
 *   L919  Pacing tab          L1003 Winners/Losers      L1084 Metrics + chart
 *   L1277 Revenue context     L1311 Helpers (public)    L1341 Private maps
 *   L1527 Motion Score        L1650 Private SQL helpers
 *
 * @see docs/pages/ads.md
 * @see docs/planning/backend.md §6 Controllers breakup plan
 */
final class AdsQueryService
{
    /**
     * Per-request cache for buildCombinedAttributionMaps() results.
     *
     * All three compute*Rows methods share the same (workspaceId, from, to, platform)
     * key so adset/ad views reuse the map already fetched by computeCampaignRows
     * (or built on first call when only adset/ad level is requested).
     *
     * Key: "<workspaceId>:<from>:<to>:<platform>"
     *
     * @var array<string, array{utm:array<int,array{revenue:float,orders:int}>, firstOrder:array<int,array{revenue:float,orders:int}>, day30:array<int,array{revenue:float}>}>
     */
    private array $combinedAttrMemo = [];

    public function __construct(
        private readonly RevenueAttributionService $attribution,
        private readonly MetricSourceResolver $sourceResolver,
    ) {}

    // ─── Campaign rows ────────────────────────────────────────────────────────

    /**
     * Campaign-level rows with every §F column (CPA §F9, First-order ROAS §F7,
     * Day-30 ROAS §F8, Motion Score §F11, Velocity §F10).
     *
     * @param  Collection<int, \App\Models\AdAccount>  $adAccounts
     * @param  int[]  $adAccountIds
     * @return array<int, array<string, mixed>>
     */
    public function computeCampaignRows(
        int $workspaceId,
        Collection $adAccounts,
        string $from,
        string $to,
        string $platform,
        string $status,
        array $adAccountIds = [],
        ?float $workspaceTargetRoas = null,
    ): array {
        $filtered = $this->filterAccounts($adAccounts, $platform, $adAccountIds);
        if ($filtered->isEmpty()) {
            return [];
        }

        $ids = $filtered->pluck('id')->all();
        $placeholders = $this->placeholders($ids);
        $statusFilter = $this->statusFilter($status, 'c');

        // Video metric aggregation via correlated JSONB subqueries.
        $v = $this->videoSumExpr();

        $rows = DB::select("
            SELECT
                c.id, c.name, aa.platform, c.status,
                c.daily_budget, c.lifetime_budget, c.budget_type, c.target_value AS target_roas,
                COALESCE(SUM(ai.spend_in_reporting_currency), 0) AS total_spend,
                COALESCE(SUM(ai.impressions), 0)                 AS total_impressions,
                COALESCE(SUM(ai.clicks), 0)                      AS total_clicks,
                COALESCE(SUM(ai.platform_conversions), 0)        AS total_platform_conversions,
                AVG(ai.platform_roas)                            AS avg_platform_roas,
                {$v('video_continuous_2_sec_watched_actions')}   AS video_3s_plays,
                {$v('video_15_sec_watched_actions')}             AS video_15s_plays,
                {$v('video_p25_watched_actions')}                AS video_p25_plays,
                {$v('video_p50_watched_actions')}                AS video_p50_plays,
                {$v('video_p75_watched_actions')}                AS video_p75_plays,
                {$v('video_p100_watched_actions')}               AS video_p100_plays,
                {$v('outbound_clicks')}                          AS outbound_clicks_count
            FROM ad_insights ai
            JOIN campaigns c    ON c.id  = ai.campaign_id
            JOIN ad_accounts aa ON aa.id = ai.ad_account_id
            WHERE ai.workspace_id = ?
              AND ai.ad_account_id IN ({$placeholders})
              AND ai.level = 'campaign'
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
              {$statusFilter}
            GROUP BY c.id, c.name, aa.platform, c.status,
                     c.daily_budget, c.lifetime_budget, c.budget_type, c.target_value
        ", array_merge([$workspaceId], $ids, [$from, $to]));

        $daysInPeriod = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
        $daysElapsed  = min(Carbon::parse($from)->diffInDays(Carbon::today()) + 1, $daysInPeriod);

        // Consolidate three attribution round-trips into one CTE query.
        // combinedAttributionMaps() memoizes per (workspaceId, from, to, platform) so adset/ad
        // views that run after campaigns share the same result without re-querying.
        $utmPlatform      = $platform === 'all' ? '' : $platform;
        $combined         = $this->combinedAttributionMaps($workspaceId, $from, $to, $utmPlatform);
        $attrMap          = $combined['utm'];
        $firstOrderMap    = $combined['firstOrder'];
        $day30Map         = $combined['day30'];
        $day30IsPending   = Carbon::parse($to)->addDays(30)->isAfter(Carbon::today());
        $day30LocksInDays = $day30IsPending
            ? (int) Carbon::today()->diffInDays(Carbon::parse($to)->addDays(30), false)
            : null;

        return array_map(function (object $row) use (
            $attrMap, $firstOrderMap, $day30Map, $day30IsPending, $day30LocksInDays,
            $daysInPeriod, $daysElapsed, $workspaceTargetRoas,
        ): array {
            $spend       = (float) $row->total_spend;
            $impressions = (int)   $row->total_impressions;
            $clicks      = (int)   $row->total_clicks;
            $platformCvs = (float) $row->total_platform_conversions;

            $attr              = $attrMap[(int) $row->id] ?? null;
            $attributedRevenue = $attr !== null ? (float) $attr['revenue'] : null;
            $attributedOrders  = $attr !== null ? (int)   $attr['orders']  : 0;

            $firstOrderData = $firstOrderMap[(int) $row->id] ?? null;
            $firstOrderRoas = ($firstOrderData !== null && $spend > 0)
                ? round((float) $firstOrderData['revenue'] / $spend, 2)
                : null;

            $day30Roas = null;
            if (! $day30IsPending) {
                $d = $day30Map[(int) $row->id] ?? null;
                if ($d !== null && $spend > 0) {
                    $day30Roas = round((float) $d['revenue'] / $spend, 2);
                }
            }

            $spendVelocity = null;
            if ($daysInPeriod > 0 && $daysElapsed > 0) {
                $budgetForPeriod = match ($row->budget_type) {
                    'daily'    => $row->daily_budget !== null
                        ? (float) $row->daily_budget * $daysInPeriod : null,
                    'lifetime' => $row->lifetime_budget !== null
                        ? (float) $row->lifetime_budget : null,
                    default    => null,
                };
                if ($budgetForPeriod !== null && $budgetForPeriod > 0) {
                    $expectedPace  = $daysElapsed / $daysInPeriod;
                    $actualPace    = $spend / $budgetForPeriod;
                    $spendVelocity = round($actualPace / $expectedPace, 3);
                }
            }

            $campaignTargetRoas = $row->target_roas !== null
                ? (float) $row->target_roas
                : $workspaceTargetRoas;

            $realRoas = ($spend > 0 && $attributedRevenue !== null && $attributedRevenue > 0)
                ? round($attributedRevenue / $spend, 2)
                : null;

            $motionScore = $this->computeMotionScore(
                video3s:          (float) $row->video_3s_plays,
                video15s:         (float) $row->video_15s_plays,
                outboundClicks:   (float) $row->outbound_clicks_count,
                impressions:      $impressions,
                clicks:           $clicks,
                attributedOrders: $attributedOrders,
                realRoas:         $realRoas,
                targetRoas:       $campaignTargetRoas,
            );

            return [
                'id'                  => (int)    $row->id,
                'name'                => (string) ($row->name ?? ''),
                'platform'            => (string) $row->platform,
                'status'              => $row->status,
                'spend'               => $spend,
                'impressions'         => $impressions,
                'clicks'              => $clicks,
                'ctr'                 => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : null,
                'cpc'                 => $clicks > 0 ? round($spend / $clicks, 4) : null,
                'cpa'                 => ($platformCvs > 0 && $spend > 0) ? round($spend / $platformCvs, 2) : null,
                'platform_roas'       => $row->avg_platform_roas !== null ? round((float) $row->avg_platform_roas, 2) : null,
                'real_roas'           => $realRoas,
                'real_cpo'            => ($spend > 0 && $attributedOrders > 0) ? round($spend / $attributedOrders, 2) : null,
                'attributed_revenue'  => $attributedRevenue,
                'attributed_orders'   => $attributedOrders,
                'first_order_roas'    => $firstOrderRoas,
                'day30_roas'          => $day30Roas,
                'day30_pending'       => $day30IsPending,
                'day30_locks_in_days' => $day30LocksInDays,
                'spend_velocity'      => $spendVelocity,
                'motion_score'        => $motionScore,
                'verdict'             => $motionScore !== null ? $motionScore['verdict'] : null,
                'target_roas'         => $row->target_roas !== null ? round((float) $row->target_roas, 2) : null,
            ];
        }, $rows);
    }

    // ─── Adset rows ──────────────────────────────────────────────────────────

    /**
     * @param  Collection<int, \App\Models\AdAccount>  $adAccounts
     * @param  int[]  $adAccountIds
     * @return array<int, array<string, mixed>>
     */
    public function computeAdsetRows(
        int $workspaceId,
        Collection $adAccounts,
        string $from,
        string $to,
        string $platform,
        string $status,
        array $adAccountIds = [],
        ?int $campaignId = null,
    ): array {
        $filtered = $this->filterAccounts($adAccounts, $platform, $adAccountIds);
        if ($filtered->isEmpty()) {
            return [];
        }

        $ids = $filtered->pluck('id')->all();
        $placeholders = $this->placeholders($ids);
        $statusFilter = $this->statusFilter($status, 'a');
        $campaignFilter = $campaignId !== null ? 'AND a.campaign_id = ?' : '';
        $campaignArgs   = $campaignId !== null ? [$campaignId] : [];

        $rows = DB::select("
            SELECT
                a.id, a.name, a.status,
                c.id AS campaign_id, c.name AS campaign_name,
                aa.platform,
                COALESCE(SUM(ai.spend_in_reporting_currency), 0) AS total_spend,
                COALESCE(SUM(ai.impressions), 0)                 AS total_impressions,
                COALESCE(SUM(ai.clicks), 0)                      AS total_clicks,
                COALESCE(SUM(ai.platform_conversions), 0)        AS total_platform_conversions,
                AVG(ai.platform_roas)                            AS avg_platform_roas
            FROM ad_insights ai
            JOIN adsets  a  ON a.id  = ai.adset_id
            JOIN campaigns c ON c.id = a.campaign_id
            JOIN ad_accounts aa ON aa.id = ai.ad_account_id
            WHERE ai.workspace_id = ?
              AND ai.ad_account_id IN ({$placeholders})
              AND ai.level = 'adset'
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
              {$statusFilter}
              {$campaignFilter}
            GROUP BY a.id, a.name, a.status, c.id, c.name, aa.platform
        ", array_merge([$workspaceId], $ids, [$from, $to], $campaignArgs));

        // Reuse the combined map (utm slice) already built by computeCampaignRows for the same
        // request, or build it now if only adset level is being rendered.
        $utmPlatform = $platform === 'all' ? '' : $platform;
        $attrMap     = $this->combinedAttributionMaps($workspaceId, $from, $to, $utmPlatform)['utm'];

        return array_map(function (object $row) use ($attrMap): array {
            $spend       = (float) $row->total_spend;
            $impressions = (int)   $row->total_impressions;
            $clicks      = (int)   $row->total_clicks;
            $platformCvs = (float) $row->total_platform_conversions;

            // Adsets are attribution-matched via parent campaign.
            $attr              = $attrMap[(int) $row->campaign_id] ?? null;
            $attributedRevenue = $attr !== null ? (float) $attr['revenue'] : null;
            $attributedOrders  = $attr !== null ? (int)   $attr['orders']  : 0;

            $rates = $this->computeRateMetrics($spend, $impressions, $clicks, $platformCvs, $attributedRevenue, $attributedOrders);

            return [
                'id'                 => (int)    $row->id,
                'name'               => (string) ($row->name ?? ''),
                'platform'           => (string) $row->platform,
                'status'             => $row->status,
                'campaign_id'        => (int) $row->campaign_id,
                'campaign_name'      => (string) ($row->campaign_name ?? ''),
                'spend'              => $spend,
                'impressions'        => $impressions,
                'clicks'             => $clicks,
                'ctr'                => $rates['ctr'],
                'cpc'                => $rates['cpc'],
                'cpa'                => $rates['cpa'],
                'platform_roas'      => $row->avg_platform_roas !== null ? round((float) $row->avg_platform_roas, 2) : null,
                'real_roas'          => $rates['real_roas'],
                'real_cpo'           => $rates['real_cpo'],
                'attributed_revenue' => $attributedRevenue,
                'attributed_orders'  => $attributedOrders,
                'target_roas'        => null,
            ];
        }, $rows);
    }

    // ─── Ad rows ─────────────────────────────────────────────────────────────

    /**
     * @param  Collection<int, \App\Models\AdAccount>  $adAccounts
     * @param  int[]  $adAccountIds
     * @return array<int, array<string, mixed>>
     */
    public function computeAdRows(
        int $workspaceId,
        Collection $adAccounts,
        string $from,
        string $to,
        string $platform,
        string $status,
        array $adAccountIds = [],
        ?int $campaignId = null,
        ?int $adsetId = null,
    ): array {
        $filtered = $this->filterAccounts($adAccounts, $platform, $adAccountIds);
        if ($filtered->isEmpty()) {
            return [];
        }

        $ids = $filtered->pluck('id')->all();
        $placeholders = $this->placeholders($ids);
        $statusFilter = $this->statusFilter($status, 'ads');
        $campaignFilter = $campaignId !== null ? 'AND c.id = ?' : '';
        $adsetFilter    = $adsetId    !== null ? 'AND a.id = ?' : '';
        $filterArgs     = array_values(array_filter([$campaignId, $adsetId], fn ($v) => $v !== null));

        $v = $this->videoSumExpr();

        $rows = DB::select("
            SELECT
                ads.id, ads.name, ads.status, ads.effective_status, ads.creative_data,
                a.id AS adset_id, a.name AS adset_name,
                c.id AS campaign_id, c.name AS campaign_name, c.target_value AS target_roas,
                aa.platform,
                COALESCE(SUM(ai.spend_in_reporting_currency), 0) AS total_spend,
                COALESCE(SUM(ai.impressions), 0)                 AS total_impressions,
                COALESCE(SUM(ai.clicks), 0)                      AS total_clicks,
                COALESCE(SUM(ai.platform_conversions), 0)        AS total_platform_conversions,
                AVG(ai.platform_roas)                            AS avg_platform_roas,
                {$v('video_continuous_2_sec_watched_actions')}   AS video_3s_plays,
                {$v('video_15_sec_watched_actions')}             AS video_15s_plays,
                {$v('outbound_clicks')}                          AS outbound_clicks_count
            FROM ad_insights ai
            JOIN ads         ON ads.id = ai.ad_id
            JOIN adsets  a   ON a.id   = ads.adset_id
            JOIN campaigns c ON c.id   = a.campaign_id
            JOIN ad_accounts aa ON aa.id = ai.ad_account_id
            WHERE ai.workspace_id = ?
              AND ai.ad_account_id IN ({$placeholders})
              AND ai.level = 'ad'
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
              {$statusFilter}
              {$campaignFilter}
              {$adsetFilter}
            GROUP BY ads.id, ads.name, ads.status, ads.effective_status, ads.creative_data,
                     a.id, a.name, c.id, c.name, c.target_value, aa.platform
        ", array_merge([$workspaceId], $ids, [$from, $to], $filterArgs));

        // Reuse the combined map (utm slice) already built by computeCampaignRows for the same
        // request, or build it now if only ad level is being rendered.
        $utmPlatform = $platform === 'all' ? '' : $platform;
        $attrMap     = $this->combinedAttributionMaps($workspaceId, $from, $to, $utmPlatform)['utm'];

        return array_map(function (object $row) use ($attrMap): array {
            $spend       = (float) $row->total_spend;
            $impressions = (int)   $row->total_impressions;
            $clicks      = (int)   $row->total_clicks;
            $platformCvs = (float) $row->total_platform_conversions;

            $attr              = $attrMap[(int) $row->campaign_id] ?? null;
            $attributedRevenue = $attr !== null ? (float) $attr['revenue'] : null;
            $attributedOrders  = $attr !== null ? (int)   $attr['orders']  : 0;

            $creative = is_string($row->creative_data)
                ? json_decode($row->creative_data, true)
                : $row->creative_data;

            $rates = $this->computeRateMetrics($spend, $impressions, $clicks, $platformCvs, $attributedRevenue, $attributedOrders);

            return [
                'id'                 => (int)    $row->id,
                'name'               => (string) ($row->name ?? ''),
                'platform'           => (string) $row->platform,
                'status'             => $row->status,
                'effective_status'   => $row->effective_status,
                'campaign_id'        => (int)    $row->campaign_id,
                'campaign_name'      => (string) ($row->campaign_name ?? ''),
                'adset_id'           => (int)    $row->adset_id,
                'adset_name'         => (string) ($row->adset_name ?? ''),
                'thumbnail_url'      => $creative['image_url'] ?? $creative['thumbnail_url'] ?? null,
                'headline'           => $creative['title'] ?? null,
                'spend'              => $spend,
                'impressions'        => $impressions,
                'clicks'             => $clicks,
                'ctr'                => $rates['ctr'],
                'cpc'                => $rates['cpc'],
                'cpa'                => $rates['cpa'],
                'platform_roas'      => $row->avg_platform_roas !== null ? round((float) $row->avg_platform_roas, 2) : null,
                'real_roas'          => $rates['real_roas'],
                'real_cpo'           => $rates['real_cpo'],
                'attributed_revenue' => $attributedRevenue,
                'attributed_orders'  => $attributedOrders,
                'target_roas'        => $row->target_roas !== null ? round((float) $row->target_roas, 2) : null,
            ];
        }, $rows);
    }

    // ─── Creative grid (§F11) ────────────────────────────────────────────────

    /**
     * Top-N ad-level creative cards for the Creative Gallery view / right panel.
     *
     * @param  int[]  $adAccountIds
     * @return array<int, array<string, mixed>>
     */
    public function buildCreativeGrid(
        int $workspaceId,
        array $adAccountIds,
        string $from,
        string $to,
        ?int $campaignId,
        ?int $adsetId,
        ?float $workspaceTargetRoas,
        int $limit = 60,
    ): array {
        if (empty($adAccountIds)) {
            return [];
        }

        $placeholders = $this->placeholders($adAccountIds);
        $campaignFilter = $campaignId !== null ? 'AND c.id = ?' : '';
        $adsetFilter    = $adsetId    !== null ? 'AND a.id = ?' : '';
        $filterArgs     = array_values(array_filter([$campaignId, $adsetId], fn ($v) => $v !== null));

        $v = $this->videoSumExpr();

        $rows = DB::select("
            SELECT
                ads.id AS ad_id, ads.name AS ad_name,
                ads.status, ads.effective_status, ads.creative_data,
                a.id AS adset_id,
                c.id AS campaign_id, c.name AS campaign_name, c.target_value AS target_roas,
                aa.platform,
                COALESCE(SUM(ai.spend_in_reporting_currency), 0) AS ad_spend,
                COALESCE(SUM(ai.impressions), 0)                 AS ad_impressions,
                COALESCE(SUM(ai.clicks), 0)                      AS ad_clicks,
                {$v('video_continuous_2_sec_watched_actions')}   AS video_3s_plays,
                {$v('video_15_sec_watched_actions')}             AS video_15s_plays,
                {$v('video_p25_watched_actions')}                AS video_p25_plays,
                {$v('video_p50_watched_actions')}                AS video_p50_plays,
                {$v('video_p75_watched_actions')}                AS video_p75_plays,
                {$v('video_p100_watched_actions')}               AS video_p100_plays,
                {$v('outbound_clicks')}                          AS outbound_clicks_count
            FROM ad_insights ai
            JOIN ads         ON ads.id = ai.ad_id
            JOIN adsets  a   ON a.id   = ads.adset_id
            JOIN campaigns c ON c.id   = a.campaign_id
            JOIN ad_accounts aa ON aa.id = ai.ad_account_id
            WHERE ai.workspace_id = ?
              AND ai.ad_account_id IN ({$placeholders})
              AND ai.level = 'ad'
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
              {$campaignFilter}
              {$adsetFilter}
            GROUP BY ads.id, ads.name, ads.status, ads.effective_status, ads.creative_data,
                     a.id, c.id, c.name, c.target_value, aa.platform
            ORDER BY ad_spend DESC
            LIMIT {$limit}
        ", array_merge([$workspaceId], $adAccountIds, [$from, $to], $filterArgs));

        // Campaign-level attribution as proxy — UTM can't resolve individual ad IDs (§F11 caveat).
        $utmAttrMap = $this->buildUtmAttributionMap($workspaceId, $from, $to, '');

        return array_map(function (object $row) use ($utmAttrMap, $workspaceTargetRoas): array {
            $spend       = (float) $row->ad_spend;
            $impressions = (int)   $row->ad_impressions;
            $clicks      = (int)   $row->ad_clicks;
            $video3s     = (float) $row->video_3s_plays;
            $video15s    = (float) $row->video_15s_plays;
            $outbound    = (float) $row->outbound_clicks_count;

            $creative = is_string($row->creative_data)
                ? json_decode($row->creative_data, true)
                : $row->creative_data;

            $attr              = $utmAttrMap[(int) $row->campaign_id] ?? null;
            $attributedRevenue = $attr !== null ? (float) $attr['revenue'] : null;
            $attributedOrders  = $attr !== null ? (int)   $attr['orders']  : 0;

            $realRoas = ($spend > 0 && $attributedRevenue !== null && $attributedRevenue > 0)
                ? round($attributedRevenue / $spend, 2) : null;

            $targetRoas = $row->target_roas !== null
                ? (float) $row->target_roas
                : $workspaceTargetRoas;

            $motionScore = $this->computeMotionScore(
                video3s:          $video3s,
                video15s:         $video15s,
                outboundClicks:   $outbound,
                impressions:      $impressions,
                clicks:           $clicks,
                attributedOrders: $attributedOrders,
                realRoas:         $realRoas,
                targetRoas:       $targetRoas,
            );

            $retention = null;
            if ($video3s > 0) {
                $retention = [
                    'p25'  => round((float) $row->video_p25_plays  / $video3s * 100, 1),
                    'p50'  => round((float) $row->video_p50_plays  / $video3s * 100, 1),
                    'p75'  => round((float) $row->video_p75_plays  / $video3s * 100, 1),
                    'p100' => round((float) $row->video_p100_plays / $video3s * 100, 1),
                ];
            }

            return [
                'ad_id'             => (int)    $row->ad_id,
                'ad_name'           => (string) ($row->ad_name ?? ''),
                'campaign_id'       => (int)    $row->campaign_id,
                'campaign_name'     => (string) ($row->campaign_name ?? ''),
                'platform'          => (string) $row->platform,
                'status'            => $row->status,
                'effective_status'  => $row->effective_status,
                'thumbnail_url'     => $creative['thumbnail_url'] ?? $creative['image_url'] ?? null,
                'headline'          => $creative['title'] ?? null,
                'spend'             => $spend,
                'impressions'       => $impressions,
                'clicks'            => $clicks,
                'real_roas'         => $realRoas,
                'attributed_orders' => $attributedOrders,
                'thumbstop_pct'     => ($impressions > 0 && $video3s > 0) ? round($video3s / $impressions * 100, 1) : null,
                'hold_rate_pct'     => ($video3s > 0 && $video15s > 0) ? round($video15s / $video3s * 100, 1) : null,
                'outbound_ctr'      => ($impressions > 0 && $outbound > 0) ? round($outbound / $impressions * 100, 2) : null,
                'thumbstop_ctr'     => ($video3s > 0 && $outbound > 0) ? round($outbound / $video3s * 100, 2) : null,
                'cvr'               => ($clicks > 0 && $attributedOrders > 0) ? round($attributedOrders / $clicks * 100, 2) : null,
                'video_retention'   => $retention,
                'motion_score'      => $motionScore,
                'verdict'           => $motionScore !== null ? $motionScore['verdict'] : null,
            ];
        }, $rows);
    }

    // ─── TSX-contract row builders ───────────────────────────────────────────

    /**
     * Build rows shaped exactly for AdRow in Ads/Index.tsx.
     *
     * TSX expects:
     *   id (string), name, platform, status ('active'|'paused'|'archived'),
     *   spend, impressions, ctr, cpc, platform_purchases, store_orders,
     *   real_revenue, roas_real, roas_platform, cac, touchpoints,
     *   thumbnail_url, letter_grade, signal_type, confidence, level.
     *
     * Delegates to the existing level-specific builders then remaps keys.
     *
     * @param  Collection<int, \App\Models\AdAccount>  $adAccounts
     * @return array<int, array<string, mixed>>
     *
     * @see docs/pages/ads.md
     */
    public function buildIndexRows(
        int $workspaceId,
        Collection $adAccounts,
        string $from,
        string $to,
        string $platform,
        string $status,
        string $level,
        array $adAccountIds = [],
        ?int $campaignId = null,
        ?int $adsetId = null,
        ?float $workspaceTargetRoas = null,
    ): array {
        $raw = match ($level) {
            'adset' => $this->computeAdsetRows(
                $workspaceId, $adAccounts, $from, $to,
                $platform, $status, $adAccountIds, $campaignId,
            ),
            'ad' => $this->computeAdRows(
                $workspaceId, $adAccounts, $from, $to,
                $platform, $status, $adAccountIds, $campaignId, $adsetId,
            ),
            default => $this->computeCampaignRows(
                $workspaceId, $adAccounts, $from, $to,
                $platform, $status, $adAccountIds, $workspaceTargetRoas,
            ),
        };

        // Also fetch platform-level conversions (purchases) from ad_insights and
        // store orders from daily_snapshots at campaign granularity for the
        // platform_purchases / store_orders discrepancy row.
        $purchasesMap = $this->buildPlatformPurchasesMap($workspaceId, $adAccountIds, $from, $to, $level);
        $storeOrdersMap = $this->buildStoreOrdersMap($workspaceId, $from, $to, $level);

        // Prior-period window — same length, immediately preceding current $from.
        $periodDays  = max(1, (int) Carbon::parse($from)->diffInDays(Carbon::parse($to)));
        $priorTo     = Carbon::parse($from)->subDay()->toDateString();
        $priorFrom   = Carbon::parse($priorTo)->subDays($periodDays - 1)->toDateString();
        $priorMap    = $this->buildPriorSpendAndRoasMap($workspaceId, $adAccountIds, $priorFrom, $priorTo, $level);

        $effectiveLevel = ($level === 'platform') ? 'campaign' : $level;

        return array_map(function (array $r) use ($purchasesMap, $storeOrdersMap, $priorMap, $effectiveLevel, $workspaceTargetRoas): array {
            $id      = (string) ($r['id'] ?? '');
            $spend   = (float)  ($r['spend'] ?? 0);

            // Normalise status to the three values TSX accepts.
            $rawStatus = strtolower((string) ($r['status'] ?? ''));
            $status = match (true) {
                in_array($rawStatus, ['active', 'enabled', 'delivering'], true) => 'active',
                in_array($rawStatus, ['paused', 'inactive', 'disabled'], true)   => 'paused',
                default                                                           => 'archived',
            };

            $platformPurchases = $purchasesMap[$r['id']] ?? null;
            $storeOrders       = $storeOrdersMap[$r['id']] ?? null;
            $realRevenue       = isset($r['attributed_revenue']) && $r['attributed_revenue'] > 0
                ? (float) $r['attributed_revenue'] : null;

            // real_cpo is CAC here (spend per first-time customer would be ideal but
            // we use spend-per-attributed-order as the page-level proxy).
            $cac = isset($r['real_cpo']) && $r['real_cpo'] > 0 ? (float) $r['real_cpo'] : null;

            // Motion verdict → letter_grade (Scale→A, Watch→B, Iterate→C, Kill→F).
            $verdict = $r['verdict'] ?? null;
            $letterGrade = match ($verdict) {
                'Scale'   => 'A',
                'Watch'   => 'B',
                'Iterate' => 'C',
                'Kill'    => 'F',
                default   => null,
            };

            // signal_type heuristic (no modeled-attribution column yet — see docs/planning/schema.md).
            // deterministic: both store orders and platform purchases present, ratio > 0.7.
            // modeled:       only platform purchases; store has zero orders.
            // mixed:         both present but ratio between 0.3 and 0.7 (partial fingerprint match).
            // null:          insufficient data to classify.
            $storeOrd = (float) ($storeOrders       ?? 0);
            $platPurch = (float) ($platformPurchases ?? 0);
            $signalType = null;
            if ($storeOrd > 0 && $platPurch > 0) {
                $ratio = min($storeOrd, $platPurch) / max($storeOrd, $platPurch);
                $signalType = $ratio >= 0.7 ? 'deterministic' : ($ratio >= 0.3 ? 'mixed' : null);
            } elseif ($platPurch > 0 && $storeOrd === 0.0) {
                $signalType = 'modeled';
            }

            // confidence: false when spend is non-zero but attribution is zero.
            $confidence = !($spend > 0 && ($r['attributed_revenue'] ?? null) === null);

            $entityId  = $r['id'] ?? null;
            $priorData = $entityId !== null ? ($priorMap[$entityId] ?? null) : null;

            return [
                'id'                => $id,
                'name'              => (string) ($r['name'] ?? ''),
                'platform'          => (string) ($r['platform'] ?? ''),
                'status'            => $status,
                'spend'             => $spend,
                'impressions'       => (int) ($r['impressions'] ?? 0),
                'ctr'               => $r['ctr'] ?? null,
                'cpc'               => $r['cpc'] ?? null,
                'platform_purchases'=> $platformPurchases,
                'store_orders'      => $storeOrders,
                'real_revenue'      => $realRevenue,
                'roas_real'         => $r['real_roas'] ?? null,
                'roas_platform'     => $r['platform_roas'] ?? null,
                'cac'               => $cac,
                'touchpoints'       => null, // UTM path not yet implemented at row level
                'thumbnail_url'     => $r['thumbnail_url'] ?? null,
                'letter_grade'      => $letterGrade,
                'signal_type'       => $signalType,
                'confidence'        => $confidence,
                'level'             => $effectiveLevel,
                'spend_prior'       => $priorData !== null ? $priorData['spend_prior'] : null,
                'roas_real_prior'   => $priorData !== null ? $priorData['roas_real_prior'] : null,
            ];
        }, $raw);
    }

    /**
     * Build summary metrics shaped for the `metrics` prop in Ads/Index.tsx:
     *   total_spend, blended_roas, cac, new_customer_roas, platform_purchases,
     *   store_orders, not_tracked, ctr.
     *
     * When $source != 'real', blended_roas uses the lens-specific revenue from
     * daily_snapshots (conservative fallback — see MetricSourceResolver::REVENUE_COLUMN). Per-row
     * attributed_revenue is order-based (Real lens) and stays unchanged; only
     * the summary-level ROAS shifts with the lens.
     *
     * For lens=facebook on a mixed-platform workspace, `blended_roas` shows only
     * Facebook-attributed revenue / total spend. This is intentionally conservative
     * (Google spend deducted but Google revenue not included).
     *
     * @param  array<int, array<string, mixed>>  $indexRows  Already-built buildIndexRows() output.
     * @param  string  $source  Active source lens ('real' default).
     */
    public function buildIndexMetrics(
        array $indexRows,
        int $workspaceId,
        string $from,
        string $to,
        ?float $totalRevenue,
        ?float $unattributedRevenue,
        string $source = 'real',
    ): array {
        $totalSpend      = (float) array_sum(array_column($indexRows, 'spend'));
        $realRevenueSum  = (float) array_sum(array_map(fn ($r) => (float) ($r['real_revenue'] ?? 0), $indexRows));

        // For non-real lenses, override realRevenueSum with the snapshot column total.
        // This gives a workspace-level lens view without touching per-row attribution.
        if ($source !== 'real') {
            $revenueColumn  = $this->sourceResolver->columnFor('revenue', $source);
            $snapRow = \Illuminate\Support\Facades\DB::table('daily_snapshots')
                ->where('workspace_id', $workspaceId)
                ->whereBetween('date', [$from, $to])
                ->selectRaw("COALESCE(SUM({$revenueColumn}), 0) AS v")
                ->first();
            $realRevenueSum = (float) ($snapRow->v ?? 0);
        }
        $impressions     = (int)   array_sum(array_column($indexRows, 'impressions'));
        $totalCtr        = null;

        $clicksArr = [];
        foreach ($indexRows as $r) {
            if (isset($r['impressions']) && $r['impressions'] > 0) {
                // reconstruct click count from CTR
                $clicksArr[] = (float) ($r['ctr'] ?? 0) / 100 * (int) $r['impressions'];
            }
        }
        $totalClicks = array_sum($clicksArr);
        if ($impressions > 0) {
            $totalCtr = round($totalClicks / $impressions * 100, 2);
        }

        $blendedRoas = ($totalSpend > 0 && $realRevenueSum > 0)
            ? round($realRevenueSum / $totalSpend, 2) : null;

        // CAC = total spend / count of first-time customer orders in the period.
        // Using is_first_for_customer flag on orders rather than back-calculating
        // from per-row cac values, which were inaccurate (divide-by-summed-cac).
        $newCustomerCount = (int) DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->where('is_first_for_customer', true)
            ->whereBetween('occurred_at', [$from, $to])
            ->whereIn('status', ['completed', 'processing'])
            ->count();
        $cac = ($totalSpend > 0 && $newCustomerCount > 0)
            ? round($totalSpend / $newCustomerCount, 2) : null;

        // New Customer ROAS = revenue from first-time orders / total ad spend.
        $newCustomerRevenue = (float) DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->where('is_first_for_customer', true)
            ->whereBetween('occurred_at', [$from, $to])
            ->whereIn('status', ['completed', 'processing'])
            ->selectRaw('COALESCE(SUM(total_in_reporting_currency), 0) AS v')
            ->value('v');
        $newCustomerRoas = ($totalSpend > 0 && $newCustomerRevenue > 0)
            ? round($newCustomerRevenue / $totalSpend, 2) : null;

        $platformPurchases = array_sum(array_map(fn ($r) => (float) ($r['platform_purchases'] ?? 0), $indexRows));
        $storeOrders       = array_sum(array_map(fn ($r) => (float) ($r['store_orders']       ?? 0), $indexRows));

        // not_tracked: unattributed revenue as pct of total revenue.
        $notTracked = 0.0;
        if ($totalRevenue !== null && $totalRevenue > 0 && $unattributedRevenue !== null) {
            $notTracked = round(($unattributedRevenue / $totalRevenue) * 100, 1);
        }

        return [
            'total_spend'        => $totalSpend,
            'blended_roas'       => $blendedRoas,
            'cac'                => $cac,
            'new_customer_roas'  => $newCustomerRoas,
            'platform_purchases' => $platformPurchases > 0 ? $platformPurchases : null,
            'store_orders'       => $storeOrders > 0 ? $storeOrders : null,
            'not_tracked'        => $notTracked,
            'ctr'                => $totalCtr,
        ];
    }

    /**
     * Build creative cards shaped exactly for CreativeCard in Ads/Creatives.tsx.
     *
     * Enriched beyond the legacy shape with:
     *   - composite_score (0–100) = ROAS 50% + CTR 25% + inverse-CPA 25%
     *   - triage_bucket ('winners'|'iteration'|'candidates') by ROAS vs target
     *   - platform_cpa (spend / platform_conversions) — on-the-fly, never stored
     *   - prior_roas — same ad in the prior window of equal length
     *   - rank_curr / rank_prev — position within this page by composite score
     *   - momentum_dir ('up'|'down'|'stable'|'new') — rank delta vs prior period
     *
     * TSX expects:
     *   ad_id (int), ad_name, status, effective_status, platform, campaign_id,
     *   campaign_name, adset_id, ad_spend, ad_impressions, ad_clicks,
     *   ctr, cpc, real_roas, platform_cpa, thumbstop_pct, hold_rate_pct,
     *   hook_rate_pct, motion_score (number|null),
     *   motion_verdict ('winner'|'loser'|'neutral'|null),
     *   target_roas, thumbnail_url, body_text, headline, ad_url,
     *   composite_score, triage_bucket, prior_roas, rank_curr, rank_prev,
     *   momentum_dir.
     *
     * @param  int[]  $adAccountIds
     * @return array<int, array<string, mixed>>
     *
     * @see docs/pages/ads.md
     * @see docs/planning/backend.md §6
     */
    public function buildCreativeCards(
        int $workspaceId,
        array $adAccountIds,
        string $from,
        string $to,
        ?int $campaignId,
        ?int $adsetId,
        ?float $workspaceTargetRoas,
        string $sort = 'spend',
        string $status = 'all',
        int $limit = 60,
    ): array {
        $raw = $this->buildCreativeGrid(
            $workspaceId, $adAccountIds, $from, $to,
            $campaignId, $adsetId, $workspaceTargetRoas, $limit,
        );

        // ── Prior-period window ───────────────────────────────────────────────
        // Window length = current range in days. Prior window ends one day before $from.
        $days        = max(1, (int) Carbon::parse($from)->diffInDays(Carbon::parse($to)));
        $priorTo     = Carbon::parse($from)->subDay()->toDateString();
        $priorFrom   = Carbon::parse($priorTo)->subDays($days - 1)->toDateString();

        // Fetch prior-period platform ROAS per ad_id (platform_conversions_value / spend).
        // Used only for momentum arrow — not shown as a primary metric.
        $priorRoasMap = $this->buildPriorPlatformRoasMap($workspaceId, $adAccountIds, $priorFrom, $priorTo);

        // ── UTM attribution map (shared with buildCreativeGrid via $raw) ──────
        // $raw already carries real_roas. We build composite scores from that +
        // the ad_insights columns that buildCreativeGrid exposes.
        //
        // Platform conversions count for CPA is NOT in $raw (buildCreativeGrid
        // joins ads, not ad_insights columns for conversions). Re-use the
        // buildCreativeGrid values and supplement from a lightweight conversions map.
        $platformConvMap = $this->buildPlatformPurchasesMap($workspaceId, $adAccountIds, $from, $to, 'ad');

        // ── First pass: compute per-card metrics + composite score ────────────
        $target = $workspaceTargetRoas ?? 2.0;

        $cards = array_map(function (array $r) use ($workspaceTargetRoas, $target, $priorRoasMap, $platformConvMap): array {
            $impressions = (int)   ($r['impressions'] ?? 0);
            $clicks      = (int)   ($r['clicks']      ?? 0);
            $spend       = (float) ($r['spend']       ?? 0);
            $adId        = (int)   ($r['ad_id']       ?? 0);

            // motion_score is an array in buildCreativeGrid; TSX wants a scalar score
            // (number|null). Compute as average of component grades (A=4…F=0) → 0–100.
            $motionArr      = $r['motion_score'] ?? null;
            $motionScoreNum = null;
            if (is_array($motionArr)) {
                $gradeMap = ['A' => 4, 'B' => 3, 'C' => 2, 'D' => 1, 'F' => 0];
                $vals = array_filter(array_map(
                    fn ($k) => isset($motionArr[$k]) && isset($gradeMap[$motionArr[$k]])
                        ? $gradeMap[$motionArr[$k]] : null,
                    ['hook', 'hold', 'click', 'convert', 'profit'],
                ), fn ($v) => $v !== null);
                if (count($vals) > 0) {
                    // Scale 0-4 average → 0-100
                    $motionScoreNum = round(array_sum($vals) / count($vals) / 4 * 100);
                }
            }

            // motion_verdict: map internal verdict strings to TSX union.
            $internalVerdict = $r['verdict'] ?? null;
            $motionVerdict   = match ($internalVerdict) {
                'Scale'   => 'winner',
                'Kill'    => 'loser',
                'Watch', 'Iterate' => 'neutral',
                default   => null,
            };

            $ctr      = $impressions > 0 ? round($clicks / $impressions * 100, 2) : null;
            $realRoas = $r['real_roas'] ?? null;

            // Platform CPA = spend / platform_conversions (on-the-fly, never stored).
            // Sourced from ad_insights.platform_conversions at level='ad'.
            $platformConvs  = $platformConvMap[$adId] ?? null;
            $platformCpa    = ($platformConvs !== null && $spend > 0 && $platformConvs > 0)
                ? round($spend / $platformConvs, 2) : null;

            // ── Composite score (0–100) ───────────────────────────────────────
            // ROAS component (50 pts): capped at 2× target → max 50.
            // CTR component  (25 pts): capped at 5% → max 25.
            // CPA component  (25 pts): inverse — lower CPA is better.
            //   Uses platform_cpa as proxy when real CPA unavailable.
            //   Reference CPA = spend/1 when no conversions (worst case).
            $roasScore = 0.0;
            if ($realRoas !== null) {
                $roasScore = min(1.0, $realRoas / ($target * 2)) * 50;
            }
            $ctrScore = $ctr !== null ? min(1.0, $ctr / 5.0) * 25 : 0.0;

            $cpaScore = 0.0;
            if ($platformCpa !== null && $platformCpa > 0) {
                // Inverse: $target CPA = $spend/1 baseline. Score=25 at CPA→0; 0 at CPA≥spend.
                $cpaScore = max(0.0, min(1.0, 1 - ($platformCpa / max($spend, 1)))) * 25;
            }

            $compositeScore = round($roasScore + $ctrScore + $cpaScore, 1);

            // ── Triage bucket ─────────────────────────────────────────────────
            // winners:    ROAS >= target AND composite >= 40
            // iteration:  ROAS > 0 but < target, OR composite 20–39
            // candidates: everything else (no revenue, no conversions, or very low composite)
            $triage = 'candidates';
            if ($realRoas !== null && $realRoas >= $target && $compositeScore >= 40) {
                $triage = 'winners';
            } elseif (
                ($realRoas !== null && $realRoas > 0)
                || ($platformConvs !== null && $platformConvs > 0)
                || $compositeScore >= 20
            ) {
                $triage = 'iteration';
            }

            $priorRoas = $priorRoasMap[$adId] ?? null;

            return [
                'ad_id'            => $adId,
                'ad_name'          => (string) ($r['ad_name']      ?? ''),
                'status'           => (string) ($r['status']       ?? ''),
                'effective_status' => $r['effective_status'] ?? null,
                'platform'         => (string) ($r['platform']     ?? ''),
                'campaign_id'      => (int)    ($r['campaign_id']  ?? 0),
                'campaign_name'    => (string) ($r['campaign_name'] ?? ''),
                'adset_id'         => (int)    ($r['adset_id']     ?? 0),
                'ad_spend'         => $spend,
                'ad_impressions'   => $impressions,
                'ad_clicks'        => $clicks,
                'ctr'              => $ctr,
                'cpc'              => $clicks > 0 ? round($spend / $clicks, 4) : null,
                'real_roas'        => $realRoas,
                'platform_cpa'     => $platformCpa,
                'thumbstop_pct'    => $r['thumbstop_pct']  ?? null,
                'hold_rate_pct'    => $r['hold_rate_pct']  ?? null,
                'hook_rate_pct'    => $r['outbound_ctr']   ?? null, // outbound_ctr as hook proxy
                'motion_score'     => $motionScoreNum,
                'motion_verdict'   => $motionVerdict,
                'target_roas'      => $workspaceTargetRoas,
                'thumbnail_url'    => $r['thumbnail_url']  ?? null,
                'body_text'        => null,                          // not in ad_insights; creative_data rarely has it
                'headline'         => $r['headline']       ?? null,
                'ad_url'           => null,                          // destination URL not synced yet
                'composite_score'  => $compositeScore,
                'triage_bucket'    => $triage,
                'prior_roas'       => $priorRoas,
                'rank_curr'        => 0, // filled in second pass
                'rank_prev'        => null,
                'momentum_dir'     => 'new',
            ];
        }, $raw);

        // ── Second pass: assign current ranks by composite score desc ─────────
        usort($cards, fn ($a, $b) => $b['composite_score'] <=> $a['composite_score']);
        foreach ($cards as $i => &$card) {
            $card['rank_curr'] = $i + 1;
        }
        unset($card);

        // ── Prior-period rank by prior composite score approximation ──────────
        // We have prior_roas; assign a prior rank ordering by prior_roas as proxy.
        // Cards that appear in prior data get a numeric rank; new entrants stay 'new'.
        $priorOrder = array_filter($cards, fn ($c) => $c['prior_roas'] !== null);
        usort($priorOrder, fn ($a, $b) => ($b['prior_roas'] ?? 0) <=> ($a['prior_roas'] ?? 0));
        $priorRankByAdId = [];
        foreach ($priorOrder as $i => $c) {
            $priorRankByAdId[$c['ad_id']] = $i + 1;
        }

        // Apply prior rank and momentum direction to each card.
        foreach ($cards as &$card) {
            $prevRank = $priorRankByAdId[$card['ad_id']] ?? null;
            $card['rank_prev'] = $prevRank;

            if ($prevRank === null) {
                $card['momentum_dir'] = 'new';
            } else {
                $delta = $prevRank - $card['rank_curr']; // positive = improved (lower rank # = better)
                $card['momentum_dir'] = match (true) {
                    $delta >  1  => 'up',
                    $delta < -1  => 'down',
                    default      => 'stable',
                };
            }
        }
        unset($card);

        return $cards;
    }

    /**
     * Map ad_id → platform ROAS (platform_conversions_value / spend) for the prior period.
     *
     * Used exclusively for momentum arrow direction; not shown as a primary metric.
     * Filters level='ad', hour IS NULL (daily rows only).
     *
     * @param  int[]  $adAccountIds
     * @return array<int, float>  ad_id → platform_roas
     */
    private function buildPriorPlatformRoasMap(
        int $workspaceId,
        array $adAccountIds,
        string $from,
        string $to,
    ): array {
        if (empty($adAccountIds)) {
            return [];
        }

        $placeholders = $this->placeholders($adAccountIds);

        $rows = DB::select("
            SELECT
                ai.ad_id,
                COALESCE(SUM(ai.spend_in_reporting_currency), 0)          AS prior_spend,
                COALESCE(SUM(ai.platform_conversions_value_in_reporting_currency), 0) AS prior_conv_value
            FROM ad_insights ai
            WHERE ai.workspace_id = ?
              AND ai.ad_account_id IN ({$placeholders})
              AND ai.level = 'ad'
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
            GROUP BY ai.ad_id
        ", array_merge([$workspaceId], $adAccountIds, [$from, $to]));

        $map = [];
        foreach ($rows as $row) {
            $spend = (float) $row->prior_spend;
            $val   = (float) $row->prior_conv_value;
            if ($spend > 0) {
                $map[(int) $row->ad_id] = round($val / $spend, 4);
            }
        }

        return $map;
    }

    // ─── Private query helpers for index rows ────────────────────────────────

    /**
     * Prior-period spend and ROAS per entity (campaign / adset / ad).
     *
     * Uses ad_insights only (same single-level guard as the main query) so there is
     * no risk of cross-level double-counting. ROAS is computed on-the-fly from
     * platform_conversions_value_in_reporting_currency / spend using NULLIF — never
     * stored. Returns null for entities with zero prior spend (no division by zero).
     *
     * @param  int[]  $adAccountIds
     * @return array<int|string, array{spend_prior:float, roas_real_prior:float|null}>
     */
    private function buildPriorSpendAndRoasMap(
        int $workspaceId,
        array $adAccountIds,
        string $from,
        string $to,
        string $level,
    ): array {
        if (empty($adAccountIds)) {
            return [];
        }

        $effectiveLevel = match ($level) {
            'adset'  => 'adset',
            'ad'     => 'ad',
            default  => 'campaign',
        };

        $idCol = match ($effectiveLevel) {
            'adset'  => 'ai.adset_id',
            'ad'     => 'ai.ad_id',
            default  => 'ai.campaign_id',
        };

        $placeholders = $this->placeholders($adAccountIds);

        $rows = DB::select("
            SELECT {$idCol} AS entity_id,
                   COALESCE(SUM(ai.spend_in_reporting_currency), 0)                      AS prior_spend,
                   SUM(ai.platform_conversions_value_in_reporting_currency)               AS prior_conv_value
            FROM ad_insights ai
            WHERE ai.workspace_id = ?
              AND ai.ad_account_id IN ({$placeholders})
              AND ai.level = ?
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
            GROUP BY {$idCol}
        ", array_merge([$workspaceId], $adAccountIds, [$effectiveLevel, $from, $to]));

        $map = [];
        foreach ($rows as $row) {
            $spend     = (float) $row->prior_spend;
            $convValue = $row->prior_conv_value !== null ? (float) $row->prior_conv_value : null;
            $map[$row->entity_id] = [
                'spend_prior'      => $spend,
                'roas_real_prior'  => ($spend > 0 && $convValue !== null)
                    ? round($convValue / $spend, 2)
                    : null,
            ];
        }

        return $map;
    }

    /**
     * Map entity_id → platform_conversions count from ad_insights at the given level.
     *
     * @param  int[]  $adAccountIds
     * @return array<int|string, float>
     */
    private function buildPlatformPurchasesMap(
        int $workspaceId,
        array $adAccountIds,
        string $from,
        string $to,
        string $level,
    ): array {
        if (empty($adAccountIds)) {
            return [];
        }

        $effectiveLevel = match ($level) {
            'adset'  => 'adset',
            'ad'     => 'ad',
            default  => 'campaign',
        };

        $idCol = match ($effectiveLevel) {
            'adset'  => 'ai.adset_id',
            'ad'     => 'ai.ad_id',
            default  => 'ai.campaign_id',
        };

        $placeholders = $this->placeholders($adAccountIds);

        $rows = DB::select("
            SELECT {$idCol} AS entity_id,
                   COALESCE(SUM(platform_conversions), 0) AS purchases
            FROM ad_insights ai
            WHERE ai.workspace_id = ?
              AND ai.ad_account_id IN ({$placeholders})
              AND ai.level = ?
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
            GROUP BY {$idCol}
        ", array_merge([$workspaceId], $adAccountIds, [$effectiveLevel, $from, $to]));

        $map = [];
        foreach ($rows as $row) {
            $map[$row->entity_id] = (float) $row->purchases > 0 ? (float) $row->purchases : null;
        }

        return $map;
    }

    /**
     * Map entity_id → store order count from orders table.
     * Campaign-level only (UTM matching); adset/ad inherit campaign mapping.
     *
     * @return array<int|string, int>
     */
    private function buildStoreOrdersMap(
        int $workspaceId,
        string $from,
        string $to,
        string $level,
    ): array {
        // Store orders can only be resolved to campaign level via UTM.
        $rows = DB::select("
            SELECT c.id AS campaign_id, COUNT(o.id) AS order_count
            FROM orders o
            JOIN campaigns c
              ON  c.workspace_id = o.workspace_id
              AND (
                    o.attribution_last_touch->>'campaign' = c.external_id
                 OR LOWER(o.attribution_last_touch->>'campaign') = LOWER(c.name)
              )
            WHERE o.workspace_id = ?
              AND o.status IN ('completed','processing')
              AND o.occurred_at BETWEEN ? AND ?
            GROUP BY c.id
        ", [$workspaceId, $from . ' 00:00:00', $to . ' 23:59:59']);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->campaign_id] = (int) $row->order_count > 0 ? (int) $row->order_count : null;
        }

        return $map;
    }

    // ─── Pacing tab (§F10) ────────────────────────────────────────────────────

    /**
     * @param  int[]  $adAccountIds
     * @return array<int, array<string, mixed>>
     */
    public function buildPacingData(int $workspaceId, array $adAccountIds, string $from, string $to): array
    {
        if (empty($adAccountIds)) {
            return [];
        }

        $placeholders = $this->placeholders($adAccountIds);

        $dailyRows = DB::select("
            SELECT
                c.id AS campaign_id, c.name AS campaign_name,
                c.daily_budget, c.lifetime_budget, c.budget_type,
                ai.date::text AS date,
                COALESCE(SUM(ai.spend_in_reporting_currency), 0) AS daily_spend
            FROM ad_insights ai
            JOIN campaigns c ON c.id = ai.campaign_id
            WHERE ai.workspace_id = ?
              AND ai.ad_account_id IN ({$placeholders})
              AND ai.level = 'campaign'
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
            GROUP BY c.id, c.name, c.daily_budget, c.lifetime_budget, c.budget_type, ai.date
            ORDER BY c.id, ai.date
        ", array_merge([$workspaceId], $adAccountIds, [$from, $to]));

        $daysInPeriod = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
        $daysElapsed  = min(Carbon::parse($from)->diffInDays(Carbon::today()) + 1, $daysInPeriod);

        $byCampaign = [];
        foreach ($dailyRows as $row) {
            $id = (int) $row->campaign_id;
            if (! isset($byCampaign[$id])) {
                $byCampaign[$id] = [
                    'campaign_id'     => $id,
                    'campaign_name'   => $row->campaign_name,
                    'daily_budget'    => $row->daily_budget !== null ? (float) $row->daily_budget : null,
                    'lifetime_budget' => $row->lifetime_budget !== null ? (float) $row->lifetime_budget : null,
                    'budget_type'     => $row->budget_type,
                    'daily_points'    => [],
                    'total_spend'     => 0.0,
                ];
            }
            $byCampaign[$id]['daily_points'][] = [
                'date'  => $row->date,
                'spend' => (float) $row->daily_spend,
            ];
            $byCampaign[$id]['total_spend'] += (float) $row->daily_spend;
        }

        return array_values(array_map(function (array $c) use ($daysInPeriod, $daysElapsed): array {
            $budgetForPeriod = match ($c['budget_type']) {
                'daily'    => $c['daily_budget'] !== null ? $c['daily_budget'] * $daysInPeriod : null,
                'lifetime' => $c['lifetime_budget'],
                default    => null,
            };

            $velocity = null;
            $status   = 'no_budget';
            if ($budgetForPeriod !== null && $budgetForPeriod > 0 && $daysElapsed > 0) {
                $expectedPace = $daysElapsed / $daysInPeriod;
                $actualPace   = $c['total_spend'] / $budgetForPeriod;
                $velocity     = round($actualPace / $expectedPace, 3);
                $status = match (true) {
                    $velocity > 1.05 => 'over',
                    $velocity < 0.85 => 'under',
                    default          => 'on_pace',
                };
            }

            return [
                ...$c,
                'budget_for_period' => $budgetForPeriod,
                'velocity'          => $velocity,
                'pacing_status'     => $status,
            ];
        }, $byCampaign));
    }

    // ─── Winners / Losers (§7 W/L classifier) ────────────────────────────────

    /**
     * Tag each row with wl_tag ('winner' | 'loser' | null) then optionally filter.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $params  Validated request params.
     * @param  int[]  $adAccountIds
     * @return array{rows:array<int,array<string,mixed>>,total_count:int,active_classifier:string,peer_avg_roas:float|null}
     */
    public function applyWinnersLosers(array $rows, array $params, Workspace $workspace, array $adAccountIds): array
    {
        // target_roas moved to workspace_targets table in L2 rebuild.
        $roasTargetVal = \App\Models\WorkspaceTarget::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('metric', 'roas')
            ->where('status', 'active')
            ->value('target_value_reporting');
        $workspaceTargetRoas = $roasTargetVal !== null ? (float) $roasTargetVal : null;
        $hasTarget           = $workspaceTargetRoas !== null;

        $effectiveClassifier = $params['classifier'] ?? ($hasTarget ? 'target' : 'peer');

        $rowsWithRoas = array_filter($rows, fn (array $r) => ($r['real_roas'] ?? null) !== null && ($r['spend'] ?? 0) > 0);
        $peerAvgRoas  = count($rowsWithRoas) > 0
            ? array_sum(array_column($rowsWithRoas, 'real_roas')) / count($rowsWithRoas)
            : null;

        $prevAttrMap  = [];
        $prevSpendMap = [];
        if ($effectiveClassifier === 'period') {
            $periodDays  = Carbon::parse($params['from'])->diffInDays(Carbon::parse($params['to'])) + 1;
            $prevTo      = Carbon::parse($params['from'])->subDay()->toDateString();
            $prevFrom    = Carbon::parse($prevTo)->subDays($periodDays - 1)->toDateString();
            $utmPlatform = $params['platform'] === 'all' ? '' : $params['platform'];
            $workspaceId = $workspace->id;
            $prevAttrMap  = $this->buildUtmAttributionMap($workspaceId, $prevFrom, $prevTo, $utmPlatform);
            $prevSpendMap = $this->buildCampaignSpendMap($workspaceId, $adAccountIds, $prevFrom, $prevTo);
        }

        $tagged = array_map(function (array $r) use (
            $effectiveClassifier, $workspaceTargetRoas, $peerAvgRoas, $prevAttrMap, $prevSpendMap,
        ): array {
            if (($r['spend'] ?? 0) <= 0) {
                return array_merge($r, ['wl_tag' => null]);
            }

            $threshold = match ($effectiveClassifier) {
                'target' => $r['target_roas'] ?? $workspaceTargetRoas,
                default  => null,
            };

            $tag = match ($effectiveClassifier) {
                'target' => ($threshold !== null && ($r['real_roas'] ?? null) !== null)
                    ? ($r['real_roas'] >= $threshold ? 'winner' : 'loser')
                    : null,
                'peer' => ($peerAvgRoas !== null && ($r['real_roas'] ?? null) !== null)
                    ? ($r['real_roas'] >= $peerAvgRoas ? 'winner' : 'loser')
                    : null,
                'period' => $this->wlTagByPeriodRow($r, $prevAttrMap, $prevSpendMap),
                default  => null,
            };

            return array_merge($r, ['wl_tag' => $tag]);
        }, $rows);

        $totalCount = count($tagged);

        if (($params['filter'] ?? 'all') !== 'all') {
            $filterTag = rtrim($params['filter'], 's');
            $tagged    = array_values(array_filter($tagged, fn (array $r) => ($r['wl_tag'] ?? null) === $filterTag));
        }

        return [
            'rows'              => $tagged,
            'total_count'       => $totalCount,
            'active_classifier' => $effectiveClassifier,
            'peer_avg_roas'     => $peerAvgRoas !== null ? round($peerAvgRoas, 2) : null,
        ];
    }

    // ─── Metrics + chart + platform breakdown ────────────────────────────────

    /** Derive summary metrics from already-computed rows (same set as the table). */
    public function metricsFromRows(array $rows, int $workspaceId, string $from, string $to): array
    {
        $spend       = (float) array_sum(array_column($rows, 'spend'));
        $attrRevenue = (float) array_sum(array_map(fn ($r) => (float) ($r['attributed_revenue'] ?? 0), $rows));
        $attrOrders  = (int)   array_sum(array_map(fn ($r) => (int)   ($r['attributed_orders']  ?? 0), $rows));
        $impressions = (int)   array_sum(array_column($rows, 'impressions'));
        $clicks      = (int)   array_sum(array_column($rows, 'clicks'));

        $snapRow = DB::table('daily_snapshots')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('COALESCE(SUM(revenue), 0) AS total_revenue, COALESCE(SUM(orders_count), 0) AS total_orders')
            ->first();

        $revenue = (float) ($snapRow->total_revenue ?? 0);
        $orders  = (int)   ($snapRow->total_orders  ?? 0);

        return [
            'roas'               => ($spend > 0 && $revenue > 0)     ? round($revenue / $spend, 2)      : null,
            'cpo'                => ($spend > 0 && $orders > 0)      ? round($spend / $orders, 2)       : null,
            'spend'              => $spend > 0 ? $spend : null,
            'revenue'            => $revenue > 0 ? $revenue : null,
            'attributed_revenue' => $attrRevenue > 0 ? $attrRevenue : null,
            'attributed_orders'  => $attrOrders,
            'real_roas'          => ($spend > 0 && $attrRevenue > 0) ? round($attrRevenue / $spend, 2) : null,
            'real_cpo'           => ($spend > 0 && $attrOrders > 0)  ? round($spend / $attrOrders, 2)  : null,
            'impressions'        => $impressions,
            'clicks'             => $clicks,
            'ctr'                => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : null,
            'cpc'                => $clicks > 0 ? round($spend / $clicks, 4) : null,
        ];
    }

    /**
     * Aggregate metrics queried directly — used for compare-period where rows
     * are not already computed.
     *
     * @param  int[]  $adAccountIds
     */
    public function computeMetrics(
        int $workspaceId,
        array $adAccountIds,
        string $from,
        string $to,
        string $platform = 'all',
    ): array {
        if (empty($adAccountIds)) {
            return $this->emptyMetrics();
        }

        $channelFilter = $this->channelFilter($platform, 'orders.');

        $snapRow = DB::table('daily_snapshots')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('COALESCE(SUM(revenue), 0) AS total_revenue, COALESCE(SUM(orders_count), 0) AS total_orders')
            ->first();

        $revenue = (float) ($snapRow->total_revenue ?? 0);
        $orders  = (int)   ($snapRow->total_orders  ?? 0);

        $placeholders = $this->placeholders($adAccountIds);
        $adRow = DB::selectOne("
            SELECT COALESCE(SUM(spend_in_reporting_currency), 0) AS total_spend,
                   COALESCE(SUM(impressions), 0)                 AS total_impressions,
                   COALESCE(SUM(clicks), 0)                      AS total_clicks
            FROM ad_insights
            WHERE workspace_id = ?
              AND ad_account_id IN ({$placeholders})
              AND level = 'campaign'
              AND hour IS NULL
              AND date BETWEEN ? AND ?
        ", array_merge([$workspaceId], $adAccountIds, [$from, $to]));

        $spend       = (float) ($adRow->total_spend       ?? 0);
        $impressions = (int)   ($adRow->total_impressions ?? 0);
        $clicks      = (int)   ($adRow->total_clicks      ?? 0);

        $attrRow = DB::selectOne("
            SELECT COALESCE(SUM(total_in_reporting_currency), 0) AS attributed_revenue,
                   COUNT(id)                                     AS attributed_orders
            FROM orders
            WHERE workspace_id = ?
              AND status IN ('completed','processing')
              AND total_in_reporting_currency IS NOT NULL
              AND attribution_source IN ('pys','wc_native')
              AND attribution_last_touch->>'campaign' IS NOT NULL
              AND attribution_last_touch->>'campaign' <> ''
              {$channelFilter}
              AND occurred_at BETWEEN ? AND ?
        ", [$workspaceId, $from . ' 00:00:00', $to . ' 23:59:59']);

        $attrRevenue = (float) ($attrRow->attributed_revenue ?? 0);
        $attrOrders  = (int)   ($attrRow->attributed_orders  ?? 0);

        return [
            'roas'               => ($spend > 0 && $revenue > 0)     ? round($revenue / $spend, 2)       : null,
            'cpo'                => ($spend > 0 && $orders > 0)      ? round($spend / $orders, 2)        : null,
            'spend'              => $spend > 0 ? $spend : null,
            'revenue'            => $revenue > 0 ? $revenue : null,
            'attributed_revenue' => $attrRevenue > 0 ? $attrRevenue : null,
            'attributed_orders'  => $attrOrders,
            'real_roas'          => ($spend > 0 && $attrRevenue > 0) ? round($attrRevenue / $spend, 2)   : null,
            'real_cpo'           => ($spend > 0 && $attrOrders > 0)  ? round($spend / $attrOrders, 2)    : null,
            'impressions'        => $impressions,
            'clicks'             => $clicks,
            'ctr'                => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : null,
            'cpc'                => $clicks > 0 ? round($spend / $clicks, 4) : null,
        ];
    }

    public function computePlatformBreakdown(int $workspaceId, string $from, string $to): array
    {
        $rows = DB::select("
            SELECT aa.platform,
                   COALESCE(SUM(ai.spend_in_reporting_currency), 0) AS total_spend,
                   COALESCE(SUM(ai.impressions), 0)                 AS total_impressions,
                   COALESCE(SUM(ai.clicks), 0)                      AS total_clicks
            FROM ad_insights ai
            JOIN ad_accounts aa ON aa.id = ai.ad_account_id
            WHERE ai.workspace_id = ?
              AND ai.level = 'campaign'
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
            GROUP BY aa.platform
        ", [$workspaceId, $from, $to]);

        $out = [];
        foreach ($rows as $row) {
            $spend       = (float) $row->total_spend;
            $impressions = (int)   $row->total_impressions;
            $clicks      = (int)   $row->total_clicks;
            $out[$row->platform] = [
                'spend'       => $spend > 0 ? $spend : null,
                'impressions' => $impressions,
                'clicks'      => $clicks,
                'ctr'         => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : null,
            ];
        }

        return $out;
    }

    /** @param int[] $adAccountIds */
    public function buildSpendChart(
        int $workspaceId,
        array $adAccountIds,
        string $from,
        string $to,
        string $granularity,
    ): array {
        if (empty($adAccountIds)) {
            return [];
        }

        $dateExpr = $granularity === 'weekly'
            ? "DATE_TRUNC('week', ai.date)::date::text"
            : 'ai.date::text';
        $placeholders = $this->placeholders($adAccountIds);

        $rows = DB::select("
            SELECT {$dateExpr} AS date, aa.platform,
                   COALESCE(SUM(ai.spend_in_reporting_currency), 0) AS spend
            FROM ad_insights ai
            JOIN ad_accounts aa ON aa.id = ai.ad_account_id
            WHERE ai.workspace_id = ?
              AND ai.ad_account_id IN ({$placeholders})
              AND ai.level = 'campaign'
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
            GROUP BY {$dateExpr}, aa.platform
            ORDER BY date
        ", array_merge([$workspaceId], $adAccountIds, [$from, $to]));

        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row->date][$row->platform] = (float) $row->spend;
        }

        return array_values(array_map(
            fn (string $date, array $platforms) => [
                'date'     => $date,
                'facebook' => $platforms['facebook'] ?? 0,
                'google'   => $platforms['google']   ?? 0,
            ],
            array_keys($byDate),
            $byDate,
        ));
    }

    // ─── Revenue context ─────────────────────────────────────────────────────

    /** @return array{float|null, float|null} [total_revenue, unattributed_revenue] */
    public function computeRevenueContext(int $workspaceId, bool $hasStore, string $from, string $to): array
    {
        if (! $hasStore) {
            return [null, null];
        }

        $snap = DB::table('daily_snapshots')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('COALESCE(SUM(revenue), 0) AS total_revenue')
            ->first();

        $totalRevenue = (float) ($snap->total_revenue ?? 0);

        $attributed = $this->attribution->getAttributedRevenue(
            $workspaceId,
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay(),
        );

        $unattributed = $this->attribution->getUnattributedRevenue(
            $totalRevenue,
            $attributed['total_tagged'],
        );

        return [
            $totalRevenue > 0 ? round($totalRevenue, 2) : null,
            $unattributed > 0 ? round($unattributed, 2) : null,
        ];
    }

    // ─── Spend vs Revenue chart ──────────────────────────────────────────────

    /**
     * Build daily spend + revenue series for the "Spend vs Real Revenue over time" chart.
     *
     * Spend: ad_insights filtered to level = 'campaign' to avoid double-counting.
     * Revenue: daily_snapshots revenue_real_attributed (the Real lens value).
     *
     * Returns two parallel arrays keyed 'spend' and 'revenue', each an array of
     * {date: 'YYYY-MM-DD', value: float} points ordered ascending by date.
     *
     * @return array{spend: list<array{date:string,value:float}>, revenue: list<array{date:string,value:float}>}
     *
     * @see docs/pages/ads.md
     */
    public function buildSpendRevenueChart(int $workspaceId, string $from, string $to): array
    {
        $spendRows = DB::select("
            SELECT date::text AS date,
                   COALESCE(SUM(spend_in_reporting_currency), 0) AS spend
            FROM ad_insights
            WHERE workspace_id = ?
              AND level = 'campaign'
              AND date BETWEEN ? AND ?
            GROUP BY date
            ORDER BY date
        ", [$workspaceId, $from, $to]);

        $revenueRows = DB::select("
            SELECT date::text AS date,
                   COALESCE(SUM(revenue_real_attributed), 0) AS revenue
            FROM daily_snapshots
            WHERE workspace_id = ?
              AND date BETWEEN ? AND ?
            GROUP BY date
            ORDER BY date
        ", [$workspaceId, $from, $to]);

        return [
            'spend'   => array_values(array_map(
                fn ($r) => ['date' => $r->date, 'value' => (float) $r->spend],
                $spendRows,
            )),
            'revenue' => array_values(array_map(
                fn ($r) => ['date' => $r->date, 'value' => (float) $r->revenue],
                $revenueRows,
            )),
        ];
    }

    // ─── Helpers (public) ────────────────────────────────────────────────────

    public function resolveName(int $workspaceId, string $table, ?int $id): ?string
    {
        if ($id === null) {
            return null;
        }
        $row = DB::selectOne("SELECT name FROM {$table} WHERE id = ? AND workspace_id = ?", [$id, $workspaceId]);

        return $row?->name;
    }

    public function emptyMetrics(): array
    {
        return [
            'roas'               => null,
            'cpo'                => null,
            'spend'              => null,
            'revenue'            => null,
            'attributed_revenue' => null,
            'attributed_orders'  => 0,
            'real_roas'          => null,
            'real_cpo'           => null,
            'impressions'        => 0,
            'clicks'             => 0,
            'ctr'                => null,
            'cpc'                => null,
        ];
    }

    // ─── Private maps + helpers ──────────────────────────────────────────────

    /**
     * Memoized wrapper around buildCombinedAttributionMaps().
     *
     * All three compute*Rows methods call this so the heavy CTE query runs at most
     * once per (workspaceId, from, to, platform) combination per request, even when
     * the controller renders adset or ad rows without first rendering campaign rows.
     *
     * @return array{
     *   utm: array<int, array{revenue:float,orders:int}>,
     *   firstOrder: array<int, array{revenue:float,orders:int}>,
     *   day30: array<int, array{revenue:float}>,
     * }
     */
    private function combinedAttributionMaps(
        int $workspaceId,
        string $from,
        string $to,
        string $platform,
    ): array {
        $key = "{$workspaceId}:{$from}:{$to}:{$platform}";
        if (! array_key_exists($key, $this->combinedAttrMemo)) {
            $this->combinedAttrMemo[$key] = $this->buildCombinedAttributionMaps(
                $workspaceId, $from, $to, $platform,
            );
        }

        return $this->combinedAttrMemo[$key];
    }

    /**
     * Build all three attribution maps in a single CTE query and return them as a tuple.
     *
     * Replaces three separate round-trips (buildUtmAttributionMap, buildFirstOrderRoasMap,
     * buildDay30RoasMap) with one query that uses CTEs. Called only from computeCampaignRows
     * where all three maps are needed.
     *
     * Day-30 CTE is still a self-join on orders (same semantic as buildDay30RoasMap), but it
     * shares the acquisition CTE with the first-order map to avoid scanning orders twice.
     *
     * @return array{
     *   utm: array<int, array{revenue:float,orders:int}>,
     *   firstOrder: array<int, array{revenue:float,orders:int}>,
     *   day30: array<int, array{revenue:float}>,
     * }
     */
    private function buildCombinedAttributionMaps(
        int $workspaceId,
        string $from,
        string $to,
        string $platform,
    ): array {
        $channelFilter = $this->channelFilter($platform, 'o.');

        // Single query: three result sets in one pass via UNION ALL + type discriminator.
        // Avoids three separate orders-table scans.
        $rows = DB::select("
            WITH
            base_orders AS (
                SELECT o.id, o.customer_email_hash, o.occurred_at,
                       o.total_in_reporting_currency, o.is_first_for_customer,
                       c.id AS campaign_id
                FROM orders o
                JOIN campaigns c
                  ON  c.workspace_id = o.workspace_id
                  AND (
                        o.attribution_last_touch->>'campaign' = c.external_id
                     OR LOWER(o.attribution_last_touch->>'campaign') = LOWER(c.name)
                     OR (
                          jsonb_array_length(COALESCE(c.previous_names, '[]'::jsonb)) > 0
                          AND LOWER(o.attribution_last_touch->>'campaign') IN (
                                SELECT LOWER(pn.value)
                                FROM jsonb_array_elements_text(COALESCE(c.previous_names, '[]'::jsonb)) AS pn(value)
                              )
                        )
                  )
                WHERE o.workspace_id = ?
                  AND o.status IN ('completed','processing')
                  AND o.total_in_reporting_currency IS NOT NULL
                  AND o.attribution_source IN ('pys','wc_native')
                  AND o.attribution_last_touch->>'campaign' IS NOT NULL
                  AND o.attribution_last_touch->>'campaign' <> ''
                  AND o.occurred_at BETWEEN ? AND ?
                  {$channelFilter}
            ),
            first_orders AS (
                SELECT * FROM base_orders WHERE is_first_for_customer = true AND customer_email_hash IS NOT NULL
            ),
            utm_agg AS (
                SELECT 'utm' AS map_type, campaign_id,
                       SUM(total_in_reporting_currency) AS revenue,
                       COUNT(id) AS orders,
                       NULL::numeric AS day30_revenue
                FROM base_orders
                GROUP BY campaign_id
            ),
            fo_agg AS (
                SELECT 'first_order' AS map_type, campaign_id,
                       SUM(total_in_reporting_currency) AS revenue,
                       COUNT(id) AS orders,
                       NULL::numeric AS day30_revenue
                FROM first_orders
                GROUP BY campaign_id
            ),
            day30_agg AS (
                SELECT 'd30' AS map_type, fo.campaign_id,
                       NULL::numeric AS revenue,
                       NULL::bigint AS orders,
                       SUM(o2.total_in_reporting_currency) AS day30_revenue
                FROM first_orders fo
                JOIN orders o2 ON o2.customer_email_hash = fo.customer_email_hash
                  AND o2.workspace_id = ?
                  AND o2.status IN ('completed','processing')
                  AND o2.total_in_reporting_currency IS NOT NULL
                  AND o2.occurred_at BETWEEN fo.occurred_at AND (fo.occurred_at + INTERVAL '30 days')
                GROUP BY fo.campaign_id
            )
            SELECT * FROM utm_agg
            UNION ALL SELECT * FROM fo_agg
            UNION ALL SELECT * FROM day30_agg
        ", [$workspaceId, $from . ' 00:00:00', $to . ' 23:59:59', $workspaceId]);

        $utm       = [];
        $firstOrder = [];
        $day30     = [];

        foreach ($rows as $row) {
            $cid = (int) $row->campaign_id;
            switch ($row->map_type) {
                case 'utm':
                    $utm[$cid] = ['revenue' => (float) $row->revenue, 'orders' => (int) $row->orders];
                    break;
                case 'first_order':
                    $firstOrder[$cid] = ['revenue' => (float) $row->revenue, 'orders' => (int) $row->orders];
                    break;
                case 'd30':
                    $day30[$cid] = ['revenue' => (float) $row->day30_revenue];
                    break;
            }
        }

        return ['utm' => $utm, 'firstOrder' => $firstOrder, 'day30' => $day30];
    }

    /**
     * Map campaign_id → {revenue, orders} from attribution-tagged orders.
     *
     * @return array<int, array{revenue:float,orders:int}>
     */
    private function buildUtmAttributionMap(int $workspaceId, string $from, string $to, string $platform): array
    {
        $channelFilter = $this->channelFilter($platform, 'o.');

        $rows = DB::select("
            SELECT
                c.id                               AS campaign_id,
                SUM(o.total_in_reporting_currency) AS attributed_revenue,
                COUNT(o.id)                        AS attributed_orders
            FROM orders o
            JOIN campaigns c
              ON  c.workspace_id = o.workspace_id
              AND (
                    o.attribution_last_touch->>'campaign' = c.external_id
                 OR LOWER(o.attribution_last_touch->>'campaign') = LOWER(c.name)
                 OR (
                      jsonb_array_length(COALESCE(c.previous_names, '[]'::jsonb)) > 0
                      AND LOWER(o.attribution_last_touch->>'campaign') IN (
                            SELECT LOWER(pn.value)
                            FROM jsonb_array_elements_text(COALESCE(c.previous_names, '[]'::jsonb)) AS pn(value)
                          )
                    )
              )
            WHERE o.workspace_id = ?
              AND o.status IN ('completed','processing')
              AND o.total_in_reporting_currency IS NOT NULL
              AND o.attribution_source IN ('pys','wc_native')
              AND o.attribution_last_touch->>'campaign' IS NOT NULL
              AND o.attribution_last_touch->>'campaign' <> ''
              AND o.occurred_at BETWEEN ? AND ?
              {$channelFilter}
            GROUP BY c.id
        ", [$workspaceId, $from . ' 00:00:00', $to . ' 23:59:59']);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->campaign_id] = [
                'revenue' => (float) $row->attributed_revenue,
                'orders'  => (int)   $row->attributed_orders,
            ];
        }

        return $map;
    }

    /** @return array<int, array{revenue:float,orders:int}> */
    private function buildFirstOrderRoasMap(int $workspaceId, string $from, string $to, string $platform): array
    {
        $channelFilter = $this->channelFilter($platform, 'o.');

        $rows = DB::select("
            SELECT
                c.id                               AS campaign_id,
                SUM(o.total_in_reporting_currency) AS first_order_revenue,
                COUNT(o.id)                        AS first_order_count
            FROM orders o
            JOIN campaigns c
              ON  c.workspace_id = o.workspace_id
              AND (
                    o.attribution_last_touch->>'campaign' = c.external_id
                 OR LOWER(o.attribution_last_touch->>'campaign') = LOWER(c.name)
                 OR (
                      jsonb_array_length(COALESCE(c.previous_names, '[]'::jsonb)) > 0
                      AND LOWER(o.attribution_last_touch->>'campaign') IN (
                            SELECT LOWER(pn.value)
                            FROM jsonb_array_elements_text(COALESCE(c.previous_names, '[]'::jsonb)) AS pn(value)
                          )
                    )
              )
            WHERE o.workspace_id = ?
              AND o.is_first_for_customer = true
              AND o.status IN ('completed','processing')
              AND o.total_in_reporting_currency IS NOT NULL
              AND o.attribution_source IN ('pys','wc_native')
              AND o.occurred_at BETWEEN ? AND ?
              {$channelFilter}
            GROUP BY c.id
        ", [$workspaceId, $from . ' 00:00:00', $to . ' 23:59:59']);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->campaign_id] = [
                'revenue' => (float) $row->first_order_revenue,
                'orders'  => (int)   $row->first_order_count,
            ];
        }

        return $map;
    }

    /** @return array<int, array{revenue:float}> */
    private function buildDay30RoasMap(int $workspaceId, string $from, string $to, string $platform): array
    {
        $channelFilter = $this->channelFilter($platform, 'o.');

        $rows = DB::select("
            WITH acquisition AS (
                SELECT
                    c.id                   AS campaign_id,
                    o.customer_email_hash,
                    o.occurred_at          AS acquired_at
                FROM orders o
                JOIN campaigns c
                  ON  c.workspace_id = o.workspace_id
                  AND (
                        o.attribution_last_touch->>'campaign' = c.external_id
                     OR LOWER(o.attribution_last_touch->>'campaign') = LOWER(c.name)
                  )
                WHERE o.workspace_id = ?
                  AND o.is_first_for_customer = true
                  AND o.status IN ('completed','processing')
                  AND o.total_in_reporting_currency IS NOT NULL
                  AND o.attribution_source IN ('pys','wc_native')
                  AND o.customer_email_hash IS NOT NULL
                  AND o.occurred_at BETWEEN ? AND ?
                  {$channelFilter}
            )
            SELECT
                aq.campaign_id,
                SUM(o2.total_in_reporting_currency) AS day30_revenue
            FROM acquisition aq
            JOIN orders o2 ON o2.customer_email_hash = aq.customer_email_hash
              AND o2.workspace_id = ?
              AND o2.status IN ('completed','processing')
              AND o2.total_in_reporting_currency IS NOT NULL
              AND o2.occurred_at BETWEEN aq.acquired_at
                                     AND (aq.acquired_at + INTERVAL '30 days')
            GROUP BY aq.campaign_id
        ", [$workspaceId, $from . ' 00:00:00', $to . ' 23:59:59', $workspaceId]);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->campaign_id] = ['revenue' => (float) $row->day30_revenue];
        }

        return $map;
    }

    /** @param int[] $adAccountIds  @return array<int, float> campaign_id => spend */
    private function buildCampaignSpendMap(int $workspaceId, array $adAccountIds, string $from, string $to): array
    {
        if (empty($adAccountIds)) {
            return [];
        }

        $placeholders = $this->placeholders($adAccountIds);

        $rows = DB::select("
            SELECT c.id AS campaign_id, COALESCE(SUM(ai.spend_in_reporting_currency), 0) AS total_spend
            FROM ad_insights ai
            JOIN campaigns c ON c.id = ai.campaign_id
            WHERE ai.workspace_id = ?
              AND ai.ad_account_id IN ({$placeholders})
              AND ai.level = 'campaign'
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
            GROUP BY c.id
        ", array_merge([$workspaceId], $adAccountIds, [$from, $to]));

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->campaign_id] = (float) $row->total_spend;
        }

        return $map;
    }

    private function wlTagByPeriodRow(array $row, array $prevAttrMap, array $prevSpendMap): ?string
    {
        $prevSpend = $prevSpendMap[$row['id']] ?? 0.0;
        $prevAttr  = $prevAttrMap[$row['id']]  ?? null;
        if ($prevAttr === null || $prevSpend <= 0 || ($row['real_roas'] ?? null) === null) {
            return null;
        }
        $prevRoas = (float) $prevAttr['revenue'] / $prevSpend;

        return $row['real_roas'] > $prevRoas ? 'winner' : 'loser';
    }

    // ─── Motion Score (§F11) ─────────────────────────────────────────────────

    /**
     * Compute the 5-component Motion Score + verdict per §F11
     * (Hook → Hold → Click → Convert → Profit).
     *
     * @return array{hook:string|null,hold:string|null,click:string|null,convert:string|null,profit:string|null,verdict:string|null}|null
     */
    private function computeMotionScore(
        float $video3s,
        float $video15s,
        float $outboundClicks,
        int $impressions,
        int $clicks,
        int $attributedOrders,
        ?float $realRoas,
        ?float $targetRoas,
    ): ?array {
        if ($impressions === 0) {
            return null;
        }

        $hasVideo = $video3s > 0;

        // Hook: Thumbstop Ratio = 3s_plays / impressions. A ≥ 0.30, F < 0.15
        $hookScore = $hasVideo
            ? $this->gradeLinear($video3s / $impressions, 0.15, 0.30)
            : null;

        // Hold: Hold Rate = 15s_plays / 3s_plays. A ≥ 0.40, F < 0.20
        $holdScore = ($hasVideo && $video15s > 0)
            ? $this->gradeLinear($video15s / $video3s, 0.20, 0.40)
            : null;

        // Click: Thumbstop CTR (video) or Outbound CTR (static fallback).
        $clickScore = null;
        if ($hasVideo && $outboundClicks > 0) {
            $clickScore = $this->gradeLinear($outboundClicks / $video3s, 0.01, 0.04);
        } elseif ($impressions > 0 && $outboundClicks > 0) {
            $clickScore = $this->gradeLinear($outboundClicks / $impressions, 0.005, 0.015);
        }

        // Convert: CVR = store_orders / clicks. A ≥ 0.03, F < 0.005
        $convertScore = ($clicks > 0 && $attributedOrders > 0)
            ? $this->gradeLinear($attributedOrders / $clicks, 0.005, 0.03)
            : null;

        // Profit: Real ROAS vs target. A ≥ target×1.2, F < target×0.5
        $profitScore = null;
        if ($realRoas !== null && $targetRoas !== null && $targetRoas > 0) {
            $profitScore = $this->gradeLinear($realRoas, $targetRoas * 0.5, $targetRoas * 1.2);
        }

        if ($hookScore === null && $holdScore === null && $clickScore === null
            && $convertScore === null && $profitScore === null) {
            return null;
        }

        $grades   = ['F', 'D', 'C', 'B', 'A'];
        $toLetter = fn (?float $s): ?string => $s === null ? null : $grades[min(4, (int) round($s))];

        return [
            'hook'    => $toLetter($hookScore),
            'hold'    => $toLetter($holdScore),
            'click'   => $toLetter($clickScore),
            'convert' => $toLetter($convertScore),
            'profit'  => $toLetter($profitScore),
            'verdict' => $this->computeVerdict($profitScore, $hookScore, $holdScore, $clickScore, $convertScore),
        ];
    }

    /** Linear interpolation between F-threshold (→ 0) and A-threshold (→ 4). */
    private function gradeLinear(float $value, float $fThreshold, float $aThreshold): float
    {
        if ($value >= $aThreshold) {
            return 4.0;
        }
        if ($value < $fThreshold) {
            return 0.0;
        }

        return ($value - $fThreshold) / ($aThreshold - $fThreshold) * 4.0;
    }

    private function computeVerdict(
        ?float $profitScore,
        ?float $hookScore,
        ?float $holdScore,
        ?float $clickScore,
        ?float $convertScore,
    ): ?string {
        if ($profitScore === null) {
            return null;
        }

        if ($profitScore < 0.5) {
            return 'Kill';
        }
        if ($hookScore !== null && $holdScore !== null && $hookScore < 0.5 && $holdScore < 0.5) {
            return 'Kill';
        }

        if ($profitScore >= 3.5) {
            $hookHoldScores = array_filter([$hookScore, $holdScore], fn ($s) => $s !== null);
            $hookHoldAvg    = count($hookHoldScores) > 0
                ? array_sum($hookHoldScores) / count($hookHoldScores)
                : null;
            if ($hookHoldAvg === null || $hookHoldAvg >= 2.5) {
                return 'Scale';
            }
        }

        $hookOrHoldWeak = ($hookScore !== null && $hookScore < 1.5) || ($holdScore !== null && $holdScore < 1.5);
        if ($hookOrHoldWeak) {
            $cc = array_filter([$clickScore, $convertScore], fn ($s) => $s !== null);
            if (count($cc) > 0 && array_sum($cc) / count($cc) >= 1.5) {
                return 'Iterate';
            }
        }

        return 'Watch';
    }

    // ─── Private shared SQL helpers ──────────────────────────────────────────

    /**
     * @param  Collection<int, \App\Models\AdAccount>  $adAccounts
     * @param  int[]  $adAccountIds
     * @return Collection<int, \App\Models\AdAccount>
     */
    private function filterAccounts(Collection $adAccounts, string $platform, array $adAccountIds): Collection
    {
        $filtered = $platform === 'all'
            ? $adAccounts
            : $adAccounts->where('platform', $platform);

        if (! empty($adAccountIds)) {
            $filtered = $filtered->whereIn('id', $adAccountIds);
        }

        return $filtered;
    }

    /**
     * Build a comma-separated list of `?` placeholders for SQL IN clauses.
     *
     * Replaces the repeated `implode(',', array_fill(0, count($ids), '?'))` pattern
     * used in 8+ query methods in this class.
     *
     * @param  array<int|string, mixed>  $items
     */
    private function placeholders(array $items): string
    {
        return implode(',', array_fill(0, count($items), '?'));
    }

    /**
     * Compute the four rate metrics (CTR, CPC, CPA, real ROAS, real CPO) from
     * raw aggregates. Returns null for each metric when its denominator is zero.
     *
     * Extracted from the repeated blocks in computeCampaignRows, computeAdsetRows,
     * computeAdRows, buildIndexRows, and computeMetrics.
     *
     * @return array{ctr: float|null, cpc: float|null, cpa: float|null, real_roas: float|null, real_cpo: float|null}
     */
    private function computeRateMetrics(
        float $spend,
        int $impressions,
        int $clicks,
        float $platformCvs,
        ?float $attributedRevenue,
        int $attributedOrders,
    ): array {
        return [
            'ctr'      => $impressions > 0
                ? round(($clicks / $impressions) * 100, 2)
                : null,
            'cpc'      => $clicks > 0
                ? round($spend / $clicks, 4)
                : null,
            'cpa'      => ($platformCvs > 0 && $spend > 0)
                ? round($spend / $platformCvs, 2)
                : null,
            'real_roas' => ($spend > 0 && $attributedRevenue !== null && $attributedRevenue > 0)
                ? round($attributedRevenue / $spend, 2)
                : null,
            'real_cpo'  => ($spend > 0 && $attributedOrders > 0)
                ? round($spend / $attributedOrders, 2)
                : null,
        ];
    }

    /** Build a SQL WHERE fragment for status filtering with a configurable table alias. */
    private function statusFilter(string $status, string $alias): string
    {
        return match ($status) {
            'active' => "AND LOWER({$alias}.status) IN ('active','enabled','delivering')",
            'paused' => "AND LOWER({$alias}.status) IN ('paused','inactive','disabled')",
            default  => '',
        };
    }

    /** Channel filter fragment (orders.attribution_last_touch->>'channel_type') for paid platforms. */
    private function channelFilter(string $platform, string $alias): string
    {
        return match ($platform) {
            'facebook' => "AND {$alias}attribution_last_touch->>'channel_type' = 'paid_social'",
            'google'   => "AND {$alias}attribution_last_touch->>'channel_type' = 'paid_search'",
            default    => "AND {$alias}attribution_last_touch->>'channel_type' IN ('paid_social','paid_search')",
        };
    }

    /**
     * Closure returning a correlated JSONB-array sum expression for a Meta insights
     * field (e.g. `video_15_sec_watched_actions`). Sums the 'value' field across all
     * array elements per row, then across days. Returns 0 for missing rows.
     *
     * @return \Closure(string): string
     */
    private function videoSumExpr(): \Closure
    {
        return fn (string $field) => "COALESCE(SUM(
            CASE WHEN ai.raw_insights IS NOT NULL AND (ai.raw_insights->'{$field}') IS NOT NULL
            THEN (
                SELECT COALESCE(SUM((elem->>'value')::numeric), 0)
                FROM jsonb_array_elements(ai.raw_insights->'{$field}') AS elem
            )
            ELSE 0 END
        ), 0)";
    }

    // ─── Daypart heatmap ────────────────────────────────────────────────────────

    /**
     * Returns spend + ROAS by day-of-week, and order revenue by day-of-week × hour-of-day.
     *
     * Since ad_insights.date is a DATE column (no hour granularity), spend is aggregated
     * per day-of-week only. Order revenue — which has full timestamps via occurred_at —
     * provides the hour dimension, so the heatmap cell intensity is driven by order revenue.
     * ROAS per day-of-week is derived by dividing same-period order revenue by ad spend.
     *
     * Always filters level = 'campaign' — never SUM across levels (CLAUDE.md gotcha).
     * Uses withoutGlobalScopes() + explicit workspace_id bind to work safely in API context.
     *
     * @param  string  $platform  'all' | 'facebook' | 'google'
     * @return array{
     *   cells: list<array{dow:int,hour:int,revenue:float,orders:int}>,
     *   roas_by_dow: list<array{dow:int,spend:float,revenue:float,roas:float|null}>,
     * }
     *
     * @see AdsController::daypartHeatmap — HTTP handler
     * @see docs/pages/ads.md §Daypart Heatmap
     */
    public function daypartHeatmap(
        int    $workspaceId,
        string $from,
        string $to,
        string $platform = 'all',
    ): array {
        // Spend by day-of-week from ad_insights (daily granularity only).
        $platformClause = $platform !== 'all' ? 'AND platform = ?' : '';
        $spendBindings  = $platform !== 'all'
            ? [$workspaceId, $from, $to, $platform]
            : [$workspaceId, $from, $to];

        $spendRows = DB::select("
            SELECT
                EXTRACT(DOW FROM date)::int                    AS dow,
                COALESCE(SUM(spend_in_reporting_currency), 0)  AS spend
            FROM ad_insights
            WHERE workspace_id = ?
              AND level = 'campaign'
              AND date BETWEEN ? AND ?
              {$platformClause}
            GROUP BY dow
            ORDER BY dow
        ", $spendBindings);

        // Order revenue + count by day-of-week × hour-of-day.
        $orderRows = DB::select("
            SELECT
                EXTRACT(DOW  FROM occurred_at AT TIME ZONE 'UTC')::int  AS dow,
                EXTRACT(HOUR FROM occurred_at AT TIME ZONE 'UTC')::int  AS hour,
                COUNT(*)                                                 AS orders,
                COALESCE(SUM(total_in_reporting_currency), 0)           AS revenue
            FROM orders
            WHERE workspace_id = ?
              AND status IN ('processing', 'completed')
              AND occurred_at BETWEEN ? AND ?
            GROUP BY dow, hour
            ORDER BY dow, hour
        ", [$workspaceId, $from . ' 00:00:00', $to . ' 23:59:59']);

        // Build a spend-by-dow lookup for ROAS computation.
        /** @var array<int, float> $spendByDow */
        $spendByDow = [];
        foreach ($spendRows as $r) {
            $spendByDow[(int) $r->dow] = (float) $r->spend;
        }

        // Build a revenue-by-dow rollup from the order rows (for ROAS denominator).
        /** @var array<int, float> $revenueByDow */
        $revenueByDow = [];
        foreach ($orderRows as $r) {
            $dow = (int) $r->dow;
            $revenueByDow[$dow] = ($revenueByDow[$dow] ?? 0.0) + (float) $r->revenue;
        }

        // ROAS by day-of-week.
        $roas_by_dow = [];
        for ($d = 0; $d <= 6; $d++) {
            $spend   = $spendByDow[$d]   ?? 0.0;
            $revenue = $revenueByDow[$d] ?? 0.0;
            $roas_by_dow[] = [
                'dow'     => $d,
                'spend'   => round($spend,   2),
                'revenue' => round($revenue, 2),
                // NULLIF logic: null when no spend, avoids divide-by-zero.
                'roas'    => $spend > 0 ? round($revenue / $spend, 2) : null,
            ];
        }

        // Cells for the 7×24 grid.
        $cells = array_values(array_map(fn ($r) => [
            'dow'     => (int)   $r->dow,
            'hour'    => (int)   $r->hour,
            'revenue' => (float) $r->revenue,
            'orders'  => (int)   $r->orders,
        ], $orderRows));

        return [
            'cells'       => $cells,
            'roas_by_dow' => $roas_by_dow,
        ];
    }
}
