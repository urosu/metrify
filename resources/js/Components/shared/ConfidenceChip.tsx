/**
 * ConfidenceChip — grey-tinted pill shown when a metric is computed on too few samples.
 *
 * When present:
 *   - Delta % is suppressed (caller should not render the delta arrow).
 *   - Sparkline is desaturated (caller responsibility via desaturate prop).
 *   - The metric value itself is greyed 20% (caller wraps with .opacity-80 or similar).
 *
 * Never hide a metric for low confidence — disclosure beats suppression.
 *
 * @see docs/UX.md §5.27 ConfidenceChip
 */
import { cn } from '@/lib/utils';

interface ConfidenceChipProps {
    /** Number of orders/sessions/impressions behind the metric. */
    sampleSize: number;
    /** Metric type determines the noun used in the tooltip. */
    metricType?: 'orders' | 'sessions' | 'impressions';
    className?: string;
}

export function ConfidenceChip({
    sampleSize,
    metricType = 'orders',
    className,
}: ConfidenceChipProps) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border border-zinc-200 bg-zinc-100',
                'px-2 py-0.5 text-xs text-zinc-500 font-medium',
                className,
            )}
            title={`Too few samples to detect a reliable trend. Wait for more data, or widen your date range.`}
        >
            Based on {sampleSize.toLocaleString()} {metricType} — low confidence
        </span>
    );
}

/**
 * Returns true when the sample count falls below the workspace default threshold.
 * Thresholds from UX §5.27: 30 orders / 100 sessions / 1000 impressions.
 */
export function isBelowConfidenceThreshold(
    count: number,
    type: 'orders' | 'sessions' | 'impressions' = 'orders',
    customThreshold?: number,
): boolean {
    if (customThreshold !== undefined) return count < customThreshold;
    const defaults = { orders: 30, sessions: 100, impressions: 1000 };
    return count < defaults[type];
}
