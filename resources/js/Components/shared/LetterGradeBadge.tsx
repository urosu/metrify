import { cn } from '@/lib/utils';

export type Grade = 'A' | 'B' | 'C' | 'D' | 'F';

interface LetterGradeBadgeProps {
    grade: Grade;
    size?: 'sm' | 'md';
    className?: string;
    /** Custom tooltip. Defaults to the CTR threshold rubric. */
    title?: string;
}

const GRADE_COLORS: Record<Grade, string> = {
    A: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    B: 'bg-primary/5 text-primary border-primary/20',
    C: 'bg-amber-50 text-amber-700 border-amber-200',
    D: 'bg-orange-50 text-orange-700 border-orange-200',
    F: 'bg-rose-50 text-rose-700 border-rose-200',
};

/** Human-readable threshold explanation for each CTR grade (hover tooltip). */
const GRADE_TITLES: Record<Grade, string> = {
    A: 'Grade A — CTR ≥ 2× workspace median. Top-tier creative efficiency.',
    B: 'Grade B — CTR ≥ 1.25× median. Strong performer, worth scaling.',
    C: 'Grade C — CTR ≥ 0.75× median. Average; iterate the hook or visual.',
    D: 'Grade D — CTR ≥ 0.4× median. Weak; audience or creative mismatch.',
    F: 'Grade F — CTR < 0.4× median. Lowest tier; pause or overhaul.',
};

export function LetterGradeBadge({ grade, size = 'md', className, title }: LetterGradeBadgeProps) {
    return (
        <span
            title={title ?? GRADE_TITLES[grade]}
            className={cn(
                'inline-flex cursor-help items-center justify-center rounded border font-bold',
                size === 'sm' ? 'h-6 w-6 text-xs' : 'h-8 w-8 text-sm',
                GRADE_COLORS[grade],
                className,
            )}
        >
            {grade}
        </span>
    );
}
