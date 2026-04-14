import axios from 'axios';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Package } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { DateRangePicker } from '@/Components/shared/DateRangePicker';
import { PageHeader } from '@/Components/shared/PageHeader';
import { AnalyticsTabBar } from '@/Components/shared/AnalyticsTabBar';
import { StoreFilter } from '@/Components/shared/StoreFilter';
import { BreakdownView, type BreakdownRow, type BreakdownColumn } from '@/Components/shared/BreakdownView';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

interface ProductRow {
    external_id: string;
    name: string;
    units: number;
    revenue: number | null;
    revenue_delta: number | null;
    units_delta: number | null;
}

interface Props {
    products: ProductRow[];
    from: string;
    to: string;
    store_ids: number[];
    sort_by: 'revenue' | 'units';
    sort_dir: 'asc' | 'desc';
}

// ─── Columns definition ───────────────────────────────────────────────────────

function buildColumns(currency: string): BreakdownColumn[] {
    return [
        { key: 'revenue',       label: 'Revenue',        format: 'currency', currency, showInCards: true },
        { key: 'revenue_delta', label: 'Rev. vs prior',  format: 'percent',  isChangePct: true, showInCards: true },
        { key: 'units',         label: 'Units',          format: 'number',   showInCards: true },
        { key: 'units_delta',   label: 'Units vs prior', format: 'percent',  isChangePct: true, showInCards: true },
    ];
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function AnalyticsProducts({ products, from, to, store_ids }: Props) {
    const { workspace, auth } = usePage<PageProps>().props;
    const currency = workspace?.reporting_currency ?? 'EUR';
    const [navigating, setNavigating] = useState(false);

    useEffect(() => {
        const removeStart  = router.on('start',  () => setNavigating(true));
        const removeFinish = router.on('finish', () => setNavigating(false));
        return () => { removeStart(); removeFinish(); };
    }, []);

    // ── Winners / Losers filter — lifted out of BreakdownView ───────────────
    // URL ?filter param takes priority (used by sidebar deep-links).
    //
    // Winners = top 10 products by revenue this period.
    // Losers  = everything else in the visible list (ranks 11–50).
    // Classification is purely rank-based — no time comparison.
    //
    // Why useEffect sync: Inertia doesn't re-mount the component on same-page
    // visits (e.g., /analytics/products → /analytics/products?filter=winners).
    // useState initial value only runs on first mount, so we must sync manually
    // after navigation events.
    // See: PLANNING.md "Products page — Winners/Losers filter chips (Phase 1.4)"
    const readFilterFromUrl = (): 'all' | 'winners' | 'losers' => {
        if (typeof window === 'undefined') return 'all';
        const f = new URLSearchParams(window.location.search).get('filter');
        return (f === 'winners' || f === 'losers') ? f : 'all';
    };

    const [filter, setFilter] = useState<'all' | 'winners' | 'losers'>(readFilterFromUrl);
    const filterPersistTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Sync filter state whenever Inertia navigates (covers same-page visits)
    useEffect(() => {
        return router.on('navigate', () => setFilter(readFilterFromUrl()));
    // readFilterFromUrl reads window.location at call time — no deps needed
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    function setFilterAndPersist(f: 'all' | 'winners' | 'losers') {
        setFilter(f);
        if (filterPersistTimeout.current) clearTimeout(filterPersistTimeout.current);
        filterPersistTimeout.current = setTimeout(() => {
            axios.patch('/settings/view-preferences', {
                preferences: { analytics_products: { filter: f } },
            }).catch(() => {});
        }, 400);
    }

    // Classify winners and losers — purely by current-period revenue rank.
    //
    // Winners = top 10 products by revenue this period.
    // Losers  = everything else in the visible list (ranks 11–50).
    //
    // Why rank-only, no time comparison: period-over-period delta has no absolute
    // anchor — a product at rank 2 with a -5% delta is still a winner by any
    // meaningful definition. Classification needs a benchmark; the benchmark here
    // is rank within the workspace's top products, not change vs last period.
    // The delta column (Rev. vs prior) is still displayed for information, but it
    // does NOT drive the winner/loser label.
    const WINNER_N = 10;

    const { winnerIds, loserIds } = useMemo(() => {
        const sorted = [...products].sort((a, b) => (b.revenue ?? 0) - (a.revenue ?? 0));
        const wIds = new Set(sorted.slice(0, WINNER_N).map(p => p.external_id));
        const lIds = new Set(sorted.slice(WINNER_N).map(p => p.external_id));
        return { winnerIds: wIds, loserIds: lIds };
    }, [products]);

    // ── Convert products to BreakdownRow format + apply filter ──────────────
    const allRows: BreakdownRow[] = useMemo(
        () => products.map(p => ({
            id: p.external_id,
            label: p.name,
            metrics: {
                revenue:       p.revenue,
                revenue_delta: p.revenue_delta,
                units:         p.units,
                units_delta:   p.units_delta,
            },
        })),
        [products],
    );

    const filteredRows = useMemo(() => {
        if (filter === 'all') return allRows;
        return allRows.filter(row => {
            const id = String(row.id);
            return filter === 'winners' ? winnerIds.has(id) : loserIds.has(id);
        });
    }, [allRows, filter, winnerIds, loserIds]);

    // ── Column definitions ───────────────────────────────────────────────────
    const columns = useMemo(() => buildColumns(currency), [currency]);

    return (
        <AppLayout dateRangePicker={<DateRangePicker />}>
            <Head title="Analytics — By Product" />
            <PageHeader title="Analytics" subtitle="Top products by revenue" />
            <AnalyticsTabBar />
            <StoreFilter selectedStoreIds={store_ids} />

            {/* ── Winners / Losers filter chips ──────────────────────────────── */}
            {products.length > 0 && (
                <div className="mb-4 flex items-center gap-2 flex-wrap">
                    <div className="flex items-center gap-1">
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
                                        ? `Top ${WINNER_N} products by revenue this period`
                                        : f === 'losers'
                                        ? `All other products (ranks ${WINNER_N + 1}–50)`
                                        : 'Show all products'
                                }
                            >
                                {f === 'all' ? 'All' : f === 'winners' ? '🏆 Winners' : '📉 Losers'}
                            </button>
                        ))}
                        {filter !== 'all' && (
                            <span className="text-xs text-zinc-400">
                                {filteredRows.length} product{filteredRows.length !== 1 ? 's' : ''}
                            </span>
                        )}
                    </div>
                </div>
            )}

            {navigating ? (
                <BreakdownView
                    breakdownBy="product"
                    cardData="store"
                    columns={columns}
                    data={[]}
                    loading={true}
                />
            ) : products.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white px-6 py-20 text-center">
                    <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100">
                        <Package className="h-6 w-6 text-zinc-400" />
                    </div>
                    <h3 className="mb-1 text-base font-semibold text-zinc-900">No product data</h3>
                    <p className="max-w-xs text-sm text-zinc-500">
                        Product data is derived from order snapshots. It appears after the nightly
                        snapshot job has run.
                    </p>
                </div>
            ) : (
                <BreakdownView
                    breakdownBy="product"
                    cardData="store"
                    columns={columns}
                    data={filteredRows}
                    defaultView="table"
                    viewKey="analytics_products"
                    currency={currency}
                    emptyMessage={
                        filter !== 'all'
                            ? `No ${filter} found for this period.`
                            : 'No product data for this period.'
                    }
                />
            )}
        </AppLayout>
    );
}
