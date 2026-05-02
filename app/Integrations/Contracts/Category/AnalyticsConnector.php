<?php

namespace App\Integrations\Contracts\Category;

use App\Integrations\Contracts\Base\Connectable;
use App\Integrations\Contracts\Base\Syncable;
use App\Models\AnalyticsProperty;
use Carbon\CarbonImmutable;

/**
 * Contract for analytics/search integrations (GA4, GSC).
 *
 * These platforms provide daily aggregate reports with dimension breakdowns.
 * They share a common pattern: fetch a date range of rows, upsert into
 * a platform-specific daily table (ga4_daily, gsc_daily).
 *
 * Both GA4 and GSC have rolling data windows (14 months / 16 months)
 * that disappear from the API -- we must sync regularly and store locally.
 */
interface AnalyticsConnector extends Connectable, Syncable
{
    /**
     * Sync daily report data for the given date range.
     *
     * GA4: Upserts ga4_daily rows (sessions, users, ecommerce, funnel metrics)
     *      with dimension breakdown (source, medium, campaign, country, device).
     *
     * GSC: Upserts gsc_daily rows (clicks, impressions, position)
     *      with dimension breakdown (query, page, country, device).
     *
     * Implementations handle pagination internally (GA4: limit+offset,
     * GSC: startRow in 25k increments).
     *
     * @param  AnalyticsProperty  $property  The GA4 property or GSC site to sync.
     *                                       Type discrimination is handled by the
     *                                       concrete model (GA4Property, SearchProperty).
     */
    public function syncDailyReport(
        AnalyticsProperty $property,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): void;
}
