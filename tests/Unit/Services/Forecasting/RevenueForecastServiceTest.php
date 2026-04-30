<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Forecasting;

use App\Services\Forecasting\ForecastResult;
use App\Services\Forecasting\RevenueForecastService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for RevenueForecastService.
 *
 * All DB interactions are mocked via DB::shouldReceive() so no real database
 * or migration is required.  Tests are pure in-process.
 */
class RevenueForecastServiceTest extends TestCase
{
    private RevenueForecastService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(RevenueForecastService::class);

        // Disable caching so every call exercises the compute path.
        Cache::shouldReceive('remember')->andReturnUsing(
            fn ($key, $ttl, $cb) => $cb()
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build a synthetic DB result set with a known daily pattern:
     *   base revenue × day-of-week multiplier for $days days ending yesterday.
     *
     * @param  float[] $dowMultipliers  indexed 0 (Sun) … 6 (Sat)
     * @return object[]  mimics the stdClass rows from DB::select()
     */
    private function buildHistory(
        int $days,
        float $baseRevenue,
        array $dowMultipliers = [],
    ): array {
        $rows = [];
        for ($i = $days; $i >= 1; $i--) {
            $date = now()->subDays($i)->toDateString();
            $dow  = (int) date('w', strtotime($date));
            $mult = $dowMultipliers[$dow] ?? 1.0;
            $row  = new \stdClass();
            $row->day            = $date;
            $row->total_revenue  = (string) round($baseRevenue * $mult, 4);
            $rows[] = $row;
        }
        return $rows;
    }

    private function mockDb(array $rows): void
    {
        DB::shouldReceive('select')
            ->once()
            ->andReturn($rows);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    #[Test]
    public function it_returns_a_forecast_result_instance(): void
    {
        $this->mockDb($this->buildHistory(90, 500.0));

        $result = $this->service->forecast(1, 30);

        $this->assertInstanceOf(ForecastResult::class, $result);
    }

    #[Test]
    public function it_returns_correct_number_of_forecast_points(): void
    {
        $this->mockDb($this->buildHistory(180, 1000.0));

        $result = $this->service->forecast(1, 30);

        $this->assertCount(30, $result->points);
    }

    #[Test]
    public function it_returns_90_points_for_90_day_horizon(): void
    {
        $this->mockDb($this->buildHistory(180, 1000.0));

        $result = $this->service->forecast(1, 90);

        $this->assertCount(90, $result->points);
    }

    #[Test]
    public function each_point_has_required_keys(): void
    {
        $this->mockDb($this->buildHistory(60, 800.0));

        $result = $this->service->forecast(1, 7);

        foreach ($result->points as $pt) {
            $this->assertArrayHasKey('date',  $pt);
            $this->assertArrayHasKey('point', $pt);
            $this->assertArrayHasKey('lower', $pt);
            $this->assertArrayHasKey('upper', $pt);
        }
    }

    #[Test]
    public function confidence_band_is_correctly_ordered(): void
    {
        $this->mockDb($this->buildHistory(90, 500.0));

        $result = $this->service->forecast(1, 30);

        foreach ($result->points as $pt) {
            $this->assertLessThanOrEqual($pt['point'], $pt['upper'], 'upper >= point');
            $this->assertGreaterThanOrEqual($pt['point'], $pt['lower'], 'lower <= point');
        }
    }

    #[Test]
    public function point_estimates_are_non_negative(): void
    {
        $this->mockDb($this->buildHistory(90, 100.0));

        $result = $this->service->forecast(1, 30);

        foreach ($result->points as $pt) {
            $this->assertGreaterThanOrEqual(0.0, $pt['point']);
            $this->assertGreaterThanOrEqual(0.0, $pt['lower']);
        }
    }

    #[Test]
    public function total_30d_matches_sum_of_first_30_points(): void
    {
        $this->mockDb($this->buildHistory(180, 300.0));

        $result = $this->service->forecast(1, 90);

        $expectedSum = array_sum(array_column(array_slice($result->points, 0, 30), 'point'));
        $this->assertEqualsWithDelta($expectedSum, $result->total30d, 0.01);
    }

    #[Test]
    public function day_of_week_seasonality_is_captured(): void
    {
        // Weekends (Sat=6, Sun=0) generate 2× the base revenue.
        $dowMultipliers = [0 => 2.0, 1 => 1.0, 2 => 1.0, 3 => 1.0, 4 => 1.0, 5 => 1.0, 6 => 2.0];
        $rows = $this->buildHistory(180, 500.0, $dowMultipliers);
        $this->mockDb($rows);

        $result = $this->service->forecast(1, 14);

        // Extract weekend vs weekday point estimates.
        $weekendPoints  = [];
        $weekdayPoints  = [];
        foreach ($result->points as $pt) {
            $dow = (int) date('w', strtotime($pt['date']));
            if (in_array($dow, [0, 6], true)) {
                $weekendPoints[] = $pt['point'];
            } else {
                $weekdayPoints[] = $pt['point'];
            }
        }

        if (empty($weekendPoints) || empty($weekdayPoints)) {
            // No weekend or weekday in this 14-day window — skip assertion.
            $this->addToAssertionCount(1);
            return;
        }

        $avgWeekend = array_sum($weekendPoints) / count($weekendPoints);
        $avgWeekday = array_sum($weekdayPoints) / count($weekdayPoints);

        // Weekend average should be meaningfully higher than weekday average.
        $this->assertGreaterThan($avgWeekday * 1.3, $avgWeekend,
            'Forecast should reflect weekend > weekday seasonality pattern');
    }

    #[Test]
    public function it_falls_back_to_flat_mean_with_sparse_history(): void
    {
        // Only 5 days of history — below MIN_HISTORY_DAYS (14).
        $rows = $this->buildHistory(5, 200.0);
        $this->mockDb($rows);

        $result = $this->service->forecast(1, 7);

        $this->assertCount(7, $result->points);
        // All points should be equal (flat mean fallback).
        $firstPoint = $result->points[0]['point'];
        foreach ($result->points as $pt) {
            $this->assertEqualsWithDelta($firstPoint, $pt['point'], 0.01,
                'Flat mean fallback should produce identical point estimates');
        }
        // Confidence band should be zero-width (lower = upper = point).
        $this->assertEqualsWithDelta($result->points[0]['lower'], $result->points[0]['upper'], 0.01);
    }

    #[Test]
    public function it_handles_empty_history_gracefully(): void
    {
        $this->mockDb([]);

        $result = $this->service->forecast(1, 30);

        $this->assertCount(30, $result->points);
        $this->assertEqualsWithDelta(0.0, $result->total30d, 0.01);
    }

    #[Test]
    public function history_days_is_reported_correctly(): void
    {
        $rows = $this->buildHistory(60, 500.0);
        $this->mockDb($rows);

        $result = $this->service->forecast(1, 30);

        $this->assertSame(60, $result->historyDays);
    }

    #[Test]
    public function forecast_dates_are_sequential_starting_tomorrow(): void
    {
        $this->mockDb($this->buildHistory(90, 500.0));

        $result = $this->service->forecast(1, 7);

        $expected = now()->addDay()->toDateString();
        $this->assertSame($expected, $result->points[0]['date']);

        for ($i = 1; $i < count($result->points); $i++) {
            $prev = new \DateTime($result->points[$i - 1]['date']);
            $curr = new \DateTime($result->points[$i]['date']);
            $diff = (int) $prev->diff($curr)->days;
            $this->assertSame(1, $diff, 'Forecast dates should be consecutive calendar days');
        }
    }
}
