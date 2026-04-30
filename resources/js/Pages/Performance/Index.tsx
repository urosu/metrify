/**
 * /performance — Core Web Vitals + Lighthouse speed page.
 *
 * Sections (top → bottom):
 *   1. AlertBanner — CrUX 28d field data vs lab distinction (dismissable, info)
 *   2. KpiGrid (4 cols) — Good LCP % · Good INP % · Good CLS % · Shopify Speed Score
 *   3. CWV trend LineChart — LCP / INP / CLS p75 over 12 weeks (ChartAnnotationLayer)
 *   4. In-page filter bar — device (mobile/desktop) · page type · score band · ad-traffic · CrUX-only
 *   5. URL Performance DataTable — per-URL with CrUX/Lab chip, score, CWV, ad-spend, sparkline
 *   6. LighthouseDrawer — row click → 4 score dials + opportunities + 90d trend + PSI link
 *   7. QuadrantChart — Speed Score × ROAS, bubble = ad spend (below the fold)
 *
 * Data priority: CrUX field data first (28d, real users); Lighthouse lab fallback
 * when CrUX sample < 75 origins. Each row carries `source: 'crux'|'lighthouse'`.
 *
 * Mobile tier: at <lg shows KpiGrid + desktop-only banner. Table/chart not rendered.
 *
 * Patterns copied:
 *   - Shopify: event-annotation tags on trend + Good/Needs-Improvement/Poor band line
 *   - PageSpeed Insights: "Field data" vs "Lab data" explicit labelling via source chip
 *   - Vercel Speed Insights: device toggle above table; P75 time-series; route Kanban
 *   - Plausible: sort-any-column; honest data-source labelling
 *   - Northbeam QuadrantChart: X=speed Y=ROAS bubble=spend; Quadrant 3 rose zone
 *
 * Font floor: 14px. Body 15px. WCAG AA throughout.
 * All colors via CSS vars. No hardcoded hex. No gold/amber for Real.
 *
 * @see docs/pages/performance.md
 * @see docs/competitors/_research_performance_page.md
 * @see docs/UX.md §5.1 MetricCard, §5.5 DataTable, §5.10 DrawerSidePanel, §5.6 QuadrantChart
 */

import React, { useMemo, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    Monitor,
    Smartphone,
    ExternalLink,
    Search,
    ChevronDown,
    ChevronUp,
    ChevronsUpDown,
    Gauge,
    Zap,
    Activity,
    LayoutList,
} from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { DateRangePicker } from '@/Components/shared/DateRangePicker';
import { PageHeader } from '@/Components/shared/PageHeader';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import { KpiGrid } from '@/Components/shared/KpiGrid';
import { MetricCard } from '@/Components/shared/MetricCard';
import { DrawerSidePanel } from '@/Components/shared/DrawerSidePanel';
import { EmptyState } from '@/Components/shared/EmptyState';
import { FilterChipSentence } from '@/Components/shared/FilterChipSentence';
import { Sparkline } from '@/Components/charts/Sparkline';
import { QuadrantChart } from '@/Components/charts/QuadrantChart';
import { cwvBand, CwvBand } from '@/Components/shared/CwvBand';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';
import type { QuadrantPoint } from '@/Components/charts/QuadrantChart';
import { CwvTrendChart } from './CwvTrendChart';

// ─── Types ─────────────────────────────────────────────────────────────────────

type ScoreBand = 'good' | 'needs-improvement' | 'poor';
type PageType  = 'homepage' | 'collection' | 'product' | 'cart' | 'checkout' | 'blog' | 'other';
type DataSource = 'crux' | 'lighthouse';
type Device = 'mobile' | 'desktop';

interface Audit {
    id: string;
    title: string;
    savings_ms: number | null;
    score: number;
}

interface UrlRow {
    id: number;
    url: string;
    page_type: PageType;
    speed_score: number;
    score_band: ScoreBand;
    lcp_ms: number;
    lcp_band: ScoreBand;
    inp_ms: number;
    inp_band: ScoreBand;
    cls: number;
    cls_band: ScoreBand;
    ttfb_ms: number;
    lighthouse_performance: number;
    lighthouse_accessibility: number;
    lighthouse_best_practices: number;
    lighthouse_seo: number;
    source: DataSource;
    sample_size: number | null;
    last_checked_at: string;
    ad_spend_28d: number | null;
    score_history: number[];
    audits: Audit[];
}

interface KpiData {
    label: string;
    qualifier: string;
    value: number | null;
    unit: 'pct' | null;
    delta_pct: number;
    sparkline: number[];
    source: string;
}

