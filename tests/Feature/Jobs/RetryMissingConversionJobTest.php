<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\RetryMissingConversionJob;
use App\Models\FxRate;
use App\Models\Store;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RetryMissingConversionJobTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = Workspace::factory()->create(['reporting_currency' => 'EUR']);
        $this->store     = Store::factory()->create(['workspace_id' => $this->workspace->id]);

        app(WorkspaceContext::class)->set($this->workspace->id);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function insertOrder(array $overrides = []): int
    {
        return DB::table('orders')->insertGetId(array_merge([
            'workspace_id'                => $this->workspace->id,
            'store_id'                    => $this->store->id,
            'external_id'                 => uniqid('order-', true),
            'status'                      => 'completed',
            'currency'                    => 'EUR',
            'total'                       => 100.00,
            'subtotal'                    => 90.00,
            'tax'                         => 10.00,
            'shipping'                    => 5.00,
            'discount'                    => 0.00,
            'total_in_reporting_currency' => null,
            'occurred_at'                 => now(),
            'synced_at'                   => now(),
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ], $overrides));
    }

    private function runJob(): void
    {
        (new RetryMissingConversionJob())->handle(app(\App\Services\Fx\FxRateService::class));
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_fills_null_total_in_reporting_currency_when_rate_available(): void
    {
        // EUR order, EUR reporting → same-currency path (no rate lookup needed)
        $orderId = $this->insertOrder(['currency' => 'EUR', 'total' => 150.00]);

        $this->runJob();

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertNotNull($order->total_in_reporting_currency);
        $this->assertEqualsWithDelta(150.00, (float) $order->total_in_reporting_currency, 0.01);
    }

    public function test_converts_foreign_currency_when_rate_available(): void
    {
        FxRate::factory()->create([
            'base_currency'   => 'EUR',
            'target_currency' => 'USD',
            'rate'            => 1.08,
            'date'            => today(),
        ]);

        $orderId = $this->insertOrder([
            'currency'   => 'USD',
            'total'      => 108.00,
            'occurred_at' => now(),
        ]);

        $this->runJob();

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertNotNull($order->total_in_reporting_currency);
        // USD 108 / rate(EUR→USD 1.08) = EUR 100
        $this->assertEqualsWithDelta(100.00, (float) $order->total_in_reporting_currency, 0.01);
    }

    public function test_leaves_null_when_fx_rate_unavailable(): void
    {
        // No FxRate in DB for USD
        $orderId = $this->insertOrder(['currency' => 'USD', 'total' => 100.00]);

        $this->runJob();

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertNull($order->total_in_reporting_currency);
    }

    public function test_skips_non_active_statuses(): void
    {
        foreach (['refunded', 'cancelled', 'other'] as $status) {
            $this->insertOrder([
                'external_id' => uniqid("order-{$status}-"),
                'status'      => $status,
                'currency'    => 'EUR',
                'total'       => 50.00,
            ]);
        }

        $this->runJob();

        // All three should remain NULL (not considered by the job)
        $count = DB::table('orders')
            ->whereNull('total_in_reporting_currency')
            ->count();
        $this->assertSame(3, $count);
    }

    public function test_processes_multiple_workspaces(): void
    {
        $workspace2 = Workspace::factory()->create(['reporting_currency' => 'EUR']);
        $store2     = Store::factory()->create(['workspace_id' => $workspace2->id]);

        $orderId1 = $this->insertOrder(['currency' => 'EUR', 'total' => 100.00]);
        $orderId2 = DB::table('orders')->insertGetId([
            'workspace_id'                => $workspace2->id,
            'store_id'                    => $store2->id,
            'external_id'                 => uniqid('order-ws2-'),
            'status'                      => 'completed',
            'currency'                    => 'EUR',
            'total'                       => 200.00,
            'subtotal'                    => 180.00,
            'tax'                         => 20.00,
            'shipping'                    => 0.00,
            'discount'                    => 0.00,
            'total_in_reporting_currency' => null,
            'occurred_at'                 => now(),
            'synced_at'                   => now(),
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        $this->runJob();

        $order1 = DB::table('orders')->where('id', $orderId1)->first();
        $order2 = DB::table('orders')->where('id', $orderId2)->first();

        $this->assertNotNull($order1->total_in_reporting_currency);
        $this->assertNotNull($order2->total_in_reporting_currency);
    }

    public function test_skips_soft_deleted_workspace(): void
    {
        DB::table('workspaces')
            ->where('id', $this->workspace->id)
            ->update(['deleted_at' => now()->toDateTimeString()]);

        $orderId = $this->insertOrder(['currency' => 'EUR', 'total' => 100.00]);

        $this->runJob();

        // Workspace is soft-deleted — processWorkspace should bail out
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertNull($order->total_in_reporting_currency);
    }

    public function test_no_nulls_logs_and_returns_early(): void
    {
        // Insert an order WITH a conversion — nothing to process
        DB::table('orders')->insert([
            'workspace_id'                => $this->workspace->id,
            'store_id'                    => $this->store->id,
            'external_id'                 => 'order-already-converted',
            'status'                      => 'completed',
            'currency'                    => 'EUR',
            'total'                       => 50.00,
            'subtotal'                    => 45.00,
            'tax'                         => 5.00,
            'shipping'                    => 0.00,
            'discount'                    => 0.00,
            'total_in_reporting_currency' => 50.00,
            'occurred_at'                 => now(),
            'synced_at'                   => now(),
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        // Should complete without error
        $this->runJob();

        $this->assertTrue(true); // reached without exception
    }
}
