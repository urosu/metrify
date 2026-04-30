<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds daily_source_disagreements — per-channel, per-day reconciliation rows.
 *
 * Derives store_claim directly from orders, platform_claim from ad_insights
 * (campaign-level only per CLAUDE.md constraint), and computes real_revenue +
 * delta columns. Mimics SourceReconciliationService but is seeder-safe
 * (does not require live OAuth credentials or the full service chain).
 *
 * Written by: SourceReconciliationService::reconcile() in production.
 * Seeded here: mirrors the service logic for demo data.
 *
 * @see docs/planning/schema.md §1.X daily_source_disagreements
 * @see app/Services/Reconciliation/SourceReconciliationService.php
 */
class DisagreementsSeeder extends Seeder
{
    private const AD_CHANNELS  = ['facebook', 'google'];
    private const ALL_CHANNELS = ['facebook', 'google', 'gsc', 'direct', 'organic', 'email'];

    public function run(): void
    {
        $workspace = DB::table('workspaces')->where('slug', 'demo-store')->first();
        if (! $workspace) {
            return;
        }

        $stores = DB::table('stores')
            ->where('workspace_id', $workspace->id)
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        if (empty($stores)) {
            return;
        }

        $from    = now()->subDays(90)->toDateString();
        $to      = now()->toDateString();
        $syncedAt = now()->toDateTimeString();

        // ── Store claims per (store, date, channel) ───────────────────────────
        $storeClaims = DB::select("
            SELECT
                store_id,
                DATE(occurred_at) AS date,
                LOWER(COALESCE(attribution_source, 'direct')) AS channel,
                SUM(COALESCE(total_in_reporting_currency, total)) AS store_claim
            FROM orders
            WHERE workspace_id = ?
              AND DATE(occurred_at) BETWEEN ? AND ?
              AND status NOT IN ('cancelled','refunded')
            GROUP BY store_id, DATE(occurred_at), channel
        ", [$workspace->id, $from, $to]);

        $storeClaimMap = [];
        foreach ($storeClaims as $row) {
            $storeClaimMap[$row->store_id][$row->date][$row->channel] = (float) $row->store_claim;
        }

        // ── Platform claims per (platform, date) ──────────────────────────────
        // Use spend_in_reporting_currency as a proxy when platform_conversions_value
        // is not populated (as is the case in seed data from AdSeeder).
        $platformClaims = DB::select("
            SELECT
                aa.platform,
                ai.date::text AS date,
                SUM(COALESCE(
                    ai.platform_conversions_value,
                    ai.spend_in_reporting_currency * 3.2
                )) AS platform_claim
            FROM ad_insights ai
            JOIN ad_accounts aa ON aa.id = ai.ad_account_id
            WHERE ai.workspace_id = ?
              AND ai.level = 'campaign'
              AND ai.hour IS NULL
              AND ai.date BETWEEN ? AND ?
            GROUP BY aa.platform, ai.date
        ", [$workspace->id, $from, $to]);

        $platformClaimMap = [];
        foreach ($platformClaims as $row) {
            $platformClaimMap[$row->platform][$row->date] = (float) $row->platform_claim;
        }

        // ── Build disagreement rows ───────────────────────────────────────────
        $rows = [];
        $date = new \DateTime($from);
        $end  = new \DateTime($to);

        while ($date <= $end) {
            $dateStr = $date->format('Y-m-d');

            foreach ($stores as $storeId) {
                foreach (self::ALL_CHANNELS as $channel) {
                    $storeClaim    = $storeClaimMap[$storeId][$dateStr][$channel] ?? 0.0;
                    $platformClaim = null;

                    if (in_array($channel, self::AD_CHANNELS)) {
                        $platformClaim = $platformClaimMap[$channel][$dateStr] ?? null;
                        if ($platformClaim !== null) {
                            // Distribute proportionally across stores (single-store: full amount)
                            $platformClaim = round($platformClaim / count($stores), 2);
                        }
                    }

                    // Skip rows where both claims are zero (no signal)
                    if ($storeClaim == 0.0 && $platformClaim === null) {
                        continue;
                    }

                    $deltaAbs = $platformClaim !== null
                        ? round($platformClaim - $storeClaim, 2)
                        : null;
                    $deltaPct = ($deltaAbs !== null && $storeClaim > 0)
                        ? round(($deltaAbs / $storeClaim) * 100, 2)
                        : null;

                    $rows[] = [
                        'workspace_id'   => $workspace->id,
                        'store_id'       => $storeId,
                        'date'           => $dateStr,
                        'channel'        => $channel,
                        'store_claim'    => round($storeClaim, 2),
                        'platform_claim' => $platformClaim,
                        'real_revenue'   => round($storeClaim, 2), // v1: store = ground truth
                        'delta_abs'      => $deltaAbs,
                        'delta_pct'      => $deltaPct,
                        'match_confidence' => null,
                        'synced_at'      => $syncedAt,
                        'created_at'     => $syncedAt,
                        'updated_at'     => $syncedAt,
                    ];
                }
            }

            $date->modify('+1 day');
        }

        // Upsert in chunks of 500 on the unique key
        $inserted = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('daily_source_disagreements')->upsert(
                $chunk,
                ['workspace_id', 'store_id', 'date', 'channel'],
                ['store_claim', 'platform_claim', 'real_revenue', 'delta_abs', 'delta_pct', 'synced_at', 'updated_at'],
            );
            $inserted += count($chunk);
        }

        $count = DB::table('daily_source_disagreements')
            ->where('workspace_id', $workspace->id)
            ->count();
        $this->command?->line("  daily_source_disagreements seeded: {$count}");
    }
}
