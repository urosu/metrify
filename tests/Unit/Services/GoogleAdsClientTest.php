<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\GoogleAccountDisabledException;
use App\Exceptions\GoogleApiException;
use App\Exceptions\GoogleRateLimitException;
use App\Exceptions\GoogleTokenExpiredException;
use App\Services\Integrations\Google\GoogleAdsClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * HTTP-layer tests for GoogleAdsClient.
 *
 * Covers typed exception mapping (token expiry, rate limit, account disabled, API error),
 * searchStream response parsing, pagination across batches, and the pool response path.
 * All HTTP calls are faked via Http::fake() — no real Google Ads API calls made.
 * Token refresh code path is not tested here (requires encrypted DB fixtures).
 */
class GoogleAdsClientTest extends TestCase
{
    private const TOKEN       = 'test_access_token';
    private const CUSTOMER_ID = '1234567890';

    private function makeClient(): GoogleAdsClient
    {
        return GoogleAdsClient::withToken(self::TOKEN);
    }

    private function adsUrl(string $path): string
    {
        return 'https://googleads.googleapis.com/v23' . $path;
    }

    // -------------------------------------------------------------------------
    // Token expiry
    // -------------------------------------------------------------------------

    public function test_throws_token_expired_on_401(): void
    {
        Http::fake([
            $this->adsUrl('/customers:listAccessibleCustomers*') => Http::response(null, 401),
        ]);

        $this->expectException(GoogleTokenExpiredException::class);

        $this->makeClient()->listAccessibleCustomers();
    }

    public function test_throws_token_expired_on_unauthenticated_status(): void
    {
        Http::fake([
            $this->adsUrl('/customers:listAccessibleCustomers*') => Http::response([
                'error' => ['status' => 'UNAUTHENTICATED', 'message' => 'Request had invalid authentication credentials'],
            ], 401),
        ]);

        $this->expectException(GoogleTokenExpiredException::class);

        $this->makeClient()->listAccessibleCustomers();
    }

    // -------------------------------------------------------------------------
    // Rate limit
    // -------------------------------------------------------------------------

