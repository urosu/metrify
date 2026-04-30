/**
 * FreshnessIndicator — top-right data freshness indicator in TopBar.
 *
 * States:
 *   < 2 min:   "● Live" (pulsing green)
 *   < 1 h:     "Updated 3 min ago" (neutral)
 *   1–24 h:    "Updated 3h ago" (amber chip)
 *   > 24 h:    "Last synced 3d ago" (rose chip)
 *
 * Clicking opens a popover with per-source sync table.
 *
 * @see docs/UX.md §5.20 FreshnessIndicator
 */
import { useState } from 'react';
import { cn } from '@/lib/utils';
import type { IntegrationFreshness } from '@/types';

interface FreshnessIndicatorProps {
    lastSyncedAt?: string | null;
    /** Per-source breakdown for the popover. */
    sources?: IntegrationFreshness[];
    className?: string;
}

function relativeTime(isoDate: string): string {
    const diff = Math.floor((Date.now() - new Date(isoDate).getTime()) / 1000);
    if (diff < 60)       return `${diff}s ago`;
    if (diff < 3600)     return `${Math.floor(diff / 60)} min ago`;
    if (diff < 86400)    return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
}

function getState(isoDate: string | null | undefined): 'live' | 'recent' | 'stale' | 'old' | 'unknown' {
    if (!isoDate) return 'unknown';
    const diff = Math.floor((Date.now() - new Date(isoDate).getTime()) / 1000);
    if (diff < 120)    return 'live';
    if (diff < 3600)   return 'recent';
    if (diff < 86400)  return 'stale';
    return 'old';
}

export function FreshnessIndicator({
    lastSyncedAt,
    sources,
    className,
}: FreshnessIndicatorProps) {
    const [open, setOpen] = useState(false);
    const state = getState(lastSyncedAt);

    const badge = (() => {
        switch (state) {
            case 'live':
                return (
                    <span className="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        Live
                    </span>
                );
            case 'recent':
                return (
                    <span className="text-xs text-zinc-500">
                        Updated {lastSyncedAt ? relativeTime(lastSyncedAt) : ''}
                    </span>
                );
            case 'stale':
                return (
                    <span className="inline-flex items-center rounded-full bg-amber-50 border border-amber-200 px-2 py-0.5 text-xs font-medium text-amber-700">
                        Updated {lastSyncedAt ? relativeTime(lastSyncedAt) : ''}
                    </span>
                );
            case 'old':
                return (
                    <span className="inline-flex items-center rounded-full bg-rose-50 border border-rose-200 px-2 py-0.5 text-xs font-medium text-rose-700">
                        Last synced {lastSyncedAt ? relativeTime(lastSyncedAt) : ''}
                    </span>
                );
            default:
                return (
                    <span className="text-xs text-zinc-400">No data</span>
                );
        }
    })();

    return (
        <div className={cn('relative', className)}>
            <button
                onClick={() => setOpen((v) => !v)}
                className="flex items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded"
                aria-label="Data freshness"
            >
                {badge}
            </button>

            {open && (
                <>
                    <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} />
                    <div
                        className="absolute right-0 top-full z-20 mt-2 w-80 rounded-lg border border-border bg-white p-3"
                        style={{ boxShadow: 'var(--shadow-raised)' }}
                    >
                        <p className="text-xs font-semibold text-zinc-900 mb-2">Data freshness</p>
                        {sources && sources.length > 0 ? (
                            <table className="w-full text-xs" aria-label="Per-source sync status">
                                <thead>
                                    <tr className="text-left text-zinc-400 border-b border-zinc-100">
                                        <th className="pb-1.5 font-medium">Source</th>
                                        <th className="pb-1.5 font-medium">Last sync</th>
                                        <th className="pb-1.5 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sources.map((src, i) => (
                                        <tr key={i} className="border-b border-zinc-50 last:border-0">
                                            <td className="py-1.5 text-zinc-700 font-medium">{src.label}</td>
                                            <td className="py-1.5 text-zinc-500 tabular-nums">
                                                {src.last_synced_at ? relativeTime(src.last_synced_at) : '—'}
                                            </td>
                                            <td className="py-1.5">
                                                <span className={cn(
                                                    'rounded-full px-1.5 py-0.5 font-medium',
                                                    src.status === 'healthy' && 'text-emerald-700 bg-emerald-50',
                                                    src.status === 'warning' && 'text-amber-700 bg-amber-50',
                                                    src.status === 'error' && 'text-rose-700 bg-rose-50',
                                                    !['healthy','warning','error'].includes(src.status) && 'text-zinc-500',
                                                )}>
                                                    {src.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : (
                            <p className="text-xs text-zinc-400">No integration data available.</p>
                        )}
                    </div>
                </>
            )}
        </div>
    );
}
