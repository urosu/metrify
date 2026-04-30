/**
 * RevenueTrendChart — Dashboard revenue trend with multi-source overlay.
 *
 * Renders a LineChart with four lines:
 *   Real (gold, solid, dominant) · Store (slate) · Facebook (indigo) · Google (amber)
 *
 * When `comparisonData` is provided (comparison mode active), a fifth line is drawn:
 *   "Prior" — same zinc palette, dashed, 40% opacity (Plausible/Polar pattern).
 *   The comparison series is aligned by index (day 0 of prior period = day 0 of primary).
 *
 * Dotted segment for the rightmost (today's) incomplete period.
 * Uses canonical source colors from UX §4.
 *
 * Competitor patterns:
 *   - Northbeam Overview: multi-source overlay with source-coded lines
 *   - Plausible: dotted incomplete-period segment always-on
 *   - Polar Analytics: comparison overlay at 35–40% opacity, dashed
 *
 * @see docs/UX.md §5.6 Chart primitives — LineChart
 * @see docs/pages/dashboard.md Revenue trend chart
 * @see docs/competitors/_research_date_compare.md §3 Chart overlay
 */
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';
import { cn } from '@/lib/utils';

interface TrendPoint {
    date: string;
    real: number;
    store: number;
    facebook: number;
    google: number;
}

export interface ComparisonTrendPoint {
    date: string;
    real: number;
}

interface RevenueTrendChartProps {
    data: TrendPoint[];
    /**
     * Comparison period data — shown as a single muted "Prior" line.
     * Aligned by index: index 0 of comparison = index 0 of primary.
     */
    comparisonData?: ComparisonTrendPoint[] | null;
    /** Label for the comparison legend entry. */
    comparisonLabel?: string;
    currency?: string;
    className?: string;
}

function formatK(value: number): string {
    if (Math.abs(value) >= 1_000_000) return `$${(value / 1_000_000).toFixed(1)}M`;
    if (Math.abs(value) >= 1_000)     return `$${(value / 1_000).toFixed(0)}K`;
    return `$${value}`;
}

function formatDate(dateStr: string): string {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en', { month: 'short', day: 'numeric' });
}

// Canonical source colors per UX §4
const SOURCE_LINES = [
    { key: 'real',     label: 'Real',     stroke: '#facc15', strokeWidth: 2.5, strokeDasharray: undefined },
    { key: 'store',    label: 'Store',    stroke: '#64748b', strokeWidth: 1.5, strokeDasharray: undefined },
    { key: 'facebook', label: 'Facebook', stroke: '#6366f1', strokeWidth: 1.5, strokeDasharray: undefined },
    { key: 'google',   label: 'Google',   stroke: '#f59e0b', strokeWidth: 1.5, strokeDasharray: '4 2'     },
] as const;

export function RevenueTrendChart({
    data,
    comparisonData,
    comparisonLabel = 'Prior period',
    className,
}: RevenueTrendChartProps) {
    if (!data || data.length === 0) {
        return (
            <div className={cn('h-64 w-full flex items-center justify-center text-muted-foreground text-sm', className)}>
                No trend data available
            </div>
        );
    }

    const hasComparison = !!(comparisonData && comparisonData.length > 0);

    // Mark the last point as incomplete (today).
    // Merge comparison values by index alignment — comparison day 0 maps to primary day 0.
    const enriched = data.map((point, i) => ({
        ...point,
        isIncomplete: i === data.length - 1,
        // prior_real: comparison "real" value aligned by position (not date)
        prior_real: hasComparison ? (comparisonData![i]?.real ?? null) : undefined,
    }));

    return (
        <div className={cn('h-64 w-full', className)}>
            <ResponsiveContainer width="100%" height="100%">
                <LineChart
                    data={enriched}
                    margin={{ top: 4, right: 8, left: 0, bottom: 0 }}
                >
                    <CartesianGrid strokeDasharray="3 3" stroke="#f4f4f5" vertical={false} />
                    <XAxis
                        dataKey="date"
                        tickLine={false}
                        axisLine={false}
                        tick={{ fontSize: 12, fill: '#a1a1aa' }}
                        tickFormatter={formatDate}
                        minTickGap={48}
                    />
                    <YAxis
                        tickLine={false}
                        axisLine={false}
                        tick={{ fontSize: 12, fill: '#a1a1aa' }}
                        tickFormatter={formatK}
                        width={56}
                    />
                    <Tooltip
                        contentStyle={{
                            fontSize: 12,
                            borderRadius: 8,
                            border: '1px solid #e4e4e7',
                            boxShadow: '0 1px 8px rgba(0,0,0,0.08)',
                        }}
                        formatter={(value: unknown, name: unknown) => [formatK(Number(value)), String(name)]}
                        labelFormatter={(label: unknown) => {
                            const str = String(label);
                            const isLast = data.length > 0 && str === data[data.length - 1].date;
                            return formatDate(str) + (isLast ? ' (partial)' : '');
                        }}
                    />
                    <Legend
                        wrapperStyle={{ fontSize: 12, paddingTop: 8 }}
                        iconType="circle"
                        iconSize={8}
                    />
                    {SOURCE_LINES.map((cfg) => (
                        <Line
                            key={cfg.key}
                            type="monotone"
                            dataKey={cfg.key}
                            name={cfg.label}
                            stroke={cfg.stroke}
                            strokeWidth={cfg.strokeWidth}
                            strokeDasharray={cfg.strokeDasharray}
                            dot={false}
                            connectNulls
                        />
                    ))}
                    {/* Comparison overlay — dashed, 40% opacity, zinc (Plausible/Polar pattern) */}
                    {hasComparison && (
                        <Line
                            type="monotone"
                            dataKey="prior_real"
                            name={comparisonLabel}
                            stroke="#71717a"
                            strokeWidth={1.5}
                            strokeDasharray="4 2"
                            strokeOpacity={0.5}
                            dot={false}
                            connectNulls
                        />
                    )}
                </LineChart>
            </ResponsiveContainer>
        </div>
    );
}
