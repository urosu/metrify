<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Attribution\MarkovChainAttributionService;
use App\Services\WorkspaceContext;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Build daily_snapshot_attribution_models rows for a workspace + date.
 *
 * Runs one SQL query per attribution model, groups by raw channel_type (lowercase),
 * and upserts into daily_snapshot_attribution_models on the unique
 * (workspace_id, date, channel_id, model) index.
 *
 * Channel IDs are stored as lowercase raw JSONB values (e.g. "paid_social") so that
 * the read path can apply INITCAP / ucwords once at display time without needing a
 * normalisation pass in the writer.
 *
 * Models written:
 *   first_click, last_click, last_non_direct — simple JSONB GROUP BY
 *   linear, position_based, time_decay, clicks_only — CTE with LATERAL unnest
 *
 * Survey model (hdyhau_response from order_metafields) is written when the
 * order_metafields table exists.
 *
 * Queue:   low
 * Timeout: 600 s
 * Tries:   3
 *
 * @see app/Services/Attribution/AttributionDataService.php  modelComparison()
 * @see app/Models/DailySnapshotAttributionModel.php
 */
class BuildAttributionSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 3;

    public function __construct(
        public readonly int    $workspaceId,
        public readonly Carbon $date,
    ) {
        $this->onQueue('low');
    }

    public function handle(WorkspaceContext $context, MarkovChainAttributionService $markov): void
    {
        $context->set($this->workspaceId);

        $dateStr  = $this->date->toDateString();
        $dayStart = $dateStr . ' 00:00:00';
        $dayEnd   = $dateStr . ' 23:59:59';

        $rows = [];

        // ── first_click ───────────────────────────────────────────────────────────
        $firstClickRows = DB::select("
            SELECT
                LOWER(COALESCE(
                    attribution_first_touch->>'channel_type',
                    attribution_first_touch->>'source',
                    'not_tracked'
                ))                                                     AS channel_id,
                SUM(COALESCE(total_in_reporting_currency, total))       AS revenue,
                COUNT(*)                                                AS orders,
                COUNT(DISTINCT customer_id)                             AS customers
            FROM orders
            WHERE workspace_id = ?
              AND occurred_at BETWEEN ? AND ?
            GROUP BY 1
        ", [$this->workspaceId, $dayStart, $dayEnd]);

        foreach ($firstClickRows as $r) {
            $rows[] = $this->row($dateStr, (string) $r->channel_id, 'first_click', $r);
        }

        // ── last_click ────────────────────────────────────────────────────────────
        $lastClickRows = DB::select("
            SELECT
                LOWER(COALESCE(
                    attribution_last_touch->>'channel_type',
                    attribution_last_touch->>'source',
                    'not_tracked'
                ))                                                     AS channel_id,
                SUM(COALESCE(total_in_reporting_currency, total))       AS revenue,
                COUNT(*)                                                AS orders,
                COUNT(DISTINCT customer_id)                             AS customers
            FROM orders
            WHERE workspace_id = ?
              AND occurred_at BETWEEN ? AND ?
            GROUP BY 1
        ", [$this->workspaceId, $dayStart, $dayEnd]);

        foreach ($lastClickRows as $r) {
            $rows[] = $this->row($dateStr, (string) $r->channel_id, 'last_click', $r);
        }

        // ── last_non_direct ───────────────────────────────────────────────────────
        $lastNdRows = DB::select("
            SELECT
                LOWER(COALESCE(attribution_source, 'not_tracked'))     AS channel_id,
                SUM(COALESCE(total_in_reporting_currency, total))       AS revenue,
                COUNT(*)                                                AS orders,
                COUNT(DISTINCT customer_id)                             AS customers
            FROM orders
            WHERE workspace_id = ?
              AND occurred_at BETWEEN ? AND ?
            GROUP BY 1
        ", [$this->workspaceId, $dayStart, $dayEnd]);

        foreach ($lastNdRows as $r) {
            $rows[] = $this->row($dateStr, (string) $r->channel_id, 'last_non_direct', $r);
        }

        // ── Journey-based models (linear, position_based, time_decay, clicks_only) ─
        $connectedPlatforms = DB::table('ad_accounts')
            ->where('workspace_id', $this->workspaceId)
            ->whereNotNull('platform')
            ->distinct()
            ->pluck('platform')
            ->map(fn (string $p) => strtolower(trim($p)))
            ->filter()
            ->values()
            ->all();

        // linear
        $linearRows = DB::select(<<<'SQL'
            WITH touches AS (
                SELECT
                    o.id                                                          AS order_id,
                    COALESCE(o.total_in_reporting_currency, o.total)              AS revenue,
                    LOWER(COALESCE(
                        t.value->>'channel_type',
                        t.value->>'channel',
                        t.value->>'source',
                        'not_tracked'
                    ))                                                            AS channel_id,
                    COUNT(*) OVER (PARTITION BY o.id)                            AS touch_count
                FROM orders o
                CROSS JOIN LATERAL jsonb_array_elements(
                    CASE WHEN jsonb_typeof(o.attribution_journey) = 'array'
                         THEN o.attribution_journey ELSE '[]'::jsonb END
                ) AS t(value)
                WHERE o.workspace_id = ?
                  AND o.occurred_at BETWEEN ? AND ?
                  AND o.attribution_journey IS NOT NULL
                  AND jsonb_typeof(o.attribution_journey) = 'array'
            )
            SELECT
                channel_id,
                SUM(revenue / NULLIF(touch_count, 0))   AS revenue,
                COUNT(DISTINCT order_id)                 AS orders,
                COUNT(DISTINCT order_id)                 AS customers
            FROM touches
            GROUP BY channel_id
        SQL, [$this->workspaceId, $dayStart, $dayEnd]);

        foreach ($linearRows as $r) {
            $rows[] = $this->row($dateStr, (string) $r->channel_id, 'linear', $r);
        }

        // position_based
        $positionRows = DB::select(<<<'SQL'
            WITH numbered AS (
                SELECT
                    o.id                                                          AS order_id,
                    COALESCE(o.total_in_reporting_currency, o.total)              AS revenue,
                    LOWER(COALESCE(
                        t.value->>'channel_type',
                        t.value->>'channel',
                        t.value->>'source',
                        'not_tracked'
                    ))                                                            AS channel_id,
                    ROW_NUMBER() OVER (
                        PARTITION BY o.id ORDER BY t.value->>'timestamp_at'
                    )                                                             AS rn,
                    COUNT(*) OVER (PARTITION BY o.id)                            AS n
                FROM orders o
                CROSS JOIN LATERAL jsonb_array_elements(
                    CASE WHEN jsonb_typeof(o.attribution_journey) = 'array'
                         THEN o.attribution_journey ELSE '[]'::jsonb END
                ) AS t(value)
                WHERE o.workspace_id = ?
                  AND o.occurred_at BETWEEN ? AND ?
                  AND o.attribution_journey IS NOT NULL
                  AND jsonb_typeof(o.attribution_journey) = 'array'
            )
            SELECT
                channel_id,
                SUM(revenue * CASE
                    WHEN n = 1  THEN 1.0
                    WHEN n = 2  THEN 0.5
                    WHEN rn = 1 THEN 0.4
                    WHEN rn = n THEN 0.4
                    ELSE 0.2 / NULLIF(n - 2, 0)
                END)                                    AS revenue,
                COUNT(DISTINCT order_id)                AS orders,
                COUNT(DISTINCT order_id)                AS customers
            FROM numbered
            GROUP BY channel_id
        SQL, [$this->workspaceId, $dayStart, $dayEnd]);

        foreach ($positionRows as $r) {
            $rows[] = $this->row($dateStr, (string) $r->channel_id, 'position_based', $r);
        }

        // time_decay
        $decayRows = DB::select(<<<'SQL'
            WITH raw_weights AS (
                SELECT
                    o.id                                                          AS order_id,
                    COALESCE(o.total_in_reporting_currency, o.total)              AS revenue,
                    LOWER(COALESCE(
                        t.value->>'channel_type',
                        t.value->>'channel',
                        t.value->>'source',
                        'not_tracked'
                    ))                                                            AS channel_id,
                    EXP(
                        -0.693147 *
                        GREATEST(0.0,
                            EXTRACT(EPOCH FROM (
                                o.occurred_at - (t.value->>'timestamp_at')::timestamptz
                            )) / 86400.0
                        ) / 7.0
                    )                                                             AS raw_weight,
                    SUM(EXP(
                        -0.693147 *
                        GREATEST(0.0,
                            EXTRACT(EPOCH FROM (
                                o.occurred_at - (t.value->>'timestamp_at')::timestamptz
                            )) / 86400.0
                        ) / 7.0
                    )) OVER (PARTITION BY o.id)                                   AS weight_sum
                FROM orders o
                CROSS JOIN LATERAL jsonb_array_elements(
                    CASE WHEN jsonb_typeof(o.attribution_journey) = 'array'
                         THEN o.attribution_journey ELSE '[]'::jsonb END
                ) AS t(value)
                WHERE o.workspace_id = ?
                  AND o.occurred_at BETWEEN ? AND ?
                  AND o.attribution_journey IS NOT NULL
                  AND jsonb_typeof(o.attribution_journey) = 'array'
                  AND (t.value->>'timestamp_at') IS NOT NULL
                  AND (t.value->>'timestamp_at') <> ''
            )
            SELECT
                channel_id,
                SUM(revenue * raw_weight / NULLIF(weight_sum, 0))   AS revenue,
                COUNT(DISTINCT order_id)                              AS orders,
                COUNT(DISTINCT order_id)                              AS customers
            FROM raw_weights
            GROUP BY channel_id
        SQL, [$this->workspaceId, $dayStart, $dayEnd]);

        foreach ($decayRows as $r) {
            $rows[] = $this->row($dateStr, (string) $r->channel_id, 'time_decay', $r);
        }

        // clicks_only
        $platformFilter = '';
        if (! empty($connectedPlatforms)) {
            $connectedSet   = implode("','", array_map('addslashes', $connectedPlatforms));
            $platformFilter = "
                AND CASE LOWER(t.value->>'source')
                    WHEN 'facebook'   THEN 'facebook'
                    WHEN 'meta'       THEN 'facebook'
                    WHEN 'fb'         THEN 'facebook'
                    WHEN 'instagram'  THEN 'facebook'
                    WHEN 'ig'         THEN 'facebook'
                    WHEN 'google'     THEN 'google'
                    WHEN 'googleads'  THEN 'google'
                    WHEN 'google_ads' THEN 'google'
                    ELSE NULL
                END IN ('{$connectedSet}')";
        }

        $jChannel = "t.value->>'channel_type'";
        $jChName  = "t.value->>'channel'";
        $jSrc     = "t.value->>'source'";
        $jTs      = "t.value->>'timestamp_at'";

        $clicksOnlySql = "
            WITH paid_touches AS (
                SELECT
                    o.id                                                          AS order_id,
                    COALESCE(o.total_in_reporting_currency, o.total)              AS revenue,
                    LOWER(COALESCE({$jChannel}, {$jChName}, {$jSrc}, 'not_tracked'))
                                                                                  AS channel_id,
                    ROW_NUMBER() OVER (
                        PARTITION BY o.id
                        ORDER BY ({$jTs}) DESC NULLS LAST
                    )                                                             AS rn
                FROM orders o
                CROSS JOIN LATERAL jsonb_array_elements(
                    CASE WHEN jsonb_typeof(o.attribution_journey) = 'array'
                         THEN o.attribution_journey ELSE '[]'::jsonb END
                ) AS t(value)
                WHERE o.workspace_id = ?
                  AND o.occurred_at BETWEEN ? AND ?
                  AND o.attribution_journey IS NOT NULL
                  AND jsonb_typeof(o.attribution_journey) = 'array'
                  AND LOWER(COALESCE({$jChannel}, ''))
                      IN ('paid_social', 'paid_search')
                  {$platformFilter}
            ),
            last_paid AS (
                SELECT order_id, revenue, channel_id
                FROM paid_touches WHERE rn = 1
            ),
            all_orders AS (
                SELECT o.id AS order_id,
                       COALESCE(o.total_in_reporting_currency, o.total) AS revenue,
                       o.customer_id
                FROM orders o
                WHERE o.workspace_id = ?
                  AND o.occurred_at BETWEEN ? AND ?
                  AND o.attribution_journey IS NOT NULL
                  AND jsonb_typeof(o.attribution_journey) = 'array'
            )
            SELECT
                COALESCE(lp.channel_id, 'not_tracked')  AS channel_id,
                SUM(ao.revenue)                          AS revenue,
                COUNT(DISTINCT ao.order_id)              AS orders,
                COUNT(DISTINCT ao.customer_id)           AS customers
            FROM all_orders ao
            LEFT JOIN last_paid lp ON lp.order_id = ao.order_id
            GROUP BY COALESCE(lp.channel_id, 'not_tracked')
        ";

        $clicksOnlyRows = DB::select($clicksOnlySql, [
            $this->workspaceId, $dayStart, $dayEnd,
            $this->workspaceId, $dayStart, $dayEnd,
        ]);

        foreach ($clicksOnlyRows as $r) {
            $rows[] = $this->row($dateStr, (string) $r->channel_id, 'clicks_only', $r);
        }

        // ── data_driven (Markov chain removal-effect) ─────────────────────────────
        // Runs after all SQL-based models so heavy PHP computation only happens once.
        // MarkovChainAttributionService streams journeys via chunk() and builds the
        // transition matrix entirely in PHP — safe for the background queue context.
        try {
            $markovFrom = Carbon::parse($dateStr)->startOfDay();
            $markovTo   = Carbon::parse($dateStr)->endOfDay();
            $markovData = $markov->attribute($this->workspaceId, $markovFrom, $markovTo);

            foreach ($markovData as $channelId => $markovRow) {
                $rows[] = [
                    'workspace_id' => $this->workspaceId,
                    'date'         => $dateStr,
                    'channel_id'   => (string) $channelId,
                    'model'        => 'data_driven',
                    'revenue'      => (float) $markovRow['revenue'],
                    'orders'       => (int)   $markovRow['orders'],
                    'customers'    => (int)   $markovRow['orders'], // proxy: unique-customers per day not tracked here
                ];
            }
        } catch (\Throwable $e) {
            // Never fail the whole job because of the Markov computation.
            // Other models are still upserted; data_driven stays absent for this date.
            Log::warning('BuildAttributionSnapshotJob: data_driven failed', [
                'workspace_id' => $this->workspaceId,
                'date'         => $dateStr,
                'error'        => $e->getMessage(),
            ]);
        }

        // ── Survey model (hdyhau_response from order_metafields) ──────────────────
        if (\Illuminate\Support\Facades\Schema::hasTable('order_metafields')) {
            $surveyRows = DB::select("
                SELECT
                    LOWER(COALESCE(om.value, 'not_tracked'))              AS channel_id,
                    SUM(COALESCE(o.total_in_reporting_currency, o.total)) AS revenue,
                    COUNT(DISTINCT o.id)                                   AS orders,
                    COUNT(DISTINCT o.customer_id)                          AS customers
                FROM order_metafields om
                JOIN orders o ON o.id = om.order_id
                WHERE o.workspace_id = ?
                  AND om.key = 'hdyhau_response'
                  AND o.occurred_at BETWEEN ? AND ?
                  AND om.value IS NOT NULL
                  AND om.value <> ''
                GROUP BY 1
            ", [$this->workspaceId, $dayStart, $dayEnd]);

            foreach ($surveyRows as $r) {
                $rows[] = $this->row($dateStr, (string) $r->channel_id, 'survey', $r);
            }
        }

        // ── Upsert all rows in one batch ──────────────────────────────────────────
        if (empty($rows)) {
            Log::info('BuildAttributionSnapshotJob: no orders found', [
                'workspace_id' => $this->workspaceId,
                'date'         => $dateStr,
            ]);
            return;
        }

        $now = now()->toDateTimeString();
        foreach ($rows as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }
        unset($row);

        DB::table('daily_snapshot_attribution_models')->upsert(
            $rows,
            ['workspace_id', 'date', 'channel_id', 'model'],
            ['revenue', 'orders', 'customers', 'updated_at'],
        );

        Log::info('BuildAttributionSnapshotJob: completed', [
            'workspace_id' => $this->workspaceId,
            'date'         => $dateStr,
            'rows'         => count($rows),
        ]);
    }

    /** Build a single upsert row array from a DB result object. */
    private function row(string $date, string $channelId, string $model, object $r): array
    {
        return [
            'workspace_id' => $this->workspaceId,
            'date'         => $date,
            'channel_id'   => $channelId,
            'model'        => $model,
            'revenue'      => (float) ($r->revenue ?? 0),
            'orders'       => (int)   ($r->orders   ?? 0),
            'customers'    => (int)   ($r->customers ?? 0),
        ];
    }
}
