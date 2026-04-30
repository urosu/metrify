<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DailySnapshot;
use App\Models\Store;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for ForecastController.
 *
 * Uses RefreshDatabase to run in-process against a real (test) database so
 * the SQL inside RevenueForecastService is exercised end-to-end.
 *
 * @see App\Http\Controllers\ForecastController
 * @see App\Services\Forecasting\RevenueForecastService
 */
class ForecastControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @return array{0: User, 1: Workspace}
     */
    private function makeUserWithWorkspace(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'owner_id' => $user->id,
            'has_ads'  => true,
        ]);
        WorkspaceUser::factory()->owner()->create([
            'user_id'      => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        return [$user, $workspace];
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    #[Test]
    public function it_returns_200_for_an_authenticated_workspace_member(): void
    {
        [$user, $workspace] = $this->makeUserWithWorkspace();

        $response = $this
            ->actingAs($user)
            ->getJson("/{$workspace->slug}/api/forecast");

        $response->assertOk();
    }

    #[Test]
    public function response_contains_forecast_and_holidays_keys(): void
    {
        [$user, $workspace] = $this->makeUserWithWorkspace();

        $response = $this
            ->actingAs($user)
            ->getJson("/{$workspace->slug}/api/forecast");

        $response
            ->assertOk()
            ->assertJsonStructure([
                'forecast' => ['points', 'total_30d', 'total_90d', 'history_days'],
                'holidays',
            ]);
    }

    #[Test]
    public function forecast_points_have_correct_shape(): void
    {
        [$user, $workspace] = $this->makeUserWithWorkspace();

        $response = $this
            ->actingAs($user)
            ->getJson("/{$workspace->slug}/api/forecast?horizon=30");

        $response->assertOk();
        $points = $response->json('forecast.points');

        $this->assertIsArray($points);
        $this->assertCount(30, $points);

        foreach ($points as $pt) {
            $this->assertArrayHasKey('date',  $pt);
            $this->assertArrayHasKey('point', $pt);
            $this->assertArrayHasKey('lower', $pt);
            $this->assertArrayHasKey('upper', $pt);
        }
    }

    #[Test]
    public function forecast_defaults_to_90_day_horizon(): void
    {
        [$user, $workspace] = $this->makeUserWithWorkspace();

        $response = $this
            ->actingAs($user)
            ->getJson("/{$workspace->slug}/api/forecast");

        $response->assertOk();
        $this->assertCount(90, $response->json('forecast.points'));
    }

    #[Test]
    public function forecast_accepts_30_day_horizon_param(): void
    {
        [$user, $workspace] = $this->makeUserWithWorkspace();

        $response = $this
            ->actingAs($user)
            ->getJson("/{$workspace->slug}/api/forecast?horizon=30");

        $response->assertOk();
        $this->assertCount(30, $response->json('forecast.points'));
    }

    #[Test]
    public function invalid_horizon_falls_back_to_90(): void
    {
        [$user, $workspace] = $this->makeUserWithWorkspace();

        $response = $this
            ->actingAs($user)
            ->getJson("/{$workspace->slug}/api/forecast?horizon=999");

        $response->assertOk();
        $this->assertCount(90, $response->json('forecast.points'));
    }

    #[Test]
    public function unauthenticated_request_is_redirected(): void
    {
        $workspace = Workspace::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'has_ads'  => true,
        ]);

        $response = $this->getJson("/{$workspace->slug}/api/forecast");

        // 401 for JSON requests, 302 for web requests; both are non-200.
        $this->assertContains($response->status(), [302, 401]);
    }

    #[Test]
    public function forecast_uses_snapshot_data_when_available(): void
    {
        [$user, $workspace] = $this->makeUserWithWorkspace();

        $store = Store::factory()->create(['workspace_id' => $workspace->id]);

        // Satisfy the EnsureOnboardingComplete middleware: it requires a
        // historical_import_jobs row when a store exists, regardless of status.
        \Illuminate\Support\Facades\DB::table('historical_import_jobs')->insert([
            'workspace_id'         => $workspace->id,
            'integrationable_type' => \App\Models\Store::class,
            'integrationable_id'   => $store->id,
            'job_type'             => 'woocommerce_orders',
            'status'               => 'completed',
            'from_date'            => now()->subYear()->toDateString(),
            'to_date'              => now()->toDateString(),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Seed 60 days of daily snapshots with a known flat revenue so we can
        // assert that total_30d is in the right ballpark.
        // Use raw DB insert to avoid factory columns that may not match the
        // live schema (e.g. items_per_order was dropped).
        for ($i = 60; $i >= 1; $i--) {
            \Illuminate\Support\Facades\DB::table('daily_snapshots')->insert([
                'workspace_id' => $workspace->id,
                'store_id'     => $store->id,
                'date'         => now()->subDays($i)->toDateString(),
                'revenue'      => 1000.00,
                'orders_count' => 10,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->getJson("/{$workspace->slug}/api/forecast?horizon=30");

        $response->assertOk();

        $total30d = (float) $response->json('forecast.total_30d');
        $historyDays = (int) $response->json('forecast.history_days');

        // With 60 days of $1,000/day history, the 30-day forecast should be in
        // a reasonable range (not zero, not astronomically high).
        $this->assertGreaterThan(0, $total30d);
        $this->assertLessThan(100_000, $total30d);
        $this->assertSame(60, $historyDays);
    }

    #[Test]
    public function holidays_key_is_an_array(): void
    {
        [$user, $workspace] = $this->makeUserWithWorkspace();

        $response = $this
            ->actingAs($user)
            ->getJson("/{$workspace->slug}/api/forecast");

        $response->assertOk();
        $this->assertIsArray($response->json('holidays'));
    }
}
