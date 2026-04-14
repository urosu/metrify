<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AdAccount;
use App\Models\AdInsight;
use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;

class RemoveAdAccountAction
{
    /**
     * Permanently remove an ad account and all its data.
     *
     * ad_insights uses nullOnDelete (SET NULL), so we delete those explicitly
     * before removing the account. campaigns/adsets/ads cascade automatically.
     *
     * Updates workspace.has_ads after removal.
     * See: PLANNING.md "Billing basis auto-derivation"
     */
    public function handle(AdAccount $adAccount): void
    {
        $workspaceId = (int) $adAccount->workspace_id;

        DB::transaction(function () use ($adAccount): void {
            AdInsight::where('ad_account_id', $adAccount->id)->delete();

            SyncLog::where('syncable_type', AdAccount::class)
                ->where('syncable_id', $adAccount->id)
                ->delete();

            $adAccount->delete();
        });

        // Why: has_ads drives billing basis (for non-ecom workspaces) + nav visibility.
        $remainingAccounts = AdAccount::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->count();

        DB::table('workspaces')
            ->where('id', $workspaceId)
            ->update(['has_ads' => $remainingAccounts > 0]);
    }
}
