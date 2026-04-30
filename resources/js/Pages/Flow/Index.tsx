/**
 * Flow/Index — User Flow Funnel page.
 *
 * Answers: "Where do ad-driven sessions drop before buying, and which products
 * attract interest without converting?"
 *
 * Sections (top → bottom):
 *   1. Page header + in-page date range + channel filter
 *   2. KPI summary row (Sessions, Funnel CVR, Purchases, Cart Abandon Rate)
 *   3. Horizontal bar funnel — 5 ordered steps with drop-off annotations
 *      (Plausible shape: proportionally-wide bars, step CVR + absolute drop-off)
 *   4. Per-channel contribution bar (stacked bar per step)
 *   5. Product drill table — top 10 products with Winner / Price-resistance chips
 *   6. ProductFlowDrawer — per-product funnel detail (slides in on row click)
 *
 * Placement: /flow (new top-level route). Justified in _research_user_flow_funnel.md:
 * funnel is acquisition-behavior, distinct from attribution (source-disagreement)
 * and customers (lifecycle CRM). Triple Whale, Heap, GA4 all treat funnel as a
 * first-class destination.
 *
 * Visualization: horizontal bar funnel (Plausible pattern) over Sankey — Sankey
 * requires free-path clickstream pixel; we have ordered 5-step session data from
 * utm_* + GA4 events. Horizontal bars are more scannable for ordered funnels.
 *
 * @see docs/competitors/_research_user_flow_funnel.md
 * @see docs/UX.md §5.1 MetricCard
 * @see docs/UX.md §5.4 FilterChipSentence
 * @see docs/UX.md §5.10 DrawerSidePanel
 */

import { useState, useCallback } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    ChevronRight,
    TrendingDown,
    TrendingUp,
    Award,
    AlertTriangle,
    X,
    ChevronDown,
    Info,
} from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DrawerSidePanel } from '@/Components/shared/DrawerSidePanel';
import { DateRangePicker } from '@/Components/shared/DateRangePicker';
import { formatNumber, formatPercent } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

interface FunnelStep {
    key: string;
    label: string;
    sublabel: string;
    count: number;
    step_cvr: number | null;
    funnel_cvr: number;
    drop_off: number;
}

interface ChannelRow {
    channel: string;
    label: string;
    sessions: number;
    purchases: number;
    cvr: number;
}

interface ProductRow {
    id: number;
    name: string;
    views: number;
    cart_rate: number;
    purchase_rate: number;
    cart_no_purchase_rate: number;
    label: 'winner' | 'price_resistance' | null;
}

interface KpiItem {
    key: string;
    label: string;
    value: number;
    format: 'number' | 'percent';
    delta: number;
}

interface FlowPageProps extends PageProps {
    from: string;
    to: string;
    channel: string;
    funnel_steps: FunnelStep[];
    channel_breakdown: ChannelRow[];
    top_products: ProductRow[];
    kpis: KpiItem[];
}

// ─── Channel config (colors per canonical sources) ────────────────────────────
// Colors use CSS vars from UX §4 source color tokens.

const CHANNEL_COLORS: Record<string, string> = {
    facebook: 'var(--color-source-facebook-fg)',
    google:   'var(--color-source-google-fg)',
    organic:  'var(--color-source-gsc-fg)',
    direct:   'var(--color-source-store-fg)',
    email:    'var(--color-source-ga4-fg)',
    all:      'var(--color-primary)',
};

const CHANNEL_OPTIONS = [
    { value: 'all',      label: 'All Channels' },
    { value: 'facebook', label: 'Facebook' },
    { value: 'google',   label: 'Google' },
    { value: 'organic',  label: 'Organic' },
    { value: 'direct',   label: 'Direct' },
    { value: 'email',    label: 'Email' },
];

// ─── KPI card ─────────────────────────────────────────────────────────────────

