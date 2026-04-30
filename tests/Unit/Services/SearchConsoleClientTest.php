<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\GoogleApiException;
use App\Exceptions\GoogleRateLimitException;
use App\Exceptions\GoogleTokenExpiredException;
use App\Services\Integrations\SearchConsole\SearchConsoleClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * HTTP-layer tests for SearchConsoleClient.
 *
 * Covers typed exception mapping (token expiry, rate limit, API error),
 * row normalisation from GSC's positional 'keys' format, and the pool path.
 * All HTTP calls are faked via Http::fake() — no real GSC API calls made.
 * Token refresh is not tested here (requires encrypted DB fixtures).
 */
class SearchConsoleClientTest extends TestCase
{
    private const TOKEN       = 'test_access_token';
    private const PROPERTY    = 'https://www.example.com/';

    private function makeClient(): SearchConsoleClient
    {
        return SearchConsoleClient::withToken(self::TOKEN);
    }

    private function gscUrl(string $path): string
    {
        return 'https://searchconsole.googleapis.com/webmasters/v3' . $path;
    }

    private function queryUrl(): string
    {
        return $this->gscUrl('/sites/' . rawurlencode(self::PROPERTY) . '/searchAnalytics/query*');
    }

    // -------------------------------------------------------------------------
    // Token expiry
    // -------------------------------------------------------------------------

    public function test_throws_token_expired_on_401(): void
    {
        Http::fake([
            $this->gscUrl('/sites*') => Http::response(null, 401),
        ]);

        $this->expectException(GoogleTokenExpiredException::class);

        $this->makeClient()->listProperties();
    }

    public function test_throws_token_expired_on_unauthenticated_status(): void
    {
        Http::fake([
            $this->queryUrl() => Http::response([
                'error' => ['status' => 'UNAUTHENTICATED', 'message' => 'Invalid credentials'],
            ], 401),
        ]);

        $this->expectException(GoogleTokenExpiredException::class);

        $this->makeClient()->queryDailyStats(self::PROPERTY, '2026-01-01', '2026-01-07');
    }

    // -------------------------------------------------------------------------
    // Rate limit
    // -------------------------------------------------------------------------

    public function test_throws_rate_limit_on_429(): void
    {
        Http::fake([
            $this->queryUrl() => Http::response(null, 429, ['Retry-After' => '60']),
        ]);

        $this->expectException(GoogleRateLimitException::class);

        $this->makeClient()->queryDailyStats(self::PROPERTY, '2026-01-01', '2026-01-07');
    }

    public function test_throws_rate_limit_on_resource_exhausted_status(): void
    {
        Http::fake([
            $this->queryUrl() => Http::response([
                'error' => ['status' => 'RESOURCE_EXHAUSTED', 'message' => 'Quota exceeded'],
            ], 429),
        ]);

        $this->expectException(GoogleRateLimitException::class);

        $this->makeClient()->queryDailyStats(self::PROPERTY, '2026-01-01', '2026-01-07');
    }

    public function test_rate_limit_exception_carries_retry_after(): void
    {
        Http::fake([
            $this->queryUrl() => Http::response(null, 429, ['Retry-After' => '90']),
        ]);

        try {
            $this->makeClient()->queryDailyStats(self::PROPERTY, '2026-01-01', '2026-01-07');
            $this->fail('Expected GoogleRateLimitException');
        } catch (GoogleRateLimitException $e) {
            $this->assertSame(90, $e->retryAfter);
        }
    }

    // -------------------------------------------------------------------------
    // Generic API errors
    // -------------------------------------------------------------------------

    public function test_throws_api_exception_on_500(): void
    {
        Http::fake([
            $this->queryUrl() => Http::response(null, 500),
        ]);

        $this->expectException(GoogleApiException::class);

        $this->makeClient()->queryDailyStats(self::PROPERTY, '2026-01-01', '2026-01-07');
    }

    public function test_throws_api_exception_on_403(): void
    {
        Http::fake([
            $this->queryUrl() => Http::response([
                'error' => ['status' => 'PERMISSION_DENIED', 'message' => 'User does not have access'],
            ], 403),
        ]);

        $this->expectException(GoogleApiException::class);

        $this->makeClient()->queryDailyStats(self::PROPERTY, '2026-01-01', '2026-01-07');
    }

    // -------------------------------------------------------------------------
    // listProperties
    // -------------------------------------------------------------------------

    public function test_list_properties_returns_site_entries(): void
    {
        Http::fake([
            $this->gscUrl('/sites*') => Http::response([
                'siteEntry' => [
                    ['siteUrl' => 'https://example.com/', 'permissionLevel' => 'siteOwner'],
                    ['siteUrl' => 'sc-domain:example.com', 'permissionLevel' => 'siteFullUser'],
                ],
            ], 200),
        ]);

        $properties = $this->makeClient()->listProperties();

        $this->assertCount(2, $properties);
        $this->assertSame('https://example.com/', $properties[0]['siteUrl']);
    }

