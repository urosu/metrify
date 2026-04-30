<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Checks whether a ShouldBeUnique job is currently locked (in-flight) for a given workspace.
 *
 * Laravel's ShouldBeUnique implementation stores the lock under:
 *   "laravel_unique_job:{xxh128(FQCN)}:{uniqueId}"
 *
 * This service centralises that key-shape so controllers don't duplicate it.
 *
 * @see App\Jobs\RecomputeAttributionJob
 * @see App\Jobs\ReclassifyOrdersForMappingJob
 */
class JobLockChecker
{
    /**
     * Returns true if the given ShouldBeUnique job class is currently locked
     * for the supplied workspace ID.
     *
     * @param  class-string  $jobClass  Fully-qualified job class name.
     */
    public function isLocked(string $jobClass, int $workspaceId): bool
    {
        $key = 'laravel_unique_job:' . hash('xxh128', $jobClass) . ':' . $workspaceId;

        return Cache::has($key);
    }
}
