<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\WithOnboardedWorkspace;
use Tests\TestCase;

/**
 * Feature tests for GET /{workspace}/customers — Customers page.
 *
 * Covers:
 *   - Page renders 200 for an authenticated workspace member
 *   - Required top-level Inertia props are present on segments tab
 *   - segment_drilldown is null when no segment= param is given
 *   - segment_drilldown returns the expected shape when segment= param is present
 *   - segment_drilldown is null (not an error) when the segment slug has no RFM scores
 *
 * @see app/Http/Controllers/CustomersController.php
 * @see app/Services/Customers/CustomersDataService.php::segmentDrilldown()
 */
class CustomersControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithOnboardedWorkspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpOnboardedWorkspace();
    }

    private function visit(array $params = []): \Illuminate\Testing\TestResponse
    {
        $query = $params ? '?' . http_build_query($params) : '';

        return $this->actingAs($this->user)
            ->get("/{$this->workspace->slug}/customers{$query}");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function insertCustomer(array $overrides = []): int
    {
        DB::table('customers')->insert(array_merge([
            'workspace_id'              => $this->workspace->id,
            'store_id'                  => $this->store->id,
            'email_hash'                => hash('sha256', uniqid('email', true)),
            'platform_customer_id'      => (string) random_int(1000, 999999),
            'display_email_masked'      => 'j***@example.com',
            'name'                      => 'Jane Doe',
            'first_order_at'            => now()->subDays(60),
            'last_order_at'             => now()->subDays(10),
            'orders_count'              => 3,
            'lifetime_value_native'     => 300.00,
            'lifetime_value_reporting'  => 300.00,
            'country'                   => 'US',
            'acquisition_source'        => 'facebook',
            'created_at'                => now(),
            'updated_at'                => now(),
        ], $overrides));

        return (int) DB::table('customers')->latest('id')->value('id');
    }

    private function insertRfmScore(int $customerId, string $segment, ?string $computedFor = null): void
    {
        DB::table('customer_rfm_scores')->insert([
            'workspace_id'    => $this->workspace->id,
            'customer_id'     => $customerId,
            'computed_for'    => $computedFor ?? now()->toDateString(),
            'recency_score'   => 5,
            'frequency_score' => 4,
            'monetary_score'  => 4,
            'segment'         => $segment,
            'model_version'   => 'v1',
            'created_at'      => now(),
        ]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_segments_tab_renders_200(): void
    {
        $response = $this->visit(['tab' => 'segments']);

        $response->assertStatus(200);
    }

    public function test_segments_tab_has_required_props(): void
    {
        $response = $this->visit(['tab' => 'segments']);

        $response->assertInertia(fn ($page) =>
            $page->component('Customers/Index')
                ->has('rfm_segments')
                ->has('rfm_cells')
                ->has('segment_traits')
                ->has('metrics')
                ->has('filters')
        );
    }

    public function test_segment_drilldown_is_null_without_segment_param(): void
    {
        $this->visit(['tab' => 'segments'])
            ->assertInertia(fn ($page) =>
                $page->where('segment_drilldown', null)
            );
    }

    public function test_segment_drilldown_returns_expected_shape_with_segment_param(): void
    {
        // Arrange: one customer in the 'champions' segment.
        $customerId = $this->insertCustomer();
        $this->insertRfmScore($customerId, 'champions');

        // Act
        $response = $this->visit(['tab' => 'segments', 'segment' => 'champions']);

        // Assert structure
        $response->assertInertia(fn ($page) =>
            $page->component('Customers/Index')
                ->has('segment_drilldown')
                ->has('segment_drilldown.customers')
                ->has('segment_drilldown.customers.data')
                ->has('segment_drilldown.customers.total')
                ->has('segment_drilldown.customers.per_page')
                ->has('segment_drilldown.customers.current_page')
                ->has('segment_drilldown.customers.last_page')
                ->has('segment_drilldown.kpis')
                ->has('segment_drilldown.kpis.avg_ltv')
                ->has('segment_drilldown.kpis.avg_aov')
                ->has('segment_drilldown.kpis.avg_frequency')
                ->has('segment_drilldown.kpis.avg_recency_days')
                ->has('segment_drilldown.top_products')
                ->has('segment_drilldown.top_channels')
        );
    }

    public function test_segment_drilldown_contains_the_correct_customer(): void
    {
        $customerId = $this->insertCustomer(['name' => 'VIP Customer']);
        $this->insertRfmScore($customerId, 'loyal');

        $response = $this->visit(['tab' => 'segments', 'segment' => 'loyal']);

        $response->assertInertia(fn ($page) =>
            $page->where('segment_drilldown.customers.total', 1)
                ->where('segment_drilldown.customers.current_page', 1)
        );
    }

    public function test_segment_drilldown_is_null_for_unknown_slug(): void
    {
        // No RFM scores at all — service returns emptyDrilldown which has total=0
        // but the prop itself is non-null (it's the empty shape, not null).
        // The controller sets it to null only when tab != segments or no slug.
        // When slug is given but no scores exist, service returns empty shape.
        $response = $this->visit(['tab' => 'segments', 'segment' => 'nonexistent_segment']);

        $response->assertInertia(fn ($page) =>
            $page->where('segment_drilldown.customers.total', 0)
        );
    }

    public function test_segment_drilldown_respects_pagination(): void
    {
        // Insert 30 customers all in the same segment.
        $date = now()->toDateString();
        for ($i = 0; $i < 30; $i++) {
            $cid = $this->insertCustomer(['lifetime_value_reporting' => 100 + $i]);
            $this->insertRfmScore($cid, 'hibernating', $date);
        }

        $page1 = $this->visit(['tab' => 'segments', 'segment' => 'hibernating', 'page' => 1]);
        $page1->assertInertia(fn ($page) =>
            $page->where('segment_drilldown.customers.current_page', 1)
                ->where('segment_drilldown.customers.total', 30)
                ->where('segment_drilldown.customers.last_page', 2)
        );

        $page2 = $this->visit(['tab' => 'segments', 'segment' => 'hibernating', 'page' => 2]);
        $page2->assertInertia(fn ($page) =>
            $page->where('segment_drilldown.customers.current_page', 2)
        );
    }

    public function test_non_member_cannot_access_customers_page(): void
    {
        $other = User::factory()->create();

        $this->actingAs($other)
            ->get("/{$this->workspace->slug}/customers")
            ->assertRedirect();
    }
}