function KpiCard({ item }: { item: KpiItem }) {
    const formatted =
        item.format === 'percent'
            ? formatPercent(item.value)
            : formatNumber(item.value);

    const deltaUp   = item.delta >= 0;
    const deltaBad  = item.key === 'cart_abandon' ? deltaUp : !deltaUp;
    const DeltaIcon = deltaUp ? TrendingUp : TrendingDown;

    return (
        <div className="rounded-xl border border-zinc-200 bg-white p-5">
            <p className="text-sm font-medium text-zinc-500">{item.label}</p>
            <p className="mt-1.5 text-3xl font-semibold tabular-nums text-zinc-900 leading-none">
                {formatted}
            </p>
            {item.delta !== 0 && (
                <span
                    className={cn(
                        'mt-2 inline-flex items-center gap-1 text-xs font-medium',
                        deltaBad ? 'text-rose-600' : 'text-emerald-600',
                    )}
                >
                    <DeltaIcon className="h-3 w-3" aria-hidden="true" />
                    {Math.abs(item.delta * 100).toFixed(0)}% vs prior period
                </span>
            )}
        </div>
    );
}

// ─── Funnel Bar ───────────────────────────────────────────────────────────────

interface FunnelBarProps {
    step: FunnelStep;
    maxCount: number;
    isLast: boolean;
    activeChannel: string;
    onClick: () => void;
    isClickable: boolean;
}

function FunnelBar({ step, maxCount, isLast, activeChannel, onClick, isClickable }: FunnelBarProps) {
    const pct = maxCount > 0 ? (step.count / maxCount) * 100 : 0;
    const barColor = CHANNEL_COLORS[activeChannel] ?? CHANNEL_COLORS.all;

    // Drop-off severity: amber if >40% drop, rose if >60%
    const dropPct = step.step_cvr !== null ? 1 - step.step_cvr : 0;
    const dropClass =
        dropPct > 0.6
            ? 'text-rose-600 font-semibold'
            : dropPct > 0.4
            ? 'text-amber-600 font-medium'
            : 'text-zinc-500';

    return (
        <div className="relative">
            {/* Step row */}
            <div
                className={cn(
                    'group flex items-center gap-4 py-3',
                    isClickable && 'cursor-pointer',
                )}
                onClick={isClickable ? onClick : undefined}
                role={isClickable ? 'button' : undefined}
                tabIndex={isClickable ? 0 : undefined}
                onKeyDown={isClickable ? (e) => e.key === 'Enter' && onClick() : undefined}
                aria-label={isClickable ? `Drill into ${step.label} products` : undefined}
            >
                {/* Label column */}
                <div className="w-36 shrink-0 text-right">
                    <p className="text-sm font-medium text-zinc-800 leading-tight">{step.label}</p>
                    <p className="text-xs text-zinc-400 leading-tight mt-0.5">{step.sublabel}</p>
                </div>

                {/* Bar + count */}
                <div className="flex-1 flex items-center gap-3 min-w-0">
                    <div className="flex-1 h-9 bg-zinc-100 rounded-md overflow-hidden relative">
                        <div
                            className={cn(
                                'absolute inset-y-0 left-0 rounded-md transition-all duration-500',
                                isClickable && 'group-hover:brightness-90',
                            )}
                            style={{
                                width: `${pct}%`,
                                backgroundColor: barColor,
                                opacity: 0.85,
                            }}
                        />
                        <div className="absolute inset-0 flex items-center px-3">
                            <span className="text-sm font-semibold tabular-nums text-white drop-shadow-sm">
                                {formatNumber(step.count)}
                            </span>
                        </div>
                    </div>

                    {/* Funnel CVR vs entry */}
                    <div className="w-16 shrink-0 text-right">
                        <span className="text-sm font-medium tabular-nums text-zinc-700">
                            {formatPercent(step.funnel_cvr)}
                        </span>
                        <p className="text-[11px] text-zinc-400 leading-none">of entry</p>
                    </div>

                    {/* Drill indicator for drillable steps */}
                    {isClickable ? (
                        <ChevronRight
                            className="h-4 w-4 text-zinc-300 group-hover:text-zinc-500 shrink-0 transition-colors"
                            aria-hidden="true"
                        />
                    ) : (
                        <div className="w-4 shrink-0" />
                    )}
                </div>
            </div>

            {/* Drop-off connector (between this step and the next) */}
            {!isLast && step.step_cvr !== null && (
                <div className="flex items-center gap-4 py-1.5 opacity-80">
                    <div className="w-36 shrink-0" /> {/* Align with label col */}
                    <div className="flex-1 flex items-center gap-2 pl-0">
                        <div className="h-px flex-1 bg-zinc-200" />
                        <div className={cn('flex items-center gap-1 text-xs shrink-0', dropClass)}>
                            <TrendingDown className="h-3 w-3" aria-hidden="true" />
                            <span>
                                −{formatNumber(step.drop_off)} dropped (
                                {formatPercent(1 - step.step_cvr)})
                            </span>
                        </div>
                        <div className="h-px flex-1 bg-zinc-200" />
                    </div>
                    <div className="w-20 shrink-0" /> {/* Align with % col + icon */}
                </div>
            )}
        </div>
    );
}