    public function test_throws_rate_limit_on_429(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response(null, 429, ['Retry-After' => '60']),
        ]);

        $this->expectException(GoogleRateLimitException::class);

        $this->makeClient()->fetchCampaigns(self::CUSTOMER_ID);
    }

    public function test_throws_rate_limit_on_resource_exhausted_status(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([
                'error' => ['status' => 'RESOURCE_EXHAUSTED', 'message' => 'Quota exceeded'],
            ], 429),
        ]);

        $this->expectException(GoogleRateLimitException::class);

        $this->makeClient()->fetchCampaigns(self::CUSTOMER_ID);
    }

    public function test_rate_limit_exception_carries_retry_after_from_header(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response(
                null,
                429,
                ['Retry-After' => '120'],
            ),
        ]);

        try {
            $this->makeClient()->fetchCampaigns(self::CUSTOMER_ID);
            $this->fail('Expected GoogleRateLimitException');
        } catch (GoogleRateLimitException $e) {
            $this->assertSame(120, $e->retryAfter);
        }
    }

    // -------------------------------------------------------------------------
    // Account disabled
    // -------------------------------------------------------------------------

    public function test_throws_account_disabled_on_customer_not_enabled(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([
                'error' => [
                    'status'  => 'PERMISSION_DENIED',
                    'message' => 'The caller does not have permission',
                    'details' => [
                        [
                            'errors' => [
                                ['errorCode' => ['authorizationError' => 'CUSTOMER_NOT_ENABLED']],
                            ],
                        ],
                    ],
                ],
            ], 403),
        ]);

        $this->expectException(GoogleAccountDisabledException::class);

        $this->makeClient()->fetchCampaigns(self::CUSTOMER_ID);
    }

    public function test_throws_account_disabled_on_developer_token_not_approved(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([
                'error' => [
                    'status'  => 'PERMISSION_DENIED',
                    'message' => 'Developer token is not approved',
                    'details' => [
                        [
                            'errors' => [
                                ['errorCode' => ['authorizationError' => 'DEVELOPER_TOKEN_NOT_APPROVED']],
                            ],
                        ],
                    ],
                ],
            ], 403),
        ]);

        $this->expectException(GoogleAccountDisabledException::class);

        $this->makeClient()->fetchCampaigns(self::CUSTOMER_ID);
    }

    // -------------------------------------------------------------------------
    // Generic API errors
    // -------------------------------------------------------------------------

    public function test_throws_api_exception_on_500(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response(null, 500),
        ]);

        $this->expectException(GoogleApiException::class);

        $this->makeClient()->fetchCampaigns(self::CUSTOMER_ID);
    }

    public function test_throws_api_exception_on_other_403(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([
                'error' => ['status' => 'PERMISSION_DENIED', 'message' => 'Access denied'],
            ], 403),
        ]);

        $this->expectException(GoogleApiException::class);

        $this->makeClient()->fetchCampaigns(self::CUSTOMER_ID);
    }

    // -------------------------------------------------------------------------
    // listAccessibleCustomers
    // -------------------------------------------------------------------------

    public function test_list_accessible_customers_returns_stripped_ids(): void
    {
        Http::fake([
            $this->adsUrl('/customers:listAccessibleCustomers*') => Http::response([
                'resourceNames' => ['customers/111', 'customers/222', 'customers/333'],
            ], 200),
        ]);

        $ids = $this->makeClient()->listAccessibleCustomers();

        $this->assertSame(['111', '222', '333'], $ids);
    }

    public function test_list_accessible_customers_returns_empty_when_no_resource_names(): void
    {
        Http::fake([
            $this->adsUrl('/customers:listAccessibleCustomers*') => Http::response([], 200),
        ]);

        $ids = $this->makeClient()->listAccessibleCustomers();

        $this->assertSame([], $ids);
    }

    // -------------------------------------------------------------------------
    // searchStream — response parsing
    // -------------------------------------------------------------------------

    public function test_search_stream_flattens_batched_results(): void
    {
        // searchStream returns a JSON array of batch objects, each with a 'results' key
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([
                ['results' => [['campaign' => ['id' => 'camp_1', 'name' => 'A']]]],
                ['results' => [['campaign' => ['id' => 'camp_2', 'name' => 'B']]]],
            ], 200),
        ]);

        $rows = $this->makeClient()->searchStream(self::CUSTOMER_ID, 'SELECT campaign.id FROM campaign');

        $this->assertCount(2, $rows);
        $this->assertSame('camp_1', $rows[0]['campaign']['id']);
        $this->assertSame('camp_2', $rows[1]['campaign']['id']);
    }

    public function test_search_stream_returns_empty_array_for_no_results(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([], 200),
        ]);

        $rows = $this->makeClient()->searchStream(self::CUSTOMER_ID, 'SELECT campaign.id FROM campaign');

        $this->assertSame([], $rows);
    }

    public function test_search_stream_handles_batch_without_results_key(): void
    {
        // Some responses include non-results batches (e.g. fieldMask only)
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([
                ['fieldMask' => 'campaign.id,campaign.name'],
                ['results'   => [['campaign' => ['id' => 'camp_1', 'name' => 'X']]]],
            ], 200),
        ]);

        $rows = $this->makeClient()->searchStream(self::CUSTOMER_ID, 'SELECT campaign.id FROM campaign');

        $this->assertCount(1, $rows);
    }

    // -------------------------------------------------------------------------
    // fetchCampaigns
    // -------------------------------------------------------------------------

    public function test_fetch_campaigns_returns_rows(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([
                ['results' => [
                    ['campaign' => ['id' => 'c1', 'name' => 'Black Friday', 'status' => 'ENABLED']],
                    ['campaign' => ['id' => 'c2', 'name' => 'Retargeting',  'status' => 'PAUSED']],
                ]],
            ], 200),
        ]);

        $campaigns = $this->makeClient()->fetchCampaigns(self::CUSTOMER_ID);

        $this->assertCount(2, $campaigns);
        $this->assertSame('c1', $campaigns[0]['campaign']['id']);
    }

    public function test_fetch_campaigns_throws_rate_limit(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response(null, 429),
        ]);

        $this->expectException(GoogleRateLimitException::class);

        $this->makeClient()->fetchCampaigns(self::CUSTOMER_ID);
    }

    // -------------------------------------------------------------------------
    // fetchCampaignInsights
    // -------------------------------------------------------------------------

    public function test_fetch_campaign_insights_returns_rows(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([
                ['results' => [
                    ['campaign' => ['id' => 'c1'], 'metrics' => ['costMicros' => '500000', 'impressions' => '2000'], 'segments' => ['date' => '2026-01-01']],
                ]],
            ], 200),
        ]);

        $rows = $this->makeClient()->fetchCampaignInsights(self::CUSTOMER_ID, '2026-01-01', '2026-01-07');

        $this->assertCount(1, $rows);
        $this->assertSame('c1', $rows[0]['campaign']['id']);
    }

    public function test_fetch_campaign_insights_throws_token_expired_on_401(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response(null, 401),
        ]);

        $this->expectException(GoogleTokenExpiredException::class);

        $this->makeClient()->fetchCampaignInsights(self::CUSTOMER_ID, '2026-01-01', '2026-01-07');
    }

    // -------------------------------------------------------------------------
    // getCustomerInfo
    // -------------------------------------------------------------------------

    public function test_get_customer_info_returns_name_currency_and_manager_flag(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([
                ['results' => [
                    ['customer' => ['descriptiveName' => 'Acme Ltd', 'currencyCode' => 'EUR', 'manager' => false]],
                ]],
            ], 200),
        ]);

        $info = $this->makeClient()->getCustomerInfo(self::CUSTOMER_ID);

        $this->assertSame('Acme Ltd', $info['name']);
        $this->assertSame('EUR', $info['currency']);
        $this->assertFalse($info['is_manager']);
    }

    public function test_get_customer_info_identifies_mcc_account(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([
                ['results' => [
                    ['customer' => ['descriptiveName' => 'Agency MCC', 'currencyCode' => 'USD', 'manager' => true]],
                ]],
            ], 200),
        ]);

        $info = $this->makeClient()->getCustomerInfo(self::CUSTOMER_ID);

        $this->assertTrue($info['is_manager']);
    }

    public function test_get_customer_info_returns_defaults_on_empty_response(): void
    {
        Http::fake([
            $this->adsUrl('/customers/' . self::CUSTOMER_ID . '/googleAds:searchStream*') => Http::response([], 200),
        ]);

        $info = $this->makeClient()->getCustomerInfo(self::CUSTOMER_ID);

        $this->assertSame(self::CUSTOMER_ID, $info['name']);
        $this->assertSame('USD', $info['currency']);
        $this->assertFalse($info['is_manager']);
    }
}
