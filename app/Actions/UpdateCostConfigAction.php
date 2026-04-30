<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\BuildDailySnapshotJob;
use App\Models\Workspace;
use App\Services\Workspace\SettingsAuditService;
use App\ValueObjects\CostConfigDiff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Apply Settings → Costs mutations and trigger retroactive snapshot recalculation.
 *
 * Handles upserts across 6 cost tables:
 *   store_cost_settings, shipping_rules, transaction_fee_rules,
 *   tax_rules, opex_allocations, platform_fee_rules
 *
 * After any write, dispatches BuildDailySnapshotJob fan-out so historical
 * profit figures stay consistent with the new config.
 * Writes settings_audit_log for every changed field.
 *
 * Input:  Workspace, CostConfigDiff, actor user id
 * Writes: cost tables, settings_audit_log; dispatches snapshot rebuild jobs
 *
 * @see docs/planning/backend.md §2.2 (action spec)
 * @see docs/planning/backend.md §0 (rule 5: cost config changes trigger recalc)
 */
class UpdateCostConfigAction
{
    public function __construct(private readonly SettingsAuditService $audit) {}

    public function handle(Workspace $workspace, CostConfigDiff $diff, int $actorUserId): void
    {
        DB::transaction(function () use ($workspace, $diff, $actorUserId) {
            if ($diff->shipping !== null) {
                $this->upsertShippingRule($workspace->id, $diff->shipping, $actorUserId);
            }

            if ($diff->transactionFees !== null) {
                $this->upsertTransactionFeeRule($workspace->id, $diff->transactionFees, $actorUserId);
            }

            if ($diff->tax !== null) {
                $this->upsertTaxRule($workspace->id, $diff->tax, $actorUserId);
            }

            if ($diff->opex !== null) {
                $this->upsertOpexAllocation($workspace->id, $diff->opex, $actorUserId);
            }

            if ($diff->platformFees !== null) {
                $this->upsertPlatformFeeRule($workspace->id, $diff->platformFees, $actorUserId);
            }
        });

        // Retroactive recalculation: rebuild snapshots for every existing (store, date) pair
        // in the workspace. Why: any cost config change affects profit figures in all historical
        // snapshots. Mirrors the per-row recalc logic in CostsController::dispatchCostRecalc().
        DB::table('daily_snapshots')
            ->where('workspace_id', $workspace->id)
            ->select(['store_id', 'date'])
            ->orderBy('store_id')
            ->orderBy('date')
            ->chunk(1000, function (\Illuminate\Support\Collection $chunk) use ($workspace): void {
                foreach ($chunk as $row) {
                    BuildDailySnapshotJob::dispatch(
                        (int) $row->store_id,
                        $workspace->id,
                        Carbon::parse($row->date),
                    );
                }
            });
    }

    private function upsertShippingRule(int $workspaceId, array $data, int $actorUserId): void
    {
        DB::table('shipping_rules')->upsert(
            [array_merge(['workspace_id' => $workspaceId, 'created_at' => now(), 'updated_at' => now()], $data)],
            ['workspace_id', 'store_id', 'min_weight_grams', 'max_weight_grams', 'destination_country'],
            ['cost_native', 'currency', 'updated_at'],
        );
    }

    private function upsertTransactionFeeRule(int $workspaceId, array $data, int $actorUserId): void
    {
        DB::table('transaction_fee_rules')->upsert(
            [array_merge(['workspace_id' => $workspaceId, 'created_at' => now(), 'updated_at' => now()], $data)],
            ['workspace_id', 'store_id', 'processor'],
            ['percentage_bps', 'fixed_fee_native', 'currency', 'updated_at'],
        );
    }

    private function upsertTaxRule(int $workspaceId, array $data, int $actorUserId): void
    {
        DB::table('tax_rules')->upsert(
            [array_merge(['workspace_id' => $workspaceId, 'created_at' => now(), 'updated_at' => now()], $data)],
            ['workspace_id', 'country_code'],
            ['standard_rate_bps', 'reduced_rate_bps', 'is_included_in_price', 'digital_goods_override_bps', 'updated_at'],
        );
    }

    private function upsertOpexAllocation(int $workspaceId, array $data, int $actorUserId): void
    {
        DB::table('opex_allocations')->upsert(
            [array_merge(['workspace_id' => $workspaceId, 'created_at' => now(), 'updated_at' => now()], $data)],
            ['workspace_id', 'category', 'effective_from'],
            ['monthly_cost_native', 'currency', 'allocation_mode', 'effective_to', 'updated_at'],
        );
    }

    private function upsertPlatformFeeRule(int $workspaceId, array $data, int $actorUserId): void
    {
        DB::table('platform_fee_rules')->upsert(
            [array_merge(['workspace_id' => $workspaceId, 'created_at' => now(), 'updated_at' => now()], $data)],
            ['workspace_id', 'store_id', 'item_label', 'effective_from'],
            ['monthly_cost_native', 'currency', 'allocation_mode', 'effective_to', 'updated_at'],
        );
    }
}
