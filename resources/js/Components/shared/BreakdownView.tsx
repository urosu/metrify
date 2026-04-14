/**
 * BreakdownView — full implementation (Phase 1.4).
 *
 * Spec: PLANNING.md "BreakdownView Component Architecture"
 * Related: resources/js/Pages/Campaigns/Index.tsx, Analytics/Products.tsx, Stores/Index.tsx
 *
 * Interaction model:
 *   breakdownBy (how rows are grouped) × cardData (which channel's metrics are shown)
 *   are two ORTHOGONAL AXES — not one selector.
 *
 * Three view modes:
 *   Cards  — grid of row cards, each showing the item label + key metrics
 *   Table  — sortable table with all metric columns (default)
 *   Graph  — horizontal bar chart for a selected metric
 *
 * Filter chips (shown only when `isWinner` predicate is provided):
 *   All | Winners | Losers
 *
 * State persistence: when `viewKey` is set, each state change is debounced
 * and saved to users.view_preferences via PATCH /settings/view-preferences.
 * Initial state is restored from usePage().props.auth.user.view_preferences[viewKey].
 */

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import {
    BarChart as RechartsBarChart,
    Bar,
    XAxis,
    YAxis,
    Tooltip,
    ResponsiveContainer,
    Cell,
} from 'recharts';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Grid2X2,
    LayoutList,
    BarChart2,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency, formatNumber, formatPercent } from '@/lib/formatters';
import { MetricSource, SourceBadge } from './MetricCard';
import type { PageProps } from '@/types';

// ---------------------------------------------------------------------------
// Public types — also re-export the stub types so consumers don't change imports
// ---------------------------------------------------------------------------

/** How rows are grouped in the view. */
export type BreakdownDimension = 'product' | 'country' | 'campaign' | 'advertiser' | 'date';

/** Which channel's metrics the cards display. 'all' = multi-source composite. */
export type BreakdownCardData = MetricSource | 'all';

/** Which visual layout is active. */
export type BreakdownViewMode = 'cards' | 'table' | 'graph';

/**
 * A single row in the BreakdownView dataset.
 * Columns are defined by BreakdownColumn[], not hardcoded.
 */
export interface BreakdownRow {
    /** Unique identifier for this row (external ID, country code, date string, etc.). */
    id: string | number;
    /** Display label for the breakdown dimension value. */
    label: string;
    /** Arbitrary metric columns — keyed by metric name, value may be null when no data. */
    metrics: Record<string, number | null>;
    /** Optional sub-rows for hierarchical breakdowns (Phase 3+). */
    children?: BreakdownRow[];
}

/**
 * Column definition — tells BreakdownView how to format and display each metric.
 */
export interface BreakdownColumn {
    /** Matches a key in BreakdownRow.metrics. */
    key: string;
    /** Human-readable column header. */
    label: string;
    /** How to format the value for display. */
    format: 'currency' | 'number' | 'percent' | 'multiplier' | 'raw';
    /** Currency code — only needed when format='currency'. Defaults to the view's `currency` prop. */
    currency?: string;
    /** Lower is better (e.g. CPO, CPC). Used for trend coloring in table cells. */
    invertTrend?: boolean;
    /** Show this column on row cards in Cards view. Default: true for first 4 columns. */
    showInCards?: boolean;
    /**
     * Mark this column as the "change" column — its value is treated as a % delta
     * and rendered with the TrendBadge color logic (green/red).
     */
    isChangePct?: boolean;
}

/**
 * Optional sort/filter controls. When omitted BreakdownView manages these internally.
 * Phase 1.4: all state is managed internally with view_preferences persistence.
 */
export interface BreakdownControls {
    filter?: 'winners' | 'losers' | 'all';
    orderBy?: string;
    orderDir?: 'asc' | 'desc';
}

