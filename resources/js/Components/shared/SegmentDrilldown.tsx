/**
 * SegmentDrilldown
 *
 * Full drill-down panel rendered below the RFM grid + segment cards when a
 * segment is selected. Receives the `segment_drilldown` Inertia prop plus
 * segment metadata (name, color, description, count, pct, trend).
 *
 * Layout:
 *   1. Header bar  — name, count, trend, "Send to email" (stub), "Export CSV"
 *   2. KPI strip   — avg LTV / avg AOV / avg frequency / days since last order
 *   3. Top products mini-table (top 5)
 *   4. Top channels mini-bar (top 5)
 *   5. Customer table (DataTable, paginated) — each row opens DrawerSidePanel
 *
 * Mobile: wrapped in DrawerSidePanel (sheet) when isMobile.
 *
 * @see docs/pages/customers.md §segment-drilldown
 */
import { useEffect, useCallback, useState } from 'react';
import { BarChart as RechartsBarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';
import { X, TrendingUp, TrendingDown, Minus, Mail, Download, User, ShoppingBag, Clock, Tag } from 'lucide-react';
import { DataTable } from '@/Components/shared/DataTable';
import type { Column } from '@/Components/shared/DataTable';
import { MetricCardCompact } from '@/Components/shared/MetricCardCompact';
import { EmptyState } from '@/Components/shared/EmptyState';
import { DrawerSidePanel } from '@/Components/shared/DrawerSidePanel';
import { toast } from '@/Components/shared/Toast';
import { formatCurrency, formatNumber } from '@/lib/formatters';
import { cn } from '@/lib/utils';

// ─── Types ────────────────────────────────────────────────────────────────────

export interface DrilldownCustomer {
    id: number | string;
    email: string;
    name: string;
    orders_count: number;
    ltv: number | null;
    last_order_at: string | null;
    recency_days: number | null;
    top_product: string | null;
    acquisition_source: string | null;
}

export interface DrilldownKpis {
    avg_ltv: number | null;
    avg_aov: number | null;
    avg_frequency: number | null;
    avg_recency_days: number | null;
}

export interface DrilldownTopProduct {
    product_name: string;
    customer_count: number;
    revenue: number;
}

export interface DrilldownTopChannel {
    channel: string;
    count: number;
    share: number;
}

export interface SegmentDrilldownData {
    customers: {
        data: DrilldownCustomer[];
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
    kpis: DrilldownKpis;
    top_products: DrilldownTopProduct[];
    top_channels: DrilldownTopChannel[];
}

export interface SegmentMeta {
    name: string;
    slug: string;
    color: string;
    description: string;
    count: number;
    pct: number;
    trend: number | null;
}

interface Props {
    data: SegmentDrilldownData;
    segment: SegmentMeta;
    currency: string;
    workspaceSlug: string | undefined;
    onClose: () => void;
    onPageChange: (page: number) => void;
}

// ─── Channel bar palette ──────────────────────────────────────────────────────

const CHANNEL_PALETTE = ['#6366f1', '#14b8a6', '#f59e0b', '#22c55e', '#94a3b8'];

// ─── Customer profile drawer ──────────────────────────────────────────────────

function CustomerProfileDrawer({
    customer,
    currency,
    onClose,
}: {
    customer: DrilldownCustomer | null;
    currency: string;
    onClose: () => void;
}) {
    if (!customer) return null;

    const aov = customer.ltv != null && customer.orders_count > 0
        ? customer.ltv / customer.orders_count
        : null;

    return (
        <DrawerSidePanel open={customer !== null} onClose={onClose} title="Customer Profile" width={420}>
            <div className="space-y-5">
                {/* Identity */}
                <div className="flex items-start gap-3">
                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-muted">
                        <User className="h-5 w-5 text-muted-foreground" />
                    </span>
                    <div className="min-w-0">
                        <p className="truncate font-semibold text-foreground">{customer.name !== '—' ? customer.name : customer.email}</p>
                        <p className="truncate text-sm text-muted-foreground">{customer.email}</p>
                        {customer.acquisition_source && (
                            <span className="mt-1 inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground capitalize">
                                <Tag className="h-3 w-3" />
                                {customer.acquisition_source}
                            </span>
                        )}
                    </div>
                </div>

                {/* KPI mini-strip */}
                <div className="grid grid-cols-2 gap-3">
                    <div className="rounded-lg border border-border bg-card p-3">
                        <p className="text-xs text-muted-foreground">Lifetime Value</p>
                        <p className="mt-0.5 text-lg font-bold tabular-nums text-foreground">
                            {customer.ltv != null ? formatCurrency(customer.ltv, currency, true) : '—'}
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-card p-3">
                        <p className="text-xs text-muted-foreground">Avg Order Value</p>
                        <p className="mt-0.5 text-lg font-bold tabular-nums text-foreground">
                            {aov != null ? formatCurrency(aov, currency, true) : '—'}
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-card p-3">
                        <p className="text-xs text-muted-foreground">Total Orders</p>
                        <p className="mt-0.5 flex items-center gap-1.5 text-lg font-bold tabular-nums text-foreground">
                            <ShoppingBag className="h-4 w-4 text-muted-foreground" />
                            {formatNumber(customer.orders_count)}
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-card p-3">
                        <p className="text-xs text-muted-foreground">Days Since Last Order</p>
                        <p className="mt-0.5 flex items-center gap-1.5 text-lg font-bold tabular-nums text-foreground">
                            <Clock className="h-4 w-4 text-muted-foreground" />
                            {customer.recency_days != null ? `${formatNumber(customer.recency_days)}d` : '—'}
                        </p>
                    </div>
                </div>

                {/* Top product */}
                {customer.top_product && (
                    <div className="rounded-lg border border-border bg-card p-3">
                        <p className="text-xs text-muted-foreground">Top Product</p>
                        <p className="mt-0.5 truncate text-sm font-medium text-foreground" title={customer.top_product}>
                            {customer.top_product}
                        </p>
                    </div>
                )}

                <p className="text-xs text-muted-foreground/70">
                    Full order history and predictive LTV are v2 features — available once the customer profile page ships.
                </p>
            </div>
        </DrawerSidePanel>
    );
}

// ─── Trend arrow (reused from parent) ─────────────────────────────────────────

function TrendArrow({ value }: { value: number | null }) {
    if (value === null) return null;
    if (value > 0) return (
        <span className="inline-flex items-center gap-0.5 text-sm font-medium text-green-700" title="vs last 30 days">
            <TrendingUp className="h-3 w-3" />
            +{formatNumber(value)}
        </span>
    );
    if (value < 0) return (
        <span className="inline-flex items-center gap-0.5 text-sm font-medium text-red-600" title="vs last 30 days">
            <TrendingDown className="h-3 w-3" />
            {formatNumber(value)}
        </span>
    );
    return (
        <span className="inline-flex items-center gap-0.5 text-sm font-medium text-muted-foreground" title="vs last 30 days">
            <Minus className="h-3 w-3" />
            0
        </span>
    );
}

// ─── Customer table columns ───────────────────────────────────────────────────

function buildCustomerColumns(currency: string): Column<DrilldownCustomer>[] {
    return [
        {
            key: 'email',
            header: 'Email',
            sortable: false,
            render: (value) => (
                <span className="font-medium text-foreground line-clamp-1 max-w-[180px] block truncate text-primary/80 hover:text-primary" title={String(value)}>{String(value)}</span>
            ),
        },
        {
            key: 'name',
            header: 'Name',
            sortable: false,
            render: (value) => (
                <span className="text-muted-foreground line-clamp-1 max-w-[140px] block truncate" title={String(value)}>{String(value)}</span>
            ),
        },
        {
            key: 'orders_count',
            header: 'Orders',
            sortable: true,
            width: 72,
            render: (value) => (
                <span className="tabular-nums text-muted-foreground text-right block">{formatNumber(Number(value))}</span>
            ),
        },
        {
            key: 'ltv',
            header: 'LTV',
            sortable: true,
            width: 96,
            render: (value) => (
                <span className="tabular-nums font-medium text-foreground text-right block">
                    {value != null ? formatCurrency(Number(value), currency, true) : '—'}
                </span>
            ),
        },
        {
            key: 'recency_days',
            header: 'Recency',
            sortable: true,
            width: 80,
            render: (value) => (
                <span className="tabular-nums text-muted-foreground text-right block">
                    {value != null ? `${formatNumber(Number(value))}d` : '—'}
                </span>
            ),
        },
        {
            key: 'top_product',
            header: 'Top Product',
            sortable: false,
            render: (value) => (
                <span className="text-muted-foreground/70 line-clamp-1 max-w-[160px] block truncate" title={value != null ? String(value) : ''}>
                    {value != null ? String(value) : '—'}
                </span>
            ),
        },
    ];
}

// ─── Main component ───────────────────────────────────────────────────────────

function DrilldownContent({
    data,
    segment,
    currency,
    onClose,
    onPageChange,
}: Omit<Props, 'isMobile' | 'workspaceSlug'>) {
    const { customers, kpis, top_products, top_channels } = data;
    const totalPages = customers.last_page;
    const currentPage = customers.current_page;

    const [profileCustomer, setProfileCustomer] = useState<DrilldownCustomer | null>(null);

    const columns = buildCustomerColumns(currency);
    const maxChannelShare = Math.max(...top_channels.map((c) => c.share), 0.01);

    return (
        <div className="space-y-6">
            {/* ── Header ───────────────────────────────────────────────────── */}
            <div
                className="flex flex-wrap items-start justify-between gap-3 rounded-xl border bg-card px-5 py-4"
                style={{ borderLeftColor: segment.color, borderLeftWidth: 3 }}
            >
                <div className="min-w-0">
                    {/* Active badge + name */}
                    <div className="flex flex-wrap items-center gap-2 mb-0.5">
                        <span
                            className="rounded px-1.5 py-0.5 text-xs font-semibold text-white"
                            style={{ backgroundColor: segment.color }}
                            aria-label="Active segment"
                        >
                            Active
                        </span>
                        <h3 className="text-base font-semibold text-foreground">{segment.name}</h3>
                        <TrendArrow value={segment.trend} />
                    </div>
                    <p className="text-sm text-muted-foreground line-clamp-2">{segment.description}</p>
                    <p className="mt-0.5 text-sm text-muted-foreground/70">
                        {formatNumber(segment.count)} customers &middot; {segment.pct.toFixed(0)}% of total
                    </p>
                </div>
                <div className="flex items-center gap-2 flex-shrink-0">
                    <button
                        type="button"
                        className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                        onClick={() => toast('Email integration coming soon — v2 feature.')}
                        title="Send to email (coming in v2)"
                    >
                        <Mail className="h-4 w-4" />
                        <span className="hidden sm:inline">Send to email</span>
                    </button>
                    <button
                        type="button"
                        className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                        title="Export segment CSV (coming in v2)"
                        onClick={() => toast('CSV export for segments is coming in v2.')}
                    >
                        <Download className="h-4 w-4" />
                        <span className="hidden sm:inline">Export CSV</span>
                    </button>
                    <button
                        type="button"
                        aria-label="Close drill-down"
                        className="rounded-lg p-2 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                        onClick={onClose}
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>
            </div>

            {/* ── KPI strip ─────────────────────────────────────────────────── */}
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <MetricCardCompact
                    label="Avg LTV"
                    value={kpis.avg_ltv != null ? formatCurrency(kpis.avg_ltv, currency, true) : '—'}
                    activeSource="store"
                />
                <MetricCardCompact
                    label="Avg AOV"
                    value={kpis.avg_aov != null ? formatCurrency(kpis.avg_aov, currency, true) : '—'}
                    activeSource="store"
                />
                <MetricCardCompact
                    label="Avg Frequency"
                    value={kpis.avg_frequency != null ? `${kpis.avg_frequency.toFixed(1)}×` : '—'}
                    activeSource="store"
                />
                <MetricCardCompact
                    label="Avg Days Since Order"
                    value={kpis.avg_recency_days != null ? `${formatNumber(kpis.avg_recency_days)}d` : '—'}
                    activeSource="store"
                />
            </div>

            {/* ── Top products + top channels (2-col on md+) ───────────────── */}
            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                {/* Top products */}
                <div>
                    <h4 className="mb-2 text-sm font-semibold text-foreground">Top Products</h4>
                    {top_products.length === 0 ? (
                        <p className="text-sm text-muted-foreground/70">No product data available.</p>
                    ) : (
                        <div className="overflow-hidden rounded-xl border border-border bg-card">
                            <table className="min-w-full divide-y divide-border text-sm">
                                <thead className="bg-muted/50 border-b border-border">
                                    <tr>
                                        <th className="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Product</th>
                                        <th className="px-3 py-2.5 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wide">Customers</th>
                                        <th className="px-3 py-2.5 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wide">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border bg-card">
                                    {top_products.map((p) => (
                                        <tr key={p.product_name} className="hover:bg-muted/50 transition-colors">
                                            <td className="px-4 py-2.5 font-medium text-foreground max-w-[160px]">
                                                <span className="block truncate" title={p.product_name}>{p.product_name}</span>
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-muted-foreground">{formatNumber(p.customer_count)}</td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-foreground font-medium">{formatCurrency(p.revenue, currency, true)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Top channels bar */}
                <div>
                    <h4 className="mb-2 text-sm font-semibold text-foreground">Acquisition Channels</h4>
                    {top_channels.length === 0 ? (
                        <p className="text-sm text-muted-foreground/70">No channel data available.</p>
                    ) : (
                        <div className="rounded-xl border border-border bg-card p-4">
                            <ResponsiveContainer width="100%" height={top_channels.length * 36 + 16}>
                                <RechartsBarChart
                                    data={top_channels}
                                    layout="vertical"
                                    margin={{ top: 0, right: 40, bottom: 0, left: 0 }}
                                    barSize={18}
                                >
                                    <XAxis
                                        type="number"
                                        domain={[0, maxChannelShare]}
                                        tickFormatter={(v: number) => `${(v * 100).toFixed(0)}%`}
                                        tick={{ fontSize: 11, fill: '#71717a' }}
                                        axisLine={false}
                                        tickLine={false}
                                    />
                                    <YAxis
                                        type="category"
                                        dataKey="channel"
                                        width={90}
                                        tick={{ fontSize: 12, fill: '#52525b' }}
                                        axisLine={false}
                                        tickLine={false}
                                    />
                                    <Tooltip
                                        formatter={(value) => [`${(Number(value) * 100).toFixed(1)}%`, 'Share']}
                                        cursor={{ fill: 'rgba(0,0,0,0.04)' }}
                                    />
                                    <Bar dataKey="share" radius={[0, 3, 3, 0]}>
                                        {top_channels.map((entry, index) => (
                                            <Cell key={entry.channel} fill={CHANNEL_PALETTE[index % CHANNEL_PALETTE.length]} />
                                        ))}
                                    </Bar>
                                </RechartsBarChart>
                            </ResponsiveContainer>
                        </div>
                    )}
                </div>
            </div>

            {/* ── Customer table ─────────────────────────────────────────────── */}
            <div>
                <h4 className="mb-2 text-sm font-semibold text-foreground">
                    Customers
                    <span className="ml-2 text-muted-foreground font-normal">({formatNumber(customers.total)})</span>
                </h4>
                {customers.data.length === 0 ? (
                    <EmptyState
                        title="No customers in this segment yet"
                        description="Try widening the date range, or check back after the next nightly RFM scoring run."
                    />
                ) : (
                    <>
                        <p className="mb-2 text-xs text-muted-foreground/60">Click a row to view customer profile</p>
                        <div className="overflow-hidden rounded-xl border border-border bg-card">
                            <DataTable
                                columns={columns}
                                data={customers.data}
                                emptyMessage="No customers"
                                onRowClick={(row) => setProfileCustomer(row)}
                            />
                        </div>
                        {/* Pagination */}
                        {totalPages > 1 && (
                            <div className="mt-3 flex items-center justify-between text-sm text-muted-foreground">
                                <span>
                                    Page {currentPage} of {totalPages} &middot; {formatNumber(customers.total)} total
                                </span>
                                <div className="flex items-center gap-1">
                                    <button
                                        type="button"
                                        disabled={currentPage <= 1}
                                        onClick={() => onPageChange(currentPage - 1)}
                                        className={cn(
                                            'rounded px-3 py-1.5 text-sm font-medium transition-colors',
                                            currentPage <= 1
                                                ? 'text-muted-foreground/40 cursor-not-allowed'
                                                : 'hover:bg-muted text-foreground',
                                        )}
                                    >
                                        Prev
                                    </button>
                                    <button
                                        type="button"
                                        disabled={currentPage >= totalPages}
                                        onClick={() => onPageChange(currentPage + 1)}
                                        className={cn(
                                            'rounded px-3 py-1.5 text-sm font-medium transition-colors',
                                            currentPage >= totalPages
                                                ? 'text-muted-foreground/40 cursor-not-allowed'
                                                : 'hover:bg-muted text-foreground',
                                        )}
                                    >
                                        Next
                                    </button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>

            {/* ── Customer profile drawer ───────────────────────────────────── */}
            <CustomerProfileDrawer
                customer={profileCustomer}
                currency={currency}
                onClose={() => setProfileCustomer(null)}
            />
        </div>
    );
}

// ─── Exported wrapper: always a slide-over Sheet (desktop 720px, mobile full) ──

export function SegmentDrilldown(props: Props) {
    const { onClose } = props;

    // ESC key is handled natively by the Sheet component via Radix Dialog;
    // we keep the manual listener only as a safety net for non-Sheet contexts.
    const handleKeyDown = useCallback(
        (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        },
        [onClose],
    );

    useEffect(() => {
        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [handleKeyDown]);

    return (
        <DrawerSidePanel open title={props.segment.name} onClose={onClose} width={720}>
            <DrilldownContent {...props} />
        </DrawerSidePanel>
    );
}
