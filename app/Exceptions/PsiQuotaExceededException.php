<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when the PageSpeed Insights API quota is exhausted.
 *
 * RunLighthouseCheckJob catches this and skips gracefully (logs a warning,
 * does NOT consume a retry attempt, does NOT increment failure counters).
 * The check will be retried on the next scheduled run the following day.
 *
 * See: PLANNING.md "Performance Monitoring — PSI Rate Limit Planning"
 */
class PsiQuotaExceededException extends \RuntimeException {}
