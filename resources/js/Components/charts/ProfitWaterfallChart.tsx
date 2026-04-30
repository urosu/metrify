/**
 * ProfitWaterfallChart — horizontal waterfall showing P&L breakdown.
 *
 * Sequence (UX §5.17):
 *   Gross revenue → (discounts) → (refunds) → Net revenue → (COGS) → (shipping)
 *   → (transaction fees) → Contribution margin → (ad spend) → Gross profit
 *   → (OpEx) → Net profit
 *
 * Positive bars = emerald; negative = rose; subtotals = zinc.
 * Missing costs render as dashed bar labeled "Not configured — click to add".
 *
 * Custom SVG — not Recharts.
 *
 * @see docs/UX.md §5.17 ProfitWaterfallChart
 * @see docs/PLANNING.md §4 Q4 (custom SVG, waterfall semantics off-label for Recharts)
 */
import { cn } from '@/lib/utils';

export interface WaterfallBar {
    id: string;
    label: string;
    /** Positive = gain, negative = deduction, null = not configured */
    value: number | null;
    type: 'start' | 'add' | 'subtract' | 'subtotal' | 'end';
    formatted?: string;
    /** If true, renders as dashed "not configured" bar. */
    notConfigured?: boolean;
    /** Source of this cost item. */
    source?: string;
}

interface ProfitWaterfallChartProps {
    bars: WaterfallBar[];
    currency?: string;
    /** Called when a deduction bar is clicked (opens cost config). */
    onBarClick?: (bar: WaterfallBar) => void;
    compact?: boolean;
    className?: string;
}

const BAR_HEIGHT = 28;
const BAR_GAP = 6;
const LABEL_W = 160;
const VALUE_W = 80;
const MIN_BAR_PX = 4;

export function ProfitWaterfallChart({
    bars,
    currency = '',
    onBarClick,
    compact = false,
    className,
}: ProfitWaterfallChartProps) {
    const barH = compact ? 22 : BAR_HEIGHT;
    const barGap = compact ? 4 : BAR_GAP;
    const rowH = barH + barGap;

    // Compute running total to position bars
    const positions: Array<{ bar: WaterfallBar; start: number; end: number; runningTotal: number }> = [];
    let running = 0;
    for (const bar of bars) {
        if (bar.type === 'start' || bar.type === 'subtotal' || bar.type === 'end') {
            positions.push({ bar, start: 0, end: running + (bar.value ?? 0), runningTotal: running });
            if (bar.type === 'start') running = bar.value ?? 0;
        } else {
            const val = bar.value ?? 0;
            const prev = running;
            running += val;
            positions.push({ bar, start: Math.min(prev, running), end: Math.max(prev, running), runningTotal: prev });
        }
    }

    const allEnds = positions.map((p) => p.end);
    const maxVal = Math.max(...allEnds, 0.001);
    const chartWidth = 300;

    const xScale = (v: number) => (v / maxVal) * chartWidth;

    const totalHeight = bars.length * rowH;

    return (
        <div className={cn('', className)}>
            <svg
                width={LABEL_W + chartWidth + VALUE_W}
                height={totalHeight}
                role="img"
                aria-label="Profit waterfall chart"
                style={{ fontFamily: 'var(--font-sans)', fontSize: 11 }}
            >
                {positions.map(({ bar, start, end }, idx) => {
                    const y = idx * rowH;
                    const barX = LABEL_W + xScale(start);
                    const barWidth = Math.max(MIN_BAR_PX, xScale(end) - xScale(start));

                    let fill = 'oklch(0.87 0 0)'; /* zinc-300 for subtotals */
                    if (bar.notConfigured) {
                        fill = 'oklch(0.967 0 0)'; /* zinc-100 */
                    } else if (bar.type === 'start' || bar.type === 'subtotal' || bar.type === 'end') {
                        fill = 'oklch(0.44 0 0)'; /* zinc-700 */
                    } else if (bar.value !== null && bar.value > 0) {
                        fill = 'oklch(0.596 0.156 149.2)'; /* emerald-600 */
                    } else if (bar.value !== null && bar.value < 0) {
                        fill = 'oklch(0.577 0.245 27.325)'; /* rose-600 */
                    }

                    return (
                        <g
                            key={bar.id}
                            style={{ cursor: bar.notConfigured || bar.type === 'subtract' ? 'pointer' : 'default' }}
                            onClick={() => onBarClick?.(bar)}
                        >
                            {/* Label */}
                            <text
                                x={LABEL_W - 8}
                                y={y + barH / 2 + 4}
                                textAnchor="end"
                                fontSize={compact ? 9 : 10}
                                fill="oklch(0.44 0 0)"
                            >
                                {bar.label}
                            </text>

                            {/* Bar */}
                            {bar.notConfigured ? (
                                <rect
                                    x={LABEL_W}
                                    y={y + 2}
                                    width={80}
                                    height={barH - 4}
                                    rx={3}
                                    fill="oklch(0.967 0 0)"
                                    stroke="oklch(0.87 0 0)"
                                    strokeWidth={1}
                                    strokeDasharray="4 3"
                                />
                            ) : (
                                <rect
                                    x={barX}
                                    y={y + 2}
                                    width={barWidth}
                                    height={barH - 4}
                                    rx={3}
                                    fill={fill}
                                />
                            )}

                            {/* Value label */}
                            <text
                                x={LABEL_W + chartWidth + 6}
                                y={y + barH / 2 + 4}
                                textAnchor="start"
                                fontSize={compact ? 9 : 10}
                                fill={bar.value && bar.value < 0 ? 'oklch(0.577 0.245 27.325)' : 'oklch(0.44 0 0)'}
                                style={{ fontVariantNumeric: 'tabular-nums' }}
                            >
                                {bar.notConfigured
                                    ? 'Not configured'
                                    : bar.formatted ?? `${currency}${Math.abs(bar.value ?? 0).toLocaleString()}`}
                            </text>
                        </g>
                    );
                })}
            </svg>

            {/* Accessible table */}
            <table className="sr-only">
                <caption>Profit waterfall breakdown</caption>
                <tbody>
                    {bars.map((bar) => (
                        <tr key={bar.id}>
                            <th scope="row">{bar.label}</th>
                            <td>{bar.formatted ?? bar.value ?? 'Not configured'}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
