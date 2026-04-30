<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\FacebookApiException;
use App\Exceptions\FacebookRateLimitException;
use App\Exceptions\FacebookTokenExpiredException;
use App\Services\Integrations\Facebook\FacebookAdsClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * HTTP-layer tests for FacebookAdsClient.
 *
 * Covers typed exception mapping (token expiry, rate limit, API error),
 * proactive BUC header throttle detection, pagination, and error code handling.
 * All HTTP calls are faked via Http::fake() — no real Graph API calls made.
 */
class FacebookAdsClientTest extends TestCase
{
    private const TOKEN = 'test_access_token';
    private const ACCOUNT_ID = '1234567890';

    private function makeClient(): FacebookAdsClient
    {
        return new FacebookAdsClient(self::TOKEN);
    }

    private function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/v25.0' . $path;
    }

    // -------------------------------------------------------------------------
    // Token expiry
    // -------------------------------------------------------------------------

    public function test_throws_token_expired_on_error_code_190(): void
    {
        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response([
                'error' => ['code' => 190, 'message' => 'Invalid OAuth access token'],
            ], 200),
        ]);

        $this->expectException(FacebookTokenExpiredException::class);

        $this->makeClient()->fetchAdAccounts();
    }

    public function test_throws_token_expired_on_error_code_190_with_http_401(): void
    {
        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response([
                'error' => ['code' => 190, 'message' => 'Session has expired'],
            ], 401),
        ]);

        $this->expectException(FacebookTokenExpiredException::class);

        $this->makeClient()->fetchAdAccounts();
    }

    // -------------------------------------------------------------------------
    // Rate limit — error codes in response body
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('rateLimitCodeProvider')]
    public function test_throws_rate_limit_on_known_error_codes(int $code): void
    {
        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response([
                'error' => ['code' => $code, 'message' => 'Rate limit hit'],
            ], 200),
        ]);

        $this->expectException(FacebookRateLimitException::class);

        $this->makeClient()->fetchAdAccounts();
    }

    public static function rateLimitCodeProvider(): array
    {
        return [
            'app burst (4)'            => [4],
            'app call volume (17)'     => [17],
            'bulk query limit (613)'   => [613],
            'ad account BUC (80000)'   => [80000],
            'ad account BUC (80003)'   => [80003],
            'ad account BUC (80004)'   => [80004],
            'ad account BUC (80014)'   => [80014],
        ];
    }

    public function test_rate_limit_exception_carries_retry_after_from_header(): void
    {
        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response(
                ['error' => ['code' => 17, 'message' => 'Rate limit']],
                429,
                ['Retry-After' => '120'],
            ),
        ]);

        try {
            $this->makeClient()->fetchAdAccounts();
            $this->fail('Expected FacebookRateLimitException');
        } catch (FacebookRateLimitException $e) {
            // withJitter applies ±20%; 120s ± 24s gives range [96, 144]
            $this->assertGreaterThanOrEqual(96, $e->retryAfter);
            $this->assertLessThanOrEqual(144, $e->retryAfter);
        }
    }

    // -------------------------------------------------------------------------
    // Proactive rate limit — X-Business-Use-Case-Usage header
    // -------------------------------------------------------------------------

    public function test_throws_rate_limit_proactively_when_buc_header_exceeds_dev_threshold(): void
    {
        // Dev tier threshold is 57%. Inject usage at 60% to trigger proactive backoff.
        $bucHeader = json_encode([
            'act_' . self::ACCOUNT_ID => [
                ['call_count' => 60, 'total_cputime' => 40, 'total_time' => 40, 'type' => 'ADS_MANAGEMENT', 'estimated_time_to_regain_access' => 0],
            ],
        ]);

        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response(
                ['data' => []],
                200,
                ['X-Business-Use-Case-Usage' => $bucHeader],
            ),
        ]);

        // Ensure we're on dev tier (default)
        config(['services.facebook.api_tier' => 'dev']);

        $this->expectException(FacebookRateLimitException::class);

        $this->makeClient()->fetchAdAccounts();
    }

    public function test_buc_header_below_threshold_does_not_throw(): void
    {
        // 30% usage — below the 57% dev threshold
        $bucHeader = json_encode([
            'act_' . self::ACCOUNT_ID => [
                ['call_count' => 30, 'total_cputime' => 20, 'total_time' => 20, 'type' => 'ADS_MANAGEMENT', 'estimated_time_to_regain_access' => 0],
            ],
        ]);

        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response(
                ['data' => [['id' => 'act_123', 'name' => 'Test', 'currency' => 'USD']]],
                200,
                ['X-Business-Use-Case-Usage' => $bucHeader],
            ),
        ]);

        config(['services.facebook.api_tier' => 'dev']);

        $accounts = $this->makeClient()->fetchAdAccounts();

        $this->assertCount(1, $accounts);
    }

    public function test_throws_rate_limit_proactively_when_insights_throttle_header_exceeds_threshold(): void
    {
        $insightsThrottle = json_encode([
            'app_id_util_pct' => 65,
            'acc_id_util_pct' => 40,
        ]);

        Http::fake([
            $this->graphUrl('/act_' . self::ACCOUNT_ID . '/insights*') => Http::response(
                ['data' => []],
                200,
                ['X-FB-Ads-Insights-Throttle' => $insightsThrottle],
            ),
        ]);

        config(['services.facebook.api_tier' => 'dev']);

        $this->expectException(FacebookRateLimitException::class);

        $this->makeClient()->fetchInsights(self::ACCOUNT_ID, 'campaign', '2026-01-01', '2026-01-07');
    }

    public function test_rate_limit_exception_carries_usage_pct_from_buc_header(): void
    {
        // 75% exceeds the dev-tier threshold of 57%, so a proactive exception is thrown.
        $bucHeader = json_encode([
            'act_' . self::ACCOUNT_ID => [
                ['call_count' => 75, 'total_cputime' => 50, 'total_time' => 50, 'type' => 'ADS_MANAGEMENT', 'estimated_time_to_regain_access' => 0],
            ],
        ]);

        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response(
                ['data' => []],
                200,
                ['X-Business-Use-Case-Usage' => $bucHeader],
            ),
        ]);

        config(['services.facebook.api_tier' => 'dev']);

        try {
            $this->makeClient()->fetchAdAccounts();
            $this->fail('Expected FacebookRateLimitException');
        } catch (FacebookRateLimitException $e) {
            $this->assertSame(75, $e->usagePct);
        }
    }

    // -------------------------------------------------------------------------
    // Generic API errors
    // -------------------------------------------------------------------------

    public function test_throws_api_exception_on_unknown_error_code(): void
    {
        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response([
                'error' => ['code' => 100, 'message' => 'Invalid parameter'],
            ], 200),
        ]);

        $this->expectException(FacebookApiException::class);

        $this->makeClient()->fetchAdAccounts();
    }

    public function test_throws_api_exception_on_failed_http_response_without_error_body(): void
    {
        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response(null, 503),
        ]);

        $this->expectException(FacebookApiException::class);

        $this->makeClient()->fetchAdAccounts();
    }

    // -------------------------------------------------------------------------
    // fetchAdAccounts — success + pagination
    // -------------------------------------------------------------------------

    public function test_returns_ad_accounts_on_success(): void
    {
        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response([
                'data' => [
                    ['id' => 'act_111', 'name' => 'Account A', 'currency' => 'USD'],
                    ['id' => 'act_222', 'name' => 'Account B', 'currency' => 'EUR'],
                ],
            ], 200),
        ]);

        $accounts = $this->makeClient()->fetchAdAccounts();

        $this->assertCount(2, $accounts);
        $this->assertSame('act_111', $accounts[0]['id']);
        $this->assertSame('EUR', $accounts[1]['currency']);
    }

    public function test_paginates_through_multiple_pages(): void
    {
        $page1NextUrl = 'https://graph.facebook.com/v25.0/me/adaccounts?after=cursor1';

        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::sequence()
                ->push([
                    'data'   => [['id' => 'act_100', 'name' => 'A', 'currency' => 'USD']],
                    'paging' => ['next' => $page1NextUrl],
                ], 200)
                ->push([
                    'data' => [['id' => 'act_200', 'name' => 'B', 'currency' => 'GBP']],
                ], 200),
        ]);

        $accounts = $this->makeClient()->fetchAdAccounts();

        $this->assertCount(2, $accounts);
        $this->assertSame('act_100', $accounts[0]['id']);
        $this->assertSame('act_200', $accounts[1]['id']);
    }

    public function test_returns_empty_array_when_no_accounts(): void
    {
        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response(['data' => []], 200),
        ]);

        $accounts = $this->makeClient()->fetchAdAccounts();

        $this->assertSame([], $accounts);
    }

    // -------------------------------------------------------------------------
    // fetchCampaigns
    // -------------------------------------------------------------------------

    public function test_fetch_campaigns_returns_data(): void
    {
        Http::fake([
            $this->graphUrl('/act_' . self::ACCOUNT_ID . '/campaigns*') => Http::response([
                'data' => [
                    ['id' => 'camp_1', 'name' => 'Summer Sale', 'effective_status' => 'ACTIVE'],
                    ['id' => 'camp_2', 'name' => 'BFCM', 'effective_status' => 'PAUSED'],
                ],
            ], 200),
        ]);

        $campaigns = $this->makeClient()->fetchCampaigns(self::ACCOUNT_ID);

        $this->assertCount(2, $campaigns);
        $this->assertSame('camp_1', $campaigns[0]['id']);
    }

    public function test_fetch_campaigns_throws_token_expired(): void
    {
        Http::fake([
            $this->graphUrl('/act_' . self::ACCOUNT_ID . '/campaigns*') => Http::response([
                'error' => ['code' => 190, 'message' => 'Token expired'],
            ], 200),
        ]);

        $this->expectException(FacebookTokenExpiredException::class);

        $this->makeClient()->fetchCampaigns(self::ACCOUNT_ID);
    }

    // -------------------------------------------------------------------------
    // fetchInsights
    // -------------------------------------------------------------------------

    public function test_fetch_insights_returns_flat_rows(): void
    {
        Http::fake([
            $this->graphUrl('/act_' . self::ACCOUNT_ID . '/insights*') => Http::response([
                'data' => [
                    ['campaign_id' => 'camp_1', 'spend' => '100.50', 'impressions' => '5000'],
                    ['campaign_id' => 'camp_2', 'spend' => '200.00', 'impressions' => '10000'],
                ],
            ], 200),
        ]);

        $rows = $this->makeClient()->fetchInsights(
            self::ACCOUNT_ID,
            'campaign',
            '2026-01-01',
            '2026-01-07',
        );

        $this->assertCount(2, $rows);
        $this->assertSame('100.50', $rows[0]['spend']);
    }

    public function test_fetch_insights_throws_rate_limit_on_code_613(): void
    {
        Http::fake([
            $this->graphUrl('/act_' . self::ACCOUNT_ID . '/insights*') => Http::response([
                'error' => ['code' => 613, 'message' => 'Insights rate limit'],
            ], 200),
        ]);

        $this->expectException(FacebookRateLimitException::class);

        $this->makeClient()->fetchInsights(self::ACCOUNT_ID, 'campaign', '2026-01-01', '2026-01-07');
    }

    // -------------------------------------------------------------------------
    // submitAsyncInsightsJob
    // -------------------------------------------------------------------------

    public function test_submit_async_job_returns_report_run_id(): void
    {
        Http::fake([
            $this->graphUrl('/act_' . self::ACCOUNT_ID . '/insights*') => Http::response([
                'report_run_id' => 'run_abc123',
            ], 200),
        ]);

        $runId = $this->makeClient()->submitAsyncInsightsJob(
            self::ACCOUNT_ID,
            '2026-01-01',
            '2026-03-31',
            'campaign',
        );

        $this->assertSame('run_abc123', $runId);
    }

    public function test_submit_async_job_throws_on_missing_report_run_id(): void
    {
        Http::fake([
            $this->graphUrl('/act_' . self::ACCOUNT_ID . '/insights*') => Http::response([
                'data' => [],
            ], 200),
        ]);

        $this->expectException(FacebookApiException::class);
        $this->expectExceptionMessage('no report_run_id');

        $this->makeClient()->submitAsyncInsightsJob(self::ACCOUNT_ID, '2026-01-01', '2026-03-31');
    }

    public function test_submit_async_job_throws_rate_limit(): void
    {
        Http::fake([
            $this->graphUrl('/act_' . self::ACCOUNT_ID . '/insights*') => Http::response([
                'error' => ['code' => 80000, 'message' => 'Ad account throttled'],
            ], 200),
        ]);

        $this->expectException(FacebookRateLimitException::class);

        $this->makeClient()->submitAsyncInsightsJob(self::ACCOUNT_ID, '2026-01-01', '2026-03-31');
    }

    // -------------------------------------------------------------------------
    // pollAsyncJob
    // -------------------------------------------------------------------------

    public function test_poll_async_job_returns_status(): void
    {
        Http::fake([
            $this->graphUrl('/run_abc123*') => Http::response([
                'async_status'              => 'Job Completed',
                'async_percent_completion'  => 100,
            ], 200),
        ]);

        $status = $this->makeClient()->pollAsyncJob('run_abc123');

        $this->assertSame('Job Completed', $status['async_status']);
        $this->assertSame(100, $status['async_percent_completion']);
    }

    public function test_poll_async_job_throws_token_expired(): void
    {
        Http::fake([
            $this->graphUrl('/run_abc123*') => Http::response([
                'error' => ['code' => 190, 'message' => 'Token expired'],
            ], 200),
        ]);

        $this->expectException(FacebookTokenExpiredException::class);

        $this->makeClient()->pollAsyncJob('run_abc123');
    }

    // -------------------------------------------------------------------------
    // streamAsyncJobResults
    // -------------------------------------------------------------------------

    public function test_stream_async_job_results_invokes_callback_per_page(): void
    {
        $page1NextUrl = 'https://graph.facebook.com/v25.0/run_xyz/insights?after=cursor1';

        Http::fake([
            $this->graphUrl('/run_xyz/insights*') => Http::sequence()
                ->push([
                    'data'   => [['campaign_id' => 'c1', 'spend' => '50.00']],
                    'paging' => ['next' => $page1NextUrl],
                ], 200)
                ->push([
                    'data' => [['campaign_id' => 'c2', 'spend' => '75.00']],
                ], 200),
        ]);

        $pages = [];
        $this->makeClient()->streamAsyncJobResults('run_xyz', function (array $page) use (&$pages): void {
            $pages[] = $page;
        });

        $this->assertCount(2, $pages);
        $this->assertSame('c1', $pages[0][0]['campaign_id']);
        $this->assertSame('c2', $pages[1][0]['campaign_id']);
    }

    public function test_stream_async_job_results_throws_rate_limit(): void
    {
        Http::fake([
            $this->graphUrl('/run_xyz/insights*') => Http::response([
                'error' => ['code' => 4, 'message' => 'App rate limit'],
            ], 200),
        ]);

        $this->expectException(FacebookRateLimitException::class);

        $this->makeClient()->streamAsyncJobResults('run_xyz', fn () => null);
    }

    // -------------------------------------------------------------------------
    // fetchAllAdAccounts — business manager deduplication
    // -------------------------------------------------------------------------

    public function test_fetch_all_ad_accounts_deduplicates_across_personal_and_business(): void
    {
        Http::fake([
            // Personal accounts
            $this->graphUrl('/me/adaccounts*') => Http::response([
                'data' => [['id' => 'act_111', 'name' => 'Personal', 'currency' => 'USD']],
            ], 200),
            // Business list
            $this->graphUrl('/me/businesses*') => Http::response([
                'data' => [['id' => 'biz_999', 'name' => 'My Agency']],
            ], 200),
            // Business ad accounts — one duplicate (act_111), one new (act_222)
            $this->graphUrl('/biz_999/adaccounts*') => Http::response([
                'data' => [
                    ['id' => 'act_111', 'name' => 'Personal', 'currency' => 'USD'],
                    ['id' => 'act_222', 'name' => 'Client Account', 'currency' => 'EUR'],
                ],
            ], 200),
        ]);

        $accounts = $this->makeClient()->fetchAllAdAccounts();

        // act_111 should appear only once despite being in both responses
        $this->assertCount(2, $accounts);
        $ids = array_column($accounts, 'id');
        $this->assertContains('act_111', $ids);
        $this->assertContains('act_222', $ids);
    }

    public function test_fetch_all_ad_accounts_continues_when_business_fetch_fails(): void
    {
        Http::fake([
            $this->graphUrl('/me/adaccounts*') => Http::response([
                'data' => [['id' => 'act_111', 'name' => 'Personal', 'currency' => 'USD']],
            ], 200),
            // /me/businesses returns an error — should be treated as non-fatal
            $this->graphUrl('/me/businesses*') => Http::response([
                'error' => ['code' => 200, 'message' => 'No businesses'],
            ], 200),
        ]);

        $accounts = $this->makeClient()->fetchAllAdAccounts();

        // Personal account still returned
        $this->assertCount(1, $accounts);
        $this->assertSame('act_111', $accounts[0]['id']);
    }
}