export interface BreakdownViewProps {
    /** How rows are grouped (e.g. by campaign, by product, by country). */
    breakdownBy: BreakdownDimension;
    /** Which data source's metrics are shown on cards. */
    cardData: BreakdownCardData;
    /** Column definitions — drive formatting, table headers, and card display. */
    columns: BreakdownColumn[];
    /** The data rows to display. */
    data: BreakdownRow[];
    /** Initial view mode (overridden by saved view_preferences when viewKey is set). */
    defaultView?: BreakdownViewMode;
    /** Key used to persist state in users.view_preferences JSONB. Set to page name. */
    viewKey?: string;
    /**
     * Predicate that determines if a row is a "winner".
     * When provided, Winners / Losers filter chips are shown.
     * Returns true = winner, false/null = loser.
     *
     * Example for campaigns:
     *   isWinner={(row) => (row.metrics.real_roas ?? 0) >= (workspace.target_roas ?? 0)}
     *
     * Example for products (top 10 by revenue delta):
     *   isWinner={(row) => topTenIds.has(row.id)}
     */
    isWinner?: (row: BreakdownRow) => boolean;
    /** Reporting currency — used for currency columns that don't specify their own. */
    currency?: string;
    /** Loading state — render skeletons instead of rows. */
    loading?: boolean;
    /** Called when view mode changes (in addition to view_preferences persistence). */
    onViewChange?: (mode: BreakdownViewMode) => void;
    /** Empty state message when data is empty after filtering. */
    emptyMessage?: string;
}

// ---------------------------------------------------------------------------
// Value formatter
// ---------------------------------------------------------------------------

function formatValue(
    value: number | null | undefined,
    col: BreakdownColumn,
    fallbackCurrency?: string,
): string {
    if (value === null || value === undefined) return '—';
    const currency = col.currency ?? fallbackCurrency ?? 'EUR';
    switch (col.format) {
        case 'currency':
            return formatCurrency(value, currency, true);
        case 'number':
            return formatNumber(value, true);
        case 'percent':
            return `${value >= 0 ? '+' : ''}${value.toFixed(1)}%`;
        case 'multiplier':
            return `${value.toFixed(2)}×`;
        case 'raw':
        default:
            return String(value);
    }
}

// ---------------------------------------------------------------------------
// Sort icon
// ---------------------------------------------------------------------------

function SortIcon({ col, sortBy, sortDir }: { col: string; sortBy: string; sortDir: 'asc' | 'desc' }) {
    if (col !== sortBy) return <ArrowUpDown className="ml-1 h-3 w-3 opacity-30" />;
    return sortDir === 'asc'
        ? <ArrowUp className="ml-1 h-3 w-3 text-primary" />
        : <ArrowDown className="ml-1 h-3 w-3 text-primary" />;
}

// ---------------------------------------------------------------------------
// Row card — used in Cards view
// ---------------------------------------------------------------------------

