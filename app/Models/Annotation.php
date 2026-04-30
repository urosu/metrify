<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Workspace events, notes, and promotions displayed as timeline overlays on charts.
 * Merges the former daily_notes and workspace_events tables.
 *
 * Reads: annotations table (workspace-scoped).
 * Writes: AnnotationService, system jobs (algorithm updates, integration events).
 * Called by: OverviewController, DashboardController; chart primitives read via Inertia props.
 *
 * @see docs/planning/schema.md#1-per-table-reference
 */
#[ScopedBy([WorkspaceScope::class])]
class Annotation extends Model
{
    protected $fillable = [
        'workspace_id',
        'author_type',
        'author_id',
        'title',
        'body',
        'annotation_type',
        'scope_type',
        'scope_id',
        'starts_at',
        'ends_at',
        'is_hidden_per_user',
        'suppress_anomalies',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'          => 'datetime',
            'ends_at'            => 'datetime',
            'is_hidden_per_user' => 'array',
            'suppress_anomalies' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to annotations visible for a given set of store IDs.
     *
     * Workspace-scoped annotations (scope_type = 'workspace') always pass through.
     * Store-scoped annotations (scope_type = 'store') are included only when their
     * scope_id matches one of the supplied store IDs.
     * Empty $storeIds means "all stores" so all annotations pass through.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int[]  $storeIds
     */
    public function scopeForAnnotationScope($query, array $storeIds): void
    {
        if (empty($storeIds)) {
            return; // no filter — all scope_types visible
        }
        $query->where(function ($q) use ($storeIds) {
            $q->where('scope_type', 'workspace')
              ->orWhere(function ($q2) use ($storeIds) {
                  $q2->where('scope_type', 'store')->whereIn('scope_id', $storeIds);
              });
        });
    }
}
