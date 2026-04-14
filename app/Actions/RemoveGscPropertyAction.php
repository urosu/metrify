<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SearchConsoleProperty;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;

// Related: app/Http/Controllers/GoogleOAuthController.php (sets has_gsc flag on connect)

class RemoveGscPropertyAction
{
    /**
     * Permanently remove a GSC property and all its data.
     *
     * FK ON DELETE CASCADE handles: gsc_daily_stats, gsc_queries, gsc_pages.
     * sync_logs is polymorphic (no FK), so we delete those explicitly.
     *
     * Updates workspace.has_gsc after removal.
     */
    public function handle(SearchConsoleProperty $property): void
    {
        $workspaceId = (int) $property->workspace_id;

        DB::transaction(function () use ($property): void {
            SyncLog::where('syncable_type', SearchConsoleProperty::class)
                ->where('syncable_id', $property->id)
                ->delete();

            $property->delete();
        });

        $remainingProperties = SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->count();

        DB::table('workspaces')
            ->where('id', $workspaceId)
            ->update(['has_gsc' => $remainingProperties > 0]);
    }
}
