/**
 * SyncHealthIndicator — compact TopBar pill showing aggregate sync health.
 *
 * Reads `integrations_freshness` from shared Inertia props. Shows:
 *   - A colored status dot (green = healthy, amber = degraded, red = errors)
 *   - A short label: "Synced" / "Syncing…" / "Issues"
 *   - On hover: tooltip with per-integration breakdown
 *   - On click: navigates to /integrations
 *
 * Color tokens:
 *   success → emerald-500, warning → amber-400, error → rose-500
 *
 * Returns null when no integrations are connected (zero chrome for new accounts).
 *
 * @see docs/UX.md §3 TopBar
 * @see docs/competitors/_research_chrome_layout.md §2 Sync health placement
 */
import { useMemo } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { FRESHNESS_THRESHOLDS, formatAge } from '@/lib/syncStatus';
import type { PageProps, IntegrationFreshness } from '@/types';
import { wurl } from '@/lib/workspace-url';

type HealthLevel = 'green' | 'amber' | 'red' | 'pending';

function computeLevel(integration: IntegrationFreshness): HealthLevel {
    if (integration.status === 'error' || integration.status === 'token_expired') return 'red';
    if (integration.historical_import_status === 'running') return 'pending';
    if (!integration.last_synced_at) return 'amber';

    const { green, amber } = FRESHNESS_THRESHOLDS[integration.type] ?? FRESHNESS_THRESHOLDS.store;
    const ageMs = Date.now() - new Date(integration.last_synced_at).getTime();

    if (ageMs <= green) {
        if ((integration.consecutive_sync_failures ?? 0) > 0) return 'amber';
        if (integration.historical_import_status === 'failed') return 'amber';
        return 'green';
    }
    if (ageMs <= amber) return 'amber';
    return 'red';
}

function overallLevel(levels: HealthLevel[]): HealthLevel {
    if (levels.includes('red')) return 'red';
    if (levels.includes('amber')) return 'amber';
    if (levels.includes('pending')) return 'pending';
    return 'green';
}

const DOT_CLASS: Record<HealthLevel, string> = {
    green:   'bg-emerald-500',
    amber:   'bg-amber-400',
    red:     'bg-rose-500',
    pending: 'bg-sky-500 animate-pulse',
};

const LABEL: Record<HealthLevel, string> = {
    green:   'Synced',
    amber:   'Issues',
    red:     'Issues',
    pending: 'Syncing…',
};

const LABEL_COLOR: Record<HealthLevel, string> = {
    green:   'text-emerald-700',
    amber:   'text-amber-600',
    red:     'text-rose-600',
    pending: 'text-sky-600',
};

const ICON: Record<HealthLevel, string> = {
    green:   '✓',
    amber:   '⚠',
    red:     '✗',
    pending: '↻',
};

export function SyncHealthIndicator() {
    const { integrations_freshness, workspace } = usePage<PageProps>().props;
    const integrations = (integrations_freshness ?? []) as IntegrationFreshness[];
    const workspaceSlug = workspace?.slug;

    const { level, lastSyncLabel, rows } = useMemo(() => {
        if (integrations.length === 0) {
            return { level: 'green' as HealthLevel, lastSyncLabel: '', rows: [] };
        }

        const levels = integrations.map(computeLevel);
        const overall = overallLevel(levels);

        // Most recently synced integration for the timestamp label
        const sorted = [...integrations]
            .filter((i) => i.last_synced_at)
            .sort((a, b) => new Date(b.last_synced_at!).getTime() - new Date(a.last_synced_at!).getTime());
        const freshest = sorted[0] ?? null;
        const lastSyncLabel = freshest ? formatAge(freshest.last_synced_at) : 'never';

        const rows = integrations.map((int) => ({
            label: int.label,
            level: computeLevel(int),
            age: formatAge(int.last_synced_at),
            status: int.status,
            failures: int.consecutive_sync_failures ?? 0,
            importStatus: int.historical_import_status,
        }));

        return { level: overall, lastSyncLabel, rows };
    }, [integrations]);

    // No integrations connected — render nothing
    if (integrations.length === 0) return null;

    return (
        <Link
            href={wurl(workspaceSlug, '/settings/integrations')}
            className="group/syncindicator relative flex items-center gap-1.5 rounded-md px-2 py-1 text-xs hover:bg-zinc-100 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            aria-label={`Sync health: ${LABEL[level]}`}
        >
            {/* Status dot — pulsing ring when green (live feel), static otherwise */}
            <span className="relative flex h-2 w-2 shrink-0">
                {level === 'green' && (
                    <span
                        className={cn(
                            'absolute inline-flex h-full w-full animate-ping rounded-full opacity-50',
                            DOT_CLASS.green,
                        )}
                    />
                )}
                <span
                    className={cn(
                        'relative inline-flex h-2 w-2 rounded-full',
                        DOT_CLASS[level],
                    )}
                />
            </span>

            {/* Label + last-sync timestamp */}
            <span className="flex flex-col leading-none">
                <span className={cn('font-medium', LABEL_COLOR[level])}>
                    {LABEL[level]}
                </span>
                {lastSyncLabel && (
                    <span className="text-[10px] text-zinc-400 mt-0.5 tabular-nums">
                        {lastSyncLabel}
                    </span>
                )}
            </span>

            {/* Hover tooltip — per-integration breakdown */}
            <div
                className="pointer-events-none invisible absolute right-0 top-full z-50 mt-2 w-max min-w-[200px] max-w-xs rounded-lg border border-zinc-200 bg-white px-3 py-2.5 text-xs shadow-lg opacity-0 transition-opacity duration-150 group-hover/syncindicator:visible group-hover/syncindicator:opacity-100"
                role="tooltip"
            >
                <div className="mb-2 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                    Data sources
                </div>
                <div className="space-y-1.5">
                    {rows.map((row, i) => {
                        const detail =
                            row.status === 'token_expired'
                                ? 'Token expired'
                                : row.failures > 0
                                ? `Failing (last ok ${row.age})`
                                : row.importStatus === 'failed'
                                ? 'Import failed'
                                : row.importStatus === 'running'
                                ? 'Importing…'
                                : row.age;

                        const textColor: Record<HealthLevel, string> = {
                            green:   'text-emerald-700',
                            amber:   'text-amber-600',
                            red:     'text-rose-600',
                            pending: 'text-sky-600',
                        };

                        return (
                            <div key={i} className="flex items-center justify-between gap-4">
                                <span className="text-zinc-500">{row.label}</span>
                                <span className={cn('font-medium tabular-nums', textColor[row.level])}>
                                    {ICON[row.level]} {detail}
                                </span>
                            </div>
                        );
                    })}
                </div>
                <div className="mt-2 border-t border-zinc-100 pt-1.5 text-[10px] text-zinc-400">
                    Click to manage integrations
                </div>
                {/* Caret pointing up */}
                <span className="absolute bottom-full right-4 border-4 border-transparent border-b-zinc-200" />
                <span className="absolute bottom-full right-4 border-[3px] border-transparent border-b-white" style={{ marginBottom: '-1px' }} />
            </div>
        </Link>
    );
}
