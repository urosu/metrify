<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Attribution window and model configuration passed to RevenueAttributionService.
 *
 * @see PLANNING.md section 3 L3
 * @see backend.md §5
 *
 * Reads: TopBar AttributionModelSelector, WindowSelector
 * Writes: Page controllers before querying RevenueAttributionService
 */
final class AttributionWindow
{
    /**
     * @param int|string $days One of: 1, 7, 28, or 'ltv' (lifetime)
     * @param string $model One of: 'first_touch', 'last_touch', 'last_non_direct', 'linear', 'data_driven'
     */
    public function __construct(
        public readonly int|string $days,
        public readonly string $model,
    ) {}

    public static function default(): self
    {
        return new self(days: 28, model: 'last_non_direct');
    }

    public function isLifetime(): bool
    {
        return $this->days === 'ltv';
    }

    public function daysAsInt(): int
    {
        return $this->days === 'ltv' ? 365 : (int) $this->days;
    }

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        return [
            'days'  => $this->days,
            'model' => $this->model,
        ];
    }

    /** @param array<string, int|string> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            days: $data['days'] ?? 28,
            model: $data['model'] ?? 'last_non_direct',
        );
    }
}
