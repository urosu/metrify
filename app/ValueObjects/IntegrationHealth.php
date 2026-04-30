<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Elevar-style integration health card payload.
 *
 * Synthesized from integration_events over a rolling window (typically 7 days).
 * Surfaces per-destination accuracy metrics to the /integrations Tracking Health tab.
 *
 * @see docs/planning/backend.md §12 (IntegrationHealthService)
 *
 * Reads: /integrations Tracking Health tab, DashboardController (Trust-health widget)
 * Writes: IntegrationHealthService
 */
final class IntegrationHealth
{
    /**
     * @param string       $destination    Integration destination (e.g., 'facebook', 'google', 'gsc')
     * @param float|null   $accuracyPct    Accuracy % (0-100); null when no events in window
     * @param float|null   $deliveryRate   Delivery rate % (0-100); null when no events in window
     * @param float|null   $matchQualityP50 Median match quality (0-10 Elevar scale); null when unavailable
     * @param float|null   $matchQualityP99 99th-pct match quality; null when unavailable
     * @param array<mixed> $topErrors      Top error code rows from errorCodeBreakdown()
     * @param int          $windowDays     Rolling window used for these metrics
     */
    public function __construct(
        public readonly string $destination,
        public readonly ?float $accuracyPct,
        public readonly ?float $deliveryRate,
        public readonly ?float $matchQualityP50,
        public readonly ?float $matchQualityP99,
        public readonly array $topErrors,
        public readonly int $windowDays = 7,
    ) {}

    public function isHealthy(): bool
    {
        return ($this->accuracyPct ?? 0) >= 85 && ($this->deliveryRate ?? 0) >= 90;
    }

    public function isWarning(): bool
    {
        return ($this->accuracyPct ?? 0) >= 70 || ($this->deliveryRate ?? 0) >= 75;
    }

    public function isCritical(): bool
    {
        return !$this->isWarning();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'destination'        => $this->destination,
            'accuracy_pct'       => $this->accuracyPct,
            'delivery_rate'      => $this->deliveryRate,
            'match_quality_p50'  => $this->matchQualityP50,
            'match_quality_p99'  => $this->matchQualityP99,
            'top_errors'         => $this->topErrors,
            'window_days'        => $this->windowDays,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            destination: (string) ($data['destination'] ?? ''),
            accuracyPct: isset($data['accuracy_pct']) ? (float) $data['accuracy_pct'] : null,
            deliveryRate: isset($data['delivery_rate']) ? (float) $data['delivery_rate'] : null,
            matchQualityP50: isset($data['match_quality_p50']) ? (float) $data['match_quality_p50'] : null,
            matchQualityP99: isset($data['match_quality_p99']) ? (float) $data['match_quality_p99'] : null,
            topErrors: (array) ($data['top_errors'] ?? []),
            windowDays: (int) ($data['window_days'] ?? 7),
        );
    }
}
