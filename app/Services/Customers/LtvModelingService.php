<?php

declare(strict_types=1);

namespace App\Services\Customers;

/**
 * Predicted LTV computation.
 *
 * v1: naive heuristic — predicted_ltv = AOV × expected_future_orders.
 *   expected_future_orders = max(1, frequency) × 1.5 heuristic multiplier.
 *   predicted_next_order_at = last_order_at + avg_inter_order_gap.
 *
 * v2 (future): BG/NBD + Gamma-Gamma model; version tagged via
 * `customer_rfm_scores.model_version`.
 *
 * Reads:  orders (via caller — customer object is pre-populated)
 * Writes: customer_rfm_scores.predicted_ltv_* (via RfmScoringService)
 * Called by: RfmScoringService
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 */
class LtvModelingService
{
    public const VERSION = 'v1';

    // v1 heuristic: multiply frequency by this factor for expected future orders.
    // Sourced from typical e-commerce repeat-purchase distributions; revisit in v2.
    private const FUTURE_MULTIPLIER = 1.5;

    /**
     * Predict LTV for a customer.
     *
     * @param  object  $customer  Must have: total_revenue, order_count.
     * @return array{predicted_ltv: float|null, predicted_next_order_at: string|null, confidence: int}
     */
    public function predict(object $customer): array
    {
        $orderCount = max(1, (int) ($customer->order_count ?? 0));
        $totalRevenue = (float) ($customer->total_revenue ?? 0);

        if ($totalRevenue <= 0) {
            return [
                'predicted_ltv'          => null,
                'predicted_next_order_at' => null,
                'confidence'             => 0,
            ];
        }

        $aov = $totalRevenue / $orderCount;
        $expectedFutureOrders = $orderCount * self::FUTURE_MULTIPLIER;
        $predictedLtv = round($aov * $expectedFutureOrders, 2);

        // Confidence: low for single-order customers, higher with more data.
        $confidence = min(90, (int) (20 + ($orderCount * 10)));

        return [
            'predicted_ltv'          => $predictedLtv,
            'predicted_next_order_at' => null, // v2: use inter-order gap
            'confidence'             => $confidence,
        ];
    }

    /** @return string Current model version tag. */
    public function version(): string
    {
        return self::VERSION;
    }
}