    public function test_list_properties_returns_empty_when_no_entries(): void
    {
        Http::fake([
            $this->gscUrl('/sites*') => Http::response([], 200),
        ]);

        $properties = $this->makeClient()->listProperties();

        $this->assertSame([], $properties);
    }

    // -------------------------------------------------------------------------
    // queryDailyStats — row normalisation
    // -------------------------------------------------------------------------

    public function test_query_daily_stats_normalises_positional_keys_to_named_fields(): void
    {
        // GSC returns 'keys' as positional array matching the dimensions order
        Http::fake([
            $this->queryUrl() => Http::response([
                'rows' => [
                    ['keys' => ['2026-01-01'], 'clicks' => 120, 'impressions' => 3400, 'ctr' => 0.035, 'position' => 4.2],
                    ['keys' => ['2026-01-02'], 'clicks' => 95, 'impressions' => 2800, 'ctr' => 0.034, 'position' => 4.5],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->queryDailyStats(self::PROPERTY, '2026-01-01', '2026-01-02');

        $this->assertCount(2, $rows);
        $this->assertSame('2026-01-01', $rows[0]['date']);
        $this->assertSame(120, $rows[0]['clicks']);
        $this->assertSame(3400, $rows[0]['impressions']);
        $this->assertEqualsWithDelta(0.035, $rows[0]['ctr'], 0.0001);
        $this->assertEqualsWithDelta(4.2, $rows[0]['position'], 0.0001);
    }

    public function test_query_daily_stats_returns_empty_when_no_rows(): void
    {
        Http::fake([
            $this->queryUrl() => Http::response([], 200),
        ]);

        $rows = $this->makeClient()->queryDailyStats(self::PROPERTY, '2026-01-01', '2026-01-07');

        $this->assertSame([], $rows);
    }

    public function test_query_daily_stats_casts_ctr_and_position_to_null_when_absent(): void
    {
        Http::fake([
            $this->queryUrl() => Http::response([
                'rows' => [
                    ['keys' => ['2026-01-01'], 'clicks' => 10, 'impressions' => 100],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->queryDailyStats(self::PROPERTY, '2026-01-01', '2026-01-01');

        $this->assertNull($rows[0]['ctr']);
        $this->assertNull($rows[0]['position']);
    }

    // -------------------------------------------------------------------------
    // querySearchQueries — multi-dimension normalisation
    // -------------------------------------------------------------------------

    public function test_query_search_queries_maps_date_and_query_dimensions(): void
    {
        Http::fake([
            $this->queryUrl() => Http::response([
                'rows' => [
                    ['keys' => ['2026-01-01', 'buy shoes online'], 'clicks' => 5, 'impressions' => 200, 'ctr' => 0.025, 'position' => 3.1],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->querySearchQueries(self::PROPERTY, '2026-01-01', '2026-01-01');

        $this->assertSame('2026-01-01', $rows[0]['date']);
        $this->assertSame('buy shoes online', $rows[0]['query']);
        $this->assertSame(5, $rows[0]['clicks']);
    }

    // -------------------------------------------------------------------------
    // queryPages
    // -------------------------------------------------------------------------

    public function test_query_pages_maps_date_and_page_dimensions(): void
    {
        Http::fake([
            $this->queryUrl() => Http::response([
                'rows' => [
                    ['keys' => ['2026-01-01', 'https://example.com/products/shoe-a'], 'clicks' => 22, 'impressions' => 580, 'ctr' => 0.038, 'position' => 2.7],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->queryPages(self::PROPERTY, '2026-01-01', '2026-01-01');

        $this->assertSame('2026-01-01', $rows[0]['date']);
        $this->assertSame('https://example.com/products/shoe-a', $rows[0]['page']);
    }

    // -------------------------------------------------------------------------
    // queryDailyStatsBreakdown — 4-dimension normalisation
    // -------------------------------------------------------------------------

    public function test_query_daily_stats_breakdown_maps_date_device_country(): void
    {
        Http::fake([
            $this->queryUrl() => Http::response([
                'rows' => [
                    ['keys' => ['2026-01-01', 'MOBILE', 'deu'], 'clicks' => 30, 'impressions' => 800, 'ctr' => 0.0375, 'position' => 3.8],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->queryDailyStatsBreakdown(self::PROPERTY, '2026-01-01', '2026-01-01');

        $this->assertSame('2026-01-01', $rows[0]['date']);
        $this->assertSame('MOBILE', $rows[0]['device']);
        $this->assertSame('deu', $rows[0]['country']);
    }
}
