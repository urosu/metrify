<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shareable snapshot links that render a read-only view of a page at a frozen date range.
 * Token is a 64-char random string used as the public URL key.
 *
 * Reads: public_snapshot_tokens table (workspace-scoped).
 * Writes: PublicSnapshotService (create, revoke, record access).
 * Called by: PublicSnapshotController (public route, no auth); ShareModal component.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class PublicSnapshotToken extends Model
{
    protected $hidden = [
        'token',
    ];

    protected $fillable = [
        'workspace_id',
        'token',
        'page',
        'url_state',
        'date_range_locked',
        'snapshot_data',
        'expires_at',
        'revoked_at',
        'created_by',
        'last_accessed_at',
        'access_count',
    ];

    protected function casts(): array
    {
        return [
            'url_state'         => 'array',
            'snapshot_data'     => 'array',
            'date_range_locked' => 'boolean',
            'expires_at'        => 'datetime',
            'revoked_at'        => 'datetime',
            'last_accessed_at'  => 'datetime',
            'access_count'      => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
