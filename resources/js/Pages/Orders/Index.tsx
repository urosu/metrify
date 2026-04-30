/**
 * Orders/Index — per-order ground truth with six-source attribution.
 *
 * Competitor patterns copied:
 *   - Linear split-pane on row click (_inspiration_linear.md §"Split pane on row click")
 *   - Triple Whale sticky header + sticky first column, column settings modal
 *     (_teardown_triple-whale.md §"Screen: Pixel / Attribution All")
 *   - Triple Whale masked email + touchpoint icon-row
 *     (_teardown_triple-whale.md §"Screen: Pixel / Customer Journeys")
 *   - Stripe click-through from KPI to filtered table + facet chips
 *     (_inspiration_stripe.md §"Payments list")
 *   - Polar dense key-indicator section with per-source breakdown
 *     (_teardown_polar.md §"Screen: Paid Marketing / Acquisition Dashboard")
 *
 * @see docs/pages/orders.md
 * @see docs/UX.md §5.4 FilterChipSentence
 * @see docs/UX.md §5.5 DataTable
 * @see docs/UX.md §5.10 DrawerSidePanel
 * @see docs/UX.md §5.36 TouchpointString
 */

import { useCallback, useMemo, useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    X, Copy, ExternalLink, ShoppingCart, Columns3,
    AlertTriangle, Download, CheckSquare, ChevronDown,
    ChevronRight, Lightbulb, Info, FileText, MoreHorizontal,
    ArrowUpDown, ArrowUp, ArrowDown, Tag,
} from 'lucide-react';

import AppLayout from '@/Components/layouts/AppLayout';
import { MetricCard } from '@/Components/shared/MetricCard';
import type { MetricSource } from '@/Components/shared/MetricCard';
import { FilterChipSentence } from '@/Components/shared/FilterChipSentence';
import { SourceBadge, SourceBadgeFromString } from '@/Components/shared/SourceBadge';
import { TouchpointString } from '@/Components/shared/TouchpointString';
import { ConfidenceChip } from '@/Components/shared/ConfidenceChip';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { SavedView } from '@/Components/shared/SavedView';
import { ExportMenu } from '@/Components/shared/ExportMenu';
import { EmptyState } from '@/Components/shared/EmptyState';
import { formatCurrency, formatNumber, formatDatetime, formatDateOnly, formatRelativeTime, maskEmail } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ── Source canonical colors (UX §4) ───────────────────────────────────────────
const SOURCE_META: Record<string, { label: string; dot: string }> = {
    real:     { label: 'Real',     dot: 'bg-zinc-400' },
    store:    { label: 'Store',    dot: 'bg-slate-400' },
    facebook: { label: 'Facebook', dot: 'bg-blue-500' },
    google:   { label: 'Google',   dot: 'bg-teal-500' },
    gsc:      { label: 'GSC',      dot: 'bg-emerald-500' },
    ga4:      { label: 'GA4',      dot: 'bg-orange-400' },
};

// ── Types ─────────────────────────────────────────────────────────────────────

interface TouchpointEntry {
    source: string;
    ts: string;
    campaign: string | null;
    credit: number;
    modeled?: boolean;
}

interface SourceEntry {
    source: string;
    attributed: boolean;
    value: number;
    campaign?: string | null;
    medium?: string | null;
    query?: string | null;
    modeled?: boolean;
}

interface Customer {
    email: string;
    name: string;
    is_first_time: boolean;
    order_count: number;
}

export interface OrderRow {
    id: string;
    order_number: string;
    created_at: string;
    customer: Customer;
    items_count: number;
    subtotal: number;
    tax: number;
    shipping: number;
    discount: number;
    total: number;
    currency: string;
    status: string;
    country: string | null;
    cogs: number | null;
    primary_source: string | null;
    sources: SourceEntry[];
    touchpoints: TouchpointEntry[];
    confidence: 'high' | 'medium' | 'low';
    is_modeled: boolean;
}

interface KpiData {
    orders: {
        value: number;
        delta_pct: number;
        sources: Record<string, number>;
    };
    revenue: {
        value: number;
        delta_pct: number;
        currency: string;
        sources: Record<string, number>;
    };
    aov: { value: number; delta_pct: number; currency: string };
    refund_rate: { value: number; delta_pct: number };
    pct_tracked: { value: number; delta_pct: number };
    top_source: { source: string; pct: number };
}

interface Pagination {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from: number;
    to: number;
}

interface Filters {
    from: string;
    to: string;
    status?: string | null;
    source?: string | null;
    customer_type?: string | null;
    min_value?: string | null;
    max_value?: string | null;
    store?: string | null;
    order?: string | null;
}

interface AvailableFilters {
    statuses: string[];
    sources: string[];
    customer_types: string[];
}

interface Props extends PageProps {
    orders: OrderRow[];
    kpis: KpiData;
    pagination: Pagination;
    filters: Filters;
    available_filters: AvailableFilters;
}

// ── Sort state ────────────────────────────────────────────────────────────────

type SortKey = 'date' | 'total' | 'items' | 'status' | 'source';
type SortDir = 'asc' | 'desc' | null;

// ── Status pill ───────────────────────────────────────────────────────────────

const STATUS_CONFIG: Record<string, { bg: string; text: string; border: string }> = {
    completed: { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200' },
    refunded:  { bg: 'bg-sky-50',     text: 'text-sky-700',     border: 'border-sky-200' },
    disputed:  { bg: 'bg-rose-50',    text: 'text-rose-700',    border: 'border-rose-200' },
    cancelled: { bg: 'bg-zinc-100',   text: 'text-zinc-500',    border: 'border-zinc-200' },
};

function StatusPill({ status, onClick }: { status: string; onClick?: (s: string) => void }) {
    const key = status.toLowerCase();
    const cfg = STATUS_CONFIG[key] ?? { bg: 'bg-zinc-100', text: 'text-zinc-500', border: 'border-zinc-200' };
    const label = status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border px-2 py-0.5 text-[14px] font-medium select-none',
                cfg.bg, cfg.text, cfg.border,
                onClick && 'cursor-pointer hover:opacity-80 transition-opacity',
            )}
            onClick={onClick ? (e) => { e.stopPropagation(); onClick(status); } : undefined}
        >
            {label}
        </span>
    );
}

// ── Customer cell ─────────────────────────────────────────────────────────────

function CustomerCell({ customer }: { customer: Customer }) {
    return (
        <div className="min-w-0">
            {/* maskEmail: j***@example.com — full email visible in order DrawerSidePanel @see docs/competitors/_research_pii_masking.md */}
            <div className="truncate text-[14px] font-mono text-zinc-700 leading-snug">
                {maskEmail(customer.email)}
            </div>
            <div className="flex items-center gap-1 mt-0.5">
                <span
                    className={cn(
                        'inline-flex items-center rounded-full px-1.5 py-0 text-[14px] font-medium border',
                        customer.is_first_time
                            ? 'bg-teal-50 text-teal-700 border-teal-200'
                            : 'bg-zinc-100 text-zinc-500 border-zinc-200',
                    )}
                >
                    {customer.is_first_time ? '1st' : `×${customer.order_count}`}
                </span>
            </div>
        </div>
    );
}

// ── Source cell ───────────────────────────────────────────────────────────────
// Shows the primary source badge. "Unattributed" when no source is tracked.

function SourceCell({ order, onClick }: { order: OrderRow; onClick?: (s: string) => void }) {
    const src = order.primary_source;
    const claimedBy = order.sources.filter(s => s.attributed && s.source !== 'store' && s.source !== 'real').length;
    const hasMultipleSources = claimedBy > 1;

    if (!src) {
        return (
            <span className="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[14px] font-medium text-zinc-400">
                Unattributed
            </span>
        );
    }

    return (
        <span className="inline-flex items-center gap-1.5">
            <SourceBadgeFromString
                source={src}
                active
                showLabel
                size="sm"
                onClick={onClick ? () => onClick(src) : undefined}
            />
            {order.is_modeled && (
                <span className="rounded border border-zinc-200 bg-zinc-50 px-1 py-0 text-[14px] font-medium text-zinc-500">
                    M
                </span>
            )}
            {hasMultipleSources && (
                <span title={`${claimedBy} sources attributed this order`}>
                    <Info className="h-3 w-3 text-zinc-400" />
                </span>
            )}
        </span>
    );
}

