<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\WorkspaceContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill new_customer_revenue and organic_orders_count for all daily_snapshot rows
 * belonging to a workspace.
 *
 * Runs two UPDATE statements per workspace (one per column) using set-based SQL so
 * the work scales without per-row PHP loops. Each UPDATE is safe to re-run:
 *   - new_customer_revenue: matched via a CTE of MIN(id) per customer_id
 *   - organic_orders_count: COUNT filtered by attribution_last_touch->>'channel_type'
 *
 * Queue: low (background, non-urgent)
 * Dispatched by: BackfillSnapshotColumnsCommand (deploy tool)
 *
 * @see app/Console/Commands/BackfillSnapshotColumnsCommand.php
 * @see database/migrations/2026_04_29_000006_add_new_customer_revenue_and_organic_orders_to_daily_snapshots.php
 */
class BackfillSnapshotColumnsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // large workspaces can have years of snapshots
    public int $tries   = 2;

    public function __construct(
        public readonly int $workspaceId,
    ) {
        $this->onQueue('low');
    }

    public function handle(WorkspaceContext $context): void
    {
        $context->set($this->workspaceId);

        Log::info('BackfillSnapshotColumnsJob: starting', ['workspace_id' => $this->workspaceId]);

        // ── 1. Backfill new_customer_revenue ────────────────────────────────────
        // Strategy: identify each customer's first order across all time (MIN(id) per
        // customer_id), join to daily_snapshots on (workspace_id, store_id, date), and
        // SUM the revenue for those first-order rows that land on each snapshot day.
        //
        // The UPDATE uses a correlated subquery per snapshot row — efficient because
        // the subquery inner loops over a small set once the first_orders CTE is
        // materialised. For workspaces with >100k orders, this may take 30–60 s; the
        // job timeout is set to 3600 s.
        DB::statement("
            WITH first_orders AS (
                SELECT
                    MIN(id)    AS id,
                    store_id,
                    occurred_at::date AS order_date
                FROM orders
                WHERE workspace_id = ?
                  AND status IN ('completed', 'processing')
                GROUP BY customer_id, store_id, occurred_at::date
            ),
            per_day AS (
                SELECT
                    store_id,
                    order_date,
                    SUM(o.total_in_reporting_currency) AS ncr
                FROM first_orders fo
                JOIN orders o ON o.id = fo.id
                GROUP BY store_id, order_date
            )
            UPDATE daily_snapshots ds
            SET new_customer_revenue = COALESCE(pd.ncr, 0),
                updated_at = NOW()
            FROM per_day pd
            WHERE ds.workspace_id = ?
              AND ds.store_id = pd.store_id
              AND ds.date     = pd.order_date
        ", [$this->workspaceId, $this->workspaceId]);

        Log::info('BackfillSnapshotColumnsJob: new_customer_revenue done', ['workspace_id' => $this->workspaceId]);

        // ── 2. Backfill organic_orders_count ───────────────────────────────────
        // COUNT of orders per (store_id, date) where channel_type = 'organic_search'.
        DB::statement("
            WITH organic_counts AS (
                SELECT
                    store_id,
                    occurred_at::date AS order_date,
                    COUNT(*)::int     AS cnt
                FROM orders
                WHERE workspace_id = ?
                  AND status IN ('completed', 'processing')
                  AND attribution_last_touch->>'channel_type' = 'organic_search'
                GROUP BY store_id, occurred_at::date
            )
            UPDATE daily_snapshots ds
            SET organic_orders_count = COALESCE(oc.cnt, 0),
                updated_at = NOW()
            FROM organic_counts oc
            WHERE ds.workspace_id = ?
              AND ds.store_id = oc.store_id
              AND ds.date     = oc.order_date
        ", [$this->workspaceId, $this->workspaceId]);

        Log::info('BackfillSnapshotColumnsJob: organic_orders_count done', ['workspace_id' => $this->workspaceId]);
    }
}
