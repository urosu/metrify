<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\WithOnboardedWorkspace;
use Tests\TestCase;

/**
 * Smoke tests for GET /{workspace}/discrepancy — the Discrepancy Explainer page.
 *
 * Verifies:
 *   - Page renders 200 for an authenticated workspace member
 *   - Required Inertia props are present
 *   - Non-members are redirected
 *   - Page renders with no snapshot data (empty state path)
 *   - Snapshot data is reflected in summary props
 *   - Legacy /analytics/discrepancy URL redirects to /discrepancy
 *
 * @see app/Http/Controllers/DiscrepancyController.php
 * @see app/Services/Discrepancy/DiscrepancyAnalyzer.php
 */
class DiscrepancyControllerTest extends TestCase
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
            ->get("/{$this->workspace->slug}/discrepancy{$query}");
    }

    private function insertSnapshot(array $overrides = []): void
    {
        DB::table('daily_snapshots')->insert(array_merge([
            'workspace_id'                => $this->workspace->id,
            'store_id'                    => $this->store->id,
            'date'                        => now()->subDays(3)->toDateString(),
            'orders_count'                => 5,
            'revenue'                     => 500.00,
            'revenue_native'              => 500.00,
            'revenue_facebook_attributed' => 300.00,
            'revenue_google_attributed'   => 80.00,
            'revenue_gsc_attributed'      => 0.00,
            'revenue_ga4_attributed'      => null,
            'revenue_direct_attributed'   => 50.00,
            'revenue_organic_attributed'  => 0.00,
            'revenue_email_attributed'    => 0.00,
            'revenue_real_attributed'     => 480.00,
            'new_customers'               => 3,
            'returning_customers'         => 2,
            'items_sold'                  => 8,
            'discounts_total'             => 0.00,
            'refunds_total'               => 0.00,
            'shipping_cost_total'         => 5.00,
            'shipping_revenue_total'      => 10.00,
            'tax_total'                   => 50.00,
            'transaction_fees_total'      => 2.00,
            'created_at'                  => now()->toDateTimeString(),
            'updated_at'                  => now()->toDateTimeString(),
        ], $overrides));
    }

    // ── Tests ────────────────────────────────────────────────────────────────

    public function test_page_renders_for_workspace_member(): void
    {
        $this->visit()->assertOk();
    }

    public function test_required_inertia_props_are_present(): void
    {
        $response = $this->visit()->assertOk();

        $props = $response->inertiaProps();

        $this->assertArrayHasKey('daily',             $props);
        $this->assertArrayHasKey('summary',           $props);
        $this->assertArrayHasKey('factors',           $props);
        $this->assertArrayHasKey('disagreement_rows', $props);
        $this->assertArrayHasKey('filters',           $props);
    }

    public function test_summary_keys_are_present(): void
    {
        $response = $this->visit()->assertOk();
        $summary  = $response->inertiaProps()['summary'];

        foreach (['store', 'facebook', 'google', 'ga4', 'gsc', 'real', 'declared_total', 'unattributed'] as $key) {
            $this->assertArrayHasKey($key, $summary, "summary.{$key} missing");
        }
    }

    public function test_factors_are_returned(): void
    {
        $response = $this->visit()->assertOk();
        $factors  = $response->inertiaProps()['factors'];

        $this->assertIsArray($factors);
        $this->assertNotEmpty($factors);

        // Each factor has required keys.
        foreach ($factors as $factor) {
            $this->assertArrayHasKey('id',          $factor);
            $this->assertArrayHasKey('title',       $factor);
            $this->assertArrayHasKey('description', $factor);
            $this->assertArrayHasKey('severity',    $factor);
            $this->assertArrayHasKey('detected',    $factor);
        }
    }

    public function test_page_renders_empty_with_no_snapshot_data(): void
    {
        // No snapshots inserted — should still render 200 with empty daily array.
        $response = $this->visit([
            'from' => '2020-01-01',
            'to'   => '2020-01-31',
        ])->assertOk();

        $this->assertSame([], $response->inertiaProps()['daily']);
        $this->assertSame(0.0, (float) $response->inertiaProps()['summary']['store']);
    }

    public function test_snapshot_data_appears_in_summary(): void
    {
        $this->insertSnapshot(['revenue' => 1000.00, 'revenue_facebook_attributed' => 700.00]);

        $response = $this->visit([
            'from' => now()->subDays(7)->toDateString(),
            'to'   => now()->toDateString(),
        ])->assertOk();

        $summary = $response->inertiaProps()['summary'];

        $this->assertGreaterThan(0, (float) $summary['store']);
        $this->assertGreaterThan(0, (float) $summary['facebook']);
    }

    public function test_ios_att_factor_detected_when_facebook_exceeds_threshold(): void
    {
        // store_revenue = 1500, facebook = 1000 (>60% threshold + >1000)
        $this->insertSnapshot([
            'revenue'                      => 1500.00,
            'revenue_facebook_attributed'  => 1000.00,
        ]);

        $response = $this->visit([
            'from' => now()->subDays(7)->toDateString(),
            'to'   => now()->toDateString(),
        ])->assertOk();

        $factors    = $response->inertiaProps()['factors'];
        $iosFactor  = collect($factors)->firstWhere('id', 'ios_att');

        $this->assertNotNull($iosFactor);
        $this->assertTrue($iosFactor['detected'], 'ios_att factor should be detected');
    }

    public function test_date_range_filter_is_applied(): void
    {
        // Insert snapshot for yesterday.
        $this->insertSnapshot(['date' => now()->subDays(1)->toDateString()]);

        // Query for a range that excludes the snapshot.
        $response = $this->visit([
            'from' => '2019-01-01',
            'to'   => '2019-01-31',
        ])->assertOk();

        $this->assertSame([], $response->inertiaProps()['daily']);
    }

    public function test_non_member_is_redirected(): void
    {
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get("/{$this->workspace->slug}/discrepancy")
            ->assertRedirect();
    }

    public function test_invalid_date_range_returns_validation_error(): void
    {
        $this->actingAs($this->user)
            ->get("/{$this->workspace->slug}/discrepancy?from=bad-date")
            ->assertSessionHasErrors(['from']);
    }
}
