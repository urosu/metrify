/**
 * Inventory/Index — stock health, sales prediction, and reorder signals per SKU.
 *
 * Competitor patterns absorbed:
 *   - Cogsy: blended 60/40 last-period / LY velocity forecast, variant accordion,
 *     "estimated run-out date" column, confidence chip per row.
 *   - Stocky (Shopify partner): "low confidence" chip when history < 30d,
 *     5 % growth nudge in predicted demand.
 *   - Shopify Native: "Days of inventory remaining" column (Advanced+ report),
 *     stock status pills (in stock / low / out of stock).
 *   - Glew: "Predicted demand" KPI in the header strip; Inventory Analytics module layout.
 *   - Bloom Analytics: "Inventory value at cost" KPI.
 *   - Triple Whale Lighthouse: AlertBanner when any SKU is < 7 days of stock.
 *   - Lebesgue: StockRisk flag (ad spend on depleting SKU) — not surfaced in table v1,
 *     available in InventoryDataService for future column.
 *
 * Prediction formula (blended — matches InventoryController):
 *   predicted_30d = round((sold_30d × 0.6 + sold_ly × 0.4) × 1.05)
 *   confidence    = "high" when LY data available, "medium" when last 30d only, "low" < 30d
 *
 * Stock health chips use semantic CSS vars:
 *   healthy      → var(--color-success)
 *   low          → var(--color-warning)
 *   critical     → var(--color-danger)
 *   out_of_stock → var(--color-danger) dimmed
 *   overstocked  → var(--color-source-ga4-fg) (sky/blue)
 *   not_tracked  → var(--color-text-muted)
 *
 * @see docs/pages/inventory.md
 * @see docs/competitors/_research_inventory_prediction.md
 * @see docs/UX.md §5.5 DataTable
 * @see docs/UX.md §5.27 ConfidenceChip
 */

import { useCallback, useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronRight,
    Package,
    AlertTriangle,
    TrendingDown,
    Download,
    MoreHorizontal,
    RefreshCw,
    Filter,
    X,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    Layers,
} from 'lucide-react';

import AppLayout from '@/Components/layouts/AppLayout';
import { MetricCard } from '@/Components/shared/MetricCard';
import { KpiGrid } from '@/Components/shared/KpiGrid';
import { PageHeader } from '@/Components/shared/PageHeader';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import { SignalTypeBadge } from '@/Components/shared/SignalTypeBadge';
import { EmptyState } from '@/Components/shared/EmptyState';
import { ExportMenu } from '@/Components/shared/ExportMenu';
import { formatCurrency, formatNumber, formatRelativeTime } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type StockHealth = 'healthy' | 'low' | 'critical' | 'out_of_stock' | 'overstocked' | 'not_tracked';
type Confidence  = 'high' | 'medium' | 'low';

export interface VariantRow {
    id: string;
    sku: string;
    label: string;
    current_stock: number;
    days_of_stock: number | null;
    stock_health: StockHealth;
    sold_last_30d: number;
    sold_ly: number | null;
    predicted_next_30d: number;
    confidence: Confidence;
    predicted_stockout: string | null;
    reorder_qty: number | null;
}

export interface ProductRow {
    id: number;
    name: string;
    sku: string;
    category: string;
    vendor: string;
    status: 'active' | 'inactive';
    thumbnail_url: string | null;
    variant_count: number;
    variants: VariantRow[];
    current_stock: number;
    days_of_stock: number | null;
    stock_health: StockHealth;
    sold_last_30d: number;
    sold_ly: number | null;
    predicted_next_30d: number;
    confidence: Confidence;
    predicted_stockout: string | null;
    reorder_qty: number | null;
    cogs_per_unit: number | null;
    price: number;
    last_synced: string;
}

interface Kpis {
    total_skus: number;
    active_skus: number;
    out_of_stock: number;
    at_risk: number;
    predicted_units_30d: number;
    inventory_value: number;
    turnover_rate: number | null;
}

interface Filters {
    category: string | null;
    vendor: string | null;
    stock_health: StockHealth | null;
    has_forecast: boolean | null;
}

interface Props extends PageProps {
    products: ProductRow[];
    kpis: Kpis;
    filters: Filters;
    alert_critical_count: number;
}

// ─── Stock health chip ────────────────────────────────────────────────────────

