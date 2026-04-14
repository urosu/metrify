import React, { useMemo, useState } from 'react';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    ReferenceLine,
    ReferenceArea,
} from 'recharts';
import { formatCurrency, formatDate, formatNumber, type Granularity } from '@/lib/formatters';

export interface MultiSeriesPoint {
    date: string;
    revenue: number;
    orders: number;
    aov: number | null;
    roas: number | null;
    ad_spend: number | null;
    /** GSC aggregate clicks (device='all', country='ZZ') — null for hourly granularity */
    gsc_clicks: number | null;
}

export interface HolidayOverlay {
    date: string;
    name: string;
}

export interface WorkspaceEventOverlay {
    date_from: string;
    date_to: string;
    name: string;
    event_type: string;
}

type SeriesKey = 'revenue' | 'orders' | 'aov' | 'roas' | 'ad_spend' | 'gsc_clicks';

interface SeriesConfig {
    key: SeriesKey;
    label: string;
    color: string;
    /** left = currency axis, right = count/ratio axis */
    yAxis: 'left' | 'right';
    valueType: 'currency' | 'number' | 'ratio';
}

const SERIES: SeriesConfig[] = [
    { key: 'revenue',    label: 'Revenue',    color: 'var(--chart-1)', yAxis: 'left',  valueType: 'currency' },
    { key: 'orders',     label: 'Orders',     color: 'var(--chart-5)', yAxis: 'right', valueType: 'number'   },
    { key: 'aov',        label: 'AOV',        color: 'var(--chart-2)', yAxis: 'left',  valueType: 'currency' },
    { key: 'ad_spend',   label: 'Ad Spend',   color: 'var(--chart-3)', yAxis: 'left',  valueType: 'currency' },
    { key: 'roas',       label: 'ROAS',       color: 'var(--chart-4)', yAxis: 'right', valueType: 'ratio'    },
    { key: 'gsc_clicks', label: 'GSC Clicks', color: 'var(--chart-2)', yAxis: 'right', valueType: 'number'   },
];

// ─── Overlay colors ────────────────────────────────────────────────────────────
// holiday: gray (#a1a1aa), promotion/workspace_event: blue (#3b82f6), note: amber (#f59e0b)

interface OverlayMarkerProps {
    viewBox?: { x: number; y: number; width: number; height: number };
    label: string;
    color: string;
    onHoverChange: (state: { label: string; x: number; y: number } | null) => void;
}

function OverlayMarker({ viewBox, label, color, onHoverChange }: OverlayMarkerProps) {
    if (!viewBox) return null;
    const cx = viewBox.x + viewBox.width / 2;
    const cy = viewBox.y;
    return (
        <circle
            cx={cx}
            cy={cy + 6}
            r={5}
            fill={color}
            style={{ cursor: 'default' }}
            onMouseEnter={() => onHoverChange({ label, x: cx, y: cy + 6 })}
            onMouseLeave={() => onHoverChange(null)}
        />
    );
}

interface Props {
    data: MultiSeriesPoint[];
    /** Comparison data — revenue series only */
    comparisonData?: MultiSeriesPoint[];
    /** Annotate specific dates with a subtle reference line + amber marker */
    notes?: Array<{ date: string; note: string }>;
    /** Public holidays for workspace's country — gray vertical line markers */
    holidays?: HolidayOverlay[];
    /** Manual promotions / expected spikes created by user — blue markers / shaded areas */
    workspaceEvents?: WorkspaceEventOverlay[];
    granularity: Granularity;
    currency?: string;
    timezone?: string;
    className?: string;
}

function formatValue(value: number, valueType: SeriesConfig['valueType'], currency: string): string {
    if (valueType === 'currency') return formatCurrency(value, currency);
    if (valueType === 'ratio')    return `${value.toFixed(2)}×`;
    return formatNumber(value);
}

function formatAxis(value: number, valueType: SeriesConfig['valueType'], currency: string): string {
    if (valueType === 'currency') return formatCurrency(value, currency, true);
    if (valueType === 'ratio')    return `${value.toFixed(1)}×`;
    return formatNumber(value, true);
}

