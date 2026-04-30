<?php

declare(strict_types=1);

namespace App\Services\Forecasting;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Simplified Holt-Winters-style revenue forecast.
 *
 * Algorithm
 * ---------
 * 1. Pull last 365 days of `daily_snapshots.revenue` (SUM across stores).
 * 2. Smooth a trend via an exponentially-weighted rolling average (α = 0.3).
 * 3. Compute day-of-week seasonality factors from the full history window.
 * 4. Project forward: trend_last_day × dow_factor.
 * 5. Confidence band: ±1.96 × std-dev of 28-day in-sample residuals.
 *
 * Constraints
 * -----------
 * - Pure PHP math — no ML libraries.
 * - Reads only `daily_snapshots`; never raw `orders`.
 * - Result is cached 1 hour per (workspaceId, today's date) key.
 * - Falls back to a flat mean when < 28 days of history are available.
 *
 * @see ForecastResult
 */
class RevenueForecastService
{
    /** Exponential smoothing factor for trend. Higher → faster adaptation. */
    private const ALPHA = 0.3;

    /** z-score for 95% confidence interval. */
    private const Z95 = 1.96;

    /** Minimum history required to use trend; below this we use flat mean. */
    private const MIN_HISTORY_DAYS = 14;

    /** Rolling window for residual std-dev calculation. */
    private const RESIDUAL_WINDOW = 28;

    /** Cache duration in seconds (1 hour). */
    private const CACHE_TTL = 3600;

    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Produce a day-by-day revenue forecast for the next $horizonDays days.
     *
     * @param int $workspaceId
     * @param int $horizonDays 30 or 90; clamped to [1, 365].
     * @return ForecastResult
     */
    public function forecast(int $workspaceId, int $horizonDays = 90): ForecastResult
    {
        $horizonDays = max(1, min(365, $horizonDays));
        $cacheKey    = "forecast:{$workspaceId}:" . now()->toDateString() . ":{$horizonDays}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($workspaceId, $horizonDays) {
            return $this->compute($workspaceId, $horizonDays);
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function compute(int $workspaceId, int $horizonDays): ForecastResult
    {
        $history = $this->fetchHistory($workspaceId);

        if (count($history) < self::MIN_HISTORY_DAYS) {
            return $this->flatMeanForecast($history, $horizonDays);
        }

        // 1. Extract ordered revenue array and their dates.
        $dates    = array_keys($history);   // 'Y-m-d' strings, ascending
        $revenues = array_values($history); // floats

        // 2. Exponential smoothing → smoothed series.
        $smoothed = $this->expSmooth($revenues, self::ALPHA);

        // 3. Day-of-week seasonality factors (0=Sun … 6=Sat).
        $dowFactors = $this->computeDowFactors($dates, $revenues, $smoothed);

        // 4. Trend level = last smoothed value.
        $trendLevel = end($smoothed);

        // 5. Residual std-dev on the last RESIDUAL_WINDOW days.
        $residualStd = $this->residualStd($revenues, $smoothed);

        // 6. Project forward.
        $points  = [];
        $total30 = 0.0;
        $total90 = 0.0;

        for ($i = 1; $i <= $horizonDays; $i++) {
            $futureDate = now()->addDays($i)->toDateString();
            $dow        = (int) now()->addDays($i)->format('w'); // 0=Sun

            $factor  = $dowFactors[$dow] ?? 1.0;
            $point   = max(0.0, $trendLevel * $factor);
            $margin  = self::Z95 * $residualStd;
            $lower   = max(0.0, $point - $margin);
            $upper   = $point + $margin;

            $points[] = [
                'date'  => $futureDate,
                'point' => round($point, 2),
                'lower' => round($lower, 2),
                'upper' => round($upper, 2),
            ];

            if ($i <= 30) $total30 += $point;
            if ($i <= 90) $total90 += $point;
        }

        return new ForecastResult(
            points:      $points,
            total30d:    $total30,
            total90d:    $total90,
            historyDays: count($history),
        );
    }

    /**
     * Pull the last 365 days of daily snapshot revenue, summed across stores.
     * Returns an associative array keyed by date string 'Y-m-d', ascending.
     *
     * @return array<string, float>
     */
    private function fetchHistory(int $workspaceId): array
    {
        $rows = DB::select("
            SELECT
                date::text            AS day,
                SUM(revenue)::float   AS total_revenue
            FROM daily_snapshots
            WHERE workspace_id = ?
              AND date >= CURRENT_DATE - INTERVAL '365 days'
              AND date < CURRENT_DATE
            GROUP BY date
            ORDER BY date ASC
        ", [$workspaceId]);

        $history = [];
        foreach ($rows as $row) {
            $history[$row->day] = (float) $row->total_revenue;
        }

        return $history;
    }

    /**
     * Exponential smoothing (Brown's simple ETS).
     * s_0 = x_0; s_t = α * x_t + (1−α) * s_{t−1}
     *
     * @param  float[] $values
     * @return float[]
     */
    private function expSmooth(array $values, float $alpha): array
    {
        $smoothed = [];
        foreach ($values as $i => $v) {
            $smoothed[$i] = $i === 0
                ? $v
                : $alpha * $v + (1 - $alpha) * $smoothed[$i - 1];
        }

        return $smoothed;
    }

    /**
     * Compute day-of-week multiplicative seasonality factors.
     *
     * For each DOW bucket, factor = mean(actual / smoothed) over all
     * matching days.  Normalised so the 7-day average factor = 1.
     *
     * @param  string[] $dates     'Y-m-d' strings, same length as $revenues.
     * @param  float[]  $revenues
     * @param  float[]  $smoothed
     * @return array<int, float>   keyed 0 (Sun) … 6 (Sat)
     */
    private function computeDowFactors(array $dates, array $revenues, array $smoothed): array
    {
        $buckets = array_fill(0, 7, []);

        foreach ($dates as $i => $date) {
            $dow  = (int) date('w', strtotime($date));
            $base = $smoothed[$i];
            if ($base > 0) {
                $buckets[$dow][] = $revenues[$i] / $base;
            }
        }

        $factors = [];
        for ($d = 0; $d < 7; $d++) {
            $factors[$d] = count($buckets[$d]) > 0
                ? array_sum($buckets[$d]) / count($buckets[$d])
                : 1.0;
        }

        // Normalise so the weekly sum of factors = 7 (average = 1).
        $mean = array_sum($factors) / 7;
        if ($mean > 0) {
            foreach ($factors as $d => $f) {
                $factors[$d] = $f / $mean;
            }
        }

        return $factors;
    }

    /**
     * Standard deviation of residuals (actual − smoothed) over the last
     * RESIDUAL_WINDOW days, used to size the confidence band.
     *
     * @param float[] $revenues
     * @param float[] $smoothed
     */
    private function residualStd(array $revenues, array $smoothed): float
    {
        $n = count($revenues);
        $window = min(self::RESIDUAL_WINDOW, $n);

        $residuals = [];
        for ($i = $n - $window; $i < $n; $i++) {
            $residuals[] = $revenues[$i] - $smoothed[$i];
        }

        if (count($residuals) < 2) {
            return 0.0;
        }

        $mean    = array_sum($residuals) / count($residuals);
        $sumSqDiff = 0.0;
        foreach ($residuals as $r) {
            $sumSqDiff += ($r - $mean) ** 2;
        }

        return sqrt($sumSqDiff / (count($residuals) - 1));
    }

    /**
     * Fallback when there is not enough history: project forward using the
     * historical mean with zero confidence (band collapses to the point).
     *
     * @param  array<string, float> $history
     */
    private function flatMeanForecast(array $history, int $horizonDays): ForecastResult
    {
        $mean = count($history) > 0
            ? array_sum($history) / count($history)
            : 0.0;

        $points  = [];
        $total30 = 0.0;
        $total90 = 0.0;

        for ($i = 1; $i <= $horizonDays; $i++) {
            $futureDate = now()->addDays($i)->toDateString();
            $points[] = [
                'date'  => $futureDate,
                'point' => round($mean, 2),
                'lower' => round($mean, 2),
                'upper' => round($mean, 2),
            ];
            if ($i <= 30) $total30 += $mean;
            if ($i <= 90) $total90 += $mean;
        }

        return new ForecastResult(
            points:      $points,
            total30d:    $total30,
            total90d:    $total90,
            historyDays: count($history),
        );
    }
}
