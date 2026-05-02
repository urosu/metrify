<?php

namespace App\Integrations\Contracts\Platform;

use App\Integrations\Contracts\Category\AdPlatformConnector;
use App\Models\AdAccount;
use Carbon\CarbonImmutable;

/**
 * Google Ads specific extensions.
 *
 * Adds Shopping SKU-level insights, which provide product_item_id
 * for matching ad spend to specific products via shopping_performance_view.
 */
interface GoogleAdsConnector extends AdPlatformConnector
{
    /**
     * Sync Shopping campaign SKU-level performance data.
     *
     * Queries shopping_performance_view for product_item_id (SKU) level
     * spend, impressions, clicks, conversions, and conversion value.
     * Used for product-level ad spend attribution.
     *
     * Upserts AdInsight rows with product_item_id populated.
     */
    public function syncShoppingSkuInsights(
        AdAccount $account,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): void;
}
