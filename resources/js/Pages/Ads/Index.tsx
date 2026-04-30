/**
 * Ads/Index — unified /ads page.
 *
 * Northbeam-grade hierarchy: Campaigns → Ad sets → Ads → Creatives.
 * Tab switch client-side; controller sends all four datasets.
 *
 * Sections (top → bottom):
 *   1. AlertBanner — stale-token warning
 *   2. FilterChipSentence — filter state sentence
 *   3. KPI strip — 8 MetricCards with §4.1 qualifier syntax
 *   4. SubNavTabs — Campaigns | Ad sets | Ads | Creatives (client-only tab switch)
 *   5. Table toolbar — AttributionModelSelector + WindowSelector + delta indicator
 *   6. ViewToggle — Table | Creative Gallery | Triage
 *   7. Level picker (breadcrumb-style) for Table view
 *   8. DataTable — Northbeam-pattern columns with per-source metrics + sparklines
 *   9. DrawerSidePanel — AdDetail with per-platform purchase comparison
 *  10. Below-fold charts — Spend vs Revenue LineChart + QuadrantChart
 *
 * @see docs/pages/ads.md
 * @see docs/UX.md §5.1 MetricCard, §5.5 DataTable, §5.10 DrawerSidePanel, §5.31 SubNavTabs
 * @see docs/competitors/_teardown_northbeam.md#screen-sales-page-the-power-user-page
 */

import { memo, useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Camera,
    ChevronRight,
    Copy,
    ExternalLink,
    LayoutGrid,
    List,
    MessageSquare,
    MoreHorizontal,
    Star,
    TriangleAlert,
    X,
    Zap,
} from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { DateRangePicker } from '@/Components/shared/DateRangePicker';
import { PageHeader } from '@/Components/shared/PageHeader';
import { MetricCard } from '@/Components/shared/MetricCard';
import type { MetricSource } from '@/Components/shared/MetricCard';
import { MetricCardCompact } from '@/Components/shared/MetricCard';
import { FilterChipSentence } from '@/Components/shared/FilterChipSentence';
import { LetterGradeBadge } from '@/Components/shared/LetterGradeBadge';
import type { Grade } from '@/Components/shared/LetterGradeBadge';
import { ConfidenceChip } from '@/Components/shared/ConfidenceChip';
import { SignalTypeBadge } from '@/Components/shared/SignalTypeBadge';
import type { SignalType } from '@/Components/shared/SignalTypeBadge';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { PlatformBadge } from '@/Components/shared/PlatformBadge';
import { SortButton } from '@/Components/shared/SortButton';
import { SubNavTabs } from '@/Components/shared/SubNavTabs';
import { AttributionModelSelector } from '@/Components/shared/AttributionModelSelector';
import type { AttributionModel } from '@/Components/shared/AttributionModelSelector';
import { WindowSelector } from '@/Components/shared/WindowSelector';
import type { AttributionWindow } from '@/Components/shared/WindowSelector';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import { Sparkline } from '@/Components/charts/Sparkline';
import { LineChart } from '@/Components/charts/LineChart';
import type { ChartDataPoint } from '@/Components/charts/LineChart';
import { QuadrantChart } from '@/Components/charts/QuadrantChart';
import type { QuadrantPoint } from '@/Components/charts/QuadrantChart';
import { formatCurrency, formatNumber } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

/** One row across all four tab datasets — campaigns / adsets / ads / creatives. */
interface AdEntity {
    id: string;
    name: string;
    platform: 'facebook' | 'google';
    status: 'active' | 'paused' | 'archived';
    // optional drill-through breadcrumbs
    campaign_id?: string;
    adset_id?: string;
    objective?: string;
    // metrics
    spend: number;
    impressions: number;
    clicks: number;
    ctr: number | null;
    cpc: number | null;
    cpm: number | null;
    cpa_first_time: number | null;
    cvr: number | null;
    attributed_revenue_real: number;
    attributed_revenue_platform: number;
    attributed_revenue_store: number;
    roas_real: number | null;
    roas_platform: number | null;
    roas_store: number | null;
    purchases_platform: number | null;
    orders_store: number | null;
    sparkline_roas_14d: number[];
    grade: Grade | null;
    confidence: 'high' | 'low' | null;
    signal_type?: SignalType | null;
    thumbnail_url?: string | null;
}

interface KPIs {
    total_spend: number;
    revenue_real: number;
    revenue_platform: number;
    revenue_store: number;
    roas_real: number | null;
    roas_platform: number | null;
    roas_store: number | null;
    cpm: number | null;
    cpc: number | null;
    ctr: number | null;
    cvr: number | null;
    cpa_first_time: number | null;
    purchases_platform: number;
    orders_store: number;
    not_tracked_pct: number;
}

interface ChartData {
    spend: Array<{ date: string; value: number }>;
    revenueR: Array<{ date: string; value: number }>;
    revenueP: Array<{ date: string; value: number }>;
}

interface Filters {
    from: string;
    to: string;
    platform: string;
    status: string;
    tab: string;
    attribution: string;
    window: string;
}

interface Props {
    filters: Filters;
    kpis: KPIs;
    campaigns: AdEntity[];
    adsets: AdEntity[];
    ads: AdEntity[];
    creatives: AdEntity[];
    chart_data: ChartData;
    roas_target: number;
}

// ─── Source colors (canonical — §4 UX.md) ────────────────────────────────────
const ROAS_REAL_COLOR    = 'var(--color-source-real-fg, #facc15)';
const SPEND_COLOR        = 'var(--color-source-facebook-fg, #6366f1)';
const REVENUE_REAL_COLOR = ROAS_REAL_COLOR;
const REVENUE_PLAT_COLOR = 'var(--color-source-facebook-fg, #6366f1)';

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Red→amber→green ROAS gradient.
 * Thresholds: <1 = red, 1–2 = amber, ≥2 = green.
 * This is the Northbeam color-coded cell pattern.
 */
function roasCell(roas: number | null): string {
    if (roas === null) return 'text-zinc-400';
    if (roas < 1)     return 'text-rose-600 font-semibold';
    if (roas < 2)     return 'text-amber-600 font-medium';
    return 'text-emerald-700 font-semibold';
}

/**
 * Inverse cost gradient: high cost vs median = red, low = green.
 */
function costCell(val: number | null, median: number | null): string {
    if (val === null || median === null) return 'text-zinc-500';
    if (val > median * 1.3) return 'text-rose-600 font-semibold';
    if (val < median * 0.7) return 'text-emerald-700 font-semibold';
    return 'text-amber-600 font-medium';
}

