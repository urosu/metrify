import { Head } from '@inertiajs/react';
import { AlertTriangle, Building2, RefreshCw, ShieldAlert, Store, TrendingUp, Users, Zap } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { InfoTooltip } from '@/Components/shared/Tooltip';
import { formatDateOnly } from '@/lib/formatters';
import type { PageProps } from '@/types';

const PLAN_LABELS: Record<string, string> = {
    starter:    'Starter',
    growth:     'Growth',
    scale:      'Scale',
    percentage: 'Percentage',
    enterprise: 'Enterprise',
};

const PLAN_COLORS: Record<string, string> = {
    starter:    'bg-blue-100 text-blue-700',
    growth:     'bg-violet-100 text-violet-700',
    scale:      'bg-primary/15 text-primary',
    percentage: 'bg-amber-100 text-amber-700',
    enterprise: 'bg-green-100 text-green-700',
};

interface SaasRevenue {
    mrr: number;
    arr: number;
    arpa: number;
    next_month_estimate: number;
    flat_mrr: number;
    percentage_mrr: number;
    enterprise_count: number;
    percentage_ws_count: number;
}

interface Stats {
    workspaces: {
        total: number;
        paying: number;
        trial_active: number;
        trial_expired: number;
        soft_deleted: number;
        new_month: number;
    };
    users: {
        total: number;
        super_admins: number;
        new_month: number;
    };
    stores: {
        total: number;
        active: number;
        error: number;
        connecting: number;
    };
    orders_this_month: number;
    failed_syncs_day: number;
    plan_breakdown: Record<string, number>;
}

interface RecentWorkspace {
    id: number;
    name: string;
    billing_plan: string | null;
    trial_ends_at: string | null;
    owner: { name: string; email: string } | null;
    created_at: string;
}

interface FacebookQuota {
    usage_pct: number | null;
    tier: string | null;
    threshold_pct: number | null;
    hard_cap_pct: number | null;
    observed_at: string | null;
    throttled_until: string | null;
    last_throttle_at: string | null;
    hits_today: number;
    calls_today: number;
    last_success_at: string | null;
}

interface SimpleQuota {
    throttled_until: string | null;
    last_throttle_at: string | null;
    hits_today: number;
    calls_today: number;
    last_success_at: string | null;
}

interface ApiQuotas {
    facebook: FacebookQuota;
    google_ads: SimpleQuota;
    gsc: SimpleQuota;
    psi: SimpleQuota;
}

