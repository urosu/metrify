import { Cpu, GitBranch, ShieldCheck } from 'lucide-react';
import { cn } from '@/lib/utils';

export type SignalType = 'deterministic' | 'modeled' | 'mixed';

interface SignalTypeBadgeProps {
    signal: SignalType;
    className?: string;
}

const SIGNAL_CONFIG: Record<
    SignalType,
    { label: string; Icon: React.ComponentType<{ className?: string }>; className: string }
> = {
    deterministic: {
        label: 'Deterministic',
        Icon: ShieldCheck,
        className: 'bg-emerald-50 border-emerald-200 text-emerald-700',
    },
    modeled: {
        label: 'Modeled',
        Icon: Cpu,
        className: 'bg-violet-50 border-violet-200 text-violet-700',
    },
    mixed: {
        label: 'Mixed',
        Icon: GitBranch,
        className: 'bg-sky-50 border-sky-200 text-sky-700',
    },
};

export function SignalTypeBadge({ signal, className }: SignalTypeBadgeProps) {
    const { label, Icon, className: variantClass } = SIGNAL_CONFIG[signal];

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full border',
                variantClass,
                className,
            )}
        >
            <Icon className="h-3 w-3" />
            {label}
        </span>
    );
}