function RowCard({
    row,
    columns,
    cardData,
    currency,
}: {
    row: BreakdownRow;
    columns: BreakdownColumn[];
    cardData: BreakdownCardData;
    currency?: string;
}) {
    // Show first 4 columns marked showInCards (or all if showInCards is not set)
    const cardCols = columns
        .filter(c => c.showInCards !== false)
        .slice(0, 4);

    return (
        <div className="rounded-xl border border-zinc-200 bg-white p-4 space-y-2 hover:shadow-sm transition-shadow">
            <div className="flex items-start justify-between gap-2 min-w-0">
                <p className="text-sm font-medium text-zinc-900 truncate leading-snug">{row.label}</p>
                {cardData !== 'all' && <SourceBadge source={cardData as MetricSource} />}
            </div>
            <div className={cn('grid gap-x-4 gap-y-2', cardCols.length > 2 ? 'grid-cols-2' : 'grid-cols-1')}>
                {cardCols.map(col => {
                    const val = row.metrics[col.key];
                    const isChange = col.isChangePct;
                    const isPositive = isChange && val !== null && val !== undefined && (col.invertTrend ? val < 0 : val > 0);
                    const isNegative = isChange && val !== null && val !== undefined && (col.invertTrend ? val > 0 : val < 0);
                    return (
                        <div key={col.key}>
                            <p className="text-[10px] uppercase tracking-wide text-zinc-400">{col.label}</p>
                            <p className={cn(
                                'text-sm font-semibold tabular-nums leading-snug',
                                isPositive ? 'text-green-600' : isNegative ? 'text-red-600' : 'text-zinc-900',
                            )}>
                                {formatValue(val, col, currency)}
                            </p>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Graph view — horizontal bar chart for a single metric
// ---------------------------------------------------------------------------

function GraphView({
    rows,
    columns,
    currency,
}: {
    rows: BreakdownRow[];
    columns: BreakdownColumn[];
    currency?: string;
}) {
    const numericCols = columns.filter(c => !c.isChangePct);
    const [graphMetric, setGraphMetric] = useState(numericCols[0]?.key ?? '');

    const col = columns.find(c => c.key === graphMetric) ?? columns[0];

    // Take top 20 rows for readability
    const chartData = rows.slice(0, 20).map(row => ({
        name: row.label.length > 24 ? row.label.slice(0, 22) + '…' : row.label,
        value: row.metrics[graphMetric] ?? 0,
    }));

    if (!col) return null;

    return (
        <div className="space-y-3">
            {numericCols.length > 1 && (
                <div className="flex gap-2 flex-wrap">
                    {numericCols.map(c => (
                        <button
                            key={c.key}
                            onClick={() => setGraphMetric(c.key)}
                            className={cn(
                                'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                graphMetric === c.key
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-zinc-200 text-zinc-500 hover:border-zinc-300 hover:text-zinc-700',
                            )}
                        >
                            {c.label}
                        </button>
                    ))}
                </div>
            )}
            <div className="h-[320px] w-full">
                <ResponsiveContainer width="100%" height="100%">
                    <RechartsBarChart
                        data={chartData}
                        layout="vertical"
                        margin={{ top: 4, right: 16, bottom: 4, left: 8 }}
                    >
                        <XAxis
                            type="number"
                            tick={{ fontSize: 11 }}
                            tickFormatter={(v: number) => formatValue(v, col, currency)}
                            width={60}
                        />
                        <YAxis
                            type="category"
                            dataKey="name"
                            tick={{ fontSize: 11 }}
                            width={120}
                        />
                        <Tooltip
                            formatter={(value) => [formatValue(value as number, col, currency), col.label]}
                            contentStyle={{ fontSize: 12, borderRadius: 8, border: '1px solid #e4e4e7' }}
                        />
                        <Bar dataKey="value" radius={[0, 4, 4, 0]}>
                            {chartData.map((_, i) => (
                                <Cell key={i} fill="#6366f1" fillOpacity={0.8} />
                            ))}
                        </Bar>
                    </RechartsBarChart>
                </ResponsiveContainer>
            </div>
            {rows.length > 20 && (
                <p className="text-xs text-zinc-400 text-center">Showing top 20 of {rows.length} rows</p>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// BreakdownView
// ---------------------------------------------------------------------------

/**
 * Full implementation of the BreakdownView — Cards / Table / Graph triplet.
 * See: PLANNING.md "BreakdownView Component Architecture (Phase 1.4 build)"
 */
export function BreakdownView({
    breakdownBy,
    cardData,
    columns,
    data,
    defaultView = 'table',
    viewKey,
    isWinner,
    currency,
    loading = false,
    onViewChange,
    emptyMessage = 'No data for this period.',
}: BreakdownViewProps) {
    const { auth } = usePage<PageProps>().props;
    const savedPrefs = viewKey ? (auth.user?.view_preferences?.[viewKey] ?? {}) : {};

    // URL ?filter param takes priority over saved prefs (used by sidebar deep-links).
    const urlFilterParam = typeof window !== 'undefined'
        ? (new URLSearchParams(window.location.search).get('filter') as 'all' | 'winners' | 'losers' | null)
        : null;

    // --- State (URL param > view_preferences > defaults) ---
    const [viewMode, setViewMode] = useState<BreakdownViewMode>(
        (savedPrefs.view as BreakdownViewMode) ?? defaultView,
    );
    const [filter, setFilter] = useState<'all' | 'winners' | 'losers'>(
        urlFilterParam ?? (savedPrefs.filter as 'all' | 'winners' | 'losers') ?? 'all',
    );
    const [sortBy, setSortBy] = useState<string>(savedPrefs.sort_by ?? (columns[0]?.key ?? ''));
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>(
        (savedPrefs.sort_dir as 'asc' | 'desc') ?? 'desc',
    );

    // --- Persist to view_preferences (debounced) ---
    const persistTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);
    const persistPrefs = useCallback(
        (prefs: Record<string, string>) => {
            if (!viewKey) return;
            if (persistTimeout.current) clearTimeout(persistTimeout.current);
            persistTimeout.current = setTimeout(() => {
                // Merge under the viewKey namespace
                axios.patch('/settings/view-preferences', {
                    preferences: { [viewKey]: prefs },
                }).catch(() => {
                    // Silently swallow — preference persistence is best-effort.
                    // The UI state is already correct; only the server persistence failed.
                });
            }, 500);
        },
        [viewKey],
    );

    // Call persistPrefs whenever relevant state changes
    useEffect(() => {
        persistPrefs({ view: viewMode, filter, sort_by: sortBy, sort_dir: sortDir });
    }, [viewMode, filter, sortBy, sortDir, persistPrefs]);

    // --- Sort handler ---
    function toggleSort(col: string) {
        if (col === sortBy) {
            setSortDir(d => (d === 'desc' ? 'asc' : 'desc'));
        } else {
            setSortBy(col);
            setSortDir('desc');
        }
    }

    // --- View mode handler ---
    function changeView(mode: BreakdownViewMode) {
        setViewMode(mode);
        onViewChange?.(mode);
    }

    // --- Filtered + sorted rows ---
    const displayRows = useMemo(() => {
        let rows = [...data];

        // Apply winner/loser filter
        if (filter !== 'all' && isWinner) {
            rows = rows.filter(row => (filter === 'winners' ? isWinner(row) : !isWinner(row)));
        }

        // Sort — nulls always last
        const dir = sortDir === 'desc' ? -1 : 1;
        rows.sort((a, b) => {
            const av = a.metrics[sortBy];
            const bv = b.metrics[sortBy];
            if (av === null || av === undefined) return 1;
            if (bv === null || bv === undefined) return -1;
            return (av - bv) * dir;
        });

        return rows;
    }, [data, filter, sortBy, sortDir, isWinner]);

    // --- Skeleton loading state ---
    if (loading) {
        return (
            <div className="space-y-2">
                {[...Array(5)].map((_, i) => (
                    <div key={i} className="h-10 rounded-lg bg-zinc-100 animate-pulse" />
                ))}
            </div>
        );
    }

    const showFilterChips = !!isWinner;
    const cardColumns = columns.filter(c => c.showInCards !== false);

    return (
        <div className="space-y-3">
            {/* ── Toolbar ─────────────────────────────────────────────────── */}
            <div className="flex items-center justify-between gap-3 flex-wrap">
                {/* Left: filter chips (only when isWinner is provided) */}
                {showFilterChips ? (
                    <div className="flex items-center gap-1">
                        {(['all', 'winners', 'losers'] as const).map(f => (
                            <button
                                key={f}
                                onClick={() => setFilter(f)}
                                className={cn(
                                    'rounded-full border px-3 py-1 text-xs font-medium transition-colors capitalize',
                                    filter === f
                                        ? f === 'winners'
                                            ? 'border-green-300 bg-green-50 text-green-700'
                                            : f === 'losers'
                                            ? 'border-red-300 bg-red-50 text-red-700'
                                            : 'border-primary bg-primary/10 text-primary'
                                        : 'border-zinc-200 text-zinc-500 hover:border-zinc-300 hover:text-zinc-700',
                                )}
                            >
                                {f === 'all' ? 'All' : f === 'winners' ? '🏆 Winners' : '📉 Losers'}
                            </button>
                        ))}
                        {filter !== 'all' && (
                            <span className="ml-1 text-xs text-zinc-400">
                                {displayRows.length} result{displayRows.length !== 1 ? 's' : ''}
                            </span>
                        )}
                    </div>
                ) : (
                    <div />
                )}

                {/* Right: view mode toggle */}
                <div className="flex items-center gap-0.5 rounded-lg border border-zinc-200 bg-zinc-50 p-0.5">
                    {(
                        [
                            { mode: 'table' as const, icon: <LayoutList className="h-3.5 w-3.5" />, label: 'Table' },
                            { mode: 'cards' as const, icon: <Grid2X2 className="h-3.5 w-3.5" />, label: 'Cards' },
                            { mode: 'graph' as const, icon: <BarChart2 className="h-3.5 w-3.5" />, label: 'Chart' },
                        ] as const
                    ).map(({ mode, icon, label }) => (
                        <button
                            key={mode}
                            onClick={() => changeView(mode)}
                            title={label}
                            className={cn(
                                'flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium transition-colors',
                                viewMode === mode
                                    ? 'bg-white text-zinc-900 shadow-sm'
                                    : 'text-zinc-400 hover:text-zinc-600',
                            )}
                        >
                            {icon}
                            <span className="hidden sm:inline">{label}</span>
                        </button>
                    ))}
                </div>
            </div>

            {/* ── Empty state ─────────────────────────────────────────────── */}
            {displayRows.length === 0 && (
                <div className="rounded-xl border border-zinc-100 bg-zinc-50 px-6 py-12 text-center text-sm text-zinc-400">
                    {filter !== 'all'
                        ? `No ${filter} found for the current period.`
                        : emptyMessage}
                </div>
            )}

            {/* ── Cards view ──────────────────────────────────────────────── */}
            {viewMode === 'cards' && displayRows.length > 0 && (
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {displayRows.map(row => (
                        <RowCard
                            key={row.id}
                            row={row}
                            columns={cardColumns}
                            cardData={cardData}
                            currency={currency}
                        />
                    ))}
                </div>
            )}

            {/* ── Table view ──────────────────────────────────────────────── */}
            {viewMode === 'table' && displayRows.length > 0 && (
                <div className="overflow-x-auto rounded-xl border border-zinc-200 bg-white">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-zinc-100 bg-zinc-50 text-left">
                                <th className="px-4 py-3 font-medium text-zinc-400 min-w-[160px]">
                                    {breakdownBy.charAt(0).toUpperCase() + breakdownBy.slice(1)}
                                </th>
                                {columns.map(col => (
                                    <th
                                        key={col.key}
                                        className="px-4 py-3 font-medium text-zinc-400 text-right whitespace-nowrap cursor-pointer select-none hover:text-zinc-700 transition-colors"
                                        onClick={() => toggleSort(col.key)}
                                    >
                                        <span className="inline-flex items-center justify-end gap-0.5">
                                            {col.label}
                                            <SortIcon col={col.key} sortBy={sortBy} sortDir={sortDir} />
                                        </span>
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-50">
                            {displayRows.map(row => (
                                <tr key={row.id} className="hover:bg-zinc-50 transition-colors">
                                    <td className="px-4 py-3 font-medium text-zinc-900 max-w-[240px] truncate">
                                        {row.label}
                                    </td>
                                    {columns.map(col => {
                                        const val = row.metrics[col.key];
                                        const isChange = col.isChangePct;
                                        const isPositive = isChange && val !== null && val !== undefined && (col.invertTrend ? val < 0 : val > 0);
                                        const isNegative = isChange && val !== null && val !== undefined && (col.invertTrend ? val > 0 : val < 0);
                                        return (
                                            <td
                                                key={col.key}
                                                className={cn(
                                                    'px-4 py-3 text-right tabular-nums whitespace-nowrap',
                                                    isPositive
                                                        ? 'text-green-600'
                                                        : isNegative
                                                        ? 'text-red-600'
                                                        : 'text-zinc-700',
                                                )}
                                            >
                                                {formatValue(val, col, currency)}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {/* ── Graph view ──────────────────────────────────────────────── */}
            {viewMode === 'graph' && displayRows.length > 0 && (
                <div className="rounded-xl border border-zinc-200 bg-white p-4">
                    <GraphView rows={displayRows} columns={columns} currency={currency} />
                </div>
            )}
        </div>
    );
}
