/**
 * Skeleton — shimmer placeholder that mirrors the final layout shape.
 *
 * Uses the `.skeleton` CSS class from app.css for the zinc-100→zinc-200
 * animation. Never use spinners or blur overlays.
 *
 * @see docs/UX.md §5.8 LoadingState
 */
import { cn } from '@/lib/utils';

interface SkeletonProps {
    className?: string;
    /** Explicit height in px. Alternatively set via className (e.g. h-4). */
    height?: number;
    /** Explicit width in px. Alternatively set via className (e.g. w-24). */
    width?: number;
    /** Override the border-radius. Defaults to radius-sm. */
    rounded?: 'sm' | 'md' | 'lg' | 'full';
}

const ROUNDED: Record<string, string> = {
    sm:   'rounded-md',
    md:   'rounded-lg',
    lg:   'rounded-xl',
    full: 'rounded-full',
};

export function Skeleton({ className, height, width, rounded = 'sm' }: SkeletonProps) {
    return (
        <div
            className={cn('skeleton', ROUNDED[rounded], className)}
            style={{ height, width }}
            aria-hidden="true"
        />
    );
}

/** Convenience wrapper for card-shaped skeletons with a padded body. */
export function SkeletonCard({ className }: { className?: string }) {
    return (
        <div className={cn('rounded-lg border border-border bg-card p-4 space-y-3', className)}>
            <Skeleton className="h-3 w-20" />
            <Skeleton className="h-7 w-32" />
            <Skeleton className="h-3 w-16" />
        </div>
    );
}

/** Convenience wrapper for table row skeletons. */
export function SkeletonRow({ cols = 4, className }: { cols?: number; className?: string }) {
    return (
        <div className={cn('flex items-center gap-4 py-3 border-b border-border', className)}>
            {Array.from({ length: cols }).map((_, i) => (
                <Skeleton key={i} className="h-4 flex-1" />
            ))}
        </div>
    );
}
