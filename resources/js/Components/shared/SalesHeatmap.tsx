import { useEffect, useRef, useState } from 'react';
import { wurl } from '@/lib/workspace-url';
import { formatCurrency, formatNumber } from '@/lib/formatters';
import { cn } from '@/lib/utils';

// ─── Types ────────────────────────────────────────────────────────────────────

interface HeatmapRow {
    dow: number;
    hour: number;
    revenue: number;
    orders: number;
}

interface HeatmapApiResponse {
    data: HeatmapRow[];
}

export interface SalesHeatmapProps {
    workspaceSlug: string | undefined;
    /** ISO date string YYYY-MM-DD. Defaults to server-side last 90 days when omitted. */
    from?: string;
    /** ISO date string YYYY-MM-DD. Defaults to server-side today when omitted. */
    to?: string;
    currency?: string;
}

// ─── Constants ────────────────────────────────────────────────────────────────

/**
 * Display order: Mon(1) … Sat(6), Sun(0). Postgres EXTRACT(DOW) uses 0=Sun.
 * @see DashboardController::heatmap
 */
const DOW_ORDER: number[] = [1, 2, 3, 4, 5, 6, 0];
const DAY_LABELS: Record<number, string> = {
    0: 'Sun',
    1: 'Mon',
    2: 'Tue',
    3: 'Wed',
    4: 'Thu',
    5: 'Fri',
    6: 'Sat',
};

const HOURS = Array.from({ length: 24 }, (_, i) => i);

/**
 * Maps a normalised intensity (0–1) to a Tailwind bg class.
 * Six buckets: 0 = no data, 1–6 = blue-100 … blue-700.
 * The step boundaries are tuned so the top bucket doesn't require a perfect max.
 */
function intensityClass(ratio: number): string {
    if (ratio <= 0)    return 'bg-muted/50';
    if (ratio < 0.10)  return 'bg-blue-100';
    if (ratio < 0.25)  return 'bg-blue-200';
    if (ratio < 0.45)  return 'bg-blue-400';
    if (ratio < 0.65)  return 'bg-blue-500';
    if (ratio < 0.82)  return 'bg-blue-600';
    return 'bg-blue-700';
}

/**
 * Tooltip text colour that stays readable against all intensity buckets.
 */
function tooltipTextClass(ratio: number): string {
    return ratio >= 0.45 ? 'text-white' : 'text-blue-900';
}

// ─── Skeleton ─────────────────────────────────────────────────────────────────

function HeatmapSkeleton() {
    return (
        <div className="animate-pulse space-y-1" aria-hidden="true">
            {DOW_ORDER.map((dow) => (
                <div key={dow} className="flex gap-0.5">
                    <div className="w-8 shrink-0 rounded bg-muted h-6" />
                    {HOURS.map((h) => (
                        <div key={h} className="h-6 flex-1 rounded bg-muted" />
                    ))}
                </div>
            ))}
        </div>
    );
}

// ─── SalesHeatmap ─────────────────────────────────────────────────────────────

/**
 * 7×24 sales heatmap: day-of-week rows × hour-of-day columns.
 *
 * Fetches its own data from GET /{workspace}/api/heatmap via native fetch
 * (SWR is not installed in this project — matches ActivityFeed pattern).
 * Each cell colour encodes value / max_value across the dataset in 6 blue buckets.
 *
 * @see DashboardController::heatmap — backend endpoint
 * @see docs/competitors/_patterns_catalog.md — pattern #47 "Sales heatmap"
 * @see docs/UX.md §5 for shared primitive catalogue
 */
