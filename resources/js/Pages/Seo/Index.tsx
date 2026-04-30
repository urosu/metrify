/**
 * /seo — Google Search Console performance page.
 *
 * Sections (top → bottom):
 *   1. AlertBanner — GSC 48-72h data lag notice (dismissable, info)
 *   2. FilterChipSentence — current filter state as readable sentence
 *   3. KpiGrid (4 cols) — Clicks, Impressions, CTR, Avg Position · all GSC source
 *   4. Trend LineChart — 4 toggleable series (Plausible metric-toggle pattern)
 *      · Clicking a KPI card also activates that metric on the chart
 *   5. SubNavTabs — Queries | Pages | Countries | Devices
 *   6. Filter bar — query text, position range, country, device
 *   7. DataTable — per-tab view with per-query DrawerSidePanel on row click
 *   8. Position movers — 10 gainers + 10 biggest drops (WoW), small mover cards
 *
 * Patterns copied:
 *   - Plausible: KPI cards double as chart-metric selectors (zero chart-picker UI)
 *   - Plausible: dotted right-edge for incomplete periods (extended to GSC 48-72h lag)
 *   - GSC native: tab-based sub-nav preserving date range across tab switches
 *   - Ahrefs: position-trend Sparkline column in query table
 *   - Peel: position-movers WoW gainers/drops cards
 *
 * Source rule (HARD): only 'gsc' (emerald-500) is relevant on this page.
 * Never use 'site' — GA4 stays 'ga4'. Real is 'real'. See docs/UX.md §4.
 *
 * Font floor: 14px. Body 15px. Nothing smaller. WCAG AA throughout.
 * 4px grid. Radii ≤ 16px. No gradients, glass, neon, emoji, card shadows.
 *
 * @see docs/pages/seo.md
 * @see docs/UX.md §5.1 MetricCard
 * @see docs/UX.md §5.5 DataTable
 * @see docs/UX.md §5.10 DrawerSidePanel
 * @see docs/competitors/_inspiration_plausible.md
 */

import React, { useCallback, useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    TrendingUp,
    TrendingDown,
    Search,
    ExternalLink,
    ChevronUp,
    ChevronDown,
    ChevronsUpDown,
    Globe,
    Monitor,
    Smartphone,
    Tablet,
} from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { DateRangePicker } from '@/Components/shared/DateRangePicker';
import { PageHeader } from '@/Components/shared/PageHeader';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import { KpiGrid } from '@/Components/shared/KpiGrid';
import { MetricCard } from '@/Components/shared/MetricCard';
import { FilterChipSentence } from '@/Components/shared/FilterChipSentence';
import { SubNavTabs } from '@/Components/shared/SubNavTabs';
import { DrawerSidePanel } from '@/Components/shared/DrawerSidePanel';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PositionBucketChip } from '@/Components/shared/PositionBucketChip';
import { OpportunityBadge } from '@/Components/shared/OpportunityBadge';
import { Sparkline } from '@/Components/charts/Sparkline';
import { GscMultiSeriesChart } from '@/Components/charts/GscMultiSeriesChart';
import { formatNumber } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ─── Types ─────────────────────────────────────────────────────────────────────

interface Kpi {
    name: string;
    qualifier: string;
    value: number;
    delta_pct: number;
    source: 'gsc';
    sparkline: number[] | null;
    lower_is_better: boolean;
    unit: 'pct' | null;
}

interface TrendPoint {
    date: string;
    clicks: number;
    impressions: number;
    ctr: number;
    avg_position: number;
    is_partial: boolean;
}

interface QueryRow {
    query: string;
    clicks: number;
    impressions: number;
    ctr: number;
    position: number;
    position_trend: number[];
    best_page: string;
    is_brand: boolean;
    opportunity: string | null;
}

interface PageRow {
    page: string;
    clicks: number;
    impressions: number;
    ctr: number;
    position: number;
    top_query: string;
    position_trend: number[];
}

interface CountryRow {
    country_code: string;
    country_name: string;
    clicks: number;
    impressions: number;
    ctr: number;
    position: number;
}

interface DeviceRow {
    device: string;
    clicks: number;
    impressions: number;
    ctr: number;
    position: number;
}

