<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Confidence signal classification for UI badge (ConfidenceChip, SignalTypeBadge).
 *
 * Evaluates sample size against configurable thresholds to determine if a metric
 * is computed from sufficient data (measured), insufficient data (insufficient),
 * a mix of measured and modeled data (mixed), or entirely modeled (modeled).
 *
 * @see docs/planning/backend.md §3 (ConfidenceThresholdService)
 * @see docs/UX.md §5.27 ConfidenceChip, §5.28 SignalTypeBadge
 *
 * Reads: ConfidenceThresholdService, frontend ConfidenceChip + SignalTypeBadge
 * Writes: DashboardController, CampaignsController, AcquisitionController, SeoController
 */
final class SignalType
{
    /**
     * @param string $type One of: 'measured', 'mixed', 'modeled', 'insufficient'
     * @param int $sampleSize The count of events (orders, sessions, impressions, etc.)
     * @param int $threshold The minimum sample size configured for this metric type
     * @param string|null $reason Optional explanation for UI tooltip (e.g., "Below 100 order threshold")
     */
    public function __construct(
        public readonly string $type,
        public readonly int $sampleSize,
        public readonly int $threshold,
        public readonly ?string $reason = null,
    ) {}

    public function isMeasured(): bool
    {
        return $this->type === 'measured';
    }

    public function isMixed(): bool
    {
        return $this->type === 'mixed';
    }

    public function isModeled(): bool
    {
        return $this->type === 'modeled';
    }

    public function isInsufficient(): bool
    {
        return $this->type === 'insufficient';
    }

    public function belowThreshold(): bool
    {
        return $this->sampleSize < $this->threshold;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type'       => $this->type,
            'sample_size' => $this->sampleSize,
            'threshold'  => $this->threshold,
            'reason'     => $this->reason,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? 'insufficient'),
            sampleSize: (int) ($data['sample_size'] ?? 0),
            threshold: (int) ($data['threshold'] ?? 0),
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        );
    }

    public static function measured(int $sampleSize, int $threshold): self
    {
        return new self(
            type: 'measured',
            sampleSize: $sampleSize,
            threshold: $threshold,
        );
    }

    public static function insufficient(int $sampleSize, int $threshold): self
    {
        return new self(
            type: 'insufficient',
            sampleSize: $sampleSize,
            threshold: $threshold,
            reason: "Below {$threshold} sample size threshold",
        );
    }
}