function fmt(n: number): string {
    return '€' + n.toLocaleString('en-EU', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function RevenueCard({ label, value, sub, highlight, tooltip }: { label: string; value: string; sub?: string; highlight?: boolean; tooltip?: string }) {
    return (
        <div className={`rounded-xl border p-5 ${highlight ? 'border-primary/20 bg-primary/10' : 'border-zinc-200 bg-white'}`}>
            <div className="flex items-center gap-1.5 text-sm font-medium text-zinc-500">
                {label}
                {tooltip && <InfoTooltip content={tooltip} />}
            </div>
            <div className={`mt-2 text-2xl font-bold ${highlight ? 'text-primary' : 'text-zinc-900'}`}>{value}</div>
            {sub && <div className="mt-1 text-xs text-zinc-400">{sub}</div>}
        </div>
    );
}

function StatCard({
    label,
    value,
    sub,
    icon: Icon,
    alert,
    tooltip,
}: {
    label: string;
    value: number | string;
    sub?: string;
    icon: React.ComponentType<{ className?: string }>;
    alert?: boolean;
    tooltip?: string;
}) {
    return (
        <div className={`rounded-xl border bg-white p-5 ${alert ? 'border-red-200' : 'border-zinc-200'}`}>
            <div className="flex items-center justify-between">
                <span className="flex items-center gap-1.5 text-sm font-medium text-zinc-500">
                    {label}
                    {tooltip && <InfoTooltip content={tooltip} />}
                </span>
                <Icon className={`h-4 w-4 ${alert ? 'text-red-500' : 'text-zinc-400'}`} />
            </div>
            <div className={`mt-2 text-2xl font-bold ${alert ? 'text-red-700' : 'text-zinc-900'}`}>{value}</div>
            {sub && <div className="mt-1 text-xs text-zinc-400">{sub}</div>}
        </div>
    );
}

/**
 * Progress bar normalised to the actual usable ceiling.
 *
 * Facebook's `call_count` header is a raw score (0–60 on dev tier, 0–100 on standard).
 * The bar always fills to 100% at the ceiling so "nearly full" looks nearly full,
 * regardless of what the raw number is.
 *
 * barPct  = (rawScore / ceiling) * 100  → position on the bar (0–100%)
 * pausePct = position of the "we stop here" marker on the bar
 */
function UsageBar({ rawScore, ceiling, pauseAt }: { rawScore: number; ceiling: number; pauseAt: number }) {
    const barPct   = Math.min((rawScore / ceiling) * 100, 100);
    const pausePct = (pauseAt / ceiling) * 100;
    const danger   = rawScore >= pauseAt;
    const warning  = rawScore >= pauseAt * 0.85;
    const color    = danger ? 'bg-red-500' : warning ? 'bg-amber-400' : 'bg-emerald-500';
    return (
        <div className="relative mt-2 h-2.5 w-full rounded-full bg-zinc-100">
            <div className={`h-2.5 rounded-full transition-all ${color}`} style={{ width: `${barPct}%` }} />
            {/* Pause marker — where we voluntarily stop to avoid hitting the ceiling */}
            <div className="absolute top-0 h-2.5 w-px bg-amber-400 opacity-80" style={{ left: `${pausePct}%` }} title={`We pause here (score ${pauseAt})`} />
        </div>
    );
}

function FacebookQuotaCard({ fb }: { fb: FacebookQuota }) {
    const isThrottled = fb.throttled_until !== null && new Date(fb.throttled_until) > new Date();
    const throttledUntilStr = fb.throttled_until
        ? new Date(fb.throttled_until).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })
        : null;
    const observedAgo = fb.observed_at
        ? Math.round((Date.now() - new Date(fb.observed_at).getTime()) / 60000)
        : null;

    // Ceiling = the score at which Facebook blocks us.
    // Dev tier: ceiling is 60 (reported as raw score in the header, not a %).
    // Standard tier: ceiling is 100 (full scale).
    const ceiling = fb.hard_cap_pct ?? 100;
    const normalizedPct = fb.usage_pct !== null ? Math.round((fb.usage_pct / ceiling) * 100) : null;

    // Red = quota near/over the pause threshold (genuinely dangerous).
    // Amber = currently throttled but quota is still low (transient burst limit).
    // Default = healthy.
    const isHighQuota = fb.usage_pct !== null && fb.threshold_pct !== null && fb.usage_pct >= fb.threshold_pct;
    const cardStyle = isHighQuota
        ? 'border-red-200 bg-red-50'
        : isThrottled
        ? 'border-amber-200 bg-amber-50'
        : 'border-zinc-200 bg-white';

    return (
        <div className={`rounded-xl border p-4 ${cardStyle}`}>
            <div className="flex items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <span className="text-sm font-semibold text-zinc-700">Facebook Ads API</span>
                    {fb.tier && (
                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${fb.tier === 'dev' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'}`}>
                            {fb.tier} tier
                        </span>
                    )}
                </div>
                {isThrottled ? (
                    <span className="flex items-center gap-1 text-xs font-medium text-amber-600">
                        <AlertTriangle className="h-3.5 w-3.5" /> Throttled until {throttledUntilStr}
                    </span>
                ) : (
                    <span className="text-xs text-zinc-400">
                        {fb.usage_pct === null ? 'No data yet' : 'OK'}
                    </span>
                )}
            </div>

            {fb.usage_pct !== null && fb.threshold_pct !== null && normalizedPct !== null ? (
                <>
                    <div className="mt-3 flex items-end justify-between text-xs text-zinc-500">
                        {/* Show normalised % as the headline — that's what matters operationally */}
                        <span>
                            <span className="text-2xl font-bold text-zinc-800">{normalizedPct}%</span>
                            <span className="ml-1">of quota</span>
                        </span>
                        <span className="text-right text-zinc-400">
                            raw {fb.usage_pct} / {ceiling}
                            {observedAgo !== null && (
                                <span className="ml-2">{observedAgo < 2 ? 'just now' : `${observedAgo}m ago`}</span>
                            )}
                        </span>
                    </div>
                    <UsageBar rawScore={fb.usage_pct} ceiling={ceiling} pauseAt={fb.threshold_pct} />
                    <div className="mt-1 text-xs text-zinc-400">
                        We pause syncs at score {fb.threshold_pct} · Facebook blocks at {ceiling}
                    </div>
                </>
            ) : (
                <p className="mt-2 text-xs text-zinc-400">No API calls recorded yet — usage data appears after the first sync runs.</p>
            )}

            <div className="mt-3 grid grid-cols-2 gap-x-4 gap-y-1.5 border-t border-zinc-100 pt-3 text-xs text-zinc-500">
                <span>API calls today: <span className="font-semibold text-zinc-700">{fb.calls_today.toLocaleString()}</span></span>
                <span>Throttles today: <span className={`font-semibold ${fb.hits_today > 0 ? 'text-amber-600' : 'text-zinc-700'}`}>{fb.hits_today}</span></span>
                {fb.last_success_at && (
                    <span>Last success: <span className="font-semibold text-zinc-700">{new Date(fb.last_success_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })}</span></span>
                )}
                {fb.last_throttle_at && (
                    <span>Last throttle: <span className="font-semibold text-zinc-700">{new Date(fb.last_throttle_at).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false })}</span></span>
                )}
            </div>
        </div>
    );
}

