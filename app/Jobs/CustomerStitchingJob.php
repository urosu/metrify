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
 * Upserts one customers row per unique (workspace_id, customer_email_hash) pair
 * found in orders, then back-fills orders.customer_id.
 *
 * Safe to re-run — uses INSERT … ON CONFLICT DO UPDATE (idempotent).
 *
 * Reads:  orders (workspace_id, customer_email_hash, store_id, occurred_at,
 *                 total, total_in_reporting_currency, customer_country,
 *                 attribution_source, is_first_for_customer, status)
 * Writes: customers (upsert), orders.customer_id (back-fill)
 *
 * Queue:     low
 * Tries:     3
 * Timeout:   600 s
 *
 * Dispatched by: WooCommerceHistoricalImportJob, ShopifyHistoricalImportJob
 *                (after the import + snapshot phase completes)
 *
 * @see app/Models/Customer.php
 * @see app/Services/Customers/CustomersDataService.php
 * @see docs/planning/schema.md#1-per-table-reference
 */
class CustomerStitchingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 3;

    public function __construct(public readonly int $workspaceId)
    {
        $this->onQueue('low');
    }

    public function handle(WorkspaceContext $context): void
    {
        $context->set($this->workspaceId);
        $now = now()->toDateTimeString();

        Log::info('CustomerStitchingJob: starting', ['workspace_id' => $this->workspaceId]);

        // Step 1: Upsert one customers row per email_hash.
        // Aggregates across ALL orders for this workspace, not just one store,
        // because a customer may have ordered from multiple stores.
        // Uses the store_id of the customer's FIRST order for the FK.
        //
        // Status filter: only 'completed' and 'processing' orders count toward
        // customer LTV — pending and cancelled should not inflate the record.
        DB::statement("
            INSERT INTO customers (
                workspace_id, store_id, email_hash,
                first_order_at, last_order_at, orders_count,
                lifetime_value_native, lifetime_value_reporting,
                country, acquisition_source,
                created_at, updated_at
            )
            SELECT
                o.workspace_id,
                (ARRAY_AGG(o.store_id ORDER BY o.occurred_at ASC))[1]  AS store_id,
                o.customer_email_hash                                   AS email_hash,
                MIN(o.occurred_at)                                      AS first_order_at,
                MAX(o.occurred_at)                                      AS last_order_at,
                COUNT(*)                                                AS orders_count,
                COALESCE(SUM(o.total), 0)                              AS lifetime_value_native,
                COALESCE(SUM(o.total_in_reporting_currency), 0)        AS lifetime_value_reporting,
                (ARRAY_AGG(o.customer_country ORDER BY o.occurred_at ASC) FILTER (WHERE o.customer_country IS NOT NULL))[1] AS country,
                (ARRAY_AGG(o.attribution_source ORDER BY o.occurred_at ASC) FILTER (WHERE o.is_first_for_customer = TRUE))[1] AS acquisition_source,
                ? AS created_at,
                ? AS updated_at
            FROM orders o
            WHERE o.workspace_id = ?
              AND o.customer_email_hash IS NOT NULL
              AND o.status IN ('completed', 'processing')
            GROUP BY o.workspace_id, o.customer_email_hash
            ON CONFLICT (workspace_id, email_hash) DO UPDATE SET
                store_id                 = EXCLUDED.store_id,
                first_order_at           = LEAST(customers.first_order_at, EXCLUDED.first_order_at),
                last_order_at            = GREATEST(customers.last_order_at, EXCLUDED.last_order_at),
                orders_count             = EXCLUDED.orders_count,
                lifetime_value_native    = EXCLUDED.lifetime_value_native,
                lifetime_value_reporting = EXCLUDED.lifetime_value_reporting,
                country                  = COALESCE(EXCLUDED.country, customers.country),
                acquisition_source       = COALESCE(customers.acquisition_source, EXCLUDED.acquisition_source),
                updated_at               = EXCLUDED.updated_at
        ", [$now, $now, $this->workspaceId]);

        // Step 2: Back-fill orders.customer_id where it's still null.
        // Joins on (workspace_id, email_hash) to avoid cross-workspace contamination.
        DB::statement("
            UPDATE orders o
            SET customer_id = c.id
            FROM customers c
            WHERE c.workspace_id = o.workspace_id
              AND c.email_hash    = o.customer_email_hash
              AND o.workspace_id  = ?
              AND o.customer_id  IS NULL
        ", [$this->workspaceId]);

        $count = DB::table('customers')->where('workspace_id', $this->workspaceId)->count();

        Log::info('CustomerStitchingJob: done', [
            'workspace_id' => $this->workspaceId,
            'customers'    => $count,
        ]);
    }
}