// ── Date cell with relative + absolute on hover ───────────────────────────────

function DateCell({ ts }: { ts: string }) {
    const [hovered, setHovered] = useState(false);
    return (
        <span
            className="relative tabular-nums text-[14px] text-zinc-500 whitespace-nowrap cursor-default"
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
        >
            {hovered ? formatDatetime(ts) : formatRelativeTime(ts)}
        </span>
    );
}

// ── Sortable column header ────────────────────────────────────────────────────

function SortTh({
    label,
    sortKey,
    currentKey,
    dir,
    onSort,
    className,
}: {
    label: string;
    sortKey: SortKey;
    currentKey: SortKey | null;
    dir: SortDir;
    onSort: (k: SortKey) => void;
    className?: string;
}) {
    const isActive = currentKey === sortKey;
    const Icon = isActive ? (dir === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown;
    return (
        <th
            className={cn(
                'px-3 py-2.5 text-left text-[14px] font-semibold text-zinc-500 uppercase tracking-wide cursor-pointer select-none whitespace-nowrap',
                'hover:text-zinc-700 transition-colors',
                isActive && 'text-zinc-900',
                className,
            )}
            onClick={() => onSort(sortKey)}
            aria-sort={isActive ? (dir === 'asc' ? 'ascending' : 'descending') : 'none'}
        >
            <span className="inline-flex items-center gap-1">
                {label}
                <Icon className="h-3 w-3 shrink-0 opacity-60" />
            </span>
        </th>
    );
}

// ── Loading skeleton ──────────────────────────────────────────────────────────

function TableSkeleton() {
    return (
        <div className="space-y-0">
            {Array.from({ length: 8 }).map((_, i) => (
                <div
                    key={i}
                    className="flex items-center gap-3 border-b border-zinc-100 px-4 py-3 last:border-0 animate-pulse"
                >
                    <div className="h-3.5 w-3.5 rounded bg-zinc-100" />
                    <div className="h-4 w-24 rounded bg-zinc-100" />
                    <div className="h-4 w-16 rounded bg-zinc-100" />
                    <div className="h-4 w-32 rounded bg-zinc-100" />
                    <div className="h-4 w-10 rounded bg-zinc-100" />
                    <div className="h-4 w-20 rounded bg-zinc-100 ml-auto" />
                    <div className="h-5 w-20 rounded-full bg-zinc-100" />
                    <div className="h-5 w-16 rounded-full bg-zinc-100" />
                    <div className="h-4 w-28 rounded bg-zinc-100" />
                </div>
            ))}
        </div>
    );
}

// ── Copy button ───────────────────────────────────────────────────────────────

function CopyButton({ text, label }: { text: string; label: string }) {
    const [copied, setCopied] = useState(false);
    function handleCopy() {
        void navigator.clipboard.writeText(text).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        });
    }
    return (
        <button
            onClick={handleCopy}
            className="rounded p-1 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 transition-colors"
            aria-label={label}
            title={copied ? 'Copied!' : label}
        >
            <Copy className={cn('h-3.5 w-3.5', copied && 'text-emerald-600')} />
        </button>
    );
}

// ── Per-source attribution card (drawer) ──────────────────────────────────────
// Shows which revenue each source attributed for this order.

