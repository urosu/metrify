<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Store;
use App\Models\Workspace;
use App\Services\RevenueAttributionService;
use App\Services\WorkspaceContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RevenueAttributionServiceTest extends TestCase
{
    use RefreshDatabase;

    private RevenueAttributionService $service;
    private Workspace $workspace;
    private Store $store;
    private Carbon $from;
    private Carbon $to;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = Workspace::factory()->create(['reporting_currency' => 'EUR']);
        $this->store     = Store::factory()->create(['workspace_id' => $this->workspace->id]);
        $this->service   = app(RevenueAttributionService::class);
        $this->from      = Carbon::today()->startOfDay();
        $this->to        = Carbon::today()->endOfDay();

        app(WorkspaceContext::class)->set($this->workspace->id);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function insertOrder(array $overrides = []): void
    {
        DB::table('orders')->insert(array_merge([
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
            'total_in_reporting_currency' => 100.00,
            'occurred_at'                 => Carbon::today()->midDay(),
            'synced_at'                   => now(),
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // getAttributedRevenue — channel bucketing
    // -------------------------------------------------------------------------

    public function test_facebook_aliases_all_bucketed(): void
    {
        foreach (['facebook', 'fb', 'ig', 'instagram'] as $source) {
            $this->insertOrder([
                'external_id'                 => uniqid("order-{$source}-"),
                'utm_source'                  => $source,
                'total_in_reporting_currency' => 50.00,
            ]);
        }

        $result = $this->service->getAttributedRevenue($this->workspace->id, $this->from, $this->to);

        $this->assertEqualsWithDelta(200.00, $result['facebook'], 0.01);
        $this->assertEqualsWithDelta(0.00, $result['google'], 0.01);
        $this->assertEqualsWithDelta(0.00, $result['other_tagged'], 0.01);
    }

    public function test_google_aliases_all_bucketed(): void
    {
        foreach (['google', 'cpc', 'google-ads', 'ppc'] as $source) {
            $this->insertOrder([
                'external_id'                 => uniqid("order-{$source}-"),
                'utm_source'                  => $source,
                'total_in_reporting_currency' => 25.00,
            ]);
        }

        $result = $this->service->getAttributedRevenue($this->workspace->id, $this->from, $this->to);

        $this->assertEqualsWithDelta(100.00, $result['google'], 0.01);
        $this->assertEqualsWithDelta(0.00, $result['facebook'], 0.01);
    }

    public function test_utm_source_case_insensitive(): void
    {
        $this->insertOrder([
            'utm_source'                  => 'FACEBOOK',
            'total_in_reporting_currency' => 80.00,
        ]);
        $this->insertOrder([
            'external_id'                 => uniqid('order-', true),
            'utm_source'                  => 'Google',
            'total_in_reporting_currency' => 40.00,
        ]);

        $result = $this->service->getAttributedRevenue($this->workspace->id, $this->from, $this->to);

        $this->assertEqualsWithDelta(80.00, $result['facebook'], 0.01);
        $this->assertEqualsWithDelta(40.00, $result['google'], 0.01);
    }

    public function test_unrecognised_source_goes_to_other_tagged(): void
    {
        $this->insertOrder([
            'utm_source'                  => 'tiktok',
            'total_in_reporting_currency' => 60.00,
        ]);

        $result = $this->service->getAttributedRevenue($this->workspace->id, $this->from, $this->to);

        $this->assertEqualsWithDelta(60.00, $result['other_tagged'], 0.01);
        $this->assertEqualsWithDelta(0.00, $result['facebook'], 0.01);
        $this->assertEqualsWithDelta(0.00, $result['google'], 0.01);
    }

    public function test_no_utm_source_excluded_from_tagged(): void
    {
        $this->insertOrder([
            'utm_source'                  => null,
            'total_in_reporting_currency' => 100.00,
        ]);

        $result = $this->service->getAttributedRevenue($this->workspace->id, $this->from, $this->to);

        $this->assertEqualsWithDelta(0.00, $result['total_tagged'], 0.01);
    }

    public function test_total_tagged_is_sum_of_channels(): void
    {
        $this->insertOrder(['utm_source' => 'facebook', 'total_in_reporting_currency' => 100.00]);
        $this->insertOrder(['external_id' => uniqid(), 'utm_source' => 'google', 'total_in_reporting_currency' => 200.00]);
        $this->insertOrder(['external_id' => uniqid(), 'utm_source' => 'tiktok', 'total_in_reporting_currency' => 50.00]);

        $result = $this->service->getAttributedRevenue($this->workspace->id, $this->from, $this->to);

        $this->assertEqualsWithDelta(350.00, $result['total_tagged'], 0.01);
        $this->assertEqualsWithDelta(
            $result['facebook'] + $result['google'] + $result['other_tagged'],
            $result['total_tagged'],
            0.01,
        );
    }

    public function test_null_total_in_reporting_currency_excluded(): void
    {
        $this->insertOrder(['utm_source' => 'facebook', 'total_in_reporting_currency' => null]);
        $this->insertOrder(['external_id' => uniqid(), 'utm_source' => 'facebook', 'total_in_reporting_currency' => 50.00]);

        $result = $this->service->getAttributedRevenue($this->workspace->id, $this->from, $this->to);

        $this->assertEqualsWithDelta(50.00, $result['facebook'], 0.01);
    }

    public function test_only_completed_and_processing_counted(): void
    {
        // Should NOT be counted
        foreach (['refunded', 'cancelled', 'other'] as $status) {
            $this->insertOrder([
                'external_id'                 => uniqid("order-{$status}-"),
                'status'                      => $status,
                'utm_source'                  => 'facebook',
                'total_in_reporting_currency' => 999.00,
            ]);
        }
        // Should be counted
        $this->insertOrder(['utm_source' => 'facebook', 'status' => 'processing', 'total_in_reporting_currency' => 100.00]);

        $result = $this->service->getAttributedRevenue($this->workspace->id, $this->from, $this->to);

        $this->assertEqualsWithDelta(100.00, $result['facebook'], 0.01);
    }

    public function test_store_filter_isolates_to_single_store(): void
    {
        $store2 = Store::factory()->create(['workspace_id' => $this->workspace->id]);

        $this->insertOrder(['store_id' => $this->store->id, 'utm_source' => 'facebook', 'total_in_reporting_currency' => 100.00]);
        $this->insertOrder(['store_id' => $store2->id, 'external_id' => uniqid(), 'utm_source' => 'facebook', 'total_in_reporting_currency' => 200.00]);

        $result = $this->service->getAttributedRevenue(
            $this->workspace->id, $this->from, $this->to, storeId: $this->store->id
        );

        $this->assertEqualsWithDelta(100.00, $result['facebook'], 0.01);
    }

    public function test_empty_date_range_returns_zeros(): void
    {
        $this->insertOrder(['utm_source' => 'facebook', 'total_in_reporting_currency' => 100.00]);

        $yesterday = Carbon::yesterday();
        $result = $this->service->getAttributedRevenue(
            $this->workspace->id,
            $yesterday->copy()->startOfDay(),
            $yesterday->copy()->endOfDay(),
        );

        $this->assertEqualsWithDelta(0.00, $result['total_tagged'], 0.01);
    }

    // -------------------------------------------------------------------------
    // getCampaignAttributedRevenue
    // -------------------------------------------------------------------------

    public function test_get_campaign_attributed_revenue_case_insensitive(): void
    {
        $this->insertOrder(['utm_campaign' => 'SUMMER20', 'total_in_reporting_currency' => 120.00]);
        $this->insertOrder(['external_id' => uniqid(), 'utm_campaign' => 'summer20', 'total_in_reporting_currency' => 80.00]);

        $result = $this->service->getCampaignAttributedRevenue(
            $this->workspace->id, 'summer20', $this->from, $this->to,
        );

        $this->assertEqualsWithDelta(200.00, $result, 0.01);
    }

    public function test_get_campaign_attributed_revenue_only_active_orders(): void
    {
        $this->insertOrder(['utm_campaign' => 'promo', 'status' => 'completed', 'total_in_reporting_currency' => 100.00]);
        $this->insertOrder(['external_id' => uniqid(), 'utm_campaign' => 'promo', 'status' => 'refunded', 'total_in_reporting_currency' => 999.00]);

        $result = $this->service->getCampaignAttributedRevenue(
            $this->workspace->id, 'promo', $this->from, $this->to,
        );

        $this->assertEqualsWithDelta(100.00, $result, 0.01);
    }

    // -------------------------------------------------------------------------
    // getUnattributedRevenue
    // -------------------------------------------------------------------------

    public function test_get_unattributed_revenue_returns_difference(): void
    {
        $result = $this->service->getUnattributedRevenue(totalRevenue: 1000.0, totalTagged: 400.0);

        $this->assertEqualsWithDelta(600.0, $result, 0.01);
    }

    public function test_get_unattributed_revenue_floors_at_zero(): void
    {
        // Tagged exceeds total (can happen with FX edge cases)
        $result = $this->service->getUnattributedRevenue(totalRevenue: 100.0, totalTagged: 150.0);

        $this->assertEqualsWithDelta(0.0, $result, 0.01);
    }
}
