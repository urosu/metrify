// Pure-SVG sparkline — no Recharts dependency.
// Decorative only: parent MetricCard conveys the numeric value.
// @see docs/UX.md#MetricCard

export interface SparklineDataPoint {
    value: number;
    label?: string;
}

interface SparklineProps {
    data: SparklineDataPoint[];
    color?: string;
    /** Height in px — default 40 */
    height?: number;
    className?: string;
    /** Whether to show a minimal tooltip on hover (no-op in SVG impl; kept for API compat) */
    showTooltip?: boolean;
    /**
     * Render mode:
     * - 'line'  (default) — stroke only, no fill
     * - 'area'  — filled area below the line with a gradient fade to transparent.
     *             Fill uses `color` at 0.18 opacity fading to 0 at the bottom.
     */
    mode?: 'line' | 'area';
}

export function Sparkline({
    data,
    color = 'var(--chart-1)',
    height = 40,
    className,
    mode = 'line',
}: SparklineProps) {
    if (data.length < 2) return <div style={{ height }} className={className} aria-hidden="true" />;

    const values = data.map((d) => d.value);
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;

    const w = 100;
    const h = height;
    // No vertical padding in area mode so the fill truly bleeds to the bottom edge.
    const pad = mode === 'area' ? 0 : 2;
    const yPad = mode === 'area' ? 2 : 2; // small top pad keeps the stroke from clipping

    const pts = values.map((v, i) => {
        const x = (i / (values.length - 1)) * w;
        const y = yPad + ((max - v) / range) * (h - yPad);
        return [x, y] as [number, number];
    });

    const linePath = pts
        .map((p, i) => `${i === 0 ? 'M' : 'L'}${p[0].toFixed(2)},${p[1].toFixed(2)}`)
        .join(' ');

    // Area path: line + drop to bottom-right corner + back to bottom-left + close
    const areaPath = `${linePath} L${w},${h} L0,${h} Z`;

    // Unique gradient id derived from color string to avoid collisions when multiple
    // Sparklines appear on the same page.
    const gradientId = `sparkline-grad-${color.replace(/[^a-z0-9]/gi, '')}`;

    return (
        <div
            className={className}
            style={{ height }}
            aria-hidden="true"
        >
            <svg
                viewBox={`0 0 ${w} ${h}`}
                preserveAspectRatio="none"
                style={{ display: 'block', width: '100%', height: '100%' }}
            >
                {mode === 'area' && (
                    <defs>
                        <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
                            {/*
                             * Top stop: source color at 18% opacity — deliberate; matches
                             * Triple Whale / Polar Analytics softness. NOT 0.5 (too heavy).
                             * Bottom stop: same color at 0% — clean fade to card background.
                             * Uses currentColor path so `color` prop controls both fill & stroke.
                             */}
                            <stop offset="0%" stopColor={color} stopOpacity="0.18" />
                            <stop offset="100%" stopColor={color} stopOpacity="0" />
                        </linearGradient>
                    </defs>
                )}

                {mode === 'area' && (
                    <path d={areaPath} fill={`url(#${gradientId})`} />
                )}

                {mode === 'area' ? (
                    <path
                        d={linePath}
                        fill="none"
                        stroke={color}
                        strokeWidth="1.5"
                        strokeLinejoin="round"
                        strokeLinecap="round"
                        vectorEffect="non-scaling-stroke"
                    />
                ) : (
                    <polyline
                        points={pts.map((p) => `${p[0].toFixed(2)},${p[1].toFixed(2)}`).join(' ')}
                        fill="none"
                        stroke={color}
                        strokeWidth="1.5"
                        strokeLinejoin="round"
                        strokeLinecap="round"
                        vectorEffect="non-scaling-stroke"
                    />
                )}
            </svg>
        </div>
    );
}
