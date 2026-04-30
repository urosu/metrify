<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\GA4PropertyNotFoundException;
use App\Exceptions\GA4QuotaExceededException;
use App\Models\Ga4Property;
use App\Services\Integrations\Google\GA4Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * HTTP-layer tests for GA4Client.
 *
 * Covers quota exceeded (429 + RESOURCE_EXHAUSTED), property not found
 * (403 + NOT_FOUND + PERMISSION_DENIED), successful row parsing,
 * GA4 YYYYMMDD date normalisation, data_state provisional/final, and
 * country_code / device_category normalisation.
 *
 * The retry-with-sleep backoff path is NOT tested here — it would add 30s
 * of sleep per run. Quota/permission errors are immediate (no retry).
 */
class GA4ClientTest extends TestCase
{
    private const TOKEN       = 'test_access_token';
    private const PROPERTY_ID = 'properties/123456789';

    private function makeClient(): GA4Client
    {
        $property = new Ga4Property(['property_id' => self::PROPERTY_ID]);

        return new GA4Client($property, self::TOKEN);
    }

    private function apiUrl(): string
    {
        // property_id = 'properties/123456789', API_BASE = '...v1beta'
        // URL = '...v1beta/properties/123456789:runReport'
        return 'https://analyticsdata.googleapis.com/v1beta/properties/123456789:runReport*';
    }

    // -------------------------------------------------------------------------
    // Quota exceeded
    // -------------------------------------------------------------------------

