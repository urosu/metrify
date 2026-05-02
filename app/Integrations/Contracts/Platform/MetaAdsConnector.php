<?php

namespace App\Integrations\Contracts\Platform;

use App\Integrations\Contracts\Category\AdPlatformConnector;
use App\Models\AdAccount;

/**
 * Meta (Facebook/Instagram) Ads specific extensions.
 *
 * Adds creative thumbnail fetching, which uses Meta's video API
 * and has no equivalent on other ad platforms.
 */
interface MetaAdsConnector extends AdPlatformConnector
{
    /**
     * Fetch the thumbnail URL for a video creative.
     *
     * Used by the Creative Analysis page to display video thumbnails.
     * Calls GET /{video_id}?fields=thumbnails on the Graph API.
     *
     * @param  string  $videoId  The Meta video asset ID.
     * @return string|null  Thumbnail URL, or null if the video is not found.
     */
    public function fetchVideoThumbnail(AdAccount $account, string $videoId): ?string;
}
