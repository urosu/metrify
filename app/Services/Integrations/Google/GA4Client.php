<?php

declare(strict_types=1);

namespace App\Services\Integrations\Google;

use App\Exceptions\GA4PropertyNotFoundException;
use App\Exceptions\GA4QuotaExceededException;
use App\Exceptions\GoogleTokenExpiredException;
use App\Models\Ga4Property;
use App\Models\IntegrationCredential;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for the Google Analytics Data API v1beta.
 *
 * Shares the Google OAuth access token with GoogleAdsClient (`analytics.readonly` scope).
 * Credentials are stored in `integration_credentials` polymorphic, keyed on `ga4_properties`.
 *
 * Rate limits:
 *   - 429 / RESOURCE_EXHAUSTED → `GA4QuotaExceededException`; caller backs off 60 s.
 *   - Quota 50k tokens/day per property.
 *
 * Never called from the request cycle — only from sync jobs.
 *
 * Reads:  Google Analytics Data API v1beta (runReport)
 * Writes: —
 * Called by: SyncGA4SessionsJob, SyncGA4AttributionJob, SyncGA4OrderAttributionJob, SyncGA4ProductViewsJob
 *
 * @see docs/planning/backend.md §4 (connector spec)
 */
class GA4Client
{
    private const API_BASE  = 'https://analyticsdata.googleapis.com/v1beta';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const TRIES     = 3;

    public function __construct(
        private readonly Ga4Property $property,
        private string $accessToken,
        private readonly ?IntegrationCredential $cred = null,
    ) {}

    /**
     * Build a client for a persisted Ga4Property, refreshing the token if needed.
     *
     * @throws GoogleTokenExpiredException  if the refresh token is missing or revoked.
     * @throws \RuntimeException            if no credentials row exists.
     */
    public static function forProperty(Ga4Property $property): self
    {
        $cred  = IntegrationCredential::mustForIntegrationable(Ga4Property::class, $property->id);
        $token = (string) $cred->decrypt('access_token_encrypted');

        if ($token === '') {
            throw new \RuntimeException("No access token found for GA4 property {$property->id}");
        }

        $instance = new self($property, $token, $cred);
        $instance->refreshIfNeeded();

        return $instance;
    }

    /**
     * Refresh the access token when within 5 minutes of expiry, or when expiry is unknown.
     *
     * Unlike SearchConsoleClient, a null token_expires_at is treated as expired rather than
     * valid — GA4 properties connected before the expiry column was populated need a forced
     * refresh on the next sync.
     *
     * @throws GoogleTokenExpiredException
     */
    public function refreshIfNeeded(): void
    {
        if ($this->cred === null) {
            return;
        }

        $expiresAt = $this->cred->token_expires_at;

        // null means expiry was never persisted (legacy connection) — always refresh.
        // Known future expiry (>5 min) → token still valid, skip.
        if ($expiresAt !== null && $expiresAt->subMinutes(5)->isFuture()) {
            return;
        }

        $this->performRefresh();
    }

    private function performRefresh(): void
    {
        if ($this->cred === null) {
            throw new GoogleTokenExpiredException();
        }

        $refreshToken = $this->cred->decrypt('refresh_token_encrypted');

        if ($refreshToken === null) {
            throw new GoogleTokenExpiredException();
        }

        $response = Http::timeout(15)->post(self::TOKEN_URL, [
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::error('GA4Client: token refresh failed', [
                'property_id' => $this->property->id,
                'http_status' => $response->status(),
                'error'       => $response->json('error', 'unknown'),
            ]);

            throw new GoogleTokenExpiredException();
        }

        $body      = $response->json();
        $newToken  = (string) ($body['access_token'] ?? '');
        $expiresIn = (int) ($body['expires_in'] ?? 3600);

        if ($newToken === '') {
            throw new GoogleTokenExpiredException();
        }

        $this->cred->updateQuietly([
            'access_token_encrypted' => Crypt::encryptString($newToken),
            'token_expires_at'       => now()->addSeconds($expiresIn),
        ]);

        $this->accessToken = $newToken;
    }

    /**
     * Fetch daily sessions by date (+ optional country / device dimensions).
     *
     * Calls `properties/{id}:runReport` with date, sessions, users dimensions.
     *
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return Collection<int, object>  Each row: date, sessions, users, country_code, device_category, data_state
     *
     * @throws GA4QuotaExceededException
     * @throws GA4PropertyNotFoundException
     */
    public function fetchDailySessions(Carbon $from, Carbon $to): Collection
    {
        $payload = [
            'dateRanges' => [
                ['startDate' => $from->toDateString(), 'endDate' => $to->toDateString()],
            ],
            'dimensions' => [
                ['name' => 'date'],
                ['name' => 'country'],
                ['name' => 'deviceCategory'],
            ],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
            ],
            'returnPropertyQuota' => true,
        ];

        // property_id stores the full resource name e.g. "properties/123456789".
        // API_BASE already ends at v1beta, so just prepend slash.
        $response = $this->post(
            "/{$this->property->property_id}:runReport",
            $payload,
        );

        return $this->parseSessionRows($response, $from, $to);
    }

