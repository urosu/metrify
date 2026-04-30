/**
 * EntityHoverCard — rich preview card shown after 400ms dwell on any entity ID.
 *
 * Shows: name/title, 3–5 key metrics, status badge, primary action.
 * Click the ID opens full detail (DrawerSidePanel or page).
 *
 * IDs rendered with MiddleTruncate utility (ord_abc...xyz789) preserve suffix readability.
 * Use JetBrains Mono for the ID itself.
 *
 * @see docs/UX.md §5.18 EntityHoverCard
 */
import { useState, useRef } from 'react';
import { cn } from '@/lib/utils';
import { StatusDot } from '@/Components/shared/StatusDot';
import type { StatusType } from '@/Components/shared/StatusDot';

/** Truncate a long ID preserving prefix and suffix for readability. */
export function MiddleTruncate({
    value,
    maxLength = 16,
    className,
}: {
    value: string;
    maxLength?: number;
    className?: string;
}) {
    if (value.length <= maxLength) {
        return <span className={cn('font-mono', className)}>{value}</span>;
    }
    const half = Math.floor((maxLength - 3) / 2);
    const prefix = value.slice(0, half + 1);
    const suffix = value.slice(-half);
    return (
        <span
            className={cn('font-mono', className)}
            title={value}
        >
            {prefix}…{suffix}
        </span>
    );
}

interface EntityMetric {
    label: string;
    value: string;
}

interface EntityHoverCardProps {
    /** The entity ID string. Shown truncated with JetBrains Mono. */
    entityId: string;
    /** Title shown in the hover card header. */
    title?: string;
    /** Status dot to show in header. */
    status?: StatusType;
    /** 3–5 key metrics shown in the card body. */
    metrics?: EntityMetric[];
    /** Primary action button label. */
    actionLabel?: string;
    /** Called when primary action is clicked. */
    onAction?: () => void;
    /** Called when the entity ID itself is clicked. */
    onClick?: () => void;
    /** Dwell time before the card appears (ms). Default 400. */
    dwellMs?: number;
    className?: string;
    children?: React.ReactNode;
}

export function EntityHoverCard({
    entityId,
    title,
    status,
    metrics = [],
    actionLabel,
    onAction,
    onClick,
    dwellMs = 400,
    className,
    children,
}: EntityHoverCardProps) {
    const [open, setOpen] = useState(false);
    const timerRef = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

    const startTimer = () => {
        timerRef.current = setTimeout(() => setOpen(true), dwellMs);
    };

    const clearTimer = () => {
        if (timerRef.current) clearTimeout(timerRef.current);
        setOpen(false);
    };

    return (
        <span
            className={cn('relative inline-flex items-center', className)}
            onMouseEnter={startTimer}
            onMouseLeave={clearTimer}
            onFocus={startTimer}
            onBlur={clearTimer}
        >
            {/* Trigger — the entity ID */}
            <button
                type="button"
                onClick={onClick}
                className="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded"
            >
                {children ?? (
                    <MiddleTruncate
                        value={entityId}
                        className="text-xs text-zinc-600 hover:text-zinc-900 transition-colors underline-offset-2 hover:underline"
                    />
                )}
            </button>

            {/* Hover card */}
            {open && (
                <div
                    className="absolute bottom-full left-0 z-50 mb-2 w-64 rounded-lg border border-border bg-white p-3"
                    style={{ boxShadow: 'var(--shadow-raised)' }}
                    role="tooltip"
                    onMouseEnter={() => { if (timerRef.current) clearTimeout(timerRef.current); setOpen(true); }}
                    onMouseLeave={clearTimer}
                >
                    {/* Header */}
                    <div className="flex items-start justify-between gap-2 mb-2">
                        <div className="min-w-0">
                            {title && (
                                <p className="text-sm font-semibold text-zinc-900 truncate">{title}</p>
                            )}
                            <MiddleTruncate
                                value={entityId}
                                className="text-xs text-zinc-400"
                            />
                        </div>
                        {status && <StatusDot status={status} />}
                    </div>

                    {/* Metrics */}
                    {metrics.length > 0 && (
                        <div className="grid grid-cols-2 gap-x-3 gap-y-1.5 mb-2 border-t border-zinc-100 pt-2">
                            {metrics.slice(0, 5).map((m) => (
                                <div key={m.label}>
                                    <p className="text-[10px] text-zinc-400 uppercase tracking-wide">{m.label}</p>
                                    <p className="text-sm font-semibold tabular-nums text-zinc-900">{m.value}</p>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Action */}
                    {actionLabel && onAction && (
                        <button
                            type="button"
                            onClick={() => { clearTimer(); onAction(); }}
                            className="w-full rounded-md bg-zinc-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-zinc-800 transition-colors"
                        >
                            {actionLabel}
                        </button>
                    )}
                </div>
            )}
        </span>
    );
}
