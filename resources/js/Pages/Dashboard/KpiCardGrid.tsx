/**
 * KpiCardGrid — Dashboard KPI section (8 MetricCard cards).
 *
 * Clean metric cards: label, value, delta vs prior period, sparkline.
 * Per-source breakdown accessible via the info icon (on demand).
 *
 * When a comparison period is active, each card shows:
 *   - `delta_pct` chip with updated label (vs prior period / vs prior year)
 *   - `comparison_sparkline` as a muted dashed overlay behind the primary sparkline
 *
 * Cards: Revenue (7d) · Profit (7d) · Orders (7d) · AOV (7d)
 *        · ROAS (7d, blended) · MER (7d) · CAC (7d, 1st Time) · CVR (7d)
 *
 * @see docs/UX.md §5.1 MetricCard
 * @see docs/pages/dashboard.md KpiGrid
 * @see docs/competitors/_research_date_compare.md §4
 */
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Sparkline } from '@/Components/charts/Sparkline';
import type { MetricSource } from '@/Components/shared/SourceBadge';

// ─── SVG comparison sparkline (muted dashed overlay, Plausible/Polar pattern) ─

function ComparisonSparklineOverlay({
    data,
    color,
    height,
}: {
    data: { value: number }[];
    color: string;
    height: number;
}) {
    const values = data.map((d) => d.value);
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    const w = 100;
    const yPad = 2;
    const pts = values.map((v, i) => {
        const x = (i / (values.length - 1)) * w;
        const y = yPad + ((max - v) / range) * (height - yPad);
        return `${x.toFixed(2)},${y.toFixed(2)}`;
    });
    return (
        <svg
            viewBox={`0 0 ${w} ${height}`}
            preserveAspectRatio="none"
            style={{ display: 'block', width: '100%', height: '100%' }}
            aria-hidden="true"
        >
            <polyline
                points={pts.join(' ')}
                fill="none"
                stroke={color}
                strokeWidth="1.5"
                strokeOpacity="0.4"
                strokeDasharray="4 2"
                strokeLinejoin="round"
                strokeLinecap="round"
                vectorEffect="non-scaling-stroke"
            />
        </svg>
    );
}

// ─── Types ────────────────────────────────────────────────────────────────────

interface KpiSource {
    source: MetricSource;
    value: number | null;
    available: boolean;
}

export interface KpiCardData {
    name: string;
    qualifier: string;
    value: string;
    delta_pct: number | null;
    delta_period: string;
    sparkline: { value: number }[];
    sources: KpiSource[];
    confidence: 'high' | 'medium' | 'low';
    disagreement_pct?: number | null;
    invert_trend?: boolean;
    expanded_label?: string;
    /**
     * Comparison period sparkline data — shown as muted dashed line behind primary.
     * Populated by the controller when compare_from / compare_to are in the URL.
     */
    comparison_sparkline?: { value: number }[] | null;
    /**
     * Comparison period absolute value — used as tooltip on the delta chip.
     */
    comparison_value?: number | null;
    /**
     * Label for the comparison period, e.g. "vs prior period" or "vs prior year".
     * Defaults to delta_period from the controller.
     */
    comparison_label?: string | null;
}

interface KpiCardProps {
    card: KpiCardData;
}

function DeltaChip({
    pct,
    invertTrend = false,
    comparisonValue,
}: {
    pct: number | null;
    invertTrend?: boolean;
    comparisonValue?: number | null;
}) {
    if (pct === null || pct === undefined) return null;
    const isPositive = invertTrend ? pct < 0 : pct > 0;
    const isNegative = invertTrend ? pct > 0 : pct < 0;
    const Icon = isPositive ? TrendingUp : isNegative ? TrendingDown : Minus;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-xs font-semibold',
                isPositive && 'bg-emerald-50 text-emerald-700',
                isNegative && 'bg-rose-50 text-rose-700',
                !isPositive && !isNegative && 'bg-muted text-muted-foreground',
            )}
            title={
                comparisonValue !== null && comparisonValue !== undefined
                    ? `Prior period: ${comparisonValue.toLocaleString()}`
                    : undefined
            }
        >
            <Icon className="h-3 w-3" aria-hidden="true" />
            {Math.abs(pct).toFixed(1)}%
        </span>
    );
}

function KpiCard({ card }: KpiCardProps) {
    // Use store source color for sparkline when available, else neutral zinc
    const hasSparkline = card.sparkline.length >= 2;
    const hasComparisonSparkline = !!(card.comparison_sparkline && card.comparison_sparkline.length >= 2);
    const label = card.expanded_label ?? `${card.name} (${card.qualifier})`;
    const sparkColor = 'var(--color-source-store-fg)';
    // Derive the comparison label: prefer explicit field, fallback to delta_period
    const compLabel = card.comparison_label ?? card.delta_period;

    return (
        <div className="flex flex-col rounded-lg border border-zinc-200 bg-white overflow-hidden">
            <div className="flex flex-col gap-2 p-5 pb-3 flex-1">
                {/* Metric label */}
                <p className="text-xs font-medium text-zinc-400 uppercase tracking-wide truncate" title={label}>
                    {label}
                </p>

                {/* Value — 36px tabular, weight 500 */}
                <div
                    className={cn(
                        'font-medium tabular-nums tracking-tight text-zinc-900 leading-none',
                        card.confidence === 'low' && 'opacity-70',
                    )}
                    style={{ fontSize: 'var(--text-3xl)', fontVariantNumeric: 'tabular-nums' }}
                >
                    {card.value}
                </div>

                {/* Delta row */}
                <div className="flex flex-wrap items-center gap-1.5 min-h-[20px]">
                    <DeltaChip
                        pct={card.delta_pct}
                        invertTrend={card.invert_trend}
                        comparisonValue={card.comparison_value}
                    />
                    {card.delta_pct !== null && (
                        <span className="text-xs text-zinc-400">{compLabel}</span>
                    )}
                </div>
            </div>

            {/* Sparkline — bleeds edge-to-edge at bottom.
                Comparison overlay drawn first (behind primary) — Plausible/Polar pattern. */}
            {hasSparkline && (
                <div className="relative w-full shrink-0" style={{ height: 40 }}>
                    {hasComparisonSparkline && (
                        <div className="absolute inset-0">
                            <ComparisonSparklineOverlay
                                data={card.comparison_sparkline!}
                                color={sparkColor}
                                height={40}
                            />
                        </div>
                    )}
                    <Sparkline
                        data={card.sparkline}
                        color={sparkColor}
                        height={40}
                        mode="area"
                        className="w-full"
                    />
                </div>
            )}
        </div>
    );
}

interface KpiCardGridProps {
    cards: KpiCardData[];
    className?: string;
}

export function KpiCardGrid({ cards, className }: KpiCardGridProps) {
    return (
        <div className={cn(
            'grid gap-4 grid-cols-2 sm:grid-cols-2 lg:grid-cols-4',
            className,
        )}>
            {cards.map((card) => (
                <KpiCard key={`${card.name}-${card.qualifier}`} card={card} />
            ))}
        </div>
    );
}
