<?php

declare(strict_types=1);

namespace App\Services\Workspace;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CRUD for workspace metric targets (goals).
 *
 * Targets are metric goals (ROAS, CAC, revenue, etc.) scoped to a period.
 * They are displayed as TargetLine / TargetProgress chrome on the relevant pages.
 * Progress is computed on-the-fly against `daily_snapshots` or `ad_insights`
 * depending on the metric type.
 *
 * Reads:  workspace_targets, daily_snapshots, ad_insights
 * Writes: workspace_targets
 * Called by: TargetsController (new)
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/planning/schema.md §1.8 (workspace_targets table)
 * @see docs/UX.md §5.23 (Target primitive)
 */
class TargetService
{
    /**
     * Metrics sourced from daily_snapshots (store-level).
     */
    private const SNAPSHOT_METRICS = ['revenue', 'orders', 'aov', 'profit', 'roas', 'mer'];

    /**
     * Metrics sourced from ad_insights (ad-spend-level).
     */
    private const AD_METRICS = ['spend', 'cac', 'cpa', 'cpc', 'cpm'];

    /**
     * Create a new workspace target.
     *
     * @param  array{metric: string, period: string, period_start?: string|null, period_end?: string|null, target_value_reporting: float, currency?: string|null, owner_user_id?: int|null, visible_on_pages?: string[]}  $data
     */
    public function create(int $workspaceId, int $createdBy, array $data): int
    {
        return DB::table('workspace_targets')->insertGetId([
            'workspace_id'            => $workspaceId,
            'metric'                  => $data['metric'],
            'period'                  => $data['period'],
            'period_start'            => $data['period_start'] ?? null,
            'period_end'              => $data['period_end'] ?? null,
            'target_value_reporting'  => $data['target_value_reporting'],
            'currency'                => $data['currency'] ?? null,
            'owner_user_id'           => $data['owner_user_id'] ?? null,
            'visible_on_pages'        => json_encode($data['visible_on_pages'] ?? []),
            'status'                  => 'active',
            'created_by'              => $createdBy,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }

    /**
     * Update a target's value, period, or ownership.
     *
     * @param  array{metric?: string, period?: string, period_start?: string|null, period_end?: string|null, target_value_reporting?: float, currency?: string|null, owner_user_id?: int|null, visible_on_pages?: string[]}  $data
     */
    public function update(int $id, array $data): void
    {
        $fields = ['updated_at' => now()];

        foreach (['metric', 'period', 'period_start', 'period_end', 'target_value_reporting', 'currency', 'owner_user_id'] as $key) {
            if (array_key_exists($key, $data)) {
                $fields[$key] = $data[$key];
            }
        }

        if (array_key_exists('visible_on_pages', $data)) {
            $fields['visible_on_pages'] = json_encode($data['visible_on_pages']);
        }

        DB::table('workspace_targets')->where('id', $id)->update($fields);
    }

    /**
     * Archive a target (soft-delete via status column).
     */
    public function archive(int $id): void
    {
        DB::table('workspace_targets')->where('id', $id)->update([
            'status'     => 'archived',
            'updated_at' => now(),
        ]);
    }

    /**
     * Compute current-period progress for a target.
     *
     * Returns the actual value achieved so far and the percentage of the target reached.
     * For revenue/orders/profit metrics, reads `daily_snapshots`.
     * For ad spend / ROAS metrics, reads `ad_insights` (level=campaign).
     * For metrics that span the full period, normalises by days elapsed.
     *
     * @param  object  $target  Row from workspace_targets
     * @return array{actual: float|null, progress_pct: float|null}
     */
    public function progressFor(object $target): array
    {
        [$from, $to] = $this->periodBounds($target);

        if ($from === null || $to === null) {
            return ['actual' => null, 'progress_pct' => null];
        }

        $actual = $this->fetchActual($target, $from, $to);

        if ($actual === null || (float) $target->target_value_reporting <= 0) {
            return ['actual' => $actual, 'progress_pct' => null];
        }

        $progressPct = round($actual / (float) $target->target_value_reporting * 100, 1);

        return ['actual' => $actual, 'progress_pct' => $progressPct];
    }

    /**
     * Resolve period start/end dates from a target row.
     *
     * @return array{string|null, string|null}
     */
    private function periodBounds(object $target): array
    {
        if ($target->period === 'custom') {
            return [$target->period_start ?? null, $target->period_end ?? null];
        }

        $now = now();

        return match ($target->period) {
            'this_week'    => [$now->startOfWeek()->toDateString(), $now->endOfWeek()->toDateString()],
            'this_month'   => [$now->startOfMonth()->toDateString(), $now->endOfMonth()->toDateString()],
            'this_quarter' => [$now->startOfQuarter()->toDateString(), $now->endOfQuarter()->toDateString()],
            default        => [null, null],
        };
    }

    /**
     * Fetch the actual metric value for the target's workspace and period.
     */
    private function fetchActual(object $target, string $from, string $to): ?float
    {
        $workspaceId = (int) $target->workspace_id;
        $metric = $target->metric;

        if (in_array($metric, self::SNAPSHOT_METRICS, true)) {
            return $this->fetchFromSnapshots($workspaceId, $metric, $from, $to);
        }

        if (in_array($metric, self::AD_METRICS, true)) {
            return $this->fetchFromAdInsights($workspaceId, $metric, $from, $to);
        }

        return null;
    }

    private function fetchFromSnapshots(int $workspaceId, string $metric, string $from, string $to): ?float
    {
        $col = match ($metric) {
            'revenue' => 'SUM(revenue)',
            'orders'  => 'SUM(orders_count)',
            'profit'  => 'SUM(gross_profit)',
            'aov'     => 'SUM(revenue) / NULLIF(SUM(orders_count), 0)',
            'roas'    => 'SUM(revenue_real_attributed) / NULLIF(SUM(ad_spend), 0)',
            'mer'     => 'SUM(revenue) / NULLIF(SUM(ad_spend), 0)',
            default   => null,
        };

        if ($col === null) {
            return null;
        }

        $row = DB::table('daily_snapshots')
            ->selectRaw("$col AS val")
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->first();

        return $row !== null ? (($row->val !== null) ? (float) $row->val : null) : null;
    }

    private function fetchFromAdInsights(int $workspaceId, string $metric, string $from, string $to): ?float
    {
        $col = match ($metric) {
            'spend' => 'SUM(spend_in_reporting_currency)',
            'cac'   => 'SUM(spend_in_reporting_currency) / NULLIF(SUM(purchases), 0)',
            'cpa'   => 'SUM(spend_in_reporting_currency) / NULLIF(SUM(purchases), 0)',
            'cpc'   => 'SUM(spend_in_reporting_currency) / NULLIF(SUM(clicks), 0)',
            'cpm'   => 'SUM(spend_in_reporting_currency) / NULLIF(SUM(impressions), 0) * 1000',
            default => null,
        };

        if ($col === null) {
            return null;
        }

        $row = DB::table('ad_insights')
            ->selectRaw("$col AS val")
            ->where('workspace_id', $workspaceId)
            ->where('level', 'campaign')
            ->whereNull('hour')
            ->whereBetween('date', [$from, $to])
            ->first();

        return $row !== null ? (($row->val !== null) ? (float) $row->val : null) : null;
    }
}
