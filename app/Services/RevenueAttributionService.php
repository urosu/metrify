<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Computes UTM-attributed and unattributed revenue from orders.
 *
 * Consumed by: DashboardController (organic row), SeoController (unattributed card),
 *              CampaignsController (revenue context cards + Real ROAS).
 *
 * All returned values are in the workspace's reporting currency
 * (uses orders.total_in_reporting_currency, which is pre-converted at sync time).
 *
 * Orders with NULL total_in_reporting_currency are excluded — they failed FX
 * conversion and will be corrected by RetryMissingConversionJob.
 *
 * Only 'completed' and 'processing' orders are included (same scope as AOV/revenue).
 *
 * See: PLANNING.md "Business Logic to Preserve" → Formulas
 */
class RevenueAttributionService
{
    /**
     * UTM source values that map to each paid channel.
     *
     * Why: Facebook and Google traffic arrives with many utm_source variants
     * (fb, ig, instagram for Meta; cpc, ppc, google-ads for Google).
     * Matching is case-insensitive via LOWER() in SQL.
     *
     * See: PLANNING.md "Business Logic to Preserve" → UTM source matching
     */
    public const FACEBOOK_SOURCES = ['facebook', 'fb', 'ig', 'instagram'];
    public const GOOGLE_SOURCES   = ['google', 'cpc', 'google-ads', 'ppc'];

    /**
     * Return UTM-attributed revenue broken down by channel for the given period.
     *
     * Return shape:
     * [
     *   'facebook'     => float,   // orders with utm_source matching Facebook aliases
     *   'google'       => float,   // orders with utm_source matching Google aliases
     *   'other_tagged' => float,   // orders with a utm_source not matching either channel
     *   'total_tagged' => float,   // facebook + google + other_tagged
     * ]
     *
     * @param int                  $workspaceId
     * @param CarbonInterface      $from        Inclusive start (occurred_at)
     * @param CarbonInterface      $to          Inclusive end (occurred_at)
     * @param int|null             $storeId     Optional: filter to a single store
     */
    public function getAttributedRevenue(
        int $workspaceId,
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $storeId = null,
    ): array {
        $facebookPlaceholders = implode(',', array_fill(0, count(self::FACEBOOK_SOURCES), '?'));
        $googlePlaceholders   = implode(',', array_fill(0, count(self::GOOGLE_SOURCES), '?'));

        $sql = <<<SQL
            SELECT
                SUM(
                    CASE WHEN LOWER(utm_source) IN ({$facebookPlaceholders}) THEN total_in_reporting_currency ELSE 0 END
                ) AS facebook,
                SUM(
                    CASE WHEN LOWER(utm_source) IN ({$googlePlaceholders}) THEN total_in_reporting_currency ELSE 0 END
                ) AS google,
                SUM(
                    CASE WHEN utm_source IS NOT NULL
                              AND LOWER(utm_source) NOT IN ({$facebookPlaceholders})
                              AND LOWER(utm_source) NOT IN ({$googlePlaceholders})
                         THEN total_in_reporting_currency ELSE 0 END
                ) AS other_tagged
            FROM orders
            WHERE workspace_id = ?
              AND status IN ('completed', 'processing')
              AND total_in_reporting_currency IS NOT NULL
              AND occurred_at BETWEEN ? AND ?
        SQL;

        $bindings = [
            ...self::FACEBOOK_SOURCES,
            ...self::GOOGLE_SOURCES,
            ...self::FACEBOOK_SOURCES,
            ...self::GOOGLE_SOURCES,
            $workspaceId,
            $from->toDateTimeString(),
            $to->toDateTimeString(),
        ];

        if ($storeId !== null) {
            $sql      .= ' AND store_id = ?';
            $bindings[] = $storeId;
        }

        $row = DB::selectOne($sql, $bindings);

        $facebook   = (float) ($row->facebook   ?? 0);
        $google     = (float) ($row->google     ?? 0);
        $otherTagged = (float) ($row->other_tagged ?? 0);

        return [
            'facebook'     => $facebook,
            'google'       => $google,
            'other_tagged' => $otherTagged,
            'total_tagged' => $facebook + $google + $otherTagged,
        ];
    }