interface MoverRow {
    query: string;
    position_now: number;
    position_prev: number;
    delta: number;
    clicks: number;
    impressions: number;
}

interface Props {
    tab: 'queries' | 'pages' | 'countries' | 'devices';
    from: string;
    to: string;
    sort: string;
    sort_dir: 'asc' | 'desc';
    filter_q: string | null;
    kpis: Kpi[];
    trend: TrendPoint[];
    queries: QueryRow[];
    pages: PageRow[];
    countries: CountryRow[];
    devices: DeviceRow[];
    movers_up: MoverRow[];
    movers_down: MoverRow[];
    gsc_connected: boolean;
    gsc_lag_warning: boolean;
}

// ─── Helpers ───────────────────────────────────────────────────────────────────

function fmtCtr(v: number): string {
    return `${v.toFixed(1)} %`;
}

function fmtPos(v: number): string {
    return v.toFixed(1);
}

function fmtNum(v: number): string {
    return formatNumber(v);
}

// Delta arrow + color for Avg Position (inverted: lower = better = green)
function PositionDelta({ delta }: { delta: number }) {
    const improved = delta < 0; // position dropped (lower number = higher rank)
    return (
        <span className={cn('inline-flex items-center gap-0.5 text-xs font-medium tabular-nums', improved ? 'text-emerald-600' : 'text-rose-600')}>
            {improved ? <TrendingUp className="h-3 w-3" /> : <TrendingDown className="h-3 w-3" />}
            {Math.abs(delta).toFixed(1)}
        </span>
    );
}

// Standard numeric delta (green up / red down)
function NumDelta({ delta, pct = false }: { delta: number; pct?: boolean }) {
    const positive = delta > 0;
    return (
        <span className={cn('inline-flex items-center gap-0.5 text-xs font-medium tabular-nums', positive ? 'text-emerald-600' : 'text-rose-600')}>
            {positive ? <TrendingUp className="h-3 w-3" /> : <TrendingDown className="h-3 w-3" />}
            {pct ? `${Math.abs(delta).toFixed(1)} %` : Math.abs(delta).toFixed(1)}
        </span>
    );
}

// Sortable table header cell
function SortTh({
    col, label, sort, sortDir, onSort, align = 'right',
}: {
    col: string; label: string; sort: string; sortDir: 'asc' | 'desc';
    onSort: (col: string) => void; align?: 'left' | 'right';
}) {
    const active = sort === col;
    return (
        <th className={cn('px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-zinc-500', align === 'right' && 'text-right')}>
            <button
                onClick={() => onSort(col)}
                className={cn('inline-flex items-center gap-1 transition-colors hover:text-zinc-800', active && 'text-zinc-800')}
            >
                {label}
                {active
                    ? (sortDir === 'desc' ? <ChevronDown className="h-3 w-3" /> : <ChevronUp className="h-3 w-3" />)
                    : <ChevronsUpDown className="h-3 w-3 opacity-40" />}
            </button>
        </th>
    );
}

// ─── QueryDrawer ───────────────────────────────────────────────────────────────