function SixSourceCard({ order }: { order: OrderRow }) {
    return (
        <div className="grid grid-cols-3 gap-2">
            {order.sources.map(s => {
                const meta = SOURCE_META[s.source] ?? { label: s.source, dot: 'bg-zinc-300' };

                return (
                    <div
                        key={s.source}
                        className={cn(
                            'rounded-lg border border-zinc-200 bg-white p-2.5 flex flex-col gap-0.5',
                            !s.attributed && 'opacity-40',
                        )}
                    >
                        <div className="flex items-center gap-1.5">
                            <span className={cn('h-2 w-2 rounded-full shrink-0', meta.dot)} />
                            <span className="text-[14px] font-medium text-zinc-700">
                                {meta.label}
                            </span>
                        </div>
                        <span className="text-[14px] font-semibold tabular-nums text-zinc-900">
                            {s.attributed ? formatCurrency(s.value, order.currency) : '—'}
                        </span>
                        {s.campaign && (
                            <span className="mt-0.5 truncate text-[14px] text-zinc-400" title={s.campaign}>
                                {s.campaign}
                            </span>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

// ── Customer journey timeline (drawer) ────────────────────────────────────────
// Northbeam Orders pattern: vertical timeline, newest at bottom (order at bottom),
// per-touchpoint: platform icon · campaign · fractional credit · timestamp.

function JourneyTimeline({ touchpoints, currency, total }: { touchpoints: TouchpointEntry[]; currency: string; total: number }) {
    if (!touchpoints.length) {
        return (
            <div className="rounded-lg border border-zinc-100 bg-zinc-50 px-4 py-6 text-center">
                <AlertTriangle className="mx-auto mb-2 h-5 w-5 text-zinc-300" />
                <p className="text-[14px] text-zinc-500">No touchpoints matched — this order is unattributed.</p>
                <p className="mt-1 text-[14px] text-zinc-400">No click ID, UTM, or referrer was captured.</p>
            </div>
        );
    }

    return (
        <ol className="relative space-y-0">
            {touchpoints.map((tp, idx) => {
                const isLast = idx === touchpoints.length - 1;
                const meta = SOURCE_META[tp.source] ?? { label: tp.source, dot: 'bg-zinc-300' };
                const creditValue = tp.credit * total;

                return (
                    <li key={idx} className="flex gap-3">
                        {/* Stem */}
                        <div className="flex flex-col items-center">
                            <div className={cn(
                                'mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-[14px] font-semibold',
                                isLast
                                    ? 'border-teal-300 bg-teal-50 text-teal-600'
                                    : 'border-zinc-200 bg-white text-zinc-500',
                            )}>
                                {idx + 1}
                            </div>
                            {!isLast && <div className="my-1 w-px flex-1 bg-zinc-200 min-h-[16px]" />}
                        </div>

                        {/* Content */}
                        <div className={cn('pb-4 min-w-0 flex-1', isLast && 'pb-0')}>
                            <div className="flex items-center gap-1.5 flex-wrap">
                                <span className={cn('h-2 w-2 rounded-full shrink-0', meta.dot)} />
                                <span className="text-[14px] font-medium text-zinc-800">{meta.label}</span>
                                {tp.modeled && (
                                    <span className="rounded border border-zinc-200 bg-zinc-50 px-1 py-0 text-[14px] font-medium text-zinc-500">Modeled</span>
                                )}
                                <span className="ml-auto text-[14px] font-semibold text-teal-600 tabular-nums">
                                    {(tp.credit * 100).toFixed(0)}% · {formatCurrency(creditValue, currency)}
                                </span>
                            </div>
                            {tp.campaign && (
                                <p className="mt-0.5 truncate text-[14px] text-zinc-500" title={tp.campaign}>
                                    {tp.campaign}
                                </p>
                            )}
                            <p className="mt-0.5 text-[14px] text-zinc-400">
                                {formatRelativeTime(tp.ts)}
                                <span className="ml-1 text-zinc-300">·</span>
                                <span className="ml-1">{formatDatetime(tp.ts)}</span>
                            </p>
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}

// ── Line items table (drawer) ─────────────────────────────────────────────────

function LineItemsTable({ order }: { order: OrderRow }) {
    const currency = order.currency;
    // Derive line items from order totals for mock — real data comes from DB
    const mockItems = [
        { sku: 'SKU-001', name: 'Premium Product A', qty: 1, unit: order.subtotal * 0.6, cogs: order.cogs ? order.cogs * 0.6 : null, total: order.subtotal * 0.6 },
        ...(order.items_count > 1 ? [{ sku: 'SKU-002', name: 'Standard Product B', qty: order.items_count - 1, unit: (order.subtotal * 0.4) / (order.items_count - 1), cogs: null, total: order.subtotal * 0.4 }] : []),
    ];

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-[14px]">
                <thead className="border-b border-zinc-100 bg-zinc-50/60">
                    <tr className="text-left text-[14px] font-semibold text-zinc-400 uppercase tracking-wide">
                        <th className="py-2 pr-3 pl-2">Product</th>
                        <th className="py-2 px-2 text-right">Qty</th>
                        <th className="py-2 px-2 text-right">Unit</th>
                        <th className="py-2 px-2 text-right">COGS</th>
                        <th className="py-2 pl-2 pr-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-zinc-100">
                    {mockItems.map((item, i) => (
                        <tr key={i}>
                            <td className="py-2 pr-3 pl-2">
                                <div className="text-zinc-800 font-medium">{item.name}</div>
                                <div className="font-mono text-[14px] text-zinc-400">{item.sku}</div>
                            </td>
                            <td className="py-2 px-2 text-right tabular-nums text-zinc-700">{item.qty}</td>
                            <td className="py-2 px-2 text-right tabular-nums text-zinc-700">{formatCurrency(item.unit, currency)}</td>
                            <td className="py-2 px-2 text-right tabular-nums text-zinc-400">
                                {item.cogs !== null ? formatCurrency(item.cogs, currency) : (
                                    <span title="Configure on /products" className="cursor-help">—</span>
                                )}
                            </td>
                            <td className="py-2 pl-2 pr-2 text-right tabular-nums font-semibold text-zinc-900">
                                {formatCurrency(item.total, currency)}
                            </td>
                        </tr>
                    ))}
                    {/* Totals footer */}
                    <tr className="bg-zinc-50/40 border-t border-zinc-200">
                        <td colSpan={4} className="py-2 pl-2 text-right text-[14px] font-semibold text-zinc-500 uppercase tracking-wide">Subtotal</td>
                        <td className="py-2 pr-2 text-right tabular-nums font-semibold text-zinc-900">{formatCurrency(order.subtotal, currency)}</td>
                    </tr>
                    {order.discount > 0 && (
                        <tr className="bg-zinc-50/40">
                            <td colSpan={4} className="py-1 pl-2 text-right text-[14px] text-zinc-400">Discount</td>
                            <td className="py-1 pr-2 text-right tabular-nums text-emerald-600">−{formatCurrency(order.discount, currency)}</td>
                        </tr>
                    )}
                    <tr className="bg-zinc-50/40">
                        <td colSpan={4} className="py-1 pl-2 text-right text-[14px] text-zinc-400">Tax</td>
                        <td className="py-1 pr-2 text-right tabular-nums text-zinc-600">{formatCurrency(order.tax, currency)}</td>
                    </tr>
                    <tr className="bg-zinc-50/40">
                        <td colSpan={4} className="py-1 pl-2 text-right text-[14px] text-zinc-400">Shipping</td>
                        <td className="py-1 pr-2 text-right tabular-nums text-zinc-600">{order.shipping > 0 ? formatCurrency(order.shipping, currency) : 'Free'}</td>
                    </tr>
                    <tr className="border-t border-zinc-200 bg-zinc-50/40">
                        <td colSpan={4} className="py-2 pl-2 text-right text-[14px] font-bold text-zinc-700 uppercase tracking-wide">Total</td>
                        <td className="py-2 pr-2 text-right tabular-nums text-[15px] font-bold text-zinc-900">{formatCurrency(order.total, currency)}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    );
}

// ── Gap reason chip (drawer) ──────────────────────────────────────────────────

type GapColor = 'amber' | 'rose' | 'zinc';

const GAP_CLASSES: Record<GapColor, { border: string; bg: string; icon: string; title: string; body: string }> = {
    amber: { border: 'border-amber-200', bg: 'bg-amber-50', icon: 'text-amber-500', title: 'text-amber-800', body: 'text-amber-600' },
    rose:  { border: 'border-rose-200',  bg: 'bg-rose-50',  icon: 'text-rose-500',  title: 'text-rose-800',  body: 'text-rose-600' },
    zinc:  { border: 'border-zinc-200',  bg: 'bg-zinc-50',  icon: 'text-zinc-400',  title: 'text-zinc-700',  body: 'text-zinc-500' },
};

function GapReasonChip({ order }: { order: OrderRow }) {
    const hasSource = order.primary_source !== null;
    if (hasSource) return null;
    const hasTP = order.touchpoints.length > 0;
    let label = 'Unattributed';
    let desc  = 'This order could not be attributed to any connected source.';
    let color: GapColor = 'zinc';

    if (!hasTP) {
        label = 'No Click ID / No UTM';
        desc  = 'No click ID, UTM, or referrer was captured. The customer may have typed the URL directly.';
        color = 'zinc';
    }

    const cls = GAP_CLASSES[color];
    return (
        <div className={cn('mb-3 flex items-start gap-2 rounded-lg border p-3', cls.border, cls.bg)}>
            <AlertTriangle className={cn('mt-0.5 h-4 w-4 shrink-0', cls.icon)} />
            <div>
                <p className={cn('text-[14px] font-semibold', cls.title)}>{label}</p>
                <p className={cn('mt-0.5 text-[14px]', cls.body)}>{desc}</p>
            </div>
        </div>
    );
}

// ── Order Detail Drawer ───────────────────────────────────────────────────────
// Linear split-pane pattern: slides from right, 480px, URL-stateful, Esc closes.
// @see docs/UX.md §5.10 DrawerSidePanel
// @see _inspiration_linear.md §"Split pane on row click"

interface DrawerProps {
    order: OrderRow;
    onClose: () => void;
}

function OrderDetailDrawer({ order, onClose }: DrawerProps) {
    const [rawOpen, setRawOpen] = useState(false);
    const currency = order.currency;

    // Esc to close
    const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
        if (e.key === 'Escape') onClose();
    }, [onClose]);

    const claimedCount = order.sources.filter(s => s.attributed && s.source !== 'store').length;
    const hasDisagreement = claimedCount > 2; // more than Real + one other

    return (
        <>
            {/* Backdrop */}
            <div
                className="fixed inset-0 z-30 bg-black/20 backdrop-blur-[1px]"
                onClick={onClose}
                aria-hidden
            />

            {/* Panel — Linear split-pane pattern */}
            <aside
                className="fixed inset-y-0 right-0 z-40 flex w-full max-w-[480px] flex-col bg-white border-l border-zinc-200 shadow-2xl"
                aria-label="Order detail"
                onKeyDown={handleKeyDown}
                tabIndex={-1}
            >
                {/* ── Header strip ─────────────────────────────────────────── */}
                <div className="flex shrink-0 items-start justify-between border-b border-zinc-100 px-5 py-4 bg-white">
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2 flex-wrap">
                            <span className="font-mono text-[15px] font-bold text-zinc-900">
                                {order.order_number}
                            </span>
                            <CopyButton text={order.order_number} label="Copy order number" />
                            <StatusPill status={order.status} />
                        </div>
                        <p className="mt-0.5 text-[14px] text-zinc-400">
                            {formatDatetime(order.created_at)}
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="ml-2 shrink-0 rounded-full p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 transition-colors"
                        aria-label="Close drawer"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                {/* ── Scrollable body ──────────────────────────────────────── */}
                <div className="flex-1 overflow-y-auto">

                    {/* Summary row — StatStripe pattern (UX §5.34) */}
                    <div className="grid grid-cols-4 gap-0 border-b border-zinc-100 bg-zinc-50/50">
                        {[
                            { label: 'Total',   value: formatCurrency(order.total, currency) },
                            { label: 'Items',   value: String(order.items_count) },
                            { label: 'Customer',value: order.customer.is_first_time ? 'New' : 'Returning' },
                            { label: 'Country', value: order.country ?? '—' },
                        ].map(({ label, value }) => (
                            <div key={label} className="px-4 py-3 border-r last:border-r-0 border-zinc-100">
                                <p className="text-[14px] text-zinc-400 uppercase tracking-wide">{label}</p>
                                <p className="mt-0.5 text-[14px] font-semibold tabular-nums text-zinc-900 truncate">
                                    {value}
                                </p>
                            </div>
                        ))}
                    </div>

                    {/* Source attribution card */}
                    <div className="border-b border-zinc-100 px-5 py-4">
                        <div className="flex items-center justify-between mb-3">
                            <h3 className="text-[14px] font-semibold uppercase tracking-wide text-zinc-400">
                                Source Attribution
                            </h3>
                            {hasDisagreement && (
                                <span className="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[14px] font-medium text-zinc-500">
                                    <Info className="h-3 w-3" />
                                    Multiple sources
                                </span>
                            )}
                        </div>
                        <GapReasonChip order={order} />
                        <SixSourceCard order={order} />
                    </div>

                    {/* Customer Journey Timeline — Northbeam Orders pattern */}
                    <div className="border-b border-zinc-100 px-5 py-4">
                        <h3 className="mb-3 text-[14px] font-semibold uppercase tracking-wide text-zinc-400">
                            Customer Journey
                        </h3>
                        <JourneyTimeline touchpoints={order.touchpoints} currency={currency} total={order.total} />
                    </div>

                    {/* Customer card */}
                    <div className="border-b border-zinc-100 px-5 py-4">
                        <h3 className="mb-3 text-[14px] font-semibold uppercase tracking-wide text-zinc-400">Customer</h3>
                        <div className="flex items-start gap-3">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-50 border border-teal-200 text-[15px] font-bold text-teal-600">
                                {order.customer.email[0].toUpperCase()}
                            </div>
                            <div className="min-w-0">
                                <p className="text-[14px] font-mono text-zinc-700 truncate">{order.customer.email}</p>
                                <div className="mt-1 flex items-center gap-2 flex-wrap">
                                    <span className={cn(
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-[14px] font-medium border',
                                        order.customer.is_first_time
                                            ? 'bg-teal-50 text-teal-700 border-teal-200'
                                            : 'bg-zinc-100 text-zinc-500 border-zinc-200',
                                    )}>
                                        {order.customer.is_first_time ? 'First-time buyer' : `${order.customer.order_count} orders total`}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Line items */}
                    <div className="border-b border-zinc-100 px-5 py-4">
                        <h3 className="mb-3 text-[14px] font-semibold uppercase tracking-wide text-zinc-400">Line Items</h3>
                        <LineItemsTable order={order} />
                    </div>

                    {/* Raw payload (collapsed by default) — CLAUDE.md: raw_meta viewer */}
                    <div className="border-b border-zinc-100 px-5 py-3">
                        <button
                            className="flex w-full items-center justify-between text-[14px] text-zinc-400 hover:text-zinc-600 transition-colors"
                            onClick={() => setRawOpen(v => !v)}
                        >
                            <span className="font-semibold uppercase tracking-wide">Raw Payload</span>
                            <ChevronDown className={cn('h-4 w-4 transition-transform', rawOpen && 'rotate-180')} />
                        </button>
                        {rawOpen && (
                            <div className="mt-3 overflow-auto rounded-lg bg-zinc-50 border border-zinc-200 p-3 max-h-48">
                                <pre className="font-mono text-[14px] text-zinc-600 whitespace-pre-wrap">
                                    {JSON.stringify({
                                        id: order.id,
                                        order_number: order.order_number,
                                        sources: order.sources,
                                        touchpoints: order.touchpoints,
                                        confidence: order.confidence,
                                        is_modeled: order.is_modeled,
                                    }, null, 2)}
                                </pre>
                            </div>
                        )}
                    </div>

                    {/* Order timeline (events) */}
                    <div className="px-5 py-4">
                        <h3 className="mb-3 text-[14px] font-semibold uppercase tracking-wide text-zinc-400">Order Timeline</h3>
                        <ol className="space-y-3">
                            {[
                                { event: 'Order placed', ts: order.created_at, color: 'bg-teal-400' },
                                ...(order.status === 'completed' ? [{ event: 'Payment captured', ts: order.created_at, color: 'bg-emerald-400' }] : []),
                                ...(order.status === 'refunded' ? [{ event: 'Refund issued', ts: order.created_at, color: 'bg-sky-400' }] : []),
                                ...(order.status === 'disputed' ? [{ event: 'Dispute opened', ts: order.created_at, color: 'bg-rose-400' }] : []),
                                ...(order.status === 'cancelled' ? [{ event: 'Order cancelled', ts: order.created_at, color: 'bg-zinc-400' }] : []),
                            ].map((ev, i) => (
                                <li key={i} className="flex items-start gap-3">
                                    <span className={cn('mt-1.5 h-2 w-2 rounded-full shrink-0', ev.color)} />
                                    <div>
                                        <p className="text-[14px] font-medium text-zinc-700">{ev.event}</p>
                                        <p className="text-[14px] text-zinc-400">{formatDatetime(ev.ts)}</p>
                                    </div>
                                </li>
                            ))}
                        </ol>
                    </div>
                </div>

                {/* ── Footer actions ─────────────────────────────────────────── */}
                <div className="shrink-0 flex items-center justify-between gap-2 border-t border-zinc-200 px-5 py-3 bg-zinc-50/50">
                    <div className="flex items-center gap-2">
                        <button
                            onClick={onClose}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-[14px] font-medium text-zinc-700 hover:bg-zinc-50 transition-colors"
                        >
                            <ExternalLink className="h-3.5 w-3.5" />
                            Open in store
                        </button>
                        <CopyButton text={`${window.location.origin}/orders?order=${order.id}`} label="Copy order link" />
                    </div>
                    <button
                        disabled
                        title="Coming in v2"
                        className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-[14px] font-medium text-zinc-400 opacity-50 cursor-not-allowed"
                    >
                        <Tag className="h-3.5 w-3.5" />
                        Add annotation
                    </button>
                </div>
            </aside>
        </>
    );
}

// ── Column picker ─────────────────────────────────────────────────────────────
// Triple Whale Attribution All pattern: column settings modal in toolbar.

type ColumnKey = 'date' | 'customer' | 'items' | 'subtotal' | 'tax' | 'shipping' | 'discount' | 'total' | 'source' | 'touchpoints' | 'confidence' | 'country' | 'cogs';

const ALL_COLUMNS: { key: ColumnKey; label: string; default: boolean }[] = [
    { key: 'date',        label: 'Date',        default: true },
    { key: 'customer',    label: 'Customer',    default: true },
    { key: 'items',       label: 'Items',       default: true },
    { key: 'subtotal',    label: 'Subtotal',    default: false },
    { key: 'tax',         label: 'Tax',         default: false },
    { key: 'shipping',    label: 'Shipping',    default: false },
    { key: 'discount',    label: 'Discount',    default: false },
    { key: 'total',       label: 'Total',       default: true },
    { key: 'source',      label: 'Source',      default: true },
    { key: 'touchpoints', label: 'Touchpoints', default: true },
    { key: 'confidence',  label: 'Confidence',  default: true },
    { key: 'country',     label: 'Country',     default: true },
    { key: 'cogs',        label: 'COGS',        default: false },
];

function ColumnPicker({ visible, onToggle }: { visible: Set<ColumnKey>; onToggle: (k: ColumnKey) => void }) {
    const [open, setOpen] = useState(false);
    return (
        <div className="relative">
            <button
                onClick={() => setOpen(v => !v)}
                className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-[14px] font-medium text-zinc-700 hover:bg-zinc-50 transition-colors"
                aria-label="Pick visible columns"
            >
                <Columns3 className="h-3.5 w-3.5" />
                Columns
            </button>
            {open && (
                <>
                    <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} />
                    <div className="absolute right-0 top-full z-20 mt-1 w-48 rounded-xl border border-zinc-200 bg-white py-1.5 shadow-lg">
                        {ALL_COLUMNS.map(col => (
                            <label
                                key={col.key}
                                className="flex cursor-pointer items-center gap-2 px-3 py-1.5 text-[14px] text-zinc-700 hover:bg-zinc-50"
                            >
                                <input
                                    type="checkbox"
                                    checked={visible.has(col.key)}
                                    onChange={() => onToggle(col.key)}
                                    className="h-3.5 w-3.5 rounded border-zinc-300 text-teal-600 accent-teal-600"
                                />
                                {col.label}
                            </label>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}

// ── Filter add popover ────────────────────────────────────────────────────────
// Stripe facet-chips pattern: expands into inline popover to edit the filter value.

function AddFilterMenu({
    available,
    current,
    onAdd,
}: {
    available: AvailableFilters;
    current: Filters;
    onAdd: (key: string, value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    return (
        <div className="relative">
            <button
                onClick={() => setOpen(v => !v)}
                className="inline-flex items-center gap-1 rounded-full border border-dashed border-zinc-300 px-2.5 py-0.5 text-[14px] font-medium text-zinc-500 hover:border-zinc-400 hover:text-zinc-700 transition-colors"
            >
                + Add filter
            </button>
            {open && (
                <>
                    <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} />
                    <div className="absolute left-0 top-full z-20 mt-1 w-56 rounded-xl border border-zinc-200 bg-white shadow-lg py-1.5">
                        <p className="px-3 py-1 text-[14px] font-semibold uppercase tracking-wide text-zinc-400">Status</p>
                        {available.statuses.map(s => (
                            <button
                                key={s}
                                disabled={current.status === s}
                                className="flex w-full items-center gap-2 px-3 py-1.5 text-[14px] text-zinc-700 hover:bg-zinc-50 disabled:opacity-40"
                                onClick={() => { onAdd('status', s); setOpen(false); }}
                            >
                                <StatusPill status={s} />
                            </button>
                        ))}
                        <div className="my-1 border-t border-zinc-100" />
                        <p className="px-3 py-1 text-[14px] font-semibold uppercase tracking-wide text-zinc-400">Source</p>
                        {available.sources.map(s => (
                            <button
                                key={s}
                                disabled={current.source === s}
                                className="flex w-full items-center gap-2 px-3 py-1.5 text-[14px] text-zinc-700 hover:bg-zinc-50 disabled:opacity-40"
                                onClick={() => { onAdd('source', s); setOpen(false); }}
                            >
                                <SourceBadgeFromString source={s} active showLabel size="sm" />
                            </button>
                        ))}
                        <div className="my-1 border-t border-zinc-100" />
                        <p className="px-3 py-1 text-[14px] font-semibold uppercase tracking-wide text-zinc-400">Customer type</p>
                        {available.customer_types.map(ct => (
                            <button
                                key={ct}
                                disabled={current.customer_type === ct}
                                className="flex w-full items-center gap-2 px-3 py-1.5 text-[14px] text-zinc-700 hover:bg-zinc-50 disabled:opacity-40"
                                onClick={() => { onAdd('customer_type', ct); setOpen(false); }}
                            >
                                {ct === 'first_time' ? '1st-time buyers' : 'Repeat buyers'}
                            </button>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}

// ── KPI strip ─────────────────────────────────────────────────────────────────
// 6 compact cards: Orders, Revenue, AOV, Refund Rate, % Tracked, Top Source.
// Stripe KPI-card-with-sparkline pattern.

function KpiStrip({ kpis, currency }: { kpis: KpiData; currency: string }) {
    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
            {/* Orders */}
            <div className="rounded-xl border border-zinc-200 bg-white p-4">
                <p className="text-[14px] font-semibold uppercase tracking-wide text-zinc-400">Orders</p>
                <p className="mt-1.5 text-[28px] font-semibold tabular-nums text-zinc-900 leading-none">
                    {formatNumber(kpis.orders.value, true)}
                </p>
                <div className="mt-1 flex items-center gap-1">
                    <span className={cn(
                        'text-[14px] font-medium tabular-nums',
                        kpis.orders.delta_pct >= 0 ? 'text-emerald-600' : 'text-rose-500',
                    )}>
                        {kpis.orders.delta_pct >= 0 ? '+' : ''}{kpis.orders.delta_pct.toFixed(1)}%
                    </span>
                    <span className="text-[14px] text-zinc-400">vs prev period</span>
                </div>
                {/* Source micro-bar */}
                <div className="mt-2 flex items-center gap-1 flex-wrap">
                    {Object.entries(kpis.orders.sources).slice(0, 3).map(([src, val]) => (
                        <span key={src} className="text-[14px] text-zinc-400">
                            <span className={cn('inline-block h-1.5 w-1.5 rounded-full mr-0.5', SOURCE_META[src]?.dot ?? 'bg-zinc-300')} />
                            {src === 'real' ? 'Real' : src.charAt(0).toUpperCase() + src.slice(1)}: {formatNumber(val as number, true)}
                        </span>
                    ))}
                </div>
            </div>

            {/* Revenue */}
            <div className="rounded-xl border border-zinc-200 bg-white p-4">
                <p className="text-[14px] font-semibold uppercase tracking-wide text-zinc-400">Revenue</p>
                <p className="mt-1.5 text-[28px] font-semibold tabular-nums text-zinc-900 leading-none">
                    {formatCurrency(kpis.revenue.value, currency, true)}
                </p>
                <div className="mt-1 flex items-center gap-1">
                    <span className={cn(
                        'text-[14px] font-medium tabular-nums',
                        kpis.revenue.delta_pct >= 0 ? 'text-emerald-600' : 'text-rose-500',
                    )}>
                        {kpis.revenue.delta_pct >= 0 ? '+' : ''}{kpis.revenue.delta_pct.toFixed(1)}%
                    </span>
                    <span className="text-[14px] text-zinc-400">vs prev period</span>
                </div>
            </div>

            {/* AOV */}
            <div className="rounded-xl border border-zinc-200 bg-white p-4">
                <p className="text-[14px] font-semibold uppercase tracking-wide text-zinc-400">AOV</p>
                <p className="mt-1.5 text-[28px] font-semibold tabular-nums text-zinc-900 leading-none">
                    {formatCurrency(kpis.aov.value, currency)}
                </p>
                <div className="mt-1 flex items-center gap-1">
                    <span className={cn(
                        'text-[14px] font-medium tabular-nums',
                        kpis.aov.delta_pct >= 0 ? 'text-emerald-600' : 'text-rose-500',
                    )}>
                        {kpis.aov.delta_pct >= 0 ? '+' : ''}{kpis.aov.delta_pct.toFixed(1)}%
                    </span>
                    <span className="text-[14px] text-zinc-400">vs prev period</span>
                </div>
            </div>

            {/* Refund Rate */}
            <div className="rounded-xl border border-zinc-200 bg-white p-4">
                <p className="text-[14px] font-semibold uppercase tracking-wide text-zinc-400">Refund Rate</p>
                <p className="mt-1.5 text-[28px] font-semibold tabular-nums text-zinc-900 leading-none">
                    {kpis.refund_rate.value.toFixed(1)}%
                </p>
                <div className="mt-1 flex items-center gap-1">
                    <span className={cn(
                        'text-[14px] font-medium tabular-nums',
                        // invert — lower is better
                        kpis.refund_rate.delta_pct <= 0 ? 'text-emerald-600' : 'text-rose-500',
                    )}>
                        {kpis.refund_rate.delta_pct >= 0 ? '+' : ''}{kpis.refund_rate.delta_pct.toFixed(1)}%
                    </span>
                    <span className="text-[14px] text-zinc-400">vs prev period</span>
                </div>
            </div>

            {/* % Tracked */}
            <div className="rounded-xl border border-zinc-200 bg-white p-4">
                <p className="text-[14px] font-semibold uppercase tracking-wide text-zinc-400">% Tracked</p>
                <p className="mt-1.5 text-[28px] font-semibold tabular-nums text-zinc-900 leading-none">
                    {kpis.pct_tracked.value.toFixed(1)}%
                </p>
                <div className="mt-1 flex items-center gap-1">
                    <span className={cn(
                        'text-[14px] font-medium tabular-nums',
                        kpis.pct_tracked.delta_pct >= 0 ? 'text-emerald-600' : 'text-rose-500',
                    )}>
                        {kpis.pct_tracked.delta_pct >= 0 ? '+' : ''}{kpis.pct_tracked.delta_pct.toFixed(1)}%
                    </span>
                </div>
                {/* Thin progress bar */}
                <div className="mt-2 h-1 w-full rounded-full bg-zinc-100">
                    <div
                        className="h-1 rounded-full bg-teal-400 transition-all"
                        style={{ width: `${Math.min(kpis.pct_tracked.value, 100)}%` }}
                    />
                </div>
            </div>

            {/* Top Source */}
            <div className="rounded-xl border border-zinc-200 bg-white p-4">
                <p className="text-[14px] font-semibold uppercase tracking-wide text-zinc-400">Top Source</p>
                <div className="mt-1.5 flex items-center gap-1.5">
                    <SourceBadgeFromString source={kpis.top_source.source} active showLabel size="sm" />
                </div>
                <p className="mt-1 text-[28px] font-semibold tabular-nums text-zinc-900 leading-none">
                    {kpis.top_source.pct.toFixed(1)}%
                </p>
                <p className="mt-0.5 text-[14px] text-zinc-400">of attributed revenue</p>
            </div>
        </div>
    );
}

// ── Bulk action footer ────────────────────────────────────────────────────────
// Linear + Stripe pattern: sticky footer bar when rows selected.

function BulkActionBar({
    count,
    onExport,
    onClear,
}: {
    count: number;
    onExport: () => void;
    onClear: () => void;
}) {
    if (count === 0) return null;
    return (
        <div className="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-2.5 shadow-xl">
            <span className="text-[14px] font-semibold text-zinc-700">
                {count} order{count !== 1 ? 's' : ''} selected
            </span>
            <div className="h-4 w-px bg-zinc-200" />
            <button
                onClick={onExport}
                className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-1.5 text-[14px] font-medium text-zinc-700 hover:bg-zinc-50 transition-colors"
            >
                <Download className="h-3.5 w-3.5" />
                Export selected
            </button>
            <button
                disabled
                className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-1.5 text-[14px] font-medium text-zinc-400 opacity-50 cursor-not-allowed"
                title="Coming in v2"
            >
                <Tag className="h-3.5 w-3.5" />
                Add tag
            </button>
            <button
                onClick={onClear}
                className="rounded-full p-1 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 transition-colors"
                aria-label="Clear selection"
            >
                <X className="h-4 w-4" />
            </button>
        </div>
    );
}

// ── Pagination row ─────────────────────────────────────────────────────────────

function PaginationRow({
    pagination,
    onPage,
}: {
    pagination: Pagination;
    onPage: (p: number) => void;
}) {
    const { total, per_page, current_page, last_page, from, to } = pagination;
    const maxPages = 7;

    // Build page number array with ellipsis
    const pages: (number | '...')[] = (() => {
        if (last_page <= maxPages) {
            return Array.from({ length: last_page }, (_, i) => i + 1);
        }
        const result: (number | '...')[] = [1];
        if (current_page > 3) result.push('...');
        for (let p = Math.max(2, current_page - 1); p <= Math.min(last_page - 1, current_page + 1); p++) {
            result.push(p);
        }
        if (current_page < last_page - 2) result.push('...');
        result.push(last_page);
        return result;
    })();

    return (
        <div className="flex items-center justify-between gap-3 border-t border-zinc-100 px-4 py-3">
            <p className="text-[14px] text-zinc-400 tabular-nums">
                {formatNumber(from)}–{formatNumber(to)} of {formatNumber(total)} orders
            </p>
            <div className="flex items-center gap-1">
                <button
                    onClick={() => onPage(current_page - 1)}
                    disabled={current_page <= 1}
                    className="rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-[14px] font-medium text-zinc-600 hover:bg-zinc-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    ← Prev
                </button>
                {pages.map((p, i) =>
                    p === '...' ? (
                        <span key={`ellipsis-${i}`} className="px-2 text-[14px] text-zinc-400">…</span>
                    ) : (
                        <button
                            key={p}
                            onClick={() => onPage(p as number)}
                            className={cn(
                                'min-w-[2rem] rounded-lg border px-2.5 py-1.5 text-[14px] font-medium transition-colors',
                                p === current_page
                                    ? 'border-teal-300 bg-teal-50 text-teal-700'
                                    : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50',
                            )}
                        >
                            {p}
                        </button>
                    )
                )}
                <button
                    onClick={() => onPage(current_page + 1)}
                    disabled={current_page >= last_page}
                    className="rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-[14px] font-medium text-zinc-600 hover:bg-zinc-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    Next →
                </button>
            </div>
        </div>
    );
}

// ── Sortable orders (client-side for mock data) ────────────────────────────────

function useSortedOrders(orders: OrderRow[], sortKey: SortKey | null, sortDir: SortDir) {
    return useMemo(() => {
        if (!sortKey || !sortDir) return orders;
        return [...orders].sort((a, b) => {
            let av: number | string = 0;
            let bv: number | string = 0;
            if (sortKey === 'date')   { av = a.created_at; bv = b.created_at; }
            if (sortKey === 'total')  { av = a.total;      bv = b.total; }
            if (sortKey === 'items')  { av = a.items_count; bv = b.items_count; }
            if (sortKey === 'status') { av = a.status;     bv = b.status; }
            if (sortKey === 'source') { av = a.primary_source ?? ''; bv = b.primary_source ?? ''; }
            if (typeof av === 'string') {
                return sortDir === 'asc' ? av.localeCompare(bv as string) : (bv as string).localeCompare(av);
            }
            return sortDir === 'asc' ? (av - (bv as number)) : ((bv as number) - av);
        });
    }, [orders, sortKey, sortDir]);
}

// ── Main page ──────────────────────────────────────────────────────────────────

/**
 * Orders — per-order ground truth with six-source attribution.
 *
 * Layout: KPI strip → FilterChipSentence → Toolbar → DataTable → Drawer.
 * URL-stateful: filters, order drawer, page all in search params.
 *
 * @see docs/pages/orders.md
 * @see docs/UX.md §5.4 FilterChipSentence
 * @see docs/UX.md §5.5 DataTable
 * @see docs/UX.md §5.10 DrawerSidePanel
 */
export default function OrdersIndex({ orders, kpis, pagination, filters, available_filters }: Props) {
    const { workspace } = usePage<PageProps>().props;
    const currency = workspace?.reporting_currency ?? 'USD';
    const slug = workspace?.slug;
    const baseUrl = useMemo(() => wurl(slug, '/orders'), [slug]);

    // ── Column visibility ─────────────────────────────────────────────────────
    const defaultVisible = new Set<ColumnKey>(['date', 'customer', 'items', 'total', 'source', 'touchpoints', 'confidence', 'country']);
    const [visibleColumns, setVisibleColumns] = useState<Set<ColumnKey>>(defaultVisible);
    const toggleColumn = useCallback((key: ColumnKey) => {
        setVisibleColumns(prev => {
            const next = new Set(prev);
            if (next.has(key)) next.delete(key);
            else next.add(key);
            return next;
        });
    }, []);

    // ── Sort ──────────────────────────────────────────────────────────────────
    const [sortKey, setSortKey] = useState<SortKey | null>('date');
    const [sortDir, setSortDir] = useState<SortDir>('desc');

    const handleSort = useCallback((key: SortKey) => {
        setSortKey(prev => {
            if (prev !== key) { setSortDir('desc'); return key; }
            setSortDir(d => d === 'desc' ? 'asc' : d === 'asc' ? null : 'desc');
            return prev;
        });
    }, []);

    const sortedOrders = useSortedOrders(orders, sortKey, sortDir);

    // ── Bulk selection ────────────────────────────────────────────────────────
    const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
    const prevOrdersRef = useRef(orders);
    if (prevOrdersRef.current !== orders) {
        prevOrdersRef.current = orders;
        setSelectedIds(new Set());
    }
    const allSelected = orders.length > 0 && orders.every(o => selectedIds.has(o.id));
    const someSelected = selectedIds.size > 0 && !allSelected;

    const toggleSelectAll = useCallback(() => {
        setSelectedIds(() => allSelected ? new Set<string>() : new Set(orders.map(o => o.id)));
    }, [orders, allSelected]);

    const toggleSelectOne = useCallback((id: string) => {
        setSelectedIds(prev => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    }, []);

    // ── Export selected (client-side CSV) ─────────────────────────────────────
    // Always includes six-source revenue columns per UX §5.30
    const exportSelected = useCallback(() => {
        const rows = orders.filter(o => selectedIds.has(o.id));
        if (!rows.length) return;
        const sources = ['real', 'store', 'facebook', 'google', 'gsc', 'ga4'];
        const headers = ['Order #', 'Date', 'Customer', 'Total', 'Status', 'Country', 'Source', 'Confidence',
            ...sources.map(s => `Revenue (${s})`)];
        const lines = rows.map(o => {
            const srcVals = sources.map(s => {
                const entry = o.sources.find(src => src.source === s);
                return entry?.attributed ? entry.value : 0;
            });
            return [
                o.order_number, o.created_at.slice(0, 10), o.customer.email, o.total,
                o.status, o.country ?? '', o.primary_source ?? 'not_tracked', o.confidence,
                ...srcVals,
            ].map(v => `"${String(v).replace(/"/g, '""')}"`).join(',');
        });
        const csv = [headers.join(','), ...lines].join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url  = URL.createObjectURL(blob);
        const a    = Object.assign(document.createElement('a'), { href: url, download: 'orders-selected.csv' });
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
    }, [orders, selectedIds]);

    // ── Filter helpers ────────────────────────────────────────────────────────
    const buildParams = useCallback((overrides: Partial<Filters>) => {
        const merged = { ...filters, ...overrides };
        const p: Record<string, string> = {};
        if (merged.from)           p.from           = merged.from;
        if (merged.to)             p.to             = merged.to;
        if (merged.status)         p.status         = merged.status;
        if (merged.source)         p.source         = merged.source;
        if (merged.customer_type)  p.customer_type  = merged.customer_type;
        if (merged.min_value)      p.min_value      = merged.min_value;
        if (merged.max_value)      p.max_value      = merged.max_value;
        if (merged.store)          p.store          = merged.store;
        return p;
    }, [filters]);

    const addFilter = useCallback((key: string, value: string) => {
        router.get(baseUrl, buildParams({ [key]: value }), { preserveScroll: true });
    }, [baseUrl, buildParams]);

    const removeFilter = useCallback((key: string) => {
        router.get(baseUrl, buildParams({ [key]: undefined }), { preserveScroll: true });
    }, [baseUrl, buildParams]);

    // Plausible filter-on-click: click a cell value → add as filter
    const clickSourceFilter = useCallback((source: string) => {
        addFilter('source', source);
    }, [addFilter]);

    const clickStatusFilter = useCallback((status: string) => {
        addFilter('status', status);
    }, [addFilter]);

    // ── Drawer ─────────────────────────────────────────────────────────────────
    const openOrder = useCallback((id: string) => {
        router.visit(`${baseUrl}?order=${id}`, { preserveState: true, preserveScroll: true });
    }, [baseUrl]);

    const closeDrawer = useCallback(() => {
        router.visit(baseUrl, { preserveState: true, preserveScroll: true });
    }, [baseUrl]);

    const selectedOrder = useMemo(() =>
        filters.order ? orders.find(o => o.id === filters.order) ?? null : null,
    [orders, filters.order]);

    // ── Pagination ─────────────────────────────────────────────────────────────
    const goToPage = useCallback((page: number) => {
        router.get(baseUrl, { ...buildParams({}), page: String(page) }, { preserveScroll: false });
    }, [baseUrl, buildParams]);

    // ── Active filter chips ────────────────────────────────────────────────────
    const activeFilters = useMemo(() => {
        const chips: { key: string; label: string; value: string; removable: boolean }[] = [
            { key: 'date', label: 'Date', value: `${formatDateOnly(filters.from)} – ${formatDateOnly(filters.to)}`, removable: false },
        ];
        if (filters.status)        chips.push({ key: 'status',        label: 'Status',        value: filters.status,        removable: true });
        if (filters.source)        chips.push({ key: 'source',        label: 'Source',        value: filters.source,        removable: true });
        if (filters.customer_type) chips.push({ key: 'customer_type', label: 'Customer',      value: filters.customer_type, removable: true });
        if (filters.store)         chips.push({ key: 'store',         label: 'Store',         value: filters.store,         removable: true });
        return chips;
    }, [filters]);

    // ── Touchpoints for TouchpointString primitive ─────────────────────────────
    const toTouchpointSources = (tps: TouchpointEntry[]): MetricSource[] => {
        const VALID: MetricSource[] = ['store', 'facebook', 'google', 'gsc', 'ga4', 'real'];
        return tps
            .map(tp => tp.source as MetricSource)
            .filter(s => VALID.includes(s))
            .slice(0, 5);
    };

    return (
        <AppLayout>
            <Head title="Orders" />

            <div className="space-y-5">

                {/* ── Page header ──────────────────────────────────────────── */}
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-[20px] font-semibold text-zinc-900">Orders</h1>
                        <p className="mt-0.5 text-[14px] text-zinc-500">
                            {formatNumber(pagination.total)} orders · {formatDateOnly(filters.from)} – {formatDateOnly(filters.to)}
                        </p>
                    </div>
                </div>

                {/* ── KPI strip ─────────────────────────────────────────────── */}
                <KpiStrip kpis={kpis} currency={currency} />

                {/* ── FilterChipSentence + Add filter ───────────────────────── */}
                <div className="flex items-center gap-2 flex-wrap">
                    <FilterChipSentence
                        entity="orders"
                        chips={activeFilters}
                        onRemove={removeFilter}
                    />
                    <AddFilterMenu
                        available={available_filters}
                        current={filters}
                        onAdd={addFilter}
                    />
                </div>

                {/* ── Toolbar row ───────────────────────────────────────────── */}
                {/* SavedView (left) + column picker + export (right) */}
                <div className="flex items-center justify-between gap-3 flex-wrap">
                    <div className="flex items-center gap-2">
                        <SavedView
                            views={[]}
                            onSelect={() => {}}
                            onSaveCurrent={() => {}}
                            onDelete={() => {}}
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <ColumnPicker visible={visibleColumns} onToggle={toggleColumn} />
                        <ExportMenu
                            onExportCsv={() => {
                                const ids = new Set(orders.map(o => o.id));
                                setSelectedIds(ids);
                                setTimeout(exportSelected, 0);
                            }}
                        />
                    </div>
                </div>

                {/* ── DataTable ─────────────────────────────────────────────── */}
                {/* Linear-pattern: dense rows, sticky header, sticky first col */}
                {/* Triple Whale Attribution All: column settings, sticky header, sticky first col */}
                <div className="overflow-hidden rounded-xl border border-zinc-200 bg-white">
                    {orders.length === 0 ? (
                        <EmptyState
                            icon={ShoppingCart}
                            title={activeFilters.length > 1 ? 'No orders match these filters' : 'No orders yet'}
                            description={
                                activeFilters.length > 1
                                    ? 'Try removing filters or expanding the date range.'
                                    : 'Connect your Shopify or WooCommerce store to start importing orders.'
                            }
                        />
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[900px] text-[14px]">
                                    {/* Sticky header */}
                                    <thead className="sticky top-0 z-10 border-b border-zinc-200 bg-zinc-50/95 backdrop-blur-sm">
                                        <tr>
                                            {/* Bulk-select header checkbox */}
                                            <th className="w-9 px-3 py-2.5">
                                                <input
                                                    type="checkbox"
                                                    checked={allSelected}
                                                    ref={el => { if (el) el.indeterminate = someSelected; }}
                                                    onChange={toggleSelectAll}
                                                    className="h-3.5 w-3.5 rounded border-zinc-300 text-teal-600 accent-teal-600 cursor-pointer"
                                                    aria-label="Select all orders on this page"
                                                />
                                            </th>
                                            {/* Sticky first column: Order # */}
                                            <th className="sticky left-0 z-20 bg-zinc-50/95 px-4 py-2.5 text-left text-[14px] font-semibold text-zinc-500 uppercase tracking-wide whitespace-nowrap">
                                                Order #
                                            </th>
                                            {visibleColumns.has('date') && (
                                                <SortTh label="Date" sortKey="date" currentKey={sortKey} dir={sortDir} onSort={handleSort} />
                                            )}
                                            {visibleColumns.has('customer') && (
                                                <th className="px-3 py-2.5 text-left text-[14px] font-semibold text-zinc-500 uppercase tracking-wide">Customer</th>
                                            )}
                                            {visibleColumns.has('items') && (
                                                <SortTh label="Items" sortKey="items" currentKey={sortKey} dir={sortDir} onSort={handleSort} className="text-right" />
                                            )}
                                            {visibleColumns.has('subtotal') && (
                                                <th className="px-3 py-2.5 text-right text-[14px] font-semibold text-zinc-500 uppercase tracking-wide">Subtotal</th>
                                            )}
                                            {visibleColumns.has('tax') && (
                                                <th className="px-3 py-2.5 text-right text-[14px] font-semibold text-zinc-500 uppercase tracking-wide">Tax</th>
                                            )}
                                            {visibleColumns.has('shipping') && (
                                                <th className="px-3 py-2.5 text-right text-[14px] font-semibold text-zinc-500 uppercase tracking-wide">Ship</th>
                                            )}
                                            {visibleColumns.has('discount') && (
                                                <th className="px-3 py-2.5 text-right text-[14px] font-semibold text-zinc-500 uppercase tracking-wide">Disc.</th>
                                            )}
                                            {visibleColumns.has('total') && (
                                                <SortTh label="Total" sortKey="total" currentKey={sortKey} dir={sortDir} onSort={handleSort} className="text-right" />
                                            )}
                                            {visibleColumns.has('source') && (
                                                <SortTh label="Source" sortKey="source" currentKey={sortKey} dir={sortDir} onSort={handleSort} />
                                            )}
                                            {visibleColumns.has('touchpoints') && (
                                                <th className="px-3 py-2.5 text-left text-[14px] font-semibold text-zinc-500 uppercase tracking-wide">Touchpoints</th>
                                            )}
                                            {visibleColumns.has('confidence') && (
                                                <th className="px-3 py-2.5 text-left text-[14px] font-semibold text-zinc-500 uppercase tracking-wide">Conf.</th>
                                            )}
                                            {visibleColumns.has('country') && (
                                                <th className="px-3 py-2.5 text-left text-[14px] font-semibold text-zinc-500 uppercase tracking-wide">Country</th>
                                            )}
                                            {visibleColumns.has('cogs') && (
                                                <th className="px-3 py-2.5 text-right text-[14px] font-semibold text-zinc-500 uppercase tracking-wide">COGS</th>
                                            )}
                                            {/* Status always last, then actions */}
                                            <SortTh label="Status" sortKey="status" currentKey={sortKey} dir={sortDir} onSort={handleSort} />
                                            <th className="w-8 px-2 py-2.5" />
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-zinc-100">
                                        {sortedOrders.map(order => (
                                            <tr
                                                key={order.id}
                                                onClick={() => openOrder(order.id)}
                                                className={cn(
                                                    'group cursor-pointer transition-colors hover:bg-zinc-50',
                                                    selectedOrder?.id === order.id && 'bg-teal-50/30',
                                                    selectedIds.has(order.id) && 'bg-teal-50/20',
                                                )}
                                                aria-selected={selectedOrder?.id === order.id}
                                            >
                                                {/* Bulk checkbox */}
                                                <td className="px-3 py-2.5" onClick={e => e.stopPropagation()}>
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedIds.has(order.id)}
                                                        onChange={() => toggleSelectOne(order.id)}
                                                        onClick={e => e.stopPropagation()}
                                                        className="h-3.5 w-3.5 rounded border-zinc-300 text-teal-600 accent-teal-600 cursor-pointer"
                                                        aria-label={`Select order ${order.order_number}`}
                                                    />
                                                </td>

                                                {/* Order # — sticky first column */}
                                                <td className="sticky left-0 z-10 bg-white px-4 py-2.5 group-hover:bg-zinc-50 transition-colors">
                                                    <span className="font-mono text-[14px] font-semibold text-zinc-900">
                                                        {order.order_number}
                                                    </span>
                                                </td>

                                                {visibleColumns.has('date') && (
                                                    <td className="px-3 py-2.5">
                                                        <DateCell ts={order.created_at} />
                                                    </td>
                                                )}

                                                {visibleColumns.has('customer') && (
                                                    <td className="px-3 py-2.5 max-w-[180px]">
                                                        <CustomerCell customer={order.customer} />
                                                    </td>
                                                )}

                                                {visibleColumns.has('items') && (
                                                    <td className="px-3 py-2.5 text-right tabular-nums text-zinc-600">
                                                        {order.items_count}
                                                    </td>
                                                )}

                                                {visibleColumns.has('subtotal') && (
                                                    <td className="px-3 py-2.5 text-right tabular-nums text-zinc-600">
                                                        {formatCurrency(order.subtotal, currency)}
                                                    </td>
                                                )}

                                                {visibleColumns.has('tax') && (
                                                    <td className="px-3 py-2.5 text-right tabular-nums text-zinc-500">
                                                        {formatCurrency(order.tax, currency)}
                                                    </td>
                                                )}

                                                {visibleColumns.has('shipping') && (
                                                    <td className="px-3 py-2.5 text-right tabular-nums text-zinc-500">
                                                        {order.shipping > 0 ? formatCurrency(order.shipping, currency) : <span className="text-zinc-300">Free</span>}
                                                    </td>
                                                )}

                                                {visibleColumns.has('discount') && (
                                                    <td className="px-3 py-2.5 text-right tabular-nums text-emerald-600">
                                                        {order.discount > 0 ? `−${formatCurrency(order.discount, currency)}` : <span className="text-zinc-300">—</span>}
                                                    </td>
                                                )}

                                                {visibleColumns.has('total') && (
                                                    <td className="px-3 py-2.5 text-right">
                                                        <span className="tabular-nums text-[14px] font-semibold text-zinc-900">
                                                            {formatCurrency(order.total, currency)}
                                                        </span>
                                                    </td>
                                                )}

                                                {visibleColumns.has('source') && (
                                                    <td className="px-3 py-2.5" onClick={e => e.stopPropagation()}>
                                                        <SourceCell order={order} onClick={clickSourceFilter} />
                                                    </td>
                                                )}

                                                {visibleColumns.has('touchpoints') && (
                                                    <td className="px-3 py-2.5">
                                                        {order.touchpoints.length > 0 ? (
                                                            <TouchpointString
                                                                sources={toTouchpointSources(order.touchpoints)}
                                                                maxVisible={4}
                                                            />
                                                        ) : (
                                                            <span className="text-[14px] text-zinc-300 italic">—</span>
                                                        )}
                                                    </td>
                                                )}

                                                {visibleColumns.has('confidence') && (
                                                    <td className="px-3 py-2.5">
                                                        {order.confidence === 'low' ? (
                                                            <ConfidenceChip sampleSize={0} />
                                                        ) : (
                                                            <span className={cn(
                                                                'inline-flex rounded-full px-2 py-0.5 text-[14px] font-medium border',
                                                                order.confidence === 'high'
                                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                                    : 'bg-zinc-100 text-zinc-600 border-zinc-200',
                                                            )}>
                                                                {order.confidence}
                                                            </span>
                                                        )}
                                                    </td>
                                                )}

                                                {visibleColumns.has('country') && (
                                                    <td className="px-3 py-2.5 text-[14px] text-zinc-500">
                                                        {order.country ?? <span className="text-zinc-300">—</span>}
                                                    </td>
                                                )}

                                                {visibleColumns.has('cogs') && (
                                                    <td className="px-3 py-2.5 text-right tabular-nums text-zinc-500">
                                                        {order.cogs !== null
                                                            ? formatCurrency(order.cogs, currency)
                                                            : <span className="text-zinc-300" title="Configure on /products">—</span>}
                                                    </td>
                                                )}

                                                {/* Status */}
                                                <td className="px-3 py-2.5" onClick={e => e.stopPropagation()}>
                                                    <StatusPill status={order.status} onClick={clickStatusFilter} />
                                                </td>

                                                {/* Row kebab — Stripe hover-reveal pattern */}
                                                <td className="px-2 py-2.5 opacity-0 group-hover:opacity-100 transition-opacity" onClick={e => e.stopPropagation()}>
                                                    <button
                                                        className="rounded p-1 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 transition-colors"
                                                        aria-label={`Actions for ${order.order_number}`}
                                                        onClick={() => openOrder(order.id)}
                                                    >
                                                        <ChevronRight className="h-3.5 w-3.5" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            <PaginationRow pagination={pagination} onPage={goToPage} />
                        </>
                    )}
                </div>

                {/* Attribution explainer — subtle, below table */}
                <p className="text-[14px] text-zinc-400">
                    Click any row to see the per-source breakdown for that order.
                </p>
            </div>

            {/* ── Bulk action bar (Linear + Stripe sticky footer) ─────────── */}
            <BulkActionBar
                count={selectedIds.size}
                onExport={exportSelected}
                onClear={() => setSelectedIds(new Set())}
            />

            {/* ── Order detail drawer (Linear split-pane pattern) ─────────── */}
            {selectedOrder && (
                <OrderDetailDrawer order={selectedOrder} onClose={closeDrawer} />
            )}
        </AppLayout>
    );
}
