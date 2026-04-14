<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DailySnapshot;
use App\Models\HourlySnapshot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SnapshotSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'demo-store')->first();
        $stores    = Store::where('workspace_id', $workspace->id)->get();

        foreach ($stores as $store) {
            $this->seedDailySnapshots($workspace->id, $store->id);
            $this->seedHourlySnapshots($workspace->id, $store->id);
        }
    }

    private function seedDailySnapshots(int $workspaceId, int $storeId): void
    {
        $orders = Order::where('workspace_id', $workspaceId)
            ->where('store_id', $storeId)
            ->whereIn('status', ['completed', 'processing'])
            ->select('id', 'total_in_reporting_currency', 'total', 'customer_email_hash', 'occurred_at')
            ->get();

        $byDate = $orders->groupBy(fn ($o) => $o->occurred_at->toDateString());

        // Track first appearance per email hash for new/returning customer counts.
        $seenHashes = [];

        foreach ($byDate as $date => $dayOrders) {
            $orderIds      = $dayOrders->pluck('id')->toArray();
            $revenue       = $dayOrders->sum('total_in_reporting_currency');
            $revenueNative = $dayOrders->sum('total');
            $count         = $dayOrders->count();

            $newCustomers       = 0;
            $returningCustomers = 0;
            foreach ($dayOrders as $order) {
                if ($order->customer_email_hash === null) {
                    continue;
                }
                if (! isset($seenHashes[$order->customer_email_hash])) {
                    $seenHashes[$order->customer_email_hash] = true;
                    $newCustomers++;
                } else {
                    $returningCustomers++;
                }
            }

            // All OrderItem queries go through order_id — workspace_id/store_id are not
            // columns on order_items (derivable via order_id). See: OrderItem model.
            $totalItems = OrderItem::whereIn('order_id', $orderIds)->sum('quantity');

            DailySnapshot::updateOrCreate(
                ['store_id' => $storeId, 'date' => $date],
                [
                    'workspace_id'        => $workspaceId,
                    'orders_count'        => $count,
                    'revenue'             => round($revenue, 4),
                    'revenue_native'      => round($revenueNative, 4),
                    'aov'                 => $count > 0 ? round($revenue / $count, 4) : null,
                    'items_sold'          => (int) $totalItems,
                    'items_per_order'     => $count > 0 ? round($totalItems / $count, 2) : null,
                    'new_customers'       => $newCustomers,
                    'returning_customers' => $returningCustomers,
                ]
            );

            // Populate daily_snapshot_products — mirrors ComputeDailySnapshotJob logic.
            // Related: app/Jobs/ComputeDailySnapshotJob.php (production path for this data)
            $productRows = DB::table('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->selectRaw("
                    oi.product_external_id,
                    MAX(oi.product_name) AS product_name,
                    SUM(oi.quantity)::int AS units,
                    SUM(oi.line_total * (o.total_in_reporting_currency / NULLIF(o.total, 0))) AS revenue
                ")
                ->where('o.store_id', $storeId)
                ->whereRaw("o.occurred_at::date = ?", [$date])
                ->whereIn('o.status', ['completed', 'processing'])
                ->whereNotNull('o.total_in_reporting_currency')
                ->groupBy('oi.product_external_id')
                ->orderByDesc('revenue')
                ->limit(50)
                ->get();

            if ($productRows->isNotEmpty()) {
                $now = now()->toDateTimeString();
                $upsertRows = $productRows->values()->map(function ($row, int $idx) use ($workspaceId, $storeId, $date, $now): array {
                    return [
                        'workspace_id'        => $workspaceId,
                        'store_id'            => $storeId,
                        'snapshot_date'       => $date,
                        'product_external_id' => $row->product_external_id,
                        'product_name'        => mb_substr((string) $row->product_name, 0, 500),
                        'revenue'             => round((float) $row->revenue, 4),
                        'units'               => (int) $row->units,
                        'rank'                => $idx + 1,
                        'created_at'          => $now,
                    ];
                })->all();

                DB::table('daily_snapshot_products')->upsert(
                    $upsertRows,
                    ['store_id', 'snapshot_date', 'product_external_id'],
                    ['product_name', 'revenue', 'units', 'rank'],
                );
            }
        }
    }

    private function seedHourlySnapshots(int $workspaceId, int $storeId): void
    {
        $orders = Order::where('workspace_id', $workspaceId)
            ->where('store_id', $storeId)
            ->whereIn('status', ['completed', 'processing'])
            ->where('occurred_at', '>=', now()->subDays(14))
            ->select('total_in_reporting_currency', 'occurred_at')
            ->get();

        $byDateHour = $orders->groupBy(function ($o) {
            return $o->occurred_at->toDateString() . '_' . $o->occurred_at->hour;
        });

        foreach ($byDateHour as $key => $hourOrders) {
            [$date, $hour] = explode('_', $key);

            HourlySnapshot::updateOrCreate(
                ['store_id' => $storeId, 'date' => $date, 'hour' => (int) $hour],
                [
                    'workspace_id' => $workspaceId,
                    'orders_count' => $hourOrders->count(),
                    'revenue'      => round($hourOrders->sum('total_in_reporting_currency'), 4),
                ]
            );
        }
    }
}
