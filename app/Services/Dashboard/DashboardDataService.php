<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\AdInsight;
use App\Models\DailySnapshot;
use App\Models\Ga4ProductPageView;
use App\Models\HistoricalImportJob;
use App\Models\HourlySnapshot;
use App\Models\Order;
use App\Models\TriageInboxItem;
use App\Services\Metrics\MetricSourceResolver;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the v1 Dashboard payload.
 *
 * One method per prop on resources/js/Pages/Dashboard.tsx so each concern stays isolated
 * and individually testable. Every read goes through daily_snapshots / hourly_snapshots /
 * ad_insights / triage_inbox_items — no raw `orders` aggregation on the request path,
 * per CLAUDE.md §Gotchas.
 *
 * Called by: App\Http\Controllers\DashboardController
 * Reads:     daily_snapshots, hourly_snapshots, ad_insights, triage_inbox_items, orders (today only)
 * Writes:    nothing (pure read service)
 *
 * @see docs/pages/dashboard.md
 * @see docs/planning/schema.md §1.5
 */
final class DashboardDataService
{
    private const TRIAGE_LIMIT = 10;
    private const SPARKLINE_MAX_POINTS = 30;

    public function __construct(
        private readonly MetricSourceResolver $sourceResolver,
    ) {}

    /**
     * Top-level KPIs for the KpiGrid and StatStripe.
     *
     * revenue comes from `revenue_real_attributed` (per-source reconciled total)
     * by default; when `$source` is provided the corresponding
     * daily_snapshots column is used instead (see MetricSourceResolver::REVENUE_COLUMN).
     *
     * `not_tracked = revenue_store - revenue_real` and CAN be negative (thesis §7).
     *
     * Sparklines are returned as up-to-30-day daily series aligned to the range.
     *
     * @param string $source  Active source lens — 'real' (default) | 'store' |
     *                        'facebook' | 'google' | 'gsc' | 'ga4'
     * @return array<string, mixed> Shape matches Props['metrics'] in Dashboard.tsx
     */
    public function metrics(int $workspaceId, string $from, string $to, string $source = 'real'): array
    {
        [$prevFrom, $prevTo] = $this->previousRange($from, $to);

        $revenueColumn = $this->sourceResolver->columnFor('revenue', $source);

        $current  = $this->aggregateDaily($workspaceId, $from, $to);
        $previous = $this->aggregateDaily($workspaceId, $prevFrom, $prevTo);

        $adCurrent  = $this->aggregateAdSpend($workspaceId, $from, $to);
        $adPrevious = $this->aggregateAdSpend($workspaceId, $prevFrom, $prevTo);

        $revenueSparkline  = $this->dailySparkline($workspaceId, $from, $to, $revenueColumn);
        $ordersSparkline   = $this->dailySparkline($workspaceId, $from, $to, 'orders_count');
        $notTrackedSpark   = $this->notTrackedSparkline($workspaceId, $from, $to);
        $adSpendSparkline  = $this->adSpendSparkline($workspaceId, $from, $to);
        $merSparkline      = $this->merSparkline($workspaceId, $from, $to);

        // revenue_real and revenue_store are always fetched for not_tracked and MER.
        // When a non-default source lens is active, `revenueActive` uses the
        // source-specific column and the revenue MetricCard shows that lens value.
        $revenueReal       = (float) $current->revenue_real;
        $revenueStore      = (float) $current->revenue;
        $revenueRealPrev   = (float) $previous->revenue_real;
        $revenueStorePrev  = (float) $previous->revenue;

        // Source-lens revenue: re-query with the selected column when not 'real'.
        if ($source !== 'real') {
            $lensRow     = $this->aggregateDailyColumn($workspaceId, $from, $to, $revenueColumn);
            $lensRowPrev = $this->aggregateDailyColumn($workspaceId, $prevFrom, $prevTo, $revenueColumn);
            $revenueActive     = (float) ($lensRow->v ?? 0);
            $revenueActivePrev = (float) ($lensRowPrev->v ?? 0);
        } else {
            $revenueActive     = $revenueReal;
            $revenueActivePrev = $revenueRealPrev;
        }

        $orders            = (int)   $current->orders_count;
        $ordersPrev        = (int)   $previous->orders_count;
        $notTracked        = round($revenueStore - $revenueReal, 2);
        $notTrackedPrev    = round($revenueStorePrev - $revenueRealPrev, 2);
        $adSpend           = (float) $adCurrent;
        $adSpendPrev       = (float) $adPrevious;
        $mer               = $adSpend > 0 ? round($revenueStore / $adSpend, 4) : null;
        $merPrev           = $adSpendPrev > 0 ? round($revenueStorePrev / $adSpendPrev, 4) : null;

        $aov = $this->aovStats($workspaceId, $from, $to);

        $sessions       = (int) ($current->sessions ?? 0);
        $cvr            = $sessions > 0 ? round($orders / $sessions, 4) : null;
        $revenueExVat   = round($revenueStore - (float) $current->tax_total, 2);
        $shippingRev    = round((float) $current->shipping_revenue_total, 2);
        $taxCollected   = round((float) $current->tax_total, 2);

        $newCustomers       = (int) ($current->new_customers ?? 0);
        $returningCustomers = (int) ($current->returning_customers ?? 0);
        $newCustomersPrev   = (int) ($previous->new_customers ?? 0);
        // NCR: new customers / (new + returning) × 100. NULLIF guards divide-by-zero (CLAUDE.md §Gotchas).
        $ncrDenom    = $newCustomers + $returningCustomers;
        $ncr         = $ncrDenom > 0 ? round($newCustomers / $ncrDenom * 100, 2) : null;
        $ncrPrevDenom = $newCustomersPrev + (int) ($previous->returning_customers ?? 0);
        $ncrPrev      = $ncrPrevDenom > 0
            ? round($newCustomersPrev / $ncrPrevDenom * 100, 2)
            : null;

        // New-customer revenue: SUM of total_in_reporting_currency for orders that are
        // each customer's first order (identified by MIN(id) per customer_id).
        // Scoped to workspace and only processing/completed orders, then filtered to the
        // current date window so it aligns with the period being reported.
        // NULLIF on ad_spend guards CAC and NCA ROAS divide-by-zero (CLAUDE.md §Gotchas).
        $newCustomerRevenue     = $this->newCustomerRevenue($workspaceId, $from, $to);
        $newCustomerRevenuePrev = $this->newCustomerRevenue($workspaceId, $prevFrom, $prevTo);

        // CAC = ad_spend / new_customers. Null when no new customers or no ad spend.
        $cac     = ($adSpend > 0 && $newCustomers > 0)
            ? round($adSpend / $newCustomers, 2)
            : null;
        $cacPrev = ($adSpendPrev > 0 && $newCustomersPrev > 0)
            ? round($adSpendPrev / $newCustomersPrev, 2)
            : null;

        // NCA ROAS = new_customer_revenue / ad_spend. Null when no ad spend or no revenue.
        $ncaRoas     = ($adSpend > 0 && $newCustomerRevenue !== null)
            ? round($newCustomerRevenue / $adSpend, 4)
            : null;
        $ncaRoasPrev = ($adSpendPrev > 0 && $newCustomerRevenuePrev !== null)
            ? round($newCustomerRevenuePrev / $adSpendPrev, 4)
            : null;

        return [
            'revenue' => [
                'value'     => $revenueActive > 0 ? round($revenueActive, 2) : ($revenueActive === 0.0 && $revenueStore === 0.0 ? null : round($revenueActive, 2)),
                'prev'      => $revenueActivePrev > 0 ? round($revenueActivePrev, 2) : null,
                'sparkline' => $revenueSparkline,
            ],
            'orders' => [
                'value'     => $orders > 0 ? $orders : null,
                'prev'      => $ordersPrev > 0 ? $ordersPrev : null,
                'sparkline' => $ordersSparkline,
            ],
            'not_tracked' => [
                // Can be negative per thesis — don't clamp.
                'value'     => $revenueStore !== 0.0 || $revenueReal !== 0.0 ? $notTracked : null,
                'prev'      => $revenueStorePrev !== 0.0 || $revenueRealPrev !== 0.0 ? $notTrackedPrev : null,
                'sparkline' => $notTrackedSpark,
            ],
            'ad_spend' => [
                'value'     => $adSpend > 0 ? round($adSpend, 2) : null,
                'prev'      => $adSpendPrev > 0 ? round($adSpendPrev, 2) : null,
                'sparkline' => $adSpendSparkline,
            ],
            'mer' => [
                'value'     => $mer,
                'prev'      => $merPrev,
                'sparkline' => $merSparkline,
            ],
            'aov_mean'           => $aov['mean'],
            'aov_median'         => $aov['median'],
            'aov_mode'           => $aov['mode'],
            'units_sold'         => ($items = (int) $current->items_sold) > 0 ? $items : null,
            'cvr'                => $cvr,
            'revenue_ex_vat'     => $revenueStore > 0 ? $revenueExVat : null,
            'shipping_revenue'   => $shippingRev > 0 ? $shippingRev : null,
            'tax_collected'      => $taxCollected > 0 ? $taxCollected : null,
            'new_customers'      => $newCustomers > 0 ? $newCustomers : null,
            'returning_customers' => $returningCustomers > 0 ? $returningCustomers : null,
            'new_customers_prev' => $newCustomersPrev > 0 ? $newCustomersPrev : null,
            // NCR = new_customers / (new + returning) × 100. Null when no customer data.
            'ncr'                => $ncr,
            'ncr_prev'           => $ncrPrev,
            // NCA metrics — new-customer revenue, CAC, and NCA ROAS.
            // @see docs/competitors/_crosscut_metric_dictionary.md §CAC, §NCA_ROAS
            'new_customer_revenue'      => $newCustomerRevenue !== null ? round($newCustomerRevenue, 2) : null,
            'new_customer_revenue_prev' => $newCustomerRevenuePrev !== null ? round($newCustomerRevenuePrev, 2) : null,
            'cac'                       => $cac,
            'cac_prev'                  => $cacPrev,
            'nca_roas'                  => $ncaRoas,
            'nca_roas_prev'             => $ncaRoasPrev,
        ];
    }

