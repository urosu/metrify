<?php

declare(strict_types=1);

namespace App\Services\Benchmarks;

use Illuminate\Support\Carbon;

/**
 * Value object returned by BenchmarkLookupService::forWorkspace().
 *
 * Carries the workspace's own metric value alongside the peer P25/P50/P75
 * from the benchmark_snapshots table. All fields are read-only.
 */
readonly class BenchmarkRow
{
    public function __construct(
        /** Metric key — e.g. 'roas', 'aov', 'cvr', 'cpa', 'mer' */
        public string  $metric,
        /** Period key — e.g. 'last_30d' */
        public string  $period,
        /** Industry vertical — e.g. 'apparel', 'beauty' */
        public string  $vertical,
        /** The workspace's own computed value. Null when no data available. */
        public ?float  $ownValue,
        /** Peer 25th percentile. */
        public ?float  $p25,
        /** Peer median (50th percentile). */
        public ?float  $p50,
        /** Peer 75th percentile. */
        public ?float  $p75,
        /** Number of workspaces in the peer cohort. Always >= 5 (privacy floor). */
        public int     $sampleSize,
        /** When the benchmark snapshot was last computed. */
        public Carbon  $computedAt,
    ) {}

    /**
     * Returns the user's percentile position relative to the peer cohort.
     * Approximated by comparing ownValue to p25/p50/p75 thresholds.
     *
     * Returns one of: 'top_25', 'top_50', 'bottom_50', 'bottom_25', or null.
     */
    public function percentileTier(): ?string
    {
        if ($this->ownValue === null || $this->p25 === null || $this->p50 === null || $this->p75 === null) {
            return null;
        }

        if ($this->ownValue >= $this->p75) {
            return 'top_25';
        }

        if ($this->ownValue >= $this->p50) {
            return 'top_50';
        }

        if ($this->ownValue >= $this->p25) {
            return 'bottom_50';
        }

        return 'bottom_25';
    }
}