function SimpleQuotaCard({ label, quota, note }: { label: string; quota: SimpleQuota; note?: string }) {
    const isThrottled = quota.throttled_until !== null && new Date(quota.throttled_until) > new Date();
    const hasActivity = quota.calls_today > 0 || quota.last_success_at !== null;
    const throttledUntilStr = quota.throttled_until
        ? new Date(quota.throttled_until).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })
        : null;

    const lastSuccessAgo = quota.last_success_at
        ? Math.round((Date.now() - new Date(quota.last_success_at).getTime()) / 60000)
        : null;

    return (
        <div className={`rounded-xl border p-4 ${isThrottled ? 'border-amber-200 bg-amber-50' : 'border-zinc-200 bg-white'}`}>
            <div className="flex items-center justify-between gap-2">
                <span className="text-sm font-semibold text-zinc-700">{label}</span>
                {isThrottled ? (
                    <span className="flex items-center gap-1 text-xs font-medium text-amber-600">
                        <AlertTriangle className="h-3.5 w-3.5" /> Throttled until {throttledUntilStr}
                    </span>
                ) : (
                    <span className="text-xs text-zinc-400">{!hasActivity ? 'No data yet' : 'OK'}</span>
                )}
            </div>

            {hasActivity ? (
                <div className="mt-3 flex items-end justify-between text-xs text-zinc-500">
                    <span>
                        <span className="text-2xl font-bold text-zinc-800">{quota.calls_today.toLocaleString()}</span>
                        <span className="ml-1">calls today</span>
                    </span>
                    {lastSuccessAgo !== null && (
                        <span className="text-zinc-400">
                            last success {lastSuccessAgo < 2 ? 'just now' : `${lastSuccessAgo}m ago`}
                        </span>
                    )}
                </div>
            ) : (
                <p className="mt-2 text-xs text-zinc-400">No API calls recorded yet — data appears after the first sync runs.</p>
            )}

            {note && (
                <p className="mt-2 text-xs text-zinc-400">{note}</p>
            )}

            <div className="mt-3 grid grid-cols-2 gap-x-4 gap-y-1.5 border-t border-zinc-100 pt-3 text-xs text-zinc-500">
                <span>Throttles today: <span className={`font-semibold ${quota.hits_today > 0 ? 'text-amber-600' : 'text-zinc-700'}`}>{quota.hits_today}</span></span>
                {quota.last_throttle_at && (
                    <span>Last throttle: <span className="font-semibold text-zinc-700">{new Date(quota.last_throttle_at).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span></span>
                )}
            </div>
        </div>
    );
}

