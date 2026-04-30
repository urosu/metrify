<?php

declare(strict_types=1);

namespace App\Services\Benchmarks;

use App\Models\BenchmarkSnapshot;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Looks up peer-benchmark data for a workspace metric.
 *
 * Usage:
 *   $row = app(BenchmarkLookupService::class)
 *       ->forWorkspace($workspaceId, 'roas');
 *
 * Returns null when:
 *  - Workspace has no vertical or vertical is 'other'
 *  - No benchmark row exists for the vertical × metric × period combination
 *    (e.g. vertical too small — below privacy floor of 5 workspaces)
 *
 * The workspace's own value for the metric is derived from the last 30 days of
 * daily_snapshots — never from raw orders (per CLAUDE.md rule).
 *
 * Ratios are computed on the fly (never stored), per CLAUDE.md.
 */
class BenchmarkLookupService
{
    private const PERIOD = 'last_30d';

    /**
     * Fetch benchmark comparison for a workspace + metric.
     *
     * @param  int    $workspaceId
     * @param  string $metric  One of: 'roas', 'aov', 'cvr', 'cpa', 'mer'
     * @param  string $period  Default 'last_30d'
     * @return BenchmarkRow|null
     */
    public function forWorkspace(
        int    $workspaceId,
        string $metric,
        string $period = self::PERIOD,
    ): ?BenchmarkRow {
        // Load workspace vertical — required for benchmark lookup.
        $workspace = Workspace::withoutGlobalScopes()
            ->select(['id', 'vertical'])
            ->find($workspaceId);

        if ($workspace === null) {
            return null;
        }

        $vertical = $workspace->vertical;

        if ($vertical === null || $vertical === 'other' || $vertical === '') {
            return null;
        }

        // Load benchmark snapshot for this vertical × metric × period.
        $snapshot = BenchmarkSnapshot::where('vertical', $vertical)
            ->where('metric', $metric)
            ->where('period', $period)
            ->first();

        if ($snapshot === null) {
            return null;
        }

        // Compute the workspace's own value from daily_snapshots (last 30 days).
        $workspaceValue = $this->computeWorkspaceMetric($workspaceId, $metric);

        return new BenchmarkRow(
            metric:      $metric,
            period:      $period,
            vertical:    $vertical,
            ownValue:    $workspaceValue,
            p25:         $snapshot->p25 !== null ? (float) $snapshot->p25 : null,
            p50:         $snapshot->p50 !== null ? (float) $snapshot->p50 : null,
            p75:         $snapshot->p75 !== null ? (float) $snapshot->p75 : null,
            sampleSize:  $snapshot->sample_size,
            computedAt:  $snapshot->computed_at,
        );
    }

    /**
     * Compute the workspace's own metric value from daily_snapshots.
     *
     * All values are computed from 30-day aggregates of daily_snapshots,
     * not from raw orders. Ratios are computed on the fly (never stored).
     */
    private function computeWorkspaceMetric(int $workspaceId, string $metric): ?float
    {
        $fromDate = now()->subDays(30)->toDateString();
        $toDate   = now()->toDateString();

        $agg = DB::selectOne("
            SELECT
                NULLIF(SUM(orders_count), 0)       AS total_orders,
                NULLIF(SUM(revenue), 0)             AS total_revenue,
                NULLIF(SUM(ad_spend), 0)            AS total_ad_spend,
                NULLIF(SUM(revenue_real_attributed), 0) AS total_revenue_attributed,
                NULLIF(SUM(sessions), 0)            AS total_sessions
            FROM daily_snapshots
            WHERE workspace_id = ?
              AND date BETWEEN ? AND ?
        ", [$workspaceId, $fromDate, $toDate]);

        if ($agg === null) {
            return null;
        }

        return match ($metric) {
            'aov' => $agg->total_revenue !== null && $agg->total_orders !== null
                ? (float) $agg->total_revenue / (float) $agg->total_orders
                : null,
            'cvr' => $agg->total_orders !== null && $agg->total_sessions !== null
                ? (float) $agg->total_orders / (float) $agg->total_sessions
                : null,
            'mer' => $agg->total_revenue !== null && $agg->total_ad_spend !== null
                ? (float) $agg->total_revenue / (float) $agg->total_ad_spend
                : null,
            'cpa' => $agg->total_ad_spend !== null && $agg->total_orders !== null
                ? (float) $agg->total_ad_spend / (float) $agg->total_orders
                : null,
            'roas' => $agg->total_revenue_attributed !== null && $agg->total_ad_spend !== null
                ? (float) $agg->total_revenue_attributed / (float) $agg->total_ad_spend
                : null,
            default => null,
        };
    }
}
