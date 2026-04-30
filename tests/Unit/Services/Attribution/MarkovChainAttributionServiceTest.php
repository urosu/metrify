<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Attribution;

use App\Models\Order;
use App\Models\Store;
use App\Models\Workspace;
use App\Services\Attribution\MarkovChainAttributionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase; // @phpstan-ignore-line
use Tests\TestCase;

/**
 * Unit tests for MarkovChainAttributionService.
 *
 * These tests seed minimal rows directly into the orders table via DB so the
 * service's chunk() path runs against real data without needing Eloquent factories
 * for every model relationship.  RefreshDatabase rolls back after each test.
 *
 * Test scenarios:
 *   1. No journeys in window → empty result.
 *   2. Single-touch journey → 100 % to that channel.
 *   3. Two-touch journey, two channels → each gets a share (no channel gets 0).
 *   4. Channel removal: channel that appears in every converting journey should
 *      receive more credit than a channel that only appears in some.
 *   5. Degenerate matrix (no multi-touch journeys at all) → equal-share fallback.
 */
class MarkovChainAttributionServiceTest extends TestCase
{
    use RefreshDatabase;

    private MarkovChainAttributionService $service;
    private Workspace $workspace;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service   = new MarkovChainAttributionService();
        $this->workspace = Workspace::factory()->create();
        $this->store     = Store::factory()->create(['workspace_id' => $this->workspace->id]);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    /**
     * Create an order row with the given journey via the Eloquent factory so all
     * NOT NULL constraints (store_id, workspace_id, …) are satisfied.
     *
     * @param  list<string>  $channelTypes  e.g. ['paid_social', 'organic_search']
     */
    private function insertOrder(
        string $orderedAt,
        float  $revenue,
        array  $channelTypes,
    ): void {
        $touches = array_map(
            static fn (string $ch): array => [
                'channel_type' => $ch,
                'source'       => $ch,
                'timestamp_at' => $orderedAt,
            ],
            $channelTypes,
        );

        Order::factory()->create([
            'workspace_id'                => $this->workspace->id,
            'store_id'                    => $this->store->id,
            'total'                       => $revenue,
            'total_in_reporting_currency' => $revenue,  // service uses COALESCE(total_in_reporting_currency, total)
            'occurred_at'                 => $orderedAt,
            'status'                      => 'completed',
            'attribution_journey'         => $touches,
        ]);
    }

    private function workspaceId(): int
    {
        return $this->workspace->id;
    }

    private function range(): array
    {
        return [
            Carbon::parse('2025-01-10')->startOfDay(),
            Carbon::parse('2025-01-10')->endOfDay(),
        ];
    }

    // ── test cases ────────────────────────────────────────────────────────────

    public function test_returns_empty_when_no_journeys_exist(): void
    {
        [$from, $to] = $this->range();

        $result = $this->service->attribute($this->workspaceId(), $from, $to);

        $this->assertSame([], $result);
    }

    public function test_allocates_full_revenue_to_single_touch_channel(): void
    {
        $this->insertOrder('2025-01-10 12:00:00', 100.0, ['paid_social']);

        [$from, $to] = $this->range();
        $result = $this->service->attribute($this->workspaceId(), $from, $to);

        $this->assertArrayHasKey('paid_social', $result);
        $this->assertEqualsWithDelta(100.0, $result['paid_social']['revenue'], 0.01);
        $this->assertSame(1, $result['paid_social']['orders']);
    }

    public function test_splits_revenue_across_channels_in_multi_touch_journey(): void
    {
        // One order, two channels in journey.
        $this->insertOrder('2025-01-10 12:00:00', 200.0, ['paid_social', 'organic_search']);

        [$from, $to] = $this->range();
        $result = $this->service->attribute($this->workspaceId(), $from, $to);

        // Both channels must appear with positive revenue.
        $this->assertArrayHasKey('paid_social',    $result);
        $this->assertArrayHasKey('organic_search', $result);

        $totalAttributed = $result['paid_social']['revenue'] + $result['organic_search']['revenue'];
        $this->assertEqualsWithDelta(200.0, $totalAttributed, 1.0); // allow small float drift
        $this->assertGreaterThan(0.0, $result['paid_social']['revenue']);
        $this->assertGreaterThan(0.0, $result['organic_search']['revenue']);
    }

    public function test_gives_more_credit_to_channel_in_every_converting_journey(): void
    {
        // paid_social appears in all 3 orders; organic_search only in 1.
        // Markov removal effect should credit paid_social more.
        $this->insertOrder('2025-01-10 10:00:00', 100.0, ['paid_social', 'organic_search']);
        $this->insertOrder('2025-01-10 11:00:00', 100.0, ['paid_social', 'email']);
        $this->insertOrder('2025-01-10 12:00:00', 100.0, ['paid_social', 'direct']);

        [$from, $to] = $this->range();
        $result = $this->service->attribute($this->workspaceId(), $from, $to);

        $this->assertArrayHasKey('paid_social', $result);
        $paidSocialRevenue    = $result['paid_social']['revenue'];
        $organicSearchRevenue = $result['organic_search']['revenue'] ?? 0.0;

        // paid_social must get more total revenue than any other single channel.
        $this->assertGreaterThan($organicSearchRevenue, $paidSocialRevenue);
    }

    public function test_handles_all_single_touch_journeys_allocates_per_order(): void
    {
        // Multiple single-touch orders, each a different channel.
        // Each single-touch order → 100 % to its own channel.
        $this->insertOrder('2025-01-10 10:00:00', 100.0, ['email']);
        $this->insertOrder('2025-01-10 11:00:00', 100.0, ['direct']);

        [$from, $to] = $this->range();
        $result = $this->service->attribute($this->workspaceId(), $from, $to);

        $this->assertArrayHasKey('email',  $result);
        $this->assertArrayHasKey('direct', $result);
        $this->assertEqualsWithDelta(100.0, $result['email']['revenue'],  0.01);
        $this->assertEqualsWithDelta(100.0, $result['direct']['revenue'], 0.01);
    }

    public function test_shares_sum_to_approximately_one(): void
    {
        $this->insertOrder('2025-01-10 09:00:00', 500.0, ['paid_social', 'organic_search', 'email']);
        $this->insertOrder('2025-01-10 10:00:00', 300.0, ['organic_search', 'direct']);
        $this->insertOrder('2025-01-10 11:00:00', 200.0, ['paid_social', 'email']);

        [$from, $to] = $this->range();
        $result = $this->service->attribute($this->workspaceId(), $from, $to);

        $shareSum = array_sum(array_column($result, 'share'));
        // Shares are normalised per-channel against total attributed revenue.
        // Their sum should be 1.0 (within floating-point tolerance).
        $this->assertEqualsWithDelta(1.0, $shareSum, 0.01);
    }

    public function test_ignores_orders_outside_the_date_window(): void
    {
        // Order outside the window (Jan 9th, not Jan 10th).
        $this->insertOrder('2025-01-09 12:00:00', 999.0, ['paid_social']);

        [$from, $to] = $this->range();
        $result = $this->service->attribute($this->workspaceId(), $from, $to);

        $this->assertSame([], $result);
    }
}
