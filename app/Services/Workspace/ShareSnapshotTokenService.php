<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Generate, revoke, and validate public-snapshot tokens.
 *
 * A snapshot token is a 64-char random hex string that encodes a frozen
 * (page + url_state) combination. Tokens have optional TTL; `null` means never
 * expiring. Tokens can be revoked instantly by setting `revoked_at`.
 *
 * Resolution returns the token row only if it is valid (not revoked, not expired)
 * and updates the access audit columns.
 *
 * Reads:  public_snapshot_tokens
 * Writes: public_snapshot_tokens
 * Called by: PublicSnapshotController (new), GenerateSnapshotTokenAction
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/planning/schema.md §1.8 (public_snapshot_tokens table)
 * @see docs/UX.md §5.29 (ShareSnapshotButton primitive)
 */
class ShareSnapshotTokenService
{
    private const TOKEN_BYTES = 32; // 64 hex chars

    /**
     * Generate a new public-snapshot token.
     *
     * @param  array<string, mixed>  $urlState       Frozen filter+date state.
     * @param  int|null              $ttlSeconds      Time-to-live in seconds (null = never expires).
     * @param  array<string, mixed>|null  $snapshotData  Optional materialised payload.
     * @return string  The generated token.
     */
    public function generate(
        int $workspaceId,
        int $createdBy,
        string $page,
        array $urlState,
        ?int $ttlSeconds = null,
        ?array $snapshotData = null,
        bool $dateRangeLocked = true,
    ): string {
        $token = Str::lower(bin2hex(random_bytes(self::TOKEN_BYTES)));
        $expiresAt = $ttlSeconds !== null ? now()->addSeconds($ttlSeconds) : null;

        DB::table('public_snapshot_tokens')->insert([
            'workspace_id'      => $workspaceId,
            'token'             => $token,
            'page'              => $page,
            'url_state'         => json_encode($urlState),
            'date_range_locked' => $dateRangeLocked,
            'snapshot_data'     => $snapshotData !== null ? json_encode($snapshotData) : null,
            'expires_at'        => $expiresAt,
            'revoked_at'        => null,
            'created_by'        => $createdBy,
            'last_accessed_at'  => null,
            'access_count'      => 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return $token;
    }

    /**
     * Revoke a token by setting `revoked_at` to now.
     *
     * Idempotent — already-revoked tokens are silently accepted.
     * Caller must verify the token belongs to the workspace.
     */
    public function revoke(string $token): void
    {
        DB::table('public_snapshot_tokens')
            ->where('token', $token)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Resolve and validate a token for a public page request.
     *
     * Returns the token row if valid, null if revoked, expired, or not found.
     * Increments `access_count` and sets `last_accessed_at` on each valid resolution.
     *
     * @return object|null  Full token row (url_state and snapshot_data as JSON strings).
     */
    public function resolve(string $token): ?object
    {
        $row = DB::table('public_snapshot_tokens')
            ->where('token', $token)
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();

        if ($row === null) {
            return null;
        }

        DB::table('public_snapshot_tokens')
            ->where('token', $token)
            ->update([
                'last_accessed_at' => now(),
                'access_count'     => DB::raw('access_count + 1'),
                'updated_at'       => now(),
            ]);

        return $row;
    }
}
