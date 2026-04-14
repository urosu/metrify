import { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface SectionCardProps {
    title?: string;
    description?: string;
    /** Optional element rendered in the header's right slot (e.g. a button) */
    action?: ReactNode;
    children: ReactNode;
    className?: string;
}

// White rounded bordered card with optional header + action slot.
// Settings pages use this repeatedly — saves copy-pasting className strings.
// Corresponds to the .settings-section CSS class in app.css @layer components.
// Related: resources/css/app.css (.settings-section, .settings-section-header, .settings-section-body)
export function SectionCard({ title, description, action, children, className }: SectionCardProps) {
    const hasHeader = title || description || action;

    return (
        <div className={cn('settings-section', className)}>
            {hasHeader && (
                <div className="settings-section-header flex items-start justify-between gap-4">
                    <div>
                        {title && (
                            <h3 className="text-base font-semibold text-zinc-900">{title}</h3>
                        )}
                        {description && (
                            <p className="mt-0.5 text-sm text-zinc-500">{description}</p>
                        )}
                    </div>
                    {action && <div className="shrink-0">{action}</div>}
                </div>
            )}
            <div className="settings-section-body">
                {children}
            </div>
        </div>
    );
}
