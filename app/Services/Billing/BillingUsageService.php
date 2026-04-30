<?php

declare(strict_types=1);

namespace App\Services\Billing;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Computes monthly 0.4% revenue-share usage per workspace and reports to Stripe.
 *
 * Billing basis (in priority order):
 *   1. Store GMV   — `daily_snapshots.revenue` summed over the month.
 *   2. Ad spend    — `ad_insights WHERE level='campaign'` (fallback when no store connected).
 *   3. Enterprise  — billing_plan='enterprise': skip (invoiced manually).
 *
 * Agency billing consolidation: when `workspaces.billing_workspace_id` is set,
 * the child's revenue rolls up to the parent workspace on report.
 *
 * Rate: `rate_bps = 40` = 0.40%.
 *
 * Reads:  daily_snapshots.revenue, ad_insights.spend, workspaces
 * Writes: billing_revenue_share_usage
 * Called by: ReportMonthlyRevenueToStripeJob
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/planning/backend.md §14 (BillingUsageService detail)
 * @see docs/planning/schema.md §1.11 (billing_revenue_share_usage table)
 */
class BillingUsageService
{
    private const RATE_BPS = 40; // 0.40%

    /**
     * Compute and persist the revenue-share usage for a workspace × month.
     *
     * Returns the `billing_revenue_share_usage.id` of the upserted row,
     * or null if the workspace is skipped (enterprise plan or no revenue/spend).
     */
    public function computeForPeriod(int $workspaceId, Carbon $month): ?int
    {
        $workspace = DB::table('workspaces')
            ->where('id', $workspaceId)
            ->first(['id', 'billing_plan', 'reporting_currency']);

        if ($workspace === null) {
            return null;
        }

        // Enterprise workspaces are invoiced manually.
        if (($workspace->billing_plan ?? '') === 'enterprise') {
            return null;
        }

        $periodMonth = $month->startOfMonth()->toDateString();
        $from = $month->startOfMonth()->toDateString();
        $to   = $month->endOfMonth()->toDateString();
        $currency = $workspace->reporting_currency ?? 'EUR';

        // has_store/has_ads were dropped from workspaces in L2; derive live from child tables.
        $hasStore = DB::table('stores')->where('workspace_id', $workspaceId)->exists();
        $hasAds   = DB::table('ad_accounts')->where('workspace_id', $workspaceId)->exists();

        $gross = $this->fetchGrossRevenue($workspaceId, $from, $to, $hasStore, $hasAds);

        if ($gross === null || $gross <= 0) {
            return null;
        }

        // No exclusions in v1 — billable = gross.
        $billable = $gross;
        $computed = round($billable * self::RATE_BPS / 10000, 2);

        $now = now()->toDateTimeString();

        DB::table('billing_revenue_share_usage')->upsert(
            [[
                'workspace_id'                 => $workspaceId,
                'period_month'                 => $periodMonth,
                'reporting_currency'           => $currency,
                'gross_revenue_reporting'      => $gross,
                'billable_revenue_reporting'   => $billable,
                'rate_bps'                     => self::RATE_BPS,
                'computed_amount_reporting'    => $computed,
                'reported_to_stripe_at'        => null,
                'stripe_usage_record_id'       => null,
                'created_at'                   => $now,
            ]],
            ['workspace_id', 'period_month'],
            ['gross_revenue_reporting', 'billable_revenue_reporting', 'rate_bps', 'computed_amount_reporting'],
        );

        return (int) DB::table('billing_revenue_share_usage')
            ->where('workspace_id', $workspaceId)
            ->where('period_month', $periodMonth)
            ->value('id');
    }

