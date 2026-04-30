<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\ComputeBenchmarksJob;
use App\Models\Store;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature tests for ComputeBenchmarksJob.
 *
 * Tests the privacy floor (< 5 workspaces → no row written), the
 * happy-path scenario (>= 5 workspaces → row written with correct percentiles),
 * and the vertical exclusion rules (null / 'other' verticals are skipped).
 *
 * Uses daily_snapshots directly rather than raw orders, per CLAUDE.md rule:
 * "Never aggregate raw orders in page requests — use daily_snapshots."
 */
class ComputeBenchmarksJobTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Privacy floor
    // =========================================================================

    public function test_privacy_floor_prevents_write_when_fewer_than_5_workspaces(): void
    {
        // 4 workspaces in the same vertical — below the floor of 5.
        $this->createWorkspacesWithSnapshots('apparel', 4, revenue: 5000, orders: 50);

        (new ComputeBenchmarksJob())->handle();

        $this->assertDatabaseMissing('benchmark_snapshots', [
            'vertical' => 'apparel',
        ]);
    }

    public function test_privacy_floor_deletes_existing_row_when_cohort_shrinks_below_5(): void
    {
        // Pre-seed a benchmark row as if it was computed yesterday.
        DB::table('benchmark_snapshots')->insert([
            'vertical'    => 'beauty',
            'metric'      => 'aov',
            'period'      => 'last_30d',
            'p25'         => 40.0,
            'p50'         => 60.0,
            'p75'         => 80.0,
            'sample_size' => 6,
            'computed_at' => now()->subDay(),
            'created_at'  => now()->subDay(),
            'updated_at'  => now()->subDay(),
        ]);

        // Now only 3 workspaces qualify — floor not met.
        $this->createWorkspacesWithSnapshots('beauty', 3, revenue: 5000, orders: 50);

        (new ComputeBenchmarksJob())->handle();

        // Stale row must be deleted.
        $this->assertDatabaseMissing('benchmark_snapshots', [
            'vertical' => 'beauty',
        ]);
    }

    // =========================================================================
    // Happy path — 10 workspaces
    // =========================================================================

    public function test_writes_benchmark_snapshot_for_vertical_with_10_workspaces(): void
    {
        // 10 workspaces in 'apparel' with varying revenue (10 × 100 … 10 × 1000).
        for ($i = 1; $i <= 10; $i++) {
            $this->createWorkspacesWithSnapshots('apparel', 1, revenue: $i * 1000, orders: 50);
        }

        (new ComputeBenchmarksJob())->handle();

        $row = DB::table('benchmark_snapshots')
            ->where('vertical', 'apparel')
            ->where('metric', 'aov')
            ->where('period', 'last_30d')
            ->first();

        $this->assertNotNull($row, 'Expected a benchmark_snapshots row for apparel/aov');
        $this->assertGreaterThanOrEqual(5, $row->sample_size);
        $this->assertNotNull($row->p25);
        $this->assertNotNull($row->p50);
        $this->assertNotNull($row->p75);
        // p25 <= p50 <= p75
        // assertLessThanOrEqual($expected, $actual): asserts $actual <= $expected
        $this->assertLessThanOrEqual((float) $row->p50, (float) $row->p25); // p25 <= p50
        $this->assertLessThanOrEqual((float) $row->p75, (float) $row->p50); // p50 <= p75
    }

    public function test_sample_size_reflects_qualifying_workspaces(): void
    {
        // 7 workspaces with different AOVs.
        for ($i = 1; $i <= 7; $i++) {
            $this->createWorkspacesWithSnapshots('home', 1, revenue: $i * 500, orders: 25);
        }

        (new ComputeBenchmarksJob())->handle();

        $row = DB::table('benchmark_snapshots')
            ->where('vertical', 'home')
            ->where('metric', 'aov')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(7, (int) $row->sample_size);
    }

    // =========================================================================
    // Vertical exclusion rules
    // =========================================================================

    public function test_skips_workspaces_with_null_vertical(): void
    {
        // 10 workspaces with null vertical — they must be excluded.
        for ($i = 0; $i < 10; $i++) {
            $workspace = Workspace::factory()->create(['vertical' => null]);
            $store     = Store::factory()->create(['workspace_id' => $workspace->id]);
            $this->insertSnapshot($workspace->id, $store->id, revenue: 5000, orders: 100);
        }

        (new ComputeBenchmarksJob())->handle();

        $this->assertDatabaseEmpty('benchmark_snapshots');
    }

    public function test_skips_workspaces_with_vertical_other(): void
    {
        // 10 workspaces in 'other' — excluded from cohorts.
        for ($i = 0; $i < 10; $i++) {
            $workspace = Workspace::factory()->create(['vertical' => 'other']);
            $store     = Store::factory()->create(['workspace_id' => $workspace->id]);
            $this->insertSnapshot($workspace->id, $store->id, revenue: 5000, orders: 100);
        }

        (new ComputeBenchmarksJob())->handle();

        $this->assertDatabaseEmpty('benchmark_snapshots');
    }

    public function test_excludes_workspaces_with_fewer_than_10_orders(): void
    {
        // 8 workspaces that qualify (>= 10 orders) + 3 with only 5 orders.
        for ($i = 0; $i < 8; $i++) {
            $this->createWorkspacesWithSnapshots('pets', 1, revenue: 3000, orders: 50);
        }
        for ($i = 0; $i < 3; $i++) {
            $workspace = Workspace::factory()->create(['vertical' => 'pets']);
            $store     = Store::factory()->create(['workspace_id' => $workspace->id]);
            $this->insertSnapshot($workspace->id, $store->id, revenue: 1000, orders: 5);
        }

        (new ComputeBenchmarksJob())->handle();

        $row = DB::table('benchmark_snapshots')
            ->where('vertical', 'pets')
            ->where('metric', 'aov')
            ->first();

        $this->assertNotNull($row);
        // Only the 8 qualifying workspaces counted.
        $this->assertSame(8, (int) $row->sample_size);
    }

    // =========================================================================
    // Multiple verticals
    // =========================================================================

    public function test_computes_separate_snapshots_for_each_vertical(): void
    {
        // 6 apparel + 6 beauty workspaces.
        $this->createWorkspacesWithSnapshots('apparel', 6, revenue: 4000, orders: 40);
        $this->createWorkspacesWithSnapshots('beauty', 6, revenue: 6000, orders: 60);

        (new ComputeBenchmarksJob())->handle();

        $this->assertDatabaseHas('benchmark_snapshots', ['vertical' => 'apparel', 'metric' => 'aov']);
        $this->assertDatabaseHas('benchmark_snapshots', ['vertical' => 'beauty',  'metric' => 'aov']);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create N workspaces in the given vertical, each with a Store and a
     * single 30-day daily_snapshot row.
     */
    private function createWorkspacesWithSnapshots(
        string $vertical,
        int    $count,
        float  $revenue,
        int    $orders,
    ): void {
        for ($i = 0; $i < $count; $i++) {
            $workspace = Workspace::factory()->create(['vertical' => $vertical]);
            $store     = Store::factory()->create(['workspace_id' => $workspace->id]);
            $this->insertSnapshot($workspace->id, $store->id, $revenue, $orders);
        }
    }

    /**
     * Insert a single daily_snapshot row dated today (within the last 30 days window).
     * Ad spend is set so MER/CPA/ROAS metrics are also computable.
     */
    private function insertSnapshot(
        int   $workspaceId,
        int   $storeId,
        float $revenue,
        int   $orders,
        float $adSpend = 1000.0,
    ): void {
        DB::table('daily_snapshots')->insert([
            'workspace_id'           => $workspaceId,
            'store_id'               => $storeId,
            'date'                   => now()->toDateString(),
            'orders_count'           => $orders,
            'revenue'                => $revenue,
            'revenue_native'         => $revenue,
            'ad_spend'               => $adSpend,
            'revenue_real_attributed' => $revenue * 0.8,
            'sessions'               => $orders * 30,   // ~3.3% CVR
            'created_at'             => now()->toDateTimeString(),
            'updated_at'             => now()->toDateTimeString(),
        ]);
    }
}
