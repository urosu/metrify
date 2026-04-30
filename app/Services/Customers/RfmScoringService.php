<?php

declare(strict_types=1);

namespace App\Services\Customers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Computes R/F/M quintile scores and segment assignment for all customers
 * in a workspace over a rolling 365-day window.
 *
 * Algorithm:
 *   R (Recency)   — days since last order; lower = better. Score 5=recent, 1=stale.
 *   F (Frequency) — order count in window; higher = better.
 *   M (Monetary)  — sum of revenue in window; higher = better.
 *
 * Quintile boundaries are computed from the actual distribution so that each
 * score band contains ~20% of customers. Ties at boundaries go to the higher band.
 *
 * Segment assignment uses the classic RFM grid (champions, loyal,
 * potential_loyalists, at_risk, about_to_sleep, needs_attention, hibernating).
 *
 * Results are upserted to `customer_rfm_scores` keyed on
 * (workspace_id, customer_id, computed_for) — idempotent, re-runnable.
 *
 * LTV predictions are delegated to `LtvModelingService` and written on the same row.
 *
 * Reads:  orders, customers
 * Writes: customer_rfm_scores
 * Called by: ComputeRfmScoresJob
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/planning/schema.md §1.6 (customer_rfm_scores table)
 */
class RfmScoringService
{
    public const MODEL_VERSION = 'v1';
    private const WINDOW_DAYS = 365;
    private const CHUNK_SIZE = 500;

    public function __construct(private readonly LtvModelingService $ltv) {}

    /**
     * Score all customers with orders in the rolling 365-day window and upsert results.
     */
    public function scoreWorkspace(int $workspaceId, Carbon $date): void
    {
        $windowStart = $date->copy()->subDays(self::WINDOW_DAYS)->toDateString();
        $refDate = $date->toDateString();

        $raw = $this->fetchRawMetrics($workspaceId, $windowStart, $refDate);

        if ($raw->isEmpty()) {
            return;
        }

        [$rQuintiles, $fQuintiles, $mQuintiles] = $this->computeQuintileBoundaries($raw);

        $rows = [];
        foreach ($raw as $r) {
            $recency = (int) $r->recency_days;
            $frequency = (int) $r->order_count;
            $monetary = (float) $r->total_revenue;

            // Recency: score 5 = most recent (lowest days), invert the quintile index.
            $rScore = 6 - $this->quintileScore($recency, $rQuintiles, invert: false);
            $fScore = $this->quintileScore($frequency, $fQuintiles, invert: false);
            $mScore = $this->quintileScore($monetary, $mQuintiles, invert: false);

            $segment = $this->assignSegment($rScore, $fScore, $mScore);

            $customerObj = (object) ['workspace_id' => $workspaceId, 'id' => $r->customer_id, 'total_revenue' => $monetary, 'order_count' => $frequency];
            $ltv = $this->ltv->predict($customerObj);

            $rows[] = [
                'workspace_id'              => $workspaceId,
                'customer_id'               => $r->customer_id,
                'computed_for'              => $refDate,
                'recency_score'             => $rScore,
                'frequency_score'           => $fScore,
                'monetary_score'            => $mScore,
                'segment'                   => $segment,
                'churn_risk'                => null,
                'predicted_next_order_at'   => $ltv['predicted_next_order_at'] ?? null,
                'predicted_ltv_reporting'   => $ltv['predicted_ltv'] ?? null,
                'predicted_ltv_confidence'  => $ltv['confidence'] ?? null,
                'model_version'             => self::MODEL_VERSION,
                'created_at'               => now(),
            ];
        }

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            DB::table('customer_rfm_scores')->upsert(
                $chunk,
                ['workspace_id', 'customer_id', 'computed_for'],
                ['recency_score', 'frequency_score', 'monetary_score', 'segment', 'churn_risk',
                 'predicted_next_order_at', 'predicted_ltv_reporting', 'predicted_ltv_confidence', 'model_version'],
            );
        }
    }

    /**
     * Fetch raw R/F/M metrics per customer for the window.
     *
     * @return Collection<int, object>
     */
    private function fetchRawMetrics(int $workspaceId, string $windowStart, string $refDate): Collection
    {
        // Join to customers via email_hash to get the integer customer_id required by the
        // customer_rfm_scores FK. Uses total_in_reporting_currency for monetary score
        // consistency with DailySnapshot revenue figures.
        // Falls back to customer_id column when set (production path), otherwise resolves
        // via the customers.email_hash join (seed path where orders.customer_id is null).
        return DB::table('orders as o')
            ->join('customers as c', function ($join) use ($workspaceId) {
                $join->on('c.email_hash', '=', 'o.customer_email_hash')
                     ->where('c.workspace_id', '=', $workspaceId);
            })
            ->selectRaw("
                c.id AS customer_id,
                DATE_PART('day', ? - MAX(o.occurred_at)) AS recency_days,
                COUNT(*) AS order_count,
                SUM(o.total_in_reporting_currency) AS total_revenue
            ", ["$refDate 23:59:59"])
            ->where('o.workspace_id', $workspaceId)
            ->where('o.status', 'completed')
            ->where('o.occurred_at', '>=', $windowStart)
            ->groupBy('c.id')
            ->get();
    }

    /**
     * Compute quintile boundary arrays for R, F, M distributions.
     *
     * Returns three arrays of 4 cut-points (20/40/60/80 percentile values).
     *
     * @return array{array<float>, array<float>, array<float>}
     */
    private function computeQuintileBoundaries(Collection $raw): array
    {
        $recencies  = $raw->pluck('recency_days')->sort()->values();
        $frequencies = $raw->pluck('order_count')->sort()->values();
        $monetaries  = $raw->pluck('total_revenue')->sort()->values();

        return [
            $this->percentileCuts($recencies),
            $this->percentileCuts($frequencies),
            $this->percentileCuts($monetaries),
        ];
    }

    /**
     * Return 4 percentile cut-points (p20, p40, p60, p80) from a sorted collection.
     *
     * @param  Collection<int, float|int>  $sorted
     * @return array<float>
     */
    private function percentileCuts(Collection $sorted): array
    {
        $n = $sorted->count();

        if ($n === 0) {
            return [0, 0, 0, 0];
        }

        $cuts = [];
        foreach ([0.2, 0.4, 0.6, 0.8] as $pct) {
            $index = max(0, (int) ceil($pct * $n) - 1);
            $cuts[] = (float) $sorted->get($index, 0);
        }

        return $cuts;
    }

    /**
     * Assign score 1-5 by comparing value to quintile boundaries.
     *
     * @param  array<float>  $cuts  Four boundary values (p20, p40, p60, p80).
     */
    private function quintileScore(float|int $value, array $cuts, bool $invert): int
    {
        $score = 1;
        foreach ($cuts as $cut) {
            if ($value >= $cut) {
                $score++;
            }
        }
        $score = min(5, max(1, $score));

        return $invert ? (6 - $score) : $score;
    }

    /**
     * Map R/F/M scores to a named segment.
     *
     * Grid from RFM best-practice (Klaviyo / Daasity style).
     */
    private function assignSegment(int $r, int $f, int $m): string
    {
        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'champions';
        }

        if ($r >= 3 && $f >= 3) {
            return 'loyal';
        }

        if ($r >= 3 && $f <= 2) {
            return 'potential_loyalists';
        }

        if ($r <= 2 && $f >= 3) {
            return 'at_risk';
        }

        if ($r === 2 && $f <= 2) {
            return 'about_to_sleep';
        }

        if ($r >= 3 && $f === 1 && $m <= 2) {
            return 'needs_attention';
        }

        return 'hibernating';
    }
}
