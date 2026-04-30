/**
 * CwvTrendChart — LCP / INP / CLS p75 over time for the /performance page.
 *
 * Three-series Recharts LineChart:
 *   - LCP p75 (ms) on left Y-axis
 *   - INP p75 (ms) on left Y-axis (same scale as LCP)
 *   - CLS p75 (unitless 0–1) on right Y-axis
 *
 * Dotted rightmost segment when `is_partial = true` (current incomplete week).
 * Vertical annotation lines for deploy events (same style as /seo).
 *
 * Google canonical threshold lines (horizontal reference):
 *   - LCP: 2500ms (good boundary) and 4000ms (poor boundary)
 *   - INP: 200ms good, 500ms poor
 *   - CLS right axis: 0.1 good, 0.25 poor
 *
 * Patterns:
 *   - Shopify Online Store Speed: event annotation tags as numbered vertical lines
 *   - Plausible: dotted right-edge for incomplete period, always-on
 *
 * @see docs/pages/performance.md §LineChart
 * @see docs/competitors/_research_performance_page.md §4 Score visualization
 */

import React, { useLayoutEffect, useRef, useState } from 'react';
import {
    ComposedChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ReferenceLine,
    ReferenceArea,
    ResponsiveContainer,
} from 'recharts';
import { cn } from '@/lib/utils';

interface TrendPoint {
    date: string;
    lcp_p75: number;
    inp_p75: number;
    cls_p75: number;
    is_partial: boolean;
}

interface Annotation {
    date: string;
    name: string;
    event_type: string;
}

interface Props {
    data: TrendPoint[];
    annotations?: Annotation[];
    className?: string;
}

function fmtDateShort(date: string): string {
    try {
        return new Date(date).toLocaleDateString('en', { month: 'short', day: 'numeric' });
    } catch {
        return date;
    }
}

/** Tooltip content — shows all three metrics for the hovered week. */
function CwvTooltip({
    active,
    payload,
    label,
}: {
    active?: boolean;
    payload?: Array<{ name: string; value: number; color: string }>;
    label?: string;
}) {
    if (!active || !payload?.length) return null;

    const lcp = payload.find((p) => p.name === 'LCP p75');
    const inp = payload.find((p) => p.name === 'INP p75');
    const cls = payload.find((p) => p.name === 'CLS p75');

    function fmtMs(v: number) {
        return v >= 1000 ? `${(v / 1000).toFixed(2)}s` : `${Math.round(v)}ms`;
    }

    function band(metric: 'lcp' | 'inp' | 'cls', v: number): string {
        if (metric === 'lcp') return v <= 2500 ? 'Good' : v <= 4000 ? 'Needs Improvement' : 'Poor';
        if (metric === 'inp') return v <= 200 ? 'Good' : v <= 500 ? 'Needs Improvement' : 'Poor';
        return v <= 0.1 ? 'Good' : v <= 0.25 ? 'Needs Improvement' : 'Poor';
    }

    const bandColor: Record<string, string> = {
        'Good': 'text-emerald-600',
        'Needs Improvement': 'text-amber-600',
        'Poor': 'text-rose-600',
    };

    return (
        <div className="rounded-lg border border-zinc-200 bg-white px-3 py-2.5 shadow-sm text-xs">
            <p className="mb-1.5 font-medium text-zinc-700">{fmtDateShort(label ?? '')}</p>
            {lcp && (
                <div className="flex items-center justify-between gap-4 mb-0.5">
                    <span className="flex items-center gap-1.5">
                        <span className="h-2 w-2 rounded-full" style={{ background: lcp.color }} />
                        LCP p75
                    </span>
                    <span className="tabular-nums font-medium text-zinc-800">
                        {fmtMs(lcp.value)} <span className={cn('font-normal', bandColor[band('lcp', lcp.value)])}>{band('lcp', lcp.value)}</span>
                    </span>
                </div>
            )}
            {inp && (
                <div className="flex items-center justify-between gap-4 mb-0.5">
                    <span className="flex items-center gap-1.5">
                        <span className="h-2 w-2 rounded-full" style={{ background: inp.color }} />
                        INP p75
                    </span>
                    <span className="tabular-nums font-medium text-zinc-800">
                        {fmtMs(inp.value)} <span className={cn('font-normal', bandColor[band('inp', inp.value)])}>{band('inp', inp.value)}</span>
                    </span>
                </div>
            )}
            {cls && (
                <div className="flex items-center justify-between gap-4">
                    <span className="flex items-center gap-1.5">
                        <span className="h-2 w-2 rounded-full" style={{ background: cls.color }} />
                        CLS p75
                    </span>
                    <span className="tabular-nums font-medium text-zinc-800">
                        {cls.value.toFixed(3)} <span className={cn('font-normal', bandColor[band('cls', cls.value)])}>{band('cls', cls.value)}</span>
                    </span>
                </div>
            )}
        </div>
    );
}

