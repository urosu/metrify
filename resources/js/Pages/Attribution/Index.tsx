/**
 * Attribution/Index — per-platform sales breakdown page.
 *
 * Shows how sales attribute across connected platforms in clean, neutral framing.
 * No "disagreement" framing — just useful business metrics per source.
 *
 * Sections (top → bottom):
 *   1. Page header — "Attribution" + subtitle
 *   2. Attribution model + window selectors (inline chip group)
 *   3. Per-platform cards grid (Store, Facebook, Google, GA4, GSC, Unattributed)
 *      — each shows attributed revenue, orders, CAC, sparkline share-of-total
 *   4. Per-platform revenue line chart (30-day trend, 5 lines)
 *   5. Cross-platform overlap table (secondary, de-emphasized)
 *   6. Tracking Health strip
 *
 * @see docs/pages/attribution.md
 * @see docs/UX.md §5.1 MetricCard
 */

import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Play, Pause, ChevronDown, ChevronUp,
} from 'lucide-react';
import { useRef, useEffect } from 'react';
import AppLayout from '@/Components/layouts/AppLayout';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import { SourceBadge } from '@/Components/shared/SourceBadge';
import type { MetricSource } from '@/Components/shared/SourceBadge';
import { SignalTypeBadge } from '@/Components/shared/SignalTypeBadge';
import { cn } from '@/lib/utils';
import { formatCurrency, formatDateOnly } from '@/lib/formatters';
import type { PageProps } from '@/types';
import {
    LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend,
    ResponsiveContainer,
} from 'recharts';

// ─── Source colors (fg tokens from CSS, for chart lines) ──────────────────────

const SRC_HEX: Record<string, string> = {
    store:    '#64748b', // slate-500
    facebook: '#3b82f6', // blue-500
    google:   '#0d9488', // teal-600
    ga4:      '#f97316', // orange-500
    gsc:      '#10b981', // emerald-500
};

const SOURCE_LABELS: Record<string, string> = {
    store: 'Store', facebook: 'Facebook',
    google: 'Google', ga4: 'GA4', gsc: 'GSC',
};

const PLATFORM_SOURCES: MetricSource[] = ['store', 'facebook', 'google', 'ga4', 'gsc'];

// ─── Prop types ────────────────────────────────────────────────────────────────

interface SourceRow {
    source: string;
    value: number | null;
    orders: number | null;
    cac: number | null;
    is_reconciled: boolean;
    note: string | null;
}

interface TrendPoint {
    date: string;
    real: number;
    store: number;
    facebook: number;
    google: number;
    ga4: number;
    gsc: number | null;
}

interface TimeMachineFrame {
    date: string;
    real: number;
    store: number;
    facebook: number;
    google: number;
    ga4: number;
    not_tracked: number;
    attribution_model: string;
}

interface TrackingHealthRow {
    source: string;
    match_quality: number | null;
    events_sent: number | null;
    events_matched: number | null;
    consent_denied_pct: number | null;
    status: string;
}

interface Filters {
    model: string;
    window: string;
    mode: string;
    from: string;
    to: string;
    breakdown: string | null;
    source: string;
}

