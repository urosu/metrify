<?php

declare(strict_types=1);

namespace App\Models;

use App\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daily session counts pulled from the Shopify Analytics API.
 *
 * Rows are upserted nightly by SyncShopifyAnalyticsJob on unique (store_id, date, source).
 * The source=NULL row is the store-total aggregate that SnapshotBuilderService reads when
 * computing daily_snapshots.sessions for Shopify stores.
 *
 * Re-auth note: the read_analytics scope is required for data to be populated.
 * Stores connected before that scope was added will have no rows until re-auth.
 *
 * Reads:  shopify_daily_sessions (workspace-scoped).
 * Writes: SyncShopifyAnalyticsJob (nightly); BackfillShopifySessionsCommand (one-shot CLI).
 * Used by: SnapshotBuilderService::buildDaily (sessions_source = 'shopify').
 *
 * @see docs/planning/schema.md §1.5 (daily_snapshots.sessions)
 * @see app/Jobs/SyncShopifyAnalyticsJob.php
 */
#[ScopedBy([WorkspaceScope::class])]
class ShopifyDailySession extends Model
{
    protected $fillable = [
        'workspace_id',
        'store_id',
        'date',
        'visits',
        'visitors',
        'source',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'date'      => 'date',
            'visits'    => 'integer',
            'visitors'  => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
