<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use App\Services\Workspace\ShareSnapshotTokenService;

/**
 * Generate a public snapshot token and return the shareable URL.
 *
 * Backing action for the ShareSnapshotButton UI primitive (UX §5.29).
 * Delegates token creation to ShareSnapshotTokenService.
 *
 * Input:  Workspace, User, page, url_state, ttl_seconds (optional)
 * Output: array{token: string, url: string}
 * Writes: public_snapshot_tokens
 *
 * @see docs/planning/backend.md §2.2 (action spec)
 * @see app/Services/Workspace/ShareSnapshotTokenService.php
 * @see docs/UX.md §5.29 (ShareSnapshotButton primitive)
 */
class ShareSnapshotAction
{
    public function __construct(private readonly ShareSnapshotTokenService $tokenService) {}

    /**
     * @param  array<string, mixed>  $urlState
     * @return array{token: string, url: string}
     */
    public function handle(
        Workspace $workspace,
        User $user,
        string $page,
        array $urlState,
        ?int $ttlSeconds = null,
    ): array {
        $token = $this->tokenService->generate(
            workspaceId: $workspace->id,
            createdBy: $user->id,
            page: $page,
            urlState: $urlState,
            ttlSeconds: $ttlSeconds,
        );

        return [
            'token' => $token,
            'url'   => url("/public/snapshot/{$token}"),
        ];
    }
}
