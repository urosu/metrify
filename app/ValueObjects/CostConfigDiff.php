<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Payload for UpdateCostConfigAction describing which cost tables changed.
 *
 * Enables selective snapshot rebuild — if only tax_rules changed, we recompute
 * only dates affected by that rule, not the entire snapshot history.
 *
 * @see PLANNING.md section 3 L3 UpdateCostConfigAction
 * @see backend.md §5, §2 Actions
 *
 * Writes: UpdateCostConfigAction
 */
final class CostConfigDiff
{
    /** @param array<string, mixed>|null $shipping Shipping rule diff */
    public function __construct(
        public readonly ?array $shipping = null,
        public readonly ?array $transactionFees = null,
        public readonly ?array $tax = null,
        public readonly ?array $opex = null,
        public readonly ?array $platformFees = null,
        /** @var string[]|null Date range affected (for selective rebuild) */
        public readonly ?array $affectsDates = null,
    ) {}

    public function hasTaxChanges(): bool
    {
        return $this->tax !== null;
    }

    public function hasShippingChanges(): bool
    {
        return $this->shipping !== null;
    }

    public function hasFeeChanges(): bool
    {
        return $this->transactionFees !== null || $this->platformFees !== null;
    }

    public function hasOpexChanges(): bool
    {
        return $this->opex !== null;
    }

    public function isEmpty(): bool
    {
        return $this->shipping === null
            && $this->transactionFees === null
            && $this->tax === null
            && $this->opex === null
            && $this->platformFees === null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'shipping'         => $this->shipping,
            'transaction_fees' => $this->transactionFees,
            'tax'              => $this->tax,
            'opex'             => $this->opex,
            'platform_fees'    => $this->platformFees,
            'affects_dates'    => $this->affectsDates,
        ];
    }
}
