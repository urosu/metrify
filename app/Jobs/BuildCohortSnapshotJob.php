<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\WorkspaceContext;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Build daily_snapshot_cohorts rows for a workspace (full matrix rebuild).
 *
 * Rebuilds the last 36 months of cohort × offset data for ONE workspace in a
 * single SQL pass and upserts into daily_snapshot_cohorts.
 *
 * Channel filtering is NOT stored in this table — that is an intentional
 * tradeoff. The snapshot serves the all-channels default (80 % of page loads).
 * When a channel filter is active, CohortController falls back to the live
 * orders aggregate for that filtered path.
 *
 * ShouldBeUnique: multiple dispatches for the same workspace within the same
 * hour collapse into one job (uniqueFor = 3600). Safe to chain from
 * DispatchDailySnapshots per store — only one rebuild fires per workspace.
 *
 * Queue:   low
 * Timeout: 1200 s
 * Tries:   2
 *
 * @see app/Http/Controllers/CohortController.php
 * @see app/Models/DailySnapshotCohort.php
 */
class BuildCohortSnapshotJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 1200;
    public int $tries     = 2;
    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int    $workspaceId,
        public readonly Carbon $asOf,
    ) {
        $this->onQueue('low');
    }

    /** Unique lock key: one rebuild per workspace per hour. */
    public function uniqueId(): string
    {
        return (string) $this->workspaceId;
    }

    public function handle(WorkspaceContext $context): void
    {
        $context->set($this->workspaceId);

        // Build the full cohort × offset matrix for the last 36 months in one
        // SQL pass — mirrors the CohortController CTE but without a LIMIT on period.
        $rows = DB::select(<<<'SQL'
            WITH acquisitions AS (
                SELECT
                    customer_id,
                    DATE_TRUNC('month', occurred_at)::date AS cohort_period
                FROM orders
                WHERE workspace_id = :ws_id
                  AND is_first_for_customer = true
                  AND status NOT IN ('cancelled', 'refunded')
                  AND occurred_at >= (DATE_TRUNC('month', NOW()) - INTERVAL '36 months')
            ),
            cohort_data AS (
                SELECT
                    a.cohort_period,
                    ROUND(
                        EXTRACT(EPOCH FROM (
                            DATE_TRUNC('month', o.occurred_at) - a.cohort_period::timestamp
                        )) / (86400 * 30.44)
                    )::int                                  AS period_offset,
                    o.customer_id,
                    o.total_in_reporting_currency           AS revenue
                FROM orders o
                JOIN acquisitions a ON a.customer_id = o.customer_id
                WHERE o.workspace_id = :ws_id2
                  AND o.status NOT IN ('cancelled', 'refunded')
            )
            SELECT
                cohort_period,
                period_offset,
                SUM(revenue)                AS revenue,
                COUNT(*)                    AS orders_count,
                COUNT(DISTINCT customer_id) AS customers_active
            FROM cohort_data
            WHERE period_offset >= 0
              AND period_offset <= 36
            GROUP BY cohort_period, period_offset
            ORDER BY cohort_period, period_offset
        SQL, [
            'ws_id'  => $this->workspaceId,
            'ws_id2' => $this->workspaceId,
        ]);

        if (empty($rows)) {
            Log::info('BuildCohortSnapshotJob: no cohort data found', [
                'workspace_id' => $this->workspaceId,
            ]);
            return;
        }

        $now    = now()->toDateTimeString();
        $upsert = [];

        foreach ($rows as $r) {
            $upsert[] = [
                'workspace_id'    => $this->workspaceId,
                'cohort_period'   => $r->cohort_period,
                'period_offset'   => (int) $r->period_offset,
                'revenue'         => (float) $r->revenue,
                'orders_count'    => (int)   $r->orders_count,
                'customers_active'=> (int)   $r->customers_active,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        // Upsert in chunks to avoid hitting Postgres parameter limits on large datasets.
        foreach (array_chunk($upsert, 500) as $chunk) {
            DB::table('daily_snapshot_cohorts')->upsert(
                $chunk,
                ['workspace_id', 'cohort_period', 'period_offset'],
                ['revenue', 'orders_count', 'customers_active', 'updated_at'],
            );
        }

        Log::info('BuildCohortSnapshotJob: completed', [
            'workspace_id' => $this->workspaceId,
            'rows'         => count($upsert),
        ]);
    }
}
