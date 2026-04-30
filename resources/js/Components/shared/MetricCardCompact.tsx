import React from 'react';
import { TrendingDown, TrendingUp } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { MetricSource } from './SourceBadge';

const SOURCE_COLORS: Record<MetricSource, string> = {
    store:    'var(--color-source-store)',
    facebook: 'var(--color-source-facebook)',
    google:   'var(--color-source-google)',
    gsc:      'var(--color-source-gsc)',
    ga4:      'var(--color-source-ga4)',
    real:     'var(--color-source-real)',
};

interface MetricCardCompactProps {
    label: string;
    value: string | number;
    delta?: number;
    activeSource?: MetricSource;
    loading?: boolean;
    className?: string;
}

export function MetricCardCompact({
    label,
    value,
    delta,
    activeSource,
    loading = false,
    className,
}: MetricCardCompactProps) {
    const cardStyle: React.CSSProperties = { minHeight: 'var(--metric-card-min-h-compact, 100px)' };

    const cardBorderStyle: React.CSSProperties = {
        border: '1px solid var(--border-subtle)',
    };

    if (loading) {
        return (
            <div className={cn('rounded-lg bg-card p-3 flex flex-col gap-0.5', className)}
                 style={{ ...cardStyle, ...cardBorderStyle }}>
                <div className="h-2 w-2 rounded-full bg-muted animate-pulse" />
                <div className="h-3 w-16 rounded bg-muted animate-pulse mt-0.5" />
                <div className="h-5 w-20 rounded bg-muted animate-pulse" />
            </div>
        );
    }

    const dotColor = activeSource ? SOURCE_COLORS[activeSource] : 'var(--color-source-real)';
    const DeltaIcon = delta !== undefined ? (delta > 0 ? TrendingUp : delta < 0 ? TrendingDown : undefined) : undefined;

    return (
        <div className={cn('rounded-lg bg-card p-3 flex flex-col gap-0.5 overflow-hidden', className)}
             style={{ ...cardStyle, ...cardBorderStyle }}>
            <span
                className="h-2 w-2 rounded-full shrink-0"
                style={{ backgroundColor: dotColor }}
            />
            <span className="text-sm truncate" style={{ color: 'var(--color-text-secondary)' }}>{label}</span>
            <span className="text-lg font-semibold tabular-nums overflow-hidden text-ellipsis"
                  style={{ color: 'var(--color-text)', fontVariantNumeric: 'tabular-nums' }}>
                {value}
            </span>
            {delta !== undefined && (
                <span
                    className={cn(
                        'inline-flex items-center gap-0.5 text-sm font-medium',
                        delta > 0 && 'text-emerald-600',
                        delta < 0 && 'text-rose-600',
                        delta === 0 && 'text-muted-foreground/70',
                    )}
                >
                    {DeltaIcon && <DeltaIcon className="h-3 w-3" />}
                    {delta > 0 ? '+' : ''}{delta.toFixed(1)}%
                </span>
            )}
        </div>
    );
}
