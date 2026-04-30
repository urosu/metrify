<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailySnapshot;
use App\Models\IntegrationRun;
use App\Models\Order;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin overview / SaaS health dashboard.
 *
 * Purpose: Renders the top-level admin overview with workspace/user/store counts,
 *          MRR/ARR/ARPA estimates, plan breakdown, and API quota telemetry.
 *
 * Reads:  workspaces, users, stores, orders, integration_runs, daily_snapshots,
 *         config/billing.php, Redis (API quota cache keys via getApiQuotas()).
 * Writes: nothing.
 * Callers: routes/web.php admin group (GET /admin/overview).
 *
 * @see docs/planning/backend.md#6
 */
class AdminOverviewController extends Controller
{
    public function __invoke(): Response
    {
        $now = now();

        // Workspace counts — single aggregate query instead of 6 individual counts.
        $wsCounts = DB::selectOne("
            SELECT
                COUNT(*) FILTER (WHERE deleted_at IS NULL)                                           AS total,
                COUNT(*) FILTER (WHERE deleted_at IS NULL AND billing_plan IS NULL AND trial_ends_at > NOW()) AS trial_active,
                COUNT(*) FILTER (WHERE deleted_at IS NULL AND billing_plan IS NULL AND trial_ends_at <= NOW()) AS trial_expired,
                COUNT(*) FILTER (WHERE deleted_at IS NULL AND billing_plan IS NOT NULL)              AS paying,
                COUNT(*) FILTER (WHERE deleted_at IS NOT NULL)                                       AS soft_deleted,
                COUNT(*) FILTER (WHERE deleted_at IS NULL AND created_at >= date_trunc('month', NOW())) AS new_month
            FROM workspaces
        ");

        $workspaceTotal = (int) $wsCounts->total;
        $trialActive    = (int) $wsCounts->trial_active;
        $trialExpired   = (int) $wsCounts->trial_expired;
        $paying         = (int) $wsCounts->paying;
        $softDeleted    = (int) $wsCounts->soft_deleted;
        $newThisMonth   = (int) $wsCounts->new_month;

        // Plan breakdown
        $planBreakdown = Workspace::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereNotNull('billing_plan')
            ->selectRaw('billing_plan, count(*) as count')
            ->groupBy('billing_plan')
            ->pluck('count', 'billing_plan');

        // Users — single aggregate query.
        $userCounts = DB::selectOne("
            SELECT
                COUNT(*)                                                        AS total,
                COUNT(*) FILTER (WHERE is_super_admin)                         AS super_admins,
                COUNT(*) FILTER (WHERE created_at >= date_trunc('month', NOW())) AS new_month
            FROM users
        ");

        $userTotal     = (int) $userCounts->total;
        $superAdmins   = (int) $userCounts->super_admins;
        $newUsersMonth = (int) $userCounts->new_month;

        // Stores — single aggregate query.
        $storeCounts = DB::selectOne("
            SELECT
                COUNT(*)                                       AS total,
                COUNT(*) FILTER (WHERE status = 'active')     AS active,
                COUNT(*) FILTER (WHERE status = 'error')      AS error,
                COUNT(*) FILTER (WHERE status = 'connecting') AS connecting
            FROM stores
        ");

        $storeTotal      = (int) $storeCounts->total;
        $storeActive     = (int) $storeCounts->active;
        $storeError      = (int) $storeCounts->error;
        $storeConnecting = (int) $storeCounts->connecting;

        // Orders this month
        $ordersThisMonth = Order::withoutGlobalScopes()
            ->whereBetween('occurred_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->count();

        // Failed syncs last 24 h
        $failedSyncsDay = IntegrationRun::withoutGlobalScopes()
            ->where('status', 'failed')
            ->where('created_at', '>=', $now->copy()->subDay())
            ->count();

        // ── SaaS revenue metrics ───────────────────────────────────────────────
        // Single plan: €39/mo min + 0.4% GMV (metered). Enterprise invoiced manually.
        $planConfig      = config('billing.plan');
        $gmvRate         = (float) ($planConfig['gmv_rate'] ?? 0.004);
        $minimumMonthly  = (float) ($planConfig['minimum_monthly'] ?? 39);

        $standardWsIds = Workspace::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('billing_plan', 'standard')
            ->select(['id'])
            ->pluck('id');

        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lastMonthEnd   = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $mrr = 0.0;
        if ($standardWsIds->isNotEmpty()) {
            $lastMonthRevenue = DailySnapshot::withoutGlobalScopes()
                ->whereIn('workspace_id', $standardWsIds)
                ->whereBetween('date', [$lastMonthStart, $lastMonthEnd])
                ->selectRaw('workspace_id, SUM(revenue) as total')
                ->groupBy('workspace_id')
                ->pluck('total', 'workspace_id');

            foreach ($standardWsIds as $wsId) {
                $wsRevenue = (float) ($lastMonthRevenue[$wsId] ?? 0);
                $mrr += max($wsRevenue * $gmvRate, $minimumMonthly);
            }
        }

        $enterpriseCount = (int) ($planBreakdown['enterprise'] ?? 0);
        $arr  = $mrr * 12;
        $arpa = $paying > 0 ? $mrr / $paying : 0;

        // Next-month estimate: extrapolate current-month GMV to full month.
        $thisMonthStart = $now->copy()->startOfMonth()->toDateString();
        $thisMonthEnd   = $now->toDateString();
        $dayOfMonth     = (int) $now->day;
        $daysInMonth    = (int) $now->daysInMonth;

        $nextMonthEstimate = 0.0;
        if ($standardWsIds->isNotEmpty() && $dayOfMonth > 0) {
            $thisMonthRevenue = DailySnapshot::withoutGlobalScopes()
                ->whereIn('workspace_id', $standardWsIds)
                ->whereBetween('date', [$thisMonthStart, $thisMonthEnd])
                ->selectRaw('workspace_id, SUM(revenue) as total')
                ->groupBy('workspace_id')
                ->pluck('total', 'workspace_id');

            foreach ($standardWsIds as $wsId) {
                $soFar        = (float) ($thisMonthRevenue[$wsId] ?? 0);
                $extrapolated = ($soFar / $dayOfMonth) * $daysInMonth;
                $nextMonthEstimate += max($extrapolated * $gmvRate, $minimumMonthly);
            }
        } else {
            $nextMonthEstimate = $mrr;
        }

        // Recent workspace signups (last 30 days)
        $recentWorkspaces = Workspace::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->with('owner:id,name,email')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($w) => [
                'id'           => $w->id,
                'name'         => $w->name,
                'billing_plan' => $w->billing_plan,
                'trial_ends_at'=> $w->trial_ends_at?->toISOString(),
                'owner'        => $w->owner ? ['name' => $w->owner->name, 'email' => $w->owner->email] : null,
                'created_at'   => $w->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/Overview', [
            'api_quotas'   => $this->getApiQuotas(),
            'saas_revenue' => [
                'mrr'                 => round($mrr, 2),
                'arr'                 => round($arr, 2),
                'arpa'                => round($arpa, 2),
                'next_month_estimate' => round($nextMonthEstimate, 2),
                'enterprise_count'    => $enterpriseCount,
                'standard_ws_count'   => $standardWsIds->count(),
            ],
            'stats' => [
                'workspaces' => [
                    'total'        => $workspaceTotal,
                    'paying'       => $paying,
                    'trial_active' => $trialActive,
                    'trial_expired'=> $trialExpired,
                    'soft_deleted' => $softDeleted,
                    'new_month'    => $newThisMonth,
                ],
                'users' => [
                    'total'       => $userTotal,
                    'super_admins'=> $superAdmins,
                    'new_month'   => $newUsersMonth,
                ],
                'stores' => [
                    'total'      => $storeTotal,
                    'active'     => $storeActive,
                    'error'      => $storeError,
                    'connecting' => $storeConnecting,
                ],
                'orders_this_month' => $ordersThisMonth,
                'failed_syncs_day'  => $failedSyncsDay,
                'plan_breakdown'    => $planBreakdown,
            ],
            'recent_workspaces' => $recentWorkspaces,
        ]);
    }

    /**
     * Read API quota snapshots from cache (written by platform API clients).
     *
     * Facebook: FacebookAdsClient writes to these keys on every API call.
     *   facebook_api_usage              — last observed usage % + tier metadata (30-min TTL)
     *   facebook_api_throttled_until    — ISO timestamp; present only while throttled
     *   facebook_api_last_throttle_at   — ISO timestamp of last throttle event (24h TTL)
     *   facebook_api_rate_limit_hits_YYYY-MM-DD — daily hit counter (2-day TTL)
     *
     * Google Ads / GSC: no usage headers exposed by Google — only reactive throttle events.
     *   google_ads_throttled_until / gsc_throttled_until
     *   google_ads_last_throttle_at / gsc_last_throttle_at
     *   google_ads_rate_limit_hits_YYYY-MM-DD / gsc_rate_limit_hits_YYYY-MM-DD
     *
     * @return array<string, mixed>
     */
    private function getApiQuotas(): array
    {
        $today = now()->toDateString();

        $fbUsage          = Cache::get('facebook_api_usage');
        $fbThrottledUntil = Cache::get('facebook_api_throttled_until');
        $fbLastThrottleAt = Cache::get('facebook_api_last_throttle_at');
        $fbHitsToday      = (int) Cache::get('facebook_api_rate_limit_hits_' . $today, 0);
        $fbCallsToday     = (int) Cache::get('facebook_api_calls_' . $today, 0);
        $fbLastSuccessAt  = Cache::get('facebook_api_last_success_at');

        $gadsThrottledUntil = Cache::get('google_ads_throttled_until');
        $gadsLastThrottleAt = Cache::get('google_ads_last_throttle_at');
        $gadsHitsToday      = (int) Cache::get('google_ads_rate_limit_hits_' . $today, 0);
        $gadsCallsToday     = (int) Cache::get('google_ads_calls_' . $today, 0);
        $gadsLastSuccessAt  = Cache::get('google_ads_last_success_at');

        $gscThrottledUntil = Cache::get('gsc_throttled_until');
        $gscLastThrottleAt = Cache::get('gsc_last_throttle_at');
        $gscHitsToday      = (int) Cache::get('gsc_rate_limit_hits_' . $today, 0);
        $gscCallsToday     = (int) Cache::get('gsc_calls_' . $today, 0);
        $gscLastSuccessAt  = Cache::get('gsc_last_success_at');

        $psiThrottledUntil = Cache::get('psi_throttled_until');
        $psiLastThrottleAt = Cache::get('psi_last_throttle_at');
        $psiHitsToday      = (int) Cache::get('psi_rate_limit_hits_' . $today, 0);
        $psiCallsToday     = (int) Cache::get('psi_calls_' . $today, 0);
        $psiLastSuccessAt  = Cache::get('psi_last_success_at');

        return [
            'facebook' => [
                'usage_pct'        => $fbUsage ? (int) $fbUsage['pct'] : null,
                'tier'             => $fbUsage ? (string) $fbUsage['tier'] : null,
                'threshold_pct'    => $fbUsage ? (int) $fbUsage['threshold'] : null,
                'hard_cap_pct'     => $fbUsage ? ($fbUsage['hard_cap'] ?? null) : null,
                'observed_at'      => $fbUsage ? (string) $fbUsage['observed_at'] : null,
                'throttled_until'  => $fbThrottledUntil ?? null,
                'last_throttle_at' => $fbLastThrottleAt ?? null,
                'hits_today'       => $fbHitsToday,
                'calls_today'      => $fbCallsToday,
                'last_success_at'  => $fbLastSuccessAt ?? null,
            ],
            'google_ads' => [
                'throttled_until'  => $gadsThrottledUntil ?? null,
                'last_throttle_at' => $gadsLastThrottleAt ?? null,
                'hits_today'       => $gadsHitsToday,
                'calls_today'      => $gadsCallsToday,
                'last_success_at'  => $gadsLastSuccessAt ?? null,
            ],
            'gsc' => [
                'throttled_until'  => $gscThrottledUntil ?? null,
                'last_throttle_at' => $gscLastThrottleAt ?? null,
                'hits_today'       => $gscHitsToday,
                'calls_today'      => $gscCallsToday,
                'last_success_at'  => $gscLastSuccessAt ?? null,
            ],
            'psi' => [
                'throttled_until'  => $psiThrottledUntil ?? null,
                'last_throttle_at' => $psiLastThrottleAt ?? null,
                'hits_today'       => $psiHitsToday,
                'calls_today'      => $psiCallsToday,
                'last_success_at'  => $psiLastSuccessAt ?? null,
            ],
        ];
    }
}