function QueryDrawer({ row, open, onClose }: { row: QueryRow | null; open: boolean; onClose: () => void }) {
    if (!row) return null;

    const trendData = row.position_trend.map((v) => ({ value: v }));

    return (
        <DrawerSidePanel
            open={open}
            onClose={onClose}
            title={row.query}
            subtitle={
                <span className="flex items-center gap-2 text-sm text-zinc-500">
                    <PositionBucketChip position={row.position} />
                    {row.is_brand && (
                        <span className="rounded-full bg-violet-100 px-2 py-px text-xs font-medium text-violet-700">Brand</span>
                    )}
                    {row.opportunity && <OpportunityBadge type={row.opportunity} />}
                </span>
            }
        >
            {/* 4 KPI tiles */}
            <div className="mb-6 grid grid-cols-2 gap-3">
                {[
                    { label: 'Clicks', value: fmtNum(row.clicks) },
                    { label: 'Impressions', value: fmtNum(row.impressions) },
                    { label: 'CTR', value: fmtCtr(row.ctr) },
                    { label: 'Avg Position', value: fmtPos(row.position) },
                ].map(({ label, value }) => (
                    <div key={label} className="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-3">
                        <div className="text-xs text-zinc-500">{label}</div>
                        <div className="mt-1 text-base font-semibold tabular-nums text-zinc-900">{value}</div>
                    </div>
                ))}
            </div>

            {/* Position trend sparkline */}
            <div className="mb-6">
                <div className="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500">Position trend (28d)</div>
                <div className="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-3">
                    <Sparkline
                        data={trendData}
                        color="var(--color-source-gsc-fg)"
                        height={56}
                        mode="area"
                    />
                    <div className="mt-1 flex justify-between text-xs text-zinc-400">
                        <span>{fmtPos(row.position_trend[0] ?? row.position)}</span>
                        <span className="text-zinc-500">Lower is better</span>
                        <span>{fmtPos(row.position)}</span>
                    </div>
                </div>
            </div>

            {/* Best ranking page */}
            <div className="mb-6">
                <div className="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500">Best ranking page</div>
                <a
                    href={row.best_page}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-3 text-sm text-primary hover:bg-zinc-100 transition-colors"
                >
                    <ExternalLink className="h-3.5 w-3.5 shrink-0 text-zinc-400" />
                    <span className="truncate font-mono text-xs">{row.best_page}</span>
                </a>
            </div>

            {/* Intent guess */}
            <div className="mb-4">
                <div className="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500">Search intent</div>
                <div className="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-3 text-sm text-zinc-700">
                    {row.is_brand
                        ? 'Brand navigational — user is looking for your store specifically.'
                        : row.position <= 5 && row.ctr < 5
                        ? 'Informational with CTR gap — high rank, low click-through. Meta description or title may need improvement.'
                        : row.position >= 11 && row.position <= 20
                        ? 'Striking distance — ranking on page 2. A content update or internal link could push this to page 1.'
                        : 'Commercial/transactional — user is likely comparing products or ready to buy.'}
                </div>
            </div>

            {/* Open in GSC deep-link */}
            <a
                href={`https://search.google.com/search-console/performance/search-analytics?query=${encodeURIComponent(row.query)}`}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-800 transition-colors"
            >
                <ExternalLink className="h-3.5 w-3.5" />
                Open in Google Search Console
            </a>
        </DrawerSidePanel>
    );
}

// ─── PageDrawer ────────────────────────────────────────────────────────────────

function PageDrawer({ row, open, onClose }: { row: PageRow | null; open: boolean; onClose: () => void }) {
    if (!row) return null;

    let displayPath = row.page;
    try { displayPath = new URL(row.page).pathname; } catch {}

    return (
        <DrawerSidePanel open={open} onClose={onClose} title={displayPath} subtitle="Page performance">
            <div className="mb-6 grid grid-cols-2 gap-3">
                {[
                    { label: 'Clicks', value: fmtNum(row.clicks) },
                    { label: 'Impressions', value: fmtNum(row.impressions) },
                    { label: 'CTR', value: fmtCtr(row.ctr) },
                    { label: 'Avg Position', value: fmtPos(row.position) },
                ].map(({ label, value }) => (
                    <div key={label} className="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-3">
                        <div className="text-xs text-zinc-500">{label}</div>
                        <div className="mt-1 text-base font-semibold tabular-nums text-zinc-900">{value}</div>
                    </div>
                ))}
            </div>

            <div className="mb-6">
                <div className="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500">Position trend (28d)</div>
                <div className="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-3">
                    <Sparkline
                        data={row.position_trend.map((v) => ({ value: v }))}
                        color="var(--color-source-gsc-fg)"
                        height={56}
                        mode="area"
                    />
                </div>
            </div>

            <div className="mb-4">
                <div className="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500">Top query</div>
                <div className="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                    {row.top_query}
                </div>
            </div>

            <a
                href={row.page}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-800 transition-colors"
            >
                <ExternalLink className="h-3.5 w-3.5" />
                Open page
            </a>
        </DrawerSidePanel>
    );
}

// ─── QueriesTab ────────────────────────────────────────────────────────────────

