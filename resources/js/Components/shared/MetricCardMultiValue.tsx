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

interface MetricValue {
    label: string;
    value: string | number;
    /** Optional previous value for growth arrow (pass undefined for backward compat). */
    prev?: string | number;
}

interface MetricCardMultiValueProps {
    title: string;
    values: MetricValue[];
    activeSource?: MetricSource;
    loading?: boolean;
    className?: string;
}

/** Parse a display string like "€52.45" or "52,45 €" into a raw number for bar sizing. */
function parseDisplayValue(v: string | number): number {
    if (typeof v === 'number') return v;
    const cleaned = v.replace(/[^0-9.,]/g, '').replace(',', '.');
    return parseFloat(cleaned) || 0;
}

export function MetricCardMultiValue({
    title,
    values,
    activeSource,
    loading = false,
    className,
}: MetricCardMultiValueProps) {
    const dotColor = activeSource ? SOURCE_COLORS[activeSource] : 'var(--color-source-real)';

    if (loading) {
        return (
            <div className={cn('rounded-lg bg-card p-4', className)}
                 style={{ border: '1px solid var(--border-subtle)' }}>
                <div className="flex items-center gap-2 mb-4">
                    <div className="h-2 w-2 rounded-full bg-muted animate-pulse" />
                    <div className="h-3 w-20 rounded bg-muted animate-pulse" />
                </div>
                <div className="space-y-2.5">
                    {[0, 1, 2].map((i) => (
                        <div key={i} className="space-y-1">
                            <div className="flex items-center justify-between">
                                <div className="h-3 w-12 rounded bg-muted animate-pulse" />
                                <div className="h-5 w-16 rounded bg-muted animate-pulse" />
                            </div>
                            <div className="h-0.5 w-full rounded-full bg-muted animate-pulse" />
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    const sliced = values.slice(0, 3);
    const rawNums = sliced.map((item) => parseDisplayValue(item.value));
    const maxVal = Math.max(...rawNums, 0.0001);

    return (
        <div className={cn('rounded-lg bg-card p-4', className)}
             style={{ border: '1px solid var(--border-subtle)' }}>
            {/* Source dot + title */}
            <div className="flex items-center gap-2 mb-4">
                <span
                    className="h-2 w-2 rounded-full shrink-0"
                    style={{ backgroundColor: dotColor }}
                />
                <span className="text-sm font-semibold" style={{ color: 'var(--color-text)' }}>{title}</span>
            </div>

            {/* Vertical stacked rows */}
            <div className="space-y-2.5">
                {sliced.map((item, idx) => {
                    const barPct = maxVal > 0 ? (rawNums[idx] / maxVal) * 100 : 0;

                    /* Growth arrow — only when prev is provided */
                    let arrow: React.ReactNode = null;
                    if (item.prev !== undefined) {
                        const prevNum = parseDisplayValue(item.prev);
                        const curNum = rawNums[idx];
                        if (prevNum > 0 && curNum !== prevNum) {
                            const up = curNum > prevNum;
                            arrow = (
                                <span
                                    className={cn(
                                        'ml-1 text-xs font-medium',
                                        up ? 'text-emerald-600' : 'text-rose-600',
                                    )}
                                    aria-label={up ? 'Growth' : 'Decline'}
                                >
                                    {up ? '↑' : '↓'}
                                </span>
                            );
                        }
                    }

                    return (
                        <div key={item.label}>
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-sm shrink-0"
                                      style={{ color: 'var(--color-text-secondary)' }}>{item.label}</span>
                                <span className="text-base font-bold tabular-nums"
                                      style={{ color: 'var(--color-text)', fontVariantNumeric: 'tabular-nums' }}>
                                    {item.value}
                                    {arrow}
                                </span>
                            </div>
                            {/* Proportional bar */}
                            <div className="mt-1 h-0.5 w-full overflow-hidden rounded-full"
                                 style={{ backgroundColor: 'var(--border-subtle)' }}>
                                <div
                                    className="h-full rounded-full transition-all duration-300"
                                    style={{
                                        width: `${barPct}%`,
                                        backgroundColor: dotColor,
                                        opacity: 0.5,
                                    }}
                                />
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
