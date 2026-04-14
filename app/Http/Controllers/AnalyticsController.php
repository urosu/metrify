<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DailyNote;
use App\Models\Store;
use App\Services\WorkspaceContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AnalyticsController extends Controller
{
    // -------------------------------------------------------------------------
    // By Product
    // -------------------------------------------------------------------------

    public function products(Request $request): InertiaResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $validated = $request->validate([
            'from'      => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to'        => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'store_ids' => ['sometimes', 'nullable', 'string'],
            'sort_by'   => ['sometimes', 'nullable', 'in:revenue,units'],
            'sort_dir'  => ['sometimes', 'nullable', 'in:asc,desc'],
        ]);

        $from     = $validated['from']   ?? now()->subDays(29)->toDateString();
        $to       = $validated['to']     ?? now()->toDateString();
        $sortBy   = $validated['sort_by']  ?? 'revenue';
        $sortDir  = strtoupper($validated['sort_dir'] ?? 'desc');
        $storeIds = $this->parseStoreIds($validated['store_ids'] ?? '', $workspaceId);

        $storeClause = ! empty($storeIds)
            ? 'AND store_id IN (' . implode(',', array_map('intval', $storeIds)) . ')'
            : '';

        $orderClause = match ($sortBy) {
            'units'   => "ORDER BY c.units {$sortDir} NULLS LAST, c.revenue DESC NULLS LAST",
            default   => "ORDER BY c.revenue {$sortDir} NULLS LAST, c.units DESC",
        };

        // Why: top_products JSONB dropped from daily_snapshots; query normalized table instead.
        // See: PLANNING.md "daily_snapshot_products"
        // Related: app/Jobs/ComputeDailySnapshotJob.php (writes this table)
        //
        // Previous period = same length, immediately before $from.
        // Deltas are NULL when compare_from falls before earliest snapshot (PLANNING.md spec).
        $periodDays  = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
        $compareTo   = Carbon::parse($from)->subDay()->toDateString();
        $compareFrom = Carbon::parse($compareTo)->subDays($periodDays - 1)->toDateString();

        $rows = DB::select(
            "WITH current_p AS (
                SELECT product_external_id,
                       MAX(product_name) AS name,
                       SUM(units)::int   AS units,
                       SUM(revenue)      AS revenue
                FROM daily_snapshot_products
                WHERE workspace_id = ?
                  AND snapshot_date BETWEEN ? AND ?
                  {$storeClause}
                GROUP BY product_external_id
            ),
            prev_p AS (
                SELECT product_external_id,
                       SUM(units)::int AS prev_units,
                       SUM(revenue)    AS prev_revenue
                FROM daily_snapshot_products
                WHERE workspace_id = ?
                  AND snapshot_date BETWEEN ? AND ?
                  {$storeClause}
                GROUP BY product_external_id
            ),
            earliest AS (
                SELECT MIN(snapshot_date) AS earliest_date
                FROM daily_snapshot_products
                WHERE workspace_id = ?
                {$storeClause}
            )
            SELECT
                c.product_external_id AS external_id,
                c.name,
                c.units,
                c.revenue,
                CASE
                    WHEN e.earliest_date IS NULL OR e.earliest_date > ?::date THEN NULL
                    WHEN p.prev_revenue IS NULL OR p.prev_revenue = 0          THEN NULL
                    ELSE ROUND(((c.revenue - p.prev_revenue) / p.prev_revenue * 100)::numeric, 1)
                END AS revenue_delta,
                CASE
                    WHEN e.earliest_date IS NULL OR e.earliest_date > ?::date THEN NULL
                    WHEN p.prev_units IS NULL OR p.prev_units = 0              THEN NULL
                    ELSE ROUND(((c.units - p.prev_units)::decimal / p.prev_units * 100)::numeric, 1)
                END AS units_delta
            FROM current_p c
            CROSS JOIN earliest e
            LEFT JOIN prev_p p ON p.product_external_id = c.product_external_id
            {$orderClause}
            LIMIT 50",
            [$workspaceId, $from, $to, $workspaceId, $compareFrom, $compareTo, $workspaceId, $compareFrom, $compareFrom],
        );

        $products = array_map(fn ($r) => [
            'external_id'   => $r->external_id,
            'name'          => $r->name,
            'units'         => (int) $r->units,
            'revenue'       => $r->revenue       !== null ? (float) $r->revenue       : null,
            'revenue_delta' => $r->revenue_delta !== null ? (float) $r->revenue_delta : null,
            'units_delta'   => $r->units_delta   !== null ? (float) $r->units_delta   : null,
        ], $rows);

        return Inertia::render('Analytics/Products', [
            'products'  => $products,
            'from'      => $from,
            'to'        => $to,
            'store_ids' => $storeIds,
            'sort_by'   => $sortBy,
            'sort_dir'  => strtolower($sortDir),
        ]);
    }

    // -------------------------------------------------------------------------
    // Daily report
    // -------------------------------------------------------------------------

    public function daily(Request $request): InertiaResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $validated = $request->validate([
            'from'       => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to'         => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'store_ids'  => ['sometimes', 'nullable', 'string'],
            'sort_by'    => ['sometimes', 'nullable', 'in:date,revenue,orders,items_sold,items_per_order,aov,ad_spend,roas,marketing_pct'],
            'sort_dir'   => ['sometimes', 'nullable', 'in:asc,desc'],
            'hide_empty' => ['sometimes', 'nullable', 'in:0,1'],
        ]);

        $from      = $validated['from']       ?? now()->subDays(29)->toDateString();
        $to        = $validated['to']         ?? now()->toDateString();
        $sortBy    = $validated['sort_by']    ?? 'date';
        $sortDir   = $validated['sort_dir']   ?? 'desc';
        $hideEmpty = ($validated['hide_empty'] ?? '0') === '1';
        $storeIds  = $this->parseStoreIds($validated['store_ids'] ?? '', $workspaceId);

        $rows   = $this->buildDailyRows($workspaceId, $from, $to, $storeIds, $sortBy, $sortDir, $hideEmpty);
        $totals = $this->buildDailyTotals($rows);

        return Inertia::render('Analytics/Daily', [
            'rows'       => $rows,
            'totals'     => $totals,
            'from'       => $from,
            'to'         => $to,
            'store_ids'  => $storeIds,
            'sort_by'    => $sortBy,
            'sort_dir'   => $sortDir,
            'hide_empty' => $hideEmpty,
        ]);
    }

    // -------------------------------------------------------------------------
    // Upsert day note
    // -------------------------------------------------------------------------

    public function upsertNote(Request $request, string $date): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $request->validate([
            'note' => ['present', 'nullable', 'string', 'max:1000'],
        ]);

        $userId = $request->user()->id;
        $note   = trim((string) $request->input('note'));

        if ($note === '') {
            // Delete the note if emptied
            DailyNote::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('date', $date)
                ->delete();
        } else {
            // Why: updateOrInsert avoids the race condition where two concurrent requests
            // both pass the first() check and then both attempt create(), causing a
            // unique-constraint violation on (workspace_id, date).
            DailyNote::withoutGlobalScopes()->updateOrInsert(
                ['workspace_id' => $workspaceId, 'date' => $date],
                ['note' => $note, 'updated_by' => $userId, 'created_by' => $userId],
            );
        }

        return response()->noContent();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @param int[]  $storeIds
     * @return array<int, array{date:string,revenue:float,orders:int,items_sold:int,items_per_order:float|null,aov:float|null,ad_spend:float|null,roas:float|null,marketing_pct:float|null,note:string|null}>
     */
    private function buildDailyRows(
        int $workspaceId,
        string $from,
        string $to,
        array $storeIds,
        string $sortBy,
        string $sortDir,
        bool $hideEmpty = false,
    ): array {
        $storeFilter = ! empty($storeIds)
            ? 'AND s.store_id IN (' . implode(',', array_map('intval', $storeIds)) . ')'
            : '';

        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $allowedSort = [
            'date', 'revenue', 'orders', 'items_sold',
            'items_per_order', 'aov', 'ad_spend', 'roas', 'marketing_pct',
        ];
        $orderCol = in_array($sortBy, $allowedSort, true) ? $sortBy : 'date';
        $orderClause = match ($orderCol) {
            'date'    => "ORDER BY s.date {$sortDir}",
            default   => "ORDER BY {$orderCol} {$sortDir} NULLS LAST, s.date DESC",
        };
        $havingClause = $hideEmpty ? 'HAVING COALESCE(SUM(s.orders_count), 0) > 0' : '';

        $rows = DB::select("
            SELECT
                s.date::text                                                          AS date,
                COALESCE(SUM(s.revenue), 0)                                           AS revenue,
                COALESCE(SUM(s.orders_count), 0)                                      AS orders,
                COALESCE(SUM(s.items_sold), 0)                                        AS items_sold,
                CASE WHEN SUM(s.orders_count) > 0
                     THEN SUM(s.items_sold)::numeric / SUM(s.orders_count)
                     ELSE NULL END                                                    AS items_per_order,
                CASE WHEN SUM(s.orders_count) > 0
                     THEN SUM(s.revenue) / SUM(s.orders_count)
                     ELSE NULL END                                                    AS aov,
                COALESCE(ai.ad_spend, 0)                                              AS ad_spend,
                CASE WHEN COALESCE(ai.ad_spend, 0) > 0
                     THEN SUM(s.revenue) / ai.ad_spend
                     ELSE NULL END                                                    AS roas,
                CASE WHEN SUM(s.revenue) > 0 AND COALESCE(ai.ad_spend, 0) > 0
                     THEN ai.ad_spend / SUM(s.revenue) * 100
                     ELSE NULL END                                                    AS marketing_pct,
                dn.note                                                               AS note
            FROM daily_snapshots s
            LEFT JOIN (
                SELECT date, SUM(spend_in_reporting_currency) AS ad_spend
                FROM ad_insights
                WHERE workspace_id = ? AND level = 'campaign' AND hour IS NULL
                GROUP BY date
            ) ai ON ai.date = s.date
            LEFT JOIN daily_notes dn
                ON dn.workspace_id = ? AND dn.date = s.date
            WHERE s.workspace_id = ?
              AND s.date BETWEEN ? AND ?
              {$storeFilter}
            GROUP BY s.date, ai.ad_spend, dn.note
            {$havingClause}
            {$orderClause}
        ", [$workspaceId, $workspaceId, $workspaceId, $from, $to]);

        return array_map(function (object $r): array {
            return [
                'date'             => $r->date,
                'revenue'          => (float) $r->revenue,
                'orders'           => (int)   $r->orders,
                'items_sold'       => (int)   $r->items_sold,
                'items_per_order'  => $r->items_per_order !== null
                    ? round((float) $r->items_per_order, 2) : null,
                'aov'              => $r->aov !== null
                    ? round((float) $r->aov, 2) : null,
                'ad_spend'         => $r->ad_spend !== null && (float) $r->ad_spend > 0
                    ? round((float) $r->ad_spend, 2) : null,
                'roas'             => $r->roas !== null
                    ? round((float) $r->roas, 2) : null,
                'marketing_pct'    => $r->marketing_pct !== null
                    ? round((float) $r->marketing_pct, 1) : null,
                'note'             => $r->note,
            ];
        }, $rows);
    }

    /**
     * Compute column totals/averages from the daily rows.
     *
     * @param  array<int, array{date:string,revenue:float,orders:int,items_sold:int,...}> $rows
     * @return array{revenue:float,orders:int,items_sold:int,items_per_order:float|null,aov:float|null,ad_spend:float|null,roas:float|null,marketing_pct:float|null}
     */
    private function buildDailyTotals(array $rows): array
    {
        if (empty($rows)) {
            return [
                'revenue' => 0, 'orders' => 0, 'items_sold' => 0,
                'items_per_order' => null, 'aov' => null,
                'ad_spend' => null, 'roas' => null, 'marketing_pct' => null,
            ];
        }

        $revenue   = array_sum(array_column($rows, 'revenue'));
        $orders    = array_sum(array_column($rows, 'orders'));
        $items     = array_sum(array_column($rows, 'items_sold'));
        $adSpend   = array_sum(array_filter(array_column($rows, 'ad_spend')));

        return [
            'revenue'         => round($revenue, 2),
            'orders'          => $orders,
            'items_sold'      => $items,
            'items_per_order' => $orders > 0 ? round($items / $orders, 2) : null,
            'aov'             => $orders > 0 ? round($revenue / $orders, 2) : null,
            'ad_spend'        => $adSpend > 0 ? round($adSpend, 2) : null,
            'roas'            => ($adSpend > 0 && $revenue > 0)
                ? round($revenue / $adSpend, 2) : null,
            'marketing_pct'   => ($adSpend > 0 && $revenue > 0)
                ? round(($adSpend / $revenue) * 100, 1) : null,
        ];
    }

    /** @return int[] */
    private function parseStoreIds(string $raw, int $workspaceId): array
    {
        if ($raw === '') {
            return [];
        }
        $ids = array_values(array_filter(
            array_map('intval', explode(',', $raw)),
            fn (int $id) => $id > 0,
        ));
        if (empty($ids)) {
            return [];
        }
        return Store::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
