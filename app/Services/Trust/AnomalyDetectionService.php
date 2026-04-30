<?php

declare(strict_types=1);

namespace App\Services\Trust;

use App\ValueObjects\WorkspaceSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Evaluates the four canonical anomaly rules against rolling metric baselines.
 *
 * Rule types (seeded in anomaly_rules; must exist per workspace or the rule is skipped):
 *   real_vs_store_delta    — |Real revenue − Store revenue| / Store > threshold %
 *   platform_overreport    — (FB claimed + Google claimed) − Real > threshold %
 *   ad_spend_dod           — today's spend vs 7-day rolling average DoD % change > threshold %
 *   integration_down       — last successful sync_at > threshold hours ago
 *
 * For each breached rule, one row is written to triage_inbox_items (workspace +
 * itemable = anomaly_rule row). Uses upsert on (workspace_id, itemable_type, itemable_id)
 * so repeat runs do not create duplicate inbox items.
 *
 * Reads:  anomaly_rules, metric_baselines, daily_snapshots, ad_insights, integration_events
 * Writes: triage_inbox_items
 * Called by: DetectAnomaliesJob
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/planning/schema.md §1.10 (anomaly_rules, triage_inbox_items)
 */
class AnomalyDetectionService
{
    public const RULE_TYPES = [
        'real_vs_store_delta',
        'platform_overreport',
        'ad_spend_dod',
        'integration_down',
    ];

