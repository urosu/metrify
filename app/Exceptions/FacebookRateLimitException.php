<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the Facebook Graph API signals a rate limit.
 *
 * Covered error codes:
 *   4, 17, 613 — app-level burst / call volume limits
 *   80000, 80003, 80004, 80014 — ad-account throttling
 *
 * Also thrown proactively by FacebookAdsClient when X-Business-Use-Case-Usage
 * or X-FB-Ads-Insights-Throttle headers exceed the configured tier threshold.
 *
 * Jobs dispatch a fresh instance with delay instead of calling release() — this
 * resets the attempt counter so rate limits never exhaust tries.
 */
class FacebookRateLimitException extends RuntimeException
{
    public function __construct(
        public readonly int $retryAfter = 60,
        public readonly ?int $usagePct = null,
    ) {
        $usageStr = $usagePct !== null ? " (usage: {$usagePct}%)" : '';
        parent::__construct("Facebook rate limit hit. Retry after {$retryAfter} seconds.{$usageStr}");
    }
}