    /**
     * Six-source totals for the TrustBar.
     *
     * Thesis: Real is the Nexstage-reconciled total; `not_tracked = store - real`
     * and can exceed 0 on any store. The GA4 bucket aggregates direct + organic +
     * email signals, which GA4 surfaces via session-source attribution. Once the
     * `revenue_ga4_attributed` snapshot column is populated by the SnapshotBuilder
     * (Phase 6), this should read that column directly.
     *
     * @return array<string, float|int|null> Matches Props['trust_bar']
     */
    public function trustBar(int $workspaceId, string $from, string $to): array
    {
        $row = DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('
                COALESCE(SUM(revenue), 0)                     AS store,
                COALESCE(SUM(revenue_facebook_attributed), 0) AS facebook,
                COALESCE(SUM(revenue_google_attributed), 0)   AS google,
                COALESCE(SUM(revenue_gsc_attributed), 0)      AS gsc,
                COALESCE(SUM(revenue_direct_attributed), 0)
                    + COALESCE(SUM(revenue_organic_attributed), 0)
                    + COALESCE(SUM(revenue_email_attributed), 0) AS ga4,
                COALESCE(SUM(revenue_real_attributed), 0)     AS real
            ')
            ->first();

        $store    = round((float) $row->store, 2);
        $real     = round((float) $row->real, 2);
        $facebook = round((float) $row->facebook, 2);
        $google   = round((float) $row->google, 2);
        $gsc      = round((float) $row->gsc, 2);
        $ga4      = round((float) $row->ga4, 2);

        return [
            'store'       => $store,
            'facebook'    => $facebook > 0 ? $facebook : null,
            'google'      => $google > 0 ? $google : null,
            'gsc'         => $gsc > 0 ? $gsc : null,
            'ga4'         => $ga4 > 0 ? $ga4 : null,
            'real'        => $real,
            // Signed — can be negative when platforms over-report.
            'not_tracked' => round($store - $real, 2),
        ];
    }

    /**
     * Daily multi-source revenue series for the LineChart overlay.
     *
     * `is_partial` marks today's row so the chart can render the dotted
     * incomplete-period segment per UX §5.6.
     *
     * When $granularity is 'weekly', rows are bucketed by ISO week start (Monday)
     * using DATE_TRUNC('week', date). The `date` field then holds the Monday of
     * each week so the LineChart X-axis formats it correctly via formatDate().
     * The `is_partial` flag marks the current week when granularity is weekly.
     *
     * @param  string $granularity 'daily' | 'weekly'
     * @return list<array<string, mixed>> Matches Props['revenue_chart']
     */
    public function revenueChart(int $workspaceId, string $from, string $to, string $granularity = 'daily'): array
    {
        $today = now()->toDateString();

        if ($granularity === 'weekly') {
            // Bucket by ISO week start (Monday). DATE_TRUNC('week', date) returns
            // the Monday of the week containing each daily_snapshot date.
            $rows = DailySnapshot::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->whereBetween('date', [$from, $to])
                ->selectRaw("
                    DATE_TRUNC('week', date)::date::text AS date,
                    COALESCE(SUM(revenue), 0)                     AS store,
                    COALESCE(SUM(revenue_facebook_attributed), 0) AS facebook,
                    COALESCE(SUM(revenue_google_attributed), 0)   AS google,
                    COALESCE(SUM(revenue_gsc_attributed), 0)      AS gsc,
                    COALESCE(SUM(revenue_real_attributed), 0)     AS real,
                    MAX(date)::text                               AS week_end
                ")
                ->groupByRaw("DATE_TRUNC('week', date)")
                ->orderByRaw("DATE_TRUNC('week', date)")
                ->get();

            $currentWeekStart = now()->startOfWeek()->toDateString();

            return $rows->map(function ($r) use ($currentWeekStart): array {
                $fb  = (float) $r->facebook;
                $gg  = (float) $r->google;
                $gsc = (float) $r->gsc;
                return [
                    'date'       => $r->date,
                    'store'      => round((float) $r->store, 2),
                    'facebook'   => $fb  > 0 ? round($fb,  2) : null,
                    'google'     => $gg  > 0 ? round($gg,  2) : null,
                    'gsc'        => $gsc > 0 ? round($gsc, 2) : null,
                    'real'       => round((float) $r->real, 2),
                    'is_partial' => $r->date === $currentWeekStart,
                ];
            })->all();
        }

        // Default: daily granularity
        $rows = DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('
                date::text AS date,
                COALESCE(SUM(revenue), 0)                     AS store,
                COALESCE(SUM(revenue_facebook_attributed), 0) AS facebook,
                COALESCE(SUM(revenue_google_attributed), 0)   AS google,
                COALESCE(SUM(revenue_gsc_attributed), 0)      AS gsc,
                COALESCE(SUM(revenue_real_attributed), 0)     AS real
            ')
            ->groupByRaw('date')
            ->orderBy('date')
            ->get();

        return $rows->map(function ($r) use ($today): array {
            $fb  = (float) $r->facebook;
            $gg  = (float) $r->google;
            $gsc = (float) $r->gsc;
            return [
                'date'       => $r->date,
                'store'      => round((float) $r->store, 2),
                'facebook'   => $fb  > 0 ? round($fb,  2) : null,
                'google'     => $gg  > 0 ? round($gg,  2) : null,
                'gsc'        => $gsc > 0 ? round($gsc, 2) : null,
                'real'       => round((float) $r->real, 2),
                'is_partial' => $r->date === $today,
            ];
        })->all();
    }

    /**
     * Top 8 channels by reconciled revenue + "Other" bucket for overflow.
     *
     * Source of truth is per-source attributed revenue on daily_snapshots
     * (6 channels: Facebook, Google, Search, Direct, Organic, Email). No
     * raw orders touched — page render stays snapshot-backed.
     *
     * @return list<array{channel:string, revenue:float}>
     */
    public function channelRevenue(int $workspaceId, string $from, string $to): array
    {
        $row = DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('
                COALESCE(SUM(revenue_facebook_attributed), 0) AS facebook,
                COALESCE(SUM(revenue_google_attributed), 0)   AS google,
                COALESCE(SUM(revenue_gsc_attributed), 0)      AS search,
                COALESCE(SUM(revenue_direct_attributed), 0)   AS direct,
                COALESCE(SUM(revenue_organic_attributed), 0)  AS organic,
                COALESCE(SUM(revenue_email_attributed), 0)    AS email
            ')
            ->first();

        $channels = collect([
            ['channel' => 'Facebook',         'revenue' => round((float) $row->facebook, 2)],
            ['channel' => 'Google Ads',        'revenue' => round((float) $row->google,   2)],
            ['channel' => 'Organic Search',    'revenue' => round((float) $row->search,   2)],
            ['channel' => 'Direct',            'revenue' => round((float) $row->direct,   2)],
            ['channel' => 'Social & Referral', 'revenue' => round((float) $row->organic,  2)],
            ['channel' => 'Email',             'revenue' => round((float) $row->email,    2)],
        ])->filter(fn (array $c) => $c['revenue'] > 0)
          ->sortByDesc('revenue')
          ->values();

        // Contract allows up to 8; we only have 6 natively. "Other" bucket stays
        // a no-op until more channels are sourced — but keep the bucketing shape.
        if ($channels->count() > 8) {
            $top   = $channels->take(8);
            $other = $channels->slice(8)->sum('revenue');
            return $top->push(['channel' => 'Other', 'revenue' => round((float) $other, 2)])->all();
        }

        return $channels->all();
    }

    /**
     * Ad spend grouped by platform (campaign level only — never mix levels per CLAUDE.md).
     *
     * @return list<array{platform:string, spend:float}>
     */
    public function platformSpend(int $workspaceId, string $from, string $to): array
    {
        $rows = AdInsight::withoutGlobalScopes()
            ->where('ad_insights.workspace_id', $workspaceId)
            ->where('ad_insights.level', 'campaign')
            ->whereBetween('ad_insights.date', [$from, $to])
            ->whereNull('ad_insights.hour')
            ->join('ad_accounts', 'ad_accounts.id', '=', 'ad_insights.ad_account_id')
            ->groupBy('ad_accounts.platform')
            ->orderByRaw('SUM(ad_insights.spend_in_reporting_currency) DESC')
            ->selectRaw('
                ad_accounts.platform AS platform,
                COALESCE(SUM(ad_insights.spend_in_reporting_currency), 0) AS spend
            ')
            ->get();

        return $rows->map(fn ($r) => [
            'platform' => ucfirst((string) $r->platform),
            'spend'    => round((float) $r->spend, 2),
        ])->all();
    }

    /**
     * Open triage items, collapsed to the TSX severity enum.
     *
     * Schema severity: info|warning|high|critical.
     * TSX severity:    info|warning|critical — `high` collapses to `critical`.
     *
     * @return list<array<string, mixed>>
     */
    public function triageItems(int $workspaceId): array
    {
        $items = TriageInboxItem::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<', now());
            })
            ->orderByRaw("CASE severity
                WHEN 'critical' THEN 1
                WHEN 'high'     THEN 2
                WHEN 'warning'  THEN 3
                ELSE 4 END")
            ->orderByDesc('created_at')
            ->limit(self::TRIAGE_LIMIT)
            ->get(['id', 'itemable_type', 'title', 'context_text', 'severity', 'created_at',
                   'primary_action_label', 'deep_link_url']);

        return $items->map(function (TriageInboxItem $i): array {
            $severity = match ($i->severity) {
                'critical', 'high' => 'critical',
                'warning'          => 'warning',
                default            => 'info',
            };

            $type = $i->itemable_type
                ? class_basename($i->itemable_type)
                : 'System';

            $message = $i->title
                ?? $i->context_text
                ?? 'Attention required';

            return [
                'id'           => (int) $i->id,
                'type'         => $type,
                'message'      => $message,
                'severity'     => $severity,
                'created_at'   => $i->created_at->toISOString(),
                // action_href / action_label enable per-item CTA buttons in Dashboard.tsx TriageInbox.
                'action_href'  => $i->deep_link_url ?? null,
                'action_label' => $i->primary_action_label ?? null,
            ];
        })->all();
    }

    /**
     * True when any historical import job for this workspace is actively running or queued.
     *
     * Reads: historical_import_jobs (status IN ('pending','running'))
     * Used by: DashboardController → `backfilling` prop → Dashboard.tsx BackfillBanner.
     */
    public function backfillingStatus(int $workspaceId): bool
    {
        return HistoricalImportJob::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereIn('status', ['pending', 'running'])
            ->exists();
    }

    /**
     * User-pinned metric labels for the Dashboard PinnedRow.
     *
     * Reads from users.view_preferences['dashboard_pinned_metrics']; max 8.
     * Returns [] when nothing pinned.
     *
     * Accepts the already-hydrated User model to avoid an extra DB round-trip;
     * the controller passes $request->user() directly.
     *
     * @param  \App\Models\User|null  $user  Already-hydrated from the request.
     * @return list<string>
     */
    public function pinnedMetrics(?\App\Models\User $user): array
    {
        if ($user === null) {
            return [];
        }

        $prefs  = $user->view_preferences ?? [];
        $pinned = $prefs['dashboard_pinned_metrics'] ?? [];

        if (! is_array($pinned)) {
            return [];
        }

        return array_slice(array_values(array_filter(array_map(
            fn ($v) => is_string($v) ? $v : null,
            $pinned,
        ))), 0, 8);
    }

    /**
     * Today's intra-day + yesterday-to-same-time-of-day snapshot.
     *
     * Preferred source: hourly_snapshots (cheap, pre-aggregated). Falls back to a
     * lightweight orders read ONLY when no hourly row exists yet today AND the
     * user is viewing a range that includes today. Returns null when nothing
     * has landed for today yet — TSX renders the empty state.
     *
     * `hourly_bars` — one entry per hour 0..currentHour for the mini sparkline.
     * `projected_revenue` / `projected_orders` — linear pace extrapolation to EOD.
     *   Formula: (current / hoursElapsed) × 24. Null when currentHour < 2 (too early
     *   to project, avoids misleading 100× inflations at midnight).
     *
     * @return array{
     *   revenue: float, orders: int,
     *   revenue_yesterday: float, orders_yesterday: int,
     *   revenue_weekday_avg: float, orders_weekday_avg: int,
     *   weekday_name: string,
     *   projected_revenue: float|null, projected_orders: int|null,
     *   hourly_bars: list<array{hour:int, revenue:float, orders:int}>
     * }|null
     */
    public function todaySoFar(int $workspaceId): ?array
    {
        $tz = CarbonImmutable::now();
        $today = $tz->toDateString();
        $yesterday = $tz->subDay()->toDateString();
        $currentHour = (int) $tz->format('H');

        // Same weekday average over the past 4 occurrences, same hours up to now.
        // Gives a more meaningful comparison than yesterday alone (e.g. Mondays are
        // consistently slower than Fridays). @see docs/UX.md#TodaySoFar
        $weekdayDates = collect(range(1, 4))
            ->map(fn ($w) => $tz->subWeeks($w)->toDateString())
            ->toArray();

        $weekdayRows = HourlySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereIn('date', $weekdayDates)
            ->where('hour', '<=', $currentHour)
            ->selectRaw('date, COALESCE(SUM(revenue), 0) AS day_rev, COALESCE(SUM(orders_count), 0) AS day_orders')
            ->groupBy('date')
            ->get();

        $weekdayRevAvg    = $weekdayRows->isNotEmpty() ? round($weekdayRows->avg('day_rev'), 2) : 0.0;
        $weekdayOrdersAvg = $weekdayRows->isNotEmpty() ? (int) round($weekdayRows->avg('day_orders')) : 0;

        // Per-hour rows for today — used for the mini sparkline chart.
        $todayHourlyRows = HourlySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('date', $today)
            ->orderBy('hour')
            ->get(['hour', 'revenue', 'orders_count']);

        $hourlyBars = $todayHourlyRows->map(fn ($r) => [
            'hour'    => (int) $r->hour,
            'revenue' => round((float) ($r->revenue ?? 0), 2),
            'orders'  => (int) ($r->orders_count ?? 0),
        ])->values()->all();

        $todayRev    = (float) array_sum(array_column($hourlyBars, 'revenue'));
        $todayOrders = (int) array_sum(array_column($hourlyBars, 'orders'));

        // Yesterday up to the same hour for apples-to-apples comparison.
        $yesterdayRow = HourlySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('date', $yesterday)
            ->where('hour', '<=', $currentHour)
            ->selectRaw('
                COALESCE(SUM(revenue), 0)      AS revenue,
                COALESCE(SUM(orders_count), 0) AS orders
            ')
            ->first();

        $yRev    = (float) ($yesterdayRow->revenue ?? 0);
        $yOrders = (int)   ($yesterdayRow->orders  ?? 0);

        // When no hourly rows exist yet today, fall back to a direct orders read
        // scoped to today only (not a range aggregate) — the single exception allowed.
        if ($todayRev === 0.0 && $todayOrders === 0) {
            $liveToday = Order::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->whereIn('status', ['completed', 'processing'])
                ->whereBetween('occurred_at', [$today . ' 00:00:00', $today . ' 23:59:59'])
                ->selectRaw('
                    COALESCE(SUM(COALESCE(total_in_reporting_currency, total)), 0) AS revenue,
                    COUNT(*) AS orders
                ')
                ->first();
            $todayRev    = (float) ($liveToday->revenue ?? 0);
            $todayOrders = (int)   ($liveToday->orders  ?? 0);
        }

        if ($todayRev === 0.0 && $todayOrders === 0 && $yRev === 0.0 && $yOrders === 0) {
            return null;
        }

        // EOD projection: pace × 24 hours. Guard: only project after hour 2 so
        // a single midnight order doesn't show a 10 000 % projected day.
        // hoursElapsed uses currentHour + 1 so hour-0 gives a non-zero denominator.
        $hoursElapsed     = $currentHour + 1;
        $projectedRevenue = null;
        $projectedOrders  = null;
        if ($currentHour >= 2 && $todayRev > 0) {
            $projectedRevenue = round($todayRev / $hoursElapsed * 24, 2);
            $projectedOrders  = (int) round($todayOrders / $hoursElapsed * 24);
        }

        return [
            'revenue'              => round($todayRev, 2),
            'orders'               => $todayOrders,
            'revenue_yesterday'    => round($yRev, 2),
            'orders_yesterday'     => $yOrders,
            'revenue_weekday_avg'  => $weekdayRevAvg,
            'orders_weekday_avg'   => $weekdayOrdersAvg,
            'weekday_name'         => $tz->format('D'), // e.g. "Mon"
            'projected_revenue'    => $projectedRevenue,
            'projected_orders'     => $projectedOrders,
            'hourly_bars'          => $hourlyBars,
        ];
    }

    /**
     * Conversion funnel stages for the Dashboard FunnelChart widget.
     *
     * Stages:
     *   1. Sessions     — from daily_snapshots.sessions (sum for the range)
     *   2. Product Views — from ga4_product_page_views (sum of item_views for the range),
     *                      populated by SyncGA4ProductViewsJob via GA4 enhanced ecommerce.
     *                      Null when the workspace has no GA4 product view data yet.
     *   3. Purchases    — from daily_snapshots.orders_count (sum for the range)
     *
     * Returns null when sessions are zero/unavailable (widget is hidden by TSX).
     * Drop-off is ((prev − current) / prev) × 100, clamped to ≥ 0.
     *
     * @return list<array{label:string, value:int|null, drop_off_pct:float|null}>|null
     * @see resources/js/Components/charts/FunnelChart.tsx
     */
    public function funnel(int $workspaceId, string $from, string $to): ?array
    {
        $agg = $this->aggregateDaily($workspaceId, $from, $to);

        $sessions  = (int) ($agg->sessions   ?? 0);
        $purchases = (int) ($agg->orders_count ?? 0);

        // Skip the widget entirely when there are no sessions to anchor the funnel.
        if ($sessions === 0) {
            return null;
        }

        // Product views from GA4 enhanced ecommerce item_views events.
        // Null when the workspace has no rows in ga4_product_page_views (GA4 not connected
        // or enhanced ecommerce not set up) so the stage renders as "N/A" in the funnel.
        $hasProductViews = Ga4ProductPageView::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->exists();

        $productViews = null;
        if ($hasProductViews) {
            $productViews = (int) Ga4ProductPageView::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->whereBetween('date', [$from, $to])
                ->sum('item_views');
        }

        // ── Drop-off calculations ─────────────────────────────────────────────

        $dropOffToProductViews = null;
        if ($productViews !== null && $sessions > 0) {
            $dropOffToProductViews = round(max(0, ($sessions - $productViews) / $sessions * 100), 1);
        }

        $prevForPurchases = $productViews ?? $sessions;
        $dropOffToPurchases = null;
        if ($prevForPurchases > 0) {
            $dropOffToPurchases = round(max(0, ($prevForPurchases - $purchases) / $prevForPurchases * 100), 1);
        }

        // ── Assemble stages ───────────────────────────────────────────────────

        $stages = [
            [
                'label'        => 'Sessions',
                'value'        => $sessions,
                'drop_off_pct' => null,
            ],
        ];

        $stages[] = [
            'label'        => 'Product Views',
            'value'        => $productViews,
            'drop_off_pct' => $dropOffToProductViews,
        ];

        $stages[] = [
            'label'        => 'Purchases',
            'value'        => $purchases > 0 ? $purchases : null,
            'drop_off_pct' => $dropOffToPurchases,
        ];

        return $stages;
    }

    // ─── private helpers ───────────────────────────────────────────────────

    /**
     * Revenue from first-time buyers (new customers) in the given date window.
     *
     * Reads from daily_snapshots.new_customer_revenue (written by SnapshotBuilderService)
     * instead of aggregating raw orders on the request path, per CLAUDE.md §Gotchas.
     *
     * Falls back to null (not 0) when no snapshot rows exist for the window, so the
     * caller can distinguish "no data" from "genuinely zero new-customer revenue".
     *
     * Called by: metrics() for both current and previous windows.
     * Reads: daily_snapshots.new_customer_revenue
     *
     * @see app/Services/Snapshots/SnapshotBuilderService.php  buildDaily()
     */
    private function newCustomerRevenue(int $workspaceId, string $from, string $to): ?float
    {
        $row = DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('SUM(new_customer_revenue) AS total')
            ->first();

        // Return null when no snapshot rows exist (distinguishes "no data" from "zero revenue").
        if ($row === null || $row->total === null) {
            return null;
        }

        return (float) $row->total;
    }

    /**
     * Aggregate daily_snapshots for the range — single row summary. Used for
     * both the current and previous windows. NULLIFs live in the caller
     * (we only return raw sums here).
     */
    private function aggregateDaily(int $workspaceId, string $from, string $to): object
    {
        return DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('
                COALESCE(SUM(revenue), 0)                 AS revenue,
                COALESCE(SUM(revenue_real_attributed), 0) AS revenue_real,
                COALESCE(SUM(orders_count), 0)            AS orders_count,
                COALESCE(SUM(items_sold), 0)              AS items_sold,
                COALESCE(SUM(tax_total), 0)               AS tax_total,
                COALESCE(SUM(shipping_revenue_total), 0)  AS shipping_revenue_total,
                COALESCE(SUM(sessions), 0)                AS sessions,
                COALESCE(SUM(new_customers), 0)           AS new_customers,
                COALESCE(SUM(returning_customers), 0)     AS returning_customers
            ')
            ->first() ?? (object) [
                'revenue' => 0, 'revenue_real' => 0, 'orders_count' => 0,
                'items_sold' => 0, 'tax_total' => 0, 'shipping_revenue_total' => 0,
                'sessions' => 0, 'new_customers' => 0, 'returning_customers' => 0,
            ];
    }

    /**
     * Aggregate a single named column from daily_snapshots for source-lens revenue.
     *
     * Used when `$source !== 'real'` in metrics() to compute the lens-specific
     * revenue total without re-running the full aggregateDaily() query.
     * Column is validated through MetricSourceResolver::columnFor() before calling,
     * so injection is not possible — but we still use raw SQL for performance
     * (Eloquent selectRaw with a trusted interpolated column name is safe here).
     */
    private function aggregateDailyColumn(int $workspaceId, string $from, string $to, string $column): object
    {
        return DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw("COALESCE(SUM({$column}), 0) AS v")
            ->first() ?? (object) ['v' => 0];
    }

    /** Campaign-level ad spend in reporting currency for the range. */
    private function aggregateAdSpend(int $workspaceId, string $from, string $to): float
    {
        $row = AdInsight::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('level', 'campaign')
            ->whereBetween('date', [$from, $to])
            ->whereNull('hour')
            ->selectRaw('COALESCE(SUM(spend_in_reporting_currency), 0) AS spend')
            ->first();
        return (float) ($row->spend ?? 0);
    }

    /**
     * Daily series for a named numeric column, capped to SPARKLINE_MAX_POINTS.
     *
     * @return list<float>
     */
    private function dailySparkline(int $workspaceId, string $from, string $to, string $column): array
    {
        $rows = DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw("date::text AS date, COALESCE(SUM({$column}), 0) AS v")
            ->groupByRaw('date')
            ->orderBy('date')
            ->limit(self::SPARKLINE_MAX_POINTS)
            ->pluck('v')
            ->map(fn ($v) => round((float) $v, 2))
            ->all();
        return $rows;
    }

    /**
     * Daily Not Tracked sparkline: store − real per day. Can be negative.
     *
     * @return list<float>
     */
    private function notTrackedSparkline(int $workspaceId, string $from, string $to): array
    {
        $rows = DailySnapshot::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('
                date::text AS date,
                COALESCE(SUM(revenue), 0) - COALESCE(SUM(revenue_real_attributed), 0) AS v
            ')
            ->groupByRaw('date')
            ->orderBy('date')
            ->limit(self::SPARKLINE_MAX_POINTS)
            ->pluck('v')
            ->map(fn ($v) => round((float) $v, 2))
            ->all();
        return $rows;
    }

    /**
     * Daily ad-spend sparkline (campaign level, reporting currency).
     *
     * @return list<float>
     */
    private function adSpendSparkline(int $workspaceId, string $from, string $to): array
    {
        $rows = AdInsight::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('level', 'campaign')
            ->whereBetween('date', [$from, $to])
            ->whereNull('hour')
            ->selectRaw('date::text AS date, COALESCE(SUM(spend_in_reporting_currency), 0) AS v')
            ->groupByRaw('date')
            ->orderBy('date')
            ->limit(self::SPARKLINE_MAX_POINTS)
            ->pluck('v')
            ->map(fn ($v) => round((float) $v, 2))
            ->all();
        return $rows;
    }

    /**
     * Daily MER sparkline: revenue / ad_spend per day (NULL for zero-spend days
     * are coerced to 0 so the TSX sparkline has a continuous series to draw).
     *
     * @return list<float>
     */
    private function merSparkline(int $workspaceId, string $from, string $to): array
    {
        $rows = DB::select('
            SELECT d.date::text AS date,
                   CASE WHEN COALESCE(a.spend, 0) > 0
                        THEN COALESCE(d.revenue, 0) / a.spend
                        ELSE 0 END AS v
            FROM (
                SELECT date, SUM(revenue) AS revenue
                FROM daily_snapshots
                WHERE workspace_id = ? AND date BETWEEN ? AND ?
                GROUP BY date
            ) d
            LEFT JOIN (
                SELECT date, SUM(spend_in_reporting_currency) AS spend
                FROM ad_insights
                WHERE workspace_id = ? AND level = ? AND hour IS NULL AND date BETWEEN ? AND ?
                GROUP BY date
            ) a ON a.date = d.date
            ORDER BY d.date
            LIMIT ?
        ', [
            $workspaceId, $from, $to,
            $workspaceId, 'campaign', $from, $to,
            self::SPARKLINE_MAX_POINTS,
        ]);

        return array_map(fn ($r) => round((float) $r->v, 4), $rows);
    }

    /**
     * Mean / Median / Mode of AOV across daily_snapshots for the range.
     *
     * Median uses PERCENTILE_CONT(0.5); mode uses the most-frequent AOV bucketed
     * to 2 decimal places. Null when there are no non-null AOV rows.
     *
     * @return array{mean:float|null, median:float|null, mode:float|null}
     */
    private function aovStats(int $workspaceId, string $from, string $to): array
    {
        // Single query: AVG, PERCENTILE_CONT, and MODE via a scalar subquery.
        // Previously two round-trips to daily_snapshots; merged to one.
        $row = DB::selectOne('
            SELECT
                AVG(revenue / NULLIF(orders_count, 0))                                          AS mean,
                PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY revenue / NULLIF(orders_count, 0)) AS median,
                (
                    SELECT ROUND((revenue / NULLIF(orders_count, 0))::numeric, 2)
                    FROM daily_snapshots
                    WHERE workspace_id = $1
                      AND date BETWEEN $2 AND $3
                      AND orders_count > 0
                    GROUP BY ROUND((revenue / NULLIF(orders_count, 0))::numeric, 2)
                    ORDER BY COUNT(*) DESC, ROUND((revenue / NULLIF(orders_count, 0))::numeric, 2) DESC
                    LIMIT 1
                ) AS mode
            FROM daily_snapshots
            WHERE workspace_id = $1
              AND date BETWEEN $2 AND $3
              AND orders_count > 0
        ', [$workspaceId, $from, $to]);

        return [
            'mean'   => $row?->mean   !== null ? round((float) $row->mean,   2) : null,
            'median' => $row?->median !== null ? round((float) $row->median, 2) : null,
            'mode'   => $row?->mode   !== null ? round((float) $row->mode,   2) : null,
        ];
    }

    /** Inclusive date-count between two Y-m-d strings. */
    private function dayCount(string $from, string $to): int
    {
        return (int) Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
    }

    /**
     * Immediately-preceding window of the same length.
     *
     * @return array{0:string, 1:string}
     */
    private function previousRange(string $from, string $to): array
    {
        $len    = $this->dayCount($from, $to);
        $prevTo = Carbon::parse($from)->subDay()->toDateString();
        $prevFrom = Carbon::parse($prevTo)->subDays($len - 1)->toDateString();
        return [$prevFrom, $prevTo];
    }
}
