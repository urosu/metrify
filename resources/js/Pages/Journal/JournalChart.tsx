/**
 * JournalChart — annotated line chart for the daily journal.
 *
 * Renders the selected primary metric (Revenue, AOV, ROAS, Ad Spend, or Orders)
 * as a Recharts LineChart with one vertical ReferenceLine per note that passes
 * the active category filter.
 *
 * Annotation line colors follow the note category palette from types.ts.
 * Hover an annotation flag → AnnotationTooltip from ChartAnnotationLayer.
 *
 * Uses the existing LineChart primitive's NoteMarker pattern and extends it
 * with category-aware stroke colors (backward-compatible — LineChart.notes
 * still renders a neutral zinc line; JournalChart adds per-category color).
 *
 * @see docs/competitors/_research_daily_journal.md §4 Chart integration
 * @see resources/js/Components/shared/ChartAnnotationLayer.tsx
 * @see resources/js/Components/charts/LineChart.tsx
 */
import React, { useLayoutEffect, useRef, useState } from 'react';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ReferenceLine,
} from 'recharts';
import type { DayRow, JournalNote, ChartMetric, NoteCategory } from './types';
import { NOTE_CATEGORY_STYLE, NOTE_CATEGORY_LABELS } from './types';

interface JournalChartProps {
    days: DayRow[];
    notes: JournalNote[];
    metric: ChartMetric;
    activeCategories: NoteCategory[];
}

function formatValue(value: number, metric: ChartMetric): string {
    if (metric === 'revenue' || metric === 'ad_spend' || metric === 'aov') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency', currency: 'USD', maximumFractionDigits: 0,
        }).format(value);
    }
    if (metric === 'roas') return `${value.toFixed(2)}×`;
    return new Intl.NumberFormat('en-US').format(Math.round(value));
}

function metricValueForRow(row: DayRow, metric: ChartMetric): number {
    switch (metric) {
        case 'revenue':  return row.revenue;
        case 'aov':      return row.aov;
        case 'roas':     return row.roas;
        case 'ad_spend': return row.ad_spend;
        case 'orders':   return row.orders;
    }
}

interface AnnotationLabelProps {
    viewBox?: { x?: number; y?: number; width?: number; height?: number };
    note: JournalNote;
    onHover: (payload: { note: JournalNote; x: number; y: number } | null) => void;
}

/**
 * SVG flag label rendered at the top of each annotation reference line.
 * Colors are driven by note category.
 */
function AnnotationLabel({ viewBox, note, onHover }: AnnotationLabelProps) {
    if (!viewBox) return null;
    const { x = 0, y = 0 } = viewBox;
    const style = NOTE_CATEGORY_STYLE[note.category];
    const label = NOTE_CATEGORY_LABELS[note.category];
    const rectW = Math.min(label.length * 6 + 10, 100);

    return (
        <g
            onMouseEnter={() => onHover({ note, x, y })}
            onMouseLeave={() => onHover(null)}
            style={{ cursor: 'default' }}
        >
            <rect
                x={x + 1}
                y={y}
                width={rectW}
                height={15}
                rx={3}
                fill={style.bg.replace('bg-', '').replace('50', '#fefce8').replace('indigo', '#eef2ff').replace('blue', '#eff6ff').replace('emerald', '#ecfdf5').replace('zinc', '#f4f4f5')}
                stroke={style.chartStroke}
                strokeWidth={0.8}
                opacity={0.95}
            />
            <text
                x={x + 5}
                y={y + 10.5}
                fontSize={8}
                fontFamily="var(--font-sans, system-ui)"
                fontWeight={600}
                fill={style.chartStroke}
            >
                {label.length > 12 ? label.slice(0, 11) + '…' : label}
            </text>
        </g>
    );
}

