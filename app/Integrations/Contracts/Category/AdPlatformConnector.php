<?php

namespace App\Integrations\Contracts\Category;

use App\Integrations\Contracts\Base\Connectable;
use App\Integrations\Contracts\Base\Syncable;
use App\Models\AdAccount;
use Carbon\CarbonImmutable;

/**
 * Contract for ad platform integrations (Meta, Google Ads, TikTok, future Pinterest, Snapchat).
 *
 * Two-phase sync: first pull the campaign/adset/ad structure tree,
 * then fetch daily performance insights at the requested granularity level.
 *
 * To add a new ad platform (e.g., Pinterest Ads):
 * 1. Create PinterestAdsClient implementing this interface
 * 2. Register it in the service container
 * 3. Add a DispatchAdSyncJobs('pinterest') scheduled entry
 */
interface AdPlatformConnector extends Connectable, Syncable
{
    /**
     * Sync the campaign structure tree: campaigns, ad sets/groups, and ads.
     *
     * Upserts AdCampaign, AdSet, and Ad records with their current
     * status, names, objectives, and creative references.
     *
     * Called less frequently than insights (structure changes rarely).
     */
    public function syncCampaignStructure(AdAccount $account): void;

    /**
     * Sync daily performance insights for the given date range and level.
     *
     * Upserts AdInsight rows with: spend, impressions, clicks, cpm, cpc, ctr,
     * conversions, purchase_value, video view metrics.
     *
     * All monetary values must be in the ad account's native currency.
     * Conversion to workspace currency happens in SnapshotBuilder via FX rates.
     *
     * @param  'campaign'|'adset'|'ad'  $level  Granularity level for the report.
     *                                          Maps to platform-specific values internally
     *                                          (e.g., TikTok: AUCTION_CAMPAIGN/AUCTION_AD).
     */
    public function syncDailyInsights(
        AdAccount $account,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        string $level = 'ad',
    ): void;
}
