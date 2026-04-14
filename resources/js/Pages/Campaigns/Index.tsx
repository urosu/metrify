import axios from 'axios';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { BarChart2, Table2, Grid2X2 } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { DateRangePicker } from '@/Components/shared/DateRangePicker';
import { PageHeader } from '@/Components/shared/PageHeader';
import { MetricCard } from '@/Components/shared/MetricCard';
import { CampaignsTabBar } from '@/Components/shared/CampaignsTabBar';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { BarChart, type BarSeriesConfig } from '@/Components/charts/BarChart';
import { QuadrantChart, type QuadrantCampaign } from '@/Components/charts/QuadrantChart';
import { formatCurrency, formatNumber, type Granularity } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { syncDotClass, syncDotTitle } from '@/lib/syncStatus';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

interface CampaignMetrics {
    roas: number | null;
    cpo: number | null;
    spend: number | null;
    revenue: number | null;
    attributed_revenue: number | null;
    attributed_orders: number;
    impressions: number;
    clicks: number;
    ctr: number | null;
    cpc: number | null;
}

interface CampaignRow {
    id: number;
    name: string;
    platform: string;
    status: string | null;
    spend: number;
    impressions: number;
    clicks: number;
    ctr: number | null;
    cpc: number | null;
    platform_roas: number | null;
    real_roas: number | null;
    real_cpo: number | null;
    attributed_revenue: number | null;
    attributed_orders: number;
    spend_velocity: number | null;
}

interface PlatformBreakdownEntry {
    spend: number | null;
    impressions: number;
    clicks: number;
    ctr: number | null;
}

interface SpendChartPoint {
    date: string;
    facebook: number;
    google: number;
}

interface AdAccountOption {
    id: number;
    platform: string;
    name: string;
    status: string;
    last_synced_at: string | null;
}