export function CwvTrendChart({ data, annotations = [], className }: Props) {
    const containerRef = useRef<HTMLDivElement>(null);
    const [width, setWidth] = useState(0);

    useLayoutEffect(() => {
        if (!containerRef.current) return;
        const ro = new ResizeObserver((entries) => {
            setWidth(entries[0].contentRect.width);
        });
        ro.observe(containerRef.current);
        return () => ro.disconnect();
    }, []);

    if (!data.length) {
        return (
            <div className={cn('flex h-56 items-center justify-center text-sm text-zinc-400', className)}>
                No trend data for this period.
            </div>
        );
    }

    const partialDate = data.find((d) => d.is_partial)?.date;

    // Annotation label map: date → short label
    const annotationMap = Object.fromEntries(annotations.map((a) => [a.date, a.name]));

    return (
        <div ref={containerRef} className={cn('w-full', className)}>
            <div className="flex items-center gap-4 mb-3 text-xs text-zinc-500">
                <span className="flex items-center gap-1.5">
                    <span className="h-0.5 w-6 rounded" style={{ background: 'var(--chart-2)' }} />
                    LCP p75
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="h-0.5 w-6 rounded" style={{ background: 'var(--chart-3)' }} />
                    INP p75
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="h-0.5 w-6 rounded" style={{ background: 'var(--chart-4)' }} />
                    CLS p75 (right axis)
                </span>
                <span className="flex items-center gap-1.5 ml-auto">
                    <span className="h-px w-6 border-t border-dashed border-zinc-400" />
                    Threshold (Good)
                </span>
            </div>

            <ComposedChart
                width={width || 800}
                height={220}
                data={data}
                margin={{ top: 8, right: 48, left: 0, bottom: 0 }}
            >
                <CartesianGrid strokeDasharray="3 3" stroke="var(--border-subtle)" vertical={false} />

                <XAxis
                    dataKey="date"
                    tickFormatter={fmtDateShort}
                    tick={{ fontSize: 11, fill: 'var(--color-text-muted)' }}
                    axisLine={false}
                    tickLine={false}
                    tickCount={6}
                />

                {/* Left Y axis: LCP + INP in ms */}
                <YAxis
                    yAxisId="ms"
                    orientation="left"
                    tickFormatter={(v) => v >= 1000 ? `${(v / 1000).toFixed(1)}s` : `${v}ms`}
                    tick={{ fontSize: 11, fill: 'var(--color-text-muted)' }}
                    axisLine={false}
                    tickLine={false}
                    width={42}
                />

                {/* Right Y axis: CLS 0–0.4 */}
                <YAxis
                    yAxisId="cls"
                    orientation="right"
                    domain={[0, 0.4]}
                    tickFormatter={(v) => v.toFixed(2)}
                    tick={{ fontSize: 11, fill: 'var(--color-text-muted)' }}
                    axisLine={false}
                    tickLine={false}
                    width={36}
                />

                <Tooltip content={<CwvTooltip />} />

                {/* Google canonical threshold lines — dotted horizontal */}
                {/* LCP Good: 2500ms */}
                <ReferenceLine yAxisId="ms" y={2500} stroke="var(--color-success)" strokeDasharray="4 3" strokeOpacity={0.5} />
                {/* INP Good: 200ms */}
                <ReferenceLine yAxisId="ms" y={200} stroke="var(--chart-3)" strokeDasharray="4 3" strokeOpacity={0.4} />
                {/* CLS Good: 0.1 */}
                <ReferenceLine yAxisId="cls" y={0.1} stroke="var(--chart-4)" strokeDasharray="4 3" strokeOpacity={0.4} />

                {/* Annotation lines (deploy events) — Shopify pattern */}
                {annotations.map((ann) => (
                    <ReferenceLine
                        key={ann.date}
                        yAxisId="ms"
                        x={ann.date}
                        stroke="var(--color-text-muted)"
                        strokeDasharray="3 3"
                        strokeOpacity={0.6}
                        label={{
                            value: ann.name.length > 20 ? ann.name.slice(0, 18) + '…' : ann.name,
                            position: 'top',
                            fontSize: 10,
                            fill: 'var(--color-text-muted)',
                        }}
                    />
                ))}

                {/* Partial period shading (current incomplete week) — Plausible pattern */}
                {partialDate && (
                    <ReferenceArea
                        yAxisId="ms"
                        x1={partialDate}
                        fill="var(--border-subtle)"
                        fillOpacity={0.35}
                    />
                )}

                {/* LCP p75 — solid line (the partial-period shading via ReferenceArea handles the visual cue) */}
                <Line
                    yAxisId="ms"
                    type="monotone"
                    dataKey="lcp_p75"
                    name="LCP p75"
                    stroke="var(--chart-2)"
                    strokeWidth={2}
                    dot={false}
                    activeDot={{ r: 4, strokeWidth: 0 }}
                />

                {/* INP p75 */}
                <Line
                    yAxisId="ms"
                    type="monotone"
                    dataKey="inp_p75"
                    name="INP p75"
                    stroke="var(--chart-3)"
                    strokeWidth={2}
                    dot={false}
                    activeDot={{ r: 4, strokeWidth: 0 }}
                />

                {/* CLS p75 — right axis */}
                <Line
                    yAxisId="cls"
                    type="monotone"
                    dataKey="cls_p75"
                    name="CLS p75"
                    stroke="var(--chart-4)"
                    strokeWidth={2}
                    dot={false}
                    activeDot={{ r: 4, strokeWidth: 0 }}
                />
            </ComposedChart>
        </div>
    );
}