interface Props {
    trust_bar: {
        real_revenue: number;
        currency: string;
        period: string;
        disagreement_vs_store_pct: number;
        disagreement_vs_platforms_pct: number;
        not_tracked_bucket: number;
        confidence: string;
    };
    sources: SourceRow[];
    source_keys: string[];
    disagreement_matrix: Record<string, Record<string, number>>;
    trend_series: TrendPoint[];
    time_machine: TimeMachineFrame[];
    top_discrepancies: unknown[];
    tracking_health: TrackingHealthRow[];
    attribution_models: string[];
    windows: string[];
    is_recomputing: boolean;
    filters: Filters;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function fmt(v: number | null, currency: string): string {
    if (v === null) return 'N/A';
    return formatCurrency(v, currency, true);
}

// ─── Per-platform card ────────────────────────────────────────────────────────

function PlatformCard({
    source,
    value,
    orders,
    cac,
    note,
    currency,
    totalRevenue,
}: {
    source: string;
    value: number | null;
    orders: number | null;
    cac: number | null;
    note: string | null;
    currency: string;
    totalRevenue: number;
}) {
    const isGsc = source === 'gsc';
    const sharePct = value !== null && totalRevenue > 0
        ? ((value / totalRevenue) * 100).toFixed(1)
        : null;

    return (
        <div className="rounded-lg border border-zinc-200 bg-white p-5 flex flex-col gap-3">
            {/* Header */}
            <div className="flex items-center justify-between">
                <SourceBadge source={source as MetricSource} active showLabel size="sm" />
                {sharePct && (
                    <span className="text-xs text-zinc-400 tabular-nums">{sharePct}%</span>
                )}
            </div>

            {/* Revenue */}
            {isGsc ? (
                <div>
                    <p className="text-2xl font-medium tabular-nums text-zinc-400">—</p>
                    <p className="text-xs text-zinc-400 mt-1">{note ?? 'Organic search — no revenue attribution'}</p>
                </div>
            ) : value !== null ? (
                <div>
                    <p
                        className="font-medium tabular-nums text-zinc-900 leading-none"
                        style={{ fontSize: 'var(--text-2xl)', fontVariantNumeric: 'tabular-nums' }}
                    >
                        {formatCurrency(value, currency, true)}
                    </p>
                    {sharePct && (
                        <div className="mt-2 h-1 w-full bg-zinc-100 rounded-full overflow-hidden">
                            <div
                                className="h-full rounded-full"
                                style={{
                                    width: `${sharePct}%`,
                                    backgroundColor: SRC_HEX[source] ?? 'var(--color-source-store-fg)',
                                }}
                            />
                        </div>
                    )}
                </div>
            ) : (
                <p className="text-2xl font-medium tabular-nums text-zinc-400">N/A</p>
            )}

            {/* Secondary stats */}
            <div className="flex items-center gap-4 text-xs text-zinc-500">
                {orders !== null && (
                    <span>
                        <span className="tabular-nums font-medium text-zinc-700">{orders.toLocaleString()}</span>{' '}
                        orders
                    </span>
                )}
                {cac !== null && (
                    <span>
                        CAC{' '}
                        <span className="tabular-nums font-medium text-zinc-700">{formatCurrency(cac, currency, true)}</span>
                    </span>
                )}
            </div>
        </div>
    );
}

// ─── Per-source line chart ────────────────────────────────────────────────────

interface TrendChartProps {
    data: TrendPoint[];
    visibleSources: Record<string, boolean>;
    onToggle: (src: string) => void;
    currency: string;
}

function SourceTrendChart({ data, visibleSources, onToggle, currency }: TrendChartProps) {
    const formatTick = (dateStr: string) => {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    };

    return (
        <div>
            {/* Legend / toggle buttons */}
            <div className="flex flex-wrap gap-2 mb-4">
                {PLATFORM_SOURCES.map((src) => (
                    <button
                        key={src}
                        type="button"
                        onClick={() => onToggle(src)}
                        className={cn(
                            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition-all',
                            visibleSources[src]
                                ? 'border-transparent text-white'
                                : 'border-zinc-200 bg-white text-zinc-500 opacity-50 hover:opacity-80',
                        )}
                        style={visibleSources[src] ? { backgroundColor: SRC_HEX[src], borderColor: SRC_HEX[src] } : undefined}
                        aria-pressed={visibleSources[src]}
                    >
                        {SOURCE_LABELS[src]}
                    </button>
                ))}
            </div>

            <ResponsiveContainer width="100%" height={280}>
                <LineChart data={data} margin={{ top: 4, right: 16, left: 0, bottom: 0 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" strokeOpacity={0.5} />
                    <XAxis
                        dataKey="date"
                        tickFormatter={formatTick}
                        tick={{ fontSize: 14, fill: 'var(--muted-foreground)' }}
                        interval="preserveStartEnd"
                    />
                    <YAxis
                        tickFormatter={(v) => formatCurrency(v, currency, true)}
                        tick={{ fontSize: 14, fill: 'var(--muted-foreground)' }}
                        width={80}
                    />
                    <Tooltip
                        // eslint-disable-next-line @typescript-eslint/no-explicit-any
                        formatter={(value: any, name: any): [string, string] => [
                            formatCurrency(Number(value), currency),
                            SOURCE_LABELS[String(name)] ?? String(name),
                        ]}
                        // eslint-disable-next-line @typescript-eslint/no-explicit-any
                        labelFormatter={(label: any) => formatTick(String(label))}
                        contentStyle={{ fontSize: 14, borderRadius: 8, border: '1px solid var(--border)' }}
                    />
                    {PLATFORM_SOURCES.map((src) => (
                        visibleSources[src] ? (
                            <Line
                                key={src}
                                type="monotone"
                                dataKey={src}
                                stroke={SRC_HEX[src]}
                                strokeWidth={1.5}
                                dot={false}
                                strokeDasharray={src === 'gsc' ? '4 3' : undefined}
                                connectNulls
                                name={src}
                            />
                        ) : null
                    ))}
                </LineChart>
            </ResponsiveContainer>
            <p className="mt-2 text-xs text-zinc-400 text-right">
                GSC shown dashed — organic search signal, no direct revenue attribution.
            </p>
        </div>
    );
}

// ─── Cross-platform overlap table (secondary, de-emphasized) ──────────────────

function CrossPlatformOverlapTable({
    matrix,
    sourceKeys,
}: {
    matrix: Record<string, Record<string, number>>;
    sourceKeys: string[];
}) {
    const platformKeys = sourceKeys.filter((k) => k !== 'real');
    const cellColor = (pct: number) => {
        if (pct >= 80) return 'text-emerald-700';
        if (pct >= 50) return 'text-zinc-700';
        if (pct > 0)   return 'text-zinc-500';
        return 'text-zinc-300';
    };

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-xs border-collapse" role="grid" aria-label="Cross-platform order overlap">
                <thead>
                    <tr>
                        <th className="px-3 py-2 text-left text-zinc-400 font-medium w-20" />
                        {platformKeys.map((col) => (
                            <th key={col} className="px-3 py-2 text-center">
                                <SourceBadge source={col as MetricSource} showLabel size="sm" />
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-zinc-50">
                    {platformKeys.map((row) => (
                        <tr key={row} className="hover:bg-zinc-50 transition-colors">
                            <td className="px-3 py-2">
                                <SourceBadge source={row as MetricSource} showLabel size="sm" />
                            </td>
                            {platformKeys.map((col) => {
                                const pct = matrix[row]?.[col] ?? 0;
                                const isDiag = row === col;
                                return (
                                    <td
                                        key={col}
                                        className={cn(
                                            'px-3 py-2 text-center tabular-nums',
                                            isDiag ? 'text-zinc-300' : cellColor(pct),
                                        )}
                                    >
                                        {isDiag ? '—' : `${pct}%`}
                                    </td>
                                );
                            })}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

// ─── Tracking Health strip ─────────────────────────────────────────────────────

function TrackingHealth({ rows }: { rows: TrackingHealthRow[] }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="bg-zinc-50 border-b border-zinc-200">
                    <tr className="text-xs font-semibold text-zinc-500 uppercase tracking-wide">
                        <th className="px-4 py-2.5 text-left">Source</th>
                        <th className="px-4 py-2.5 text-right">Match Quality</th>
                        <th className="px-4 py-2.5 text-right">Events Sent</th>
                        <th className="px-4 py-2.5 text-right">Events Matched</th>
                        <th className="px-4 py-2.5 text-right">Consent Denied</th>
                        <th className="px-4 py-2.5 text-left">Status</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-zinc-100">
                    {rows.map((row) => (
                        <tr key={row.source} className="hover:bg-zinc-50 transition-colors">
                            <td className="px-4 py-3">
                                <SourceBadge source={row.source as MetricSource} active showLabel size="sm" />
                            </td>
                            <td className="px-4 py-3 text-right tabular-nums">
                                {row.match_quality !== null ? (
                                    <span className={cn('font-semibold', row.match_quality >= 8 ? 'text-emerald-700' : row.match_quality >= 6 ? 'text-zinc-600' : 'text-rose-600')}>
                                        {row.match_quality.toFixed(1)}/10
                                    </span>
                                ) : <span className="text-zinc-400">—</span>}
                            </td>
                            <td className="px-4 py-3 text-right tabular-nums text-zinc-700">
                                {row.events_sent?.toLocaleString() ?? '—'}
                            </td>
                            <td className="px-4 py-3 text-right tabular-nums text-zinc-700">
                                {row.events_matched?.toLocaleString() ?? '—'}
                            </td>
                            <td className="px-4 py-3 text-right tabular-nums">
                                {row.consent_denied_pct !== null ? (
                                    <span className={row.consent_denied_pct > 5 ? 'text-zinc-700 font-semibold' : 'text-zinc-700'}>
                                        {row.consent_denied_pct.toFixed(1)}%
                                    </span>
                                ) : <span className="text-zinc-400">—</span>}
                            </td>
                            <td className="px-4 py-3">
                                <span className={cn(
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    row.status === 'healthy' ? 'bg-emerald-50 text-emerald-700' :
                                    row.status === 'warning' ? 'bg-zinc-100 text-zinc-600' :
                                    row.status === 'organic' ? 'bg-zinc-100 text-zinc-500' :
                                    'bg-rose-50 text-rose-700',
                                )}>
                                    {row.status === 'organic' ? 'Organic' : row.status}
                                </span>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

// ─── View as of (renamed Time Machine) ───────────────────────────────────────

interface ViewAsOfProps {
    frames: TimeMachineFrame[];
    currency: string;
}

function ViewAsOf({ frames, currency }: ViewAsOfProps) {
    const [idx, setIdx] = useState(frames.length - 1);
    const [playing, setPlay] = useState(false);
    const ivRef = useRef<ReturnType<typeof setInterval> | null>(null);

    useEffect(() => {
        setIdx(frames.length - 1);
        setPlay(false);
    }, [frames.length]);

    useEffect(() => {
        if (!playing) { if (ivRef.current) clearInterval(ivRef.current); return; }
        ivRef.current = setInterval(() => {
            setIdx((prev) => {
                if (prev >= frames.length - 1) { setPlay(false); return prev; }
                return prev + 1;
            });
        }, 1800);
        return () => { if (ivRef.current) clearInterval(ivRef.current); };
    }, [playing, frames.length]);

    if (frames.length === 0) return (
        <p className="text-sm text-zinc-400 py-8 text-center">
            Historical snapshots not yet available.
        </p>
    );

    const frame = frames[idx];
    const displayDate = new Date(frame.date + 'T00:00:00').toLocaleDateString('en-US', {
        weekday: 'short', month: 'short', day: 'numeric', year: 'numeric',
    });

    const kpis = [
        { key: 'store',    label: 'Store',    value: frame.store },
        { key: 'facebook', label: 'Facebook', value: frame.facebook },
        { key: 'google',   label: 'Google',   value: frame.google },
        { key: 'ga4',      label: 'GA4',      value: frame.ga4 },
    ];

    return (
        <div>
            <div className="flex items-center gap-4 mb-4">
                <button
                    type="button"
                    onClick={() => setPlay((v) => !v)}
                    className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-white text-zinc-500 hover:bg-zinc-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400"
                    aria-label={playing ? 'Pause' : 'Play through dates'}
                >
                    {playing ? <Pause className="h-3.5 w-3.5" /> : <Play className="h-3.5 w-3.5" />}
                </button>
                <div className="flex-1">
                    <p className="text-sm font-medium text-zinc-700 text-center mb-1.5">{displayDate}</p>
                    <input
                        type="range"
                        min={0}
                        max={frames.length - 1}
                        value={idx}
                        onChange={(e) => { setPlay(false); setIdx(Number(e.target.value)); }}
                        className="w-full accent-zinc-600"
                        aria-label="Scrub through historical data"
                    />
                    <div className="flex justify-between mt-1">
                        <span className="text-xs text-zinc-400">
                            {new Date(frames[0].date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                        </span>
                        <span className="text-xs text-zinc-400">
                            {new Date(frames[frames.length - 1].date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                        </span>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-4 gap-3">
                {kpis.map(({ key, label, value }) => (
                    <div key={key} className="rounded-lg border border-zinc-200 bg-white p-3 text-center">
                        <SourceBadge source={key as MetricSource} showLabel size="sm" />
                        <p className="mt-2 text-base font-semibold tabular-nums text-zinc-900">
                            {formatCurrency(value, currency, true)}
                        </p>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function AttributionIndex({
    trust_bar,
    sources,
    source_keys,
    disagreement_matrix,
    trend_series,
    time_machine,
    tracking_health,
    attribution_models,
    windows,
    is_recomputing,
    filters,
}: Props) {
    const { workspace } = usePage<PageProps>().props;
    const currency = workspace?.reporting_currency ?? trust_bar.currency ?? 'USD';

    const [model, setModel] = useState(filters.model);
    const [window_, setWindow] = useState(filters.window);

    // Source trend chart — toggleable lines
    const [visibleSources, setVisibleSources] = useState<Record<string, boolean>>(() =>
        Object.fromEntries(PLATFORM_SOURCES.map((s) => [s, s !== 'gsc']))
    );
    function toggleSource(src: string) {
        setVisibleSources((prev) => ({ ...prev, [src]: !prev[src] }));
    }

    const [overlapOpen, setOverlapOpen] = useState(false);

    function handleModelChange(newModel: string) {
        setModel(newModel);
        router.visit(window.location.href, {
            method: 'get',
            data: { ...filters, model: newModel },
            preserveState: true,
            preserveScroll: true,
        });
    }

    function handleWindowChange(newWindow: string) {
        setWindow(newWindow);
        router.visit(window.location.href, {
            method: 'get',
            data: { ...filters, window: newWindow },
            preserveState: true,
            preserveScroll: true,
        });
    }

    // Total revenue across all platforms for share-of-total calculation
    const platformRevenue = sources
        .filter((s) => s.source !== 'real' && s.value !== null)
        .reduce((sum, s) => sum + (s.value ?? 0), 0);

    const platformSources = sources.filter((s) => s.source !== 'real');

    return (
        <AppLayout>
            <Head title="Attribution" />

            <div className="space-y-6 mx-auto max-w-screen-xl px-6 py-6">

                {/* ── Page header ── */}
                <div>
                    <h1 className="text-2xl font-semibold text-zinc-900">Attribution</h1>
                    <p className="mt-1 text-sm text-zinc-500">
                        How sales attribute across your tracked platforms
                    </p>
                </div>

                {/* ── Recomputing alert ── */}
                {is_recomputing && (
                    <AlertBanner
                        severity="info"
                        message="Recomputing attribution — numbers will refresh once complete."
                    />
                )}

                {/* ── Model + Window selectors (inline chip group) ── */}
                <section aria-label="Attribution settings">
                    <div className="flex flex-wrap items-center gap-4 rounded-lg border border-zinc-200 bg-white px-5 py-3">
                        <div className="flex items-center gap-2">
                            <span className="text-xs font-medium uppercase tracking-wide text-zinc-400">Model</span>
                            <div className="flex flex-wrap gap-1.5">
                                {attribution_models.map((m) => (
                                    <button
                                        key={m}
                                        type="button"
                                        onClick={() => handleModelChange(m)}
                                        className={cn(
                                            'rounded-md border px-3 py-1 text-xs font-medium transition-colors',
                                            model === m
                                                ? 'border-zinc-900 bg-zinc-900 text-white'
                                                : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50',
                                            m === 'data-driven' && 'opacity-50 cursor-not-allowed',
                                        )}
                                        title={m === 'data-driven' ? 'Calibrating — requires 30 days of data' : undefined}
                                        disabled={m === 'data-driven'}
                                        aria-pressed={model === m}
                                    >
                                        {m === 'last-click'      ? 'Last Click'       :
                                         m === 'first-click'     ? 'First Click'      :
                                         m === 'last-non-direct' ? 'Last Non-Direct'  :
                                         m === 'linear'          ? 'Linear'           :
                                         m === 'data-driven'     ? 'Data-Driven · Calibrating' :
                                         m}
                                    </button>
                                ))}
                            </div>
                        </div>
                        <div className="h-6 w-px bg-zinc-200" />
                        <div className="flex items-center gap-2">
                            <span className="text-xs font-medium uppercase tracking-wide text-zinc-400">Window</span>
                            <div className="flex gap-1.5">
                                {windows.map((w) => (
                                    <button
                                        key={w}
                                        type="button"
                                        onClick={() => handleWindowChange(w)}
                                        className={cn(
                                            'rounded-md border px-3 py-1 text-xs font-medium transition-colors',
                                            window_ === w
                                                ? 'border-zinc-900 bg-zinc-900 text-white'
                                                : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50',
                                        )}
                                        aria-pressed={window_ === w}
                                    >
                                        {w === 'ltv' ? 'LTV' : w}
                                    </button>
                                ))}
                            </div>
                        </div>
                        <div className="ml-auto">
                            <SignalTypeBadge signal="mixed" />
                        </div>
                    </div>
                </section>

                {/* ── Per-platform cards ── */}
                <section aria-label="Revenue by platform">
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        {platformSources.map((src) => (
                            <PlatformCard
                                key={src.source}
                                source={src.source}
                                value={src.value}
                                orders={src.orders}
                                cac={src.cac}
                                note={src.note}
                                currency={currency}
                                totalRevenue={platformRevenue}
                            />
                        ))}

                        {/* Unattributed card */}
                        <div className="rounded-lg border border-zinc-200 bg-white p-5 flex flex-col gap-3">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-medium text-zinc-400 uppercase tracking-wide">Unattributed</span>
                            </div>
                            <p
                                className="font-medium tabular-nums text-zinc-600 leading-none"
                                style={{ fontSize: 'var(--text-2xl)', fontVariantNumeric: 'tabular-nums' }}
                            >
                                {formatCurrency(Math.abs(trust_bar.not_tracked_bucket), currency, true)}
                            </p>
                            <p className="text-xs text-zinc-400">
                                Orders with no tracked source
                            </p>
                        </div>
                    </div>
                </section>

                {/* ── Per-platform revenue line chart ── */}
                <section aria-label="Revenue by platform trend">
                    <div className="rounded-lg border border-zinc-200 bg-white px-5 py-5">
                        <div className="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-sm font-semibold text-zinc-900">
                                    Revenue by Platform · 30-day
                                </h2>
                                <p className="mt-0.5 text-xs text-zinc-400">
                                    Toggle platforms using the legend above the chart.
                                </p>
                            </div>
                            <span className="text-xs text-zinc-400 shrink-0">
                                {formatDateOnly(filters.from)} – {formatDateOnly(filters.to)}
                            </span>
                        </div>
                        <SourceTrendChart
                            data={trend_series}
                            visibleSources={visibleSources}
                            onToggle={toggleSource}
                            currency={currency}
                        />
                    </div>
                </section>

                {/* ── View as of (time machine) ── */}
                <section aria-label="Historical snapshot" className="hidden xl:block">
                    <div className="rounded-lg border border-zinc-200 bg-white px-5 py-5">
                        <div className="mb-4">
                            <h2 className="text-sm font-semibold text-zinc-900">View as of</h2>
                            <p className="mt-0.5 text-xs text-zinc-400">
                                Scrub through the last 12 weeks to see how platform values evolved.
                            </p>
                        </div>
                        <ViewAsOf frames={time_machine} currency={currency} />
                    </div>
                </section>

                {/* ── Cross-platform overlap (secondary, collapsible) ── */}
                <section aria-label="Cross-platform overlap">
                    <div className="rounded-lg border border-zinc-200 bg-white overflow-hidden">
                        <button
                            type="button"
                            onClick={() => setOverlapOpen((v) => !v)}
                            className="w-full flex items-center justify-between px-5 py-4 hover:bg-zinc-50 transition-colors text-left"
                            aria-expanded={overlapOpen}
                        >
                            <div>
                                <span className="text-sm font-medium text-zinc-700">Cross-platform overlap</span>
                                <p className="text-xs text-zinc-400 mt-0.5">
                                    % of orders attributed by both platforms
                                </p>
                            </div>
                            {overlapOpen ? <ChevronUp className="h-4 w-4 text-zinc-400 shrink-0" /> : <ChevronDown className="h-4 w-4 text-zinc-400 shrink-0" />}
                        </button>
                        {overlapOpen && (
                            <div className="border-t border-zinc-100">
                                <CrossPlatformOverlapTable
                                    matrix={disagreement_matrix}
                                    sourceKeys={source_keys}
                                />
                            </div>
                        )}
                    </div>
                </section>

                {/* ── Tracking Health strip ── */}
                <section aria-label="Tracking health per source">
                    <div className="rounded-lg border border-zinc-200 bg-white overflow-hidden">
                        <div className="px-5 py-4 border-b border-zinc-100">
                            <h2 className="text-sm font-semibold text-zinc-900">Tracking health</h2>
                            <p className="mt-0.5 text-xs text-zinc-400">
                                Per-platform signal quality. Match Quality mirrors Meta's Event Match Quality (0–10).
                            </p>
                        </div>
                        <TrackingHealth rows={tracking_health} />
                    </div>
                </section>

            </div>
        </AppLayout>
    );
}