// ─── Channel stacked bar ──────────────────────────────────────────────────────

function ChannelBreakdown({ rows }: { rows: ChannelRow[] }) {
    const maxSessions = Math.max(...rows.map((r) => r.sessions), 1);

    return (
        <div className="space-y-2.5">
            {rows.map((row) => {
                const sessionPct = (row.sessions / maxSessions) * 100;
                const purchasePct = (row.purchases / row.sessions) * 100;
                const color = CHANNEL_COLORS[row.channel] ?? CHANNEL_COLORS.all;

                return (
                    <div key={row.channel} className="flex items-center gap-3">
                        <div className="w-28 shrink-0 text-right text-sm text-zinc-600 font-medium">
                            {row.label}
                        </div>
                        <div className="flex-1 flex items-center gap-1.5 min-w-0">
                            {/* Sessions bar (full width = max sessions) */}
                            <div className="relative h-6 flex-1 rounded bg-zinc-100 overflow-hidden">
                                <div
                                    className="absolute inset-y-0 left-0 rounded opacity-25"
                                    style={{ width: `${sessionPct}%`, backgroundColor: color }}
                                />
                                {/* Purchase bar nested inside */}
                                <div
                                    className="absolute inset-y-0 left-0 rounded opacity-80"
                                    style={{ width: `${(purchasePct / 100) * sessionPct}%`, backgroundColor: color }}
                                />
                            </div>
                            <span className="w-12 shrink-0 text-right text-xs tabular-nums text-zinc-500">
                                {formatPercent(row.cvr)}
                            </span>
                            <span className="w-20 shrink-0 text-right text-xs tabular-nums text-zinc-400">
                                {formatNumber(row.sessions)} sess.
                            </span>
                        </div>
                    </div>
                );
            })}
            <div className="flex items-center gap-3 pt-1">
                <div className="w-28 shrink-0" />
                <div className="flex-1 flex items-center gap-4 text-[11px] text-zinc-400">
                    <span className="flex items-center gap-1">
                        <span className="inline-block w-3 h-2 rounded-sm bg-zinc-300 opacity-60" />
                        Sessions
                    </span>
                    <span className="flex items-center gap-1">
                        <span className="inline-block w-3 h-2 rounded-sm" style={{ backgroundColor: 'var(--color-primary)', opacity: 0.8 }} />
                        Purchases
                    </span>
                    <span className="ml-auto">CVR</span>
                    <span>Volume</span>
                </div>
            </div>
        </div>
    );
}

// ─── Product label chip ───────────────────────────────────────────────────────

function ProductLabelChip({ label }: { label: ProductRow['label'] }) {
    if (!label) return null;

    if (label === 'winner') {
        return (
            <span className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <Award className="h-2.5 w-2.5" aria-hidden="true" />
                Winner
            </span>
        );
    }

    return (
        <span className="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
            <AlertTriangle className="h-2.5 w-2.5" aria-hidden="true" />
            Possible price-resistance
        </span>
    );
}

// ─── Rate mini-bar ────────────────────────────────────────────────────────────

