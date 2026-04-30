/**
 * TodaySoFarPanel — Dashboard-local intra-day progress widget.
 *
 * Wraps the TodaySoFar primitive with a bar-chart hourly strip showing
 * revenue-by-hour for today vs. the baseline (same weekday last week).
 *
 * Headline: today's cumulative revenue.
 * Sub-headline: projected end-of-day estimate.
 * Strip: per-hour bars colored by source-real.
 *
 * @see docs/UX.md §5.25 TodaySoFar
 * @see docs/pages/dashboard.md TodaySoFar strip
 */
import { BarChart, Bar, ResponsiveContainer, Tooltip, ReferenceLine } from 'recharts';
import { Clock } from 'lucide-react';
import { cn } from '@/lib/utils';

interface HourPoint {
    hour: number;
    revenue: number;
}

interface TodaySoFarData {
    revenue: number;
    revenue_formatted: string;
    orders: number;
    projected_revenue: number;
    projected_revenue_formatted: string;
    hourly_data: HourPoint[];
    baseline_revenue: number;
}

interface TodaySoFarPanelProps {
    data: TodaySoFarData;
    className?: string;
}

function HourLabel({ hour }: { hour: number }) {
    const period = hour < 12 ? 'am' : 'pm';
    const h = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
    return `${h}${period}`;
}

export function TodaySoFarPanel({ data, className }: TodaySoFarPanelProps) {
    const pctOfBaseline = data.baseline_revenue > 0
        ? Math.round((data.revenue / data.baseline_revenue) * 100)
        : null;

    const isAhead = pctOfBaseline !== null && pctOfBaseline >= 100;
    const isBehind = pctOfBaseline !== null && pctOfBaseline < 85;

    return (
        <div className={cn('rounded-xl border border-border bg-card p-4 flex flex-col gap-3', className)}>
            {/* Header */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-1.5">
                    <Clock className="h-3.5 w-3.5 text-muted-foreground" aria-hidden="true" />
                    <p className="text-sm font-medium text-muted-foreground">Today so far</p>
                </div>
                {pctOfBaseline !== null && (
                    <span className={cn(
                        'text-xs font-semibold rounded-full px-2 py-0.5',
                        isAhead ? 'bg-emerald-50 text-emerald-700' : isBehind ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700',
                    )}>
                        {isAhead ? '+' : ''}{pctOfBaseline - 100}% vs last {new Date().toLocaleDateString('en', { weekday: 'short' })}
                    </span>
                )}
            </div>

            {/* Hero revenue */}
            <div>
                <p className="text-3xl font-semibold tabular-nums text-foreground leading-none">
                    {data.revenue_formatted}
                </p>
                <p className="mt-1 text-sm text-muted-foreground">
                    {data.orders.toLocaleString()} order{data.orders !== 1 ? 's' : ''}
                </p>
            </div>

            {/* Hourly bar chart */}
            {data.hourly_data.length > 0 && (
                <div className="h-16">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart
                            data={data.hourly_data}
                            barSize={6}
                            margin={{ top: 0, right: 0, bottom: 0, left: 0 }}
                        >
                            <Bar
                                dataKey="revenue"
                                fill="var(--color-source-real-fg)"
                                radius={[2, 2, 0, 0]}
                                opacity={0.85}
                            />
                            <Tooltip
                                contentStyle={{ fontSize: 12, borderRadius: 6, border: '1px solid #e4e4e7' }}
                                formatter={(value: unknown) => [`$${Number(value).toLocaleString()}`, 'Revenue']}
                                labelFormatter={(label: unknown) => HourLabel({ hour: Number(label) })}
                            />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            )}

            {/* Projected end-of-day */}
            <div className="flex items-center justify-between text-sm border-t border-border pt-2">
                <span className="text-muted-foreground">Projected EOD</span>
                <span className="font-semibold tabular-nums text-foreground">
                    {data.projected_revenue_formatted}
                </span>
            </div>
        </div>
    );
}
