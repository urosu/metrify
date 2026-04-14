<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Store;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;

// Related: app/Actions/ConnectStoreAction.php (sets has_store flag on connect)

class RemoveStoreAction
{
    /**
     * Permanently remove a store and all its associated data.
     *
     * FK ON DELETE CASCADE handles: orders, order_items, products,
     * daily_snapshots, hourly_snapshots, webhook_logs, alerts.
     * sync_logs is polymorphic (no FK), so we delete those explicitly.
     *
     * Updates workspace.has_store after removal.
     * See: PLANNING.md "Billing basis auto-derivation"
     */
    public function handle(Store $store): void
    {
        $workspaceId = (int) $store->workspace_id;

        DB::transaction(function () use ($store): void {
            SyncLog::where('syncable_type', Store::class)
                ->where('syncable_id', $store->id)
                ->delete();

            $store->delete();
        });

        // Why: has_store drives billing basis + nav visibility. Recompute after every removal.
        $remainingStores = Store::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->count();

        DB::table('workspaces')
            ->where('id', $workspaceId)
            ->update(['has_store' => $remainingStores > 0]);
    }
}
