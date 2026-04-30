<?php

declare(strict_types=1);

namespace App\Services\Discrepancy;

/**
 * DTO returned by DiscrepancyAnalyzer::analyze().
 *
 * daily            — per-day breakdown: date, store, facebook, google, ga4, gsc, real,
 *                    declared_total, unattributed, discrepancy_pct.
 * summary          — period totals for the same fields.
 * factors          — detected contributing factors with id, title, description, severity, detected.
 * disagreement_rows — platform-vs-store deltas from daily_source_disagreements.
 */
final readonly class DiscrepancyResult
{
    /**
     * @param list<array<string, mixed>> $daily
     * @param array<string, mixed>       $summary
     * @param list<array<string, mixed>> $factors
     * @param list<array<string, mixed>> $disagreement_rows
     */
    public function __construct(
        public array $daily,
        public array $summary,
        public array $factors,
        public array $disagreement_rows,
    ) {}

    /**
     * Serialise to the shape expected by DiscrepancyController / Inertia props.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'daily'             => $this->daily,
            'summary'           => $this->summary,
            'factors'           => $this->factors,
            'disagreement_rows' => $this->disagreement_rows,
        ];
    }
}
