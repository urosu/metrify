import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

interface Tab {
    label: string;
    value: string;
    href: string;
    active?: boolean;
}

interface SubNavTabsProps {
    tabs: Tab[];
    className?: string;
}

export function SubNavTabs({ tabs, className }: SubNavTabsProps) {
    return (
        <div className={cn('flex border-b border-border', className)}>
            {tabs.map((tab) => (
                <Link
                    key={tab.value}
                    href={tab.href}
                    className={cn(
                        'px-4 py-2 text-sm font-medium transition-colors',
                        tab.active
                            ? 'border-b-2 border-primary text-primary'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    {tab.label}
                </Link>
            ))}
        </div>
    );
}
