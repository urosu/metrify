import { useEffect, useMemo, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { BarChart2, Table2, Grid2X2 } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { DateRangePicker } from '@/Components/shared/DateRangePicker';
import { PageHeader } from '@/Components/shared/PageHeader';
import { CampaignsTabBar } from '@/Components/shared/CampaignsTabBar';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { QuadrantChart, type QuadrantCampaign } from '@/Components/charts/QuadrantChart';
import { formatCurrency, formatNumber } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

interface AdRow {
    id: number;
    name: string;
    status: string | null;
    platform: string;
    adset_id: number;
    adset_name: string;
    campaign_id: number;
    campaign_name: string;
    spend: number;
    impressions: number;
    clicks: number;
    ctr: number | null;
    cpc: number | null;
    platform_roas: number | null;
    real_roas: number | null;
    attributed_revenue: number | null;
    attributed_orders: number;
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
    ads: AdRow[];
    campaign_name: string | null;
    adset_name: string | null;
    workspace_target_roas: number | null;
    from: string;
    to: string;
    platform: 'all' | 'facebook' | 'google';
    status: 'all' | 'active' | 'paused';
    view: 'table' | 'quadrant';
    sort: string;
    direction: 'asc' | 'desc';
    campaign_id: number | null;
    adset_id: number | null;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

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

function ToggleGroup<T extends string>({
    options, value, onChange,
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

function SortButton({
    col, label, currentSort, currentDir, onSort,
}: {
    col: string; label: string; currentSort: string; currentDir: 'asc' | 'desc'; onSort: (col: string) => void;
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

function navigate(params: Record<string, string | number | undefined>) {
    router.get('/campaigns/ads', params as Record<string, string>, { preserveState: true, replace: true });
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function Ads(props: Props) {
    const { workspace } = usePage<PageProps>().props;
    const currency = workspace?.reporting_currency ?? 'EUR';

    const {
        has_ad_accounts,
        ads,
        campaign_name,
        adset_name,
        workspace_target_roas,
        from, to,
        platform, status, view, sort, direction,
        campaign_id, adset_id,
    } = props;

    const [navigating, setNavigating] = useState(false);
    const [roasFilter, setRoasFilter] = useState<'all' | 'winners' | 'losers'>('all');
    const [roasType, setRoasType] = useState<'real' | 'platform'>('real');

    useEffect(() => {
        const off1 = router.on('start',  () => setNavigating(true));
        const off2 = router.on('finish', () => setNavigating(false));
        return () => { off1(); off2(); };
    }, []);

    useEffect(() => {
        setRoasFilter('all');
    }, [ads]);

    const roasThreshold = workspace_target_roas ?? 1.0;

    const filteredAds = useMemo(() => {
        if (roasFilter === 'all') return ads;
        return ads.filter(a => {
            // Zero-spend ads are dormant, not losers — exclude from losers filter
            if (roasFilter === 'losers' && !a.spend) return false;
            const roas = roasType === 'real' ? a.real_roas : a.platform_roas;
            if (roas === null) return roasFilter === 'losers';
            return roasFilter === 'winners' ? roas >= roasThreshold : roas < roasThreshold;
        });
    }, [ads, roasFilter, roasThreshold, roasType]);

    // Quadrant uses filteredAds so winners/losers chips work in both table and quadrant view.
    const quadrantData: QuadrantCampaign[] = useMemo(() => {
        return filteredAds
            .filter(a => (roasType === 'real' ? a.real_roas : a.platform_roas) !== null)
            .map(a => ({
                id:                 a.id,
                name:               a.name,
                platform:           a.platform,
                spend:              a.spend,
                real_roas:          roasType === 'real' ? a.real_roas : a.platform_roas,
                attributed_revenue: roasType === 'real' ? a.attributed_revenue : null,
                attributed_orders:  roasType === 'real' ? a.attributed_orders  : 0,
            }));
    }, [filteredAds, roasType]);
    const hiddenCount = filteredAds.filter(
        a => (roasType === 'real' ? a.real_roas : a.platform_roas) === null,
    ).length;

    const currentParams = {
        from, to, platform, status, view, sort, direction,
        ...(campaign_id ? { campaign_id: String(campaign_id) } : {}),
        ...(adset_id    ? { adset_id:    String(adset_id) }    : {}),
    };

    function setPlatform(v: 'all' | 'facebook' | 'google') {
        navigate({ ...currentParams, platform: v });
    }
    function setStatus(v: 'all' | 'active' | 'paused') {
        navigate({ ...currentParams, status: v });
    }
    function setView(v: 'table' | 'quadrant') {
        navigate({ ...currentParams, view: v });
    }
    function setSort(col: string) {
        const newDir = sort === col && direction === 'desc' ? 'asc' : 'desc';
        navigate({ ...currentParams, sort: col, direction: newDir });
    }

    const subtitle = adset_name
        ? `Ads in "${adset_name}"`
        : campaign_name
        ? `Ads in "${campaign_name}"`
        : 'Individual ad performance';

    const sortBtn = (col: string, label: string) => (
        <SortButton col={col} label={label} currentSort={sort} currentDir={direction} onSort={setSort} />
    );

    if (!has_ad_accounts) {
        return (
            <AppLayout dateRangePicker={<DateRangePicker />}>
                <Head title="Ads" />
                <PageHeader title="Campaigns" subtitle="Individual ad performance" />
                <CampaignsTabBar />
                <div className="flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white px-6 py-20 text-center">
                    <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100">
                        <BarChart2 className="h-6 w-6 text-zinc-400" />
                    </div>
                    <h3 className="mb-1 text-base font-semibold text-zinc-900">No ad accounts connected</h3>
                    <p className="mb-5 max-w-xs text-sm text-zinc-500">
                        Connect a Facebook or Google Ads account to view ad performance.
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
            <Head title="Ads" />
            <PageHeader title="Campaigns" subtitle={subtitle} />
            <CampaignsTabBar />

            {/* ── Breadcrumb — always visible ── */}
            <div className="mb-4 flex items-center gap-1.5 text-sm text-zinc-500">
                <Link href={`/campaigns?from=${from}&to=${to}`} className="hover:text-zinc-700 transition-colors">
                    Campaigns
                </Link>
                <span className="text-zinc-300">›</span>
                <Link href={`/campaigns/adsets?from=${from}&to=${to}`} className="hover:text-zinc-700 transition-colors">
                    Ad Sets
                </Link>
                {adset_id && campaign_name && (
                    <>
                        <span className="text-zinc-300">›</span>
                        <Link
                            href={`/campaigns/adsets?campaign_id=${campaign_id ?? ''}&from=${from}&to=${to}`}
                            className="hover:text-zinc-700 transition-colors"
                        >
                            {campaign_name}
                        </Link>
                    </>
                )}
                {campaign_id && !adset_id && campaign_name && (
                    <>
                        <span className="text-zinc-300">›</span>
                        <span className="font-medium text-zinc-700">{campaign_name}</span>
                    </>
                )}
                {adset_name && (
                    <>
                        <span className="text-zinc-300">›</span>
                        <span className="font-medium text-zinc-700">{adset_name}</span>
                    </>
                )}
                <span className="text-zinc-300">›</span>
                <span className="font-medium text-zinc-700">Ads</span>
            </div>

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

                {/* View toggle */}
                <div className="ml-auto inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5">
                    <button
                        onClick={() => setView('table')}
                        title="Table view"
                        className={cn(
                            'rounded-md p-1.5 transition-colors',
                            view === 'table' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-400 hover:text-zinc-600',
                        )}
                    >
                        <Table2 className="h-4 w-4" />
                    </button>
                    <button
                        onClick={() => setView('quadrant')}
                        title="Quadrant view"
                        className={cn(
                            'rounded-md p-1.5 transition-colors',
                            view === 'quadrant' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-400 hover:text-zinc-600',
                        )}
                    >
                        <Grid2X2 className="h-4 w-4" />
                    </button>
                </div>

                {/* Winners / Losers — visible in both table and quadrant view */}
                <div className="flex items-center gap-1">
                    {(['all', 'winners', 'losers'] as const).map(f => (
                        <button
                            key={f}
                            onClick={() => setRoasFilter(f)}
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
                                    ? `Platform ROAS ≥ ${roasThreshold.toFixed(2)}×`
                                    : f === 'losers'
                                    ? `Platform ROAS < ${roasThreshold.toFixed(2)}×`
                                    : 'Show all ads'
                            }
                        >
                            {f === 'all' ? 'All' : f === 'winners' ? '🏆 Winners' : '📉 Losers'}
                        </button>
                    ))}
                    {roasFilter !== 'all' && (
                        <span className="text-xs text-zinc-400">
                            {filteredAds.length} / {ads.length}
                        </span>
                    )}
                </div>
            </div>

            {/* ── Quadrant view ── */}
            {view === 'quadrant' && (
                <div className="rounded-xl border border-zinc-200 bg-white p-5">
                    <div className="mb-3 flex items-center justify-between">
                        <div>
                            <div className="text-sm font-medium text-zinc-500">Performance quadrant</div>
                            <p className="mt-0.5 text-xs text-zinc-400">
                                Each bubble is one ad. X = spend (log), Y = platform ROAS (log).
                            </p>
                        </div>
                        {/* Real ROAS uses utm_term → ad attribution; Platform uses ad platform's own reporting */}
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
                    <QuadrantChart
                        campaigns={quadrantData}
                        currency={currency}
                        targetRoas={workspace_target_roas ?? 1.5}
                        yLabel={roasType === 'real' ? 'Real ROAS' : 'Platform ROAS'}
                        hiddenCount={hiddenCount}
                        hiddenLabel="ads"
                    />
                </div>
            )}

            {/* ── Ads table ── */}
            {view === 'table' && (
            <div className="rounded-xl border border-zinc-200 bg-white">
                <div className="flex items-center border-b border-zinc-100 px-5 py-4">
                    <div className="text-sm font-medium text-zinc-500">
                        Ads
                        {ads.length > 0 && (
                            <span className="ml-2 rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500">
                                {filteredAds.length}{roasFilter !== 'all' ? ` / ${ads.length}` : ''}
                            </span>
                        )}
                    </div>
                </div>

                {filteredAds.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-16 text-center">
                        <p className="text-sm text-zinc-400">
                            {ads.length === 0
                                ? 'No ads found for this account.'
                                : `No ${roasFilter} for this period.`}
                        </p>
                        {ads.length === 0 && (
                            <p className="mt-1 text-xs text-zinc-400">
                                Ad structure is synced hourly. Check back after the next sync.
                            </p>
                        )}
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs font-medium uppercase tracking-wide text-zinc-400">
                                    <th className="px-5 py-3">Ad</th>
                                    {!adset_id && <th className="px-5 py-3">Ad Set</th>}
                                    {!campaign_id && !adset_id && <th className="px-5 py-3">Campaign</th>}
                                    <th className="px-5 py-3">Platform</th>
                                    <th className="px-5 py-3">Status</th>
                                    <th className="px-5 py-3 text-right">{sortBtn('spend', 'Spend')}</th>
                                    <th className="px-5 py-3 text-right">{sortBtn('platform_roas', 'Platform ROAS')}</th>
                                    <th className="px-5 py-3 text-right">{sortBtn('impressions', 'Impressions')}</th>
                                    <th className="px-5 py-3 text-right">{sortBtn('clicks', 'Clicks')}</th>
                                    <th className="px-5 py-3 text-right">{sortBtn('ctr', 'CTR')}</th>
                                    <th className="px-5 py-3 text-right">{sortBtn('cpc', 'CPC')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-100">
                                {filteredAds.map((a) => (
                                    <tr key={a.id} className={cn('hover:bg-zinc-50', navigating && 'opacity-60')}>
                                        <td className="max-w-[220px] px-5 py-3">
                                            <span className="block truncate font-medium text-zinc-800" title={a.name}>
                                                {a.name || '—'}
                                            </span>
                                        </td>
                                        {!adset_id && (
                                            <td className="max-w-[160px] px-5 py-3">
                                                {/* Ad set name — click to filter to this adset's ads */}
                                                <Link
                                                    href={`/campaigns/ads?adset_id=${a.adset_id}&from=${from}&to=${to}`}
                                                    className="block truncate text-zinc-600 hover:text-primary transition-colors"
                                                    title={a.adset_name}
                                                >
                                                    {a.adset_name || '—'}
                                                </Link>
                                            </td>
                                        )}
                                        {!campaign_id && !adset_id && (
                                            <td className="max-w-[160px] px-5 py-3">
                                                {/* Campaign name — click to see all adsets in that campaign */}
                                                <Link
                                                    href={`/campaigns/adsets?campaign_id=${a.campaign_id}&from=${from}&to=${to}`}
                                                    className="block truncate text-zinc-600 hover:text-primary transition-colors"
                                                    title={a.campaign_name}
                                                >
                                                    {a.campaign_name || '—'}
                                                </Link>
                                            </td>
                                        )}
                                        <td className="px-5 py-3">
                                            <PlatformBadge platform={a.platform} />
                                        </td>
                                        <td className="px-5 py-3">
                                            {a.status ? <StatusBadge status={a.status} /> : <span className="text-zinc-400">—</span>}
                                        </td>
                                        <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                            {a.spend > 0 ? formatCurrency(a.spend, currency) : <span className="text-zinc-300">—</span>}
                                        </td>
                                        <td className="px-5 py-3 text-right tabular-nums">
                                            {a.platform_roas != null ? (
                                                <span className={a.platform_roas >= roasThreshold ? 'text-green-700 font-medium' : 'text-blue-600 font-medium'}>
                                                    {a.platform_roas.toFixed(2)}×
                                                </span>
                                            ) : (
                                                <span className="text-zinc-300">—</span>
                                            )}
                                        </td>
                                        <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                            {a.impressions > 0 ? formatNumber(a.impressions) : <span className="text-zinc-300">—</span>}
                                        </td>
                                        <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                            {a.clicks > 0 ? formatNumber(a.clicks) : <span className="text-zinc-300">—</span>}
                                        </td>
                                        <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                            {a.ctr != null ? `${a.ctr.toFixed(2)}%` : <span className="text-zinc-300">—</span>}
                                        </td>
                                        <td className="px-5 py-3 text-right tabular-nums text-zinc-700">
                                            {a.cpc != null ? formatCurrency(a.cpc, currency) : <span className="text-zinc-300">—</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
            )}
        </AppLayout>
    );
}
