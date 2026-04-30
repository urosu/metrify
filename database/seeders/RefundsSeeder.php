<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds refunds table — one row per refunded order + partial refunds on ~2% of
 * completed/processing orders (customer returns, discount adjustments).
 *
 * ~5% refund rate matches WooCommerce industry average for tech accessories.
 *
 * Reads:  orders (status='refunded' or rand() < 0.02 for partials on completed)
 * Writes: refunds
 *
 * @see docs/planning/schema.md §1.4 refunds
 */
class RefundsSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = DB::table('workspaces')->where('slug', 'demo-store')->first();
        if (! $workspace) {
            return;
        }

        $refundedOrders = DB::table('orders')
            ->where('workspace_id', $workspace->id)
            ->where('status', 'refunded')
            ->select('id', 'total', 'occurred_at')
            ->get();

        // Also get ~2% of completed/processing orders for partial refunds
        $completedOrders = DB::table('orders')
            ->where('workspace_id', $workspace->id)
            ->whereIn('status', ['completed', 'processing'])
            ->select('id', 'total', 'occurred_at')
            ->get()
            ->filter(fn () => mt_rand(0, 100) < 2); // ~2% partial refund rate

        $rows = [];
        $now  = now()->toDateTimeString();
        $counter = 1;

        foreach ($refundedOrders as $order) {
            $refundedAt = (new \Carbon\Carbon($order->occurred_at))->addDays(rand(1, 14));
            $rows[] = [
                'order_id'            => $order->id,
                'workspace_id'        => $workspace->id,
                'platform_refund_id'  => 'ref_' . str_pad((string) $counter, 7, '0', STR_PAD_LEFT),
                'amount'              => $order->total,
                'reason'              => $this->randomReason(),
                'refunded_by_id'      => null,
                'refunded_at'         => $refundedAt->toDateTimeString(),
                'raw_meta'            => json_encode(['line_items' => []]),
                'raw_meta_api_version' => 'wc/v3',
                'created_at'          => $refundedAt->toDateTimeString(),
            ];
            $counter++;
        }

        foreach ($completedOrders as $order) {
            $refundedAt = (new \Carbon\Carbon($order->occurred_at))->addDays(rand(3, 30));
            $partialAmount = round($order->total * mt_rand(20, 80) / 100, 2);
            $rows[] = [
                'order_id'            => $order->id,
                'workspace_id'        => $workspace->id,
                'platform_refund_id'  => 'ref_' . str_pad((string) $counter, 7, '0', STR_PAD_LEFT),
                'amount'              => $partialAmount,
                'reason'              => $this->randomReason(),
                'refunded_by_id'      => null,
                'refunded_at'         => $refundedAt->toDateTimeString(),
                'raw_meta'            => json_encode(['partial' => true, 'line_items' => []]),
                'raw_meta_api_version' => 'wc/v3',
                'created_at'          => $refundedAt->toDateTimeString(),
            ];
            $counter++;
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('refunds')->insertOrIgnore($chunk);
        }

        $count = DB::table('refunds')->where('workspace_id', $workspace->id)->count();
        $this->command?->line("  refunds seeded: {$count}");
    }

    private function randomReason(): string
    {
        $reasons = [
            'Customer request — product not as described',
            'Defective item on arrival',
            'Wrong item shipped',
            'Customer cancelled before dispatch',
            'Duplicate order',
            'Partial goodwill refund — delayed delivery',
            'Returned — changed mind',
            'Price adjustment — discount applied retroactively',
        ];
        return $reasons[array_rand($reasons)];
    }
}
