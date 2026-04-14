<?php

declare(strict_types=1);

namespace App\Services\PerformanceMonitoring;

use App\Exceptions\PsiQuotaExceededException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * PageSpeed Insights API v5 wrapper.
 *
 * Calls Google's PageSpeed Insights API and parses the Lighthouse result
 * into the shape expected by RunLighthouseCheckJob and lighthouse_snapshots.
 *
 * Rate limit behaviour:
 *   - Without a key: ~25 req/day per IP (insufficient for production).
 *   - With a key (PSI_API_KEY): 25,000 req/day, 400 req/100s per project.
 *   - On quota exceeded (429 or quotaExceeded error): throws PsiQuotaExceededException.
 *     The job catches this and skips gracefully — no retry consumed.
 *
 * Strategy: 'mobile' (default) or 'desktop'.
 * The API runs a real Lighthouse analysis; responses can take 10–30 s.
 *
 * See: PLANNING.md "Performance Monitoring — PSI Rate Limit Planning"
 * Related: app/Jobs/RunLighthouseCheckJob.php
 */
class PsiClient
{
    private const API_URL     = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    private const API_VERSION = 'v5';

    public function __construct(
        private readonly string|null $apiKey,
        private readonly int $timeoutSeconds,
    ) {}

    /**
     * Run a PageSpeed Insights check for the given URL.
     *
     * @param  string $url      Full URL to analyse (must be publicly accessible).
     * @param  string $strategy 'mobile' or 'desktop'. Default: 'mobile'.
     * @return array{
     *     performance_score: int|null,
     *     seo_score: int|null,
     *     accessibility_score: int|null,
     *     best_practices_score: int|null,
     *     lcp_ms: int|null,
     *     fcp_ms: int|null,
     *     cls_score: float|null,
     *     inp_ms: int|null,
     *     ttfb_ms: int|null,
     *     tbt_ms: int|null,
     *     raw_response: array<string,mixed>,
     *     api_version: string,
     * }
     *
     * @throws PsiQuotaExceededException  When quota is exhausted for today.
     * @throws \RuntimeException          On HTTP error or unparseable response.
     */
    public function check(string $url, string $strategy = 'mobile'): array
    {
        // Why: PSI API requires `category` as repeated query params
        // (category=performance&category=seo&...). PHP's http_build_query encodes
        // arrays as category[0]=performance&category[1]=seo, which the API ignores,
        // silently returning only the default performance category. Build the URL manually.
        $scalarParams = array_filter([
            'url'      => $url,
            'strategy' => $strategy,
            'key'      => ($this->apiKey !== null && $this->apiKey !== '') ? $this->apiKey : null,
        ]);

        $query = http_build_query($scalarParams)
            . '&category=performance&category=seo&category=accessibility&category=best-practices';

        $response = Http::timeout($this->timeoutSeconds)
            ->get(self::API_URL . '?' . $query);

        if ($response->status() === 429) {
            // Plain 429 with no parseable reason — treat as burst rate limit (clears in minutes).
            $this->trackQuotaError(isHardQuota: false);
            throw new PsiQuotaExceededException("PSI quota exceeded for URL: {$url}");
        }

        if (! $response->successful()) {
            $body = $response->json();

            // Catch Google's quota error format: {"error": {"errors": [{"reason": "quotaExceeded", ...}]}}
            $reason = $body['error']['errors'][0]['reason'] ?? null;
            if ($reason === 'quotaExceeded') {
                // Daily hard limit — resets at midnight Pacific (≈ midnight UTC). Set throttled_until
                // to end of today UTC so the admin card shows when recovery is expected.
                $this->trackQuotaError(isHardQuota: true);
                throw new PsiQuotaExceededException("PSI daily quota exhausted for URL: {$url}");
            }
            if ($reason === 'rateLimitExceeded') {
                $this->trackQuotaError(isHardQuota: false);
                throw new PsiQuotaExceededException("PSI rate limit exceeded for URL: {$url}");
            }

            throw new \RuntimeException(
                "PSI API returned HTTP {$response->status()} for URL: {$url}. Body: " .
                mb_substr($response->body(), 0, 200)
            );
        }

        $body = $response->json();

        if (empty($body['lighthouseResult'])) {
            throw new \RuntimeException("PSI response missing lighthouseResult for URL: {$url}");
        }

        $result = $this->parse($body);

        // Track successful call for admin quota visibility (same pattern as GoogleAdsClient).
        Cache::put('psi_last_success_at', now()->toISOString(), 86400);
        $callKey = 'psi_calls_' . now()->toDateString();
        Cache::add($callKey, 0, 172800);
        Cache::increment($callKey);

        return $result;
    }

