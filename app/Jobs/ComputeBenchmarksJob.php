<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Computes peer-cohort benchmark snapshots for all verticals.
 *
 * Queue:   low
 * Timeout: 300 s
 * Tries:   3
 *
 * Runs daily (scheduled in routes/console.php). For each vertical × metric pair,
 * computes P25/P50/P75 using Postgres PERCENTILE_CONT against all workspaces in
 * that vertical that have >= 10 orders in the last 30 days.
 *
 * Privacy floor: rows with sample_size < 5 are never written (or deleted if they
 * exist). This is non-negotiable per the benchmark privacy guarantee.
 *
 * Per CLAUDE.md: "Never aggregate raw orders in page requests — use daily_snapshots."
 * This job runs on a background queue and is NOT on the request path, so it reads
 * from daily_snapshots (which is a 30-day roll-up) rather than raw orders.
 *
 * Ratios are never stored on workspace rows (CLAUDE.md rule). We compute them
 * here at aggregation time from daily_snapshot components.
 *
 * Metrics computed:
 *  - aov    : revenue / orders_count
 *  - cvr    : orders_count / sessions  (only when sessions IS NOT NULL)
 *  - mer    : revenue / ad_spend       (only when ad_spend > 0)
 *  - cpa    : ad_spend / orders_count  (only when ad_spend > 0 and orders_count > 0)
 *  - roas   : revenue_real_attributed / ad_spend (only when ad_spend > 0)
 */
class ComputeBenchmarksJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 300;
    public int $tries     = 3;
    public int $uniqueFor = 360;

    /** Privacy floor: never write a row with fewer than 5 workspaces. */
    private const PRIVACY_FLOOR = 5;

    /** Minimum order count in the last 30 days to be included in benchmark cohort. */
    private const MIN_ORDERS = 10;

    private const PERIOD = 'last_30d';

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        $now      = now();
        $fromDate = now()->subDays(30)->toDateString();
        $toDate   = now()->toDateString();

        // Fetch all distinct verticals (excluding null and 'other').
        $verticals = DB::table('workspaces')
            ->whereNotNull('vertical')
            ->where('vertical', '!=', 'other')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('vertical');

        foreach ($verticals as $vertical) {
            $this->computeForVertical($vertical, $fromDate, $toDate, $now);
        }

        Log::info('ComputeBenchmarksJob: completed', [
            'verticals' => $verticals->count(),
            'computed_at' => $now->toIso8601String(),
        ]);
    }

    private function computeForVertical(
        string $vertical,
        string $fromDate,
        string $toDate,
        \Illuminate\Support\Carbon $now,
    ): void {
        // Build aggregated per-workspace metrics for the last 30 days.
        // Reads from daily_snapshots per CLAUDE.md rule (never aggregate raw orders).
        // ad_spend comes from the ad_spend column added in migration 2026_04_29_000002.
        $workspaceAggregates = DB::select("
            SELECT
                ds.workspace_id,
                SUM(ds.orders_count)                         AS total_orders,
                SUM(ds.revenue)                              AS total_revenue,
                SUM(ds.ad_spend)                             AS total_ad_spend,
                SUM(ds.revenue_real_attributed)              AS total_revenue_attributed,
                SUM(ds.sessions)                             AS total_sessions,
                NULLIF(SUM(ds.revenue), 0)
                    / NULLIF(SUM(ds.orders_count), 0)        AS aov,
                NULLIF(SUM(ds.orders_count), 0)
                    / NULLIF(SUM(ds.sessions), 0)            AS cvr,
                NULLIF(SUM(ds.revenue), 0)
                    / NULLIF(SUM(ds.ad_spend), 0)            AS mer,
                NULLIF(SUM(ds.ad_spend), 0)
                    / NULLIF(SUM(ds.orders_count), 0)        AS cpa,
                NULLIF(SUM(ds.revenue_real_attributed), 0)
                    / NULLIF(SUM(ds.ad_spend), 0)            AS roas
            FROM daily_snapshots ds
            JOIN workspaces w ON w.id = ds.workspace_id
            WHERE w.vertical = ?
              AND w.deleted_at IS NULL
              AND ds.date BETWEEN ? AND ?
            GROUP BY ds.workspace_id
            HAVING SUM(ds.orders_count) >= ?
        ", [$vertical, $fromDate, $toDate, self::MIN_ORDERS]);

        $sampleSize = count($workspaceAggregates);

        if ($sampleSize < self::PRIVACY_FLOOR) {
            // Delete any existing row so stale data is not served after workspaces leave.
            DB::table('benchmark_snapshots')
                ->where('vertical', $vertical)
                ->where('period', self::PERIOD)
                ->delete();

            Log::info('ComputeBenchmarksJob: skipped vertical (below privacy floor)', [
                'vertical'    => $vertical,
                'sample_size' => $sampleSize,
                'floor'       => self::PRIVACY_FLOOR,
            ]);
            return;
        }

        // Compute percentiles per metric using PERCENTILE_CONT in Postgres.
        // We pass the per-workspace values as a VALUES list to avoid a temp table.
        $metrics = ['aov', 'cvr', 'mer', 'cpa', 'roas'];

        foreach ($metrics as $metric) {
            $values = array_filter(
                array_map(fn ($row) => (float) ($row->$metric ?? 0), $workspaceAggregates),
                fn ($v) => $v > 0,
            );

            // Re-count after filtering nulls/zeros — metric may not be available for all workspaces.
            $metricSampleSize = count($values);

            if ($metricSampleSize < self::PRIVACY_FLOOR) {
                continue;
            }

            // Use Postgres PERCENTILE_CONT via unnest on a VALUES clause.
            // Cast each placeholder to FLOAT8 (double precision) explicitly —
            // without the cast Postgres infers type 'unknown' from bound params
            // and PERCENTILE_CONT rejects the ambiguous overload.
            $placeholders = implode(',', array_fill(0, $metricSampleSize, '(?::float8)'));
            $bindings     = array_values($values);

            $result = DB::selectOne("
                SELECT
                    PERCENTILE_CONT(0.25) WITHIN GROUP (ORDER BY v) AS p25,
                    PERCENTILE_CONT(0.50) WITHIN GROUP (ORDER BY v) AS p50,
                    PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY v) AS p75
                FROM (VALUES {$placeholders}) AS t(v)
            ", $bindings);

            if ($result === null) {
                continue;
            }

            DB::table('benchmark_snapshots')->upsert(
                [[
                    'vertical'    => $vertical,
                    'metric'      => $metric,
                    'period'      => self::PERIOD,
                    'p25'         => round((float) $result->p25, 4),
                    'p50'         => round((float) $result->p50, 4),
                    'p75'         => round((float) $result->p75, 4),
                    'sample_size' => $metricSampleSize,
                    'computed_at' => $now->toDateTimeString(),
                    'created_at'  => $now->toDateTimeString(),
                    'updated_at'  => $now->toDateTimeString(),
                ]],
                ['vertical', 'metric', 'period'],
                ['p25', 'p50', 'p75', 'sample_size', 'computed_at', 'updated_at'],
            );
        }

        Log::info('ComputeBenchmarksJob: processed vertical', [
            'vertical'    => $vertical,
            'sample_size' => $sampleSize,
        ]);
    }
}