function RateBar({ value, variant = 'neutral' }: { value: number; variant?: 'good' | 'warn' | 'neutral' }) {
    const bg =
        variant === 'good'
            ? 'bg-emerald-500'
            : variant === 'warn'
            ? 'bg-amber-400'
            : 'bg-zinc-300';

    return (
        <div className="flex items-center gap-2">
            <div className="flex-1 h-1.5 bg-zinc-100 rounded-full overflow-hidden">
                <div
                    className={cn('h-full rounded-full transition-all duration-300', bg)}
                    style={{ width: `${Math.min(value * 100 * 3, 100)}%` }}
                />
            </div>
            <span className="w-10 text-right text-xs tabular-nums text-zinc-600">
                {formatPercent(value)}
            </span>
        </div>
    );
}

// ─── Product drill table ──────────────────────────────────────────────────────

interface ProductTableProps {
    products: ProductRow[];
    onRowClick: (product: ProductRow) => void;
}

function ProductTable({ products, onRowClick }: ProductTableProps) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm border-collapse">
                <thead>
                    <tr className="border-b border-zinc-200">
                        <th className="text-left py-3 px-4 text-xs font-semibold text-zinc-500 uppercase tracking-wide w-[30%]">
                            Product
                        </th>
                        <th className="text-right py-3 px-4 text-xs font-semibold text-zinc-500 uppercase tracking-wide">
                            Views
                        </th>
                        <th className="py-3 px-4 text-xs font-semibold text-zinc-500 uppercase tracking-wide">
                            Add-to-Cart Rate
                        </th>
                        <th className="py-3 px-4 text-xs font-semibold text-zinc-500 uppercase tracking-wide">
                            Purchase Rate
                        </th>
                        <th className="py-3 px-4 text-xs font-semibold text-zinc-500 uppercase tracking-wide">
                            <span className="inline-flex items-center gap-1">
                                Cart ¬ Purchase
                                <Info
                                    className="h-3 w-3 text-zinc-400"
                                    aria-label="Share of users who added to cart but didn't buy — high = price resistance signal"
                                />
                            </span>
                        </th>
                        <th className="py-3 px-4 text-xs font-semibold text-zinc-500 uppercase tracking-wide">
                            Signal
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {products.map((product) => (
                        <tr
                            key={product.id}
                            className="border-b border-zinc-100 hover:bg-zinc-50 cursor-pointer transition-colors"
                            onClick={() => onRowClick(product)}
                        >
                            <td className="py-3 px-4 font-medium text-zinc-900 max-w-[220px] truncate">
                                {product.name}
                            </td>
                            <td className="py-3 px-4 text-right tabular-nums text-zinc-700">
                                {formatNumber(product.views)}
                            </td>
                            <td className="py-3 px-4">
                                <RateBar
                                    value={product.cart_rate}
                                    variant={product.cart_rate >= 0.20 ? 'good' : 'neutral'}
                                />
                            </td>
                            <td className="py-3 px-4">
                                <RateBar
                                    value={product.purchase_rate}
                                    variant={
                                        product.purchase_rate >= 0.40
                                            ? 'good'
                                            : product.purchase_rate < 0.15
                                            ? 'warn'
                                            : 'neutral'
                                    }
                                />
                            </td>
                            <td className="py-3 px-4">
                                <RateBar
                                    value={product.cart_no_purchase_rate}
                                    variant={
                                        product.cart_no_purchase_rate > 0.20 ? 'warn' : 'neutral'
                                    }
                                />
                            </td>
                            <td className="py-3 px-4">
                                <ProductLabelChip label={product.label} />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

// ─── Product Flow Drawer ──────────────────────────────────────────────────────
/**
 * ProductFlowDrawer — per-product funnel detail slide-over.
 *
 * Shows:
 *   - Mini funnel for this product alone (views → cart → purchase)
 *   - Traffic sources breakdown for this product
 *   - Related products (customers also viewed)
 *
 * @see docs/UX.md §5.10 DrawerSidePanel
 */

interface ProductFlowDrawerProps {
    product: ProductRow | null;
    open: boolean;
    onClose: () => void;
    channel: string;
}

// Mock "related products" data for the drawer
const RELATED_PRODUCT_NAMES = [
    'Classic Leather Sneakers',
    'Performance Running Shoes',
    'Waterproof Trail Boots',
    'Minimalist Canvas Slip-ons',
    'Premium Leather Oxford',
];

function ProductFlowDrawer({ product, open, onClose, channel }: ProductFlowDrawerProps) {
    if (!product) return null;

    // Derive a per-product mini funnel from the product's own rates
    const miniSteps = [
        {
            label: 'Product Views',
            count: product.views,
            pct: 1.0,
        },
        {
            label: 'Add to Cart',
            count: Math.round(product.views * product.cart_rate),
            pct: product.cart_rate,
        },
        {
            label: 'Purchase',
            count: Math.round(product.views * product.cart_rate * product.purchase_rate),
            pct: product.cart_rate * product.purchase_rate,
        },
    ];

    // Per-source breakdown (mock realistic distribution)
    const sourceBreakdown = [
        { label: 'Facebook',      share: 0.42, color: 'var(--color-source-facebook-fg)' },
        { label: 'Google',        share: 0.28, color: 'var(--color-source-google-fg)' },
        { label: 'Organic',       share: 0.18, color: 'var(--color-source-gsc-fg)' },
        { label: 'Direct',        share: 0.07, color: 'var(--color-source-store-fg)' },
        { label: 'Email',         share: 0.05, color: 'var(--color-source-ga4-fg)' },
    ];

    // Related products: pick a few from the static list excluding this one
    const related = RELATED_PRODUCT_NAMES.filter((n) => n !== product.name).slice(0, 3);

    return (
        <DrawerSidePanel
            open={open}
            onClose={onClose}
            title={product.name}
            subtitle={<ProductLabelChip label={product.label} />}
            width={520}
        >
            <div className="px-5 py-5 space-y-8">

                {/* Mini funnel */}
                <section>
                    <h3 className="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-4">
                        Product Funnel
                    </h3>
                    <div className="space-y-2">
                        {miniSteps.map((step, i) => (
                            <div key={step.label} className="flex items-center gap-3">
                                <div className="w-28 shrink-0 text-right">
                                    <p className="text-sm font-medium text-zinc-700">{step.label}</p>
                                </div>
                                <div className="flex-1 flex items-center gap-2">
                                    <div className="flex-1 h-7 bg-zinc-100 rounded overflow-hidden relative">
                                        <div
                                            className="absolute inset-y-0 left-0 rounded transition-all duration-500"
                                            style={{
                                                width: `${step.pct * 100}%`,
                                                backgroundColor: CHANNEL_COLORS[channel] ?? CHANNEL_COLORS.all,
                                                opacity: 0.80,
                                            }}
                                        />
                                        <div className="absolute inset-0 flex items-center px-2.5">
                                            <span className="text-xs font-semibold text-white drop-shadow-sm tabular-nums">
                                                {formatNumber(step.count)}
                                            </span>
                                        </div>
                                    </div>
                                    <span className="w-12 shrink-0 text-right text-xs tabular-nums text-zinc-500">
                                        {formatPercent(step.pct)}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Price-resistance explanation */}
                    {product.label === 'price_resistance' && (
                        <div className="mt-4 rounded-lg bg-amber-50 border border-amber-200 p-3">
                            <p className="text-xs font-semibold text-amber-800 flex items-center gap-1 mb-1">
                                <AlertTriangle className="h-3.5 w-3.5" aria-hidden="true" />
                                Price-resistance signal detected
                            </p>
                            <p className="text-xs text-amber-700">
                                {formatPercent(product.cart_rate)} of viewers add this product to cart,
                                but only {formatPercent(product.purchase_rate)} complete the purchase.
                                Consider testing a lower price point, a promotion, or adding social proof
                                (reviews, size guides) to reduce checkout hesitation.
                            </p>
                        </div>
                    )}

                    {product.label === 'winner' && (
                        <div className="mt-4 rounded-lg bg-emerald-50 border border-emerald-200 p-3">
                            <p className="text-xs font-semibold text-emerald-800 flex items-center gap-1 mb-1">
                                <Award className="h-3.5 w-3.5" aria-hidden="true" />
                                Winner product
                            </p>
                            <p className="text-xs text-emerald-700">
                                High add-to-cart rate ({formatPercent(product.cart_rate)}) and strong purchase
                                completion ({formatPercent(product.purchase_rate)}). Prioritise in ads, bundles,
                                and promotions. Consider increasing stock.
                            </p>
                        </div>
                    )}
                </section>

                {/* Traffic sources */}
                <section>
                    <h3 className="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-4">
                        Traffic Sources
                    </h3>
                    <div className="space-y-2.5">
                        {sourceBreakdown.map((src) => (
                            <div key={src.label} className="flex items-center gap-3">
                                <div className="w-20 shrink-0 text-right text-xs text-zinc-600 font-medium">
                                    {src.label}
                                </div>
                                <div className="flex-1 h-5 bg-zinc-100 rounded overflow-hidden relative">
                                    <div
                                        className="absolute inset-y-0 left-0 rounded"
                                        style={{
                                            width: `${src.share * 100}%`,
                                            backgroundColor: src.color,
                                            opacity: 0.70,
                                        }}
                                    />
                                </div>
                                <span className="w-10 shrink-0 text-right text-xs tabular-nums text-zinc-500">
                                    {formatPercent(src.share)}
                                </span>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Related products */}
                <section>
                    <h3 className="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">
                        Customers Also Viewed
                    </h3>
                    <div className="space-y-1.5">
                        {related.map((name) => (
                            <div
                                key={name}
                                className="flex items-center justify-between rounded-lg px-3 py-2 bg-zinc-50 hover:bg-zinc-100 transition-colors"
                            >
                                <span className="text-sm text-zinc-700">{name}</span>
                                <ChevronRight className="h-3.5 w-3.5 text-zinc-400" aria-hidden="true" />
                            </div>
                        ))}
                    </div>
                </section>

            </div>
        </DrawerSidePanel>
    );
}

// ─── Channel filter chip group ────────────────────────────────────────────────

interface ChannelFilterProps {
    value: string;
    onChange: (channel: string) => void;
}

function ChannelFilter({ value, onChange }: ChannelFilterProps) {
    return (
        <div className="flex flex-wrap gap-1.5">
            {CHANNEL_OPTIONS.map((opt) => {
                const active = value === opt.value;
                const color = CHANNEL_COLORS[opt.value] ?? CHANNEL_COLORS.all;
                return (
                    <button
                        key={opt.value}
                        onClick={() => onChange(opt.value)}
                        className={cn(
                            'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition-colors',
                            active
                                ? 'text-white shadow-sm'
                                : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200',
                        )}
                        style={active ? { backgroundColor: color } : undefined}
                        aria-pressed={active}
                    >
                        {opt.value !== 'all' && (
                            <span
                                className="inline-block w-1.5 h-1.5 rounded-full"
                                style={{ backgroundColor: active ? 'rgba(255,255,255,0.7)' : color }}
                            />
                        )}
                        {opt.label}
                    </button>
                );
            })}
        </div>
    );
}

// ─── Section header ───────────────────────────────────────────────────────────

function SectionHeader({ title, description }: { title: string; description?: string }) {
    return (
        <div className="mb-4">
            <h2 className="text-sm font-semibold text-zinc-900">{title}</h2>
            {description && <p className="text-xs text-zinc-500 mt-0.5">{description}</p>}
        </div>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function FlowIndex() {
    const page = usePage<FlowPageProps>();
    const {
        from,
        to,
        channel,
        funnel_steps,
        channel_breakdown,
        top_products,
        kpis,
        workspace,
    } = page.props;

    const slug = workspace?.slug;
    const [drawerProduct, setDrawerProduct] = useState<ProductRow | null>(null);
    const [drawerOpen, setDrawerOpen] = useState(false);

    // Derive max count (entry step) for bar proportioning
    const maxCount = funnel_steps[0]?.count ?? 1;

    // Steps that open the product drill (product_view, add_to_cart)
    const drillableKeys = new Set(['product_view', 'add_to_cart']);

    const handleChannelChange = useCallback(
        (newChannel: string) => {
            router.get(
                wurl(slug, '/flow'),
                { from, to, channel: newChannel },
                { preserveScroll: true, preserveState: true, replace: true },
            );
        },
        [slug, from, to],
    );

    const handleProductClick = useCallback((product: ProductRow) => {
        setDrawerProduct(product);
        setDrawerOpen(true);
    }, []);

    return (
        <AppLayout>
            <Head title="User Flow Funnel" />

            <div className="mx-auto max-w-5xl px-6 py-8 space-y-8">

                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <PageHeader
                        title="User Flow"
                        subtitle="Acquisition funnel — landing page to purchase, with product-level drop-off signals."
                    />
                    {/* In-page date range (not TopBar — spec §"Page-level filters live in content") */}
                    <div className="shrink-0">
                        <DateRangePicker />
                    </div>
                </div>

                {/* KPI summary */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    {kpis.map((kpi) => (
                        <KpiCard key={kpi.key} item={kpi} />
                    ))}
                </div>

                {/* Funnel section */}
                <section className="rounded-xl border border-zinc-200 bg-white p-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
                        <SectionHeader
                            title="Conversion Funnel"
                            description="Click Product Page or Add to Cart to drill into per-product breakdown."
                        />
                        {/* Channel filter — in-page, per spec "source filters contextually next to the section" */}
                        <ChannelFilter value={channel} onChange={handleChannelChange} />
                    </div>

                    <div className="divide-y divide-zinc-50">
                        {funnel_steps.map((step, idx) => (
                            <FunnelBar
                                key={step.key}
                                step={step}
                                maxCount={maxCount}
                                isLast={idx === funnel_steps.length - 1}
                                activeChannel={channel}
                                isClickable={drillableKeys.has(step.key)}
                                onClick={() => {
                                    // Clicking a drillable funnel step opens the product
                                    // table below (scroll) and optionally highlights
                                    const el = document.getElementById('product-drill');
                                    el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                }}
                            />
                        ))}
                    </div>

                    {/* Overall funnel CVR callout */}
                    <div className="mt-6 flex items-center gap-3 rounded-lg bg-zinc-50 border border-zinc-200 px-4 py-3">
                        <span className="text-sm text-zinc-600">
                            Overall funnel CVR:
                        </span>
                        <span className="text-sm font-semibold tabular-nums text-zinc-900">
                            {formatPercent(funnel_steps[funnel_steps.length - 1]?.funnel_cvr ?? 0)}
                        </span>
                        <span className="text-xs text-zinc-400">
                            ({formatNumber(funnel_steps[0]?.count ?? 0)} sessions → {formatNumber(funnel_steps[funnel_steps.length - 1]?.count ?? 0)} purchases)
                        </span>
                    </div>
                </section>

                {/* Per-channel breakdown */}
                <section className="rounded-xl border border-zinc-200 bg-white p-6">
                    <SectionHeader
                        title="Channel Breakdown"
                        description="Sessions (light) vs purchases (solid) per channel. CVR shown right."
                    />
                    <ChannelBreakdown rows={channel_breakdown} />
                </section>

                {/* Product drill */}
                <section id="product-drill" className="rounded-xl border border-zinc-200 bg-white">
                    <div className="px-6 py-5 border-b border-zinc-100">
                        <SectionHeader
                            title="Product Performance"
                            description='Click any row for per-product funnel detail. "Winner" = high cart + high purchase. "Possible price-resistance" = high cart but low purchase rate.'
                        />
                        <div className="flex items-center gap-3 mt-3">
                            <span className="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <Award className="h-2.5 w-2.5" aria-hidden="true" />
                                Winner — high cart + high purchase rate
                            </span>
                            <span className="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                <AlertTriangle className="h-2.5 w-2.5" aria-hidden="true" />
                                Possible price-resistance — high cart, low purchase
                            </span>
                        </div>
                    </div>
                    <ProductTable products={top_products} onRowClick={handleProductClick} />
                </section>

            </div>

            {/* Per-product detail drawer */}
            <ProductFlowDrawer
                product={drawerProduct}
                open={drawerOpen}
                onClose={() => setDrawerOpen(false)}
                channel={channel}
            />
        </AppLayout>
    );
}