    /**
     * Report a computed usage row to Stripe and mark it as reported.
     *
     * Calls `Stripe\SubscriptionItem::createUsageRecord` on the workspace's metered
     * subscription item. Stores the returned usage record ID.
     */
    public function reportToStripe(int $usageId): void
    {
        $row = DB::table('billing_revenue_share_usage')->where('id', $usageId)->first();

        if ($row === null || $row->reported_to_stripe_at !== null) {
            return;
        }

        $workspace = DB::table('workspaces')
            ->where('id', $row->workspace_id)
            ->first(['stripe_id', 'pm_type']);

        if ($workspace === null || empty($workspace->stripe_id)) {
            Log::warning('BillingUsageService: no Stripe customer for workspace', [
                'workspace_id' => $row->workspace_id,
                'usage_id'     => $usageId,
            ]);
            return;
        }

        try {
            $stripe = app(\Stripe\StripeClient::class);

            $subscriptionItemId = $this->resolveMeteredSubscriptionItem($stripe, $workspace->stripe_id);

            if ($subscriptionItemId === null) {
                Log::warning('BillingUsageService: no metered subscription item', [
                    'workspace_id' => $row->workspace_id,
                ]);
                return;
            }

            $usageRecord = $stripe->subscriptionItems->createUsageRecord(
                $subscriptionItemId,
                [
                    'quantity'  => (int) round((float) $row->computed_amount_reporting * 100), // cents
                    'timestamp' => now()->timestamp,
                    'action'    => 'set',
                ]
            );

            DB::table('billing_revenue_share_usage')->where('id', $usageId)->update([
                'reported_to_stripe_at'  => now(),
                'stripe_usage_record_id' => $usageRecord->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('BillingUsageService: Stripe report failed', [
                'workspace_id' => $row->workspace_id,
                'usage_id'     => $usageId,
                'error'        => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Return the raw billable amount in the workspace's reporting currency for the given date range.
     *
     * Derives the billing basis live (store GMV preferred, ad spend as fallback).
     * Returns null if the workspace has no measurable activity (skip billing).
     *
     * Used by ReportMonthlyRevenueToStripeJob to obtain the figure before FX conversion.
     *
     * @see docs/planning/backend.md §14
     */
    public function billableAmountForPeriod(int $workspaceId, string $from, string $to): ?float
    {
        $hasStore = DB::table('stores')->where('workspace_id', $workspaceId)->exists();
        $hasAds   = DB::table('ad_accounts')->where('workspace_id', $workspaceId)->exists();

        return $this->fetchGrossRevenue($workspaceId, $from, $to, $hasStore, $hasAds);
    }

    /**
     * Fetch gross revenue using the appropriate billing basis for this workspace.
     */
    private function fetchGrossRevenue(int $workspaceId, string $from, string $to, bool $hasStore, bool $hasAds): ?float
    {
        if ($hasStore) {
            $row = DB::table('daily_snapshots')
                ->selectRaw('SUM(revenue) AS total')
                ->where('workspace_id', $workspaceId)
                ->whereBetween('date', [$from, $to])
                ->first();

            $value = $row?->total !== null ? (float) $row->total : null;
            if ($value !== null && $value > 0) {
                return $value;
            }
        }

        if ($hasAds) {
            $row = DB::table('ad_insights')
                ->selectRaw('SUM(spend_in_reporting_currency) AS total')
                ->where('workspace_id', $workspaceId)
                ->where('level', 'campaign')
                ->whereNull('hour')
                ->whereBetween('date', [$from, $to])
                ->first();

            return $row?->total !== null ? (float) $row->total : null;
        }

        return null;
    }

    /**
     * Find the first metered subscription item ID for a Stripe customer.
     */
    private function resolveMeteredSubscriptionItem(\Stripe\StripeClient $stripe, string $stripeCustomerId): ?string
    {
        $subscriptions = $stripe->subscriptions->all([
            'customer' => $stripeCustomerId,
            'status'   => 'active',
            'limit'    => 1,
        ]);

        if (empty($subscriptions->data)) {
            return null;
        }

        foreach ($subscriptions->data[0]->items->data as $item) {
            if (($item->price->recurring->usage_type ?? '') === 'metered') {
                return $item->id;
            }
        }

        return null;
    }
}
