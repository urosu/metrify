import { Link } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

interface EmptyStateProps {
    icon?: LucideIcon;
    title: string;
    description?: string;
    action?: {
        label: string;
        href?: string;
        onClick?: () => void;
    };
}

// Centered empty-state card used when data isn't available yet (no integration,
// no synced data, filtered results return nothing, etc.).
// See: PLANNING.md Phase 1 "Day-1 empty state" (progress indicator variant added in Phase 1).
// Related: Components/shared/index.ts (exported from here)
export function EmptyState({ icon: Icon, title, description, action }: EmptyStateProps) {
    return (
        <div className="flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white px-6 py-20 text-center">
            {Icon && (
                <Icon className="mb-4 h-8 w-8 text-zinc-300" />
            )}
            <p className="text-sm font-semibold text-zinc-900">{title}</p>
            {description && (
                <p className="mt-1 max-w-xs text-sm text-zinc-500">{description}</p>
            )}
            {action && (
                action.href ? (
                    <Link
                        href={action.href}
                        className="mt-5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        {action.label}
                    </Link>
                ) : (
                    <button
                        onClick={action.onClick}
                        className="mt-5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        {action.label}
                    </button>
                )
            )}
        </div>
    );
}
