<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time backfill: insert the 4 default anomaly_rules rows for every workspace
 * that currently has zero anomaly_rules.
 *
 * Safe to run multiple times — workspaces that already have rules are skipped.
 *
 * Default thresholds match the values seeded by CreateWorkspaceAction for new
 * workspaces going forward.
 *
 * Usage:
 *   php artisan anomaly:seed-rules
 *
 * Writes: anomaly_rules
 * Called by: CLI (one-time backfill)
 *
 * @see app/Actions/CreateWorkspaceAction.php::seedDefaultAnomalyRules()
 * @see docs/planning/schema.md §1.10 anomaly_rules
 */
class SeedAnomalyRulesCommand extends Command
{
    protected $signature = 'anomaly:seed-rules';

    protected $description = 'Insert default anomaly rules for workspaces that have none (one-time backfill).';

    public function handle(): int
    {
        // Fetch all non-deleted workspace IDs that have no anomaly_rules rows.
        // Use DB:: directly (no Eloquent global scope) — this is a CLI backfill.
        $workspaceIds = DB::table('workspaces')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('anomaly_rules')
                    ->whereColumn('anomaly_rules.workspace_id', 'workspaces.id');
            })
            ->pluck('id');

        if ($workspaceIds->isEmpty()) {
            $this->info('All workspaces already have anomaly rules. Nothing to do.');
            return self::SUCCESS;
        }

        $this->info("Seeding anomaly rules for {$workspaceIds->count()} workspace(s)…");

        $inserted = 0;

        foreach ($workspaceIds as $workspaceId) {
            $now = now()->toDateTimeString();

            DB::table('anomaly_rules')->insert([
                [
                    'workspace_id'      => $workspaceId,
                    'rule_type'         => 'real_vs_store_delta',
                    'threshold_value'   => 15,
                    'threshold_unit'    => 'percent',
                    'enabled'           => true,
                    'delivery_channels' => '[]',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
                [
                    'workspace_id'      => $workspaceId,
                    'rule_type'         => 'platform_overreport',
                    'threshold_value'   => 20,
                    'threshold_unit'    => 'percent',
                    'enabled'           => true,
                    'delivery_channels' => '[]',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
                [
                    'workspace_id'      => $workspaceId,
                    'rule_type'         => 'ad_spend_dod',
                    'threshold_value'   => 50,
                    'threshold_unit'    => 'percent',
                    'enabled'           => true,
                    'delivery_channels' => '[]',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
                [
                    'workspace_id'      => $workspaceId,
                    'rule_type'         => 'integration_down',
                    'threshold_value'   => 4,
                    'threshold_unit'    => 'hours',
                    'enabled'           => true,
                    'delivery_channels' => '[]',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
            ]);

            $this->line("  workspace_id={$workspaceId} — 4 rules inserted.");
            $inserted++;
        }

        $this->info("Done. {$inserted} workspace(s) seeded.");

        return self::SUCCESS;
    }
}
