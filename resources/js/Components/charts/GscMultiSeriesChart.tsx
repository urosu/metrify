import React, { useMemo, useState } from 'react';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    ReferenceArea,
} from 'recharts';
import { formatDate, formatNumber, type Granularity } from '@/lib/formatters';

// Related: resources/js/Pages/Seo/Index.tsx (consumer)

export interface GscDataPoint {
    date: string;
    clicks: number;
    impressions: number;
    ctr: number | null;       // raw fraction, e.g. 0.05 = 5%
    position: number | null;  // avg ranking position — lower is better
}

type SeriesKey = 'clicks' | 'impressions' | 'ctr' | 'position';

interface SeriesConfig {
    key: SeriesKey;
    label: string;
    color: string;
    yAxis: 'left' | 'right';
    valueType: 'number' | 'percent' | 'position';
}

// Left axis: count metrics. Right axis: rate/position metrics.
const SERIES: SeriesConfig[] = [
    { key: 'clicks',      label: 'Clicks',      color: 'var(--chart-1)', yAxis: 'left',  valueType: 'number'   },
    { key: 'impressions', label: 'Impressions',  color: 'var(--chart-5)', yAxis: 'left',  valueType: 'number'   },
    { key: 'ctr',         label: 'CTR',          color: 'var(--chart-2)', yAxis: 'right', valueType: 'percent'  },
    { key: 'position',    label: 'Avg Position', color: 'var(--chart-4)', yAxis: 'right', valueType: 'position' },
];

function formatValue(value: number, valueType: SeriesConfig['valueType']): string {
    if (valueType === 'percent')  return `${(value * 100).toFixed(2)}%`;
    if (valueType === 'position') return value.toFixed(1);
    return formatNumber(value);
}

function formatAxisTick(value: number, valueType: SeriesConfig['valueType']): string {
    if (valueType === 'percent')  return `${(value * 100).toFixed(0)}%`;
    if (valueType === 'position') return value.toFixed(0);
    return formatNumber(value, true);
}

interface Props {
    data: GscDataPoint[];
    granularity: Granularity;
    timezone?: string;
    className?: string;
}

export function GscMultiSeriesChart({ data, granularity, timezone, className }: Props) {
    const [visible, setVisible] = useState<Set<SeriesKey>>(new Set(['clicks']));

    // Zoom state — drag to zoom like MultiSeriesLineChart
    const [refAreaLeft,  setRefAreaLeft]  = useState<string | null>(null);
    const [refAreaRight, setRefAreaRight] = useState<string | null>(null);
    const [isSelecting,  setIsSelecting]  = useState(false);
    const [zoomedIndices, setZoomedIndices] = useState<{ start: number; end: number } | null>(null);

    function toggle(key: SeriesKey): void {
        setVisible((prev) => {
            const next = new Set(prev);
            if (next.has(key)) {
                if (next.size === 1) return prev; // keep at least one series
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

    const hasLeft  = SERIES.some((s) => s.yAxis === 'left'  && visible.has(s.key));
    const hasRight = SERIES.some((s) => s.yAxis === 'right' && visible.has(s.key));

    const leftSeries  = SERIES.filter((s) => s.yAxis === 'left');
    const rightSeries = SERIES.filter((s) => s.yAxis === 'right');

    return (
        <div className={className ?? 'w-full'}>
            {/* Series toggle pills */}
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
                className="relative h-56"
                style={{ userSelect: isSelecting ? 'none' : undefined }}
            >
                <ResponsiveContainer width="100%" height="100%" minWidth={0} initialDimension={{ width: 0, height: 1 }}>
                    <LineChart
                        data={displayData}
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

                        {/* Left Y-axis — click/impression counts */}
                        <YAxis
                            yAxisId="left"
                            orientation="left"
                            tickLine={false}
                            axisLine={false}
                            tick={{ fontSize: 11, fill: '#a1a1aa' }}
                            tickFormatter={(v) => {
                                const first = leftSeries.find((s) => visible.has(s.key));
                                return first ? formatAxisTick(v, first.valueType) : String(v);
                            }}
                            width={52}
                            hide={!hasLeft}
                        />

                        {/* Right Y-axis — CTR % or position */}
                        <YAxis
                            yAxisId="right"
                            orientation="right"
                            tickLine={false}
                            axisLine={false}
                            tick={{ fontSize: 11, fill: '#a1a1aa' }}
                            tickFormatter={(v) => {
                                const first = rightSeries.find((s) => visible.has(s.key));
                                return first ? formatAxisTick(v, first.valueType) : String(v);
                            }}
                            width={44}
                            hide={!hasRight}
                            // Why: position axis is inverted — rank 1 is best, higher numbers are worse.
                            // Reversing the domain puts the best rank at the top of the chart.
                            reversed={visible.has('position') && !visible.has('ctr')}
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
                                const cfg = SERIES.find((s) => s.key === String(name));
                                if (!cfg) return [String(value), String(name)];
                                const label = cfg.key === 'position'
                                    ? `${cfg.label} (lower = better)`
                                    : cfg.label;
                                return [formatValue(num, cfg.valueType), label] as [string, string];
                            }}
                            labelFormatter={(label) => formatDate(String(label), granularity, timezone)}
                        />

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
}