interface Props {
    has_ad_accounts: boolean;
    ad_accounts: AdAccountOption[];
    ad_account_ids: number[];
    metrics: CampaignMetrics | null;
    compare_metrics: CampaignMetrics | null;
    campaigns: CampaignRow[];
    platform_breakdown: Record<string, PlatformBreakdownEntry>;
    chart_data: SpendChartPoint[];
    compare_chart_data: SpendChartPoint[] | null;
    total_revenue: number | null;
    unattributed_revenue: number | null;
    // Workspace ROAS target for Winners/Losers chips. Null = no target set.
    workspace_target_roas: number | null;
    from: string;
    to: string;
    compare_from: string | null;
    compare_to: string | null;
    granularity: Granularity;
    platform: 'all' | 'facebook' | 'google';
    status: 'all' | 'active' | 'paused';
    view: 'table' | 'quadrant';
    sort: string;
    direction: 'asc' | 'desc';
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function pctChange(current: number | null, previous: number | null): number | null {
    if (current === null || previous === null || previous === 0) return null;
    return ((current - previous) / previous) * 100;
}

function navigate(params: Record<string, string | undefined>) {
    router.get('/campaigns', params as Record<string, string>, { preserveState: true, replace: true });
}

// ─── Toggle button group ──────────────────────────────────────────────────────

function ToggleGroup<T extends string>({
    options,
    value,
    onChange,
}: {
    options: { label: string; value: T }[];
    value: T;
    onChange: (v: T) => void;
}) {
    return (
        <div className="inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5">
            {options.map((opt) => (
                <button
                    key={opt.value}
                    onClick={() => onChange(opt.value)}
                    className={cn(
                        'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                        value === opt.value
                            ? 'bg-white text-zinc-900 shadow-sm'
                            : 'text-zinc-500 hover:text-zinc-700',
                    )}
                >
                    {opt.label}
                </button>
            ))}
        </div>
    );
}

// ─── Platform badge ───────────────────────────────────────────────────────────

const PLATFORM_COLORS: Record<string, string> = {
    facebook: 'bg-blue-50 text-blue-700',
    google:   'bg-red-50 text-red-700',
};

function PlatformBadge({ platform }: { platform: string }) {
    return (
        <span className={cn(
            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize',
            PLATFORM_COLORS[platform] ?? 'bg-zinc-100 text-zinc-500',
        )}>
            {platform}
        </span>
    );
}

// ─── Sort button ──────────────────────────────────────────────────────────────

function SortButton({
    col,
    label,
    currentSort,
    currentDir,
    onSort,
}: {
    col: string;
    label: string;
    currentSort: string;
    currentDir: 'asc' | 'desc';
    onSort: (col: string) => void;
}) {
    const active = currentSort === col;
    return (
        <button
            onClick={() => onSort(col)}
            className={cn('flex items-center gap-1 hover:text-zinc-700 transition-colors', active ? 'text-primary' : 'text-zinc-400')}
        >
            {label}
            {active && <span className="text-[10px]">{currentDir === 'desc' ? '↓' : '↑'}</span>}
        </button>
    );
}

// ─── Spend velocity badge ─────────────────────────────────────────────────────
// velocity = (spend / budget_for_period) / (days_elapsed / days_in_period)
// 1.0 = on pace, >1.0 = pacing fast, <1.0 = pacing slow

function SpendVelocityBadge({ velocity }: { velocity: number | null }) {
    if (velocity === null) return <span className="text-zinc-400">—</span>;

    const pct     = Math.round(velocity * 100);
    const isHigh  = velocity > 1.15;
    const isLow   = velocity < 0.85;
    const color   = isHigh ? 'text-amber-700' : isLow ? 'text-blue-600' : 'text-green-700';

    return (
        <span className={cn('tabular-nums font-medium', color)} title={`${pct}% of expected pacing`}>
            {pct}%
        </span>
    );
}

// ─── Spend chart (multi-platform stacked) ────────────────────────────────────

const SPEND_SERIES: BarSeriesConfig[] = [
    { dataKey: 'facebook', name: 'Facebook', color: '#3b82f6', stackId: 'spend' },
    { dataKey: 'google',   name: 'Google',   color: '#ef4444', stackId: 'spend' },
];

function SpendChart({
    chartData,
    granularity,
    currency,
    navigating,
}: {
    chartData: SpendChartPoint[];
    granularity: Granularity;
    currency: string;
    navigating: boolean;
}) {
    const activeSeries = useMemo(
        () => SPEND_SERIES.filter((s) => chartData.some((d) => (d[s.dataKey as keyof SpendChartPoint] as number) > 0)),
        [chartData],
    );

    return (
        <div className="mb-6 rounded-xl border border-zinc-200 bg-white p-5">
            <div className="mb-4 text-sm font-medium text-zinc-500">Daily ad spend</div>
            {chartData.length === 0 ? (
                <div className="flex h-64 flex-col items-center justify-center gap-2">
                    <p className="text-sm text-zinc-400">No spend data for this period.</p>
                </div>
            ) : (
                <BarChart
                    data={chartData}
                    granularity={granularity}
                    currency={currency}
                    valueType="currency"
                    series={activeSeries}
                    loading={navigating}
                    className="h-64 w-full"
                />
            )}
        </div>
    );
}

// ─── Campaign table ───────────────────────────────────────────────────────────

function CampaignTable({
    campaigns,
    currency,
    sort,
    direction,
    onSort,
    from,
    to,
}: {
    campaigns: CampaignRow[];
    currency: string;
    sort: string;
    direction: 'asc' | 'desc';
    onSort: (col: string) => void;
    from: string;
    to: string;
}) {
    const sortBtn = (col: string, label: string) => (
        <SortButton col={col} label={label} currentSort={sort} currentDir={direction} onSort={onSort} />
    );

    return (
        <div className="rounded-xl border border-zinc-200 bg-white">
            <div className="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
                <div className="text-sm font-medium text-zinc-500">
                    Campaigns
                    {campaigns.length > 0 && (
                        <span className="ml-2 rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500">
                            {campaigns.length}
                        </span>
                    )}
                </div>
            </div>

            {campaigns.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-16 text-center">
                    <p className="text-sm text-zinc-400">No campaign data for this period.</p>
                    <p className="mt-1 text-xs text-zinc-400">Data appears after the next sync completes.</p>
                </div>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            {/* Platform ROAS is adjacent to Real ROAS so the contrast is immediately visible.
                                This side-by-side comparison is the primary trust-building moment:
                                "Facebook says 4.2× / Store says 2.8×" on the same row.
                                See: PLANNING.md "Platform ROAS vs Real ROAS" */}
                            <tr className="text-left text-xs font-medium uppercase tracking-wide text-zinc-400">
                                <th className="px-5 py-3">Campaign</th>
                                <th className="px-5 py-3">Platform</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3 text-right">
                                    {sortBtn('spend', 'Spend')}
                                </th>
                                {/* Platform ROAS next to Real ROAS — the visual contrast is the point */}
                                <th className="px-5 py-3 text-right">
                                    <span className="inline-flex items-center gap-1">
                                        Platform ROAS
                                        <a
                                            href="/help/data-accuracy#roas"
                                            className="text-zinc-300 hover:text-zinc-500 transition-colors"
                                            title="What Meta/Google report using pixel-based attribution. Typically higher than Real ROAS due to iOS14+ modeled conversions and cross-platform double-counting."
                                            onClick={(e) => e.stopPropagation()}
                                        >
                                            ⓘ
                                        </a>
                                    </span>
                                </th>
                                <th className="px-5 py-3 text-right">
                                    <span title="UTM-attributed revenue ÷ ad spend. Calculated from your actual orders, not platform pixel attribution. Requires UTM parameters on your ad links.">
                                        {sortBtn('real_roas', 'Real ROAS')}
                                    </span>
                                </th>
                                <th className="px-5 py-3 text-right">
                                    <span title="Ad spend ÷ UTM-attributed orders">
                                        {sortBtn('real_cpo', 'Real CPO')}
                                    </span>
                                </th>
                                <th className="px-5 py-3 text-right">
                                    {sortBtn('attributed_revenue', 'Attr. Revenue')}
                                </th>
                                <th className="px-5 py-3 text-right">Attr. Orders</th>
                                <th className="px-5 py-3 text-right">
                                    <span title="Spend velocity: how fast this campaign is burning through its budget vs expected daily pace. 100% = on pace. Requires a daily or lifetime budget set on the campaign.">
                                        {sortBtn('spend_velocity', 'Velocity')}
                                    </span>
                                </th>
                                <th className="px-5 py-3 text-right">Impressions</th>
                                <th className="px-5 py-3 text-right">Clicks</th>
                                <th className="px-5 py-3 text-right">CTR</th>
                                <th className="px-5 py-3 text-right">CPC</th>
                                <th className="px-5 py-3 text-right">Drill →</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-100">
                            {campaigns.map((c) => (
                                <tr key={c.id} className="hover:bg-zinc-50">
                                    <td className="max-w-[200px] px-5 py-3">
                                        <Link
                                            href={`/campaigns/adsets?campaign_id=${c.id}&from=${from}&to=${to}`}
                                            className="block truncate font-medium text-zinc-800 hover:text-primary transition-colors"
                                            title={c.name}
                                        >
                                            {c.name || '—'}
                                        </Link>
                                    </td>
                                    <td className="px-5 py-3">
                                        <PlatformBadge platform={c.platform} />
                                    </td>
                                    <td className="px-5 py-3">
                                        {c.status ? <StatusBadge status={c.status} /> : <span className="text-zinc-400">—</span>}
                                    </td>
                                    <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                        {c.spend > 0 ? formatCurrency(c.spend, currency) : 'N/A'}
                                    </td>
                                    {/* Platform ROAS — shown in muted blue to distinguish from Real ROAS.
                                        Adjacent columns make the gap between platform-reported and store-verified
                                        ROAS immediately visible without needing a tooltip. */}
                                    <td className="px-5 py-3 text-right tabular-nums">
                                        {c.platform_roas != null ? (
                                            <span className="text-blue-600 font-medium">
                                                {c.platform_roas.toFixed(2)}×
                                            </span>
                                        ) : (
                                            <span className="text-zinc-400">—</span>
                                        )}
                                    </td>
                                    {/* Real ROAS — green/red based on ≥1×. Adjacent to Platform ROAS for contrast. */}
                                    <td className="px-5 py-3 text-right tabular-nums font-medium">
                                        {c.real_roas != null ? (
                                            <span className={c.real_roas >= 1 ? 'text-green-700' : 'text-red-600'}>
                                                {c.real_roas.toFixed(2)}×
                                            </span>
                                        ) : (
                                            <span className="text-zinc-400" title="No UTM-matched orders — add UTM parameters to your ad links to enable Real ROAS tracking.">—</span>
                                        )}
                                    </td>
                                    <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                        {c.real_cpo != null ? formatCurrency(c.real_cpo, currency) : <span className="text-zinc-400">—</span>}
                                    </td>
                                    <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                        {c.attributed_revenue != null ? formatCurrency(c.attributed_revenue, currency) : <span className="text-zinc-400">—</span>}
                                    </td>
                                    <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                        {c.attributed_orders > 0 ? formatNumber(c.attributed_orders) : <span className="text-zinc-400">—</span>}
                                    </td>
                                    <td className="px-5 py-3 text-right">
                                        <SpendVelocityBadge velocity={c.spend_velocity} />
                                    </td>
                                    <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                        {formatNumber(c.impressions)}
                                    </td>
                                    <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                        {formatNumber(c.clicks)}
                                    </td>
                                    <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                        {c.ctr != null ? `${c.ctr.toFixed(2)}%` : 'N/A'}
                                    </td>
                                    <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                        {c.cpc != null ? formatCurrency(c.cpc, currency) : 'N/A'}
                                    </td>
                                    <td className="px-5 py-3 text-right">
                                        <Link
                                            href={`/campaigns/adsets?campaign_id=${c.id}&from=${from}&to=${to}`}
                                            className="text-xs text-zinc-400 hover:text-primary transition-colors"
                                            title="View ad sets for this campaign"
                                        >
                                            Ad Sets →
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function CampaignsIndex(props: Props) {
    const { workspace, auth } = usePage<PageProps>().props;
    const currency = workspace?.reporting_currency ?? 'EUR';

    const {
        has_ad_accounts,
        ad_accounts,
        ad_account_ids,
        metrics,
        compare_metrics,
        campaigns,
        chart_data,
        total_revenue,
        unattributed_revenue,
        workspace_target_roas,
        from,
        to,
        compare_from,
        compare_to,
        granularity,
        platform,
        status,
        view,
        sort,
        direction,
    } = props;

    const [navigating, setNavigating] = useState(false);

    // ── Quadrant ROAS type toggle — local state, no server round-trip needed ──
    // Both real_roas and platform_roas are already in the campaign rows.
    const [roasType, setRoasType] = useState<'real' | 'platform'>('real');

    // ── Winners / Losers filter ──────────────────────────────────────────────
    // Restored from view_preferences on mount; URL ?filter param takes priority
    // (used by sidebar "Winners / Losers" deep-links). Persisted on change.
    // Winners = real_roas >= threshold (workspace target or 1.0 break-even fallback).
    // See: PLANNING.md "Winners/Losers" filter chips
    const urlFilter = typeof window !== 'undefined'
        ? (new URLSearchParams(window.location.search).get('filter') ?? null)
        : null;
    const savedFilter = (urlFilter ?? auth.user?.view_preferences?.campaigns?.filter ?? 'all') as 'all' | 'winners' | 'losers';
    const [roasFilter, setRoasFilter] = useState<'all' | 'winners' | 'losers'>(savedFilter);
    const filterPersistTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    function setRoasFilterAndPersist(f: 'all' | 'winners' | 'losers') {
        setRoasFilter(f);
        if (filterPersistTimeout.current) clearTimeout(filterPersistTimeout.current);
        filterPersistTimeout.current = setTimeout(() => {
            axios.patch('/settings/view-preferences', {
                preferences: { campaigns: { filter: f } },
            }).catch(() => {});
        }, 400);
    }

    // Threshold: workspace target, or 1.0× break-even if no target is set.
    const roasThreshold = workspace_target_roas ?? 1.0;

    const filteredCampaigns = useMemo(() => {
        if (roasFilter === 'all') return campaigns;
        return campaigns.filter(c => {
            // Zero-spend campaigns are dormant, not losers — exclude from losers filter
            if (roasFilter === 'losers' && !c.spend) return false;
            if (c.real_roas === null) return roasFilter === 'losers'; // null ROAS = no attribution = loser
            return roasFilter === 'winners' ? c.real_roas >= roasThreshold : c.real_roas < roasThreshold;
        });
    }, [campaigns, roasFilter, roasThreshold]);

    useEffect(() => {
        const off1 = router.on('start',  () => setNavigating(true));
        const off2 = router.on('finish', () => setNavigating(false));
        return () => { off1(); off2(); };
    }, []);

    const changes = useMemo(() => ({
        roas:    pctChange(metrics?.roas    ?? null, compare_metrics?.roas    ?? null),
        cpo:     pctChange(metrics?.cpo     ?? null, compare_metrics?.cpo     ?? null),
        spend:   pctChange(metrics?.spend   ?? null, compare_metrics?.spend   ?? null),
        revenue: pctChange(metrics?.revenue ?? null, compare_metrics?.revenue ?? null),
    }), [metrics, compare_metrics]);

    // ── Param helpers ────────────────────────────────────────────────────────
    const currentParams = useMemo(() => ({
        from, to,
        ...(compare_from              ? { compare_from }                              : {}),
        ...(compare_to                ? { compare_to }                                : {}),
        ...(ad_account_ids.length > 0 ? { ad_account_ids: ad_account_ids.join(',') } : {}),
        granularity,
        platform,
        status,
        view,
        sort,
        direction,
    }), [from, to, compare_from, compare_to, ad_account_ids, granularity, platform, status, view, sort, direction]);

    function setPlatform(v: 'all' | 'facebook' | 'google') {
        // Clear ad account filter when switching platforms
        const { ad_account_ids: _removed, ...rest } = currentParams;
        navigate({ ...rest, platform: v });
    }
    function setStatus(v: 'all' | 'active' | 'paused') {
        navigate({ ...currentParams, status: v });
    }
    function toggleAdAccount(id: number) {
        const next = ad_account_ids.includes(id)
            ? ad_account_ids.filter((x) => x !== id)
            : [...ad_account_ids, id];
        const { ad_account_ids: _removed, ...rest } = currentParams;
        navigate(next.length > 0 ? { ...rest, ad_account_ids: next.join(',') } : rest);
    }
    function setView(v: 'table' | 'quadrant') {
        navigate({ ...currentParams, view: v });
    }
    function setSort(col: string) {
        const newDir = sort === col && direction === 'desc' ? 'asc' : 'desc';
        navigate({ ...currentParams, sort: col, direction: newDir });
    }

    // ── Empty state ──────────────────────────────────────────────────────────
    if (!has_ad_accounts) {
        return (
            <AppLayout dateRangePicker={<DateRangePicker />}>
                <Head title="Campaigns" />
                <PageHeader title="Campaigns" subtitle="Cross-platform ad performance" />
                <CampaignsTabBar />
                <div className="flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white px-6 py-20 text-center">
                    <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100">
                        <BarChart2 className="h-6 w-6 text-zinc-400" />
                    </div>
                    <h3 className="mb-1 text-base font-semibold text-zinc-900">No ad accounts connected</h3>
                    <p className="mb-5 max-w-xs text-sm text-zinc-500">
                        Connect a Facebook or Google Ads account to view campaign performance and ROAS.
                    </p>
                    <Link
                        href="/settings/integrations"
                        className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Connect ad accounts →
                    </Link>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout dateRangePicker={<DateRangePicker />}>
            <Head title="Campaigns" />
            <PageHeader title="Campaigns" subtitle="Cross-platform ad performance" />
            <CampaignsTabBar />

            {/* ── Ad account pills — one row per platform ── */}
            {(['facebook', 'google'] as const).map((plat) => {
                const accounts = ad_accounts.filter((a) => a.platform === plat);
                if (accounts.length === 0) return null;
                return (
                    <div key={plat} className="mb-3 flex flex-wrap items-center gap-2">
                        <span className="text-xs font-medium text-zinc-400 shrink-0">
                            {plat === 'facebook' ? 'Facebook' : 'Google'}
                        </span>
                        {accounts.length > 1 && (
                            <button
                                onClick={() => {
                                    // Deselect all accounts for this platform
                                    const otherIds = ad_account_ids.filter((id) => !accounts.some((a) => a.id === id));
                                    const { ad_account_ids: _r, ...rest } = currentParams;
                                    navigate(otherIds.length > 0 ? { ...rest, ad_account_ids: otherIds.join(',') } : rest);
                                }}
                                className={cn(
                                    'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                    accounts.every((a) => !ad_account_ids.includes(a.id))
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300',
                                )}
                            >
                                All
                            </button>
                        )}
                        {accounts.map((a) => (
                            <button
                                key={a.id}
                                onClick={() => toggleAdAccount(a.id)}
                                className={cn(
                                    'flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                    ad_account_ids.includes(a.id)
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300',
                                )}
                                title={`${a.name} — ${syncDotTitle(a.status, a.last_synced_at)}`}
                            >
                                <span className={cn('h-1.5 w-1.5 shrink-0 rounded-full', syncDotClass(a.status, a.last_synced_at))} />
                                {a.name}
                            </button>
                        ))}
                    </div>
                );
            })}

            {/* ── Filter bar ── */}
            <div className="mb-6 flex flex-wrap items-center gap-3">
                <ToggleGroup
                    options={[
                        { label: 'All', value: 'all' },
                        { label: 'Facebook', value: 'facebook' },
                        { label: 'Google', value: 'google' },
                    ]}
                    value={platform}
                    onChange={setPlatform}
                />
                <ToggleGroup
                    options={[
                        { label: 'All status', value: 'all' },
                        { label: 'Active', value: 'active' },
                        { label: 'Paused', value: 'paused' },
                    ]}
                    value={status}
                    onChange={setStatus}
                />

                <div className="ml-auto flex items-center gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-0.5">
                    <button
                        onClick={() => setView('table')}
                        title="Table view"
                        className={cn(
                            'rounded-md p-1.5 transition-colors',
                            view === 'table' ? 'bg-white shadow-sm text-zinc-800' : 'text-zinc-400 hover:text-zinc-600',
                        )}
                    >
                        <Table2 className="h-4 w-4" />
                    </button>
                    <button
                        onClick={() => setView('quadrant')}
                        title="Quadrant view"
                        className={cn(
                            'rounded-md p-1.5 transition-colors',
                            view === 'quadrant' ? 'bg-white shadow-sm text-zinc-800' : 'text-zinc-400 hover:text-zinc-600',
                        )}
                    >
                        <Grid2X2 className="h-4 w-4" />
                    </button>
                </div>

                {/* Winners / Losers chips — visible in both table and quadrant view.
                    Real ROAS ≥ target (or ≥ 1× break-even). Persisted to view_preferences. See: PLANNING.md "Winners/Losers" */}
                <div className="flex items-center gap-1">
                    {(['all', 'winners', 'losers'] as const).map(f => (
                        <button
                            key={f}
                            onClick={() => setRoasFilterAndPersist(f)}
                            className={cn(
                                'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                roasFilter === f
                                    ? f === 'winners'
                                        ? 'border-green-300 bg-green-50 text-green-700'
                                        : f === 'losers'
                                        ? 'border-red-300 bg-red-50 text-red-700'
                                        : 'border-primary bg-primary/10 text-primary'
                                    : 'border-zinc-200 text-zinc-500 hover:border-zinc-300 hover:text-zinc-700',
                            )}
                            title={
                                f === 'winners'
                                    ? `Real ROAS ≥ ${roasThreshold.toFixed(2)}×`
                                    : f === 'losers'
                                    ? `Real ROAS < ${roasThreshold.toFixed(2)}×`
                                    : 'Show all campaigns'
                            }
                        >
                            {f === 'all' ? 'All' : f === 'winners' ? '🏆 Winners' : '📉 Losers'}
                        </button>
                    ))}
                    {roasFilter !== 'all' && (
                        <span className="text-xs text-zinc-400">
                            {filteredCampaigns.length} / {campaigns.length}
                        </span>
                    )}
                </div>
            </div>

            {/* ── Revenue context cards (only when store is connected) ── */}
            {/* Why: these cards give the Platform ROAS vs Real ROAS contrast its context.
                Without seeing total store revenue next to ad-attributed revenue, the gap is invisible. */}
            {(total_revenue !== null || unattributed_revenue !== null) && (
                <div className="mb-4 grid grid-cols-2 gap-4">
                    <MetricCard
                        label="Total Store Revenue"
                        source="store"
                        value={total_revenue !== null ? formatCurrency(total_revenue, currency) : null}
                        loading={navigating}
                        tooltip="Total revenue from your store for the selected period, from daily snapshots."
                    />
                    <MetricCard
                        label="Not Tracked Revenue"
                        source="real"
                        value={unattributed_revenue !== null ? formatCurrency(unattributed_revenue, currency) : null}
                        loading={navigating}
                        tooltip="Revenue not tracked by any ad platform. Includes organic search, direct, email campaigns (Klaviyo, Mailchimp), affiliates, and any other untagged traffic. When negative, platforms over-reported — usually due to iOS14+ modeled conversions. To see email revenue separately, add utm_medium=email to your email campaigns."
                    />
                </div>
            )}

            {/* ── Metric cards ── */}
            <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <MetricCard
                    label="Blended ROAS"
                    value={metrics?.roas != null ? `${metrics.roas.toFixed(2)}×` : null}
                    change={changes.roas}
                    loading={navigating}
                    tooltip="Blended Return On Ad Spend. Total store revenue (from daily snapshots) divided by total ad spend across all platforms. Not limited to UTM-attributed orders — use Real ROAS per campaign for UTM-matched attribution."
                />
                <MetricCard
                    label="CPO"
                    value={metrics?.cpo != null ? formatCurrency(metrics.cpo, currency) : null}
                    change={changes.cpo}
                    invertTrend
                    loading={navigating}
                    tooltip="Cost Per Order. Ad spend divided by the number of orders attributed to this platform via UTM tracking. N/A when no orders have matching UTM parameters."
                />
                <MetricCard
                    label="Total Spend"
                    value={metrics?.spend != null ? formatCurrency(metrics.spend, currency) : null}
                    change={changes.spend}
                    invertTrend
                    loading={navigating}
                    tooltip="Total ad spend reported by the platform for the selected period, converted to your reporting currency."
                />
                <MetricCard
                    label="Attributed Revenue"
                    value={metrics?.attributed_revenue != null ? formatCurrency(metrics.attributed_revenue, currency) : null}
                    change={pctChange(
                        metrics?.attributed_revenue ?? null,
                        compare_metrics?.attributed_revenue ?? null,
                    )}
                    loading={navigating}
                    subtext="UTM-matched orders"
                    tooltip="Revenue from orders where utm_source matches this platform and utm_campaign matches a campaign name. Best-effort attribution — requires UTM parameters on your store links."
                />
            </div>

            {/* ── Table view ── */}
            {view === 'table' && (
                <>
                    <SpendChart
                        chartData={chart_data}
                        granularity={granularity}
                        currency={currency}
                        navigating={navigating}
                    />
                    <CampaignTable
                        campaigns={filteredCampaigns}
                        currency={currency}
                        sort={sort}
                        direction={direction}
                        onSort={setSort}
                        from={from}
                        to={to}
                    />
                </>
            )}

            {/* ── Quadrant view ── */}
            {view === 'quadrant' && (() => {
                // Filter out campaigns with no ROAS signal — quadrant needs a Y value to be useful.
                // They're still visible in the table view.
                const allMapped: QuadrantCampaign[] = filteredCampaigns.map(c => ({
                    id:                 c.id,
                    name:               c.name,
                    platform:           c.platform,
                    spend:              c.spend,
                    real_roas:          roasType === 'real' ? c.real_roas : c.platform_roas,
                    attributed_revenue: roasType === 'real' ? c.attributed_revenue : null,
                    attributed_orders:  roasType === 'real' ? c.attributed_orders  : 0,
                }));
                const quadrantData = allMapped.filter(c => c.real_roas !== null);
                const hiddenCount  = allMapped.length - quadrantData.length;
                return (
                    <div className="rounded-xl border border-zinc-200 bg-white p-5">
                        <div className="mb-3 flex items-center justify-between">
                            <div>
                                <div className="text-sm font-medium text-zinc-500">Performance quadrant</div>
                                <p className="mt-0.5 text-xs text-zinc-400">
                                    Each bubble is one campaign. X = ad spend (log), Y = ROAS, bubble size = attributed revenue.
                                </p>
                            </div>
                            {/* ROAS source toggle — Real uses UTM attribution; Platform uses ad platform's own reporting */}
                            <div className="inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5 shrink-0">
                                <button
                                    onClick={() => setRoasType('real')}
                                    className={cn(
                                        'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                                        roasType === 'real' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-400 hover:text-zinc-600',
                                    )}
                                >
                                    Real ROAS
                                </button>
                                <button
                                    onClick={() => setRoasType('platform')}
                                    className={cn(
                                        'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                                        roasType === 'platform' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-400 hover:text-zinc-600',
                                    )}
                                >
                                    Platform ROAS
                                </button>
                            </div>
                        </div>
                        {navigating ? (
                            <div className="h-[460px] w-full animate-pulse rounded-lg bg-zinc-100" />
                        ) : (
                            <QuadrantChart
                                campaigns={quadrantData}
                                currency={currency}
                                targetRoas={workspace_target_roas ?? 1.5}
                                yLabel={roasType === 'real' ? 'Real ROAS' : 'Platform ROAS'}
                                hiddenCount={hiddenCount}
                                hiddenLabel="campaigns"
                            />
                        )}
                    </div>
                );
            })()}
        </AppLayout>
    );
}
