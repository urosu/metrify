/**
 * DaypartHeatmap — 7×24 grid (rows = day of week, cols = hour of day).
 *
 * Cell value = any metric (CPA, sales, purchases, etc.).
 * Color scale from zinc-100 (low) → metric source color (high).
 * Desktop-only per UX §8.
 *
 * Used on /ads (ad scheduling) and /products (top-SKU sales by daypart).
 *
 * Custom SVG — not Recharts.
 *
 * @see docs/UX.md §5.6 Chart primitives — DaypartHeatmap
 */
import { cn } from '@/lib/utils';

export interface DaypartCell {
    day: number;   /* 0 = Sunday, 6 = Saturday */
    hour: number;  /* 0–23 */
    value: number;
    formatted?: string;
}

interface DaypartHeatmapProps {
    cells: DaypartCell[];
    /** Color for max-value cells. Default: zinc-900. */
    highColor?: string;
    metricLabel?: string;
    className?: string;
}

const DAY_LABELS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const CELL_W = 22;
const CELL_H = 22;
const ROW_LABEL_W = 32;
const COL_LABEL_H = 20;

/** Interpolate between two OKLCH colors based on t (0–1). */
function interpolateColor(t: number): string {
    // Low: zinc-100 oklch(0.967 0 0) → High: zinc-700 oklch(0.37 0 0)
    const l = 0.967 - t * 0.597;
    return `oklch(${l.toFixed(3)} 0 0)`;
}

export function DaypartHeatmap({
    cells,
    highColor,
    metricLabel = 'Value',
    className,
}: DaypartHeatmapProps) {
    const allValues = cells.map((c) => c.value);
    const maxVal = Math.max(...allValues, 0.001);
    const minVal = Math.min(...allValues, 0);

    const cellMap = new Map<string, DaypartCell>();
    cells.forEach((c) => cellMap.set(`${c.day}-${c.hour}`, c));

    const width = ROW_LABEL_W + 24 * CELL_W;
    const height = COL_LABEL_H + 7 * CELL_H;

    return (
        <div className={cn('', className)}>
            <div className="hidden max-[1279px]:block rounded-lg border border-border bg-zinc-50 p-6 text-center text-sm text-zinc-500">
                DaypartHeatmap requires a desktop screen (1280px+).
            </div>

            <div className="max-[1279px]:hidden overflow-x-auto">
                <svg
                    width={width}
                    height={height}
                    role="img"
                    aria-label={`${metricLabel} by day and hour`}
                    style={{ fontFamily: 'var(--font-sans)' }}
                >
                    {/* Hour labels */}
                    {[0, 3, 6, 9, 12, 15, 18, 21].map((h) => (
                        <text
                            key={h}
                            x={ROW_LABEL_W + h * CELL_W + CELL_W / 2}
                            y={COL_LABEL_H - 4}
                            textAnchor="middle"
                            fontSize={9}
                            fill="oklch(0.552 0 0)"
                        >
                            {h === 0 ? '12am' : h < 12 ? `${h}am` : h === 12 ? '12pm' : `${h - 12}pm`}
                        </text>
                    ))}

                    {/* Cells */}
                    {DAY_LABELS.map((dayLabel, dayIdx) => (
                        <g key={dayIdx}>
                            {/* Day label */}
                            <text
                                x={ROW_LABEL_W - 4}
                                y={COL_LABEL_H + dayIdx * CELL_H + CELL_H / 2 + 4}
                                textAnchor="end"
                                fontSize={9}
                                fill="oklch(0.552 0 0)"
                            >
                                {dayLabel}
                            </text>

                            {Array.from({ length: 24 }).map((_, hourIdx) => {
                                const cell = cellMap.get(`${dayIdx}-${hourIdx}`);
                                const value = cell?.value ?? 0;
                                const t = maxVal > minVal ? (value - minVal) / (maxVal - minVal) : 0;
                                const fill = highColor
                                    ? `oklch(from ${highColor} calc(l + ${(0.97 - 0.1) * (1 - t)}) calc(c * ${t}) h)`
                                    : interpolateColor(t);
                                const cx = ROW_LABEL_W + hourIdx * CELL_W;
                                const cy = COL_LABEL_H + dayIdx * CELL_H;

                                return (
                                    <rect
                                        key={hourIdx}
                                        x={cx + 0.5}
                                        y={cy + 0.5}
                                        width={CELL_W - 1}
                                        height={CELL_H - 1}
                                        rx={2}
                                        fill={fill}
                                    >
                                        <title>
                                            {dayLabel} {hourIdx}:00 — {cell?.formatted ?? value.toLocaleString()}
                                        </title>
                                    </rect>
                                );
                            })}
                        </g>
                    ))}
                </svg>

                {/* Accessible table */}
                <table className="sr-only">
                    <caption>{metricLabel} by day and hour</caption>
                    <thead>
                        <tr>
                            <th>Hour</th>
                            {DAY_LABELS.map((d) => <th key={d}>{d}</th>)}
                        </tr>
                    </thead>
                    <tbody>
                        {Array.from({ length: 24 }).map((_, hour) => (
                            <tr key={hour}>
                                <th>{hour}:00</th>
                                {DAY_LABELS.map((_, day) => {
                                    const cell = cellMap.get(`${day}-${hour}`);
                                    return <td key={day}>{cell?.formatted ?? cell?.value ?? '—'}</td>;
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
