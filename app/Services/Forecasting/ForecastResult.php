<?php

declare(strict_types=1);

namespace App\Services\Forecasting;

/**
 * Value object returned by RevenueForecastService::forecast().
 *
 * @property-read array<int, array{date: string, point: float, lower: float, upper: float}> $points
 * @property-read float $total30d      Point-estimate sum for days 1–30.
 * @property-read float $total90d      Point-estimate sum for days 1–90.
 * @property-read int   $historyDays   Number of historical days used in training.
 */
final class ForecastResult
{
    /**
     * @param array<int, array{date: string, point: float, lower: float, upper: float}> $points
     */
    public function __construct(
        public readonly array $points,
        public readonly float $total30d,
        public readonly float $total90d,
        public readonly int   $historyDays,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'points'      => $this->points,
            'total_30d'   => round($this->total30d, 2),
            'total_90d'   => round($this->total90d, 2),
            'history_days' => $this->historyDays,
        ];
    }
}
