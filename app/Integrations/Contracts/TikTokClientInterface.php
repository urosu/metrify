<?php

namespace App\Integrations\Contracts;

use App\Integrations\Contracts\Category\AdPlatformConnector;

/**
 * TikTok Marketing API v1.3.
 *
 * @deprecated Use {@see AdPlatformConnector} directly. TikTok has no platform-specific
 *             extensions beyond the standard AdPlatformConnector contract.
 *             This interface is kept for backward compatibility.
 *
 * Migration path: implements AdPlatformConnector (Connectable + Syncable).
 * The syncReport() method maps to syncDailyInsights() with level translation
 * ('ad' -> 'AUCTION_AD', 'adset' -> 'AUCTION_ADGROUP', 'campaign' -> 'AUCTION_CAMPAIGN').
 */
interface TikTokClientInterface extends AdPlatformConnector
{
    //
}