    /**
     * Return revenue attributed to a specific campaign by name (case-insensitive utm_campaign match).
     *
     * Used for Real ROAS calculation: attributed_revenue / campaign_spend.
     *
     * Why name-based matching: orders only store utm_campaign strings, not platform campaign IDs.
     * See: PLANNING.md "Business Logic to Preserve" → Real ROAS per campaign
     *
     * @param int             $workspaceId
     * @param string          $campaignName  Value from campaigns.name (case-insensitive match)
     * @param CarbonInterface $from
     * @param CarbonInterface $to
     * @param int|null        $storeId
     */
    public function getCampaignAttributedRevenue(
        int $workspaceId,
        string $campaignName,
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $storeId = null,
    ): float {
        $query = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereIn('status', ['completed', 'processing'])
            ->whereNotNull('total_in_reporting_currency')
            ->whereRaw('LOWER(utm_campaign) = LOWER(?)', [$campaignName])
            ->whereBetween('occurred_at', [
                $from->toDateTimeString(),
                $to->toDateTimeString(),
            ]);

        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }

        return (float) $query->sum('total_in_reporting_currency');
    }

    /**
     * Return utm_source values in the "other_tagged" bucket (not Facebook or Google aliases)
     * along with their order count and share of total revenue for the period.
     *
     * Used by ComputeUtmCoverageJob to populate workspaces.utm_unrecognized_sources.
     * Surfaced on the Dashboard near attribution metrics to help users fix mistagged URLs.
     *
     * Return shape: array of ['source' => string, 'order_count' => int, 'revenue_pct' => float]
     * ordered by order_count DESC. Empty array when no unrecognized sources exist.
     *
     * See: PLANNING.md "UTM Coverage Health Check + Tag Generator" — unrecognized sources
     */
    public function getUnrecognizedSources(
        int $workspaceId,
        string $from,
        string $to,
        ?int $storeId = null,
    ): array {
        $facebookPlaceholders = implode(',', array_fill(0, count(self::FACEBOOK_SOURCES), '?'));
        $googlePlaceholders   = implode(',', array_fill(0, count(self::GOOGLE_SOURCES), '?'));

        // Why: exclude NULL total_in_reporting_currency for consistency with getAttributedRevenue.
        // Orders with NULL FX conversion are pending RetryMissingConversionJob; their
        // revenue is unknown so they should not inflate order_count without revenue contribution.
        $sql = <<<SQL
            SELECT
                LOWER(utm_source)                                         AS source,
                COUNT(*)                                                  AS order_count,
                ROUND(
                    COALESCE(SUM(total_in_reporting_currency), 0) * 100.0
                    / NULLIF(SUM(SUM(total_in_reporting_currency)) OVER (), 0),
                    2
                )                                                         AS revenue_pct
            FROM orders
            WHERE workspace_id = ?
              AND status IN ('completed', 'processing')
              AND total_in_reporting_currency IS NOT NULL
              AND utm_source IS NOT NULL
              AND LOWER(utm_source) NOT IN ({$facebookPlaceholders})
              AND LOWER(utm_source) NOT IN ({$googlePlaceholders})
              AND occurred_at BETWEEN ? AND ?
        SQL;

        $bindings = [
            $workspaceId,
            ...self::FACEBOOK_SOURCES,
            ...self::GOOGLE_SOURCES,
            $from . ' 00:00:00',
            $to . ' 23:59:59',
        ];

        if ($storeId !== null) {
            $sql      .= ' AND store_id = ?';
            $bindings[] = $storeId;
        }

        $sql .= ' GROUP BY LOWER(utm_source) ORDER BY order_count DESC LIMIT 10';

        $rows = DB::select($sql, $bindings);

        return array_map(fn ($row) => [
            'source'      => (string) $row->source,
            'order_count' => (int) $row->order_count,
            'revenue_pct' => (float) $row->revenue_pct,
        ], $rows);
    }

    /**
     * Compute unattributed revenue = max(0, totalRevenue - total_tagged).
     *
     * Pass the totalRevenue from daily_snapshots (not from orders directly).
     * See: PLANNING.md "Cross-Channel Page Enhancements" → SEO page
     */
    public function getUnattributedRevenue(
        float $totalRevenue,
        float $totalTagged,
    ): float {
        return max(0.0, $totalRevenue - $totalTagged);
    }
}
