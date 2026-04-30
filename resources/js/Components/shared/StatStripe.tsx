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

interface StatStripeProps {
    icon?: React.ComponentType<{ className?: string }>;
    label: string;
    value: string | number;
    delta?: number;
    source?: MetricSource;
    className?: string;
}

export function StatStripe({ icon: Icon, label, value, delta, source, className }: StatStripeProps) {
    const DeltaIcon = delta !== undefined ? (delta > 0 ? TrendingUp : delta < 0 ? TrendingDown : undefined) : undefined;

    return (
        <div className={cn('flex items-center gap-3 py-2 text-sm', className)}>
            {Icon && <Icon className="h-4 w-4 text-muted-foreground/70 shrink-0" />}

            {source && !Icon && (
                <span
                    className="h-2 w-2 rounded-full shrink-0"
                    style={{ backgroundColor: SOURCE_COLORS[source] }}
                />
            )}

            <span className="text-muted-foreground flex-1">{label}</span>

            <span className="font-semibold text-foreground tabular-nums">
                {value}
            </span>

            {delta !== undefined && (
                <span
                    className={cn(
                        'inline-flex items-center gap-0.5 text-xs font-medium',
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
