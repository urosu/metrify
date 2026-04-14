import { TrendingDown, TrendingUp, Minus } from 'lucide-react';
import { cn } from '@/lib/utils';

interface TrendBadgeProps {
    /** The delta percentage (e.g. 12.5 for +12.5%, -3.2 for -3.2%) */
    value: number;
    /** When true, a negative value is good (e.g. cost, refund rate) */
    invert?: boolean;
    size?: 'sm' | 'md';
}

// Used standalone wherever a % delta is shown inline (not inside MetricCard).
// MetricCard has its own built-in trend display — this is for tables and
// other contexts where the badge appears outside a card.
// Related: Components/shared/MetricCard.tsx (same color logic, not shared to avoid coupling)
export function TrendBadge({ value, invert = false, size = 'sm' }: TrendBadgeProps) {
    const isPositive = invert ? value < 0 : value > 0;
    const isNegative = invert ? value > 0 : value < 0;
    const isNeutral  = value === 0;

    const Icon = isPositive ? TrendingUp : isNegative ? TrendingDown : Minus;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-0.5 rounded-full font-semibold',
                size === 'sm' ? 'px-1.5 py-0.5 text-xs' : 'px-2 py-1 text-sm',
                isPositive && 'bg-green-50 text-green-700',
                isNegative && 'bg-red-50 text-red-700',
                isNeutral  && 'bg-zinc-100 text-zinc-500',
            )}
        >
            <Icon className={cn(size === 'sm' ? 'h-3 w-3' : 'h-3.5 w-3.5')} />
            {Math.abs(value).toFixed(1)}%
        </span>
    );
}
