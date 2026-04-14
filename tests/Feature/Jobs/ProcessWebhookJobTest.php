<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Actions\UpsertWooCommerceOrderAction;
use App\Actions\UpsertWooCommerceProductAction;
use App\Jobs\ProcessWebhookJob;
use App\Models\Order;
use App\Models\Store;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessWebhookJobTest extends TestCase
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

    private function makeOrderPayload(array $overrides = []): array
    {
        return array_merge([
            'id'               => 1001,
            'number'           => '1001',
            'status'           => 'completed',
            'date_created_gmt' => now()->toIso8601String(),
            'currency'         => 'EUR',
            'total'            => '100.00',
            'subtotal'         => '90.00',
            'total_tax'        => '10.00',
            'shipping_total'   => '0.00',
            'discount_total'   => '0.00',
            'billing'          => ['email' => 'test@example.com', 'country' => 'DE'],
            'line_items'       => [],
            'meta_data'        => [],
        ], $overrides);
    }

    private function makeProductPayload(array $overrides = []): array
    {
        return array_merge([
            'id'           => 55,
            'name'         => 'Test Product',
            'sku'          => 'SKU-55',
            'price'        => '19.99',
            'status'       => 'publish',
            'stock_status' => 'instock',
            'type'         => 'simple',
            'categories'   => [],
        ], $overrides);
    }

    private function insertWebhookLog(string $event = 'order.created', string $status = 'pending'): int
    {
        return DB::table('webhook_logs')->insertGetId([
            'store_id'        => $this->store->id,
            'workspace_id'    => $this->workspace->id,
            'event'           => $event,
            'payload'         => json_encode(['id' => 1001]),
            'signature_valid' => true,
            'status'          => $status,
            'created_at'      => now()->toDateTimeString(),
            'updated_at'      => now()->toDateTimeString(),
        ]);
    }

    private function runJob(string $event, array $payload, int $logId): void
    {
        $job = new ProcessWebhookJob(
            webhookLogId: $logId,
            storeId:      $this->store->id,
            workspaceId:  $this->workspace->id,
            event:        $event,
            payload:      $payload,
        );
        $job->handle(
            app(UpsertWooCommerceOrderAction::class),
            app(UpsertWooCommerceProductAction::class),
        );
    }

    // -------------------------------------------------------------------------
    // Order events
    // -------------------------------------------------------------------------

    public function test_order_created_upserts_order(): void
    {
        $logId = $this->insertWebhookLog('order.created');

        $this->runJob('order.created', $this->makeOrderPayload(), $logId);

        $this->assertDatabaseHas('orders', [
            'store_id'    => $this->store->id,
            'external_id' => '1001',
            'status'      => 'completed',
        ]);
    }

    public function test_order_updated_upserts_order(): void
    {
        $logId = $this->insertWebhookLog('order.updated');

        $this->runJob('order.updated', $this->makeOrderPayload(['status' => 'processing']), $logId);

        $this->assertDatabaseHas('orders', [
            'external_id' => '1001',
            'status'      => 'processing',
        ]);
    }

    public function test_order_deleted_soft_cancels_order(): void
    {
        // Pre-insert an order
        DB::table('orders')->insert([
            'workspace_id' => $this->workspace->id,
            'store_id'     => $this->store->id,
            'external_id'  => '1001',
            'status'       => 'completed',
            'currency'     => 'EUR',
            'total'        => 100,
            'subtotal'     => 90,
            'tax'          => 10,
            'shipping'     => 0,
            'discount'     => 0,
            'occurred_at'  => now(),
            'synced_at'    => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $logId = $this->insertWebhookLog('order.deleted');

        $this->runJob('order.deleted', ['id' => 1001], $logId);

        $this->assertDatabaseHas('orders', [
            'external_id' => '1001',
            'status'      => 'cancelled',
        ]);
    }

    public function test_order_deleted_noop_when_order_not_found(): void
    {
        $logId = $this->insertWebhookLog('order.deleted');

        // Should not throw even though there's no matching order
        $this->runJob('order.deleted', ['id' => 99999], $logId);

        $this->assertTrue(true); // reached without exception
    }

    // -------------------------------------------------------------------------
    // Product events
    // -------------------------------------------------------------------------

    public function test_product_updated_upserts_product(): void
    {
        $logId = $this->insertWebhookLog('product.updated');

        $this->runJob('product.updated', $this->makeProductPayload(), $logId);

        $this->assertDatabaseHas('products', [
            'store_id'    => $this->store->id,
            'external_id' => '55',
            'name'        => 'Test Product',
        ]);
    }

    // -------------------------------------------------------------------------
    // WebhookLog status updates
    // -------------------------------------------------------------------------

    public function test_marks_webhook_log_processed_on_success(): void
    {
        $logId = $this->insertWebhookLog('order.created');

        $this->runJob('order.created', $this->makeOrderPayload(), $logId);

        $this->assertDatabaseHas('webhook_logs', [
            'id'     => $logId,
            'status' => 'processed',
        ]);

        $log = DB::table('webhook_logs')->where('id', $logId)->first();
        $this->assertNotNull($log->processed_at);
    }

    public function test_marks_webhook_log_failed_on_exception(): void
    {
        $logId = $this->insertWebhookLog('order.created');

        // Pass a payload missing the required workspace relationship — the workspace
        // lookup for reporting_currency will succeed, but we can force failure by
        // pointing the job at a non-existent workspace so the order action throws.
        $job = new ProcessWebhookJob(
            webhookLogId: $logId,
            storeId:      $this->store->id,
            workspaceId:  99999, // non-existent → store->workspace will be null
            event:        'order.created',
            payload:      $this->makeOrderPayload(),
        );

        try {
            $job->handle(
                app(UpsertWooCommerceOrderAction::class),
                app(UpsertWooCommerceProductAction::class),
            );
        } catch (\Throwable) {
            // Exception is re-thrown by design; swallow for test assertion
        }

        $this->assertDatabaseHas('webhook_logs', [
            'id'     => $logId,
            'status' => 'failed',
        ]);
    }

    public function test_updates_store_last_synced_at_on_success(): void
    {
        $before = now()->subMinute();
        $logId  = $this->insertWebhookLog('order.created');

        $this->runJob('order.created', $this->makeOrderPayload(), $logId);

        $store = DB::table('stores')->where('id', $this->store->id)->first();
        $this->assertNotNull($store->last_synced_at);
        $this->assertTrue(\Carbon\Carbon::parse($store->last_synced_at)->isAfter($before));
    }

    // -------------------------------------------------------------------------
    // Deduplication
    // -------------------------------------------------------------------------

    public function test_deduplication_skips_repeated_event_within_24_hours(): void
    {
        // Simulate a previously processed delivery for the same entity
        DB::table('webhook_logs')->insert([
            'store_id'        => $this->store->id,
            'workspace_id'    => $this->workspace->id,
            'event'           => 'order.created',
            'payload'         => json_encode(['id' => 1001]),
            'signature_valid' => true,
            'status'          => 'processed',
            'processed_at'    => now()->toDateTimeString(),
            'created_at'      => now()->subHours(2)->toDateTimeString(),
            'updated_at'      => now()->toDateTimeString(),
        ]);

        $logId = $this->insertWebhookLog('order.created');
        $this->runJob('order.created', $this->makeOrderPayload(['id' => 1001]), $logId);

        // The job should skip processing — no order upserted
        $this->assertDatabaseCount('orders', 0);

        // But the log should still be marked processed
        $this->assertDatabaseHas('webhook_logs', [
            'id'     => $logId,
            'status' => 'processed',
        ]);
    }

    public function test_deduplication_does_not_skip_after_24_hours(): void
    {
        // Previous processed log is 25h old — outside deduplication window
        DB::table('webhook_logs')->insert([
            'store_id'        => $this->store->id,
            'workspace_id'    => $this->workspace->id,
            'event'           => 'order.created',
            'payload'         => json_encode(['id' => 1001]),
            'signature_valid' => true,
            'status'          => 'processed',
            'processed_at'    => now()->subHours(25)->toDateTimeString(),
            'created_at'      => now()->subHours(25)->toDateTimeString(),
            'updated_at'      => now()->subHours(25)->toDateTimeString(),
        ]);

        $logId = $this->insertWebhookLog('order.created');
        $this->runJob('order.created', $this->makeOrderPayload(['id' => 1001]), $logId);

        // Should have processed normally
        $this->assertDatabaseHas('orders', ['external_id' => '1001']);
    }

    // -------------------------------------------------------------------------
    // Store not found
    // -------------------------------------------------------------------------

    public function test_store_not_found_marks_log_failed(): void
    {
        $logId = $this->insertWebhookLog('order.created');

        $job = new ProcessWebhookJob(
            webhookLogId: $logId,
            storeId:      99999, // non-existent store
            workspaceId:  $this->workspace->id,
            event:        'order.created',
            payload:      $this->makeOrderPayload(),
        );
        $job->handle(
            app(UpsertWooCommerceOrderAction::class),
            app(UpsertWooCommerceProductAction::class),
        );

        $this->assertDatabaseHas('webhook_logs', [
            'id'     => $logId,
            'status' => 'failed',
        ]);
    }
}
