<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ShareSnapshotAction;
use App\Models\PublicSnapshotToken;
use App\Models\Workspace;
use App\Services\Workspace\ShareSnapshotTokenService;
use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public snapshot share links — generate, revoke, and render tokenized read-only views.
 *
 * generate() — called by ShareSnapshotButton (fetch POST, JSON response).
 * revoke()   — called by workspace member wanting to kill a share link.
 * render()   — unauthenticated public route; resolves token → frozen page state.
 *
 * Reads: public_snapshot_tokens
 * Writes: public_snapshot_tokens (via ShareSnapshotAction / ShareSnapshotTokenService)
 * Called by: POST /{workspace}/share-snapshots, DELETE /{workspace}/share-snapshots/{id},
 *            GET /public/snapshot/{token}
 *
 * @see docs/planning/backend.md §13
 * @see app/Actions/ShareSnapshotAction.php
 * @see app/Services/Workspace/ShareSnapshotTokenService.php
 * @see docs/UX.md §5.29 (ShareSnapshotButton primitive)
 */
class PublicSnapshotController extends Controller
{
    public function __construct(
        private readonly ShareSnapshotAction $shareSnapshot,
        private readonly ShareSnapshotTokenService $tokenService,
    ) {}

    /**
     * Generate a public snapshot token and return {token, url} as JSON.
     *
     * Frontend (ShareSnapshotButton.tsx) uses fetch() and expects:
     *   { url: string }
     * We return the full {token, url} shape; the component reads `data.url`.
     *
     * ttl_seconds is optional; omit for a never-expiring link.
     */
    public function generate(Request $request): JsonResponse
    {
        $workspace = Workspace::findOrFail(app(WorkspaceContext::class)->id());

        $validated = $request->validate([
            'page'        => ['required', 'string', 'max:255'],
            'url_state'   => ['required', 'string'],   // JSON-encoded string from frontend
            'ttl_seconds' => ['sometimes', 'nullable', 'integer', 'min:3600'],
        ]);

        // url_state arrives as a JSON-encoded string from the frontend component
        $urlState = json_decode($validated['url_state'], true) ?? [];

        $result = $this->shareSnapshot->handle(
            workspace: $workspace,
            user: $request->user(),
            page: $validated['page'],
            urlState: $urlState,
            ttlSeconds: $validated['ttl_seconds'] ?? null,
        );

        return response()->json($result);
    }

    /**
     * Revoke a snapshot token by DB row id.
     *
     * Only the creator or a workspace owner may revoke.
     * Sets revoked_at via ShareSnapshotTokenService::revoke (idempotent).
     */
    public function revoke(Request $request, int $id): RedirectResponse
    {
        /** @var PublicSnapshotToken $snapshotToken */
        $snapshotToken = PublicSnapshotToken::findOrFail($id);

        // Only the creator of the token may revoke it
        if ($snapshotToken->created_by !== $request->user()->id) {
            abort(403, 'You did not create this snapshot link.');
        }

        $this->tokenService->revoke($snapshotToken->token);

        return back()->with('success', 'Snapshot link revoked.');
    }

    /**
     * Render a public frozen page for an unauthenticated viewer.
     *
     * Resolves the token via ShareSnapshotTokenService::resolve (which also
     * increments the access counter and sets last_accessed_at).
     * Returns 404 when the token is missing, revoked, or expired.
     */
    public function render(Request $request, string $token): Response
    {
        $snapshot = $this->tokenService->resolve($token);

        if ($snapshot === null) {
            abort(404, 'This snapshot link is no longer valid.');
        }

        /** @var \App\Models\Workspace $workspace */
        $workspace = Workspace::withoutGlobalScopes()->findOrFail($snapshot->workspace_id);

        // Extract date_range from url_state (stored as JSON string or already decoded)
        $urlState = is_string($snapshot->url_state)
            ? (json_decode($snapshot->url_state, true) ?? [])
            : (array) $snapshot->url_state;

        $from  = $urlState['from'] ?? null;
        $to    = $urlState['to']   ?? null;
        $label = ($from && $to)
            ? \Carbon\Carbon::parse($from)->format('M j, Y') . ' – ' . \Carbon\Carbon::parse($to)->format('M j, Y')
            : 'Custom range';

        return Inertia::render('Public/Snapshot', [
            'token'          => $snapshot->token,
            'workspace_name' => $workspace->name,
            'currency'       => $workspace->reporting_currency ?? 'USD',
            'date_range'     => ['from' => $from, 'to' => $to, 'label' => $label],
            'generated_at'   => \Carbon\Carbon::parse($snapshot->created_at)->toIso8601String(),
            'expires_at'     => $snapshot->expires_at
                ? \Carbon\Carbon::parse($snapshot->expires_at)->toIso8601String()
                : null,
            'snapshot_data'  => $snapshot->snapshot_data !== null
                ? json_decode($snapshot->snapshot_data, true)
                : null,
        ]);
    }
}
