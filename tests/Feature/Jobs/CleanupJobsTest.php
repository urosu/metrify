<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\CleanupOldSyncLogsJob;
use App\Jobs\CleanupOldWebhookLogsJob;
use App\Models\Store;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CleanupJobsTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        // Freeze time so the cutoff computed inside the job and the timestamps
        // inserted in tests are derived from the exact same instant.
        Carbon::setTestNow(Carbon::now());

        $this->workspace = Workspace::factory()->create();
        $this->store     = Store::factory()->create(['workspace_id' => $this->workspace->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function insertSyncLog(string $createdAt): void
    {
        DB::table('sync_logs')->insert([
            'workspace_id'  => $this->workspace->id,
            'syncable_type' => \App\Models\Store::class,
            'syncable_id'   => $this->store->id,
            'job_type'      => 'SyncStoreOrdersJob',
            'status'        => 'completed',
            'error_message' => null,
            'started_at'    => $createdAt,
            'completed_at'  => $createdAt,
            'created_at'    => $createdAt,
            'updated_at'    => $createdAt,
        ]);
    }

    private function insertWebhookLog(string $createdAt): void
    {
        DB::table('webhook_logs')->insert([
            'store_id'        => $this->store->id,
            'workspace_id'    => $this->workspace->id,
            'event'           => 'order.created',
            'payload'         => '{}',
            'signature_valid' => true,
            'status'          => 'processed',
            'created_at'      => $createdAt,
            'updated_at'      => $createdAt,
        ]);
    }

    // -------------------------------------------------------------------------
    // CleanupOldSyncLogsJob — 90-day retention
    // -------------------------------------------------------------------------

    public function test_deletes_sync_logs_older_than_90_days(): void
    {
        $this->insertSyncLog(now()->subDays(91)->toDateTimeString());

        (new CleanupOldSyncLogsJob())->handle();

        $this->assertDatabaseCount('sync_logs', 0);
    }

    public function test_preserves_sync_logs_within_90_days(): void
    {
        $this->insertSyncLog(now()->subDays(89)->toDateTimeString());

        (new CleanupOldSyncLogsJob())->handle();

        $this->assertDatabaseCount('sync_logs', 1);
    }

    public function test_sync_logs_cleanup_logs_deleted_count(): void
    {
        Log::spy();

        $this->insertSyncLog(now()->subDays(91)->toDateTimeString());
        $this->insertSyncLog(now()->subDays(92)->toDateTimeString());

        (new CleanupOldSyncLogsJob())->handle();

        Log::shouldHaveReceived('info')
            ->with('CleanupOldSyncLogsJob: completed', \Mockery::on(fn ($ctx) => ($ctx['deleted'] ?? 0) === 2));
    }

    public function test_sync_logs_boundary_at_exactly_90_days_preserved(): void
    {
        // Exactly 90 days ago — cutoff is "older than" 90 days, so this should be kept
        $this->insertSyncLog(now()->subDays(90)->toDateTimeString());

        (new CleanupOldSyncLogsJob())->handle();

        $this->assertDatabaseCount('sync_logs', 1);
    }

    // -------------------------------------------------------------------------
    // CleanupOldWebhookLogsJob — 30-day retention
    // -------------------------------------------------------------------------

    public function test_deletes_webhook_logs_older_than_30_days(): void
    {
        $this->insertWebhookLog(now()->subDays(31)->toDateTimeString());

        (new CleanupOldWebhookLogsJob())->handle();

        $this->assertDatabaseCount('webhook_logs', 0);
    }

    public function test_preserves_webhook_logs_within_30_days(): void
    {
        $this->insertWebhookLog(now()->subDays(29)->toDateTimeString());

        (new CleanupOldWebhookLogsJob())->handle();

        $this->assertDatabaseCount('webhook_logs', 1);
    }

    public function test_webhook_logs_cleanup_logs_deleted_count(): void
    {
        Log::spy();

        $this->insertWebhookLog(now()->subDays(31)->toDateTimeString());
        $this->insertWebhookLog(now()->subDays(35)->toDateTimeString());

        (new CleanupOldWebhookLogsJob())->handle();

        Log::shouldHaveReceived('info')
            ->with('CleanupOldWebhookLogsJob: completed', \Mockery::on(fn ($ctx) => ($ctx['deleted'] ?? 0) === 2));
    }

    public function test_webhook_logs_boundary_at_exactly_30_days_preserved(): void
    {
        $this->insertWebhookLog(now()->subDays(30)->toDateTimeString());

        (new CleanupOldWebhookLogsJob())->handle();

        $this->assertDatabaseCount('webhook_logs', 1);
    }
}