export function SalesHeatmap({ workspaceSlug, from, to, currency = 'EUR' }: SalesHeatmapProps) {
    const [rows, setRows] = useState<HeatmapRow[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);
    const [metric, setMetric] = useState<'revenue' | 'orders'>('revenue');
    const [tooltip, setTooltip] = useState<{
        dow: number;
        hour: number;
        revenue: number;
        orders: number;
        x: number;
        y: number;
    } | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        abortRef.current?.abort();
        const ctrl = new AbortController();
        abortRef.current = ctrl;

        setLoading(true);
        setError(false);

        const params = new URLSearchParams();
        if (from) params.set('from', from);
        if (to)   params.set('to',   to);

        const url = wurl(workspaceSlug, `/api/heatmap?${params.toString()}`);

        fetch(url, { signal: ctrl.signal, headers: { Accept: 'application/json' } })
            .then((r) => (r.ok ? r.json() : Promise.reject(new Error(`HTTP ${r.status}`))))
            .then((body: HeatmapApiResponse) => {
                setRows(body.data ?? []);
                setLoading(false);
            })
            .catch((err: unknown) => {
                if (err instanceof Error && err.name === 'AbortError') return;
                setError(true);
                setLoading(false);
            });

        return () => ctrl.abort();
    }, [workspaceSlug, from, to]);

    // Build a lookup map (dow × hour) → row for O(1) cell access
    const cellMap = new Map<string, HeatmapRow>();
    for (const row of rows) {
        cellMap.set(`${row.dow}-${row.hour}`, row);
    }

    // Derive max value across the full dataset for the active metric
    let maxValue = 0;
    for (const row of rows) {
        const v = metric === 'revenue' ? row.revenue : row.orders;
        if (v > maxValue) maxValue = v;
    }

    function handleCellMouseEnter(
        e: React.MouseEvent<HTMLDivElement>,
        dow: number,
        hour: number,
        row: HeatmapRow | undefined,
    ) {
        if (!row) return;
        const rect = e.currentTarget.getBoundingClientRect();
        setTooltip({
            dow,
            hour,
            revenue: row.revenue,
            orders: row.orders,
            x: rect.left + rect.width / 2,
            y: rect.top,
        });
    }

    function handleCellMouseLeave() {
        setTooltip(null);
    }

    return (
        <div className="rounded-xl border border-border bg-card p-5 shadow-sm">
            {/* Header */}
            <div className="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h3 className="text-sm font-semibold text-foreground">Sales by day &amp; hour</h3>
                    <p className="mt-0.5 text-sm text-muted-foreground/70">
                        Colour intensity = {metric === 'revenue' ? 'revenue' : 'order count'} relative to peak cell
                    </p>
                </div>
                {/* Metric toggle */}
                <div className="flex rounded-lg border border-border bg-muted/50 p-0.5 text-xs font-medium">
                    <button
                        type="button"
                        onClick={() => setMetric('revenue')}
                        className={cn(
                            'rounded-md px-3 py-1 transition-colors',
                            metric === 'revenue'
                                ? 'bg-card text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        Revenue
                    </button>
                    <button
                        type="button"
                        onClick={() => setMetric('orders')}
                        className={cn(
                            'rounded-md px-3 py-1 transition-colors',
                            metric === 'orders'
                                ? 'bg-card text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        Orders
                    </button>
                </div>
            </div>

            {loading && <HeatmapSkeleton />}

            {error && !loading && (
                <p className="py-6 text-center text-sm text-muted-foreground/70">
                    Could not load heatmap data.
                </p>
            )}

            {!loading && !error && (
                <>
                {/* Visually-hidden accessible table — WCAG 2.1 SC 1.1.1 */}
                <table className="sr-only" aria-label="Sales heatmap data by day and hour">
                    <thead>
                        <tr>
                            <th scope="col">Day</th>
                            {HOURS.map((h) => (
                                <th key={h} scope="col">{String(h).padStart(2, '0')}:00</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {DOW_ORDER.map((dow) => (
                            <tr key={dow}>
                                <th scope="row">{DAY_LABELS[dow]}</th>
                                {HOURS.map((hour) => {
                                    const row = cellMap.get(`${dow}-${hour}`);
                                    return (
                                        <td key={hour}>
                                            {row
                                                ? (metric === 'revenue'
                                                    ? formatCurrency(row.revenue, currency)
                                                    : formatNumber(row.orders))
                                                : '—'}
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
                <div className="overflow-x-auto">
                    <div className="min-w-[640px]">
                        {/* Hour header row — label every 4th column (0, 4, 8, 12, 16, 20) */}
                        <div className="mb-0.5 flex gap-0.5">
                            {/* Spacer for the day-label column */}
                            <div className="w-8 shrink-0" />
                            {HOURS.map((h) => (
                                <div
                                    key={h}
                                    className="flex-1 text-center text-[10px] font-medium text-muted-foreground/70 leading-none pb-1"
                                >
                                    {h % 4 === 0 ? String(h).padStart(2, '0') : ''}
                                </div>
                            ))}
                        </div>

                        {/* Data rows */}
                        {DOW_ORDER.map((dow) => (
                            <div key={dow} className="mb-0.5 flex gap-0.5">
                                {/* Day label */}
                                <div className="flex w-8 shrink-0 items-center text-[11px] font-medium text-muted-foreground">
                                    {DAY_LABELS[dow]}
                                </div>

                                {/* Hour cells */}
                                {HOURS.map((hour) => {
                                    const row = cellMap.get(`${dow}-${hour}`);
                                    const value = row
                                        ? (metric === 'revenue' ? row.revenue : row.orders)
                                        : 0;
                                    const ratio = maxValue > 0 ? value / maxValue : 0;
                                    const bgClass = intensityClass(ratio);

                                    return (
                                        <div
                                            key={hour}
                                            className={cn(
                                                'h-6 flex-1 rounded-sm cursor-default transition-opacity hover:opacity-80',
                                                bgClass,
                                            )}
                                            onMouseEnter={(e) => handleCellMouseEnter(e, dow, hour, row)}
                                            onMouseLeave={handleCellMouseLeave}
                                            aria-label={
                                                row
                                                    ? `${DAY_LABELS[dow]} ${String(hour).padStart(2, '0')}:00 — ${formatCurrency(row.revenue, currency)} revenue, ${formatNumber(row.orders)} orders`
                                                    : `${DAY_LABELS[dow]} ${String(hour).padStart(2, '0')}:00 — no data`
                                            }
                                        />
                                    );
                                })}
                            </div>
                        ))}

                        {/* Intensity legend */}
                        <div className="mt-3 flex items-center gap-2">
                            <span className="text-[10px] text-muted-foreground/70">Low</span>
                            {(['bg-blue-100', 'bg-blue-200', 'bg-blue-400', 'bg-blue-500', 'bg-blue-600', 'bg-blue-700'] as const).map((cls) => (
                                <div key={cls} className={cn('h-3 w-6 rounded-sm', cls)} />
                            ))}
                            <span className="text-[10px] text-muted-foreground/70">High</span>
                        </div>
                    </div>
                </div>
                </>
            )}

            {/* Floating tooltip (rendered via fixed positioning to escape overflow:hidden) */}
            {tooltip && (
                <div
                    role="tooltip"
                    className={cn(
                        'pointer-events-none fixed z-50 -translate-x-1/2 -translate-y-full',
                        'mb-2 rounded-lg border border-border bg-card px-3 py-2 shadow-lg',
                        'text-sm text-foreground',
                    )}
                    style={{ left: tooltip.x, top: tooltip.y - 8 }}
                >
                    <div className="font-semibold text-foreground">
                        {DAY_LABELS[tooltip.dow]} {String(tooltip.hour).padStart(2, '0')}:00
                    </div>
                    <div className="mt-0.5 space-y-0.5">
                        <div>{formatCurrency(tooltip.revenue, currency)} revenue</div>
                        <div>{formatNumber(tooltip.orders)} orders</div>
                    </div>
                </div>
            )}
        </div>
    );
}