const HEALTH_CONFIG: Record<StockHealth, { label: string; chipStyle: React.CSSProperties; dotStyle: React.CSSProperties }> = {
    healthy: {
        label: 'Healthy',
        chipStyle: { backgroundColor: 'color-mix(in srgb, var(--color-success) 12%, transparent)', color: 'var(--color-success)', border: '1px solid color-mix(in srgb, var(--color-success) 30%, transparent)' },
        dotStyle: { backgroundColor: 'var(--color-success)' },
    },
    low: {
        label: 'Low',
        chipStyle: { backgroundColor: 'color-mix(in srgb, var(--color-warning) 12%, transparent)', color: 'var(--color-warning)', border: '1px solid color-mix(in srgb, var(--color-warning) 30%, transparent)' },
        dotStyle: { backgroundColor: 'var(--color-warning)' },
    },
    critical: {
        label: 'Critical',
        chipStyle: { backgroundColor: 'color-mix(in srgb, var(--color-danger) 12%, transparent)', color: 'var(--color-danger)', border: '1px solid color-mix(in srgb, var(--color-danger) 30%, transparent)' },
        dotStyle: { backgroundColor: 'var(--color-danger)' },
    },
    out_of_stock: {
        label: 'Out of stock',
        chipStyle: { backgroundColor: 'color-mix(in srgb, var(--color-danger) 8%, transparent)', color: 'var(--color-danger)', border: '1px solid color-mix(in srgb, var(--color-danger) 20%, transparent)', opacity: 0.85 },
        dotStyle: { backgroundColor: 'var(--color-danger)', opacity: 0.7 },
    },
    overstocked: {
        label: 'Overstocked',
        chipStyle: { backgroundColor: 'color-mix(in srgb, var(--color-source-ga4-fg) 10%, transparent)', color: 'var(--color-source-ga4-fg)', border: '1px solid color-mix(in srgb, var(--color-source-ga4-fg) 25%, transparent)' },
        dotStyle: { backgroundColor: 'var(--color-source-ga4-fg)' },
    },
    not_tracked: {
        label: 'Not tracked',
        chipStyle: { backgroundColor: 'color-mix(in srgb, var(--color-text-muted) 10%, transparent)', color: 'var(--color-text-muted)', border: '1px solid color-mix(in srgb, var(--color-text-muted) 20%, transparent)' },
        dotStyle: { backgroundColor: 'var(--color-text-muted)' },
    },
};

function StockHealthChip({ health, size = 'sm' }: { health: StockHealth; size?: 'xs' | 'sm' }) {
    const config = HEALTH_CONFIG[health];
    const sizeClass = size === 'xs'
        ? 'px-1.5 py-0.5 text-xs'
        : 'px-2 py-0.5 text-xs font-medium';

    return (
        <span
            className={cn('inline-flex items-center gap-1 rounded-full', sizeClass)}
            style={config.chipStyle}
        >
            <span className="h-1.5 w-1.5 rounded-full shrink-0" style={config.dotStyle} />
            {config.label}
        </span>
    );
}

// ─── Confidence chip ──────────────────────────────────────────────────────────

const CONFIDENCE_LABEL: Record<Confidence, string> = {
    high:   'High confidence',
    medium: 'Based on 30d',
    low:    'Low confidence',
};

function PredictionConfidenceChip({ confidence }: { confidence: Confidence }) {
    const style: React.CSSProperties =
        confidence === 'high'
            ? { backgroundColor: 'color-mix(in srgb, var(--color-success) 10%, transparent)', color: 'var(--color-success)', border: '1px solid color-mix(in srgb, var(--color-success) 25%, transparent)' }
            : confidence === 'medium'
            ? { backgroundColor: 'color-mix(in srgb, var(--color-text-muted) 10%, transparent)', color: 'var(--color-text-muted)', border: '1px solid color-mix(in srgb, var(--color-text-muted) 20%, transparent)' }
            : { backgroundColor: 'color-mix(in srgb, var(--color-warning) 10%, transparent)', color: 'var(--color-warning)', border: '1px solid color-mix(in srgb, var(--color-warning) 25%, transparent)' };

    return (
        <span className="ml-1.5 inline-flex items-center rounded-full px-1.5 py-0.5 text-xs" style={style}>
            {CONFIDENCE_LABEL[confidence]}
        </span>
    );
}

// ─── Days of stock display ────────────────────────────────────────────────────

