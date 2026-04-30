<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Customers;

use App\Services\Customers\CustomersDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Smoke tests for CustomersDataService::segmentDrilldown().
 *
 * These are lightweight integration tests — they hit a real SQLite/Postgres
 * test database to verify the query plumbing works end-to-end.
 *
 * They do NOT test business logic at pixel depth (that lives in the feature
 * controller test); they verify:
 *   - Return shape keys are present
 *   - Empty workspace returns safe zero-state
 *   - A workspace with RFM data returns the correct customer count
 *   - Pagination slices correctly
 *   - An unknown segment returns the empty shape (not an exception)
 *
 * @see app/Services/Customers/CustomersDataService.php::segmentDrilldown()
 */
class SegmentsDataServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomersDataService $service;
    private int $workspaceId;
    private int $storeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(CustomersDataService::class);

        // Minimal workspace + store rows (no Eloquent factories needed for a unit smoke test).
        $this->workspaceId = (int) DB::table('workspaces')->insertGetId([
            'name'               => 'Test WS',
            'slug'               => 'test-ws-' . uniqid(),
            'reporting_currency' => 'EUR',
            'owner_id'           => $this->createUser(),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->storeId = (int) DB::table('stores')->insertGetId([
            'workspace_id' => $this->workspaceId,
            'name'         => 'Test Store',
            'slug'         => 'test-store-' . uniqid(),
            'platform'     => 'woocommerce',
            'domain'       => 'example-' . uniqid() . '.com',
            'website_url'  => 'https://example.com',
            'currency'     => 'EUR',
            'status'       => 'active',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createUser(): int
    {
        return (int) DB::table('users')->insertGetId([
            'name'              => 'Test User',
            'email'             => 'test-' . uniqid() . '@example.com',
            'password'          => bcrypt('password'),
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    private function insertCustomer(array $overrides = []): int
    {
        return (int) DB::table('customers')->insertGetId(array_merge([
            'workspace_id'             => $this->workspaceId,
            'store_id'                 => $this->storeId,
            'email_hash'               => hash('sha256', uniqid('e', true)),
            'platform_customer_id'     => (string) random_int(1000, 99999),
            'display_email_masked'     => 'a***@b.com',
            'name'                     => 'Test Customer',
            'first_order_at'           => now()->subDays(90),
            'last_order_at'            => now()->subDays(10),
            'orders_count'             => 2,
            'lifetime_value_native'    => 200.00,
            'lifetime_value_reporting' => 200.00,
            'created_at'               => now(),
            'updated_at'               => now(),
        ], $overrides));
    }

    private function insertRfmScore(int $customerId, string $segment, ?string $date = null): void
    {
        DB::table('customer_rfm_scores')->insert([
            'workspace_id'    => $this->workspaceId,
            'customer_id'     => $customerId,
            'computed_for'    => $date ?? now()->toDateString(),
            'recency_score'   => 4,
            'frequency_score' => 3,
            'monetary_score'  => 3,
            'segment'         => $segment,
            'model_version'   => 'v1',
            'created_at'      => now(),
        ]);
    }

    // ─── Tests ────────────────────────────────────────────────────────────────

    public function test_returns_correct_shape_keys(): void
    {
        $result = $this->service->segmentDrilldown($this->workspaceId, 'champions');

        $this->assertArrayHasKey('customers', $result);
        $this->assertArrayHasKey('kpis', $result);
        $this->assertArrayHasKey('top_products', $result);
        $this->assertArrayHasKey('top_channels', $result);

        // customers sub-keys
        foreach (['data', 'total', 'per_page', 'current_page', 'last_page'] as $key) {
            $this->assertArrayHasKey($key, $result['customers'], "Missing customers.{$key}");
        }

        // kpis sub-keys
        foreach (['avg_ltv', 'avg_aov', 'avg_frequency', 'avg_recency_days'] as $key) {
            $this->assertArrayHasKey($key, $result['kpis'], "Missing kpis.{$key}");
        }
    }

    public function test_empty_workspace_returns_zero_total(): void
    {
        $result = $this->service->segmentDrilldown($this->workspaceId, 'champions');

        $this->assertSame(0, $result['customers']['total']);
        $this->assertSame([], $result['customers']['data']);
        $this->assertSame([], $result['top_products']);
        $this->assertSame([], $result['top_channels']);
        $this->assertNull($result['kpis']['avg_ltv']);
    }

    public function test_unknown_segment_slug_returns_empty_shape(): void
    {
        $cid = $this->insertCustomer();
        $this->insertRfmScore($cid, 'champions');

        $result = $this->service->segmentDrilldown($this->workspaceId, 'does_not_exist');

        $this->assertSame(0, $result['customers']['total']);
    }

    public function test_returns_correct_customer_count_for_segment(): void
    {
        $date = now()->toDateString();

        // 3 champions, 1 loyal
        for ($i = 0; $i < 3; $i++) {
            $cid = $this->insertCustomer();
            $this->insertRfmScore($cid, 'champions', $date);
        }
        $loyalCid = $this->insertCustomer();
        $this->insertRfmScore($loyalCid, 'loyal', $date);

        $result = $this->service->segmentDrilldown($this->workspaceId, 'champions');
        $this->assertSame(3, $result['customers']['total']);

        $loyalResult = $this->service->segmentDrilldown($this->workspaceId, 'loyal');
        $this->assertSame(1, $loyalResult['customers']['total']);
    }

    public function test_pagination_slices_correctly(): void
    {
        $date = now()->toDateString();

        for ($i = 0; $i < 30; $i++) {
            $cid = $this->insertCustomer(['lifetime_value_reporting' => 100 + $i]);
            $this->insertRfmScore($cid, 'hibernating', $date);
        }

        $page1 = $this->service->segmentDrilldown($this->workspaceId, 'hibernating', 1, 25);
        $this->assertSame(30, $page1['customers']['total']);
        $this->assertSame(1,  $page1['customers']['current_page']);
        $this->assertSame(2,  $page1['customers']['last_page']);
        $this->assertCount(25, $page1['customers']['data']);

        $page2 = $this->service->segmentDrilldown($this->workspaceId, 'hibernating', 2, 25);
        $this->assertSame(2, $page2['customers']['current_page']);
        $this->assertCount(5, $page2['customers']['data']);
    }

    public function test_kpis_are_computed_from_materialised_fields(): void
    {
        $date = now()->toDateString();

        $this->insertCustomer([
            'lifetime_value_reporting' => 300.0,
            'orders_count'             => 3,
            'last_order_at'            => now()->subDays(10),
        ]);
        $cid = (int) DB::table('customers')->latest('id')->value('id');
        $this->insertRfmScore($cid, 'at_risk', $date);

        $result = $this->service->segmentDrilldown($this->workspaceId, 'at_risk');

        $this->assertEqualsWithDelta(300.0, $result['kpis']['avg_ltv'], 0.1);
        $this->assertEqualsWithDelta(100.0, $result['kpis']['avg_aov'],  0.1); // 300/3
        $this->assertEqualsWithDelta(3.0,   $result['kpis']['avg_frequency'], 0.1);
        $this->assertNotNull($result['kpis']['avg_recency_days']);
    }

    public function test_top_channels_aggregate_acquisition_source(): void
    {
        $date = now()->toDateString();

        foreach (['facebook', 'facebook', 'google'] as $source) {
            $cid = $this->insertCustomer(['acquisition_source' => $source]);
            $this->insertRfmScore($cid, 'loyal', $date);
        }

        $result    = $this->service->segmentDrilldown($this->workspaceId, 'loyal');
        $channelMap = array_column($result['top_channels'], 'count', 'channel');

        $this->assertSame(2, $channelMap['Facebook'] ?? 0);
        $this->assertSame(1, $channelMap['Google'] ?? 0);
    }
}
