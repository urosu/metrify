import React from 'react';
import { cn } from '@/lib/utils';

interface KpiGridProps {
    children: React.ReactNode;
    cols?: 2 | 3 | 4 | 5;
    className?: string;
}

const COLS_CLASS: Record<2 | 3 | 4 | 5, string> = {
    2: 'grid-cols-2',
    3: 'grid-cols-2 sm:grid-cols-3',
    4: 'grid-cols-2 sm:grid-cols-2 lg:grid-cols-4',
    5: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5',
};

export function KpiGrid({ children, cols = 4, className }: KpiGridProps) {
    return (
        <div
            className={cn(
                'grid gap-4',
                COLS_CLASS[cols],
                className,
            )}
        >
            {children}
        </div>
    );
}