function DaysOfStockDisplay({ days }: { days: number | null }) {
    if (days === null) {
        return <span className="text-muted-foreground text-xs">∞</span>;
    }
    if (days === 0) {
        return <span style={{ color: 'var(--color-danger)' }} className="font-medium">0d</span>;
    }
    const style: React.CSSProperties =
        days < 7  ? { color: 'var(--color-danger)', fontWeight: 600 } :
        days < 30 ? { color: 'var(--color-warning)', fontWeight: 500 } :
        { color: 'var(--color-success)' };

    return <span style={style}>{Math.round(days)}d</span>;
}

// ─── Predicted stockout date badge ────────────────────────────────────────────

function StockoutDateBadge({ date, daysOfStock }: { date: string; daysOfStock: number | null }) {
    if (daysOfStock === null || daysOfStock >= 60) return null;
    const isCritical = daysOfStock < 14;
    const style: React.CSSProperties = isCritical
        ? { backgroundColor: 'color-mix(in srgb, var(--color-danger) 12%, transparent)', color: 'var(--color-danger)', border: '1px solid color-mix(in srgb, var(--color-danger) 30%, transparent)' }
        : { backgroundColor: 'color-mix(in srgb, var(--color-warning) 10%, transparent)', color: 'var(--color-warning)', border: '1px solid color-mix(in srgb, var(--color-warning) 25%, transparent)' };

    const formatted = new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });

    return (
        <span className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" style={style}>
            {isCritical && <AlertTriangle className="mr-1 h-3 w-3" />}
            {formatted}
        </span>
    );
}

// ─── Variant sub-table ────────────────────────────────────────────────────────