    public function test_throws_quota_exceeded_on_429(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response(null, 429),
        ]);

        $this->expectException(GA4QuotaExceededException::class);

        $this->makeClient()->fetchDailySessions(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-03'));
    }

    public function test_throws_quota_exceeded_on_resource_exhausted_body(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response([
                'error' => ['status' => 'RESOURCE_EXHAUSTED', 'message' => 'Quota exceeded'],
            ], 429),
        ]);

        $this->expectException(GA4QuotaExceededException::class);

        $this->makeClient()->fetchDailySessions(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-03'));
    }

    // -------------------------------------------------------------------------
    // Property not found
    // -------------------------------------------------------------------------

    public function test_throws_property_not_found_on_403(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response(null, 403),
        ]);

        $this->expectException(GA4PropertyNotFoundException::class);

        $this->makeClient()->fetchDailySessions(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-03'));
    }

    public function test_throws_property_not_found_on_not_found_status(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response([
                'error' => ['status' => 'NOT_FOUND', 'message' => 'Property does not exist'],
            ], 404),
        ]);

        $this->expectException(GA4PropertyNotFoundException::class);

        $this->makeClient()->fetchDailySessions(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-03'));
    }

    public function test_throws_property_not_found_on_permission_denied_body(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response([
                'error' => ['status' => 'PERMISSION_DENIED', 'message' => 'User does not have sufficient permissions'],
            ], 403),
        ]);

        $this->expectException(GA4PropertyNotFoundException::class);

        $this->makeClient()->fetchDailySessions(Carbon::parse('2026-01-01'), Carbon::parse('2026-01-03'));
    }

    // -------------------------------------------------------------------------
    // Successful response — row parsing
    // -------------------------------------------------------------------------

    public function test_returns_parsed_rows_on_success(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response([
                'rows' => [
                    [
                        'dimensionValues' => [
                            ['value' => '20260101'],
                            ['value' => 'Germany'],
                            ['value' => 'desktop'],
                        ],
                        'metricValues' => [
                            ['value' => '1200'],
                            ['value' => '980'],
                        ],
                    ],
                    [
                        'dimensionValues' => [
                            ['value' => '20260102'],
                            ['value' => 'United States'],
                            ['value' => 'mobile'],
                        ],
                        'metricValues' => [
                            ['value' => '3400'],
                            ['value' => '2800'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->fetchDailySessions(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-02'),
        );

        $this->assertCount(2, $rows);
        $this->assertSame(1200, $rows[0]->sessions);
        $this->assertSame(980, $rows[0]->users);
        $this->assertSame('desktop', $rows[0]->device_category);
        $this->assertSame(3400, $rows[1]->sessions);
        $this->assertSame('mobile', $rows[1]->device_category);
    }

    public function test_returns_empty_collection_when_no_rows(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response(['rows' => []], 200),
        ]);

        $rows = $this->makeClient()->fetchDailySessions(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-03'),
        );

        $this->assertTrue($rows->isEmpty());
    }

    public function test_returns_empty_collection_when_rows_key_absent(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response([], 200),
        ]);

        $rows = $this->makeClient()->fetchDailySessions(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-03'),
        );

        $this->assertTrue($rows->isEmpty());
    }

    // -------------------------------------------------------------------------
    // Date normalisation (YYYYMMDD → YYYY-MM-DD)
    // -------------------------------------------------------------------------

    public function test_converts_ga4_date_format_to_iso(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response([
                'rows' => [
                    [
                        'dimensionValues' => [
                            ['value' => '20260315'],
                            ['value' => 'France'],
                            ['value' => 'tablet'],
                        ],
                        'metricValues' => [['value' => '500'], ['value' => '400']],
                    ],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->fetchDailySessions(
            Carbon::parse('2026-03-15'),
            Carbon::parse('2026-03-15'),
        );

        $this->assertSame('2026-03-15', $rows[0]->date);
    }

    // -------------------------------------------------------------------------
    // data_state — provisional vs final
    // -------------------------------------------------------------------------

    public function test_marks_recent_dates_as_provisional(): void
    {
        // A date that is within the last 3 days is 'provisional'
        $recentDate = now()->subDays(1)->toDateString();
        $ga4Date    = str_replace('-', '', $recentDate);

        Http::fake([
            $this->apiUrl() => Http::response([
                'rows' => [
                    [
                        'dimensionValues' => [
                            ['value' => $ga4Date],
                            ['value' => 'Germany'],
                            ['value' => 'desktop'],
                        ],
                        'metricValues' => [['value' => '100'], ['value' => '80']],
                    ],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->fetchDailySessions(
            Carbon::parse($recentDate),
            Carbon::parse($recentDate),
        );

        $this->assertSame('provisional', $rows[0]->data_state);
    }

    public function test_marks_old_dates_as_final(): void
    {
        // A date older than 3 days should be 'final'
        $oldDate = '2026-01-01';
        $ga4Date = '20260101';

        Http::fake([
            $this->apiUrl() => Http::response([
                'rows' => [
                    [
                        'dimensionValues' => [
                            ['value' => $ga4Date],
                            ['value' => 'Germany'],
                            ['value' => 'desktop'],
                        ],
                        'metricValues' => [['value' => '2000'], ['value' => '1500']],
                    ],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->fetchDailySessions(
            Carbon::parse($oldDate),
            Carbon::parse($oldDate),
        );

        $this->assertSame('final', $rows[0]->data_state);
    }

    // -------------------------------------------------------------------------
    // country_code normalisation
    // -------------------------------------------------------------------------

    public function test_normalises_country_to_first_two_letters_uppercase(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response([
                'rows' => [
                    [
                        'dimensionValues' => [
                            ['value' => '20260101'],
                            ['value' => 'Germany'],  // GA4 returns full name; we take first 2 chars → 'GE'
                            ['value' => 'desktop'],
                        ],
                        'metricValues' => [['value' => '100'], ['value' => '80']],
                    ],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->fetchDailySessions(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-01'),
        );

        // GA4 returns full country names like "Germany" — client takes first 2 chars uppercased
        $this->assertSame('GE', $rows[0]->country_code);
    }

    public function test_country_code_is_null_when_not_set(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response([
                'rows' => [
                    [
                        'dimensionValues' => [
                            ['value' => '20260101'],
                            ['value' => '(not set)'],
                            ['value' => 'desktop'],
                        ],
                        'metricValues' => [['value' => '100'], ['value' => '80']],
                    ],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->fetchDailySessions(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-01'),
        );

        $this->assertNull($rows[0]->country_code);
    }

    public function test_device_category_is_null_when_not_set(): void
    {
        Http::fake([
            $this->apiUrl() => Http::response([
                'rows' => [
                    [
                        'dimensionValues' => [
                            ['value' => '20260101'],
                            ['value' => 'Germany'],
                            ['value' => '(not set)'],
                        ],
                        'metricValues' => [['value' => '100'], ['value' => '80']],
                    ],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->fetchDailySessions(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-01'),
        );

        $this->assertNull($rows[0]->device_category);
    }
}
