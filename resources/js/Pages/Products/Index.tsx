import { memo, useCallback, useMemo, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Package, X, ChevronRight, ChevronDown, ShoppingBag, GitBranch, LayoutList, Search } from 'lucide-react';
import { QuadrantChart } from '@/Components/charts/QuadrantChart';
import type { QuadrantPoint, QuadrantFieldConfig } from '@/Components/charts/QuadrantChart';
import { Sparkline } from '@/Components/charts/Sparkline';
import type { SparklineDataPoint } from '@/Components/charts/Sparkline';
import { ComposedChart, Bar, Cell, Line, ReferenceLine, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import AppLayout from '@/Components/layouts/AppLayout';
import { MetricCard } from '@/Components/shared/MetricCard';
import { KpiGrid } from '@/Components/shared/KpiGrid';
import { PageHeader } from '@/Components/shared/PageHeader';
import { InlineEditableCell } from '@/Components/shared/InlineEditableCell';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import { InfoTooltip } from '@/Components/shared/Tooltip';
import { DataTable } from '@/Components/shared/DataTable';
import type { Column } from '@/Components/shared/DataTable';
import { LineChart } from '@/Components/charts/LineChart';
import { formatCurrency, formatNumber } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';
import { EmptyState } from '@/Components/shared/EmptyState';
import { getNextOnboardingStep } from '@/lib/onboarding';

// ─── Types ────────────────────────────────────────────────────────────────────

export interface ProductRow {
    id: number;
    external_id: string;
    name: string;
    thumbnail_url: string | null;
    sku: string | null;
    units: number;
    revenue: number;
    orders: number;
    aov: number | null;
    cogs: number | null;
    margin_pct: number | null;
    velocity: number | null;
    velocity_sparkline: number[];
    label: 'rockstar' | 'hot' | 'cold' | 'at_risk' | null;
    sources: Array<'store' | 'facebook' | 'google' | 'gsc' | 'ga4' | 'real'>;
    days_of_cover: number | null;
    views: number | null;
    view_cvr: number | null;       // 0–1, items_purchased / item_views
    add_to_cart_rate: number | null;
    top_campaigns: string[];       // up to 3 GA4 campaign names
    repeat_rate: number | null;    // share of customers who reordered (null = < 5 customers)
}

interface Metrics {
    products_sold: number;
    top10_concentration: number | null;
    median_gross_margin: number | null;
    velocity_mean: number | null;
    velocity_median: number | null;
    velocity_mode: number | null;
}

interface ParetoPoint {
    rank: number;
    revenue: number;
    cumulative_pct: number;
    name: string;
}

type SortKey = 'revenue' | 'margin' | 'velocity' | 'orders' | 'units' | 'name' | 'aov' | 'repeat_rate';

interface Filters {
    from: string;
    to: string;
    country?: string;
    channel?: string;
    stock_alert?: 7 | 30 | null;
    sort?: SortKey;
}

export interface ProductJourneyRow {
    product_external_id: string;
    product_name: string;
    as_first: number;
    as_second: number;
    as_third: number;
}

export interface MarketBasketRow {
    product_a_id: string;
    product_a: string;
    product_b_id: string;
    product_b: string;
    co_purchase_count: number;
    lift_pct: number;
}

interface Props extends PageProps {
    products: ProductRow[];
    metrics: Metrics;
    pareto_data: ParetoPoint[];
    filters: Filters;
    cogs_configured_count: number;
    total_skus: number;
    product_journey: ProductJourneyRow[];
    market_basket: MarketBasketRow[];
    /** Pre-populate the search box — used when linking from LTV Drivers (Customers page). */
    initial_search?: string | null;
}

// ─── Product lifecycle chip ───────────────────────────────────────────────────

const LIFECYCLE_CONFIG = {
    rockstar: { label: 'Rockstar', className: 'bg-yellow-100 text-yellow-800' },
    hot:      { label: 'Hot',      className: 'bg-green-100 text-green-800' },
    cold:     { label: 'Cold',     className: 'bg-blue-100 text-blue-800' },
    at_risk:  { label: 'At Risk',  className: 'bg-rose-100 text-rose-800' },
} as const;

function ProductLifecycleChip({ label }: { label: ProductRow['label'] }) {
    if (!label) return null;
    const { label: text, className } = LIFECYCLE_CONFIG[label];
    return (
        <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', className)}>
            {text}
        </span>
    );
}

// ─── Row sparkline ────────────────────────────────────────────────────────────

function RowSparkline({ data }: { data: number[] }) {
    if (data.length < 2) {
        return <span className="text-xs text-muted-foreground/70">—</span>;
    }
    const last = data[data.length - 1];
    const first = data[0];
    const color = last >= first ? 'var(--color-emerald-600, #059669)' : 'var(--color-red-600, #dc2626)';
    const sparkData: SparklineDataPoint[] = data.map((v) => ({ value: v }));
    return <Sparkline data={sparkData} color={color} height={24} />;
}

// ─── Sortable column header ────────────────────────────────────────────────────

function SortableHeader({
    label,
    sortKey,
    activeSort,
    onSort,
    align = 'right',
    children,
}: {
    label: string;
    sortKey: SortKey;
    activeSort: SortKey;
    onSort: (k: SortKey) => void;
    align?: 'left' | 'right';
    children?: React.ReactNode;
}) {
    const active = activeSort === sortKey;
    return (
        <th
            scope="col"
            aria-sort={active ? 'descending' : 'none'}
            className={cn(
                'px-3 py-2.5 text-xs font-semibold text-muted-foreground uppercase tracking-wide cursor-pointer select-none hover:text-foreground transition-colors',
                align === 'right' ? 'text-right' : 'text-left',
            )}
            onClick={() => onSort(sortKey)}
        >
            <span className={cn('inline-flex items-center gap-0.5', align === 'right' && 'justify-end w-full')}>
                {children ?? label}
                {active
                    ? <ChevronDown className="h-3 w-3 text-primary" />
                    : <ChevronDown className="h-3 w-3 text-muted-foreground/50" />}
            </span>
        </th>
    );
}

// ─── Source badge strip ───────────────────────────────────────────────────────
// Uses --color-source-* tokens defined in resources/css/app.css.
// Never alias one to another (CLAUDE.md constraint).

const SOURCE_ORDER: Array<'real' | 'store' | 'facebook' | 'google' | 'gsc' | 'ga4'> = ['real', 'store', 'facebook', 'google', 'gsc', 'ga4'];

function SourceStrip({ sources }: { sources: ProductRow['sources'] }) {
    return (
        <div className="flex items-center gap-0.5" aria-hidden="true">
            {SOURCE_ORDER.map((s) => (
                <span
                    key={s}
                    title={s}
                    className="h-1.5 w-1.5 rounded-full"
                    style={sources.includes(s)
                        ? { backgroundColor: `var(--color-source-${s})` }
                        : { backgroundColor: 'var(--muted)' }}
                />
            ))}
        </div>
    );
}

// ─── Memoized product table row ───────────────────────────────────────────────
//
// Memoized because the products table can exceed 500 rows and client-side
// filter/sort changes (labelFilter, savedView, searchQuery) would otherwise
// re-render every row on each keystroke. Props are stable scalars — no inline
// object/function props are passed.

interface ProductTableRowProps {
    p: ProductRow;
    currency: string;
    onSelect: (p: ProductRow) => void;
}

const ProductTableRow = memo(function ProductTableRow({ p, currency, onSelect }: ProductTableRowProps) {
    const profit = p.margin_pct != null ? p.revenue * (p.margin_pct / 100) : null;
    const [thumbHovered, setThumbHovered] = useState(false);

    return (
        <tr
            onClick={() => onSelect(p)}
            className="cursor-pointer hover:bg-muted/50 transition-colors"
        >
            {/* Product cell */}
            <td className="px-4 py-2.5 max-w-[220px]">
                <div className="flex items-center gap-2">
                    {p.thumbnail_url ? (
                        <div
                            className="relative shrink-0"
                            onMouseEnter={() => setThumbHovered(true)}
                            onMouseLeave={() => setThumbHovered(false)}
                            onClick={(e) => e.stopPropagation()}
                        >
                            <img src={p.thumbnail_url} alt="" className="h-8 w-8 rounded object-cover" />
                            {thumbHovered && (
                                <div className="absolute left-10 top-1/2 z-50 -translate-y-1/2 rounded-lg border border-border bg-card shadow-xl p-1.5">
                                    <img src={p.thumbnail_url} alt={p.name} className="h-28 w-28 rounded object-cover" />
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-muted">
                            <Package className="h-4 w-4 text-muted-foreground/50" />
                        </div>
                    )}
                    <div className="min-w-0">
                        <div className="truncate text-sm font-medium text-foreground">{p.name}</div>
                        {p.sku && <div className="font-mono text-xs text-muted-foreground/70 truncate">{p.sku}</div>}
                    </div>
                    <ChevronRight className="ml-auto h-3.5 w-3.5 shrink-0 text-muted-foreground/50" />
                </div>
            </td>

            {/* Label */}
            <td className="px-3 py-2.5">
                <ProductLifecycleChip label={p.label} />
            </td>

            {/* Revenue */}
            <td className="px-3 py-2.5 text-right text-sm tabular-nums text-foreground">
                {formatCurrency(p.revenue, currency, true)}
            </td>

            {/* Units */}
            <td className="px-3 py-2.5 text-right text-sm tabular-nums text-foreground">
                {formatNumber(p.units)}
            </td>

            {/* Margin % */}
            <td className={cn(
                'px-3 py-2.5 text-right text-sm tabular-nums font-medium',
                p.margin_pct == null ? 'text-muted-foreground/70'
                : p.margin_pct < 0   ? 'text-red-600'
                : p.margin_pct < 20  ? 'text-amber-600'
                : 'text-green-700',
            )}>
                {p.margin_pct != null ? `${p.margin_pct.toFixed(1)}%` : '—'}
            </td>

            {/* Gross Profit $ */}
            <td className={cn(
                'px-3 py-2.5 text-right text-sm tabular-nums font-medium',
                profit == null  ? 'text-muted-foreground/70'
                : profit < 0   ? 'text-red-600'
                : profit < 100 ? 'text-amber-600'
                : 'text-green-700',
            )}>
                {profit != null ? formatCurrency(profit, currency, true) : '—'}
            </td>

            {/* Repeat Rate */}
            <td className="px-3 py-2.5 text-right text-sm tabular-nums">
                {p.repeat_rate != null ? (
                    <span className={cn(
                        'font-medium',
                        p.repeat_rate >= 0.30 ? 'text-green-700' :
                        p.repeat_rate >= 0.10 ? 'text-amber-600' :
                        'text-muted-foreground',
                    )}>
                        {(p.repeat_rate * 100).toFixed(1)}%
                    </span>
                ) : (
                    <span className="text-muted-foreground/50">—</span>
                )}
            </td>

            {/* Stock (days of cover) */}
            <td className="px-3 py-2.5 text-right text-sm tabular-nums">
                <DaysOfCoverCell value={p.days_of_cover} />
            </td>

            {/* Velocity sparkline */}
            <td className="px-3 py-2.5">
                <div className="flex flex-col gap-0.5">
                    <RowSparkline data={p.velocity_sparkline} />
                    {p.velocity != null && (
                        <span className="text-xs text-muted-foreground/70 tabular-nums">{p.velocity.toFixed(2)}/day</span>
                    )}
                </div>
            </td>
        </tr>
    );
});

// ─── Days of cover cell ───────────────────────────────────────────────────────

function DaysOfCoverCell({ value }: { value: number | null }) {
    if (value === null) {
        return <span className="text-muted-foreground/70">—</span>;
    }
    const colorClass =
        value <= 7  ? 'text-red-600 font-semibold' :
        value <= 30 ? 'text-amber-600 font-medium' :
        'text-foreground';
    return <span className={colorClass}>{value.toFixed(1)}d</span>;
}

// ─── Pareto chart ─────────────────────────────────────────────────────────────

const MAX_PARETO_BARS = 20;

interface ParetoChartItem {
    rank: number;
    name: string;
    revenue: number;
    cumPct: number;
    isCore: boolean;
}

function ParetoCustomTooltip({
    active,
    payload,
    currency,
}: {
    active?: boolean;
    payload?: Array<{ payload: ParetoChartItem }>;
    currency: string;
}) {
    if (!active || !payload?.length) return null;
    const d = payload[0].payload;
    return (
        <div className="rounded-lg border border-border bg-card px-3 py-2 shadow-md text-xs">
            <p className="font-medium text-foreground mb-1">{d.name}</p>
            <p className="text-muted-foreground">Revenue: <span className="font-semibold text-foreground">{formatCurrency(d.revenue, currency, true)}</span></p>
            <p className="text-muted-foreground">Cumulative: <span className="font-semibold text-foreground">{d.cumPct.toFixed(1)}%</span></p>
        </div>
    );
}

function ParetoChart({
    data,
    onBarClick,
    selectedRank,
    currency,
}: {
    data: ParetoPoint[];
    onBarClick: (rank: number) => void;
    selectedRank: number | null;
    currency: string;
}) {
    if (data.length === 0) {
        return (
            <div className="flex h-40 items-center justify-center text-sm text-muted-foreground/70">
                No pareto data available.
            </div>
        );
    }

    // Slice to top 20 (already sorted by rank = revenue DESC from the server)
    const sliced = data.slice(0, MAX_PARETO_BARS);
    const truncated = data.length > MAX_PARETO_BARS;

    // Natural break: first product that pushes cumulative past 80%
    const naturalBreak = sliced.findIndex((d) => d.cumulative_pct > 80);
    const splitAt = naturalBreak !== -1 ? naturalBreak : Math.ceil(sliced.length / 2);
    const threshold80Rank = naturalBreak !== -1 ? naturalBreak : null;

    const chartData: ParetoChartItem[] = sliced.map((d, i) => ({
        rank: d.rank,
        name: d.name,
        revenue: d.revenue,
        cumPct: parseFloat(d.cumulative_pct.toFixed(1)),
        isCore: i < splitAt,
    }));

    return (
        <div>
            {truncated && (
                <p className="mb-2 text-sm text-muted-foreground/70">Showing top {MAX_PARETO_BARS} by revenue</p>
            )}
            <ResponsiveContainer width="100%" height={260}>
                <ComposedChart data={chartData} margin={{ top: 10, right: 40, bottom: 20, left: 0 }}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="var(--border)" />
                    <XAxis dataKey="rank" tickLine={false} axisLine={false} tick={{ fontSize: 11, fill: 'var(--muted-foreground)' }} />
                    <YAxis yAxisId="left" tickFormatter={(v) => formatCurrency(v, currency, true)} tickLine={false} axisLine={false} tick={{ fontSize: 10, fill: 'var(--muted-foreground)' }} width={55} />
                    <YAxis yAxisId="right" orientation="right" domain={[0, 100]} tickFormatter={(v) => `${v}%`} tickLine={false} axisLine={false} tick={{ fontSize: 10, fill: 'var(--muted-foreground)' }} width={35} />
                    <Tooltip content={<ParetoCustomTooltip currency={currency} />} />
                    <Bar yAxisId="left" dataKey="revenue" radius={[2, 2, 0, 0]} onClick={(d) => onBarClick((d as unknown as ParetoChartItem).rank)} cursor="pointer">
                        {chartData.map((d, i) => (
                            <Cell key={i} fill={d.isCore ? 'var(--chart-1)' : 'var(--muted-foreground)'} opacity={selectedRank !== null && selectedRank !== d.rank ? 0.5 : 1} />
                        ))}
                    </Bar>
                    <Line yAxisId="right" type="monotone" dataKey="cumPct" stroke="var(--chart-3)" strokeWidth={2} dot={false} />
                    {threshold80Rank !== null && (
                        <ReferenceLine yAxisId="right" y={80} stroke="var(--destructive)" strokeDasharray="4 4" strokeWidth={1.5} label={{ value: '80%', position: 'insideTopRight', fontSize: 10, fill: 'var(--destructive)' }} />
                    )}
                </ComposedChart>
            </ResponsiveContainer>

            <div className="mt-2 flex items-center gap-4 text-sm text-muted-foreground">
                <span className="flex items-center gap-1">
                    <span className="inline-block h-2.5 w-3 rounded-sm bg-[var(--chart-1)]" />
                    Core (top 80% revenue)
                </span>
                <span className="flex items-center gap-1">
                    <span className="inline-block h-2.5 w-3 rounded-sm bg-muted-foreground" />
                    Long tail
                </span>
                <span className="flex items-center gap-1">
                    <span className="inline-block h-0.5 w-4 bg-[var(--chart-3)]" />
                    Cumulative %
                </span>
                {threshold80Rank !== null && (
                    <span className="flex items-center gap-1">
                        <span className="inline-block h-0.5 w-4 border-t-2 border-dashed border-destructive" />
                        80% threshold
                    </span>
                )}
                {selectedRank !== null && (
                    <span className="flex items-center gap-1">
                        <span className="inline-block h-2.5 w-3 rounded-sm bg-[var(--chart-1)]" />
                        Selected (others dimmed)
                    </span>
                )}
            </div>
        </div>
    );
}

// ─── View CVR cell ────────────────────────────────────────────────────────────

function ViewCvrCell({ views, cvr }: { views: number | null; cvr: number | null }) {
    if (views === null) return <span className="text-muted-foreground/50">—</span>;
    if (cvr === null) return <span className="text-muted-foreground/70">{formatNumber(views)}</span>;
    const pct = cvr * 100;
    const colorClass =
        pct >= 5  ? 'text-green-700' :
        pct >= 2  ? 'text-amber-600' :
        'text-red-600';
    return (
        <div className="text-right leading-tight">
            <div className="text-sm text-muted-foreground tabular-nums">{formatNumber(views)}</div>
            <div className={cn('text-xs font-medium tabular-nums', colorClass)}>{pct.toFixed(1)}%</div>
        </div>
    );
}

// ─── Traffic vs Conversion quadrant ──────────────────────────────────────────

function TrafficVsConversionChart({
    products,
    currency,
    onProductClick,
}: {
    products: ProductRow[];
    currency: string;
    onProductClick?: (p: ProductRow) => void;
}) {
    const data: QuadrantPoint[] = products
        .filter((p) => p.views !== null && p.views > 0)
        .map((p) => ({
            id: p.external_id,
            label: p.name,
            x: p.views!,
            y: p.view_cvr !== null ? p.view_cvr * 100 : null,
            size: p.revenue,
        }));

    if (data.length === 0) return null;

    const config: QuadrantFieldConfig = {
        xLabel: 'Product page views',
        yLabel: 'View → purchase rate (%)',
        sizeLabel: 'Revenue',
        xFormatter: (v) => formatNumber(v),
        yFormatter: (v) => v == null ? '—' : `${v.toFixed(1)}%`,
        sizeFormatter: (v) => formatCurrency(v ?? 0, currency, true),
        topRightLabel: 'Winners',
        topLeftLabel: 'Hidden gems',
        bottomRightLabel: 'Price / friction',
        bottomLeftLabel: 'Underperformers',
    };

    const handleDotClick = onProductClick
        ? (id: string | number) => {
            const product = products.find((p) => p.external_id === id);
            if (product) onProductClick(product);
        }
        : undefined;

    return (
        <div className="rounded-xl border border-border bg-card p-5">
            <div className="mb-1 flex items-center gap-1.5">
                <h2 className="text-sm font-medium text-foreground">Traffic vs Conversion</h2>
                <InfoTooltip content="Each bubble = one product. X axis: product page views from GA4. Y axis: share of viewers who purchased. Bubble size = revenue. Winners have high traffic and high conversion. High views + low CVR often signals a pricing or friction issue." />
            </div>
            <p className="mb-4 text-sm text-muted-foreground/70">
                Views from GA4 enhanced ecommerce · Purchases from store
            </p>
            <QuadrantChart data={data} config={config} xLogScale onDotClick={handleDotClick} />
        </div>
    );
}

// ─── Inline COGS cell ─────────────────────────────────────────────────────────
// Thin adapter: InlineEditableCell handles all edit/save/cancel UX.

function InlineCogs({
    productId,
    value,
    currency,
    workspaceSlug,
}: {
    productId: number;
    value: number | null;
    currency: string;
    workspaceSlug: string | undefined;
}) {
    function handleSave(newValue: string): Promise<void> {
        const num = parseFloat(newValue);
        if (isNaN(num) || num < 0) return Promise.reject(new Error('invalid'));
        return new Promise<void>((resolve, reject) => {
            router.patch(
                wurl(workspaceSlug, `/products/${productId}/cogs`),
                { cogs: num },
                {
                    preserveScroll: true,
                    onSuccess: () => resolve(),
                    onError: () => reject(new Error('save failed')),
                },
            );
        });
    }

    return (
        <InlineEditableCell
            value={value != null ? String(value) : ''}
            onSave={handleSave}
            type="number"
            prefix={value == null ? '+' : currency}
        />
    );
}

// ─── Product detail drawer ────────────────────────────────────────────────────

function ProductDetailDrawer({
    product,
    currency,
    workspaceSlug,
    onClose,
}: {
    product: ProductRow;
    currency: string;
    workspaceSlug: string | undefined;
    onClose: () => void;
}) {
    const lineData = product.velocity_sparkline.map((v, i) => ({
        date: `Day ${i + 1}`,
        value: v,
    }));

    const buyersHref = workspaceSlug && product.sku
        ? `/${workspaceSlug}/customers?bought=${encodeURIComponent(product.sku)}`
        : null;

    return (
        <div className="fixed inset-0 z-40 flex">
            {/* Backdrop */}
            <div className="flex-1 bg-black/30" onClick={onClose} />

            {/* Panel */}
            <div className="flex w-[480px] flex-col bg-card shadow-xl overflow-y-auto">
                {/* Header */}
                <div className="flex items-start gap-3 border-b border-border p-5">
                    {product.thumbnail_url ? (
                        <img src={product.thumbnail_url} alt="" className="h-14 w-14 rounded-lg object-cover flex-shrink-0" />
                    ) : (
                        <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-muted">
                            <Package className="h-7 w-7 text-muted-foreground/50" />
                        </div>
                    )}
                    <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between gap-2">
                            <h2 className="text-base font-semibold text-foreground truncate">{product.name}</h2>
                            <button onClick={onClose} aria-label="Close" className="shrink-0 rounded p-1 text-muted-foreground/70 hover:text-muted-foreground">
                                <X className="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>
                        {product.sku && <p className="mt-0.5 font-mono text-xs text-muted-foreground/70">{product.sku}</p>}
                        <div className="mt-1 flex items-center gap-2">
                            <ProductLifecycleChip label={product.label} />
                            {buyersHref && (
                                <Link
                                    href={buyersHref}
                                    className="text-xs text-primary hover:underline"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    View buyers →
                                </Link>
                            )}
                        </div>
                    </div>
                </div>

                <div className="flex-1 space-y-6 p-5">
                    {/* KPI grid */}
                    <div className="grid grid-cols-2 gap-3">
                        <MetricCard label="Revenue" value={formatCurrency(product.revenue, currency, true)} source="store" />
                        <MetricCard label="Orders" value={formatNumber(product.orders)} source="store" />
                        <MetricCard label="Units" value={formatNumber(product.units)} source="store" />
                        <MetricCard
                            label="Margin %"
                            value={product.margin_pct != null ? `${product.margin_pct.toFixed(1)}%` : 'N/A'}
                            source="real"
                        />
                    </div>

                    {/* Repeat rate */}
                    {product.repeat_rate != null && (
                        <div className="rounded-xl border border-border bg-card p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium text-muted-foreground">Repeat purchase rate</span>
                                <span className={cn(
                                    'text-sm font-semibold tabular-nums',
                                    product.repeat_rate >= 0.30 ? 'text-green-700' :
                                    product.repeat_rate >= 0.10 ? 'text-amber-600' :
                                    'text-muted-foreground',
                                )}>
                                    {(product.repeat_rate * 100).toFixed(1)}%
                                </span>
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground/70">
                                Share of buyers who placed another order containing this SKU in the period.
                            </p>
                        </div>
                    )}

                    {/* COGS (editable) */}
                    <div className="rounded-xl border border-border bg-card p-4">
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-medium text-muted-foreground">COGS</span>
                            <InlineCogs
                                productId={product.id}
                                value={product.cogs}
                                currency={currency}
                                workspaceSlug={workspaceSlug}
                            />
                        </div>
                    </div>

                    {/* Velocity over time */}
                    {lineData.length >= 2 && (
                        <div>
                            <h3 className="mb-3 text-sm font-medium text-foreground">Units/day over time</h3>
                            <LineChart
                                data={lineData}
                                granularity="daily"
                                valueType="number"
                                seriesLabel="Units/day"
                                className="w-full"
                            />
                        </div>
                    )}

                    {/* Top campaigns from GA4 */}
                    {product.top_campaigns.length > 0 && (
                        <div className="rounded-xl border border-border bg-card p-4">
                            <div className="mb-2 flex items-center gap-1">
                                <span className="text-sm font-medium text-muted-foreground">Top campaigns driving purchases</span>
                                <span className="rounded-full bg-sky-100 px-1.5 py-0.5 text-xs font-medium text-sky-700">GA4</span>
                            </div>
                            <ul className="space-y-1">
                                {product.top_campaigns.map((c) => (
                                    <li key={c} className="text-sm text-foreground truncate">{c}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {/* LTV correlation note */}
                    <div className="rounded-lg bg-violet-50 border border-violet-100 p-4">
                        <p className="text-xs text-violet-700">
                            <span className="font-semibold">LTV signal:</span> Customer cohort LTV data for this SKU is computed from repeat orders. Visit the Customers page for full LTV curves.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ─── Product Journey table ────────────────────────────────────────────────────

type JourneyRow = ProductJourneyRow & { id: string };

function ProductJourneySection({ rows }: { rows: ProductJourneyRow[] }) {
    const totalFirst  = rows.reduce((s, r) => s + r.as_first,  0);
    const totalSecond = rows.reduce((s, r) => s + r.as_second, 0);
    const totalThird  = rows.reduce((s, r) => s + r.as_third,  0);

    const pct = (n: number, total: number) =>
        total > 0 ? ` (${((n / total) * 100).toFixed(1)}%)` : '';

    const data: JourneyRow[] = rows.map((r) => ({ ...r, id: r.product_external_id }));

    const columns: Column<JourneyRow>[] = [
        {
            key: 'product_name',
            header: 'Product',
            render: (_, row) => (
                <span className="truncate block max-w-[280px] font-medium text-foreground">{row.product_name}</span>
            ),
        },
        {
            key: 'as_first',
            header: '1st Purchase',
            sortable: true,
            render: (_, row) => row.as_first > 0 ? (
                <span className="font-semibold text-indigo-700 tabular-nums">
                    {formatNumber(row.as_first)}
                    <span className="ml-1 text-xs font-normal text-muted-foreground/70">{pct(row.as_first, totalFirst)}</span>
                </span>
            ) : <span className="text-muted-foreground/50">—</span>,
        },
        {
            key: 'as_second',
            header: '2nd Purchase',
            sortable: true,
            render: (_, row) => row.as_second > 0 ? (
                <span className="tabular-nums">
                    {formatNumber(row.as_second)}
                    <span className="ml-1 text-xs text-muted-foreground/70">{pct(row.as_second, totalSecond)}</span>
                </span>
            ) : <span className="text-muted-foreground/50">—</span>,
        },
        {
            key: 'as_third',
            header: '3rd Purchase',
            sortable: true,
            render: (_, row) => row.as_third > 0 ? (
                <span className="tabular-nums">
                    {formatNumber(row.as_third)}
                    <span className="ml-1 text-xs text-muted-foreground/70">{pct(row.as_third, totalThird)}</span>
                </span>
            ) : <span className="text-muted-foreground/50">—</span>,
        },
    ];

    return (
        <div className="rounded-xl border border-border bg-card p-5">
            <div className="mb-1 flex items-center gap-1.5">
                <GitBranch className="h-4 w-4 text-muted-foreground/70" />
                <h2 className="text-sm font-medium text-foreground">Product Journey</h2>
                <InfoTooltip content="Gateway products — which items start the customer relationship? Counts are distinct customers who bought this product as their 1st, 2nd, or 3rd purchase ever." />
            </div>
            <p className="mb-4 text-sm text-muted-foreground/70">
                Gateway products — which items start the customer relationship?
            </p>
            <DataTable
                columns={columns}
                data={data}
                defaultSort={{ key: 'as_first', dir: 'desc' }}
                emptyMessage="No journey data yet"
                emptyDescription="Journey analysis requires orders with linked customers. Data appears once customers have made multiple purchases."
            />
        </div>
    );
}

// ─── Market Basket table ──────────────────────────────────────────────────────

type BasketRow = MarketBasketRow & { id: string };

function MarketBasketSection({
    rows,
    products,
    onProductClick,
}: {
    rows: MarketBasketRow[];
    products: ProductRow[];
    onProductClick: (p: ProductRow) => void;
}) {
    const productByExtId = useMemo(() => {
        const map = new Map<string, ProductRow>();
        for (const p of products) map.set(p.external_id, p);
        return map;
    }, [products]);

    const data: BasketRow[] = rows.map((r, i) => ({
        ...r,
        id: `${r.product_a_id}-${r.product_b_id}-${i}`,
    }));

    const makeProductCell = (extId: string, name: string) => {
        const product = productByExtId.get(extId);
        if (product) {
            return (
                <button
                    type="button"
                    onClick={(e) => { e.stopPropagation(); onProductClick(product); }}
                    className="truncate block max-w-[220px] font-medium text-primary hover:underline text-left"
                >
                    {name}
                </button>
            );
        }
        return <span className="truncate block max-w-[220px] font-medium text-foreground">{name}</span>;
    };

    const columns: Column<BasketRow>[] = [
        {
            key: 'product_a',
            header: 'Product A',
            render: (_, row) => makeProductCell(row.product_a_id, row.product_a),
        },
        {
            key: 'product_b',
            header: 'Product B',
            render: (_, row) => makeProductCell(row.product_b_id, row.product_b),
        },
        {
            key: 'co_purchase_count',
            header: 'Times Together',
            sortable: true,
            render: (_, row) => (
                <span className="font-semibold text-foreground tabular-nums">{formatNumber(row.co_purchase_count)}</span>
            ),
        },
        {
            key: 'lift_pct',
            header: '% of Orders',
            sortable: true,
            render: (_, row) => (
                <span className={cn(
                    'font-medium tabular-nums',
                    row.lift_pct >= 10 ? 'text-green-700' :
                    row.lift_pct >= 5  ? 'text-amber-600' :
                    'text-muted-foreground',
                )}>
                    {row.lift_pct.toFixed(1)}%
                </span>
            ),
        },
    ];

    return (
        <div className="rounded-xl border border-border bg-card p-5">
            <div className="mb-1 flex items-center gap-1.5">
                <ShoppingBag className="h-4 w-4 text-muted-foreground/70" />
                <h2 className="text-sm font-medium text-foreground">Frequently Bought Together</h2>
                <InfoTooltip content="Product pairs that appear in the same order. Minimum 3 co-purchases to appear. Click a product name to open its detail drawer." />
            </div>
            <p className="mb-4 text-sm text-muted-foreground/70">
                Frequently bought together — bundle and upsell opportunities · Click a product name to drill in
            </p>
            <DataTable
                columns={columns}
                data={data}
                defaultSort={{ key: 'co_purchase_count', dir: 'desc' }}
                emptyMessage="No basket data yet"
                emptyDescription="Basket analysis requires at least 3 orders containing the same two products. Data appears as your order history grows."
            />
        </div>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function ProductsIndex({
    products,
    metrics,
    pareto_data,
    filters,
    cogs_configured_count,
    total_skus,
    product_journey,
    market_basket,
    initial_search,
}: Props) {
    const { workspace } = usePage<PageProps>().props;
    const currency = workspace?.reporting_currency ?? 'EUR';
    const w = (path: string) => wurl(workspace?.slug, path);

    const [activeTab, setActiveTab] = useState<'products' | 'journeys' | 'baskets'>('products');
    const [selectedProduct, setSelectedProduct] = useState<ProductRow | null>(null);
    const [paretoFilter, setParetoFilter] = useState<number | null>(null);
    const [activeSort, setActiveSort] = useState<SortKey>(filters.sort ?? 'revenue');
    const [labelFilter, setLabelFilter] = useState<ProductRow['label'] | 'all'>('all');
    // Conjura-style saved-view chips: one-click segment presets
    const [savedView, setSavedView] = useState<'all' | 'unprofitable' | 'slow_movers' | 'selling_out'>('all');
    const [searchQuery, setSearchQuery] = useState(initial_search ?? '');

    const sortedProducts = useMemo(() => {
        const arr = [...products];
        const sorters: Record<SortKey, (a: ProductRow, b: ProductRow) => number> = {
            revenue:  (a, b) => (b.revenue ?? 0) - (a.revenue ?? 0),
            orders:   (a, b) => (b.orders ?? 0) - (a.orders ?? 0),
            units:    (a, b) => (b.units ?? 0) - (a.units ?? 0),
            aov:      (a, b) => (b.aov ?? 0) - (a.aov ?? 0),
            margin:   (a, b) => (b.margin_pct ?? -Infinity) - (a.margin_pct ?? -Infinity),
            velocity:    (a, b) => (b.velocity ?? 0) - (a.velocity ?? 0),
            name:        (a, b) => a.name.localeCompare(b.name),
            repeat_rate: (a, b) => (b.repeat_rate ?? -Infinity) - (a.repeat_rate ?? -Infinity),
        };
        return arr.sort(sorters[activeSort] ?? sorters.revenue);
    }, [products, activeSort]);

    const displayedProducts = useMemo(() => {
        let list = sortedProducts;
        if (paretoFilter != null) {
            list = list.filter((p) => {
                const rank = pareto_data.findIndex((d) => d.name === p.name) + 1;
                return rank === paretoFilter;
            });
        }
        if (labelFilter !== 'all') {
            list = list.filter((p) => p.label === labelFilter);
        }
        // Conjura-style saved-view presets
        if (savedView === 'unprofitable') {
            list = list.filter((p) => p.margin_pct != null && p.margin_pct < 0);
        } else if (savedView === 'slow_movers') {
            list = list.filter((p) => p.velocity != null && p.velocity < 0.5);
        } else if (savedView === 'selling_out') {
            list = list.filter((p) => p.days_of_cover != null && p.days_of_cover <= 14);
        }
        // Text search: match on name or SKU
        if (searchQuery.trim()) {
            const q = searchQuery.trim().toLowerCase();
            list = list.filter((p) =>
                p.name.toLowerCase().includes(q) ||
                (p.sku?.toLowerCase().includes(q) ?? false),
            );
        }
        return list;
    }, [paretoFilter, sortedProducts, pareto_data, labelFilter, savedView, searchQuery]);

    const labelCounts = useMemo(() => {
        const counts: Record<string, number> = { rockstar: 0, hot: 0, cold: 0, at_risk: 0 };
        for (const p of products) {
            if (p.label) counts[p.label] = (counts[p.label] ?? 0) + 1;
        }
        return counts;
    }, [products]);

    const cogsConfiguredPct = useMemo(() =>
        total_skus > 0 ? Math.round((cogs_configured_count / total_skus) * 100) : 0,
    [cogs_configured_count, total_skus]);

    const filterLabel = useMemo(() => {
        const parts = [];
        if (filters.from && filters.to) parts.push(`${filters.from} → ${filters.to}`);
        if (filters.country) parts.push(`Country = ${filters.country}`);
        if (filters.channel) parts.push(`Channel = ${filters.channel}`);
        return parts.join(' · ') || 'Last 28d';
    }, [filters.from, filters.to, filters.country, filters.channel]);

    const setSort = setActiveSort;

    // Stable reference so ProductTableRow (memo'd) doesn't re-render when parent state changes.
    const handleSelectProduct = useCallback((p: ProductRow) => setSelectedProduct(p), []);

    function setDateRange(days: number) {
        const today = new Date();
        const from = new Date(today);
        from.setDate(today.getDate() - (days - 1));
        const fmt = (d: Date) => d.toISOString().slice(0, 10);
        router.get(window.location.pathname, {
            from: fmt(from),
            to: fmt(today),
            country: filters.country ?? undefined,
            channel: filters.channel ?? undefined,
        }, { preserveState: true });
    }

    const [stockAlertDismissed, setStockAlertDismissed] = useState(false);

    function setStockAlert(days: 7 | 30 | null) {
        const query: Record<string, string | undefined> = {
            from: filters.from,
            to: filters.to,
            country: filters.country ?? undefined,
            channel: filters.channel ?? undefined,
            stock_alert: days != null ? String(days) : undefined,
        };
        // Remove undefined keys so they don't appear in the URL.
        const clean = Object.fromEntries(
            Object.entries(query).filter(([, v]) => v !== undefined),
        ) as Record<string, string>;
        setStockAlertDismissed(false);
        router.get(window.location.pathname, clean, { preserveState: true });
    }

    return (
        <AppLayout>
            <Head title="Products" />

            <div className="space-y-6">

            <PageHeader
                title="Products"
                subtitle="SKU-level revenue, margin, and inventory across your catalog."
            />

            {cogs_configured_count === 0 && total_skus > 0 && (
                <AlertBanner
                    severity="info"
                    message={`COGS not configured for any of your ${total_skus} SKUs. Add cost-of-goods to unlock Margin % and Profit Mode.`}
                    action={{ label: 'Add COGS', href: w('/settings/costs') }}
                />
            )}

            {filters.stock_alert != null && !stockAlertDismissed && (
                <div className="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-800">
                    <span className="flex-1">
                        Showing products running out in &le;&nbsp;{filters.stock_alert} days
                    </span>
                    <button
                        type="button"
                        onClick={() => setStockAlertDismissed(true)}
                        className="rounded p-0.5 text-amber-600 hover:text-amber-900"
                        aria-label="Dismiss"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>
            )}
                {/* KPI grid */}
                <KpiGrid cols={4}>
                    <MetricCard
                        label="Products Sold"
                        value={formatNumber(metrics.products_sold)}
                        source="store"
                        tooltip="Count of distinct SKUs with at least one unit sold in the selected period."
                    />
                    <MetricCard
                        label="Top-10 Revenue Concentration"
                        value={metrics.top10_concentration != null ? `${metrics.top10_concentration.toFixed(1)}%` : 'N/A'}
                        source="store"
                        tooltip="Share of Net Sales captured by the top 10 SKUs by revenue in this period."
                    />
                    <MetricCard
                        label="Median Gross Margin"
                        value={metrics.median_gross_margin != null ? `${metrics.median_gross_margin.toFixed(1)}%` : 'N/A'}
                        source="real"
                        subtext={cogs_configured_count < 30 ? `Based on ${cogs_configured_count} SKUs with COGS` : undefined}
                        tooltip="(Revenue − COGS) ÷ Revenue × 100. Median across SKUs with COGS configured."
                    />
                    <MetricCard
                        label="Avg. Sales Velocity"
                        value={metrics.velocity_mean != null ? `${metrics.velocity_mean.toFixed(2)}/day` : 'N/A'}
                        source="store"
                        subtext={
                            metrics.velocity_median != null
                                ? `Median ${metrics.velocity_median.toFixed(2)} · Mode ${metrics.velocity_mode?.toFixed(2) ?? '—'}`
                                : undefined
                        }
                        tooltip="Average daily sales over the last 28 days across all SKUs. Used to compute days-of-cover for stockout alerts."
                    />
                </KpiGrid>

                {/* Tab bar */}
                <div
                    role="tablist"
                    aria-label="Product views"
                    className="flex border-b border-border"
                >
                    {([
                        { id: 'products' as const,  label: 'Products',          icon: LayoutList },
                        { id: 'journeys' as const,  label: 'Customer Journeys', icon: GitBranch },
                        { id: 'baskets'  as const,  label: 'Bought Together',   icon: ShoppingBag },
                    ]).map(({ id, label, icon: Icon }) => (
                        <button
                            key={id}
                            role="tab"
                            id={`tab-${id}`}
                            aria-selected={activeTab === id}
                            aria-controls={`tabpanel-${id}`}
                            type="button"
                            onClick={() => setActiveTab(id)}
                            className={cn(
                                'inline-flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                                activeTab === id
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border',
                            )}
                        >
                            <Icon className="h-3.5 w-3.5" />
                            {label}
                        </button>
                    ))}
                </div>

                {/* Tab: Products */}
                <div
                    role="tabpanel"
                    id="tabpanel-products"
                    aria-labelledby="tab-products"
                    hidden={activeTab !== 'products'}
                    className="space-y-6"
                >
                    {/* ── Profit landscape — strategic overview (unfiltered) ── */}
                    <div className="grid grid-cols-1 gap-6 xl:grid-cols-12">
                        {/* Pareto chart */}
                        <div className="rounded-xl border border-border bg-card p-5 xl:col-span-7">
                            <div className="mb-1 flex items-center justify-between">
                                <div className="flex items-center gap-1.5">
                                    <h2 className="text-sm font-semibold text-foreground">Profit landscape</h2>
                                    <InfoTooltip content="The Pareto principle states that roughly 80% of outcomes come from 20% of causes. Here, the line marks the products that collectively account for 80% of total revenue — focus your inventory and marketing efforts on these." />
                                </div>
                                {paretoFilter != null && (
                                    <button
                                        type="button"
                                        onClick={() => setParetoFilter(null)}
                                        className="text-xs text-muted-foreground/70 hover:text-muted-foreground flex items-center gap-1"
                                    >
                                        <X className="h-3 w-3" /> Clear filter
                                    </button>
                                )}
                            </div>
                            <p className="mt-0.5 text-sm text-muted-foreground/70">
                                All products plotted by revenue rank. Click a bar to highlight that product in the list below.
                            </p>
                            <ParetoChart
                                data={pareto_data}
                                onBarClick={(rank) => setParetoFilter(paretoFilter === rank ? null : rank)}
                                selectedRank={paretoFilter}
                                currency={currency}
                            />
                        </div>

                        {/* Traffic vs Conversion quadrant */}
                        <div className="xl:col-span-5">
                            <TrafficVsConversionChart
                                products={products}
                                currency={currency}
                                onProductClick={handleSelectProduct}
                            />
                        </div>
                    </div>

                    {/* ── Products list ─────────────────────────────────────── */}
                    <div>
                        <h2 className="mb-3 text-sm font-semibold text-foreground">Products list</h2>

                        {/* Saved-view chips + search — affect only the table below */}
                        <div className="mb-3 flex flex-wrap items-center gap-2">
                            {([
                                { id: 'all'         as const, label: 'All products',     className: '' },
                                { id: 'unprofitable'as const, label: 'Unprofitable',     className: 'border-red-300 bg-red-50 text-red-700' },
                                { id: 'slow_movers' as const, label: 'Slow movers',      className: 'border-blue-300 bg-blue-50 text-blue-700' },
                                { id: 'selling_out' as const, label: 'Selling out',      className: 'border-amber-300 bg-amber-50 text-amber-700' },
                            ]).map(({ id, label, className: activeClass }) => (
                                <button
                                    key={id}
                                    type="button"
                                    onClick={() => setSavedView(id)}
                                    className={cn(
                                        'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                        savedView === id
                                            ? activeClass || 'border-primary bg-primary/10 text-primary'
                                            : 'border-border bg-card text-muted-foreground hover:border-input',
                                    )}
                                >
                                    {label}
                                </button>
                            ))}

                            {/* Search */}
                            <div className="relative ml-2">
                                <Search className="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground/60 pointer-events-none" />
                                <input
                                    type="search"
                                    placeholder="Search name or SKU…"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="h-8 w-48 rounded-lg border border-border bg-card pl-8 pr-3 text-sm text-foreground placeholder:text-muted-foreground/60 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary/30"
                                />
                            </div>
                        </div>

                        {/* Filter sentence + date range + lifecycle + stock alert */}
                        <div className="mb-4 flex flex-wrap items-center gap-2">
                            <span className="text-sm text-muted-foreground">Showing products from</span>
                            <span className="rounded-full border border-border bg-muted/50 px-3 py-0.5 text-sm font-medium text-foreground">
                                {filterLabel}
                            </span>

                            {/* Date range preset buttons */}
                            <div className="flex items-center gap-1.5">
                                {([7, 14, 28, 90] as const).map((days) => {
                                    const today = new Date().toISOString().slice(0, 10);
                                    const expectedFrom = (() => {
                                        const d = new Date(); d.setDate(d.getDate() - (days - 1)); return d.toISOString().slice(0, 10);
                                    })();
                                    const active = filters.from === expectedFrom && (filters.to === today || !filters.to);
                                    return (
                                        <button
                                            key={days}
                                            type="button"
                                            onClick={() => setDateRange(days)}
                                            className={cn(
                                                'rounded-full border px-3 py-0.5 text-xs font-medium transition-colors',
                                                active
                                                    ? 'border-primary bg-primary/10 text-primary'
                                                    : 'border-border bg-card text-muted-foreground hover:border-input',
                                            )}
                                        >
                                            {days}d
                                        </button>
                                    );
                                })}
                            </div>

                            {/* Lifecycle label filter */}
                            <div className="flex items-center gap-1.5">
                                <span className="text-sm text-muted-foreground">Lifecycle:</span>
                                {([
                                    { value: 'all',      label: 'All' },
                                    { value: 'rockstar', label: 'Rockstar' },
                                    { value: 'hot',      label: 'Hot' },
                                    { value: 'cold',     label: 'Cold' },
                                    { value: 'at_risk',  label: 'At Risk' },
                                ] as const).map(({ value, label }) => {
                                    const active = labelFilter === value;
                                    const count = value !== 'all' ? labelCounts[value] ?? 0 : null;
                                    const activeClass =
                                        value === 'rockstar' ? 'border-yellow-400 bg-yellow-100 text-yellow-800' :
                                        value === 'hot'      ? 'border-green-400 bg-green-100 text-green-800' :
                                        value === 'cold'     ? 'border-blue-400 bg-blue-100 text-blue-800' :
                                        value === 'at_risk'  ? 'border-rose-400 bg-rose-100 text-rose-800' :
                                        'border-primary bg-primary/10 text-primary';
                                    return (
                                        <button
                                            key={value}
                                            type="button"
                                            onClick={() => setLabelFilter(value)}
                                            className={cn(
                                                'rounded-full border px-3 py-0.5 text-xs font-medium transition-colors',
                                                active
                                                    ? activeClass
                                                    : 'border-border bg-card text-muted-foreground hover:border-input',
                                            )}
                                        >
                                            {label}{count !== null ? ` (${count})` : ''}
                                        </button>
                                    );
                                })}
                            </div>

                            {/* Stock Alert button group */}
                            <div className="ml-auto flex items-center gap-1.5">
                                <span className="text-sm text-muted-foreground">Running out in:</span>
                                <InfoTooltip content="Filter to products running out of stock within 7 or 30 days at current sales velocity. Based on 28-day average daily sales." />
                                {([null, 7, 30] as const).map((opt) => {
                                    const label = opt === null ? 'All' : `≤ ${opt}d`;
                                    const active = (filters.stock_alert ?? null) === opt;
                                    return (
                                        <button
                                            key={String(opt)}
                                            type="button"
                                            onClick={() => setStockAlert(opt)}
                                            className={cn(
                                                'rounded-full border px-3 py-0.5 text-xs font-medium transition-colors',
                                                active
                                                    ? 'border-amber-400 bg-amber-100 text-amber-800'
                                                    : 'border-border bg-card text-muted-foreground hover:border-input hover:bg-muted/50',
                                            )}
                                        >
                                            {label}
                                        </button>
                                    );
                                })}
                            </div>

                            {cogs_configured_count > 0 && (
                                <span className="text-sm text-muted-foreground/70">
                                    COGS configured: {cogs_configured_count}/{total_skus} SKUs ({cogsConfiguredPct}%)
                                </span>
                            )}
                        </div>

                    {/* Products data table */}
                    <div className="rounded-xl border border-border bg-card overflow-hidden">
                        <div className="border-b border-border px-5 py-4">
                            <h2 className="text-sm font-medium text-foreground">Products</h2>
                            <p className="mt-0.5 text-sm text-muted-foreground/70">
                                {displayedProducts.length} product{displayedProducts.length !== 1 ? 's' : ''}
                                {paretoFilter != null ? ' (filtered by Pareto selection)' : ''}
                                {' · Click a row to see details'}
                            </p>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-border text-sm">
                                <thead className="bg-muted/50 border-b border-border">
                                    <tr className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                        <th scope="col" className="px-4 py-2.5 text-left">Product</th>
                                        <th scope="col" className="px-3 py-2.5 text-left">
                                            <span className="inline-flex items-center gap-1">
                                                Label
                                                <InfoTooltip content="Rockstar: top 10% by revenue. Hot: rising trend. Cold: below 50% of last-28d avg. At-Risk: stockout imminent." />
                                            </span>
                                        </th>
                                        <SortableHeader label="Revenue" sortKey="revenue" activeSort={activeSort} onSort={setSort} />
                                        <SortableHeader label="Units" sortKey="units" activeSort={activeSort} onSort={setSort} />
                                        <SortableHeader label="Margin %" sortKey="margin" activeSort={activeSort} onSort={setSort} />
                                        <th scope="col" className="px-3 py-2.5 text-right">
                                            <span className="inline-flex items-center justify-end gap-1">
                                                Profit
                                                <InfoTooltip content="Gross profit in currency: Revenue × Margin %. Requires COGS to be configured. Negative = selling at a loss." />
                                            </span>
                                        </th>
                                        <SortableHeader label="Repeat Rate" sortKey="repeat_rate" activeSort={activeSort} onSort={setSort}>
                                            <span className="inline-flex items-center gap-1 justify-end w-full">
                                                Repeat Rate
                                                <InfoTooltip content="Share of customers who ordered this product more than once in the period. Needs 5+ customers to show. A strong repeat rate is the best per-SKU LTV signal." />
                                            </span>
                                        </SortableHeader>
                                        <th scope="col" className="px-3 py-2.5 text-right">
                                            <span className="inline-flex items-center justify-end gap-1">
                                                Stock
                                                <InfoTooltip content="Days of stock remaining at the current 28-day sales velocity. Red ≤ 7d, amber ≤ 30d. '—' means velocity is unknown." />
                                            </span>
                                        </th>
                                        <SortableHeader label="Velocity" sortKey="velocity" activeSort={activeSort} onSort={setSort} align="left">
                                            <span className="inline-flex items-center gap-1">
                                                Velocity
                                                <InfoTooltip content="Average daily sales over the last 28 days. Used to compute days-of-cover for stockout alerts." />
                                            </span>
                                        </SortableHeader>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border bg-card">
                                    {displayedProducts.length === 0 ? (
                                        <tr>
                                            <td colSpan={9} className="px-0 py-0">
                                                <EmptyState
                                                    title={products.length === 0 ? 'No products yet — connect your store' : 'No products match your filters'}
                                                    description={products.length === 0 ? 'Connect your store to start seeing product performance, margins, and velocity.' : 'Try adjusting the date range or active filters.'}
                                                    action={products.length === 0 ? (() => { const step = getNextOnboardingStep(workspace); return { label: step.label, href: step.href }; })() : undefined}
                                                />
                                            </td>
                                        </tr>
                                    ) : displayedProducts.map((p) => (
                                        <ProductTableRow
                                            key={p.external_id}
                                            p={p}
                                            currency={currency}
                                            onSelect={handleSelectProduct}
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Inventory forecast footer link */}
                    <div className="flex items-center justify-between rounded-xl border border-border bg-muted/50 px-5 py-3">
                        <div>
                            <p className="text-sm font-medium text-foreground">Inventory Forecast</p>
                            <p className="text-sm text-muted-foreground/70">
                                Velocity-based stock depletion forecast and reorder alerts
                            </p>
                        </div>
                        <Link
                            href={w('/inventory')}
                            className="shrink-0 rounded-lg border border-border bg-card px-3 py-1.5 text-sm font-medium text-foreground shadow-sm hover:bg-muted/50 transition-colors"
                        >
                            View Inventory →
                        </Link>
                    </div>
                    </div>{/* end Products list */}
                </div>{/* end tabpanel-products */}

                {/* Tab: Customer Journeys */}
                <div
                    role="tabpanel"
                    id="tabpanel-journeys"
                    aria-labelledby="tab-journeys"
                    hidden={activeTab !== 'journeys'}
                >
                    <ProductJourneySection rows={product_journey} />
                </div>

                {/* Tab: Bought Together */}
                <div
                    role="tabpanel"
                    id="tabpanel-baskets"
                    aria-labelledby="tab-baskets"
                    hidden={activeTab !== 'baskets'}
                >
                    <MarketBasketSection
                        rows={market_basket}
                        products={products}
                        onProductClick={handleSelectProduct}
                    />
                </div>
            </div>

            {/* Product detail drawer */}
            {selectedProduct && (
                <ProductDetailDrawer
                    product={selectedProduct}
                    currency={currency}
                    workspaceSlug={workspace?.slug}
                    onClose={() => setSelectedProduct(null)}
                />
            )}
        </AppLayout>
    );
}
