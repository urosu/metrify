<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\WorkspaceContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Populates customer_rfm_scores.cac_at_acquisition and .payback_days for a workspace.
 *
 * Algorithm:
 *  1. For each customer in customer_rfm_scores (latest computed_for row only):
 *     a. Find first_order_at and acquisition_source from the customers table.
 *     b. Compute cac_at_acquisition:
 *        - If acquisition_source is a paid channel (facebook/google/etc.):
 *          cac = total ad_spend for that platform on first_order date (7-day window)
 *                ÷ new_customer_count for that workspace on the same date.
 *          Uses daily_snapshots.new_customers and ad_insights (level='campaign') spend.
 *          Falls back to a 7-day window if same-day data has zero new customers.
 *        - For organic/direct/referral/email channels: cac = 0.
 *     c. Compute payback_days:
 *        - Walk forward from first_order_at through the customer's orders, summing
 *          total_in_reporting_currency until cumulative >= cac_at_acquisition.
 *        - Days elapsed from first_order_at to the crossing order's occurred_at.
 *        - If cac = 0: payback_days = 0 (no investment to recoup).
 *        - If revenue never crosses cac: payback_days = NULL.
 *  2. Writes back to the latest customer_rfm_scores row (UPDATE, not INSERT).
 *
 * Queue:     low
 * Timeout:   1800 s
 * Tries:     3
 * Unique:    yes — one run per workspace at a time
 *
 * Dispatched by: schedule (nightly 04:15 UTC, after ComputeRfmScoresJob at 04:30),
 *                RecomputeAttributionJob
 *
 * Note: Schedule runs at 04:15 UTC. ComputeRfmScoresJob dispatches at 04:30 UTC.
 * The 15-minute head-start means fresh RFM rows written at 04:30 will be processed
 * by the NEXT night's run. Payback data is stable (historical orders only), so the
 * 24-hour lag is acceptable. If same-night freshness is required, change schedule to 05:00.
 *
 * Reads:  customers, orders, ad_insights, daily_snapshots, customer_rfm_scores
 * Writes: customer_rfm_scores (cac_at_acquisition, payback_days)
 *
 * @see docs/planning/backend.md §3.3 (job spec)
 * @see docs/planning/schema.md §1.4 customer_rfm_scores
 * @see docs/competitors/_crosscut_metric_dictionary.md (CAC, Payback Period)
 */
class ComputePaybackDaysJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 1800;
    public int $tries     = 3;
    public int $uniqueFor = 1860;

    /**
     * Fall-back window (days) for CAC denominator when same-day new_customers = 0.
     * 7 days gives a stable average without going too far back.
     */
    private const CAC_WINDOW_DAYS = 7;

    /**
     * Customers processed per batch — each customer requires a forward-scan of their
     * orders, so we keep this smaller than pure-SQL jobs.
     */
    private const BATCH_SIZE = 100;

    /**
     * Paid acquisition sources that warrant a non-zero CAC.
     * Matches the acquisition_source values set by the customer upsert pipeline.
     */
    private const PAID_SOURCES = ['facebook', 'google', 'bing', 'tiktok', 'pinterest', 'snapchat'];

    public function __construct(public readonly int $workspaceId)
    {
        $this->onQueue('low');
    }

    public function uniqueId(): string
    {
        return (string) $this->workspaceId;
    }

    public function handle(): void
    {
        app(WorkspaceContext::class)->set($this->workspaceId);

        Log::info('ComputePaybackDaysJob: starting', ['workspace_id' => $this->workspaceId]);

        $processed = 0;
        $updated   = 0;

        // Fetch the latest RFM score row per customer.
        // We update these rows in place (cac_at_acquisition, payback_days are written
        // on the most-recent computed_for row — historical rows are left unchanged).
        DB::table('customer_rfm_scores as rfm')
            ->join(
                DB::raw('(
                    SELECT workspace_id, customer_id, MAX(computed_for) AS latest_date
                    FROM customer_rfm_scores
                    WHERE workspace_id = ' . $this->workspaceId . '
                    GROUP BY workspace_id, customer_id
                ) AS latest'),
                function ($join) {
                    $join->on('rfm.workspace_id', '=', 'latest.workspace_id')
                         ->on('rfm.customer_id', '=', 'latest.customer_id')
                         ->on('rfm.computed_for', '=', 'latest.latest_date');
                }
            )
            ->join('customers as c', function ($join) {
                $join->on('c.id', '=', 'rfm.customer_id')
                     ->on('c.workspace_id', '=', 'rfm.workspace_id');
            })
            ->where('rfm.workspace_id', $this->workspaceId)
            ->select([
                'rfm.id AS rfm_id',
                'rfm.customer_id',
                'c.first_order_at',
                'c.acquisition_source',
            ])
            ->orderBy('rfm.customer_id')
            ->chunk(self::BATCH_SIZE, function ($rows) use (&$processed, &$updated): void {
                foreach ($rows as $row) {
                    $cac         = $this->computeCac($row->acquisition_source, $row->first_order_at);
                    $paybackDays = $this->computePaybackDays((int) $row->customer_id, $row->first_order_at, $cac);

                    DB::table('customer_rfm_scores')
                        ->where('id', $row->rfm_id)
                        ->update([
                            'cac_at_acquisition' => $cac,
                            'payback_days'       => $paybackDays,
                        ]);

                    $processed++;
                    if ($paybackDays !== null) {
                        $updated++;
                    }
                }
            });

        Log::info('ComputePaybackDaysJob: completed', [
            'workspace_id'    => $this->workspaceId,
            'processed'       => $processed,
            'payback_written' => $updated,
        ]);
    }

    /**
     * Compute acquisition CAC for a customer.
     *
     * For paid channels: ad_spend(platform, date±window) / new_customers(date±window).
     * For organic/direct/email/referral: 0.00.
     *
     * @return float|null  null if data is unavailable (missing snapshots / ad data)
     */
    private function computeCac(?string $acquisitionSource, ?string $firstOrderAt): ?float
    {
        if ($firstOrderAt === null) {
            return null;
        }

        // Organic/direct/referral/email channels have no paid acquisition cost.
        if (! in_array(strtolower((string) $acquisitionSource), self::PAID_SOURCES, true)) {
            return 0.0;
        }

        $firstOrderDate = substr($firstOrderAt, 0, 10); // YYYY-MM-DD
        $windowStart    = date('Y-m-d', strtotime($firstOrderDate . ' -' . (self::CAC_WINDOW_DAYS - 1) . ' days'));
        $windowEnd      = $firstOrderDate;

        // Map acquisition_source to ad_accounts.platform value.
        $platform = $this->sourceToPlatform($acquisitionSource);

        // Ad spend for this platform over the window (campaign level to avoid double-counting).
        $spend = DB::table('ad_insights as ai')
            ->join('ad_accounts as aa', 'aa.id', '=', 'ai.ad_account_id')
            ->where('aa.workspace_id', $this->workspaceId)
            ->where('aa.platform', $platform)
            ->where('ai.level', 'campaign')
            ->whereBetween('ai.date', [$windowStart, $windowEnd])
            ->sum('ai.spend');

        // New customers over the same window from daily_snapshots.
        $newCustomers = DB::table('daily_snapshots')
            ->where('workspace_id', $this->workspaceId)
            ->whereBetween('date', [$windowStart, $windowEnd])
            ->sum('new_customers');

        if ($newCustomers <= 0) {
            // Zero new customers in window — cannot compute meaningful CAC.
            return null;
        }

        return round((float) $spend / (int) $newCustomers, 2);
    }

    /**
     * Compute payback days for a customer given their acquisition CAC.
     *
     * Walk forward through the customer's orders summing revenue until cumulative
     * total_in_reporting_currency >= cac. Return the elapsed days from first_order_at.
     *
     * Returns null when revenue never crosses CAC within the customer's order history.
     * Returns 0 when CAC is zero (organic/direct customers).
     */
    private function computePaybackDays(int $customerId, ?string $firstOrderAt, ?float $cac): ?int
    {
        if ($firstOrderAt === null) {
            return null;
        }

        if ($cac === null) {
            return null;
        }

        if ($cac <= 0.0) {
            return 0;
        }

        // Walk forward through all completed orders for this customer in this workspace.
        $orders = DB::table('orders')
            ->where('workspace_id', $this->workspaceId)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['completed', 'processing'])
            ->whereNotNull('total_in_reporting_currency')
            ->orderBy('occurred_at')
            ->select(['occurred_at', 'total_in_reporting_currency'])
            ->get();

        if ($orders->isEmpty()) {
            return null;
        }

        $firstTs   = strtotime($firstOrderAt);
        $cumulative = 0.0;

        foreach ($orders as $order) {
            $cumulative += (float) $order->total_in_reporting_currency;

            if ($cumulative >= $cac) {
                $orderTs     = strtotime($order->occurred_at);
                $elapsedDays = (int) max(0, ceil(($orderTs - $firstTs) / 86400));
                return $elapsedDays;
            }
        }

        return null; // Revenue never crossed CAC.
    }

    /**
     * Map an acquisition_source string to the corresponding ad_accounts.platform value.
     */
    private function sourceToPlatform(string $source): string
    {
        return match (strtolower($source)) {
            'google'    => 'google',
            'bing',
            'microsoft' => 'microsoft',
            default     => strtolower($source), // 'facebook', 'tiktok', etc.
        };
    }
}
