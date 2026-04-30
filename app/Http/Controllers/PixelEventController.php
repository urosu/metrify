<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PixelEvent;
use App\Models\Workspace;
use App\Scopes\WorkspaceScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Public server-side pixel endpoint.
 *
 * Route: POST /pixel/{workspace}/event?token={pixel_tracking_token}
 *        OPTIONS /pixel/{workspace}/event  (CORS preflight)
 *
 * Authentication: per-workspace static token passed as ?token= query param.
 * The endpoint is intentionally NOT in any session / auth middleware group
 * because events come from arbitrary store domains.
 *
 * Performance notes:
 *  - Insert is synchronous but cheap (single upsert, indexed dedup key).
 *  - The (workspace_id, event_id) unique constraint handles concurrent dupes.
 *  - If write volume grows beyond ~500 req/s consider dispatching a queued
 *    job (DispatchPixelEventJob) instead of the direct upsert.
 *
 * Privacy:
 *  - ip_address is stored verbatim. TODO: when workspace has a future
 *    "privacy_strict" flag, truncate last octet of IPv4 / last 80 bits of IPv6.
 *
 * @see app/Models/PixelEvent.php
 * @see app/Services/PixelEvents/ServerSideEventForwarder.php
 * @see database/migrations/2026_04_29_000012_create_pixel_events_table.php
 */
class PixelEventController extends Controller
{
    private const CORS_HEADERS = [
        'Access-Control-Allow-Origin'  => '*',
        'Access-Control-Allow-Methods' => 'POST, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Accept',
        'Access-Control-Max-Age'       => '86400',
    ];

    private const VALID_EVENT_TYPES = [
        'page_view',
        'add_to_cart',
        'begin_checkout',
        'purchase',
        'custom',
    ];

    // ── CORS preflight ────────────────────────────────────────────────────────

    public function preflight(): Response
    {
        return response('', 204, self::CORS_HEADERS);
    }

    // ── Store event ───────────────────────────────────────────────────────────

    public function store(Request $request, Workspace $workspace): JsonResponse|Response
    {
        // Load workspace without WorkspaceScope — no session context on public routes.
        $workspace = Workspace::withoutGlobalScope(WorkspaceScope::class)
            ->where('id', $workspace->id)
            ->first();

        if ($workspace === null) {
            return $this->corsJson(['error' => 'Not found'], 404);
        }

        // ── Auth — token in query string ──────────────────────────────────────
        $token = (string) $request->query('token', '');

        if (
            empty($workspace->pixel_tracking_token) ||
            ! hash_equals($workspace->pixel_tracking_token, $token)
        ) {
            Log::warning('PixelEventController: invalid token', [
                'workspace_id' => $workspace->id,
            ]);

            return $this->corsJson(['error' => 'Unauthorized'], 401);
        }

        // ── Validation ────────────────────────────────────────────────────────
        $validated = $request->validate([
            'event_id'      => ['required', 'string', 'max:64'],
            'event_type'    => ['required', 'string', Rule::in(self::VALID_EVENT_TYPES)],
            'occurred_at'   => ['sometimes', 'nullable', 'date'],
            'session_id'    => ['sometimes', 'nullable', 'string', 'max:64'],
            'url'           => ['required', 'string', 'max:2048'],
            'referrer'      => ['sometimes', 'nullable', 'string', 'max:2048'],
            'utm_source'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'utm_medium'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'utm_campaign'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'utm_content'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'utm_term'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'store_id'      => ['sometimes', 'nullable', 'integer', 'min:1'],
            'payload'       => ['sometimes', 'nullable', 'array'],
        ]);

        // ── Build row ─────────────────────────────────────────────────────────
        $row = [
            'workspace_id' => $workspace->id,
            'store_id'     => $validated['store_id'] ?? null,
            'event_id'     => $validated['event_id'],
            'event_type'   => $validated['event_type'],
            'occurred_at'  => isset($validated['occurred_at'])
                ? $validated['occurred_at']
                : now(),
            'session_id'   => $validated['session_id'] ?? null,
            'user_agent'   => $request->userAgent() ? substr($request->userAgent(), 0, 512) : null,
            'ip_address'   => $request->ip(),
            'url'          => substr($validated['url'], 0, 2048),
            'referrer'     => isset($validated['referrer'])
                ? substr($validated['referrer'], 0, 2048)
                : null,
            'utm_source'   => $validated['utm_source'] ?? null,
            'utm_medium'   => $validated['utm_medium'] ?? null,
            'utm_campaign' => $validated['utm_campaign'] ?? null,
            'utm_content'  => $validated['utm_content'] ?? null,
            'utm_term'     => $validated['utm_term'] ?? null,
            'payload'      => json_encode($validated['payload'] ?? []),
        ];

        // ── Upsert — dedup on (workspace_id, event_id) ────────────────────────
        // If the same event_id arrives twice (browser pixel + server relay), the
        // second write silently no-ops via the unique constraint.
        PixelEvent::withoutGlobalScope(WorkspaceScope::class)
            ->updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'event_id'     => $validated['event_id'],
                ],
                $row
            );

        return response('', 204, self::CORS_HEADERS);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function corsJson(array $data, int $status): JsonResponse
    {
        return response()->json($data, $status, self::CORS_HEADERS);
    }
}