    /**
     * Fetch daily attribution breakdowns via a single batchRunReports call with two sub-requests.
     *
     * GA4 runReport limits "nested requests" (large date ranges) to 9 dimensions. Combining
     * session-side and first-user-side dimensions would need 11+, so we split into two
     * independent sub-requests sent in one HTTP round-trip:
     *
     *   Sub A — session-side (8 dims):
     *     date, sessionSource, sessionMedium, sessionCampaignName,
     *     sessionDefaultChannelGroup, landingPage, deviceCategory, country
     *
     *   Sub B — first-user-side (4 dims):
     *     date, firstUserSource, firstUserMedium, firstUserCampaignName
     *
     * Both use 5 metrics: sessions, activeUsers, engagedSessions, conversions, totalRevenue.
     * GA4 caps 100,000 rows per sub-report — each sub-report paginates independently.
     *
     * Rows from A and B are returned as a flat array; each has the full field set with the
     * unused dimension fields set to null. The caller's row_signature distinguishes the two
     * types so they are stored as separate rows in ga4_daily_attribution.
     *
     * @param  string  $start  Date string YYYY-MM-DD
     * @param  string  $end    Date string YYYY-MM-DD
     * @return array<int, object>  Each row: date, session_source, session_medium, session_campaign,
     *                             session_default_channel_group, first_user_source, first_user_medium,
     *                             first_user_campaign, landing_page, device_category, country_code,
     *                             sessions, active_users, engaged_sessions, conversions, total_revenue, data_state
     *
     * @throws GA4QuotaExceededException
     * @throws GA4PropertyNotFoundException
     */
    public function fetchDailyAttribution(string $start, string $end): array
    {
        $metrics = [
            ['name' => 'sessions'],
            ['name' => 'activeUsers'],
            ['name' => 'engagedSessions'],
            ['name' => 'conversions'],
            ['name' => 'totalRevenue'],
        ];

        $dateRange = [['startDate' => $start, 'endDate' => $end]];
        $cutoff    = now()->subDays(3)->toDateString();
        $limit     = 100_000;
        $result    = [];

        // Sub A: session-side (8 dims — well under the 9-dim nested-request ceiling).
        $sessionDims = [
            ['name' => 'date'],
            ['name' => 'sessionSource'],
            ['name' => 'sessionMedium'],
            ['name' => 'sessionCampaignName'],
            ['name' => 'sessionDefaultChannelGroup'],
            ['name' => 'landingPage'],
            ['name' => 'deviceCategory'],
            ['name' => 'countryId'],  // ISO 3166-1 alpha-2; 'country' returns full names
        ];

        // Sub B: first-user-side (4 dims).
        $firstUserDims = [
            ['name' => 'date'],
            ['name' => 'firstUserSource'],
            ['name' => 'firstUserMedium'],
            ['name' => 'firstUserCampaignName'],
        ];

        // Paginate both sub-reports together — offset advances until neither has more rows.
        $offsetA    = 0;
        $offsetB    = 0;
        $moreA      = true;
        $moreB      = true;

        while ($moreA || $moreB) {
            $requests = [];

            if ($moreA) {
                $requests[] = [
                    'dateRanges'          => $dateRange,
                    'dimensions'          => $sessionDims,
                    'metrics'             => $metrics,
                    'limit'               => $limit,
                    'offset'              => $offsetA,
                    'returnPropertyQuota' => true,
                ];
            }

            if ($moreB) {
                $requests[] = [
                    'dateRanges'          => $dateRange,
                    'dimensions'          => $firstUserDims,
                    'metrics'             => $metrics,
                    'limit'               => $limit,
                    'offset'              => $offsetB,
                    'returnPropertyQuota' => true,
                ];
            }

            $response = $this->post(
                "/{$this->property->property_id}:batchRunReports",
                ['requests' => $requests],
            );

            $reports = $response['reports'] ?? [];
            $rIdx    = 0;

            // Parse session-side report (Sub A).
            if ($moreA) {
                $reportA   = $reports[$rIdx++] ?? [];
                $rowsA     = $reportA['rows'] ?? [];
                $totalA    = (int) ($reportA['rowCount'] ?? 0);

                foreach ($rowsA as $row) {
                    $dims = $row['dimensionValues'] ?? [];
                    $mets = $row['metricValues']   ?? [];
                    $date = $dims[0]['value']       ?? null;
                    if ($date === null) {
                        continue;
                    }
                    $dateFormatted = strlen($date) === 8
                        ? substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)
                        : $date;

                    $result[] = (object) [
                        'date'                          => $dateFormatted,
                        'session_source'                => $this->nullIfNotSet($dims[1]['value'] ?? null),
                        'session_medium'                => $this->nullIfNotSet($dims[2]['value'] ?? null),
                        'session_campaign'              => $this->nullIfNotSet($dims[3]['value'] ?? null),
                        'session_default_channel_group' => $this->nullIfNotSet($dims[4]['value'] ?? null),
                        'landing_page'                  => $this->nullIfNotSet($dims[5]['value'] ?? null),
                        'device_category'               => $this->nullIfNotSet($dims[6]['value'] ?? null),
                        'country_code'                  => $this->nullIfNotSet($dims[7]['value'] ?? null),
                        'first_user_source'             => null,
                        'first_user_medium'             => null,
                        'first_user_campaign'           => null,
                        'sessions'                      => (int) ($mets[0]['value'] ?? 0),
                        'active_users'                  => (int) ($mets[1]['value'] ?? 0),
                        'engaged_sessions'              => (int) ($mets[2]['value'] ?? 0),
                        'conversions'                   => (int) ($mets[3]['value'] ?? 0),
                        'total_revenue'                 => (float) ($mets[4]['value'] ?? 0),
                        'data_state'                    => $dateFormatted >= $cutoff ? 'provisional' : 'final',
                    ];
                }

                $offsetA += $limit;
                $moreA    = count($rowsA) === $limit && $offsetA < $totalA;
            }

            // Parse first-user-side report (Sub B).
            if ($moreB) {
                $reportB   = $reports[$rIdx] ?? [];
                $rowsB     = $reportB['rows'] ?? [];
                $totalB    = (int) ($reportB['rowCount'] ?? 0);

                foreach ($rowsB as $row) {
                    $dims = $row['dimensionValues'] ?? [];
                    $mets = $row['metricValues']   ?? [];
                    $date = $dims[0]['value']       ?? null;
                    if ($date === null) {
                        continue;
                    }
                    $dateFormatted = strlen($date) === 8
                        ? substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)
                        : $date;

                    $result[] = (object) [
                        'date'                          => $dateFormatted,
                        'first_user_source'             => $this->nullIfNotSet($dims[1]['value'] ?? null),
                        'first_user_medium'             => $this->nullIfNotSet($dims[2]['value'] ?? null),
                        'first_user_campaign'           => $this->nullIfNotSet($dims[3]['value'] ?? null),
                        'session_source'                => null,
                        'session_medium'                => null,
                        'session_campaign'              => null,
                        'session_default_channel_group' => null,
                        'landing_page'                  => null,
                        'device_category'               => null,
                        'country_code'                  => null,
                        'sessions'                      => (int) ($mets[0]['value'] ?? 0),
                        'active_users'                  => (int) ($mets[1]['value'] ?? 0),
                        'engaged_sessions'              => (int) ($mets[2]['value'] ?? 0),
                        'conversions'                   => (int) ($mets[3]['value'] ?? 0),
                        'total_revenue'                 => (float) ($mets[4]['value'] ?? 0),
                        'data_state'                    => $dateFormatted >= $cutoff ? 'provisional' : 'final',
                    ];
                }

                $offsetB += $limit;
                $moreB    = count($rowsB) === $limit && $offsetB < $totalB;
            }
        }

        return $result;
    }

    /**
     * Fetch per-item ecommerce event counts from GA4 enhanced ecommerce.
     *
     * Calls `properties/{id}:runReport` with 4 dimensions:
     *   date, itemName, itemId
     * And 3 metrics: itemViews, itemsAddedToCart, itemsPurchased.
     *
     * Requires the store to have GA4 enhanced ecommerce tracking set up with
     * view_item / add_to_cart / purchase events. item_id in GA4 should match
     * products.external_id (Shopify product ID or WooCommerce product ID).
     *
     * GA4 caps 100,000 rows per call — paginates automatically.
     *
     * @param  string  $start  Date string YYYY-MM-DD
     * @param  string  $end    Date string YYYY-MM-DD
     * @return array<int, object>  Each row: date, item_name, item_id,
     *                             item_views, items_added_to_cart, items_purchased, data_state
     *
     * @throws GA4QuotaExceededException
     * @throws GA4PropertyNotFoundException
     */
    public function fetchProductPageViews(string $start, string $end): array
    {
        $dimensions = [
            ['name' => 'date'],
            ['name' => 'itemName'],
            ['name' => 'itemId'],
        ];

        $metrics = [
            ['name' => 'itemsViewed'],
            ['name' => 'itemsAddedToCart'],
            ['name' => 'itemsPurchased'],
        ];

        $cutoff = now()->subDays(3)->toDateString();
        $result = [];
        $offset = 0;
        $limit  = 100_000;

        do {
            $payload = [
                'dateRanges'          => [['startDate' => $start, 'endDate' => $end]],
                'dimensions'          => $dimensions,
                'metrics'             => $metrics,
                'limit'               => $limit,
                'offset'              => $offset,
                'returnPropertyQuota' => true,
            ];

            $response = $this->post("/{$this->property->property_id}:runReport", $payload);
            $rows     = $response['rows'] ?? [];

            foreach ($rows as $row) {
                $dims = $row['dimensionValues'] ?? [];
                $mets = $row['metricValues']   ?? [];

                $date = $dims[0]['value'] ?? null;
                if ($date === null) {
                    continue;
                }

                // Normalise YYYYMMDD → YYYY-MM-DD.
                $dateFormatted = strlen($date) === 8
                    ? substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)
                    : $date;

                $itemName = $this->nullIfNotSet($dims[1]['value'] ?? null);
                $itemId   = $this->nullIfNotSet($dims[2]['value'] ?? null);

                // Skip rows with no item identity — they can't be joined to products.
                if ($itemName === null && $itemId === null) {
                    continue;
                }

                $result[] = (object) [
                    'date'                 => $dateFormatted,
                    'item_name'            => $itemName,
                    'item_id'              => $itemId,
                    'item_views'           => (int) ($mets[0]['value'] ?? 0),
                    'items_added_to_cart'  => (int) ($mets[1]['value'] ?? 0),
                    'items_purchased'      => (int) ($mets[2]['value'] ?? 0),
                    'data_state'           => $dateFormatted >= $cutoff ? 'provisional' : 'final',
                ];
            }

            $totalRows = (int) ($response['rowCount'] ?? 0);
            $offset   += $limit;
        } while (count($rows) === $limit && $offset < $totalRows);

        return $result;
    }

    /**
     * Fetch per-order (transaction-level) attribution from GA4.
     *
     * Calls `properties/{id}:runReport` with `transactionId` as a dimension
     * and a filter that excludes empty transaction IDs. Returns one row per
     * transaction visible in GA4 for the date window.
     *
     * Dimensions: date, transactionId, sessionSource, sessionMedium,
     *   sessionCampaignName, sessionDefaultChannelGroup,
     *   firstUserSource, firstUserMedium, firstUserCampaignName, landingPage
     *
     * Metric: purchaseRevenue
     *
     * @param  string  $start  Date string YYYY-MM-DD
     * @param  string  $end    Date string YYYY-MM-DD
     * @return array<int, object>  Each row: date, transaction_id, session_source, session_medium,
     *                             session_campaign, session_default_channel_group, first_user_source,
     *                             first_user_medium, first_user_campaign, landing_page, conversion_value
     *
     * @throws GA4QuotaExceededException
     * @throws GA4PropertyNotFoundException
     */
    public function fetchOrderAttribution(string $start, string $end): array
    {
        // GA4 runReport caps at 9 dimensions per request.
        // landingPage is omitted here; that column stores null.
        $dimensions = [
            ['name' => 'date'],
            ['name' => 'transactionId'],
            ['name' => 'sessionSource'],
            ['name' => 'sessionMedium'],
            ['name' => 'sessionCampaignName'],
            ['name' => 'sessionDefaultChannelGroup'],
            ['name' => 'firstUserSource'],
            ['name' => 'firstUserMedium'],
            ['name' => 'firstUserCampaignName'],
        ];

        // Filter: only include rows where transactionId is not empty.
        $dimensionFilter = [
            'filter' => [
                'fieldName' => 'transactionId',
                'stringFilter' => [
                    'matchType' => 'FULL_REGEXP',
                    'value'     => '.+',
                ],
            ],
        ];

        $result = [];
        $offset = 0;
        $limit  = 100_000;

        do {
            $payload = [
                'dateRanges'          => [['startDate' => $start, 'endDate' => $end]],
                'dimensions'          => $dimensions,
                'metrics'             => [['name' => 'purchaseRevenue']],
                'dimensionFilter'     => $dimensionFilter,
                'limit'               => $limit,
                'offset'              => $offset,
                'returnPropertyQuota' => true,
            ];

            $response = $this->post("/{$this->property->property_id}:runReport", $payload);
            $rows     = $response['rows'] ?? [];

            foreach ($rows as $row) {
                $dims = $row['dimensionValues'] ?? [];
                $mets = $row['metricValues']   ?? [];

                $date          = $dims[0]['value'] ?? null;
                $transactionId = $this->nullIfNotSet($dims[1]['value'] ?? null);

                if ($date === null || $transactionId === null) {
                    continue;
                }

                // Normalise YYYYMMDD → YYYY-MM-DD.
                $dateFormatted = strlen($date) === 8
                    ? substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)
                    : $date;

                $result[] = (object) [
                    'date'                          => $dateFormatted,
                    'transaction_id'                => $transactionId,
                    'session_source'                => $this->nullIfNotSet($dims[2]['value'] ?? null),
                    'session_medium'                => $this->nullIfNotSet($dims[3]['value'] ?? null),
                    'session_campaign'              => $this->nullIfNotSet($dims[4]['value'] ?? null),
                    'session_default_channel_group' => $this->nullIfNotSet($dims[5]['value'] ?? null),
                    'first_user_source'             => $this->nullIfNotSet($dims[6]['value'] ?? null),
                    'first_user_medium'             => $this->nullIfNotSet($dims[7]['value'] ?? null),
                    'first_user_campaign'           => $this->nullIfNotSet($dims[8]['value'] ?? null),
                    'landing_page'                  => null,
                    'conversion_value'              => (float) ($mets[0]['value'] ?? 0),
                ];
            }

            $totalRows = (int) ($response['rowCount'] ?? 0);
            $offset   += $limit;
        } while (count($rows) === $limit && $offset < $totalRows);

        return $result;
    }

    /**
     * Send a POST request with retry on transient errors.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws GA4QuotaExceededException
     * @throws GA4PropertyNotFoundException
     */
    private function post(string $path, array $payload): array
    {
        $attempt = 0;

        retry:
        $attempt++;

        $response = Http::withToken($this->accessToken)
            ->timeout(30)
            ->post(self::API_BASE . $path, $payload);

        if ($response->successful()) {
            return $response->json();
        }

        $status = $response->status();
        $body   = $response->json() ?? [];

        if ($status === 429 || $this->isQuotaError($body)) {
            throw new GA4QuotaExceededException(
                "GA4 quota exceeded for property {$this->property->property_id}"
            );
        }

        if ($status === 403 || $this->isPermissionError($body)) {
            throw new GA4PropertyNotFoundException(
                "GA4 property not found or no permission: {$this->property->property_id}"
            );
        }

        // 400 is a permanent client error (e.g. too many dimensions) — do not retry.
        if ($status === 400) {
            $message = $body['error']['message'] ?? 'unknown';
            Log::error('GA4Client: invalid request (400)', [
                'property_id' => $this->property->property_id,
                'message'     => $message,
            ]);
            throw new \RuntimeException("GA4 API error 400 for property {$this->property->property_id}: {$message}");
        }

        // Transient error — retry up to TRIES times with exponential backoff.
        if ($attempt < self::TRIES) {
            $delay = min(60, pow(2, $attempt) * 5);
            sleep($delay);
            goto retry;
        }

        Log::error('GA4Client: request failed', [
            'property_id' => $this->property->property_id,
            'status'      => $status,
            'body'        => $body,
        ]);

        throw new \RuntimeException("GA4 API error {$status} for property {$this->property->property_id}");
    }

    /** @param array<string, mixed> $body */
    private function isQuotaError(array $body): bool
    {
        $code = $body['error']['status'] ?? '';
        return $code === 'RESOURCE_EXHAUSTED';
    }

    /** @param array<string, mixed> $body */
    private function isPermissionError(array $body): bool
    {
        $code = $body['error']['status'] ?? '';
        return in_array($code, ['NOT_FOUND', 'PERMISSION_DENIED'], true);
    }

    /**
     * Parse runReport response rows into typed objects.
     *
     * GA4 data younger than 3 days may be revised — mark as 'provisional'.
     *
     * @param  array<string, mixed>  $response
     * @return Collection<int, object>
     */
    private function parseSessionRows(array $response, Carbon $from, Carbon $to): Collection
    {
        $rows     = $response['rows'] ?? [];
        $cutoff   = now()->subDays(3)->toDateString();
        $result   = [];

        foreach ($rows as $row) {
            $dims   = $row['dimensionValues'] ?? [];
            $mets   = $row['metricValues'] ?? [];

            $date         = $dims[0]['value'] ?? null;
            $country      = $dims[1]['value'] ?? null;
            $device       = $dims[2]['value'] ?? null;
            $sessions     = (int) ($mets[0]['value'] ?? 0);
            $users        = (int) ($mets[1]['value'] ?? 0);

            if ($date === null) {
                continue;
            }

            // Normalise GA4 date format YYYYMMDD → YYYY-MM-DD.
            $dateFormatted = strlen($date) === 8
                ? substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2)
                : $date;

            $result[] = (object) [
                'date'            => $dateFormatted,
                'sessions'        => $sessions,
                'users'           => $users,
                'country_code'    => ($country && $country !== '(not set)') ? strtoupper(substr($country, 0, 2)) : null,
                'device_category' => ($device && $device !== '(not set)') ? strtolower($device) : null,
                'data_state'      => $dateFormatted >= $cutoff ? 'provisional' : 'final',
            ];
        }

        return collect($result);
    }

    /**
     * Normalise a GA4 dimension value: treat "(not set)" and empty strings as null.
     */
    private function nullIfNotSet(?string $value): ?string
    {
        if ($value === null || $value === '' || $value === '(not set)' || $value === 'not set') {
            return null;
        }

        return $value;
    }

    /**
     * Parse a GA4 country name into a 2-letter country code.
     *
     * GA4 returns the full country name (e.g. "United States") not an ISO code.
     * We take the first 2 characters and uppercase — this is a best-effort
     * approximation used only for aggregation bucketing in ga4_daily_attribution.
     * Exact country codes are available via the sessions report; the attribution
     * breakdown is coarser by design (quota budget).
     */
    private function parseCountryCode(?string $country): ?string
    {
        $country = $this->nullIfNotSet($country);

        if ($country === null) {
            return null;
        }

        // GA4 country dimension is already ISO 3166-1 alpha-2 in some API versions
        // but full names in others. Keep it short and consistent.
        return strtoupper(substr($country, 0, 2));
    }
}