export default function Overview({
    saas_revenue,
    stats,
    recent_workspaces,
    api_quotas,
}: PageProps<{ saas_revenue: SaasRevenue; stats: Stats; recent_workspaces: RecentWorkspace[]; api_quotas: ApiQuotas }>) {
    const planOrder = ['starter', 'growth', 'scale', 'percentage', 'enterprise'];

    return (
        <AppLayout>
            <Head title="Admin Overview" />

            <PageHeader
                title="Admin Overview"
                subtitle="System-wide metrics across all workspaces"
            />

            <div className="mt-1 mb-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                <ShieldAlert className="h-4 w-4 shrink-0" />
                Super admin panel — you are viewing data across all workspaces
            </div>

            {/* SaaS revenue */}
            <div className="mb-6">
                <div className="mb-2 flex items-center gap-2">
                    <TrendingUp className="h-4 w-4 text-primary" />
                    <h2 className="text-sm font-semibold text-zinc-700">Nexstage revenue</h2>
                    {saas_revenue.enterprise_count > 0 && (
                        <span className="text-xs text-zinc-400">({saas_revenue.enterprise_count} enterprise workspace{saas_revenue.enterprise_count !== 1 ? 's' : ''} excluded — custom pricing)</span>
                    )}
                </div>
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <RevenueCard label="MRR" value={fmt(saas_revenue.mrr)} sub="current month" highlight
                        tooltip="Monthly Recurring Revenue. Flat plan prices × workspace count, plus 1% of last month's revenue for each % tier workspace (floored at €149). Enterprise excluded." />
                    <RevenueCard label="ARR" value={fmt(saas_revenue.arr)} sub="MRR × 12"
                        tooltip="Annual Recurring Revenue. A simple MRR × 12 projection — does not account for churn or expansion." />
                    <RevenueCard label="ARPA" value={fmt(saas_revenue.arpa)} sub="per paying workspace"
                        tooltip="Average Revenue Per Account. MRR divided by the number of paying workspaces (trial and enterprise excluded)." />
                    <RevenueCard
                        label="Next month estimate"
                        value={fmt(saas_revenue.next_month_estimate)}
                        sub={saas_revenue.percentage_ws_count > 0
                            ? `incl. ${saas_revenue.percentage_ws_count} % tier workspace${saas_revenue.percentage_ws_count !== 1 ? 's' : ''} extrapolated`
                            : 'flat plans only'}
                        tooltip="Flat plans are fixed. % tier workspaces are estimated by extrapolating their current-month revenue to a full month, then applying the 1% rate with €149 floor." />
                </div>
                {(saas_revenue.flat_mrr > 0 || saas_revenue.percentage_mrr > 0) && (
                    <div className="mt-2 flex gap-4 text-xs text-zinc-400">
                        <span>Flat plans: <span className="font-medium text-zinc-600">{fmt(saas_revenue.flat_mrr)}/mo</span></span>
                        {saas_revenue.percentage_ws_count > 0 && (
                            <span>% tier (last month actuals): <span className="font-medium text-zinc-600">{fmt(saas_revenue.percentage_mrr)}/mo</span> <span title="Revenue is in each workspace's reporting currency; converted amounts are approximate">(approx.)</span></span>
                        )}
                    </div>
                )}
            </div>

            {/* Main stats */}
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <StatCard label="Total workspaces"   value={stats.workspaces.total}   sub={`+${stats.workspaces.new_month} this month`} icon={Building2}
                    tooltip="All active (non-deleted) workspaces. Includes trial, paying, and expired-trial workspaces." />
                <StatCard label="Paying customers"   value={stats.workspaces.paying}  sub={`${stats.workspaces.trial_active} on trial`}  icon={Building2}
                    tooltip="Workspaces with an active billing plan (Starter, Growth, Scale, Percentage, or Enterprise). Trial-only workspaces not included." />
                <StatCard label="Total users"        value={stats.users.total}        sub={`+${stats.users.new_month} this month`}        icon={Users}
                    tooltip="All registered user accounts, including super admins. A user can belong to multiple workspaces." />
                <StatCard label="Active stores"      value={stats.stores.active}      sub={`${stats.stores.total} total`}                 icon={Store}
                    tooltip="Stores with status 'active' — connected and syncing successfully. Does not include stores in error, connecting, or disconnected states." />
                <StatCard label="Orders this month"  value={stats.orders_this_month.toLocaleString()}  sub="across all stores"          icon={RefreshCw}
                    tooltip="Total number of orders (all statuses) placed this calendar month across every workspace and store." />
                <StatCard
                    label="Trial expired (no plan)"
                    value={stats.workspaces.trial_expired}
                    sub="no active subscription"
                    icon={AlertTriangle}
                    alert={stats.workspaces.trial_expired > 0}
                    tooltip="Workspaces whose 14-day trial has ended and have not subscribed. Syncs are paused for these — they see a billing prompt on every page." />
                <StatCard
                    label="Stores in error"
                    value={stats.stores.error}
                    sub={stats.stores.connecting > 0 ? `${stats.stores.connecting} connecting` : undefined}
                    icon={AlertTriangle}
                    alert={stats.stores.error > 0}
                    tooltip="Stores that have hit 3+ consecutive sync failures. An alert has been sent to the workspace owner. Syncs continue retrying." />
                <StatCard
                    label="Failed syncs (24 h)"
                    value={stats.failed_syncs_day}
                    icon={AlertTriangle}
                    alert={stats.failed_syncs_day > 0}
                    tooltip="Number of sync job failures logged in the last 24 hours across all workspaces. Check the Logs page for details." />
            </div>

            {/* API quotas */}
            <div className="mt-6">
                <div className="mb-2 flex items-center gap-2">
                    <Zap className="h-4 w-4 text-primary" />
                    <h2 className="text-sm font-semibold text-zinc-700">API quotas</h2>
                    <InfoTooltip content="Live usage snapshots from integration API clients. Updated on every sync call; stale after 30 min with no activity." />
                </div>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <FacebookQuotaCard fb={api_quotas.facebook} />
                    <SimpleQuotaCard label="Google Ads API" quota={api_quotas.google_ads} note="Google does not expose quota consumption in response headers — no usage % available." />
                    <SimpleQuotaCard label="Google Search Console API" quota={api_quotas.gsc} note="Google does not expose quota consumption in response headers — no usage % available." />
                    <SimpleQuotaCard label="PageSpeed Insights API" quota={api_quotas.psi} note="25,000 req/day limit · resets midnight Pacific · Google does not expose remaining quota in response headers." />
                </div>
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* Plan breakdown */}
                <div className="rounded-xl border border-zinc-200 bg-white p-5">
                    <h2 className="mb-4 text-sm font-semibold text-zinc-700">Billing plan breakdown</h2>
                    {Object.keys(stats.plan_breakdown).length === 0 ? (
                        <p className="text-sm text-zinc-400">No paying workspaces yet.</p>
                    ) : (
                        <div className="space-y-2">
                            {planOrder
                                .filter((p) => stats.plan_breakdown[p])
                                .map((plan) => (
                                    <div key={plan} className="flex items-center justify-between">
                                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${PLAN_COLORS[plan] ?? 'bg-zinc-100 text-zinc-600'}`}>
                                            {PLAN_LABELS[plan] ?? plan}
                                        </span>
                                        <span className="text-sm font-semibold text-zinc-800">
                                            {stats.plan_breakdown[plan]}
                                            <span className="ml-1 text-xs font-normal text-zinc-400">workspace{stats.plan_breakdown[plan] !== 1 ? 's' : ''}</span>
                                        </span>
                                    </div>
                                ))}
                        </div>
                    )}

                    <div className="mt-4 border-t border-zinc-100 pt-4 grid grid-cols-2 gap-3 text-xs text-zinc-500">
                        <div>Super admins: <span className="font-semibold text-zinc-700">{stats.users.super_admins}</span></div>
                        <div>Soft-deleted workspaces: <span className="font-semibold text-zinc-700">{stats.workspaces.soft_deleted}</span></div>
                        <div>Stores connecting: <span className="font-semibold text-zinc-700">{stats.stores.connecting}</span></div>
                    </div>
                </div>

                {/* Recent signups */}
                <div className="rounded-xl border border-zinc-200 bg-white p-5">
                    <h2 className="mb-4 text-sm font-semibold text-zinc-700">Recent workspace signups (30 days)</h2>
                    {recent_workspaces.length === 0 ? (
                        <p className="text-sm text-zinc-400">No new workspaces in the last 30 days.</p>
                    ) : (
                        <div className="space-y-2">
                            {recent_workspaces.map((w) => (
                                <div key={w.id} className="flex items-center justify-between gap-3 rounded-lg px-2 py-1.5 hover:bg-zinc-50">
                                    <div className="min-w-0">
                                        <div className="truncate text-sm font-medium text-zinc-800">{w.name}</div>
                                        {w.owner && (
                                            <div className="truncate text-xs text-zinc-400">{w.owner.email}</div>
                                        )}
                                    </div>
                                    <div className="shrink-0 text-right">
                                        {w.billing_plan ? (
                                            <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${PLAN_COLORS[w.billing_plan] ?? 'bg-zinc-100 text-zinc-600'}`}>
                                                {PLAN_LABELS[w.billing_plan] ?? w.billing_plan}
                                            </span>
                                        ) : (
                                            <span className="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500">Trial</span>
                                        )}
                                        <div className="mt-0.5 text-xs text-zinc-400">
                                            {formatDateOnly(w.created_at)}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
