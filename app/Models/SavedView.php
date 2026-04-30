<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User-created filter/sort/column/date-range combos persisted per page.
 * Pinned saved views appear in the SavedViewBar primitive (UX §5.12).
 *
 * Reads: saved_views table (workspace-scoped).
 * Writes: SavedViewService (create, pin, delete actions).
 * Called by: All page controllers that support saved views; SavedViewBar component.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class SavedView extends Model
{
    protected $fillable = [
        'workspace_id',
        'user_id',
        'page',
        'name',
        'url_state',
        'is_pinned',
        'pin_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'url_state' => 'array',
            'is_pinned' => 'boolean',
            'pin_order' => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
