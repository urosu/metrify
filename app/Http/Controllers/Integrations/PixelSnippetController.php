<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\PixelEvent;
use App\Models\Workspace;
use App\Scopes\WorkspaceScope;
use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Server-side pixel snippet page.
 *
 * Renders the JS snippet merchants paste into their storefront and exposes the
 * workspace's pixel_tracking_token so the snippet URL can be built client-side.
 *
 * Route: GET /{workspace:slug}/integrations/pixel-snippet
 *
 * The token is read-only here; rotation is a future feature.
 *
 * @see app/Http/Controllers/PixelEventController.php (the public endpoint)
 * @see resources/js/Pages/Integrations/PixelSnippet.tsx
 */
class PixelSnippetController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $workspace = Workspace::withoutGlobalScope(WorkspaceScope::class)
            ->where('id', $workspaceId)
            ->select(['id', 'slug', 'pixel_tracking_token'])
            ->firstOrFail();

        return Inertia::render('Integrations/PixelSnippet', [
            'workspaceSlug'       => $workspace->slug,
            'pixelTrackingToken'  => $workspace->pixel_tracking_token,
            'endpointBase'        => rtrim(config('app.url'), '/'),
        ]);
    }

    /**
     * Return the 10 most recent pixel events for this workspace.
     * URL, referrer, and ip_address are masked for the feed display.
     *
     * Route: GET /{workspace:slug}/integrations/pixel-snippet/recent-events
     */
    public function recentEvents(Request $request): JsonResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $events = PixelEvent::withoutGlobalScope(WorkspaceScope::class)
            ->where('workspace_id', $workspaceId)
            ->orderByDesc('occurred_at')
            ->limit(10)
            ->get(['event_type', 'occurred_at', 'url', 'utm_source', 'utm_medium', 'session_id'])
            ->map(fn (PixelEvent $e) => [
                'event_type'  => $e->event_type,
                'occurred_at' => $e->occurred_at?->toISOString(),
                // Mask URL to path only — no domain, no query string that could leak tokens.
                'url_path'    => $this->maskUrl($e->url),
                'utm_source'  => $e->utm_source,
                'utm_medium'  => $e->utm_medium,
                // Mask session ID — show first 8 chars only.
                'session_id'  => $e->session_id ? substr($e->session_id, 0, 8) . '…' : null,
            ])
            ->all();

        return response()->json(['events' => $events]);
    }

    private function maskUrl(?string $url): string
    {
        if ($url === null || $url === '') {
            return '—';
        }
        try {
            return parse_url($url, PHP_URL_PATH) ?: '/';
        } catch (\Throwable) {
            return '/';
        }
    }
}
