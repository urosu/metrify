<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Benchmarks;

use App\Models\Store;
use App\Models\Workspace;
use App\Services\Benchmarks\BenchmarkLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unit tests for BenchmarkLookupService::forWorkspace().
 *
 * Tests:
 *  - Returns null when no benchmark row exists
 *  - Returns null when workspace has no vertical
 *  - Returns null when workspace vertical is 'other'
 *  - Returns a populated BenchmarkRow when all conditions are met
 *  - BenchmarkRow::percentileTier() returns correct tier
 */
class BenchmarkLookupServiceTest extends TestCase
{
    use RefreshDatabase;

    private BenchmarkLookupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(BenchmarkLookupService::class);
    }

    // =========================================================================
    // Null cases
    // =========================================================================

    public function test_returns_null_when_no_benchmark_row_exists(): void
    {
        $workspace = Workspace::factory()->create(['vertical' => 'apparel']);

        $result = $this->service->forWorkspace($workspace->id, 'roas');

        $this->assertNull($result);
    }

    public function test_returns_null_when_workspace_has_no_vertical(): void
    {
        $workspace = Workspace::factory()->create(['vertical' => null]);

        // Seed a benchmark row for a different workspace's vertical.
        $this->insertBenchmarkSnapshot('beauty', 'roas');

        $result = $this->service->forWorkspace($workspace->id, 'roas');

        $this->assertNull($result);
    }

    public function test_returns_null_when_vertical_is_other(): void
    {
        $workspace = Workspace::factory()->create(['vertical' => 'other']);
        $this->insertBenchmarkSnapshot('other', 'roas');

        $result = $this->service->forWorkspace($workspace->id, 'roas');

        $this->assertNull($result);
    }

    public function test_returns_null_when_workspace_does_not_exist(): void
    {
        $result = $this->service->forWorkspace(999999, 'roas');

        $this->assertNull($result);
    }

    // =========================================================================
    // Happy path
    // =========================================================================

    public function test_returns_benchmark_row_when_snapshot_exists(): void
    {
        $workspace = Workspace::factory()->create(['vertical' => 'apparel']);
        $store     = Store::factory()->create(['workspace_id' => $workspace->id]);

        // Seed snapshot data so the service can compute the workspace's own value.
        DB::table('daily_snapshots')->insert([
            'workspace_id'           => $workspace->id,
            'store_id'               => $store->id,
            'date'                   => now()->toDateString(),
            'orders_count'           => 100,
            'revenue'                => 10000.0,
            'revenue_native'         => 10000.0,
            'ad_spend'               => 2000.0,
            'revenue_real_attributed' => 8000.0,
            'sessions'               => 5000,
            'created_at'             => now()->toDateTimeString(),
            'updated_at'             => now()->toDateTimeString(),
        ]);

        $this->insertBenchmarkSnapshot('apparel', 'roas', p25: 2.0, p50: 3.0, p75: 4.5, sampleSize: 12);

        $row = $this->service->forWorkspace($workspace->id, 'roas');

        $this->assertNotNull($row);
        $this->assertSame('apparel', $row->vertical);
        $this->assertSame('roas', $row->metric);
        $this->assertSame('last_30d', $row->period);
        $this->assertEqualsWithDelta(2.0, $row->p25, 0.001);
        $this->assertEqualsWithDelta(3.0, $row->p50, 0.001);
        $this->assertEqualsWithDelta(4.5, $row->p75, 0.001);
        $this->assertSame(12, $row->sampleSize);
        // Own value = revenue_real_attributed / ad_spend = 8000 / 2000 = 4.0
        $this->assertNotNull($row->ownValue);
        $this->assertEqualsWithDelta(4.0, $row->ownValue, 0.01);
    }

    public function test_own_value_is_null_when_no_snapshots_exist(): void
    {
        $workspace = Workspace::factory()->create(['vertical' => 'beauty']);

        $this->insertBenchmarkSnapshot('beauty', 'aov', p25: 40.0, p50: 60.0, p75: 80.0);

        $row = $this->service->forWorkspace($workspace->id, 'aov');

        $this->assertNotNull($row);
        // No snapshot rows → own value cannot be computed.
        $this->assertNull($row->ownValue);
    }

    // =========================================================================
    // BenchmarkRow::percentileTier()
    // =========================================================================

    public function test_percentile_tier_top_25_when_above_p75(): void
    {
        $workspace = Workspace::factory()->create(['vertical' => 'fitness']);
        $store     = Store::factory()->create(['workspace_id' => $workspace->id]);

        // ROAS = 8000 / 1000 = 8.0, which is above p75 = 5.0
        DB::table('daily_snapshots')->insert([
            'workspace_id'           => $workspace->id,
            'store_id'               => $store->id,
            'date'                   => now()->toDateString(),
            'orders_count'           => 50,
            'revenue'                => 5000.0,
            'revenue_native'         => 5000.0,
            'ad_spend'               => 1000.0,
            'revenue_real_attributed' => 8000.0,
            'sessions'               => 2000,
            'created_at'             => now()->toDateTimeString(),
            'updated_at'             => now()->toDateTimeString(),
        ]);

        $this->insertBenchmarkSnapshot('fitness', 'roas', p25: 2.0, p50: 3.5, p75: 5.0);

        $row = $this->service->forWorkspace($workspace->id, 'roas');

        $this->assertNotNull($row);
        $this->assertSame('top_25', $row->percentileTier());
    }

    public function test_percentile_tier_bottom_25_when_below_p25(): void
    {
        $workspace = Workspace::factory()->create(['vertical' => 'electronics']);
        $store     = Store::factory()->create(['workspace_id' => $workspace->id]);

        // ROAS = 800 / 1000 = 0.8, below p25 = 1.5
        DB::table('daily_snapshots')->insert([
            'workspace_id'           => $workspace->id,
            'store_id'               => $store->id,
            'date'                   => now()->toDateString(),
            'orders_count'           => 50,
            'revenue'                => 5000.0,
            'revenue_native'         => 5000.0,
            'ad_spend'               => 1000.0,
            'revenue_real_attributed' => 800.0,
            'sessions'               => 2000,
            'created_at'             => now()->toDateTimeString(),
            'updated_at'             => now()->toDateTimeString(),
        ]);

        $this->insertBenchmarkSnapshot('electronics', 'roas', p25: 1.5, p50: 2.5, p75: 4.0);

        $row = $this->service->forWorkspace($workspace->id, 'roas');

        $this->assertNotNull($row);
        $this->assertSame('bottom_25', $row->percentileTier());
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function insertBenchmarkSnapshot(
        string $vertical,
        string $metric,
        float  $p25 = 1.0,
        float  $p50 = 2.0,
        float  $p75 = 3.5,
        int    $sampleSize = 8,
    ): void {
        DB::table('benchmark_snapshots')->insert([
            'vertical'    => $vertical,
            'metric'      => $metric,
            'period'      => 'last_30d',
            'p25'         => $p25,
            'p50'         => $p50,
            'p75'         => $p75,
            'sample_size' => $sampleSize,
            'computed_at' => now()->toDateTimeString(),
            'created_at'  => now()->toDateTimeString(),
            'updated_at'  => now()->toDateTimeString(),
        ]);
    }
}
