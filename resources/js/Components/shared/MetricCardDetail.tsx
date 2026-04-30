import { TrendingDown, TrendingUp } from 'lucide-react';
import { cn } from '@/lib/utils';
import { SourceBadge, MetricSource } from './SourceBadge';
import { Sparkline } from '@/Components/charts/Sparkline';

interface MetricCardDetailProps {
    label: string;
    value: string | number;
    delta?: number;
    deltaLabel?: string;
    previousYear?: number;
    previousYearDelta?: number;
    sources?: MetricSource[];
    activeSource?: MetricSource;
    onSourceChange?: (s: MetricSource) => void;
    sparklineData?: number[];
    loading?: boolean;
    className?: string;
}

const ALL_SOURCES: MetricSource[] = ['store', 'facebook', 'google', 'gsc', 'ga4', 'real'];

function DeltaPill({ delta, label }: { delta: number; label?: string }) {
    const Icon = delta > 0 ? TrendingUp : delta < 0 ? TrendingDown : undefined;
    return (
        <span className="inline-flex items-center gap-1">
            <span
                className={cn(
                    'inline-flex items-center gap-0.5 text-sm font-medium',
                    delta > 0 && 'text-emerald-600',
                    delta < 0 && 'text-rose-600',
                    delta === 0 && 'text-muted-foreground/70',
                )}
            >
                {Icon && <Icon className="h-3 w-3" />}
                {delta > 0 ? '+' : ''}{delta.toFixed(1)}%
            </span>
            {label && <span className="text-sm text-muted-foreground/70">{label}</span>}
        </span>
    );
}

export function MetricCardDetail({
    label,
    value,
    delta,
    deltaLabel = 'vs prev period',
    previousYear,
    previousYearDelta,
    sources = ALL_SOURCES,
    activeSource,
    onSourceChange,
    sparklineData,
    loading = false,
    className,
}: MetricCardDetailProps) {
    if (loading) {
        return (
            <div className={cn('rounded-lg bg-card p-4 w-full space-y-3', className)}
                 style={{ border: '1px solid var(--border-subtle)' }}>
                <div className="flex gap-1">
                    {ALL_SOURCES.map((s) => (
                        <div key={s} className="h-5 w-14 rounded-full bg-muted animate-pulse" />
                    ))}
                </div>
                <div className="flex gap-4">
                    <div className="flex-1 space-y-2">
                        <div className="h-3 w-20 rounded bg-muted animate-pulse" />
                        <div className="h-8 w-32 rounded bg-muted animate-pulse" />
                        <div className="h-3 w-24 rounded bg-muted animate-pulse" />
                    </div>
                    <div className="w-40 space-y-2">
                        <div className="h-10 w-full rounded bg-muted animate-pulse" />
                        <div className="h-3 w-24 rounded bg-muted animate-pulse" />
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className={cn('rounded-lg bg-card p-5 w-full', className)}
             style={{ border: '1px solid var(--border-subtle)' }}>
            {/* Source badge row */}
            <div className="flex flex-wrap items-center gap-1 mb-4">
                {sources.map((s) => (
                    <SourceBadge
                        key={s}
                        source={s}
                        size="sm"
                        active={s === activeSource}
                        onClick={onSourceChange ? () => onSourceChange(s) : undefined}
                    />
                ))}
            </div>

            {/* Two-column layout */}
            <div className="flex gap-6 items-start">
                {/* Left: current value, label, deltas */}
                <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium" style={{ color: 'var(--color-text-secondary)' }}>{label}</p>
                    <div
                        className="font-semibold mt-1 tabular-nums overflow-hidden text-ellipsis"
                        style={{
                            fontSize: 'var(--text-3xl)',
                            fontVariantNumeric: 'tabular-nums',
                            color: 'var(--color-text)',
                        }}
                    >
                        {value}
                    </div>
                    <div className="mt-1.5 flex flex-col gap-0.5">
                        {delta !== undefined && (
                            <DeltaPill delta={delta} label={deltaLabel} />
                        )}
                        {previousYearDelta !== undefined && (
                            <DeltaPill delta={previousYearDelta} label="vs prev year" />
                        )}
                    </div>
                </div>

                {/* Right: sparkline + prev year value */}
                <div className="w-40 shrink-0">
                    {sparklineData && sparklineData.length > 0 && (
                        <Sparkline data={sparklineData.map(v => ({ value: v }))} height={40} />
                    )}
                    {previousYear !== undefined && (
                        <div className="mt-2 text-right">
                            <span className="text-sm" style={{ color: 'var(--color-text-muted)' }}>Prev year: </span>
                            <span className="text-sm font-semibold tabular-nums"
                                  style={{ color: 'var(--color-text-secondary)', fontVariantNumeric: 'tabular-nums' }}>
                                {previousYear}
                            </span>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
