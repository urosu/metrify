<?php

declare(strict_types=1);

namespace App\Services\PixelEvents;

use App\Models\PixelEvent;
use Illuminate\Support\Facades\Log;

/**
 * Stub: Server-side event forwarder.
 *
 * Seam for forwarding captured PixelEvents to ad-platform Conversions APIs.
 * Currently a no-op — the real CAPI relay is out of scope for this sprint.
 *
 * Integration plan (future):
 *  - forwardToFacebookCapi: POST to https://graph.facebook.com/v19.0/{pixel_id}/events
 *    with the server_event payload built from the PixelEvent row + identity data.
 *    Requires workspace.facebook_capi_token (to be added) and pixel_id.
 *    Dedup: include event_id in the request so Meta ignores duplicate browser fires.
 *
 *  - forwardToGoogleEnhancedConversions: POST to Google's Conversion Tracking endpoint
 *    with gclid extracted from payload['gclid'] if present.
 *
 *  - forwardToTiktokCapi, forwardToPinterestCapi, etc. — same pattern.
 *
 * Dispatching strategy (future):
 *  - A queued job (ForwardPixelEventJob) should call this after insertion so the
 *    pixel endpoint stays at <5 ms response time under high write volume.
 *
 * @see app/Http/Controllers/PixelEventController.php (insertion point)
 * @see docs/competitors/features/server-side-pixel.md (requirements)
 */
class ServerSideEventForwarder
{
    /**
     * Forward a captured pixel event to Facebook Conversions API.
     *
     * TODO: implement. Requires workspace.facebook_capi_token and a pixel_id
     * linked to the workspace's ad account. See docs/competitors/features/server-side-pixel.md
     * for the Elevar-inspired dedup pattern (event_id prevents double-counting
     * browser pixel + server CAPI fires for the same conversion).
     *
     * @param  PixelEvent  $event  The freshly persisted pixel event row.
     */
    public function forwardToFacebookCapi(PixelEvent $event): void
    {
        // no-op stub — CAPI relay not yet implemented.
        Log::debug('ServerSideEventForwarder: forwardToFacebookCapi stub called', [
            'workspace_id' => $event->workspace_id,
            'event_id'     => $event->event_id,
            'event_type'   => $event->event_type,
        ]);
    }

    /**
     * Forward a captured pixel event to Google Enhanced Conversions.
     *
     * TODO: implement. Uses gclid from payload if present.
     *
     * @param  PixelEvent  $event  The freshly persisted pixel event row.
     */
    public function forwardToGoogleEnhancedConversions(PixelEvent $event): void
    {
        // no-op stub.
        Log::debug('ServerSideEventForwarder: forwardToGoogleEnhancedConversions stub called', [
            'workspace_id' => $event->workspace_id,
            'event_id'     => $event->event_id,
            'event_type'   => $event->event_type,
        ]);
    }
}
