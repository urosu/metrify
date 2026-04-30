<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Revoke public_snapshot_tokens past their expires_at timestamp.
 *
 * Sets `revoked_at = NOW()` for all tokens where `expires_at < NOW()`
 * and `revoked_at IS NULL`. Does not delete rows — keeps the audit trail.
 *
 * Queue:     low
 * Schedule:  daily 04:15 UTC
 * Timeout:   60 s
 * Tries:     3
 *
 * Dispatched by: schedule (global — touches all workspaces)
 *
 * @see docs/planning/backend.md §3.3 (job spec)
 * @see app/Services/Workspace/ShareSnapshotTokenService.php
 */
class ExpirePublicSnapshotTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries   = 3;

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        DB::table('public_snapshot_tokens')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
