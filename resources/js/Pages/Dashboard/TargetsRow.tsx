/**
 * TargetsRow — Dashboard targets strip.
 *
 * Renders 2–3 Target widgets (Monthly Revenue, ROAS, New Customers) as
 * a horizontal card strip. Each widget shows a TargetProgress bar with
 * current/target values and a status chip.
 *
 * Uses the Target composite primitive from shared/Target.tsx.
 *
 * @see docs/UX.md §5.23 Target
 * @see docs/pages/dashboard.md Targets row
 */
import { cn } from '@/lib/utils';
import { Target, type TargetProps } from '@/Components/shared/Target';

interface TargetData {
    label: string;
    metric: string;
    current: number;
    target: number;
    unit: string;
    deadline: string;
    status: 'on_track' | 'at_risk' | 'missed';
}

interface TargetsRowProps {
    targets: TargetData[];
    className?: string;
}

export function TargetsRow({ targets, className }: TargetsRowProps) {
    if (!targets || targets.length === 0) return null;

    return (
        <div className={cn('rounded-xl border border-border bg-card p-4', className)}>
            <p className="text-sm font-semibold text-foreground mb-4">Monthly Targets</p>
            <div className={cn(
                'grid gap-6',
                targets.length === 1 && 'grid-cols-1',
                targets.length === 2 && 'grid-cols-1 sm:grid-cols-2',
                targets.length >= 3 && 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            )}>
                {targets.map((t) => (
                    <Target
                        key={t.metric}
                        label={t.label}
                        current={t.current}
                        target={t.target}
                        unit={t.unit}
                        deadline={t.deadline}
                        status={t.status}
                    />
                ))}
            </div>
        </div>
    );
}