function QueriesTab({
    rows,
    sort,
    sortDir,
    filterQ,
    onSort,
    onFilterQ,
}: {
    rows: QueryRow[];
    sort: string;
    sortDir: 'asc' | 'desc';
    filterQ: string;
    onSort: (col: string) => void;
    onFilterQ: (q: string) => void;
}) {
    const [openRow, setOpenRow] = useState<QueryRow | null>(null);

    const filtered = useMemo(() => {
        if (!filterQ.trim()) return rows;
        const q = filterQ.toLowerCase();
        return rows.filter((r) => r.query.toLowerCase().includes(q));
    }, [rows, filterQ]);

    return (
        <>
            {/* Toolbar */}
            <div className="flex items-center gap-3 border-b border-zinc-100 px-4 py-3">
                <div className="relative flex-1 max-w-sm">
                    <Search className="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400" />
                    <input
                        type="text"
                        placeholder="Filter queries…"
                        value={filterQ}
                        onChange={(e) => onFilterQ(e.target.value)}
                        className="w-full rounded-md border border-zinc-200 bg-white py-1.5 pl-9 pr-3 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary/30"
                    />
                </div>
                <span className="text-xs text-zinc-400 shrink-0">{filtered.length} queries</span>
            </div>

            {filtered.length === 0 ? (
                <div className="flex h-40 items-center justify-center text-sm text-zinc-400">
                    No queries match "{filterQ}" — try a different search.
                </div>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[680px]">
                        <thead className="sticky top-0 z-10 bg-white border-b border-zinc-100">
                            <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                <th className="px-4 py-2.5 text-left">Query</th>
                                <SortTh col="clicks" label="Clicks" sort={sort} sortDir={sortDir} onSort={onSort} />
                                <SortTh col="impressions" label="Impressions" sort={sort} sortDir={sortDir} onSort={onSort} />
                                <SortTh col="ctr" label="CTR" sort={sort} sortDir={sortDir} onSort={onSort} />
                                <SortTh col="position" label="Position" sort={sort} sortDir={sortDir} onSort={onSort} />
                                <th className="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Trend</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-100">
                            {filtered.map((row) => (
                                <tr
                                    key={row.query}
                                    className="cursor-pointer transition-colors hover:bg-zinc-50"
                                    onClick={() => setOpenRow(row)}
                                >
                                    <td className="max-w-[280px] px-4 py-2.5">
                                        <div className="flex flex-col gap-1">
                                            <span className="block truncate text-sm text-zinc-800" title={row.query}>
                                                {row.query}
                                            </span>
                                            <div className="flex flex-wrap items-center gap-1">
                                                <PositionBucketChip position={row.position} />
                                                {row.is_brand && (
                                                    <span className="rounded-full bg-violet-100 px-1.5 py-px text-[10px] font-medium text-violet-700">Brand</span>
                                                )}
                                                <OpportunityBadge type={row.opportunity} />
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">
                                        {fmtNum(row.clicks)}
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">
                                        {fmtNum(row.impressions)}
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">
                                        {fmtCtr(row.ctr)}
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">
                                        {fmtPos(row.position)}
                                    </td>
                                    <td className="px-4 py-2.5 text-right">
                                        <div className="flex justify-end">
                                            <Sparkline
                                                data={row.position_trend.map((v) => ({
                                                    // Invert for visual: higher on chart = better rank (lower number)
                                                    value: 100 - v,
                                                }))}
                                                color="var(--color-source-gsc-fg)"
                                                height={24}
                                                mode="line"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <QueryDrawer row={openRow} open={openRow !== null} onClose={() => setOpenRow(null)} />
        </>
    );
}

// ─── PagesTab ──────────────────────────────────────────────────────────────────

function PagesTab({
    rows,
    sort,
    sortDir,
    onSort,
}: {
    rows: PageRow[];
    sort: string;
    sortDir: 'asc' | 'desc';
    onSort: (col: string) => void;
}) {
    const [openRow, setOpenRow] = useState<PageRow | null>(null);

    return (
        <>
            <div className="overflow-x-auto">
                <table className="w-full min-w-[720px]">
                    <thead className="sticky top-0 z-10 bg-white border-b border-zinc-100">
                        <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            <th className="px-4 py-2.5 text-left">Page</th>
                            <SortTh col="clicks" label="Clicks" sort={sort} sortDir={sortDir} onSort={onSort} />
                            <SortTh col="impressions" label="Impressions" sort={sort} sortDir={sortDir} onSort={onSort} />
                            <SortTh col="ctr" label="CTR" sort={sort} sortDir={sortDir} onSort={onSort} />
                            <SortTh col="position" label="Position" sort={sort} sortDir={sortDir} onSort={onSort} />
                            <th className="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Trend</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-100">
                        {rows.map((row) => {
                            let displayPath = row.page;
                            try { displayPath = new URL(row.page).pathname; } catch {}
                            return (
                                <tr
                                    key={row.page}
                                    className="cursor-pointer transition-colors hover:bg-zinc-50"
                                    onClick={() => setOpenRow(row)}
                                >
                                    <td className="max-w-[320px] px-4 py-2.5">
                                        <span
                                            className="block truncate font-mono text-xs text-primary"
                                            title={row.page}
                                        >
                                            {displayPath}
                                        </span>
                                        <span className="block truncate text-xs text-zinc-400 mt-0.5" title={row.top_query}>
                                            Top: {row.top_query}
                                        </span>
                                    </td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtNum(row.clicks)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtNum(row.impressions)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtCtr(row.ctr)}</td>
                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtPos(row.position)}</td>
                                    <td className="px-4 py-2.5 text-right">
                                        <div className="flex justify-end">
                                            <Sparkline
                                                data={row.position_trend.map((v) => ({ value: 100 - v }))}
                                                color="var(--color-source-gsc-fg)"
                                                height={24}
                                                mode="line"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
            <PageDrawer row={openRow} open={openRow !== null} onClose={() => setOpenRow(null)} />
        </>
    );
}

// ─── CountriesTab ──────────────────────────────────────────────────────────────

function CountriesTab({ rows }: { rows: CountryRow[] }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[520px]">
                <thead className="sticky top-0 z-10 bg-white border-b border-zinc-100">
                    <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        <th className="px-4 py-2.5 text-left">Country</th>
                        <th className="px-4 py-2.5 text-right">Clicks</th>
                        <th className="px-4 py-2.5 text-right">Impressions</th>
                        <th className="px-4 py-2.5 text-right">CTR</th>
                        <th className="px-4 py-2.5 text-right">Position</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-zinc-100">
                    {rows.map((row) => (
                        <tr key={row.country_code} className="transition-colors hover:bg-zinc-50">
                            <td className="px-4 py-2.5 text-sm text-zinc-800">
                                <span className="inline-flex items-center gap-2">
                                    <Globe className="h-3.5 w-3.5 text-zinc-400 shrink-0" />
                                    {row.country_name}
                                    <span className="text-xs text-zinc-400">{row.country_code}</span>
                                </span>
                            </td>
                            <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtNum(row.clicks)}</td>
                            <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtNum(row.impressions)}</td>
                            <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtCtr(row.ctr)}</td>
                            <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtPos(row.position)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

// ─── DevicesTab ────────────────────────────────────────────────────────────────

const DEVICE_ICON: Record<string, React.FC<{ className?: string }>> = {
    Mobile: Smartphone,
    Desktop: Monitor,
    Tablet: Tablet,
};

function DevicesTab({ rows }: { rows: DeviceRow[] }) {
    const total = rows.reduce((s, r) => s + r.clicks, 0);

    return (
        <div className="p-4 space-y-6">
            {/* Stacked bar — mobile vs desktop split */}
            <div>
                <div className="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500">Click share by device</div>
                <div className="flex h-8 overflow-hidden rounded-lg">
                    {rows.map((row, i) => {
                        const pct = total > 0 ? (row.clicks / total) * 100 : 0;
                        const colors = ['bg-emerald-500', 'bg-blue-500', 'bg-amber-400'];
                        return (
                            <div
                                key={row.device}
                                className={cn('flex items-center justify-center text-xs font-medium text-white transition-all', colors[i])}
                                style={{ width: `${pct}%` }}
                                title={`${row.device}: ${pct.toFixed(1)} %`}
                            >
                                {pct > 8 && `${pct.toFixed(0)} %`}
                            </div>
                        );
                    })}
                </div>
                <div className="mt-2 flex gap-4">
                    {rows.map((row, i) => {
                        const colors = ['text-emerald-600', 'text-blue-600', 'text-amber-600'];
                        const DeviceIcon = DEVICE_ICON[row.device] ?? Monitor;
                        const pct = total > 0 ? (row.clicks / total) * 100 : 0;
                        return (
                            <div key={row.device} className="flex items-center gap-1.5 text-xs text-zinc-500">
                                <DeviceIcon className={cn('h-3.5 w-3.5', colors[i])} />
                                <span>{row.device} {pct.toFixed(0)} %</span>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Summary table */}
            <table className="w-full">
                <thead className="border-b border-zinc-100">
                    <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        <th className="pb-2 text-left">Device</th>
                        <th className="pb-2 text-right">Clicks</th>
                        <th className="pb-2 text-right">Impressions</th>
                        <th className="pb-2 text-right">CTR</th>
                        <th className="pb-2 text-right">Avg Position</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-zinc-100">
                    {rows.map((row) => {
                        const DeviceIcon = DEVICE_ICON[row.device] ?? Monitor;
                        return (
                            <tr key={row.device} className="hover:bg-zinc-50 transition-colors">
                                <td className="py-2.5 text-sm text-zinc-800">
                                    <span className="flex items-center gap-2">
                                        <DeviceIcon className="h-3.5 w-3.5 text-zinc-400" />
                                        {row.device}
                                    </span>
                                </td>
                                <td className="py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtNum(row.clicks)}</td>
                                <td className="py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtNum(row.impressions)}</td>
                                <td className="py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtCtr(row.ctr)}</td>
                                <td className="py-2.5 text-right tabular-nums text-sm text-zinc-700">{fmtPos(row.position)}</td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

// ─── PositionMoversSection ─────────────────────────────────────────────────────

function MoverCard({ row, direction }: { row: MoverRow; direction: 'up' | 'down' }) {
    const isUp = direction === 'up';
    return (
        <div className="flex items-center justify-between gap-2 rounded-lg border border-zinc-100 bg-white px-3 py-2.5 transition-colors hover:border-zinc-200">
            <div className="min-w-0 flex-1">
                <div className="truncate text-sm text-zinc-800" title={row.query}>{row.query}</div>
                <div className="mt-0.5 text-xs text-zinc-400 tabular-nums">
                    {fmtPos(row.position_prev)} → {fmtPos(row.position_now)}
                </div>
            </div>
            <div className={cn('shrink-0 text-sm font-semibold tabular-nums', isUp ? 'text-emerald-600' : 'text-rose-600')}>
                {isUp ? '↑' : '↓'} {Math.abs(row.delta).toFixed(1)}
            </div>
        </div>
    );
}

function PositionMoversSection({ moversUp, moversDown }: { moversUp: MoverRow[]; moversDown: MoverRow[] }) {
    return (
        <div className="mt-8">
            <div className="mb-4 text-sm font-semibold text-zinc-800">Position movers (week over week)</div>
            <div className="grid gap-6 lg:grid-cols-2">
                {/* Gainers */}
                <div className="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                    <div className="mb-3 flex items-center gap-2">
                        <TrendingUp className="h-4 w-4 text-emerald-600" />
                        <span className="text-sm font-medium text-emerald-700">Biggest gainers</span>
                        <span className="text-xs text-zinc-400">position improved</span>
                    </div>
                    <div className="space-y-1.5">
                        {moversUp.map((row) => (
                            <MoverCard key={row.query} row={row} direction="up" />
                        ))}
                    </div>
                </div>
                {/* Position drops */}
                <div className="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                    <div className="mb-3 flex items-center gap-2">
                        <TrendingDown className="h-4 w-4 text-rose-600" />
                        <span className="text-sm font-medium text-rose-700">Biggest drops</span>
                        <span className="text-xs text-zinc-400">position declined</span>
                    </div>
                    <div className="space-y-1.5">
                        {moversDown.map((row) => (
                            <MoverCard key={row.query} row={row} direction="down" />
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}

// ─── Main page ─────────────────────────────────────────────────────────────────

export default function SeoIndex(props: Props) {
    const {
        tab,
        from,
        to,
        sort,
        sort_dir,
        filter_q,
        kpis,
        trend,
        queries,
        pages,
        countries,
        devices,
        movers_up,
        movers_down,
        gsc_connected,
        gsc_lag_warning,
    } = props;

    const { workspace } = usePage<PageProps>().props;

    // Active chart metric — Plausible pattern: KPI cards double as chart-metric selectors.
    // Clicking a card sets the active metric in the trend chart without any separate picker.
    const [activeMetric, setActiveMetric] = useState<'clicks' | 'impressions' | 'ctr' | 'position'>('clicks');

    // Client-side query filter (avoids full-page reload for the common case)
    const [localFilterQ, setLocalFilterQ] = useState(filter_q ?? '');

    const navigate = useCallback(
        (params: Record<string, string | undefined>) => {
            router.get(
                wurl(workspace?.slug, '/seo'),
                Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined)) as Record<string, string>,
                { preserveState: true, replace: true },
            );
        },
        [workspace?.slug],
    );

    function setTab(t: string) {
        navigate({ tab: t, from, to, sort, sort_dir });
    }

    function setSort(col: string) {
        const newDir = sort === col && sort_dir === 'desc' ? 'asc' : 'desc';
        navigate({ tab, from, to, sort: col, sort_dir: newDir });
    }

    // Chart data: the GscMultiSeriesChart expects { date, clicks, impressions, ctr, position }
    const chartData = useMemo(
        () =>
            trend.map((d) => ({
                date: d.date,
                clicks: d.clicks,
                impressions: d.impressions,
                ctr: d.ctr / 100, // raw fraction
                position: d.avg_position,
            })),
        [trend],
    );

    const tabs = [
        { label: 'Queries', value: 'queries', href: wurl(workspace?.slug, '/seo?tab=queries'), active: tab === 'queries' },
        { label: 'Pages', value: 'pages', href: wurl(workspace?.slug, '/seo?tab=pages'), active: tab === 'pages' },
        { label: 'Countries', value: 'countries', href: wurl(workspace?.slug, '/seo?tab=countries'), active: tab === 'countries' },
        { label: 'Devices', value: 'devices', href: wurl(workspace?.slug, '/seo?tab=devices'), active: tab === 'devices' },
    ];

    // ── Pre-integration empty state ────────────────────────────────────────────
    if (!gsc_connected) {
        return (
            <AppLayout dateRangePicker={<DateRangePicker />}>
                <Head title="SEO" />
                <PageHeader title="SEO" subtitle="Google Search Console performance" />
                <EmptyState
                    icon={Search}
                    title="Google Search Console not connected"
                    description="Connect GSC to see clicks, impressions, CTR and position data for every query and page on your store."
                    action={{
                        label: 'Connect Google Search Console',
                        href: wurl(workspace?.slug, '/settings/integrations'),
                    }}
                />
            </AppLayout>
        );
    }

    return (
        <AppLayout dateRangePicker={<DateRangePicker />}>
            <Head title="SEO" />

            {/* ── GSC 48-72h lag banner — per docs/pages/seo.md §AlertBanner ── */}
            {gsc_lag_warning && (
                <AlertBanner
                    severity="info"
                    message={
                        <>
                            GSC data lags ~48 hours behind Google. The last 2 days shown as dotted segments are provisional and will backfill.{' '}
                            <a href="/help/data-accuracy#gsc-lag" className="font-medium underline underline-offset-2 hover:no-underline">
                                Learn more
                            </a>
                        </>
                    }
                    onDismiss={() => {}}
                    persistence={{ key: 'seo-lag-banner', storage: 'local' }}
                />
            )}

            <div className="px-0 py-6 space-y-6">
                <PageHeader title="SEO" subtitle="Google Search Console · organic search performance" />

                {/* ── FilterChipSentence §5.4 ── */}
                <FilterChipSentence
                    entity="organic search"
                    chips={[
                        { key: 'range', label: 'Range', value: `${from} – ${to}` },
                        { key: 'country', label: 'Country', value: 'All' },
                        { key: 'device', label: 'Device', value: 'All' },
                    ]}
                />

                {/* ── KPI strip — 4 cols ── */}
                {/* Plausible pattern: clicking a card sets that metric active on the trend chart */}
                <KpiGrid cols={4}>
                    {kpis.map((kpi) => {
                        const metricKey = kpi.name === 'Avg Position' ? 'position'
                            : kpi.name === 'CTR' ? 'ctr'
                            : kpi.name === 'Impressions' ? 'impressions'
                            : 'clicks';
                        const isActive = activeMetric === metricKey;

                        // Format display value
                        let displayValue: string;
                        if (kpi.unit === 'pct') {
                            displayValue = `${kpi.value.toFixed(1)} %`;
                        } else if (kpi.value >= 1_000_000) {
                            displayValue = `${(kpi.value / 1_000_000).toFixed(1)}M`;
                        } else if (kpi.value >= 1_000) {
                            displayValue = formatNumber(kpi.value);
                        } else {
                            displayValue = kpi.value.toFixed(kpi.name === 'Avg Position' ? 1 : 0);
                        }

                        return (
                            <div
                                key={kpi.name}
                                onClick={() => setActiveMetric(metricKey as typeof activeMetric)}
                                className={cn(
                                    'cursor-pointer rounded-xl border transition-all',
                                    isActive
                                        ? 'border-emerald-400 ring-1 ring-emerald-300'
                                        : 'border-zinc-200 hover:border-zinc-300',
                                )}
                            >
                                <MetricCard
                                    label={`${kpi.name} (${kpi.qualifier})`}
                                    value={displayValue}
                                    activeSource="gsc"
                                    availableSources={['gsc']}
                                    change={kpi.delta_pct}
                                    invertTrend={kpi.lower_is_better}
                                    sparklineData={kpi.sparkline?.map((v) => ({ value: v })) ?? undefined}
                                    tooltip={
                                        kpi.name === 'Avg Position'
                                            ? 'Impression-weighted average ranking position. Lower is better — position 1 is the top Google result.'
                                            : kpi.name === 'CTR'
                                            ? 'Click-Through Rate = Σ clicks ÷ Σ impressions. Displayed as a percentage.'
                                            : undefined
                                    }
                                    className="border-0 rounded-xl"
                                />
                            </div>
                        );
                    })}
                </KpiGrid>

                {/* ── Trend chart — GSC multi-series ── */}
                <div className="rounded-xl border border-zinc-200 bg-white p-5">
                    <div className="mb-1 flex items-center gap-2">
                        <span className="text-sm font-medium text-zinc-700">Organic performance over time</span>
                        <span className="text-xs text-zinc-400">· click a metric card above to highlight</span>
                    </div>
                    <p className="mb-3 text-xs text-zinc-400">
                        Dotted right edge = provisional GSC data (48-72h lag). Not disableable — prevents over-reading the dip.
                    </p>
                    {chartData.length === 0 ? (
                        <div className="flex h-56 items-center justify-center text-sm text-zinc-400">No data for this period.</div>
                    ) : (
                        <GscMultiSeriesChart data={chartData} granularity="daily" className="w-full" />
                    )}
                </div>

                {/* ── Sub-nav tabs ── */}
                <div className="rounded-xl border border-zinc-200 bg-white overflow-hidden">
                    <SubNavTabs tabs={tabs} className="px-2 pt-1" />

                    {/* Tab content */}
                    <div className="min-h-64">
                        {tab === 'queries' && (
                            <QueriesTab
                                rows={queries}
                                sort={sort}
                                sortDir={sort_dir}
                                filterQ={localFilterQ}
                                onSort={setSort}
                                onFilterQ={setLocalFilterQ}
                            />
                        )}
                        {tab === 'pages' && (
                            <PagesTab rows={pages} sort={sort} sortDir={sort_dir} onSort={setSort} />
                        )}
                        {tab === 'countries' && <CountriesTab rows={countries} />}
                        {tab === 'devices' && <DevicesTab rows={devices} />}
                    </div>
                </div>

                {/* ── Position movers (Peel-style WoW gainers/losers) ── */}
                <PositionMoversSection moversUp={movers_up} moversDown={movers_down} />
            </div>
        </AppLayout>
    );
}
