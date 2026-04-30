<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\DailyDigestMail;
use App\Models\DigestSchedule;
use App\Models\TriageInboxItem;
use App\Models\Workspace;
use App\Scopes\WorkspaceScope;
use App\Services\NarrativeTemplateService;
use App\Services\WorkspaceContext;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Deliver a scheduled email digest for a single workspace + user.
 *
 * Constructor takes ($workspaceId, $userId). Dispatched either:
 *   - by the hourly schedule entries in routes/console.php that read
 *     digest_schedules (frequency, send_at_hour, day_of_week), or
 *   - synchronously via the "Send test email" endpoint
 *     (POST /{workspace}/settings/notifications/digest/test).
 *
 * Data is fetched from daily_snapshots (last 7 days vs prior 7 days) and
 * triage_inbox_items (top 3 open items). Reuses DailyDigestMail + its Blade
 * templates so rendering is consistent between the daily scheduler and this job.
 *
 * WorkspaceScope guard: MUST call app(WorkspaceContext::class)->set() first — see
 * CLAUDE.md §Gotchas. This job does NOT rely on WorkspaceScope; all queries
 * use explicit workspace_id filters (withoutGlobalScopes + where).
 *
 * Queue:    default
 * Timeout:  60 s
 * Tries:    3
 *
 * Reads:    workspaces, users, daily_snapshots, ad_insights, triage_inbox_items
 * Writes:   mail queue
 *
 * @see App\Mail\DailyDigestMail
 * @see routes/console.php  (deliver-daily-digests, deliver-weekly-digests)
 * @see docs/planning/backend.md §3.3 (job spec)
 */
class DeliverDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries   = 3;

    public function __construct(
        public readonly int $workspaceId,
        public readonly int $userId,
    ) {
        $this->onQueue('default');
    }

    public function handle(NarrativeTemplateService $narrativeService): void
    {
        // CRITICAL: WorkspaceScope throws if WorkspaceContext::id() is null in jobs.
        // Set it here even though all queries below use withoutGlobalScopes() —
        // any injected service that calls WorkspaceContext::id() will be guarded.
        app(WorkspaceContext::class)->set($this->workspaceId);

        $workspace = Workspace::withoutGlobalScope(WorkspaceScope::class)
            ->find($this->workspaceId);

        if ($workspace === null) {
            return;
        }

        $user = \App\Models\User::find($this->userId);

        if ($user === null || $user->email_verified_at === null) {
            return;
        }

        $tz        = $workspace->reporting_timezone ?: 'UTC';
        $endDate   = Carbon::yesterday($tz)->toDateString();
        $startDate = Carbon::yesterday($tz)->subDays(6)->toDateString();

        // Build KPI rows for current period and compute % change vs prior 7 days.
        $heroMetrics    = $this->computeHeroMetrics($this->workspaceId, $startDate, $endDate, $tz);
        $attentionItems = $this->computeAttentionItems($this->workspaceId, $workspace->slug);

        $hasGsc = \App\Models\SearchConsoleProperty::withoutGlobalScopes()
            ->where('workspace_id', $this->workspaceId)
            ->exists();

        $narrative = $narrativeService->forDashboard(
            revenue:         $heroMetrics['revenue'] > 0 ? $heroMetrics['revenue'] : null,
            compareRevenue:  null,
            comparisonLabel: null,
            roas:            $heroMetrics['roas'],
            hasAds:          (bool) $workspace->has_ads,
            hasGsc:          $hasGsc,
        );

        Mail::to($user->email)->queue(new DailyDigestMail(
            workspace:      $workspace,
            narrative:      $narrative,
            heroMetrics:    $heroMetrics,
            attentionItems: $attentionItems,
            isWeekly:       true, // always covers last 7 days
            startDate:      $startDate,
            endDate:        $endDate,
        ));

        Log::info('DeliverDigestJob: digest queued', [
            'workspace_id' => $this->workspaceId,
            'user_id'      => $this->userId,
            'date_range'   => "{$startDate} → {$endDate}",
        ]);
    }

    /**
     * KPIs from daily_snapshots for the current 7-day window and the prior
     * 7-day window. ROAS = revenue / ad_spend; NULLIF guards divide-by-zero.
     * new_customers is summed from daily_snapshots.new_customers.
     *
     * All queries are explicit workspace_id-filtered — WorkspaceScope unavailable.
     * Never aggregates raw orders (CLAUDE.md §Gotchas).
     *
     * @return array{
     *   revenue: float, orders: int, ad_spend: float, roas: float|null,
     *   new_customers: int,
     *   change: array{revenue: float|null, orders: float|null, ad_spend: float|null, roas: float|null, new_customers: float|null}
     * }
     */
    private function computeHeroMetrics(
        int    $workspaceId,
        string $startDate,
        string $endDate,
        string $tz,
    ): array {
        $priorEnd   = Carbon::parse($startDate, $tz)->subDay()->toDateString();
        $priorStart = Carbon::parse($priorEnd, $tz)->subDays(6)->toDateString();

        $currentSnap = DB::table('daily_snapshots')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('
                COALESCE(SUM(revenue), 0)       AS revenue,
                COALESCE(SUM(orders_count), 0)  AS orders,
                COALESCE(SUM(new_customers), 0) AS new_customers
            ')
            ->first();

        $priorSnap = DB::table('daily_snapshots')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$priorStart, $priorEnd])
            ->selectRaw('
                COALESCE(SUM(revenue), 0)       AS revenue,
                COALESCE(SUM(orders_count), 0)  AS orders,
                COALESCE(SUM(new_customers), 0) AS new_customers
            ')
            ->first();

        $currentSpend = (float) DB::table('ad_insights')
            ->join('ad_accounts', 'ad_insights.ad_account_id', '=', 'ad_accounts.id')
            ->where('ad_accounts.workspace_id', $workspaceId)
            ->where('ad_insights.level', 'campaign')
            ->whereNull('ad_insights.hour')
            ->whereBetween('ad_insights.date', [$startDate, $endDate])
            ->sum('ad_insights.spend');

        $priorSpend = (float) DB::table('ad_insights')
            ->join('ad_accounts', 'ad_insights.ad_account_id', '=', 'ad_accounts.id')
            ->where('ad_accounts.workspace_id', $workspaceId)
            ->where('ad_insights.level', 'campaign')
            ->whereNull('ad_insights.hour')
            ->whereBetween('ad_insights.date', [$priorStart, $priorEnd])
            ->sum('ad_insights.spend');

        $revenue      = (float) ($currentSnap->revenue      ?? 0);
        $orders       = (int)   ($currentSnap->orders       ?? 0);
        $newCustomers = (int)   ($currentSnap->new_customers ?? 0);
        // NULLIF equivalent: only compute ROAS when spend > 0.
        $roas = $currentSpend > 0 ? round($revenue / $currentSpend, 2) : null;

        $priorRevenue      = (float) ($priorSnap->revenue      ?? 0);
        $priorOrders       = (int)   ($priorSnap->orders       ?? 0);
        $priorNewCustomers = (int)   ($priorSnap->new_customers ?? 0);
        $priorRoas = $priorSpend > 0 ? round($priorRevenue / $priorSpend, 2) : null;

        return [
            'revenue'       => $revenue,
            'orders'        => $orders,
            'ad_spend'      => $currentSpend,
            'roas'          => $roas,
            'new_customers' => $newCustomers,
            'change'        => [
                'revenue'       => $this->pctChange($priorRevenue, $revenue),
                'orders'        => $this->pctChange((float) $priorOrders, (float) $orders),
                'ad_spend'      => $this->pctChange($priorSpend, $currentSpend),
                'roas'          => ($priorRoas !== null && $roas !== null) ? $this->pctChange($priorRoas, $roas) : null,
                'new_customers' => $this->pctChange((float) $priorNewCustomers, (float) $newCustomers),
            ],
        ];
    }

    /**
     * Returns percentage change from $old to $new, or null when $old = 0.
     * Result is a signed float (e.g. 12.5 means +12.5 %).
     */
    private function pctChange(float $old, float $new): ?float
    {
        if ($old == 0) {
            return null;
        }

        return round((($new - $old) / $old) * 100, 1);
    }

    /**
     * Top 3 open triage_inbox_items, severity-ordered, with absolute URLs for email links.
     *
     * Replicates the pattern from SendDailyDigestJob::computeAttentionItems().
     * Uses withoutGlobalScopes() because WorkspaceScope is request-bound.
     *
     * @return array<int, array{text: string, href: string, severity: string}>
     */
    private function computeAttentionItems(int $workspaceId, string $workspaceSlug): array
    {
        $items = [];

        $triageItems = TriageInboxItem::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('status', 'open')
            ->where(function ($q): void {
                $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<', now());
            })
            ->whereNotIn('severity', ['info'])
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'warning' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['title', 'deep_link_url', 'severity']);

        foreach ($triageItems as $item) {
            $rawHref = $item->deep_link_url ?: '/manage/integrations';
            $href    = str_starts_with($rawHref, 'http')
                ? $rawHref
                : url("/{$workspaceSlug}" . $rawHref);

            $items[] = [
                'text'     => $item->title ?? 'Attention required',
                'href'     => $href,
                'severity' => in_array($item->severity, ['critical', 'high'], true) ? 'critical' : 'warning',
            ];
        }

        return $items;
    }
}