export function JournalChart({ days, notes, metric, activeCategories }: JournalChartProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const [chartSize, setChartSize] = useState<{ w: number; h: number } | null>(null);
    const [hoveredAnnotation, setHoveredAnnotation] = useState<{
        note: JournalNote; x: number; y: number;
    } | null>(null);

    useLayoutEffect(() => {
        const el = containerRef.current;
        if (!el) return;
        const { width, height } = el.getBoundingClientRect();
        if (width > 0) setChartSize({ w: width, h: height });
        const ro = new ResizeObserver(([entry]) => {
            const { inlineSize: w, blockSize: h } = entry.contentBoxSize[0];
            setChartSize({ w, h });
        });
        ro.observe(el);
        return () => ro.disconnect();
    }, []);

    // Build chart data from days
    const chartData = days.map((row) => ({
        date: row.date.slice(5),   // MM-DD for x-axis labels
        fullDate: row.date,
        value: metricValueForRow(row, metric),
    }));

    // Filter notes by active categories
    const visibleNotes = notes.filter((note) =>
        activeCategories.length === 0 || activeCategories.includes(note.category),
    );

    // Map note dates to MM-DD for x-axis lookup
    const noteDateSet = new Set(visibleNotes.map((n) => n.date.slice(5)));

    return (
        <div className="relative" ref={containerRef} style={{ height: 220 }}>
            {/* Annotation tooltip */}
            {hoveredAnnotation && (
                <div
                    className="pointer-events-none absolute z-30 w-56 rounded-lg border border-zinc-200 bg-white p-3 text-xs shadow-lg"
                    style={{
                        left:      hoveredAnnotation.x + 8,
                        top:       hoveredAnnotation.y,
                        transform: 'translateY(-50%)',
                    }}
                >
                    <p className="mb-0.5 font-semibold text-zinc-900">
                        {NOTE_CATEGORY_LABELS[hoveredAnnotation.note.category]}
                    </p>
                    <p className="leading-relaxed text-zinc-600">{hoveredAnnotation.note.text}</p>
                    <p className="mt-1 text-zinc-400">
                        {hoveredAnnotation.note.date} · by {hoveredAnnotation.note.author}
                    </p>
                </div>
            )}

            {chartSize && (
                <LineChart
                    width={chartSize.w}
                    height={chartSize.h}
                    data={chartData}
                    margin={{ top: 8, right: 16, left: 0, bottom: 0 }}
                >
                    <CartesianGrid strokeDasharray="3 3" stroke="#f4f4f5" vertical={false} />
                    <XAxis
                        dataKey="date"
                        tickLine={false}
                        axisLine={false}
                        tick={{ fontSize: 11, fill: '#a1a1aa' }}
                        minTickGap={30}
                        interval="preserveStartEnd"
                    />
                    <YAxis
                        tickLine={false}
                        axisLine={false}
                        tick={{ fontSize: 11, fill: '#a1a1aa' }}
                        tickFormatter={(v) => formatValue(v, metric)}
                        width={68}
                    />
                    <Tooltip
                        contentStyle={{
                            fontSize:     12,
                            borderRadius: 8,
                            border:       '1px solid #e4e4e7',
                            boxShadow:    '0 1px 8px rgba(0,0,0,0.08)',
                        }}
                        formatter={(value: unknown) => [
                            formatValue(Number(value), metric),
                        ]}
                    />

                    {/* Primary metric line */}
                    <Line
                        type="monotone"
                        dataKey="value"
                        stroke="var(--chart-1, #6366f1)"
                        strokeWidth={2}
                        dot={false}
                        connectNulls
                    />

                    {/* One ReferenceLine per note — category-colored dashed vertical */}
                    {visibleNotes.map((note) => {
                        const xKey = note.date.slice(5);
                        if (!noteDateSet.has(xKey)) return null;
                        const style = NOTE_CATEGORY_STYLE[note.category];
                        return (
                            <ReferenceLine
                                key={note.id}
                                x={xKey}
                                stroke={style.chartStroke}
                                strokeDasharray="4 3"
                                strokeWidth={1}
                                label={(props: object) => (
                                    <AnnotationLabel
                                        {...(props as { viewBox?: { x?: number; y?: number; width?: number; height?: number } })}
                                        note={note}
                                        onHover={setHoveredAnnotation}
                                    />
                                )}
                            />
                        );
                    })}
                </LineChart>
            )}
        </div>
    );
}