/** ROAS delta: Real vs Platform. Positive = we attribute more than platform (good). */
function DeltaChip({ real, platform }: { real: number | null; platform: number | null }) {
    if (real === null || platform === null || platform === 0) {
        return <span className="text-zinc-400 text-xs">—</span>;
    }
    const pct = ((real - platform) / platform) * 100;
    if (Math.abs(pct) < 5) return <span className="text-zinc-400 text-xs">≈</span>;
    if (real > platform) {
        return (
            <span className="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 text-xs font-medium text-emerald-700">
                ▲{Math.abs(pct).toFixed(0)}%
            </span>
        );
    }
    return (
        <span className="inline-flex items-center gap-0.5 rounded-full bg-zinc-100 border border-zinc-200 px-1.5 py-0.5 text-xs font-medium text-zinc-600"
              title="Platform reports more purchases than store recorded">
            ▼{Math.abs(pct).toFixed(0)}%
        </span>
    );
}

// ─── Ad Detail Drawer ─────────────────────────────────────────────────────────

interface DrawerProps {
    entity: AdEntity | null;
    currency: string;
    workspaceSlug: string | undefined;
    onClose: () => void;
}

const AdDetailDrawer = memo(function AdDetailDrawer({ entity, currency, workspaceSlug, onClose }: DrawerProps) {
    if (!entity) return null;

    const platformLabel = entity.platform === 'facebook' ? 'Facebook' : 'Google';
    const attrSource    = entity.platform === 'facebook' ? 'facebook' : 'google';
    const delta = entity.purchases_platform !== null && entity.orders_store !== null
        ? entity.purchases_platform - entity.orders_store : null;
    const deltaPct = delta !== null && entity.orders_store !== null && entity.orders_store > 0
        ? (delta / entity.orders_store) * 100 : null;

    return (
        <>
            <div className="fixed inset-0 z-40 bg-black/30" onClick={onClose} aria-hidden="true" />
            <div
                role="dialog"
                aria-modal="true"
                aria-label="Ad detail"
                className="fixed inset-y-0 right-0 z-50 flex w-full max-w-[480px] flex-col overflow-y-auto bg-card border-l border-border"
            >
                {/* Header */}
                <div className="flex items-start justify-between border-b border-border px-5 py-4">
                    <div className="min-w-0 flex-1 pr-4">
                        <div className="flex flex-wrap items-center gap-2 mb-1.5">
                            <PlatformBadge platform={entity.platform} />
                            <StatusBadge status={entity.status} />
                            {entity.grade && <LetterGradeBadge grade={entity.grade} size="sm" />}
                            {entity.signal_type && <SignalTypeBadge signal={entity.signal_type} />}
                        </div>
                        <p className="text-sm font-semibold text-foreground truncate" title={entity.name}>
                            {entity.name}
                        </p>
                        <p className="mt-0.5 font-mono text-xs text-zinc-400 truncate">{entity.id}</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="shrink-0 rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600"
                        aria-label="Close"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                {/* Creative thumbnail — ads/creatives only */}
                {entity.thumbnail_url && (
                    <div className="border-b border-border px-5 py-3">
                        <div className="aspect-video w-full overflow-hidden rounded-lg bg-zinc-100">
                            <img src={entity.thumbnail_url} alt={entity.name} className="h-full w-full object-cover" />
                        </div>
                    </div>
                )}

                {/* KPI 2-col compact grid */}
                <div className="grid grid-cols-2 gap-3 border-b border-border px-5 py-4">
                    {[
                        { label: 'Spend',           value: formatCurrency(entity.spend, currency) },
                        { label: 'Impressions',      value: formatNumber(entity.impressions) },
                        { label: 'Revenue (Real)',    value: formatCurrency(entity.attributed_revenue_real, currency) },
                        { label: `Revenue (${platformLabel})`, value: formatCurrency(entity.attributed_revenue_platform, currency) },
                        { label: 'ROAS (Real)',       value: entity.roas_real != null ? `${entity.roas_real.toFixed(2)}×` : 'N/A' },
                        { label: `ROAS (${platformLabel})`, value: entity.roas_platform != null ? `${entity.roas_platform.toFixed(2)}×` : 'N/A' },
                        { label: 'CPA (1st Time)',    value: entity.cpa_first_time != null ? formatCurrency(entity.cpa_first_time, currency) : 'N/A' },
                        { label: 'CTR',               value: entity.ctr != null ? `${entity.ctr.toFixed(2)}%` : 'N/A' },
                        { label: 'CPC',               value: entity.cpc != null ? formatCurrency(entity.cpc, currency) : 'N/A' },
                        { label: 'CVR',               value: entity.cvr != null ? `${entity.cvr.toFixed(2)}%` : 'N/A' },
                    ].map(({ label, value }) => (
                        <MetricCardCompact
                            key={label}
                            label={label}
                            value={value}
                            className="text-left"
                        />
                    ))}
                </div>

                {/* ROAS sparkline */}
                {entity.sparkline_roas_14d.length > 1 && (
                    <div className="border-b border-border px-5 py-3">
                        <p className="mb-1.5 text-xs font-medium text-zinc-500 uppercase tracking-wide">ROAS (Real) — last 14d</p>
                        <Sparkline
                            data={entity.sparkline_roas_14d.map((v) => ({ value: v }))}
                            color={ROAS_REAL_COLOR}
                            height={48}
                            mode="area"
                            className="w-full"
                        />
                    </div>
                )}

                {/* Platform vs store purchases comparison */}
                {entity.purchases_platform !== null && entity.orders_store !== null && (
                    <div className="border-b border-border px-5 py-4">
                        <p className="mb-2 text-xs font-semibold text-zinc-500 uppercase tracking-wide">
                            Platform purchases vs store orders
                        </p>
                        <div className="rounded-lg bg-zinc-50 border border-zinc-200 px-4 py-3 text-sm">
                            <p className="text-zinc-700">
                                <span className="font-semibold">{platformLabel}</span>{' '}
                                <span className="font-semibold tabular-nums">{formatNumber(entity.purchases_platform)}</span> purchases{' '}
                                · Store{' '}
                                <span className="font-semibold tabular-nums">{formatNumber(entity.orders_store)}</span> orders
                            </p>
                            {delta !== null && (
                                <p className={cn('mt-1 text-xs font-medium', delta > 0 ? 'text-zinc-600' : 'text-emerald-700')}>
                                    Δ = {delta > 0 ? '+' : ''}{formatNumber(delta)}
                                    {deltaPct !== null && (
                                        <> ({deltaPct > 0 ? '+' : ''}{deltaPct.toFixed(1)}%)</>
                                    )}
                                </p>
                            )}
                        </div>
                        <a
                            href={`${wurl(workspaceSlug, '/attribution')}?source=${attrSource}`}
                            className="mt-2 inline-flex items-center gap-1 text-xs text-zinc-500 hover:text-zinc-700 hover:underline"
                        >
                            <ExternalLink className="h-3 w-3" /> See in Attribution
                        </a>
                    </div>
                )}

                {/* Quick actions */}
                <div className="border-b border-border px-5 py-4">
                    <p className="mb-2 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Quick actions</p>
                    <div className="flex flex-wrap gap-2">
                        {[
                            { icon: <Copy className="h-3 w-3" />, label: 'Copy link', onClick: () => void navigator.clipboard.writeText(window.location.href) },
                            { icon: <ExternalLink className="h-3 w-3" />, label: 'Open Attribution', href: `${wurl(workspaceSlug, '/attribution')}?source=${attrSource}` },
                            { icon: <MessageSquare className="h-3 w-3" />, label: 'Add note', onClick: () => {} },
                            { icon: <Star className="h-3 w-3" />, label: 'Pin creative', onClick: () => {} },
                        ].map(({ icon, label, onClick, href }) =>
                            href ? (
                                <a
                                    key={label}
                                    href={href}
                                    className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
                                >
                                    {icon} {label}
                                </a>
                            ) : (
                                <button
                                    key={label}
                                    onClick={onClick}
                                    className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
                                >
                                    {icon} {label}
                                </button>
                            )
                        )}
                    </div>
                </div>
            </div>
        </>
    );
});