function VariantSubTable({ variants }: { variants: VariantRow[] }) {
    return (
        <tr>
            <td colSpan={12} className="p-0">
                <div
                    className="mx-4 mb-3 rounded-md overflow-hidden"
                    style={{ border: '1px solid var(--border-subtle)', backgroundColor: 'var(--color-muted-50, #fafafa)' }}
                >
                    <table className="w-full text-sm">
                        <thead>
                            <tr
                                className="text-xs uppercase tracking-wide"
                                style={{ borderBottom: '1px solid var(--border-subtle)', color: 'var(--color-text-muted)' }}
                            >
                                <th className="px-3 py-2 text-left">Variant</th>
                                <th className="px-3 py-2 text-left font-mono">SKU</th>
                                <th className="px-3 py-2 text-right">Stock</th>
                                <th className="px-3 py-2 text-right">Days</th>
                                <th className="px-3 py-2 text-left">Health</th>
                                <th className="px-3 py-2 text-right">Sold 30d</th>
                                <th className="px-3 py-2 text-right">Predicted 30d</th>
                                <th className="px-3 py-2 text-left">Stock-out</th>
                                <th className="px-3 py-2 text-right">Reorder</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y" style={{ borderColor: 'var(--border-subtle)' }}>
                            {variants.map((v) => (
                                <tr
                                    key={v.id}
                                    className="transition-colors"
                                    style={{ '--tw-bg-opacity': 1 } as React.CSSProperties}
                                    onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = 'var(--color-muted-100, #f5f5f5)')}
                                    onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = '')}
                                >
                                    <td className="px-3 py-2 text-sm font-medium" style={{ color: 'var(--color-text-primary)' }}>
                                        {v.label}
                                    </td>
                                    <td className="px-3 py-2 font-mono text-xs" style={{ color: 'var(--color-text-muted)' }}>
                                        {v.sku}
                                    </td>
                                    <td className="px-3 py-2 text-right tabular-nums">
                                        {v.current_stock === 0 ? (
                                            <span style={{ color: 'var(--color-danger)' }}>0</span>
                                        ) : (
                                            formatNumber(v.current_stock)
                                        )}
                                    </td>
                                    <td className="px-3 py-2 text-right tabular-nums">
                                        <DaysOfStockDisplay days={v.days_of_stock} />
                                    </td>
                                    <td className="px-3 py-2">
                                        <StockHealthChip health={v.stock_health} size="xs" />
                                    </td>
                                    <td className="px-3 py-2 text-right tabular-nums">{formatNumber(v.sold_last_30d)}</td>
                                    <td className="px-3 py-2 text-right tabular-nums">
                                        <span>{formatNumber(v.predicted_next_30d)}</span>
                                        <PredictionConfidenceChip confidence={v.confidence} />
                                    </td>
                                    <td className="px-3 py-2">
                                        {v.predicted_stockout ? (
                                            <StockoutDateBadge date={v.predicted_stockout} daysOfStock={v.days_of_stock} />
                                        ) : (
                                            <span className="text-xs" style={{ color: 'var(--color-text-muted)' }}>—</span>
                                        )}
                                    </td>
                                    <td className="px-3 py-2 text-right tabular-nums">
                                        {v.reorder_qty !== null ? (
                                            <span className="font-medium">{formatNumber(v.reorder_qty)}</span>
                                        ) : (
                                            <span style={{ color: 'var(--color-text-muted)' }}>—</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    );
}

// ─── Sort control ─────────────────────────────────────────────────────────────

type SortKey = 'days_of_stock' | 'current_stock' | 'sold_last_30d' | 'predicted_next_30d' | 'name';
type SortDir = 'asc' | 'desc';

function SortIcon({ active, dir }: { active: boolean; dir: SortDir }) {
    if (!active) return <ArrowUpDown className="ml-1 inline h-3.5 w-3.5 opacity-40" />;
    return dir === 'asc'
        ? <ArrowUp className="ml-1 inline h-3.5 w-3.5" />
        : <ArrowDown className="ml-1 inline h-3.5 w-3.5" />;
}

// ─── Filter bar ───────────────────────────────────────────────────────────────

interface FilterBarProps {
    filters: Filters;
    products: ProductRow[];
    onFilterChange: (key: keyof Filters, value: string | boolean | null) => void;
    onClearAll: () => void;
}

function FilterBar({ filters, products, onFilterChange, onClearAll }: FilterBarProps) {
    const categories = useMemo(() => {
        const cats = [...new Set(products.map((p) => p.category))].sort();
        return cats;
    }, [products]);

    const vendors = useMemo(() => {
        const vs = [...new Set(products.map((p) => p.vendor))].sort();
        return vs;
    }, [products]);

    const hasActiveFilters = filters.category || filters.vendor || filters.stock_health || filters.has_forecast;

    return (
        <div className="flex flex-wrap items-center gap-2">
            {/* Category */}
            <select
                value={filters.category ?? ''}
                onChange={(e) => onFilterChange('category', e.target.value || null)}
                className="h-8 rounded-md border text-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
                style={{ border: '1px solid var(--border-subtle)', backgroundColor: 'var(--color-card)', color: 'var(--color-text-primary)' }}
            >
                <option value="">All categories</option>
                {categories.map((c) => <option key={c} value={c}>{c}</option>)}
            </select>

            {/* Vendor */}
            <select
                value={filters.vendor ?? ''}
                onChange={(e) => onFilterChange('vendor', e.target.value || null)}
                className="h-8 rounded-md border text-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
                style={{ border: '1px solid var(--border-subtle)', backgroundColor: 'var(--color-card)', color: 'var(--color-text-primary)' }}
            >
                <option value="">All vendors</option>
                {vendors.map((v) => <option key={v} value={v}>{v}</option>)}
            </select>

            {/* Stock health */}
            <select
                value={filters.stock_health ?? ''}
                onChange={(e) => onFilterChange('stock_health', (e.target.value as StockHealth) || null)}
                className="h-8 rounded-md border text-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-ring"
                style={{ border: '1px solid var(--border-subtle)', backgroundColor: 'var(--color-card)', color: 'var(--color-text-primary)' }}
            >
                <option value="">All stock health</option>
                <option value="healthy">Healthy (≥30d)</option>
                <option value="low">Low (7–29d)</option>
                <option value="critical">Critical (&lt;7d)</option>
                <option value="out_of_stock">Out of stock</option>
                <option value="overstocked">Overstocked</option>
            </select>

            {/* Has forecast */}
            <button
                type="button"
                onClick={() => onFilterChange('has_forecast', filters.has_forecast ? null : true)}
                className={cn(
                    'h-8 inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-sm transition-colors',
                    filters.has_forecast
                        ? 'bg-foreground text-background border-foreground'
                        : 'border-border bg-card text-foreground hover:bg-muted',
                )}
                style={!filters.has_forecast ? { border: '1px solid var(--border-subtle)' } : undefined}
            >
                <Filter className="h-3.5 w-3.5" />
                Has prediction
            </button>

            {hasActiveFilters && (
                <button
                    type="button"
                    onClick={onClearAll}
                    className="h-8 inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm transition-colors hover:bg-muted"
                    style={{ color: 'var(--color-text-muted)' }}
                >
                    <X className="h-3.5 w-3.5" />
                    Clear
                </button>
            )}
        </div>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function InventoryIndex({ products, kpis, filters: initialFilters, alert_critical_count }: Props) {
    const { props } = usePage<Props>();
    const workspace = (props as PageProps).workspace?.slug ?? '';

    // ── Local state ──────────────────────────────────────────────────────────
    const [filters, setFilters] = useState<Filters>(initialFilters);
    const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());
    const [sort, setSort] = useState<{ key: SortKey; dir: SortDir }>({ key: 'days_of_stock', dir: 'asc' });
    const [alertDismissed, setAlertDismissed] = useState(false);

    // ── Filter handlers ──────────────────────────────────────────────────────
    const handleFilterChange = useCallback((key: keyof Filters, value: string | boolean | null) => {
        setFilters((prev) => ({ ...prev, [key]: value }));
    }, []);

    const handleClearAll = useCallback(() => {
        setFilters({ category: null, vendor: null, stock_health: null, has_forecast: null });
    }, []);

    // ── Row expansion ────────────────────────────────────────────────────────
    const toggleExpand = useCallback((id: number) => {
        setExpandedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    }, []);

    // ── Sort ─────────────────────────────────────────────────────────────────
    const handleSort = useCallback((key: SortKey) => {
        setSort((prev) =>
            prev.key === key
                ? { key, dir: prev.dir === 'asc' ? 'desc' : 'asc' }
                : { key, dir: key === 'days_of_stock' ? 'asc' : 'desc' },
        );
    }, []);

    // ── Filtered + sorted products ────────────────────────────────────────────
    const displayProducts = useMemo(() => {
        let rows = products;

        if (filters.category) {
            rows = rows.filter((p) => p.category === filters.category);
        }
        if (filters.vendor) {
            rows = rows.filter((p) => p.vendor === filters.vendor);
        }
        if (filters.stock_health) {
            rows = rows.filter((p) => p.stock_health === filters.stock_health);
        }
        if (filters.has_forecast) {
            rows = rows.filter((p) => p.predicted_next_30d > 0);
        }

        return [...rows].sort((a, b) => {
            const dir = sort.dir === 'asc' ? 1 : -1;
            const av  = a[sort.key];
            const bv  = b[sort.key];
            if (av === null && bv === null) return 0;
            if (av === null) return 1;   // null (∞) goes to end when asc
            if (bv === null) return -1;
            return av < bv ? -dir : av > bv ? dir : 0;
        });
    }, [products, filters, sort]);

    // ── CSV export (client-side mock) ─────────────────────────────────────────
    const handleExportCsv = useCallback(() => {
        const rows = [
            ['Product', 'SKU', 'Category', 'Vendor', 'Stock', 'Days of stock', 'Health', 'Sold 30d', 'Sold LY', 'Predicted 30d', 'Confidence', 'Stock-out date', 'Reorder qty'],
            ...displayProducts.map((p) => [
                p.name, p.sku, p.category, p.vendor,
                p.current_stock,
                p.days_of_stock ?? '∞',
                p.stock_health,
                p.sold_last_30d,
                p.sold_ly ?? '',
                p.predicted_next_30d,
                p.confidence,
                p.predicted_stockout ?? '',
                p.reorder_qty ?? '',
            ]),
        ];
        const csv  = rows.map((r) => r.join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'inventory.csv';
        a.click();
        URL.revokeObjectURL(url);
    }, [displayProducts]);

    const showAlert = !alertDismissed && alert_critical_count > 0;

    return (
        <AppLayout>
            <Head title="Inventory" />

            <div className="mx-auto max-w-[1440px] space-y-6 p-6">

                {/* ── Alert banner (Triple Whale Lighthouse pattern) ─────────── */}
                {showAlert && (
                    <AlertBanner
                        severity="critical"
                        message={
                            <>
                                <strong>{alert_critical_count} product{alert_critical_count !== 1 ? 's' : ''}</strong>
                                {' '}have fewer than 7 days of stock at current velocity.
                                {' '}
                                <button
                                    type="button"
                                    className="underline underline-offset-2 font-medium"
                                    onClick={() => handleFilterChange('stock_health', 'critical')}
                                >
                                    Show critical only
                                </button>
                            </>
                        }
                        onDismiss={() => setAlertDismissed(true)}
                    />
                )}

                {/* ── Page header ───────────────────────────────────────────── */}
                <PageHeader
                    title="Inventory"
                    subtitle="Stock health, predicted demand, and reorder signals per SKU."
                    action={
                        <div className="flex items-center gap-2">
                            <span className="text-xs" style={{ color: 'var(--color-text-muted)' }}>
                                Updated hourly
                            </span>
                            <ExportMenu onExportCsv={handleExportCsv} />
                        </div>
                    }
                />

                {/* ── KPI strip (7 cards — Glew + Bloom pattern) ────────────── */}
                <KpiGrid cols={4}>
                    <MetricCard
                        label="Total SKUs"
                        value={formatNumber(kpis.total_skus)}
                    />
                    <MetricCard
                        label="Active SKUs"
                        value={formatNumber(kpis.active_skus)}
                    />
                    <MetricCard
                        label="Out of Stock"
                        value={formatNumber(kpis.out_of_stock)}
                    />
                    <MetricCard
                        label="At Risk (<30d)"
                        value={formatNumber(kpis.at_risk)}
                    />
                </KpiGrid>

                <KpiGrid cols={3}>
                    <MetricCard
                        label="Predicted Units (30d)"
                        value={formatNumber(kpis.predicted_units_30d)}
                    />
                    <MetricCard
                        label="Inventory Value"
                        value={formatCurrency(kpis.inventory_value, 'USD', true)}
                    />
                    <MetricCard
                        label="Turnover Rate (ann.)"
                        value={kpis.turnover_rate !== null ? `${kpis.turnover_rate}×` : 'N/A'}
                    />
                </KpiGrid>

                {/* ── Filters ───────────────────────────────────────────────── */}
                <FilterBar
                    filters={filters}
                    products={products}
                    onFilterChange={handleFilterChange}
                    onClearAll={handleClearAll}
                />

                {/* ── Inventory table ───────────────────────────────────────── */}
                <div
                    className="rounded-lg overflow-hidden"
                    style={{ border: '1px solid var(--border-subtle)', backgroundColor: 'var(--color-card)' }}
                >
                    {/* Table toolbar */}
                    <div
                        className="flex items-center justify-between px-4 py-3"
                        style={{ borderBottom: '1px solid var(--border-subtle)' }}
                    >
                        <div className="flex items-center gap-2">
                            <Package className="h-4 w-4" style={{ color: 'var(--color-text-muted)' }} />
                            <span className="text-sm font-medium" style={{ color: 'var(--color-text-primary)' }}>
                                {displayProducts.length} of {products.length} products
                            </span>
                        </div>
                        <div className="flex items-center gap-1.5 text-xs" style={{ color: 'var(--color-text-muted)' }}>
                            <Layers className="h-3.5 w-3.5" />
                            Click a product row to expand variants
                        </div>
                    </div>

                    {/* Table */}
                    <div className="overflow-x-auto">
                        {displayProducts.length === 0 ? (
                            <EmptyState
                                icon={Package}
                                title="No products match your filters"
                                description="Try adjusting the filters above."
                            />
                        ) : (
                            <table className="w-full min-w-[1100px]">
                                <thead>
                                    <tr
                                        className="text-xs font-semibold uppercase tracking-wide"
                                        style={{
                                            backgroundColor: 'var(--color-muted-50, #fafafa)',
                                            borderBottom: '1px solid var(--border-subtle)',
                                            color: 'var(--color-text-muted)',
                                        }}
                                    >
                                        {/* expand toggle col */}
                                        <th className="w-8 px-3 py-2.5" />
                                        <th
                                            className="px-4 py-2.5 text-left cursor-pointer select-none hover:text-foreground"
                                            onClick={() => handleSort('name')}
                                        >
                                            Product
                                            <SortIcon active={sort.key === 'name'} dir={sort.dir} />
                                        </th>
                                        <th className="px-4 py-2.5 text-left">SKU</th>
                                        <th className="px-4 py-2.5 text-center">Variants</th>
                                        <th
                                            className="px-4 py-2.5 text-right cursor-pointer select-none hover:text-foreground"
                                            onClick={() => handleSort('current_stock')}
                                        >
                                            Stock
                                            <SortIcon active={sort.key === 'current_stock'} dir={sort.dir} />
                                        </th>
                                        <th
                                            className="px-4 py-2.5 text-right cursor-pointer select-none hover:text-foreground"
                                            onClick={() => handleSort('days_of_stock')}
                                        >
                                            Days of stock
                                            <SortIcon active={sort.key === 'days_of_stock'} dir={sort.dir} />
                                        </th>
                                        <th className="px-4 py-2.5 text-left">Health</th>
                                        <th
                                            className="px-4 py-2.5 text-right cursor-pointer select-none hover:text-foreground"
                                            onClick={() => handleSort('sold_last_30d')}
                                        >
                                            Sold 30d
                                            <SortIcon active={sort.key === 'sold_last_30d'} dir={sort.dir} />
                                        </th>
                                        <th className="px-4 py-2.5 text-right">Sold LY</th>
                                        <th
                                            className="px-4 py-2.5 text-right cursor-pointer select-none hover:text-foreground"
                                            onClick={() => handleSort('predicted_next_30d')}
                                        >
                                            Predicted 30d
                                            <SortIcon active={sort.key === 'predicted_next_30d'} dir={sort.dir} />
                                        </th>
                                        <th className="px-4 py-2.5 text-left">Stock-out</th>
                                        <th className="px-4 py-2.5 text-right">Reorder</th>
                                        <th className="w-10 px-3 py-2.5" />
                                    </tr>
                                </thead>

                                <tbody className="divide-y" style={{ borderColor: 'var(--border-subtle)' }}>
                                    {displayProducts.map((product) => {
                                        const isExpanded = expandedIds.has(product.id);
                                        const hasVariants = product.variant_count > 0;

                                        return (
                                            <>
                                                <tr
                                                    key={product.id}
                                                    className={cn(
                                                        'transition-colors',
                                                        hasVariants && 'cursor-pointer',
                                                        product.status === 'inactive' && 'opacity-60',
                                                    )}
                                                    onMouseEnter={(e) => {
                                                        e.currentTarget.style.backgroundColor = 'var(--color-muted-50, #fafafa)';
                                                    }}
                                                    onMouseLeave={(e) => {
                                                        e.currentTarget.style.backgroundColor = '';
                                                    }}
                                                    onClick={() => hasVariants && toggleExpand(product.id)}
                                                >
                                                    {/* Expand toggle */}
                                                    <td className="px-3 py-2.5 text-center">
                                                        {hasVariants ? (
                                                            isExpanded
                                                                ? <ChevronDown className="h-4 w-4 mx-auto" style={{ color: 'var(--color-text-muted)' }} />
                                                                : <ChevronRight className="h-4 w-4 mx-auto" style={{ color: 'var(--color-text-muted)' }} />
                                                        ) : null}
                                                    </td>

                                                    {/* Product name + thumbnail */}
                                                    <td className="px-4 py-2.5">
                                                        <div className="flex items-center gap-3 min-w-0">
                                                            <div
                                                                className="h-8 w-8 shrink-0 rounded overflow-hidden flex items-center justify-center"
                                                                style={{ backgroundColor: 'var(--color-muted-100, #f0f0f0)' }}
                                                            >
                                                                {product.thumbnail_url ? (
                                                                    <img src={product.thumbnail_url} alt={product.name} className="h-full w-full object-cover" />
                                                                ) : (
                                                                    <Package className="h-4 w-4" style={{ color: 'var(--color-text-muted)' }} />
                                                                )}
                                                            </div>
                                                            <div className="min-w-0">
                                                                <p className="text-sm font-medium truncate max-w-[220px]" style={{ color: 'var(--color-text-primary)' }} title={product.name}>
                                                                    {product.name}
                                                                </p>
                                                                <p className="text-xs truncate max-w-[220px]" style={{ color: 'var(--color-text-muted)' }}>
                                                                    {product.category} · {product.vendor}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    {/* SKU */}
                                                    <td className="px-4 py-2.5">
                                                        <span className="font-mono text-xs" style={{ color: 'var(--color-text-muted)' }}>
                                                            {product.sku}
                                                        </span>
                                                    </td>

                                                    {/* Variants count */}
                                                    <td className="px-4 py-2.5 text-center">
                                                        {product.variant_count > 0 ? (
                                                            <span
                                                                className="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-medium"
                                                                style={{
                                                                    backgroundColor: 'color-mix(in srgb, var(--color-text-muted) 12%, transparent)',
                                                                    color: 'var(--color-text-muted)',
                                                                    border: '1px solid color-mix(in srgb, var(--color-text-muted) 20%, transparent)',
                                                                }}
                                                            >
                                                                {product.variant_count}
                                                            </span>
                                                        ) : (
                                                            <span style={{ color: 'var(--color-text-muted)' }}>—</span>
                                                        )}
                                                    </td>

                                                    {/* Current stock */}
                                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm">
                                                        {product.current_stock === 0 ? (
                                                            <span style={{ color: 'var(--color-danger)' }} className="font-medium">0</span>
                                                        ) : (
                                                            formatNumber(product.current_stock)
                                                        )}
                                                    </td>

                                                    {/* Days of stock */}
                                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm">
                                                        <DaysOfStockDisplay days={product.days_of_stock} />
                                                    </td>

                                                    {/* Stock health chip */}
                                                    <td className="px-4 py-2.5">
                                                        <StockHealthChip health={product.stock_health} />
                                                    </td>

                                                    {/* Sold last 30d */}
                                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm">
                                                        {formatNumber(product.sold_last_30d)}
                                                    </td>

                                                    {/* Sold same period LY */}
                                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm" style={{ color: 'var(--color-text-muted)' }}>
                                                        {product.sold_ly !== null ? formatNumber(product.sold_ly) : '—'}
                                                    </td>

                                                    {/* Predicted next 30d — with SignalTypeBadge "Modeled" */}
                                                    <td className="px-4 py-2.5 text-right text-sm">
                                                        <div className="flex items-center justify-end gap-1.5">
                                                            <span className="tabular-nums font-medium">
                                                                {formatNumber(product.predicted_next_30d)}
                                                            </span>
                                                            <PredictionConfidenceChip confidence={product.confidence} />
                                                        </div>
                                                    </td>

                                                    {/* Predicted stock-out date */}
                                                    <td className="px-4 py-2.5">
                                                        {product.predicted_stockout ? (
                                                            <StockoutDateBadge
                                                                date={product.predicted_stockout}
                                                                daysOfStock={product.days_of_stock}
                                                            />
                                                        ) : (
                                                            <span className="text-xs" style={{ color: 'var(--color-text-muted)' }}>—</span>
                                                        )}
                                                    </td>

                                                    {/* Suggested reorder qty */}
                                                    <td className="px-4 py-2.5 text-right tabular-nums text-sm">
                                                        {product.reorder_qty !== null ? (
                                                            <span className="font-medium" style={{ color: 'var(--color-text-primary)' }}>
                                                                {formatNumber(product.reorder_qty)}
                                                            </span>
                                                        ) : (
                                                            <span style={{ color: 'var(--color-text-muted)' }}>—</span>
                                                        )}
                                                    </td>

                                                    {/* Action menu */}
                                                    <td className="px-3 py-2.5 text-center">
                                                        <button
                                                            type="button"
                                                            className="rounded p-1 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-muted"
                                                            style={{ color: 'var(--color-text-muted)' }}
                                                            onClick={(e) => { e.stopPropagation(); /* future: open ContextMenu */ }}
                                                            aria-label="Product actions"
                                                        >
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </button>
                                                    </td>
                                                </tr>

                                                {/* Variant sub-table (Cogsy accordion pattern) */}
                                                {isExpanded && product.variants.length > 0 && (
                                                    <VariantSubTable key={`variants-${product.id}`} variants={product.variants} />
                                                )}
                                            </>
                                        );
                                    })}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {/* Table footer */}
                    <div
                        className="flex items-center justify-between px-4 py-2 text-xs"
                        style={{ borderTop: '1px solid var(--border-subtle)', color: 'var(--color-text-muted)' }}
                    >
                        <span>
                            Sorted by <strong>{sort.key.replace(/_/g, ' ')}</strong> ({sort.dir})
                        </span>
                        <div className="flex items-center gap-1.5">
                            <SignalTypeBadge signal="modeled" />
                            <span>Predicted demand uses blended 60/40 last-30d + same-period LY velocity with 5 % growth factor.</span>
                        </div>
                    </div>
                </div>

            </div>
        </AppLayout>
    );
}