interface TrendPoint {
    date: string;
    lcp_p75: number;
    inp_p75: number;
    cls_p75: number;
    is_partial: boolean;
}

// QuadrantPoint is re-used from the shared chart component.
// We alias it here so the controller-provided data maps directly.
type QuadrantPointData = QuadrantPoint;

interface Annotation {
    date: string;
    name: string;
    event_type: string;
}

interface Props {
    from: string;
    to: string;
    device: Device;
    kpis: KpiData[];
    trend: TrendPoint[];
    url_rows: UrlRow[];
    quadrant_points: QuadrantPointData[];
    annotations: Annotation[];
    psi_connected: boolean;
    total_urls: number;
    crux_url_count: number;
}

// ─── Helpers ───────────────────────────────────────────────────────────────────

function fmtMs(ms: number): string {
    if (ms >= 1000) return `${(ms / 1000).toFixed(1)}s`;
    return `${Math.round(ms)}ms`;
}

function fmtCls(v: number): string { return v.toFixed(3); }

function fmtCurrency(n: number): string {
    return '$' + new Intl.NumberFormat('en').format(Math.round(n));
}

function fmtRelative(iso: string): string {
    const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (seconds < 60) return 'just now';
    const m = Math.floor(seconds / 60);
    if (m < 60) return `${m}m ago`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h}h ago`;
    return `${Math.floor(h / 24)}d ago`;
}

const PAGE_TYPE_LABELS: Record<PageType, string> = {
    homepage:   'Homepage',
    collection: 'Collection',
    product:    'Product',
    cart:       'Cart',
    checkout:   'Checkout',
    blog:       'Blog',
    other:      'Other',
};

const SCORE_BAND_STYLES: Record<ScoreBand, { dot: string; text: string }> = {
    'good':             { dot: 'bg-emerald-500', text: 'text-emerald-700' },
    'needs-improvement':{ dot: 'bg-amber-400',   text: 'text-amber-700'  },
    'poor':             { dot: 'bg-rose-500',     text: 'text-rose-700'  },
};

function ScoreCell({ score, band }: { score: number; band: ScoreBand }) {
    const { dot, text } = SCORE_BAND_STYLES[band];
    return (
        <span className={cn('inline-flex items-center gap-1.5 tabular-nums font-medium text-sm', text)}>
            <span className={cn('h-2 w-2 rounded-full shrink-0', dot)} />
            {score}
        </span>
    );
}

/** Source chip: 'crux' (emerald, field data) or 'lighthouse' (sky, lab). */
function SourceChip({ source }: { source: DataSource }) {
    if (source === 'crux') {
        return (
            <span
                className="inline-flex items-center rounded-full px-1.5 py-px text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200"
                title="CrUX: 28-day Chrome User Experience Report (real users)"
            >
                CrUX
            </span>
        );
    }
    return (
        <span
            className="inline-flex items-center rounded-full px-1.5 py-px text-xs font-medium bg-sky-50 text-sky-700 border border-sky-200"
            title="Lab: Lighthouse synthetic test (no real-user field data for this URL)"
        >
            Lab
        </span>
    );
}

/** Sortable column header */
function SortTh({
    col, label, sort, sortDir, onSort, align = 'right',
}: {
    col: string; label: string; sort: string; sortDir: 'asc' | 'desc';
    onSort: (c: string) => void; align?: 'left' | 'right';
}) {
    const active = sort === col;
    return (
        <th className={cn('px-3 py-2.5 text-xs font-semibold uppercase tracking-wide text-zinc-500', align === 'right' && 'text-right')}>
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

// ─── Lighthouse Score Dial ─────────────────────────────────────────────────────

function ScoreDial({ label, score }: { label: string; score: number }) {
    const band = score >= 90 ? 'good' : score >= 50 ? 'needs-improvement' : 'poor';
    const color = band === 'good' ? 'var(--color-success)' : band === 'needs-improvement' ? 'var(--color-warning)' : 'var(--color-danger)';
    const r = 28;
    const circ = 2 * Math.PI * r;
    const offset = circ - (score / 100) * circ;

    return (
        <div className="flex flex-col items-center gap-1">
            <div className="relative h-16 w-16">
                <svg viewBox="0 0 72 72" className="h-16 w-16 -rotate-90">
                    <circle cx="36" cy="36" r={r} fill="none" strokeWidth="6" stroke="var(--border-subtle)" />
                    <circle
                        cx="36" cy="36" r={r}
                        fill="none" strokeWidth="6"
                        stroke={color}
                        strokeDasharray={circ}
                        strokeDashoffset={offset}
                        strokeLinecap="round"
                        style={{ transition: 'stroke-dashoffset 0.6s ease' }}
                    />
                </svg>
                <span className="absolute inset-0 flex items-center justify-center text-sm font-semibold tabular-nums text-zinc-800">
                    {score}
                </span>
            </div>
            <span className="text-xs text-zinc-500 text-center leading-tight">{label}</span>
        </div>
    );
}

// ─── Lighthouse Drawer ─────────────────────────────────────────────────────────

function LighthouseDrawer({
    row,
    open,
    onClose,
}: {
    row: UrlRow | null;
    open: boolean;
    onClose: () => void;
}) {
    if (!row) return null;

    let displayPath = row.url;
    try { displayPath = new URL(row.url).pathname; } catch {}

    const historyData = row.score_history.map((v) => ({ value: v }));

    return (
        <DrawerSidePanel
            open={open}
            onClose={onClose}
            title={displayPath}
            subtitle={
                <div className="flex items-center gap-2 flex-wrap">
                    <SourceChip source={row.source} />
                    <span className="text-xs text-zinc-400">{PAGE_TYPE_LABELS[row.page_type]}</span>
                    <span className="text-xs text-zinc-400">· synced {fmtRelative(row.last_checked_at)}</span>
                </div>
            }
            headerActions={
                <a
                    href={`https://pagespeed.web.dev/report?url=${encodeURIComponent(row.url)}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1.5 rounded-md border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50 hover:text-zinc-800"
                >
                    <ExternalLink className="h-3 w-3" />
                    PageSpeed Insights
                </a>
            }
            width={540}
        >
            {/* ── Four score dials ── */}
            <div className="mb-6">
                <h3 className="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">Lighthouse scores</h3>
                <div className="flex items-center justify-around rounded-xl border border-zinc-200 bg-zinc-50 py-5">
                    <ScoreDial label="Performance" score={row.lighthouse_performance} />
                    <ScoreDial label="Accessibility" score={row.lighthouse_accessibility} />
                    <ScoreDial label="Best Practices" score={row.lighthouse_best_practices} />
                    <ScoreDial label="SEO" score={row.lighthouse_seo} />
                </div>
            </div>

            {/* ── Core Web Vitals ── */}
            <div className="mb-6">
                <h3 className="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    Core Web Vitals
                    {row.source === 'crux' && (
                        <span className="ml-2 normal-case font-normal text-zinc-400">
                            · 28-day field data {row.sample_size ? `(${new Intl.NumberFormat('en').format(row.sample_size)} sessions)` : ''}
                        </span>
                    )}
                    {row.source === 'lighthouse' && (
                        <span className="ml-2 normal-case font-normal text-zinc-400">· lab (synthetic) — no real-user data for this URL</span>
                    )}
                </h3>
                <div className="grid grid-cols-3 gap-3">
                    {([
                        { label: 'LCP', metric: 'lcp' as const, value: fmtMs(row.lcp_ms), rawValue: row.lcp_ms, threshold: '≤ 2.5s good' },
                        { label: 'INP', metric: 'inp' as const, value: fmtMs(row.inp_ms), rawValue: row.inp_ms, threshold: '≤ 200ms good' },
                        { label: 'CLS', metric: 'cls' as const, value: fmtCls(row.cls),   rawValue: row.cls,    threshold: '≤ 0.1 good' },
                    ] as const).map(({ label, metric, value, rawValue, threshold }) => (
                        <div key={label} className="rounded-lg border border-zinc-200 bg-white px-3 py-3">
                            <div className="flex items-center justify-between mb-1">
                                <span className="text-xs font-medium text-zinc-500">{label}</span>
                                <CwvBand metric={metric} value={rawValue} showLabel={false} className="h-2 w-2 rounded-full p-0" />
                            </div>
                            <div className="text-base font-semibold tabular-nums text-zinc-800">{value}</div>
                            <div className="mt-0.5 text-xs text-zinc-400">{threshold}</div>
                        </div>
                    ))}
                </div>

                {/* TTFB */}
                <div className="mt-2 flex items-center justify-between rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-2">
                    <span className="text-xs text-zinc-500">TTFB (Time to First Byte)</span>
                    <span className={cn(
                        'tabular-nums text-sm font-medium',
                        row.ttfb_ms <= 800 ? 'text-emerald-700' : row.ttfb_ms <= 1800 ? 'text-amber-700' : 'text-rose-700',
                    )}>
                        {fmtMs(row.ttfb_ms)}
                    </span>
                </div>
            </div>

            {/* ── Ad spend context ── */}
            {row.ad_spend_28d !== null && (
                <div className="mb-6 flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <div>
                        <div className="text-xs text-zinc-500">Ad spend driving this URL (28d)</div>
                        <div className="text-base font-semibold tabular-nums text-zinc-800">{fmtCurrency(row.ad_spend_28d)}</div>
                    </div>
                    {row.score_band === 'poor' && (
                        <div className="max-w-[180px] text-xs text-rose-700 bg-rose-50 rounded-md px-2 py-1.5 border border-rose-100 text-right">
                            Speed is Poor — budget may be converting below potential.
                        </div>
                    )}
                </div>
            )}

            {/* ── Score history sparkline (90d equivalent shown as 30 points) ── */}
            <div className="mb-6">
                <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Speed score over time</h3>
                <div className="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <Sparkline data={historyData} color="var(--color-primary)" height={56} mode="area" />
                    <div className="mt-1.5 flex justify-between text-xs text-zinc-400">
                        <span>30d ago</span>
                        <span>Now: {row.speed_score}</span>
                    </div>
                </div>
            </div>

            {/* ── Lighthouse opportunities ── */}
            {row.audits.length > 0 && (
                <div className="mb-4">
                    <h3 className="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        Opportunities &amp; diagnostics
                        <span className="ml-1 normal-case font-normal text-zinc-400">sorted by impact</span>
                    </h3>
                    <div className="space-y-1.5">
                        {row.audits.map((audit) => (
                            <div
                                key={audit.id}
                                className="flex items-center justify-between rounded-lg border border-zinc-100 bg-white px-3 py-2.5 gap-2"
                            >
                                <span className="text-sm text-zinc-700 flex-1 min-w-0 truncate" title={audit.title}>
                                    {audit.title}
                                </span>
                                {audit.savings_ms !== null && (
                                    <span className="shrink-0 tabular-nums text-xs font-medium text-amber-700 bg-amber-50 rounded px-1.5 py-0.5">
                                        −{fmtMs(audit.savings_ms)}
                                    </span>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </DrawerSidePanel>
    );
}

// ─── URL Filter Bar ────────────────────────────────────────────────────────────

interface Filters {
    device: Device;
    pageType: string;
    scoreBand: string;
    hasAdSpend: boolean;
    hasCrux: boolean;
    urlSearch: string;
}

function FilterBar({
    filters,
    onChange,
}: {
    filters: Filters;
    onChange: (f: Partial<Filters>) => void;
}) {
    const chipClass = (active: boolean) =>
        cn(
            'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-medium transition-colors cursor-pointer select-none',
            active
                ? 'border-transparent bg-zinc-800 text-white'
                : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:text-zinc-800',
        );

    return (
        <div className="flex flex-wrap items-center gap-2">
            {/* Device toggle — matches PSI default of mobile */}
            <div className="flex rounded-lg border border-zinc-200 overflow-hidden">
                {(['mobile', 'desktop'] as Device[]).map((d) => (
                    <button
                        key={d}
                        onClick={() => onChange({ device: d })}
                        className={cn(
                            'inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium transition-colors',
                            filters.device === d
                                ? 'bg-zinc-800 text-white'
                                : 'bg-white text-zinc-600 hover:bg-zinc-50',
                        )}
                    >
                        {d === 'mobile' ? <Smartphone className="h-3.5 w-3.5" /> : <Monitor className="h-3.5 w-3.5" />}
                        {d.charAt(0).toUpperCase() + d.slice(1)}
                    </button>
                ))}
            </div>

            {/* Score band filter */}
            {(['', 'good', 'needs-improvement', 'poor'] as const).map((band) => {
                const labels: Record<string, string> = {
                    '': 'All scores',
                    'good': 'Good',
                    'needs-improvement': 'Needs Improvement',
                    'poor': 'Poor',
                };
                return (
                    <button
                        key={band || 'all'}
                        onClick={() => onChange({ scoreBand: band })}
                        className={chipClass(filters.scoreBand === band)}
                    >
                        {band === 'good' && <span className="h-2 w-2 rounded-full bg-emerald-500" />}
                        {band === 'needs-improvement' && <span className="h-2 w-2 rounded-full bg-amber-400" />}
                        {band === 'poor' && <span className="h-2 w-2 rounded-full bg-rose-500" />}
                        {labels[band]}
                    </button>
                );
            })}

            {/* Ad-spend only */}
            <button
                onClick={() => onChange({ hasAdSpend: !filters.hasAdSpend })}
                className={chipClass(filters.hasAdSpend)}
            >
                Has Ad Spend
            </button>

            {/* CrUX field data only */}
            <button
                onClick={() => onChange({ hasCrux: !filters.hasCrux })}
                className={chipClass(filters.hasCrux)}
            >
                CrUX field data
            </button>

            {/* URL search */}
            <div className="relative ml-auto">
                <Search className="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400" />
                <input
                    type="text"
                    placeholder="Filter URLs…"
                    value={filters.urlSearch}
                    onChange={(e) => onChange({ urlSearch: e.target.value })}
                    className="w-48 rounded-lg border border-zinc-200 bg-white py-1.5 pl-8 pr-3 text-sm text-zinc-800 placeholder:text-zinc-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary/20"
                />
            </div>
        </div>
    );
}

// ─── Performance Table ─────────────────────────────────────────────────────────

function PerformanceTable({
    rows,
    onRowClick,
}: {
    rows: UrlRow[];
    onRowClick: (row: UrlRow) => void;
}) {
    const [sort, setSort] = useState('speed_score');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const sorted = useMemo(() => {
        return [...rows].sort((a, b) => {
            let av: number, bv: number;
            switch (sort) {
                case 'speed_score':  av = a.speed_score;  bv = b.speed_score;  break;
                case 'lcp_ms':       av = a.lcp_ms;       bv = b.lcp_ms;       break;
                case 'inp_ms':       av = a.inp_ms;       bv = b.inp_ms;       break;
                case 'cls':          av = a.cls;          bv = b.cls;          break;
                case 'ttfb_ms':      av = a.ttfb_ms;      bv = b.ttfb_ms;      break;
                case 'ad_spend_28d': av = a.ad_spend_28d ?? 0; bv = b.ad_spend_28d ?? 0; break;
                default:             av = a.speed_score;  bv = b.speed_score;
            }
            return sortDir === 'asc' ? av - bv : bv - av;
        });
    }, [rows, sort, sortDir]);

    function handleSort(col: string) {
        if (sort === col) {
            setSortDir((d) => (d === 'desc' ? 'asc' : 'desc'));
        } else {
            setSort(col);
            setSortDir('asc');
        }
    }

    if (rows.length === 0) {
        return (
            <div className="flex h-32 items-center justify-center text-sm text-zinc-400">
                No URLs match the current filters.
            </div>
        );
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[900px]">
                <thead className="sticky top-0 z-10 border-b border-zinc-100 bg-white">
                    <tr>
                        <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            URL
                        </th>
                        <th className="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Type
                        </th>
                        <SortTh col="speed_score"  label="Score"    sort={sort} sortDir={sortDir} onSort={handleSort} />
                        <SortTh col="lcp_ms"       label="LCP"      sort={sort} sortDir={sortDir} onSort={handleSort} />
                        <SortTh col="inp_ms"       label="INP"      sort={sort} sortDir={sortDir} onSort={handleSort} />
                        <SortTh col="cls"          label="CLS"      sort={sort} sortDir={sortDir} onSort={handleSort} />
                        <SortTh col="ttfb_ms"      label="TTFB"     sort={sort} sortDir={sortDir} onSort={handleSort} />
                        <SortTh col="ad_spend_28d" label="Ad Spend" sort={sort} sortDir={sortDir} onSort={handleSort} />
                        <th className="px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Trend
                        </th>
                        <th className="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Source
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-zinc-100">
                    {sorted.map((row) => {
                        let displayPath = row.url;
                        try { displayPath = new URL(row.url).pathname; } catch {}

                        return (
                            <tr
                                key={row.id}
                                className="cursor-pointer transition-colors hover:bg-zinc-50"
                                onClick={() => onRowClick(row)}
                            >
                                {/* URL — JetBrains Mono, middle-truncate via title */}
                                <td className="max-w-[220px] px-4 py-2.5">
                                    <div className="flex flex-col gap-0.5">
                                        <span
                                            className="block truncate font-mono text-xs text-primary"
                                            title={row.url}
                                            style={{ fontFamily: "'JetBrains Mono', monospace" }}
                                        >
                                            {displayPath}
                                        </span>
                                        {/* Open URL in new tab without triggering row click */}
                                        <a
                                            href={row.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            onClick={(e) => e.stopPropagation()}
                                            className="inline-flex items-center gap-0.5 text-xs text-zinc-400 hover:text-zinc-600 w-fit"
                                        >
                                            <ExternalLink className="h-2.5 w-2.5" />
                                            open
                                        </a>
                                    </div>
                                </td>

                                {/* Page type */}
                                <td className="px-3 py-2.5">
                                    <span className="rounded-full bg-zinc-100 px-2 py-px text-xs font-medium text-zinc-600">
                                        {PAGE_TYPE_LABELS[row.page_type]}
                                    </span>
                                </td>

                                {/* Speed score */}
                                <td className="px-3 py-2.5 text-right">
                                    <ScoreCell score={row.speed_score} band={row.score_band} />
                                </td>

                                {/* LCP */}
                                <td className="px-3 py-2.5 text-right">
                                    <div className="flex flex-col items-end gap-0.5">
                                        <span className="tabular-nums text-sm text-zinc-700">{fmtMs(row.lcp_ms)}</span>
                                        <CwvBand metric="lcp" value={row.lcp_ms} showLabel={false} className="h-1.5 w-8 rounded-full px-0 py-0" />
                                    </div>
                                </td>

                                {/* INP */}
                                <td className="px-3 py-2.5 text-right">
                                    <div className="flex flex-col items-end gap-0.5">
                                        <span className="tabular-nums text-sm text-zinc-700">{fmtMs(row.inp_ms)}</span>
                                        <CwvBand metric="inp" value={row.inp_ms} showLabel={false} className="h-1.5 w-8 rounded-full px-0 py-0" />
                                    </div>
                                </td>

                                {/* CLS */}
                                <td className="px-3 py-2.5 text-right">
                                    <div className="flex flex-col items-end gap-0.5">
                                        <span className="tabular-nums text-sm text-zinc-700">{fmtCls(row.cls)}</span>
                                        <CwvBand metric="cls" value={row.cls} showLabel={false} className="h-1.5 w-8 rounded-full px-0 py-0" />
                                    </div>
                                </td>

                                {/* TTFB */}
                                <td className="px-3 py-2.5 text-right">
                                    <span className={cn(
                                        'tabular-nums text-sm',
                                        row.ttfb_ms <= 800 ? 'text-emerald-700' : row.ttfb_ms <= 1800 ? 'text-amber-700' : 'text-rose-700',
                                    )}>
                                        {fmtMs(row.ttfb_ms)}
                                    </span>
                                </td>

                                {/* Ad Spend */}
                                <td className="px-3 py-2.5 text-right">
                                    <span className="tabular-nums text-sm text-zinc-700">
                                        {row.ad_spend_28d !== null ? fmtCurrency(row.ad_spend_28d) : '—'}
                                    </span>
                                </td>

                                {/* Score sparkline (30d) */}
                                <td className="px-3 py-2.5 text-right">
                                    <div className="flex justify-end">
                                        <Sparkline
                                            data={row.score_history.map((v) => ({ value: v }))}
                                            color={
                                                row.score_band === 'good'
                                                    ? 'var(--color-success)'
                                                    : row.score_band === 'needs-improvement'
                                                    ? 'var(--color-warning)'
                                                    : 'var(--color-danger)'
                                            }
                                            height={24}
                                            mode="line"
                                        />
                                    </div>
                                </td>

                                {/* Source */}
                                <td className="px-3 py-2.5">
                                    <SourceChip source={row.source} />
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

// ─── Mobile fallback ───────────────────────────────────────────────────────────

function MobileOnlyBanner() {
    return (
        <div className="mt-6 rounded-xl border border-zinc-200 bg-zinc-50 px-6 py-8 text-center">
            <Monitor className="mx-auto mb-3 h-8 w-8 text-zinc-300" />
            <p className="text-sm font-medium text-zinc-700">Full Performance report available on desktop</p>
            <p className="mt-1 text-sm text-zinc-500">Open Nexstage on a screen wider than 1280px to see the URL table and trend charts.</p>
        </div>
    );
}

// ─── Main page ─────────────────────────────────────────────────────────────────

export default function PerformanceIndex(props: Props) {
    const {
        from, to, kpis, trend, url_rows, quadrant_points, annotations, psi_connected,
        total_urls, crux_url_count,
    } = props;

    const { workspace } = usePage<PageProps>().props;

    // ── Filter state ──────────────────────────────────────────────────────────
    const [filters, setFilters] = useState<Filters>({
        device:     props.device ?? 'mobile',
        pageType:   '',
        scoreBand:  '',
        hasAdSpend: false,
        hasCrux:    false,
        urlSearch:  '',
    });

    function updateFilters(partial: Partial<Filters>) {
        setFilters((f) => ({ ...f, ...partial }));
    }

    // ── Drawer state ──────────────────────────────────────────────────────────
    const [drawerRow, setDrawerRow] = useState<UrlRow | null>(null);

    // ── Filtered rows (client-side — no full-page reload for filter changes) ──
    const filteredRows = useMemo(() => {
        return url_rows.filter((r) => {
            if (filters.scoreBand && r.score_band !== filters.scoreBand) return false;
            if (filters.hasAdSpend && !r.ad_spend_28d) return false;
            if (filters.hasCrux && r.source !== 'crux') return false;
            if (filters.pageType && r.page_type !== filters.pageType) return false;
            if (filters.urlSearch) {
                const q = filters.urlSearch.toLowerCase();
                if (!r.url.toLowerCase().includes(q)) return false;
            }
            return true;
        });
    }, [url_rows, filters]);

    // ── FilterChipSentence chips ───────────────────────────────────────────────
    const filterChips = useMemo(() => {
        const chips = [
            { key: 'range', label: 'Range', value: `${from} – ${to}` },
            { key: 'device', label: 'Device', value: filters.device === 'mobile' ? 'Mobile' : 'Desktop' },
        ];
        if (filters.scoreBand) {
            const labels: Record<string, string> = {
                'good': 'Good', 'needs-improvement': 'Needs Improvement', 'poor': 'Poor',
            };
            chips.push({ key: 'score_band', label: 'Score', value: labels[filters.scoreBand] ?? filters.scoreBand, removable: true } as typeof chips[0]);
        }
        if (filters.hasAdSpend) chips.push({ key: 'has_ad_spend', label: 'Filter', value: 'Has Ad Spend', removable: true } as typeof chips[0]);
        if (filters.hasCrux)    chips.push({ key: 'has_crux',     label: 'Data',   value: 'CrUX only',   removable: true } as typeof chips[0]);
        return chips;
    }, [from, to, filters]);

    function removeChip(key: string) {
        if (key === 'score_band')   updateFilters({ scoreBand: '' });
        if (key === 'has_ad_spend') updateFilters({ hasAdSpend: false });
        if (key === 'has_crux')     updateFilters({ hasCrux: false });
    }

    // ── No PSI integration ─────────────────────────────────────────────────────
    if (!psi_connected) {
        return (
            <AppLayout dateRangePicker={<DateRangePicker />}>
                <Head title="Performance" />
                <PageHeader title="Performance" subtitle="Core Web Vitals · PageSpeed Insights" />
                <EmptyState
                    icon={Gauge}
                    title="No performance data yet"
                    description="Connect Google Search Console or run a Lighthouse audit to see Core Web Vitals and speed scores for your store pages."
                    action={{
                        label: 'Set up integrations',
                        href: wurl(workspace?.slug, '/integrations'),
                    }}
                />
            </AppLayout>
        );
    }

    return (
        <AppLayout dateRangePicker={<DateRangePicker />}>
            <Head title="Performance" />

            {/* ── CrUX field vs lab banner ── */}
            <AlertBanner
                severity="info"
                message="CrUX data reflects real users over the last 28 days — not lab conditions. Lighthouse (lab) results may differ. URLs without sufficient traffic show Lab data only."
                onDismiss={() => {}}
                persistence={{ key: 'perf-crux-banner', storage: 'local' }}
            />

            <div className="px-0 py-6 space-y-6">
                <PageHeader
                    title="Performance"
                    subtitle={`Core Web Vitals · ${crux_url_count} of ${total_urls} URLs have CrUX field data`}
                />

                {/* ── FilterChipSentence §5.4 ── */}
                <FilterChipSentence
                    entity="performance data"
                    chips={filterChips}
                    onRemove={removeChip}
                />

                {/* ── KPI strip — 4 cards ── */}
                <KpiGrid cols={4}>
                    {kpis.map((kpi) => {
                        const display = kpi.value !== null
                            ? (kpi.unit === 'pct' ? `${kpi.value.toFixed(1)} %` : String(kpi.value))
                            : '—';

                        return (
                            <MetricCard
                                key={kpi.label}
                                label={`${kpi.label} (${kpi.qualifier})`}
                                value={display}
                                activeSource={kpi.source as 'gsc' | 'store'}
                                availableSources={[kpi.source as 'gsc' | 'store']}
                                change={kpi.delta_pct}
                                sparklineData={kpi.sparkline.map((v) => ({ value: v }))}
                                tooltip={
                                    kpi.label === 'Good LCP URLs'
                                        ? 'Percentage of tracked URLs with Largest Contentful Paint ≤ 2.5s in the 28-day CrUX window.'
                                        : kpi.label === 'Good INP URLs'
                                        ? 'Percentage of tracked URLs with Interaction to Next Paint ≤ 200ms in the 28-day CrUX window.'
                                        : kpi.label === 'Good CLS URLs'
                                        ? 'Percentage of tracked URLs with Cumulative Layout Shift ≤ 0.1 in the 28-day CrUX window.'
                                        : 'Shopify composite speed score (0–100) combining Lighthouse scores for homepage and top landing pages.'
                                }
                            />
                        );
                    })}
                </KpiGrid>

                {/* ── CWV trend chart ── */}
                <div className="rounded-xl border border-zinc-200 bg-white p-5">
                    <div className="mb-1 flex items-center gap-2">
                        <Activity className="h-4 w-4 text-zinc-400" />
                        <span className="text-sm font-medium text-zinc-700">Core Web Vitals trend (12 weeks)</span>
                    </div>
                    <p className="mb-3 text-xs text-zinc-400">
                        p75 field data — dotted right edge = current incomplete week. Annotations mark deploy events.
                    </p>
                    <div className="hidden lg:block">
                        <CwvTrendChart data={trend} annotations={annotations} />
                    </div>
                    <div className="lg:hidden flex h-24 items-center justify-center text-sm text-zinc-400">
                        Chart available on desktop.
                    </div>
                </div>

                {/* ── URL performance table ── */}
                <div className="rounded-xl border border-zinc-200 bg-white overflow-hidden">
                    {/* Table toolbar */}
                    <div className="border-b border-zinc-100 px-4 py-3">
                        <div className="mb-3 flex items-center justify-between gap-3">
                            <div className="flex items-center gap-2">
                                <LayoutList className="h-4 w-4 text-zinc-400" />
                                <span className="text-sm font-medium text-zinc-700">URL Performance</span>
                                <span className="text-xs text-zinc-400">
                                    {filteredRows.length} of {total_urls} URLs · default sort: worst first
                                </span>
                            </div>
                        </div>
                        <div className="hidden lg:block">
                            <FilterBar filters={filters} onChange={updateFilters} />
                        </div>
                    </div>

                    {/* Desktop: full table */}
                    <div className="hidden lg:block min-h-[300px]">
                        <PerformanceTable rows={filteredRows} onRowClick={setDrawerRow} />
                    </div>

                    {/* Mobile: simplified view */}
                    <div className="lg:hidden">
                        <MobileOnlyBanner />
                    </div>
                </div>

                {/* ── QuadrantChart — Speed × ROAS (below the fold) ── */}
                {quadrant_points.length > 0 && (
                    <div className="hidden lg:block rounded-xl border border-zinc-200 bg-white p-5">
                        <div className="mb-1 flex items-center gap-2">
                            <Zap className="h-4 w-4 text-zinc-400" />
                            <span className="text-sm font-medium text-zinc-700">Speed vs ROAS</span>
                            <span className="text-xs text-zinc-400">· bubble size = ad spend · click a bubble to highlight in table</span>
                        </div>
                        <p className="mb-3 text-xs text-zinc-400">
                            Quadrant 1 (fast + high ROAS) = keep investing. Quadrant 3 (slow + low ROAS) = fix speed before adding budget.
                        </p>
                        <QuadrantChart
                            data={quadrant_points}
                            config={{
                                xLabel: 'Speed Score',
                                yLabel: 'ROAS',
                                sizeLabel: 'Ad Spend',
                                xThreshold: 50,
                                yThreshold: 2,
                                topRightLabel:    'Fast + High ROAS',
                                topLeftLabel:     'Slow + High ROAS',
                                bottomRightLabel: 'Fast + Low ROAS',
                                bottomLeftLabel:  'Slow + Low ROAS',
                            }}
                            onDotClick={(id) => {
                                const row = url_rows.find((r) => r.id === id);
                                if (row) setDrawerRow(row);
                            }}
                        />
                    </div>
                )}
            </div>

            {/* ── Lighthouse detail drawer ── */}
            <LighthouseDrawer
                row={drawerRow}
                open={drawerRow !== null}
                onClose={() => setDrawerRow(null)}
            />
        </AppLayout>
    );
}