// ─── Ads DataTable ────────────────────────────────────────────────────────────

interface TableProps {
    rows: AdEntity[];
    currency: string;
    sort: string;
    direction: 'asc' | 'desc';
    onSort: (col: string) => void;
    onRowClick: (row: AdEntity) => void;
    showThumbnails?: boolean;
    /** Window label for column headers e.g. "7d" */
    windowLabel?: string;
}

/**
 * Northbeam-grade DataTable.
 * Columns: Name · Grade · Confidence · Spend · Revenue(Real) · Revenue(Platform)
 *          · ROAS(Real) · ROAS(Platform) · Δ · CPA(1st Time) · CPC · CPM · CTR · CVR
 *          · Sparkline · Actions
 *
 * Red→green gradient on ROAS and cost cells (Northbeam pattern).
 * ROAS shown as store-attributed vs platform-reported side-by-side columns.
 *
 * @see docs/competitors/_teardown_northbeam.md#screen-sales-page
 */
const AdsDataTable = memo(function AdsDataTable({
    rows, currency, sort, direction, onSort, onRowClick,
    showThumbnails = false, windowLabel = '7d',
}: TableProps) {
    const sb = (col: string, label: string) => (
        <SortButton col={col} label={label} currentSort={sort} currentDir={direction} onSort={onSort} />
    );

    // Median CPA for relative gradient coloring
    const medianCpa = useMemo(() => {
        const vals = rows.map((r) => r.cpa_first_time).filter((v): v is number => v !== null).sort((a, b) => a - b);
        if (!vals.length) return null;
        const mid = Math.floor(vals.length / 2);
        return vals.length % 2 !== 0 ? vals[mid] : (vals[mid - 1] + vals[mid]) / 2;
    }, [rows]);

    if (rows.length === 0) {
        return (
            <div className="flex h-48 items-center justify-center rounded-xl border border-border bg-card text-sm text-zinc-400">
                No data for current filters.
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-border bg-card overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full min-w-[1200px] text-sm">
                    <thead className="bg-zinc-50 border-b border-border sticky top-0 z-10">
                        <tr className="text-left">
                            {showThumbnails && <th className="w-12 px-3 py-3" aria-label="Thumbnail" />}
                            {/* Sticky first col: Name */}
                            <th className="sticky left-0 bg-zinc-50 px-4 py-3 font-semibold text-zinc-500 text-xs uppercase tracking-wide min-w-[200px]">
                                Name
                            </th>
                            <th className="px-3 py-3 font-semibold text-zinc-500 text-xs uppercase tracking-wide">Grade</th>
                            <th className="px-4 py-3 font-semibold text-zinc-500 text-xs uppercase tracking-wide text-right">
                                {sb('spend', 'Spend')}
                            </th>
                            <th className="px-4 py-3 font-semibold text-zinc-500 text-xs uppercase tracking-wide text-right">
                                <span title="Nexstage-attributed revenue (store-reconciled)">
                                    {sb('attributed_revenue_real', `Revenue (Real, ${windowLabel})`)}
                                </span>
                            </th>
                            <th className="px-4 py-3 font-semibold text-zinc-600 text-xs uppercase tracking-wide text-right">
                                <span title="Revenue claimed by the ad platform (self-reported, may over-count)">
                                    {sb('attributed_revenue_platform', 'Revenue (Platform)')}
                                </span>
                            </th>
                            <th className="px-4 py-3 font-semibold text-zinc-500 text-xs uppercase tracking-wide text-right">
                                {sb('roas_real', `ROAS (Real, ${windowLabel})`)}
                            </th>
                            <th className="px-4 py-3 font-semibold text-zinc-600 text-xs uppercase tracking-wide text-right">
                                <span title="ROAS as self-reported by the ad platform">
                                    {sb('roas_platform', 'ROAS (Platform)')}
                                </span>
                            </th>
                            <th className="px-3 py-3 font-semibold text-zinc-500 text-xs uppercase tracking-wide text-center"
                                title="Difference between platform-reported ROAS and store-attributed ROAS">
                                Δ
                            </th>
                            <th className="px-4 py-3 font-semibold text-zinc-500 text-xs uppercase tracking-wide text-right">
                                <span title="CPA (1st Time) — spend ÷ new customers acquired">
                                    {sb('cpa_first_time', `CPA (1st Time)`)}
                                </span>
                            </th>
                            <th className="px-4 py-3 font-semibold text-zinc-500 text-xs uppercase tracking-wide text-right">
                                {sb('cpc', 'CPC')}
                            </th>
                            <th className="px-4 py-3 font-semibold text-zinc-500 text-xs uppercase tracking-wide text-right">
                                {sb('ctr', 'CTR')}
                            </th>
                            <th className="px-4 py-3 font-semibold text-zinc-500 text-xs uppercase tracking-wide text-right">
                                {sb('cvr', 'CVR')}
                            </th>
                            <th className="px-4 py-3 font-semibold text-zinc-500 text-xs uppercase tracking-wide"
                                title="ROAS (Real) over the last 14 days">
                                Sparkline (14d)
                            </th>
                            <th className="px-3 py-3" aria-label="Actions" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {rows.map((row) => (
                            <tr
                                key={row.id}
                                className="cursor-pointer hover:bg-zinc-50 transition-colors"
                                onClick={() => onRowClick(row)}
                            >
                                {/* Thumbnail */}
                                {showThumbnails && (
                                    <td className="px-3 py-2">
                                        <div className="h-10 w-10 rounded-md bg-zinc-100 flex items-center justify-center overflow-hidden shrink-0">
                                            {row.thumbnail_url ? (
                                                <img src={row.thumbnail_url} alt="" className="h-full w-full object-cover" />
                                            ) : (
                                                <Camera className="h-4 w-4 text-zinc-400" />
                                            )}
                                        </div>
                                    </td>
                                )}

                                {/* Name — sticky first col */}
                                <td className="sticky left-0 bg-white px-4 py-3 min-w-[200px] border-r border-border/50">
                                    <div className="flex items-center gap-2 min-w-0">
                                        <PlatformBadge platform={row.platform} />
                                        <span className="block truncate font-medium text-foreground text-sm" title={row.name}>
                                            {row.name}
                                        </span>
                                        <span className="shrink-0"><StatusBadge status={row.status} /></span>
                                    </div>
                                </td>

                                {/* Grade + Confidence */}
                                <td className="px-3 py-3">
                                    <div className="flex flex-col items-start gap-1">
                                        {row.grade
                                            ? <LetterGradeBadge grade={row.grade} size="sm" />
                                            : <span className="text-zinc-300 text-xs">—</span>
                                        }
                                        {row.confidence === 'low' && (
                                            <ConfidenceChip sampleSize={20} metricType="orders" className="text-xs" />
                                        )}
                                    </div>
                                </td>

                                {/* Spend */}
                                <td className="px-4 py-3 text-right tabular-nums text-foreground">
                                    {row.spend > 0 ? formatCurrency(row.spend, currency) : '—'}
                                </td>

                                {/* Revenue Real */}
                                <td className="px-4 py-3 text-right tabular-nums font-medium text-foreground">
                                    {formatCurrency(row.attributed_revenue_real, currency)}
                                </td>

                                {/* Revenue Platform — indigo-600 (Facebook source color) */}
                                <td className="px-4 py-3 text-right tabular-nums text-indigo-600">
                                    {formatCurrency(row.attributed_revenue_platform, currency)}
                                </td>

                                {/* ROAS Real — color gradient */}
                                <td className="px-4 py-3 text-right">
                                    <div className="flex flex-col items-end gap-0.5">
                                        <span className={cn('tabular-nums', roasCell(row.roas_real))}>
                                            {row.roas_real != null ? `${row.roas_real.toFixed(2)}×` : '—'}
                                        </span>
                                        {row.signal_type && (
                                            <SignalTypeBadge signal={row.signal_type} />
                                        )}
                                    </div>
                                </td>

                                {/* ROAS Platform — indigo-600 */}
                                <td className="px-4 py-3 text-right tabular-nums text-indigo-600">
                                    {row.roas_platform != null ? `${row.roas_platform.toFixed(2)}×` : '—'}
                                </td>

                                {/* Delta chip */}
                                <td className="px-3 py-3 text-center">
                                    <DeltaChip real={row.roas_real} platform={row.roas_platform} />
                                </td>

                                {/* CPA (1st Time) — inverse gradient */}
                                <td className="px-4 py-3 text-right">
                                    <span className={cn('tabular-nums', costCell(row.cpa_first_time, medianCpa))}>
                                        {row.cpa_first_time != null ? formatCurrency(row.cpa_first_time, currency) : '—'}
                                    </span>
                                </td>

                                {/* CPC */}
                                <td className="px-4 py-3 text-right tabular-nums text-foreground">
                                    {row.cpc != null ? formatCurrency(row.cpc, currency) : '—'}
                                </td>

                                {/* CTR */}
                                <td className="px-4 py-3 text-right tabular-nums text-foreground">
                                    {row.ctr != null ? `${row.ctr.toFixed(2)}%` : '—'}
                                </td>

                                {/* CVR */}
                                <td className="px-4 py-3 text-right tabular-nums text-foreground">
                                    {row.cvr != null ? `${row.cvr.toFixed(2)}%` : '—'}
                                </td>

                                {/* Sparkline — 14d ROAS */}
                                <td className="px-4 py-2">
                                    <div className="w-[80px]">
                                        <Sparkline
                                            data={row.sparkline_roas_14d.map((v) => ({ value: v }))}
                                            color={ROAS_REAL_COLOR}
                                            height={32}
                                            mode="line"
                                            className="w-full"
                                        />
                                    </div>
                                </td>

                                {/* Action menu */}
                                <td className="px-3 py-3">
                                    <button
                                        onClick={(e) => { e.stopPropagation(); }}
                                        className="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600"
                                        aria-label="Row actions"
                                    >
                                        <MoreHorizontal className="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
});

// ─── Creative Card ────────────────────────────────────────────────────────────

const CreativeCard = memo(function CreativeCard({
    row, currency, onClick,
}: { row: AdEntity; currency: string; onClick: (r: AdEntity) => void }) {
    const [thumbErr, setThumbErr] = useState(false);
    const showThumb = row.thumbnail_url && !thumbErr;

    return (
        <div
            role="button"
            tabIndex={0}
            onClick={() => onClick(row)}
            onKeyDown={(e) => e.key === 'Enter' && onClick(row)}
            className={cn(
                'relative flex flex-col rounded-xl border border-border bg-card cursor-pointer',
                'hover:border-zinc-300 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                row.status !== 'active' && 'opacity-60',
            )}
        >
            {/* Grade badge — absolute top-right */}
            {row.grade && (
                <div className="absolute right-2 top-2 z-10">
                    <LetterGradeBadge grade={row.grade} size="sm" />
                </div>
            )}

            {/* Thumbnail */}
            <div className="mx-3 mt-3 mb-2 aspect-video overflow-hidden rounded-lg bg-zinc-100 flex items-center justify-center">
                {showThumb ? (
                    <img
                        src={row.thumbnail_url!}
                        alt={row.name}
                        className="h-full w-full object-cover"
                        onError={() => setThumbErr(true)}
                    />
                ) : (
                    <div className="flex flex-col items-center gap-1 text-zinc-400">
                        <Camera className="h-6 w-6" />
                        <span className="text-xs">No preview</span>
                    </div>
                )}
            </div>

            {/* Name */}
            <div className="px-3 pb-1.5">
                <p className="truncate text-sm font-medium text-foreground leading-tight" title={row.name}>
                    {row.name}
                </p>
            </div>

            {/* StatStripe — Spend · CTR · ROAS · CPA */}
            <div className="border-t border-border px-3 py-2 space-y-1">
                {[
                    { label: 'Spend', value: formatCurrency(row.spend, currency) },
                    { label: 'CTR',   value: row.ctr != null ? `${row.ctr.toFixed(2)}%` : '—' },
                    { label: 'ROAS (Real)', value: row.roas_real != null ? `${row.roas_real.toFixed(2)}×` : '—', cls: roasCell(row.roas_real) },
                    { label: 'CPA (1st Time)', value: row.cpa_first_time != null ? formatCurrency(row.cpa_first_time, currency) : '—' },
                ].map(({ label, value, cls }) => (
                    <div key={label} className="flex items-center justify-between text-xs">
                        <span className="text-zinc-400">{label}</span>
                        <span className={cn('font-medium text-foreground tabular-nums', cls)}>{value}</span>
                    </div>
                ))}
            </div>

            {/* Platform badge at foot */}
            <div className="border-t border-border px-3 py-2">
                <PlatformBadge platform={row.platform} />
            </div>
        </div>
    );
});

// ─── Creative Gallery View ────────────────────────────────────────────────────

function CreativeGalleryView({ rows, currency, onRowClick }: {
    rows: AdEntity[]; currency: string; onRowClick: (r: AdEntity) => void;
}) {
    const [plat, setPlat] = useState<'all' | 'facebook' | 'google'>('all');
    const [sort, setSort] = useState<'spend' | 'roas_real' | 'ctr'>('spend');
    const [hideInactive, setHideInactive] = useState(false);

    const filtered = useMemo(() => {
        let r = rows;
        if (plat !== 'all') r = r.filter((x) => x.platform === plat);
        if (hideInactive) r = r.filter((x) => x.status === 'active');
        return [...r].sort((a, b) => {
            if (sort === 'roas_real') return (b.roas_real ?? -Infinity) - (a.roas_real ?? -Infinity);
            if (sort === 'ctr')       return (b.ctr ?? -Infinity) - (a.ctr ?? -Infinity);
            return b.spend - a.spend;
        });
    }, [rows, plat, sort, hideInactive]);

    return (
        <div>
            {/* Toolbar */}
            <div className="mb-4 flex flex-wrap items-center gap-3">
                <div className="flex rounded-lg border border-border overflow-hidden text-xs font-medium">
                    {(['all', 'facebook', 'google'] as const).map((p) => (
                        <button
                            key={p}
                            onClick={() => setPlat(p)}
                            className={cn(
                                'px-3 py-1.5 transition-colors border-r border-border last:border-r-0',
                                plat === p ? 'bg-foreground text-background' : 'bg-card text-zinc-500 hover:text-foreground',
                            )}
                        >
                            {p === 'all' ? 'All' : p === 'facebook' ? 'Facebook' : 'Google'}
                        </button>
                    ))}
                </div>
                <select
                    value={sort}
                    onChange={(e) => setSort(e.target.value as typeof sort)}
                    className="rounded-lg border border-border bg-card px-3 py-1.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                >
                    <option value="spend">Sort: Spend</option>
                    <option value="roas_real">Sort: ROAS (Real)</option>
                    <option value="ctr">Sort: CTR</option>
                </select>
                <label className="flex items-center gap-2 text-xs text-zinc-500 cursor-pointer">
                    <input
                        type="checkbox"
                        checked={hideInactive}
                        onChange={(e) => setHideInactive(e.target.checked)}
                        className="rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                    />
                    Hide inactive
                </label>
            </div>

            {filtered.length === 0 ? (
                <div className="flex h-40 items-center justify-center rounded-xl border border-border bg-card text-sm text-zinc-400">
                    No creatives match the current filters.
                </div>
            ) : (
                <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                    {filtered.map((row) => (
                        <CreativeCard key={row.id} row={row} currency={currency} onClick={onRowClick} />
                    ))}
                </div>
            )}
        </div>
    );
}

// ─── Triage View ──────────────────────────────────────────────────────────────

/**
 * Three-column Winners / Iteration Potential / Candidates — Atria + Wicked pattern.
 * Scale / Iterate / Kill triage for rapid creative decisions.
 *
 * @see docs/pages/ads.md §Triage view
 * @see docs/competitors/_teardown_northbeam.md#screen-creative-analytics
 */
function TriageView({ rows, currency, roasTarget, onRowClick }: {
    rows: AdEntity[]; currency: string; roasTarget: number; onRowClick: (r: AdEntity) => void;
}) {
    const winners = useMemo(
        () => rows.filter((r) => r.spend > 0 && (r.roas_real ?? 0) >= roasTarget),
        [rows, roasTarget],
    );
    const iteration = useMemo(
        () => rows.filter((r) => r.spend > 0 && (r.roas_real ?? 0) > 0 && (r.roas_real ?? 0) < roasTarget),
        [rows, roasTarget],
    );
    const candidates = useMemo(
        () => rows.filter((r) => r.spend > 0 && ((r.roas_real ?? 0) === 0 || r.roas_real === null)),
        [rows],
    );

    const totalSpend = (arr: AdEntity[]) => arr.reduce((s, r) => s + r.spend, 0);

    return (
        <div>
            <p className="mb-4 text-sm text-zinc-500">
                Rows classified by ROAS (Real) vs your {roasTarget}&times; target.{' '}
                Grades A–F refine buckets when available.
            </p>
            <div className="overflow-x-auto">
                <div className="grid grid-cols-3 gap-4 min-w-[900px]">
                    {[
                        { label: 'Winners',             subtitle: 'Scale these',         rows: winners,    headerCls: 'bg-emerald-50 text-emerald-800 border-b border-emerald-200', borderCls: 'border-emerald-200' },
                        { label: 'Iteration Potential', subtitle: 'Test new creatives',  rows: iteration,  headerCls: 'bg-amber-50 text-amber-800 border-b border-amber-200',      borderCls: 'border-amber-200'   },
                        { label: 'Candidates',          subtitle: 'Investigate or pause', rows: candidates, headerCls: 'bg-rose-50 text-rose-800 border-b border-rose-200',         borderCls: 'border-rose-200'    },
                    ].map(({ label, subtitle, rows: col, headerCls, borderCls }) => (
                        <div key={label} className={cn('flex flex-col rounded-xl border bg-card overflow-hidden', borderCls)}>
                            <div className={cn('px-4 py-3', headerCls)}>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-semibold">{label}</span>
                                    <span className="text-xs font-medium opacity-70">{col.length}</span>
                                </div>
                                <p className="text-xs opacity-60 mt-0.5">
                                    Spend: {formatCurrency(totalSpend(col), currency)} · {subtitle}
                                </p>
                            </div>
                            <div className="flex flex-col gap-2 p-3">
                                {col.length === 0 ? (
                                    <p className="py-6 text-center text-sm text-zinc-400">None in this bucket</p>
                                ) : (
                                    col.map((row) => (
                                        <div
                                            key={row.id}
                                            role="button"
                                            tabIndex={0}
                                            onClick={() => onRowClick(row)}
                                            onKeyDown={(e) => e.key === 'Enter' && onRowClick(row)}
                                            className="rounded-lg border border-border bg-white p-3 cursor-pointer hover:border-zinc-300 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        >
                                            <div className="flex items-start justify-between gap-2 mb-1.5">
                                                <span className="text-xs font-medium text-foreground truncate" title={row.name}>{row.name}</span>
                                                <div className="flex items-center gap-1 shrink-0">
                                                    <PlatformBadge platform={row.platform} />
                                                    {row.grade && <LetterGradeBadge grade={row.grade} size="sm" />}
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-2 gap-x-3 gap-y-0.5 text-xs">
                                                <span className="text-zinc-400">Spend</span>
                                                <span className="tabular-nums text-right font-medium">{formatCurrency(row.spend, currency)}</span>
                                                <span className="text-zinc-400">ROAS (Real)</span>
                                                <span className={cn('tabular-nums text-right font-semibold', roasCell(row.roas_real))}>
                                                    {row.roas_real != null ? `${row.roas_real.toFixed(2)}×` : '—'}
                                                </span>
                                                {label === 'Candidates' && row.spend > 250 && (
                                                    <>
                                                        <span className="col-span-2 mt-1 text-rose-600 font-medium">
                                                            Spend {formatCurrency(row.spend, currency)} · {row.orders_store ?? 0} orders · consider pausing
                                                        </span>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

// ─── QuadrantChart adapter ────────────────────────────────────────────────────

/**
 * Maps AdEntity[] → QuadrantPoint[] for the Efficiency Map.
 * X = Spend (raw) · Y = ROAS (Real) · size = Spend · color = platform.
 * Northbeam quadrant labeling: Scale / Optimize / Cut / Test.
 */
function toQuadrantPoints(rows: AdEntity[], roasTarget: number): QuadrantPoint[] {
    return rows
        .filter((r) => r.roas_real !== null && r.spend > 0)
        .map((r) => ({
            id:    r.id,
            label: r.name || r.id,
            x:     r.spend,
            y:     r.roas_real,
            size:  r.spend,
            color: r.platform,
            meta: {
                Platform:       r.platform,
                'ROAS (Real)':  r.roas_real != null ? `${r.roas_real.toFixed(2)}×` : '—',
                'ROAS (Plat.)': r.roas_platform != null ? `${r.roas_platform.toFixed(2)}×` : '—',
                Spend:          `$${r.spend.toLocaleString()}`,
                Grade:          r.grade ?? '—',
            },
        }));
}

// ─── Main page ────────────────────────────────────────────────────────────────

type TabKey = 'campaigns' | 'adsets' | 'ads' | 'creatives';
type ViewKey = 'table' | 'creative-gallery' | 'triage';

export default function AdsIndex(props: Props & PageProps) {
    const { workspace } = usePage<PageProps>().props;
    const currency = workspace?.reporting_currency ?? 'EUR';
    const { filters, kpis, campaigns, adsets, ads, creatives, chart_data, roas_target } = props;
    const roasTarget = roas_target ?? 2;

    // ── Local state ───────────────────────────────────────────────────────────
    const [activeTab,    setActiveTab]    = useState<TabKey>(filters.tab as TabKey ?? 'campaigns');
    const [activeView,   setActiveView]   = useState<ViewKey>('table');
    const [attrModel,    setAttrModel]    = useState<AttributionModel>(filters.attribution as AttributionModel ?? 'last-non-direct-click');
    const [attrWindow,   setAttrWindow]   = useState<AttributionWindow>(filters.window as AttributionWindow ?? '7d-click');
    const [sort,         setSort]         = useState('spend');
    const [direction,    setDirection]    = useState<'asc' | 'desc'>('desc');
    const [selectedRow,  setSelectedRow]  = useState<AdEntity | null>(null);
    const [quadrantMode, setQuadrantMode] = useState<'all' | 'winners' | 'candidates' | 'fatiguing'>('all');
    const [tableFilter,  setTableFilter]  = useState<'all' | 'winners' | 'iteration' | 'candidates'>('all');

    // Window label (e.g. "7d") extracted for column headers — Northbeam pattern
    const windowLabel = attrWindow.split('-click')[0]; // "7d", "28d", "1d"

    // ── Navigation ────────────────────────────────────────────────────────────
    function navigate(patch: Record<string, string | null | undefined>) {
        const params: Record<string, string> = {};
        Object.entries(patch).forEach(([k, v]) => { if (v != null) params[k] = v; });
        router.get(
            wurl(workspace?.slug, '/ads'),
            params,
            { preserveState: true, replace: true },
        );
    }

    function handleSort(col: string) {
        const newDir = sort === col && direction === 'desc' ? 'asc' : 'desc';
        setSort(col);
        setDirection(newDir);
    }

    // ── Active rows for current tab ───────────────────────────────────────────
    const tabRows: AdEntity[] = (() => {
        switch (activeTab) {
            case 'adsets':    return adsets;
            case 'ads':       return ads;
            case 'creatives': return creatives;
            default:          return campaigns;
        }
    })();

    // ── Sorted rows ───────────────────────────────────────────────────────────
    const sortedRows = useMemo(() => {
        return [...tabRows].sort((a, b) => {
            const getV = (r: AdEntity): number => {
                switch (sort) {
                    case 'spend':                   return r.spend;
                    case 'attributed_revenue_real':  return r.attributed_revenue_real;
                    case 'attributed_revenue_platform': return r.attributed_revenue_platform;
                    case 'roas_real':               return r.roas_real ?? -Infinity;
                    case 'roas_platform':           return r.roas_platform ?? -Infinity;
                    case 'cpa_first_time':          return r.cpa_first_time ?? -Infinity;
                    case 'cpc':                     return r.cpc ?? -Infinity;
                    case 'ctr':                     return r.ctr ?? -Infinity;
                    case 'cvr':                     return r.cvr ?? -Infinity;
                    default:                        return r.spend;
                }
            };
            const av = getV(a), bv = getV(b);
            return direction === 'desc' ? bv - av : av - bv;
        });
    }, [tabRows, sort, direction]);

    // ── Triage quick-filter chips ─────────────────────────────────────────────
    const filteredRows = useMemo(() => {
        if (tableFilter === 'all') return sortedRows;
        return sortedRows.filter((r) => {
            const roas = r.roas_real ?? 0;
            if (!r.spend) return false;
            if (tableFilter === 'winners')    return roas >= roasTarget;
            if (tableFilter === 'iteration')  return roas > 0 && roas < roasTarget;
            if (tableFilter === 'candidates') return roas === 0 || r.roas_real === null;
            return true;
        });
    }, [sortedRows, tableFilter, roasTarget]);

    // ── Quadrant data ─────────────────────────────────────────────────────────
    const quadrantPoints = useMemo(() => {
        const all = toQuadrantPoints(tabRows, roasTarget);
        if (quadrantMode === 'winners')    return all.filter((p) => (p.y ?? 0) >= roasTarget);
        if (quadrantMode === 'candidates') return all.filter((p) => (p.y ?? 0) < roasTarget);
        if (quadrantMode === 'fatiguing')  return all.filter((p) => (p.y ?? 0) >= roasTarget && (p.x ?? 0) > 3000);
        return all;
    }, [tabRows, roasTarget, quadrantMode]);

    // ── Chart series ──────────────────────────────────────────────────────────
    const spendSeries:   ChartDataPoint[] = (chart_data?.spend ?? []).map((d) => ({ date: d.date, value: d.value }));
    const revRealSeries: ChartDataPoint[] = (chart_data?.revenueR ?? []).map((d) => ({ date: d.date, value: d.value }));

    // ── KPI source badges: ads page shows real, facebook, google, store ───────
    const kpiSources: MetricSource[] = ['real', 'facebook', 'google', 'store'];

    return (
        <AppLayout dateRangePicker={<DateRangePicker />}>
            <Head title="Ads" />
            <PageHeader
                title="Ads"
                subtitle={`${filters.from} — ${filters.to}`}
            />

            {/* Filter chip sentence */}
            <div className="mb-5">
                <FilterChipSentence
                    entity="ads"
                    chips={[
                        { key: 'range',    label: 'Period',   value: `${filters.from} – ${filters.to}` },
                        { key: 'platform', label: 'Platform', value: filters.platform !== 'all' ? filters.platform : 'Facebook, Google' },
                        { key: 'status',   label: 'Status',   value: filters.status   !== 'all' ? filters.status   : 'Active, Paused'  },
                    ]}
                />
            </div>

            {/* ── KPI Strip — 8 cards — §4.1 qualifier syntax ── */}
            <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-8">
                <MetricCard
                    label={`Spend (${windowLabel})`}
                    value={formatCurrency(kpis.total_spend, currency, true)}
                    activeSource="facebook"
                    availableSources={kpiSources}
                    invertTrend
                    tooltip="Total ad spend across all connected platforms for the selected period."
                />
                <MetricCard
                    label={`Revenue (Real, ${windowLabel})`}
                    value={formatCurrency(kpis.revenue_real, currency, true)}
                    activeSource="real"
                    availableSources={kpiSources}
                    tooltip="Nexstage-attributed revenue (store-reconciled). Gold lightbulb = our pick."
                    sparklineData={revRealSeries.slice(-14).map((d) => ({ value: d.value }))}
                />
                <MetricCard
                    label={`ROAS (${windowLabel}, blended)`}
                    value={kpis.roas_real != null ? `${kpis.roas_real.toFixed(2)}×` : null}
                    activeSource="real"
                    availableSources={kpiSources}
                    target={roasTarget}
                    targetLabel={`Target: ${roasTarget}×`}
                    tooltip={`Blended ROAS — Real attributed revenue ÷ total spend. Platform ROAS: ${kpis.roas_platform?.toFixed(2) ?? 'N/A'}×`}
                />
                <MetricCard
                    label="CPM"
                    value={kpis.cpm != null ? formatCurrency(kpis.cpm, currency) : null}
                    activeSource="facebook"
                    availableSources={['facebook', 'google']}
                    invertTrend
                    tooltip="Cost per 1,000 impressions — blended across platforms."
                />
                <MetricCard
                    label="CPC"
                    value={kpis.cpc != null ? formatCurrency(kpis.cpc, currency) : null}
                    activeSource="facebook"
                    availableSources={['facebook', 'google']}
                    invertTrend
                    tooltip="Cost per link click — blended across platforms."
                />
                <MetricCard
                    label={`CPA (1st Time)`}
                    value={kpis.cpa_first_time != null ? formatCurrency(kpis.cpa_first_time, currency) : null}
                    activeSource="real"
                    availableSources={kpiSources}
                    invertTrend
                    tooltip="Cost per new customer acquired. Spend ÷ first-time orders."
                />
                <MetricCard
                    label="CTR"
                    value={kpis.ctr != null ? `${kpis.ctr.toFixed(2)}%` : null}
                    activeSource="facebook"
                    availableSources={['facebook', 'google']}
                    tooltip="Click-Through Rate — link clicks ÷ impressions. Blended."
                />
                <MetricCard
                    label="Conversion Rate"
                    value={kpis.cvr != null ? `${kpis.cvr.toFixed(2)}%` : null}
                    activeSource="real"
                    availableSources={kpiSources}
                    tooltip="CVR — orders ÷ clicks. Blended across platforms."
                />
            </div>

            {/* ── Attribution toolbar — Northbeam-signature ── */}
            <div className="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-border bg-zinc-50 px-4 py-2.5">
                <span className="text-xs font-medium text-zinc-500 mr-1">Attribution:</span>
                <AttributionModelSelector value={attrModel} onChange={setAttrModel} />
                <WindowSelector value={attrWindow} onChange={setAttrWindow} />
                {/* Delta indicator — shows how ROAS shifts when model/window changes */}
                <span className="ml-2 inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                    <Zap className="h-3 w-3" />
                    Changing model/window shifts ROAS on every row
                </span>
                <div className="ml-auto flex items-center gap-2">
                    {/* ViewToggle — Table | Creative Gallery | Triage */}
                    <div className="flex items-center rounded-lg border border-border bg-card overflow-hidden text-xs font-medium">
                        {([
                            { value: 'table' as ViewKey,            icon: <List className="h-3.5 w-3.5" />,       label: 'Table'            },
                            { value: 'creative-gallery' as ViewKey,  icon: <LayoutGrid className="h-3.5 w-3.5" />, label: 'Creative Gallery'  },
                            { value: 'triage' as ViewKey,            icon: <TriangleAlert className="h-3.5 w-3.5" />, label: 'Triage'         },
                        ]).map((opt) => (
                            <button
                                key={opt.value}
                                onClick={() => setActiveView(opt.value)}
                                className={cn(
                                    'flex items-center gap-1.5 px-3 py-1.5 border-r border-border last:border-r-0 transition-colors',
                                    activeView === opt.value
                                        ? 'bg-foreground text-background'
                                        : 'text-zinc-500 hover:text-foreground',
                                )}
                                aria-pressed={activeView === opt.value}
                            >
                                {opt.icon}
                                <span className="hidden sm:inline">{opt.label}</span>
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            {/* ── SubNavTabs — Campaigns | Ad sets | Ads | Creatives ── */}
            <div className="mb-4 flex items-center border-b border-border gap-1">
                {([
                    { key: 'campaigns' as TabKey, label: 'Campaigns',  count: campaigns.length  },
                    { key: 'adsets'    as TabKey, label: 'Ad sets',    count: adsets.length     },
                    { key: 'ads'       as TabKey, label: 'Ads',        count: ads.length        },
                    { key: 'creatives' as TabKey, label: 'Creatives',  count: creatives.length  },
                ]).map((tab) => (
                    <button
                        key={tab.key}
                        onClick={() => { setActiveTab(tab.key); setTableFilter('all'); }}
                        className={cn(
                            'flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium transition-colors border-b-2',
                            activeTab === tab.key
                                ? 'border-teal-600 text-teal-700'
                                : 'border-transparent text-zinc-500 hover:text-foreground hover:border-zinc-300',
                        )}
                    >
                        {tab.label}
                        <span className={cn(
                            'rounded-full px-1.5 py-0.5 text-xs font-medium',
                            activeTab === tab.key ? 'bg-teal-100 text-teal-700' : 'bg-zinc-100 text-zinc-500',
                        )}>
                            {tab.count}
                        </span>
                    </button>
                ))}
            </div>

            {/* ── Table view ── */}
            {activeView === 'table' && (
                <>
                    {/* Triage quick-filter chips — Northbeam "Only Winners" pattern */}
                    <div className="mb-3 flex flex-wrap items-center gap-1.5">
                        {([
                            { value: 'all'        as const, label: 'All',                 cls: 'border-border text-zinc-500 hover:border-zinc-300' },
                            { value: 'winners'    as const, label: 'Only Winners',         cls: 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' },
                            { value: 'iteration'  as const, label: 'Candidates',          cls: 'border-amber-300 text-amber-700 hover:bg-amber-50' },
                            { value: 'candidates' as const, label: 'Fatiguing / No Revenue', cls: 'border-rose-300 text-rose-700 hover:bg-rose-50' },
                        ]).map(({ value, label, cls }) => (
                            <button
                                key={value}
                                onClick={() => setTableFilter(value)}
                                className={cn(
                                    'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                    tableFilter === value
                                        ? value === 'winners'    ? 'border-emerald-600 bg-emerald-100 text-emerald-800'
                                        : value === 'iteration'  ? 'border-amber-600 bg-amber-100 text-amber-800'
                                        : value === 'candidates' ? 'border-rose-600 bg-rose-100 text-rose-800'
                                        : 'border-teal-600 bg-teal-50 text-teal-700'
                                        : cn('border bg-card', cls),
                                )}
                            >
                                {label}
                                {value !== 'all' && (
                                    <span className="ml-1.5 opacity-60">
                                        {value === 'winners'    ? sortedRows.filter((r) => r.spend > 0 && (r.roas_real ?? 0) >= roasTarget).length
                                        : value === 'iteration' ? sortedRows.filter((r) => r.spend > 0 && (r.roas_real ?? 0) > 0 && (r.roas_real ?? 0) < roasTarget).length
                                        : sortedRows.filter((r) => r.spend > 0 && (r.roas_real === null || (r.roas_real ?? 0) === 0)).length}
                                    </span>
                                )}
                            </button>
                        ))}
                        {tableFilter !== 'all' && (
                            <span className="text-xs text-zinc-400 ml-1">
                                — {filteredRows.length} of {sortedRows.length} rows
                            </span>
                        )}
                    </div>

                    <AdsDataTable
                        rows={filteredRows}
                        currency={currency}
                        sort={sort}
                        direction={direction}
                        onSort={handleSort}
                        onRowClick={setSelectedRow}
                        showThumbnails={activeTab === 'ads' || activeTab === 'creatives'}
                        windowLabel={windowLabel}
                    />
                </>
            )}

            {/* ── Creative Gallery view ── */}
            {activeView === 'creative-gallery' && (
                <CreativeGalleryView
                    rows={activeTab === 'campaigns' ? campaigns : activeTab === 'adsets' ? adsets : ads}
                    currency={currency}
                    onRowClick={setSelectedRow}
                />
            )}

            {/* ── Triage view ── */}
            {activeView === 'triage' && (
                <TriageView
                    rows={activeTab === 'campaigns' ? campaigns : activeTab === 'adsets' ? adsets : ads}
                    currency={currency}
                    roasTarget={roasTarget}
                    onRowClick={setSelectedRow}
                />
            )}

            {/* ── Below-fold: Spend vs Revenue — LineChart ── */}
            <div className="mt-8 rounded-lg border border-zinc-200 bg-white p-5">
                <div className="mb-3 flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h3 className="text-sm font-semibold text-zinc-900">Spend vs Revenue over time</h3>
                        <p className="mt-0.5 text-xs text-zinc-400">
                            Daily ad spend vs attributed revenue. Dotted tail = partial period.
                        </p>
                    </div>
                </div>
                {spendSeries.length > 0 ? (
                    <LineChart
                        data={spendSeries}
                        granularity="daily"
                        currency={currency}
                        valueType="currency"
                        seriesLabel="Spend"
                        comparisonData={revRealSeries}
                        compareLabel="Revenue (Real)"
                        loading={false}
                    />
                ) : (
                    <div className="flex h-48 items-center justify-center text-sm text-zinc-400">
                        No chart data for selected range.
                    </div>
                )}
            </div>

            {/* ── Below-fold: Ad Efficiency Map — QuadrantChart ── */}
            <div className="mt-6 rounded-xl border border-border bg-card p-5">
                <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 className="text-sm font-semibold text-foreground">Ad Efficiency Map</h3>
                        <p className="mt-0.5 text-sm text-zinc-500">
                            X = Spend · Y = ROAS (Real) · size = Spend · color = platform.
                            Quadrants: <span className="text-emerald-700 font-medium">Scale</span> ·{' '}
                            <span className="text-amber-700 font-medium">Optimize</span> ·{' '}
                            <span className="text-rose-600 font-medium">Cut</span> ·{' '}
                            <span className="text-blue-600 font-medium">Test</span>
                        </p>
                    </div>
                    {/* Northbeam quick-filter buttons */}
                    <div className="flex flex-wrap items-center gap-1.5">
                        {([
                            { value: 'all'        as const, label: 'All'             },
                            { value: 'winners'    as const, label: 'Only Winners'    },
                            { value: 'candidates' as const, label: 'Only Candidates' },
                            { value: 'fatiguing'  as const, label: 'Fatiguing'       },
                        ]).map((opt) => (
                            <button
                                key={opt.value}
                                onClick={() => setQuadrantMode(opt.value)}
                                className={cn(
                                    'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                    quadrantMode === opt.value
                                        ? 'border-teal-600 bg-teal-50 text-teal-700'
                                        : 'border-border bg-card text-zinc-500 hover:border-zinc-300',
                                )}
                            >
                                {opt.label}
                            </button>
                        ))}
                    </div>
                </div>

                <QuadrantChart
                    data={quadrantPoints}
                    config={{
                        xLabel: 'Spend',
                        yLabel: `ROAS (Real, ${windowLabel})`,
                        sizeLabel: 'Spend',
                        xFormatter: (v: number) => formatCurrency(v, currency, true),
                        yFormatter: (v: number | null) => v != null ? `${v.toFixed(2)}×` : 'N/A',
                        yThreshold: roasTarget,
                        yThresholdLabel: `ROAS target: ${roasTarget}×`,
                        topRightLabel:    'Scale',
                        topLeftLabel:     'Optimize',
                        bottomRightLabel: 'Cut',
                        bottomLeftLabel:  'Test',
                        colorMode: 'quadrant',
                    }}
                    onDotClick={(id) => {
                        const found = tabRows.find((r) => String(r.id) === String(id));
                        if (found) setSelectedRow(found);
                    }}
                />
            </div>

            {/* ── Ad Detail DrawerSidePanel ── */}
            <AdDetailDrawer
                entity={selectedRow}
                currency={currency}
                workspaceSlug={workspace?.slug}
                onClose={() => setSelectedRow(null)}
            />
        </AppLayout>
    );
}
