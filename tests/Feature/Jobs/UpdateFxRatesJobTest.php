<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\UpdateFxRatesJob;
use App\Models\FxRate;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UpdateFxRatesJobTest extends TestCase
{
    use RefreshDatabase;

    private const FRANKFURTER_URL = 'https://api.frankfurter.test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.frankfurter.url' => self::FRANKFURTER_URL]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a Frankfurter v2 flat-array response for a single date.
     */
    private function frankfurterResponse(string $date, array $rates = []): array
    {
        $entries = [];
        foreach ($rates as $quote => $rate) {
            $entries[] = ['date' => $date, 'base' => 'EUR', 'quote' => $quote, 'rate' => $rate];
        }
        return $entries;
    }

    private function runJob(?Carbon $from = null, ?Carbon $to = null): void
    {
        (new UpdateFxRatesJob($from, $to))->handle();
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_fetches_and_upserts_missing_dates(): void
    {
        Http::fake([
            self::FRANKFURTER_URL . '/*' => Http::response(
                $this->frankfurterResponse('2026-01-01', ['USD' => 1.08, 'GBP' => 0.86]),
                200,
            ),
        ]);

        $date = Carbon::parse('2026-01-01');
        $this->runJob($date, $date);

        $this->assertDatabaseHas('fx_rates', [
            'base_currency'   => 'EUR',
            'target_currency' => 'USD',
            'rate'            => 1.08,
        ]);
        $this->assertDatabaseHas('fx_rates', [
            'base_currency'   => 'EUR',
            'target_currency' => 'GBP',
            'rate'            => 0.86,
        ]);
    }

    public function test_skips_dates_already_in_cache(): void
    {
        Http::fake();

        $date = Carbon::parse('2026-01-01');

        FxRate::factory()->create([
            'base_currency'   => 'EUR',
            'target_currency' => 'USD',
            'rate'            => 1.08,
            'date'            => $date->toDateString(),
        ]);

        $this->runJob($date, $date);

        Http::assertNothingSent();
    }

    public function test_from_after_to_returns_early(): void
    {
        Http::fake();

        $this->runJob(
            from: Carbon::parse('2026-01-05'),
            to:   Carbon::parse('2026-01-01'),
        );

        Http::assertNothingSent();
    }

    public function test_api_error_produces_no_rows(): void
    {
        Http::fake([
            self::FRANKFURTER_URL . '/*' => Http::response(null, 500),
        ]);

        // When run directly (not via queue), $this->fail() on a job is a no-op
        // that does not throw. Verify the error path leaves the DB empty.
        $date = Carbon::parse('2026-01-01');
        $this->runJob($date, $date);

        $this->assertDatabaseCount('fx_rates', 0);
    }

    public function test_empty_api_response_logs_warning_no_upsert(): void
    {
        Log::spy();

        Http::fake([
            self::FRANKFURTER_URL . '/*' => Http::response([], 200),
        ]);

        $date = Carbon::parse('2026-01-01');
        $this->runJob($date, $date);

        $this->assertDatabaseCount('fx_rates', 0);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_upserts_correct_rows(): void
    {
        Http::fake([
            self::FRANKFURTER_URL . '/*' => Http::response([
                ['date' => '2026-02-01', 'base' => 'EUR', 'quote' => 'CHF', 'rate' => 0.93],
            ], 200),
        ]);

        $date = Carbon::parse('2026-02-01');
        $this->runJob($date, $date);

        $this->assertDatabaseHas('fx_rates', [
            'base_currency'   => 'EUR',
            'target_currency' => 'CHF',
            'rate'            => 0.93,
            'date'            => '2026-02-01',
        ]);
    }

    public function test_idempotent_on_second_run(): void
    {
        Http::fake([
            self::FRANKFURTER_URL . '/*' => Http::response(
                $this->frankfurterResponse('2026-01-01', ['USD' => 1.08]),
                200,
            ),
        ]);

        $date = Carbon::parse('2026-01-01');

        $this->runJob($date, $date);
        $countAfterFirst = FxRate::count();

        // Second run: date already cached → no HTTP call, no new rows
        $this->runJob($date, $date);
        $countAfterSecond = FxRate::count();

        $this->assertSame($countAfterFirst, $countAfterSecond);
        Http::assertSentCount(1); // Only one real HTTP call across both runs
    }

    public function test_request_includes_correct_date_range_params(): void
    {
        Http::fake([
            self::FRANKFURTER_URL . '/*' => Http::response(
                $this->frankfurterResponse('2026-03-01', ['USD' => 1.07]),
                200,
            ),
        ]);

        $from = Carbon::parse('2026-03-01');
        $to   = Carbon::parse('2026-03-01');
        $this->runJob($from, $to);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.frankfurter.test')
                && $request['from'] === '2026-03-01'
                && $request['base'] === 'EUR';
        });
    }
}
