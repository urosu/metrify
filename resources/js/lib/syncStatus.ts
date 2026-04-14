/**
 * Shared sync-health helpers used by StoreFilter, SEO property pills,
 * and Campaigns ad account pills.
 *
 * Thresholds:
 *   green  — active + synced ≤48 h ago
 *   amber  — active + synced 48 h–7 d ago, or active but never synced
 *   red    — error, or no sync in >7 days
 *   gray   — any other status (pending, paused, etc.)
 */

export function syncDotClass(status: string, lastSyncedAt: string | null): string {
    if (status === 'error') return 'bg-red-400';
    if (status !== 'active') return 'bg-zinc-300';

    if (!lastSyncedAt) return 'bg-amber-400';

    const hoursAgo = (Date.now() - new Date(lastSyncedAt).getTime()) / 3_600_000;
    if (hoursAgo <= 48)  return 'bg-green-500';
    if (hoursAgo <= 168) return 'bg-amber-400';
    return 'bg-red-400';
}

export function syncDotTitle(status: string, lastSyncedAt: string | null): string {
    if (status === 'error') return 'Sync error';
    if (!lastSyncedAt)     return 'Never synced';
    const d = new Date(lastSyncedAt);
    return `Last synced ${d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`;
}
