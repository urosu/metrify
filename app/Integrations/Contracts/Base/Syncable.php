<?php

namespace App\Integrations\Contracts\Base;

use App\Models\Workspace;
use App\ValueObjects\DateRange;
use Carbon\CarbonImmutable;

/**
 * Base sync contract shared by every integration that pulls data.
 *
 * Defines the common sync lifecycle: trigger a sync for a date range,
 * track when the last sync completed, and report current sync status.
 */
interface Syncable
{
    /**
     * Run the primary data sync for the given date range.
     *
     * Each integration decides internally what "sync" means:
     * - StoreConnector: import orders/products modified since last sync
     * - AdPlatformConnector: fetch daily insights for the date window
     * - AnalyticsConnector: fetch daily report rows
     * - EmailPlatformConnector: fetch campaign/flow stats
     *
     * Implementations must be idempotent (upsert, not insert).
     *
     * @param  DateRange  $range  The date window to sync
     * @return void
     *
     * @throws \App\Exceptions\IntegrationSyncException
     * @throws \App\Exceptions\IntegrationRateLimitException
     */
    public function sync(Workspace $workspace, DateRange $range): void;

    /**
     * Get the timestamp of the last successful sync completion.
     *
     * Returns null if the integration has never been synced.
     * Used by reconciliation jobs to determine the incremental window.
     */
    public function getLastSyncedAt(Workspace $workspace): ?CarbonImmutable;

    /**
     * Get the current sync status.
     *
     * @return 'idle'|'syncing'|'failed'|'queued'
     */
    public function getSyncStatus(Workspace $workspace): string;
}
