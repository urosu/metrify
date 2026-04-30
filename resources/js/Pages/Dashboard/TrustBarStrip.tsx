/**
 * TrustBarStrip — Dashboard per-source revenue summary strip.
 *
 * NOTE: No longer rendered on Dashboard as of the 2026 Shopify-style pivot.
 * Kept for potential future use or Attribution page embedding.
 * The Dashboard now shows KPI cards directly without a source-comparison header.
 *
 * If reinstated, this component shows each platform's attributed revenue
 * as a neutral informational row — no "over-report" framing, no gold border.
 *
 * @see docs/UX.md §5.14 TrustBar
 */
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { SourceBadge, type MetricSource } from '@/Components/shared/SourceBadge';

interface TrustBarSource {
    source: MetricSource;
    value: number | null;
    formatted: string;
    available: boolean;
}

interface TrustBarData {
    revenue: TrustBarSource[];
    real_revenue: { value: number; formatted: string };
    not_tracked: { value: number; formatted: string };
    orders: number;
    confidence: 'high' | 'medium' | 'low';
}

interface TrustBarStripProps {
    data: TrustBarData;
    attributionHref?: string;
    className?: string;
}

/** A single per-source cell in the strip. */
function SourceCell({
    source,
    formatted,
    available,
    href,
}: {
    source: MetricSource;
    formatted: string;
    available: boolean;
    href: string;
}) {
    // Real is shown separately as the summary cell.
    if (source === 'real') return null;

    const cell = (
        <div
            className={cn(
                'flex flex-col gap-0.5 px-4 py-3 rounded-lg border border-zinc-200 bg-white',
                'min-w-[120px]',
                available ? 'cursor-pointer hover:bg-zinc-50 transition-colors' : 'opacity-50',
            )}
        >
            <SourceBadge source={source} active showLabel size="sm" />
            <p className={cn('text-base font-semibold tabular-nums mt-1', available ? 'text-zinc-900' : 'text-zinc-400')}>
                {available ? formatted : 'N/A'}
            </p>
        </div>
    );

    if (!available) return cell;

    return (
        <Link href={href} className="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-lg">
            {cell}
        </Link>
    );
}

export function TrustBarStrip({ data, attributionHref = '/attribution', className }: TrustBarStripProps) {
    return (
        <div
            className={cn(
                'rounded-lg border border-zinc-200 bg-white px-5 py-4',
                className,
            )}
            aria-label="Revenue by source"
        >
            {/* Header row */}
            <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-2">
                    <span className="text-sm font-semibold text-zinc-900">Sales by channel</span>
                    <span className="text-xs text-zinc-400">· {data.orders.toLocaleString()} orders</span>
                </div>
                <Link
                    href={attributionHref}
                    className="inline-flex items-center gap-1 text-xs text-zinc-400 hover:text-zinc-600 transition-colors"
                >
                    View attribution
                    <ArrowRight className="h-3 w-3" />
                </Link>
            </div>

            {/* Cells row */}
            <div className="flex flex-wrap items-stretch gap-3">
                {/* Summary cell */}
                <div className="flex flex-col gap-0.5 px-4 py-3 rounded-lg border border-zinc-200 bg-zinc-50 min-w-[140px]">
                    <p className="text-xs font-medium text-zinc-400 uppercase tracking-wide">Total</p>
                    <p className="text-xl font-semibold tabular-nums text-zinc-900 mt-1">
                        {data.real_revenue.formatted}
                    </p>
                    <p className="text-xs text-zinc-400">
                        {data.orders.toLocaleString()} orders
                    </p>
                </div>

                <div className="h-auto w-px bg-zinc-200 self-stretch mx-1" aria-hidden="true" />

                {/* Per-source cells */}
                {data.revenue.map((item) => (
                    <SourceCell
                        key={item.source}
                        source={item.source}
                        formatted={item.formatted}
                        available={item.available}
                        href={`${attributionHref}?source=${item.source}`}
                    />
                ))}

                {/* Unattributed bucket */}
                <div className="h-auto w-px bg-zinc-200 self-stretch mx-1" aria-hidden="true" />
                <div className="flex flex-col gap-0.5 px-4 py-3 rounded-lg border border-zinc-200 bg-white min-w-[120px]">
                    <p className="text-xs font-medium text-zinc-400">Unattributed</p>
                    <p className="text-base font-semibold tabular-nums text-zinc-600 mt-1">
                        {data.not_tracked.formatted}
                    </p>
                </div>
            </div>
        </div>
    );
}