    /**
     * Evaluate all enabled anomaly rules for the workspace and write breaches to triage_inbox_items.
     */
    public function evaluate(int $workspaceId): void
    {
        $rules = DB::table('anomaly_rules')
            ->where('workspace_id', $workspaceId)
            ->where('enabled', true)
            ->get(['id', 'rule_type', 'threshold_value', 'threshold_unit']);

        if ($rules->isEmpty()) {
            return;
        }

        foreach ($rules as $rule) {
            try {
                $breach = match ($rule->rule_type) {
                    'real_vs_store_delta' => $this->checkRealVsStoreDelta($workspaceId, (float) $rule->threshold_value),
                    'platform_overreport' => $this->checkPlatformOverreport($workspaceId, (float) $rule->threshold_value),
                    'ad_spend_dod'        => $this->checkAdSpendDod($workspaceId, (float) $rule->threshold_value),
                    'integration_down'    => $this->checkIntegrationDown($workspaceId, (float) $rule->threshold_value),
                    default               => null,
                };

                if ($breach !== null) {
                    $this->upsertTriageItem($workspaceId, (int) $rule->id, $breach);

                    DB::table('anomaly_rules')
                        ->where('id', $rule->id)
                        ->update(['last_fired_at' => now()]);
                }
            } catch (\Throwable $e) {
                Log::warning('AnomalyDetectionService: rule check failed', [
                    'workspace_id' => $workspaceId,
                    'rule_type' => $rule->rule_type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** @return string[] List of valid rule type slugs. */
    public function ruleTypes(): array
    {
        return self::RULE_TYPES;
    }

    /**
     * Real revenue vs Store revenue disagreement.
     *
     * Looks at yesterday's daily_snapshot for this workspace.
     * Breach when |real - store| / store > threshold_pct.
     *
     * @return array{title: string, context_text: string, severity: string}|null
     */
    private function checkRealVsStoreDelta(int $workspaceId, float $thresholdPct): ?array
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $row = DB::table('daily_snapshots')
            ->selectRaw('SUM(revenue) AS store_revenue, SUM(revenue_real_attributed) AS real_revenue')
            ->where('workspace_id', $workspaceId)
            ->where('date', $yesterday)
            ->first();

        if ($row === null || (float) ($row->store_revenue ?? 0) <= 0) {
            return null;
        }

        $storeRevenue = (float) $row->store_revenue;
        $realRevenue = (float) ($row->real_revenue ?? 0);
        $deltaPct = abs($storeRevenue - $realRevenue) / $storeRevenue * 100;

        if ($deltaPct < $thresholdPct) {
            return null;
        }

        $delta = round($deltaPct, 1);
        $severity = $deltaPct >= $thresholdPct * 2 ? 'high' : 'warning';

        return [
            'title' => "Real vs Store revenue gap: {$delta}%",
            'context_text' => "Yesterday's Real revenue differs from Store revenue by {$delta}% (threshold: {$thresholdPct}%).",
            'severity' => $severity,
        ];
    }

    /**
     * Platform overreporting: FB + Google claimed > Real by threshold %.
     *
     * @return array{title: string, context_text: string, severity: string}|null
     */
    private function checkPlatformOverreport(int $workspaceId, float $thresholdPct): ?array
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $row = DB::table('daily_snapshots')
            ->selectRaw('
                SUM(revenue_real_attributed)     AS real_revenue,
                SUM(revenue_facebook_attributed) AS fb_revenue,
                SUM(revenue_google_attributed)   AS google_revenue
            ')
            ->where('workspace_id', $workspaceId)
            ->where('date', $yesterday)
            ->first();

        if ($row === null || (float) ($row->real_revenue ?? 0) <= 0) {
            return null;
        }

        $real = (float) $row->real_revenue;
        $platformClaimed = (float) ($row->fb_revenue ?? 0) + (float) ($row->google_revenue ?? 0);

        if ($platformClaimed <= 0) {
            return null;
        }

        $overreportPct = ($platformClaimed - $real) / $real * 100;

        if ($overreportPct < $thresholdPct) {
            return null;
        }

        $delta = round($overreportPct, 1);
        $severity = $overreportPct >= $thresholdPct * 2 ? 'high' : 'warning';

        return [
            'title' => "Ad platforms overreporting by {$delta}%",
            'context_text' => "Facebook + Google claimed revenue exceeds Real attributed revenue by {$delta}% (threshold: {$thresholdPct}%).",
            'severity' => $severity,
        ];
    }

    /**
     * Ad spend day-over-day spike.
     *
     * Compares yesterday's total spend against the 7-day rolling average.
     * Breach when DoD change % > threshold_pct.
     *
     * @return array{title: string, context_text: string, severity: string}|null
     */
    private function checkAdSpendDod(int $workspaceId, float $thresholdPct): ?array
    {
        $yesterday = Carbon::yesterday()->toDateString();
        $sevenDaysAgo = Carbon::now()->subDays(8)->toDateString();

        $rows = DB::table('ad_insights')
            ->selectRaw('date, SUM(spend_in_reporting_currency) AS daily_spend')
            ->where('workspace_id', $workspaceId)
            ->where('level', 'campaign')
            ->whereNull('hour')
            ->whereBetween('date', [$sevenDaysAgo, $yesterday])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        if ($rows->count() < 2) {
            return null;
        }

        $yesterdayRow = $rows->firstWhere('date', $yesterday);
        if ($yesterdayRow === null) {
            return null;
        }

        $yesterdaySpend = (float) $yesterdayRow->daily_spend;
        $prior = $rows->where('date', '<', $yesterday);

        if ($prior->isEmpty()) {
            return null;
        }

        $rollingAvg = $prior->avg('daily_spend');

        if ($rollingAvg <= 0) {
            return null;
        }

        $dodPct = abs($yesterdaySpend - $rollingAvg) / $rollingAvg * 100;

        if ($dodPct < $thresholdPct) {
            return null;
        }

        $direction = $yesterdaySpend > $rollingAvg ? 'spike' : 'drop';
        $delta = round($dodPct, 1);
        $severity = $dodPct >= $thresholdPct * 2 ? 'high' : 'warning';

        return [
            'title' => "Ad spend {$direction}: {$delta}% vs 7-day avg",
            'context_text' => "Yesterday's ad spend changed by {$delta}% vs the 7-day rolling average (threshold: {$thresholdPct}%).",
            'severity' => $severity,
        ];
    }

    /**
     * Integration down — last event received more than threshold hours ago.
     *
     * Checks integration_events for each store in the workspace.
     * Breach when max(received_at) < now() - threshold hours.
     *
     * @return array{title: string, context_text: string, severity: string}|null
     */
    private function checkIntegrationDown(int $workspaceId, float $thresholdHours): ?array
    {
        $cutoff = Carbon::now()->subHours($thresholdHours);

        $staleStores = DB::table('stores')
            ->where('workspace_id', $workspaceId)
            ->where('status', 'active')
            ->whereNotExists(function ($q) use ($cutoff) {
                $q->select(DB::raw(1))
                  ->from('integration_events')
                  ->whereColumn('integration_events.integrationable_id', 'stores.id')
                  ->where('integration_events.integrationable_type', 'App\\Models\\Store')
                  ->where('integration_events.received_at', '>=', $cutoff);
            })
            ->count();

        if ($staleStores === 0) {
            return null;
        }

        $hours = (int) $thresholdHours;
        $severity = $thresholdHours >= 24 ? 'critical' : 'high';

        return [
            'title' => "{$staleStores} store(s) not syncing for {$hours}h+",
            'context_text' => "{$staleStores} active store(s) have not received integration events in over {$hours} hours.",
            'severity' => $severity,
        ];
    }

    /**
     * Write or update a triage_inbox_items row for the given anomaly rule breach.
     *
     * @param array{title: string, context_text: string, severity: string} $breach
     */
    private function upsertTriageItem(int $workspaceId, int $ruleId, array $breach): void
    {
        $now = now()->toDateTimeString();

        DB::table('triage_inbox_items')->upsert(
            [[
                'workspace_id'    => $workspaceId,
                'itemable_type'   => 'App\\Models\\AnomalyRule',
                'itemable_id'     => $ruleId,
                'severity'        => $breach['severity'],
                'title'           => $breach['title'],
                'context_text'    => $breach['context_text'],
                'status'          => 'open',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]],
            ['workspace_id', 'itemable_type', 'itemable_id'],
            ['severity', 'title', 'context_text', 'status', 'updated_at'],
        );
    }
}