    /**
     * Record a quota/rate-limit error for admin quota card visibility.
     *
     * Hard quota (daily limit exhausted): throttled_until = midnight UTC tonight.
     * Soft rate limit (burst 400/100s):   throttled_until = now + 5 minutes.
     */
    private function trackQuotaError(bool $isHardQuota): void
    {
        $throttledUntil = $isHardQuota
            ? today()->addDay()->toISOString()          // midnight UTC = ≈ midnight Pacific
            : now()->addMinutes(5)->toISOString();
        $ttl = $isHardQuota ? 90000 : 360;             // 25 h vs 6 min

        Cache::put('psi_throttled_until', $throttledUntil, $ttl);
        Cache::put('psi_last_throttle_at', now()->toISOString(), 86400);
        $hitKey = 'psi_rate_limit_hits_' . now()->toDateString();
        Cache::add($hitKey, 0, 172800);
        Cache::increment($hitKey);
    }

    /**
     * Parse the raw PSI API response into a flat array suitable for lighthouse_snapshots.
     *
     * @param  array<string,mixed> $body
     * @return array<string,mixed>
     */
    private function parse(array $body): array
    {
        $lr         = $body['lighthouseResult'];
        $categories = $lr['categories'] ?? [];
        $audits     = $lr['audits']     ?? [];

        // Category scores are 0–1 floats; multiply × 100 and round to integer.
        $scoreInt = static function (mixed $cat): ?int {
            $v = $cat['score'] ?? null;
            return $v !== null ? (int) round((float) $v * 100) : null;
        };

        // Numeric audit values — milliseconds are returned as floats; round to int.
        $ms = static function (string $key) use ($audits): ?int {
            $v = $audits[$key]['numericValue'] ?? null;
            return $v !== null ? (int) round((float) $v) : null;
        };

        // CLS is dimensionless (0–1+ range); keep as 4-decimal float.
        $clsRaw = $audits['cumulative-layout-shift']['numericValue'] ?? null;
        $cls    = $clsRaw !== null ? round((float) $clsRaw, 4) : null;

        // INP: Lighthouse 13 removed the lab audit for interaction-to-next-paint.
        // INP is inherently a field metric (requires real user interactions, not a simulated crawl).
        // Fall back to the CrUX p75 value from loadingExperience when the lab audit is absent.
        // Both values are in milliseconds so the column is semantically consistent.
        $inpMs = $ms('interaction-to-next-paint')
            ?? (isset($body['loadingExperience']['metrics']['INTERACTION_TO_NEXT_PAINT']['percentile'])
                ? (int) $body['loadingExperience']['metrics']['INTERACTION_TO_NEXT_PAINT']['percentile']
                : null);

        return [
            'performance_score'    => $scoreInt($categories['performance']    ?? null),
            'seo_score'            => $scoreInt($categories['seo']            ?? null),
            'accessibility_score'  => $scoreInt($categories['accessibility']  ?? null),
            'best_practices_score' => $scoreInt($categories['best-practices'] ?? null),
            'lcp_ms'               => $ms('largest-contentful-paint'),
            'fcp_ms'               => $ms('first-contentful-paint'),
            'cls_score'            => $cls,
            'inp_ms'               => $inpMs,
            'ttfb_ms'              => $ms('server-response-time'),
            'tbt_ms'               => $ms('total-blocking-time'),
            'raw_response'         => $body,
            'api_version'          => self::API_VERSION,
        ];
    }
}