const MultiSeriesLineChartInner = React.memo(function MultiSeriesLineChartInner({
    data,
    comparisonData,
    notes,
    holidays,
    workspaceEvents,
    granularity,
    currency = 'EUR',
    timezone,
    className,
}: Props) {
    const [visible, setVisible] = useState<Set<SeriesKey>>(new Set(['revenue']));
    const [hoveredOverlay, setHoveredOverlay] = useState<{ label: string; x: number; y: number } | null>(null);

    // Overlay visibility toggles
    const [showHolidays, setShowHolidays]           = useState(true);
    const [showWorkspaceEvents, setShowWorkspaceEvents] = useState(true);

    // Zoom state
    const [refAreaLeft, setRefAreaLeft]   = useState<string | null>(null);
    const [refAreaRight, setRefAreaRight] = useState<string | null>(null);
    const [isSelecting, setIsSelecting]   = useState(false);
    const [zoomedIndices, setZoomedIndices] = useState<{ start: number; end: number } | null>(null);

    function toggle(key: SeriesKey): void {
        setVisible((prev) => {
            const next = new Set(prev);
            if (next.has(key)) {
                if (next.size === 1) return prev;
                next.delete(key);
            } else {
                next.add(key);
            }
            return next;
        });
    }

    function handleMouseDown(e: { activeLabel?: string | number }) {
        if (e?.activeLabel != null) {
            setRefAreaLeft(String(e.activeLabel));
            setIsSelecting(true);
        }
    }

    function handleMouseMove(e: { activeLabel?: string | number }) {
        if (isSelecting && e?.activeLabel != null) {
            setRefAreaRight(String(e.activeLabel));
        }
    }

    function handleMouseUp() {
        if (!isSelecting || !refAreaLeft) {
            setIsSelecting(false);
            setRefAreaLeft(null);
            setRefAreaRight(null);
            return;
        }

        let left  = refAreaLeft;
        let right = refAreaRight ?? refAreaLeft;
        if (left > right) [left, right] = [right, left];

        const startIdx = data.findIndex((p) => p.date >= left);
        let endIdx = -1;
        for (let i = data.length - 1; i >= 0; i--) {
            if (data[i].date <= right) { endIdx = i; break; }
        }

        if (startIdx !== -1 && endIdx !== -1 && endIdx > startIdx) {
            setZoomedIndices({ start: startIdx, end: endIdx });
        }

        setRefAreaLeft(null);
        setRefAreaRight(null);
        setIsSelecting(false);
    }

    function resetZoom() {
        setZoomedIndices(null);
        setRefAreaLeft(null);
        setRefAreaRight(null);
        setIsSelecting(false);
    }

    const displayData = useMemo(() => {
        if (!zoomedIndices) return data;
        return data.slice(zoomedIndices.start, zoomedIndices.end + 1);
    }, [data, zoomedIndices]);

    const displayComparison = useMemo(() => {
        if (!zoomedIndices || !comparisonData?.length) return comparisonData;
        return comparisonData.slice(zoomedIndices.start, zoomedIndices.end + 1);
    }, [comparisonData, zoomedIndices]);

    const merged = useMemo(() => {
        if (!displayComparison?.length) return displayData;
        return displayData.map((point, i) => {
            const cmp = displayComparison[i];
            return {
                ...point,
                compare_revenue:  cmp?.revenue   ?? null,
                compare_orders:   cmp?.orders    ?? null,
                compare_aov:      cmp?.aov       ?? null,
                compare_ad_spend: cmp?.ad_spend  ?? null,
                compare_roas:     cmp?.roas      ?? null,
            };
        });
    }, [displayData, displayComparison]);

    const hasLeft  = SERIES.some((s) => s.yAxis === 'left'  && visible.has(s.key));
    const hasRight = SERIES.some((s) => s.yAxis === 'right' && visible.has(s.key));

    const leftSeries  = SERIES.filter((s) => s.yAxis === 'left');
    const rightSeries = SERIES.filter((s) => s.yAxis === 'right');

    // Clamp event dates to visible data range when zoomed
    const displayFrom = displayData[0]?.date;
    const displayTo   = displayData[displayData.length - 1]?.date;

    const visibleHolidays = useMemo(() => {
        if (!holidays?.length) return [];
        if (!displayFrom || !displayTo) return holidays;
        return holidays.filter((h) => h.date >= displayFrom && h.date <= displayTo);
    }, [holidays, displayFrom, displayTo]);

    const visibleEvents = useMemo(() => {
        if (!workspaceEvents?.length) return [];
        if (!displayFrom || !displayTo) return workspaceEvents;
        return workspaceEvents.filter(
            (e) => e.date_from <= displayTo && e.date_to >= displayFrom,
        );
    }, [workspaceEvents, displayFrom, displayTo]);

    const hasHolidayOverlays      = (holidays?.length ?? 0) > 0;
    const hasWorkspaceEventOverlays = (workspaceEvents?.length ?? 0) > 0;

    return (
        <div className={className ?? 'w-full'}>
            {/* Series toggle pills + overlay toggles + zoom reset */}
            <div className="mb-3 flex flex-wrap items-center gap-1.5">
                {SERIES.map((s) => {
                    const on = visible.has(s.key);
                    return (
                        <button
                            key={s.key}
                            onClick={() => toggle(s.key)}
                            className={`flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors ${
                                on
                                    ? 'border-transparent text-white'
                                    : 'border-zinc-200 bg-white text-zinc-400 hover:text-zinc-600'
                            }`}
                            style={on ? { backgroundColor: s.color, borderColor: s.color } : undefined}
                        >
                            <span
                                className="h-1.5 w-1.5 rounded-full"
                                style={{ backgroundColor: on ? 'white' : s.color }}
                            />
                            {s.label}
                        </button>
                    );
                })}

                {/* Overlay toggles — only shown when there's relevant data */}
                {hasHolidayOverlays && (
                    <button
                        onClick={() => setShowHolidays((v) => !v)}
                        className={`flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors ${
                            showHolidays
                                ? 'border-zinc-300 bg-zinc-100 text-zinc-600'
                                : 'border-zinc-200 bg-white text-zinc-400 hover:text-zinc-600'
                        }`}
                    >
                        <span className="h-1.5 w-1.5 rounded-full bg-zinc-400" />
                        Holidays
                    </button>
                )}
                {hasWorkspaceEventOverlays && (
                    <button
                        onClick={() => setShowWorkspaceEvents((v) => !v)}
                        className={`flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors ${
                            showWorkspaceEvents
                                ? 'border-blue-300 bg-blue-50 text-blue-700'
                                : 'border-zinc-200 bg-white text-zinc-400 hover:text-zinc-600'
                        }`}
                    >
                        <span className="h-1.5 w-1.5 rounded-full bg-blue-400" />
                        Promotions
                    </button>
                )}

                {comparisonData?.length && (
                    <span className="flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-2.5 py-0.5 text-xs text-zinc-400">
                        <span className="inline-block h-0 w-3 border-t-2 border-dashed border-zinc-400" />
                        Previous period
                    </span>
                )}
                {zoomedIndices && (
                    <button
                        onClick={resetZoom}
                        className="ml-auto rounded-full border border-primary/20 bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary hover:bg-primary/15 transition-colors"
                    >
                        Reset zoom
                    </button>
                )}
            </div>

            <div
                className="relative h-64"
                style={{ userSelect: isSelecting ? 'none' : undefined }}
            >
                {/* Overlay tooltip — shown on hover of any marker */}
                {hoveredOverlay && (
                    <div
                        className="pointer-events-none absolute z-10 max-w-[220px] -translate-x-1/2 -translate-y-full rounded border border-zinc-200 bg-white px-2.5 py-1.5 text-xs text-zinc-700 shadow-md"
                        style={{ left: hoveredOverlay.x, top: hoveredOverlay.y - 10 }}
                    >
                        {hoveredOverlay.label}
                    </div>
                )}
                <ResponsiveContainer width="100%" height="100%" minWidth={0} initialDimension={{ width: 0, height: 1 }}>
                    <LineChart
                        data={merged}
                        margin={{ top: 4, right: hasRight ? 56 : 8, left: 0, bottom: 0 }}
                        onMouseDown={handleMouseDown}
                        onMouseMove={handleMouseMove}
                        onMouseUp={handleMouseUp}
                        onMouseLeave={() => {
                            if (isSelecting) {
                                setIsSelecting(false);
                                setRefAreaLeft(null);
                                setRefAreaRight(null);
                            }
                        }}
                        style={{ cursor: isSelecting ? 'col-resize' : 'crosshair' }}
                    >
                        <CartesianGrid strokeDasharray="3 3" stroke="#f4f4f5" vertical={false} />
                        <XAxis
                            dataKey="date"
                            tickLine={false}
                            axisLine={false}
                            tick={{ fontSize: 11, fill: '#a1a1aa' }}
                            tickFormatter={(d) => formatDate(d, granularity, timezone)}
                            minTickGap={40}
                        />

                        {/* Left Y-axis — currency */}
                        <YAxis
                            yAxisId="left"
                            orientation="left"
                            tickLine={false}
                            axisLine={false}
                            tick={{ fontSize: 11, fill: '#a1a1aa' }}
                            tickFormatter={(v) => {
                                const first = leftSeries.find((s) => visible.has(s.key));
                                return first ? formatAxis(v, first.valueType, currency) : String(v);
                            }}
                            width={60}
                            hide={!hasLeft}
                        />

                        {/* Right Y-axis — count / ratio */}
                        <YAxis
                            yAxisId="right"
                            orientation="right"
                            tickLine={false}
                            axisLine={false}
                            tick={{ fontSize: 11, fill: '#a1a1aa' }}
                            tickFormatter={(v) => {
                                const first = rightSeries.find((s) => visible.has(s.key));
                                return first ? formatAxis(v, first.valueType, currency) : String(v);
                            }}
                            width={52}
                            hide={!hasRight}
                        />

                        <Tooltip
                            contentStyle={{
                                fontSize: 12,
                                borderRadius: 8,
                                border: '1px solid #e4e4e7',
                                boxShadow: '0 1px 8px rgba(0,0,0,0.08)',
                            }}
                            formatter={(value: unknown, name: unknown) => {
                                const num = typeof value === 'number' ? value : Number(value);
                                const key = String(name);
                                if (key.startsWith('compare_')) {
                                    const baseKey = key.replace('compare_', '') as SeriesKey;
                                    const cfg = SERIES.find((s) => s.key === baseKey);
                                    return [
                                        cfg ? formatValue(num, cfg.valueType, currency) : String(num),
                                        `${cfg?.label ?? baseKey} (prev)`,
                                    ] as [string, string];
                                }
                                const cfg = SERIES.find((s) => s.key === key);
                                return [
                                    cfg ? formatValue(num, cfg.valueType, currency) : String(num),
                                    cfg?.label ?? key,
                                ] as [string, string];
                            }}
                            labelFormatter={(label) => formatDate(String(label), granularity, timezone)}
                        />

                        {/* Drag-to-zoom selection area */}
                        {refAreaLeft && refAreaRight && (
                            <ReferenceArea
                                yAxisId="left"
                                x1={refAreaLeft}
                                x2={refAreaRight}
                                strokeOpacity={0.3}
                                fill="var(--chart-1)"
                                fillOpacity={0.1}
                            />
                        )}

                        {/* ── Holiday overlays — gray dashed vertical lines ───────────── */}
                        {showHolidays && visibleHolidays.map(({ date, name }) => (
                            <ReferenceLine
                                key={`holiday-${date}`}
                                x={date}
                                yAxisId="left"
                                stroke="#a1a1aa"
                                strokeDasharray="3 3"
                                strokeWidth={1}
                                label={(props: object) => (
                                    <OverlayMarker
                                        {...(props as OverlayMarkerProps)}
                                        label={name}
                                        color="#a1a1aa"
                                        onHoverChange={setHoveredOverlay}
                                    />
                                )}
                            />
                        ))}

                        {/* ── Workspace event overlays — blue shaded areas or lines ──── */}
                        {showWorkspaceEvents && visibleEvents.map((event) => {
                            const isSingleDay = event.date_from === event.date_to;
                            const label = `${event.name}${event.event_type !== 'promotion' ? ` (${event.event_type.replace(/_/g, ' ')})` : ''}`;

                            if (isSingleDay) {
                                return (
                                    <ReferenceLine
                                        key={`event-${event.date_from}-${event.name}`}
                                        x={event.date_from}
                                        yAxisId="left"
                                        stroke="#3b82f6"
                                        strokeDasharray="3 3"
                                        strokeWidth={1.5}
                                        label={(props: object) => (
                                            <OverlayMarker
                                                {...(props as OverlayMarkerProps)}
                                                label={label}
                                                color="#3b82f6"
                                                onHoverChange={setHoveredOverlay}
                                            />
                                        )}
                                    />
                                );
                            }

                            // Multi-day event: shaded reference area + marker at start
                            return (
                                <React.Fragment key={`event-${event.date_from}-${event.name}`}>
                                    <ReferenceArea
                                        yAxisId="left"
                                        x1={event.date_from}
                                        x2={event.date_to}
                                        fill="#3b82f6"
                                        fillOpacity={0.06}
                                        stroke="#3b82f6"
                                        strokeOpacity={0.2}
                                    />
                                    <ReferenceLine
                                        x={event.date_from}
                                        yAxisId="left"
                                        stroke="#3b82f6"
                                        strokeDasharray="3 3"
                                        strokeWidth={1.5}
                                        label={(props: object) => (
                                            <OverlayMarker
                                                {...(props as OverlayMarkerProps)}
                                                label={label}
                                                color="#3b82f6"
                                                onHoverChange={setHoveredOverlay}
                                            />
                                        )}
                                    />
                                </React.Fragment>
                            );
                        })}

                        {/* ── Daily note overlays — amber dashed lines ────────────────── */}
                        {notes?.map(({ date, note }) => (
                            <ReferenceLine
                                key={`note-${date}`}
                                x={date}
                                yAxisId="left"
                                stroke="#d4d4d8"
                                strokeDasharray="3 3"
                                strokeWidth={1}
                                label={(props: object) => (
                                    <OverlayMarker
                                        {...(props as OverlayMarkerProps)}
                                        label={note}
                                        color="#f59e0b"
                                        onHoverChange={setHoveredOverlay}
                                    />
                                )}
                            />
                        ))}

                        {/* Comparison lines — one per visible series */}
                        {comparisonData?.length && SERIES.map((s) =>
                            visible.has(s.key) ? (
                                <Line
                                    key={`compare_${s.key}`}
                                    yAxisId={s.yAxis}
                                    type="monotone"
                                    dataKey={`compare_${s.key}`}
                                    name={`compare_${s.key}`}
                                    stroke={s.color}
                                    strokeWidth={1.5}
                                    dot={false}
                                    strokeDasharray="4 2"
                                    connectNulls
                                />
                            ) : null,
                        )}

                        {/* Main series */}
                        {SERIES.map((s) =>
                            visible.has(s.key) ? (
                                <Line
                                    key={s.key}
                                    yAxisId={s.yAxis}
                                    type="monotone"
                                    dataKey={s.key}
                                    name={s.key}
                                    stroke={s.color}
                                    strokeWidth={2}
                                    dot={false}
                                    connectNulls
                                />
                            ) : null,
                        )}
                    </LineChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
});

export { MultiSeriesLineChartInner as MultiSeriesLineChart };
