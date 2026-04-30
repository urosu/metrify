/**
 * LayerCakeChart — stacked cohort revenue over time (LTV visualization).
 *
 * Shows cumulative revenue contribution per cohort as stacked filled areas.
 * Each cohort = one colored layer. The "cake" grows as older cohorts compound.
 *
 * Used on /customers LTV tab.
 *
 * Built with Recharts AreaChart in stacked mode.
 *
 * @see docs/UX.md §5.6 Chart primitives — LayerCakeChart
 * @see docs/competitors/_teardown_polar.md Daasity pattern reference
 */
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';
import { cn } from '@/lib/utils';

export interface LayerCakeCohort {
    /** Cohort identifier, e.g. "2025-01" */
    id: string;
    /** Display label, e.g. "Jan 2025" */
    label: string;
}

export interface LayerCakeDataPoint {
    /** X-axis label, e.g. "Month 1", "Month 6" */
    period: string;
    /** Revenue per cohort. Key = cohort.id, value = cumulative revenue. */
    [cohortId: string]: number | string;
}

interface LayerCakeChartProps {
    cohorts: LayerCakeCohort[];
    data: LayerCakeDataPoint[];
    yFormatter?: (v: number) => string;
    className?: string;
    height?: number;
}

/* Deterministic color per cohort layer index */
const LAYER_COLORS = [
    'oklch(0.42 0.21 254)',  /* blue     */
    'oklch(0.40 0.12 182)',  /* teal     */
    'oklch(0.40 0.17 155)',  /* emerald  */
    'oklch(0.42 0.20 295)',  /* violet   */
    'oklch(0.53 0.18 60)',   /* amber    */
    'oklch(0.44 0.04 257)',  /* slate    */
    'oklch(0.50 0.19 25)',   /* rose     */
    'oklch(0.55 0.15 210)',  /* sky      */
];

export function LayerCakeChart({
    cohorts,
    data,
    yFormatter = (v) => `$${(v / 1000).toFixed(1)}k`,
    className,
    height = 280,
}: LayerCakeChartProps) {
    return (
        <div className={cn('w-full', className)}>
            <ResponsiveContainer width="100%" height={height}>
                <AreaChart data={data} margin={{ top: 8, right: 24, bottom: 0, left: 8 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="oklch(0.92 0 0)" vertical={false} />
                    <XAxis
                        dataKey="period"
                        tick={{ fontSize: 11, fill: 'oklch(0.552 0 0)', fontFamily: 'var(--font-sans)' }}
                        axisLine={false}
                        tickLine={false}
                    />
                    <YAxis
                        tickFormatter={yFormatter}
                        tick={{ fontSize: 11, fill: 'oklch(0.552 0 0)', fontFamily: 'var(--font-sans)' }}
                        axisLine={false}
                        tickLine={false}
                        width={56}
                    />
                    <Tooltip
                        formatter={(value, name) => [yFormatter(Number(value)), String(name)]}
                        contentStyle={{
                            border: '1px solid oklch(0.92 0 0)',
                            borderRadius: 8,
                            fontSize: 12,
                            fontFamily: 'var(--font-sans)',
                            boxShadow: 'var(--shadow-overlay)',
                        }}
                    />
                    <Legend
                        wrapperStyle={{ fontSize: 11, fontFamily: 'var(--font-sans)' }}
                        iconType="circle"
                        iconSize={8}
                    />
                    {cohorts.map((cohort, i) => (
                        <Area
                            key={cohort.id}
                            type="monotone"
                            dataKey={cohort.id}
                            name={cohort.label}
                            stackId="1"
                            stroke={LAYER_COLORS[i % LAYER_COLORS.length]}
                            fill={LAYER_COLORS[i % LAYER_COLORS.length]}
                            fillOpacity={0.7}
                            strokeWidth={1}
                            dot={false}
                            activeDot={{ r: 4 }}
                        />
                    ))}
                </AreaChart>
            </ResponsiveContainer>
        </div>
    );
}
