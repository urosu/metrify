import axios from 'axios';
import { useMemo, useRef, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { wurl } from '@/lib/workspace-url';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { formatCurrency } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

interface StoreListItem {
    id: number;
    slug: string;
    name: string;
    domain: string;
    type: string;
    status: 'connecting' | 'active' | 'error' | 'disconnected';
    currency: string;
    timezone: string;
    last_synced_at: string | null;
    historical_import_status: string | null;
    // Last 30-day revenue + marketing % (workspace ad spend / store revenue × 100)
    revenue_30d: number | null;
    marketing_pct: number | null;
}

interface Props extends PageProps {
    stores: StoreListItem[];
    // Null when no workspace target is set — chips are hidden entirely in that case.
    workspace_target_marketing_pct: number | null;
}

function formatRelativeTime(iso: string | null): string {
    if (!iso) return '—';
    const diff = Date.now() - new Date(iso).getTime();
    const mins  = Math.floor(diff / 60_000);
    const hours = Math.floor(mins / 60);
    const days  = Math.floor(hours / 24);
    if (mins < 1)   return 'just now';
    if (mins < 60)  return `${mins}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
}

export default function StoresIndex({ stores, workspace_target_marketing_pct }: Props) {
    const { workspace, auth } = usePage<PageProps>().props;
    const currency = workspace?.reporting_currency ?? 'EUR';
    const w = (path: string) => wurl(workspace?.slug, path);

    // ── Winners / Losers filter ──────────────────────────────────────────────
    // Winners = stores whose marketing % is below the workspace target.
    // Losers  = stores whose marketing % is at or above the workspace target.
    //
    // Chips are hidden when no target is set — classification requires a benchmark.
    // Persisted to view_preferences. See: PLANNING.md "Winners/Losers" — /stores chip
    const savedFilter = (auth.user?.view_preferences?.stores?.filter ?? 'all') as 'all' | 'winners' | 'losers';
    const [filter, setFilter] = useState<'all' | 'winners' | 'losers'>(savedFilter);
    const filterPersistTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    function setFilterAndPersist(f: 'all' | 'winners' | 'losers') {
        setFilter(f);
        if (filterPersistTimeout.current) clearTimeout(filterPersistTimeout.current);
        filterPersistTimeout.current = setTimeout(() => {
            axios.patch('/settings/view-preferences', {
                preferences: { stores: { filter: f } },
            }).catch(() => {});
        }, 400);
    }

    const filteredStores = useMemo(() => {
        if (filter === 'all' || workspace_target_marketing_pct === null) return stores;
        return stores.filter(s => {
            // Stores with no marketing data (no ad spend or no revenue) → treated as losers:
            // we can't confirm they're healthy, so we don't surface them as winners.
            if (s.marketing_pct === null) return filter === 'losers';
            return filter === 'winners'
                ? s.marketing_pct < workspace_target_marketing_pct
                : s.marketing_pct >= workspace_target_marketing_pct;
        });
    }, [stores, filter, workspace_target_marketing_pct]);

    // Only show chips when the workspace has a marketing target set.
    const showFilterChips = workspace_target_marketing_pct !== null && stores.length > 0;

    return (
        <AppLayout>
            <Head title="Stores" />
            <PageHeader
                title="Stores"
                subtitle="All connected stores in this workspace"
                action={
                    <Link
                        href="/onboarding"
                        className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"
                    >
                        Connect store
                    </Link>
                }
            />

            {stores.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white px-6 py-20 text-center">
                    <p className="text-sm text-zinc-500">No stores connected yet.</p>
                    <Link
                        href="/onboarding"
                        className="mt-4 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"
                    >
                        Connect a store →
                    </Link>
                </div>
            ) : (
                <>
                    {/* Winners / Losers chips — only shown when multiple stores have delta data.
                        Winners = above workspace-avg revenue growth. Losers = below.
                        See: PLANNING.md "Winners/Losers" */}
                    {showFilterChips && (
                        <div className="mb-3 flex items-center gap-2">
                            {(['all', 'winners', 'losers'] as const).map(f => (
                                <button
                                    key={f}
                                    onClick={() => setFilterAndPersist(f)}
                                    className={cn(
                                        'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                        filter === f
                                            ? f === 'winners'
                                                ? 'border-green-300 bg-green-50 text-green-700'
                                                : f === 'losers'
                                                ? 'border-red-300 bg-red-50 text-red-700'
                                                : 'border-primary bg-primary/10 text-primary'
                                            : 'border-zinc-200 text-zinc-500 hover:border-zinc-300 hover:text-zinc-700',
                                    )}
                                    title={
                                        f === 'winners'
                                            ? `Marketing % < ${workspace_target_marketing_pct?.toFixed(1)}% target`
                                            : f === 'losers'
                                            ? `Marketing % ≥ ${workspace_target_marketing_pct?.toFixed(1)}% target`
                                            : 'Show all stores'
                                    }
                                >
                                    {f === 'all' ? 'All' : f === 'winners' ? '🏆 Winners' : '📉 Losers'}
                                </button>
                            ))}
                            {filter !== 'all' && (
                                <span className="text-xs text-zinc-400">
                                    {filteredStores.length} / {stores.length}
                                </span>
                            )}
                        </div>
                    )}

                    <div className="rounded-xl border border-zinc-200 bg-white overflow-hidden">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-zinc-100 text-left bg-zinc-50">
                                    <th className="px-4 py-3 font-medium text-zinc-400">Store</th>
                                    <th className="px-4 py-3 font-medium text-zinc-400">Status</th>
                                    <th className="px-4 py-3 font-medium text-zinc-400 hidden md:table-cell text-right">30d Revenue</th>
                                    <th className="px-4 py-3 font-medium text-zinc-400 hidden sm:table-cell">Currency</th>
                                    <th className="px-4 py-3 font-medium text-zinc-400 hidden lg:table-cell">Last Synced</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-100">
                                {filteredStores.map((store) => (
                                    <tr key={store.id} className="hover:bg-zinc-50 transition-colors">
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-zinc-900">{store.name}</div>
                                            <div className="text-xs text-zinc-400">{store.domain}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <StatusBadge status={store.status} />
                                        </td>
                                        <td className="px-4 py-3 text-right hidden md:table-cell">
                                            {store.revenue_30d !== null ? (
                                                <>
                                                    <div className="tabular-nums text-zinc-700">
                                                        {formatCurrency(store.revenue_30d, currency)}
                                                    </div>
                                                    {store.marketing_pct !== null && (
                                                        <div className="text-xs text-zinc-400 mt-0.5 tabular-nums">
                                                            {store.marketing_pct.toFixed(1)}% mktg
                                                        </div>
                                                    )}
                                                </>
                                            ) : (
                                                <span className="text-zinc-400">—</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-zinc-600 hidden sm:table-cell tabular-nums">
                                            {store.currency}
                                        </td>
                                        <td className="px-4 py-3 text-zinc-400 hidden lg:table-cell">
                                            {formatRelativeTime(store.last_synced_at)}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={w(`/stores/${store.slug}/overview`)}
                                                className="text-sm font-medium text-primary hover:text-primary/80"
                                            >
                                                View →
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </>
            )}
        </AppLayout>
    );
}
