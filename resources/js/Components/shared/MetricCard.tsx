/**
 * MetricCard — clean ecommerce analytics metric card.
 *
 * Design shape:
 *   1. Label — metric name with qualifier suffix (e.g. "Revenue (7d)").
 *   2. Value — large 36px tabular number, weight 500.
 *   3. Delta — smaller chip vs prior period (green/red).
 *   4. Sparkline (optional) — 40px area chart bleeds edge-to-edge at bottom.
 *   5. TargetProgress (optional) — thin bar beneath value.
 *
 *   On-demand source detail: clicking the Info icon opens a popover
 *   listing per-source values for power users. Not always visible.
 *
 * Padding: 24px. Value: text-3xl / font-medium. Borders: 1px zinc-200, no shadow.
 *
 * Variants:
 *   MetricCard         — default 240×140+
 *   MetricCardCompact  — 180×80, no sparkline, dense grids
 *   MetricCardDetail   — full-width, prev period + prev year + source detail popover
 *   MetricCardMultiValue — mean/median/mode side-by-side (AOV, AOP, LTV)
 *   MetricCardPortfolio  — multi-workspace contribution bar + per-workspace sparklines
 *
 * @see docs/UX.md §5.1 MetricCard
 * @see docs/UX.md §5.1.1 MetricCardPortfolio
 * @see docs/UX.md §5.1.2 MetricCardMultiValue
 */

import React, { useState, useRef, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { TrendingUp, TrendingDown, Minus, Zap, Info } from 'lucide-react';
import { cn } from '@/lib/utils';
import { SourceBadge } from '@/Components/shared/SourceBadge';
import { Sparkline } from '@/Components/charts/Sparkline';
import { TargetProgress } from '@/Components/shared/Target';

// Re-export MetricSource for backwards-compat callers.
export type { MetricSource } from '@/Components/shared/SourceBadge';

import type { MetricSource } from '@/Components/shared/SourceBadge';

/** Maps source → CSS color token for sparklines. */
const SOURCE_COLOR: Record<MetricSource, string> = {
    store:    'var(--color-source-store-fg)',
    facebook: 'var(--color-source-facebook-fg)',
    google:   'var(--color-source-google-fg)',
    gsc:      'var(--color-source-gsc-fg)',
    ga4:      'var(--color-source-ga4-fg)',
    real:     'var(--color-source-real-fg)',
};

const SOURCE_LABELS: Record<MetricSource, string> = {
    store:    'Store',
    facebook: 'Facebook',
    google:   'Google',
    gsc:      'GSC',
    ga4:      'GA4',
    real:     'Real',
};

// ─── SourceComparePopover ──────────────────────────────────────────────────────
/**
 * On-demand popover listing all 6 sources with their values.
 * Triggered by the Info icon — not always visible (Design v2).
 */
interface SourceValue {
    source: MetricSource;
    value: string | null;
    available: boolean;
}

function SourceComparePopover({
    sourceValues,
    activeSource,
    onSourceClick,
}: {
    sourceValues: SourceValue[];
    activeSource: MetricSource;
    onSourceClick?: (src: MetricSource) => void;
}) {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;
        function handleClickOutside(e: MouseEvent) {
            if (ref.current && !ref.current.contains(e.target as Node)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [open]);

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                onClick={(e) => { e.stopPropagation(); e.preventDefault(); setOpen((v) => !v); }}
                className="flex items-center justify-center h-5 w-5 rounded transition-colors duration-150"
                style={{ color: 'var(--color-text-muted)' }}
                aria-label="Per-source breakdown"
                title="Per-source breakdown"
            >
                <Info className="h-3.5 w-3.5" />
            </button>
            {open && (
                <div
                    className="absolute right-0 top-full z-50 mt-1.5 w-48 rounded-lg bg-card py-1"
                    style={{
                        border: '1px solid var(--border)',
                        boxShadow: 'var(--shadow-raised)',
                    }}
                    onClick={(e) => e.stopPropagation()}
                >
                    <p className="px-3 py-1.5 text-xs font-semibold uppercase tracking-wide"
                       style={{
                           color: 'var(--color-text-muted)',
                           borderBottom: '1px solid var(--border-subtle)',
                       }}>
                        By source
                    </p>
                    {sourceValues.map(({ source, value, available }) => (
                        <button
                            key={source}
                            type="button"
                            onClick={() => {
                                if (available && onSourceClick) {
                                    onSourceClick(source);
                                    setOpen(false);
                                }
                            }}
                            className={cn(
                                'flex w-full items-center justify-between px-3 py-1.5 text-xs transition-colors duration-150',
                                available ? 'cursor-pointer' : 'cursor-default opacity-50',
                            )}
                            style={source === activeSource
                                ? { backgroundColor: 'var(--brand-primary-subtle)' }
                                : available ? undefined : undefined
                            }
                        >
                            <SourceBadge
                                source={source}
                                active={source === activeSource}
                                disabled={!available}
                                showLabel
                                size="sm"
                            />
                            <span className="tabular-nums font-medium"
                                  style={{ color: available ? 'var(--color-text)' : 'var(--color-text-muted)' }}>
                                {value ?? '—'}
                            </span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

// ─── ActiveSourceChip ──────────────────────────────────────────────────────────
/**
 * Small text chip showing the active source name (e.g. "· Real").
 * Replaces the always-visible six-badge row in Design v2.
 */
function ActiveSourceChip({ source }: { source: MetricSource }) {
    return (
        <span
            className="inline-flex items-center gap-1 text-xs font-medium"
            style={{ color: `var(--color-source-${source}-fg)` }}
            aria-label={`Active source: ${SOURCE_LABELS[source]}`}
        >
            <span style={{ color: 'var(--color-text-muted)' }}>·</span>
            {SOURCE_LABELS[source]}
        </span>
    );
}

// ─── ComparisonSparkline ───────────────────────────────────────────────────────
/**
 * Renders a muted dashed line overlay for the comparison period.
 * Uses the same coordinate system as Sparkline but without fill area.
 * Color = primary series color at 40% opacity, dashed stroke.
 * @see docs/competitors/_research_date_compare.md §3 Chart overlay
 */
function ComparisonSparkline({
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

// ─── MetricCard ────────────────────────────────────────────────────────────────

export interface MetricCardProps {
    /** Metric name with optional qualifier: "Revenue (28d)", "ROAS (blended)" */
    label: string;
    value: string | null;

    /** Which source is currently active (controls sparkline color + chip). */
    activeSource?: MetricSource;
    /**
     * @deprecated Use `activeSource`. Alias kept for backward compat with pre-refactor pages.
     */
    source?: MetricSource | string;
    /** Sources for which data exists. Unlisted sources show as unavailable in popover. */
    availableSources?: MetricSource[];
    /**
     * Per-source values for the compare popover.
     * If not provided, the popover will not render the Info button.
     */
    sourceValues?: Partial<Record<MetricSource, string>>;
    /** Called when user selects a source in the compare popover. */
    onSourceChange?: (source: MetricSource) => void;

    /** Δ% vs comparison period (positive = up). */
    change?: number | null;
    /** True = up is bad (e.g. CPA, refund rate). Inverts green/red. */
    invertTrend?: boolean;

    /** Target for TargetProgress bar beneath value. */
    target?: number | null;
    targetLabel?: string;
    /** @deprecated Pre-refactor alias. */
    targetDirection?: string;

    /** When true the card flips to profit flavor (header shows Zap icon). */
    profitMode?: boolean;

    /** Sub-text shown when there's no change value. */
    subtext?: string;
    /** Formula / source tooltip on hover. */
    tooltip?: string;

    /** Navigate destination when card body is clicked. */
    href?: string;

    /**
     * @deprecated Pre-refactor prop. Use `href` instead.
     */
    actionLine?: string;
    /** @deprecated Pre-refactor prop, use `href` instead. */
    actionHref?: string;

    /**
     * @deprecated Pre-refactor prop. No-op in new MetricCard.
     */
    helpLink?: string;

    /**
     * @deprecated Pre-refactor prop. MetricCard is now a leaf.
     */
    children?: React.ReactNode;

    /** @deprecated Pre-refactor prop — expandable cards no longer exist. */
    expandable?: boolean;

    sparklineData?: { value: number }[];
    /** Comparison sparkline shown as a muted dashed line behind the primary sparkline. */
    comparisonSparklineData?: { value: number }[];

    /**
     * Absolute value for the comparison period (e.g. 108210.10 for "prior 7d").
     * When provided alongside `change`, the delta chip tooltip shows the absolute diff.
     * @see docs/competitors/_research_date_compare.md §4
     */
    comparisonValue?: number | null;
    /**
     * Label for the comparison period shown below the delta chip.
     * Defaults to "vs prior period". Update to "vs prior year" for YoY.
     * @see docs/competitors/_research_date_compare.md §3
     */
    comparisonLabel?: string;

    loading?: boolean;
    className?: string;
}

export const MetricCard = React.memo(function MetricCard({
    label,
    value,
    activeSource = 'real',
    availableSources = ['real', 'store'],
    sourceValues,
    onSourceChange,
    change,
    invertTrend = false,
    target,
    targetLabel,
    profitMode = false,
    subtext,
    tooltip,
    href,
    sparklineData,
    comparisonSparklineData,
    comparisonValue,
    comparisonLabel = 'vs prior period',
    loading = false,
    className,
}: MetricCardProps) {
    const [localSource, setLocalSource] = useState<MetricSource>(activeSource);

    if (loading) {
        return (
            <div
                className={cn('rounded-lg bg-card overflow-hidden', className)}
                style={{
                    minHeight: 'var(--metric-card-min-h)',
                    minWidth: '14rem',
                    border: '1px solid var(--border-subtle)',
                }}
            >
                <div className="p-5 space-y-3">
                    <div className="flex justify-between items-center">
                        <div className="skeleton h-3 w-24" />
                        <div className="skeleton h-3 w-12" />
                    </div>
                    <div className="skeleton h-10 w-32" />
                    <div className="skeleton h-3 w-16" />
                </div>
                <div className="skeleton w-full" style={{ height: 'var(--metric-card-sparkline-h)' }} />
            </div>
        );
    }

    const hasChange = change !== undefined && change !== null;
    const isPositive = hasChange ? (invertTrend ? change < 0 : change > 0) : false;
    const isNegative = hasChange ? (invertTrend ? change > 0 : change < 0) : false;
    const TrendIcon = isPositive ? TrendingUp : isNegative ? TrendingDown : Minus;

    const currentSource = onSourceChange ? activeSource : localSource;

    const handleSourceClick = (src: MetricSource) => {
        if (onSourceChange) {
            onSourceChange(src);
        } else {
            setLocalSource(src);
        }
    };

    const sparkColor = SOURCE_COLOR[currentSource] ?? SOURCE_COLOR.real;
    const hasSparkline = sparklineData && sparklineData.length >= 2;

    const numericValue = value !== null && value !== undefined
        ? parseFloat(value.replace(/[^0-9.-]/g, ''))
        : null;
    const hasTarget = target !== null && target !== undefined && !isNaN(numericValue ?? NaN);

    // Build source values for the popover
    const ALL_SOURCES: MetricSource[] = ['real', 'store', 'facebook', 'google', 'gsc', 'ga4'];
    const popoverSourceValues: SourceValue[] | undefined = sourceValues
        ? ALL_SOURCES.map((src) => ({
            source: src,
            value: sourceValues[src] ?? null,
            available: availableSources.includes(src),
        }))
        : undefined;

    const cardBody = (
        <div
            className={cn(
                'relative flex flex-col rounded-lg overflow-hidden',
                'border transition-colors duration-150',
                'bg-card',
                href && 'cursor-pointer hover:border-[var(--color-primary)] hover:bg-[var(--brand-primary-subtle)]/30',
                !href && 'hover:border-[var(--color-primary)]/40',
                className,
            )}
            style={{
                minHeight: 'var(--metric-card-min-h)',
                minWidth: '14rem',
                borderColor: 'var(--border-subtle)',
            }}
            title={tooltip}
        >
            <div className="flex flex-col gap-2 flex-1 p-5 pb-3">
                {/* Top row: label + profit mode icon + source detail icon */}
                <div className="flex items-center justify-between gap-2">
                    <p className="text-xs font-medium uppercase tracking-wide truncate overflow-hidden"
                       style={{ color: 'var(--color-text-tertiary)' }}>
                        {label}
                    </p>
                    <div className="flex items-center gap-1.5 shrink-0">
                        {profitMode && (
                            <span title="Profit mode active" aria-hidden="true">
                                <Zap className="h-3 w-3" style={{ color: 'var(--color-text-muted)' }} />
                            </span>
                        )}
                        {popoverSourceValues && (
                            <SourceComparePopover
                                sourceValues={popoverSourceValues}
                                activeSource={currentSource}
                                onSourceClick={handleSourceClick}
                            />
                        )}
                    </div>
                </div>

                {/* Value — text-4xl / font-medium / tabular-nums */}
                <div
                    className="font-medium tabular-nums tracking-tight leading-none overflow-hidden text-ellipsis"
                    style={{
                        fontSize: 'var(--text-4xl)',
                        fontVariantNumeric: 'tabular-nums',
                        color: 'var(--color-text)',
                    }}
                >
                    {value ?? 'N/A'}
                </div>

                {/* Delta */}
                <div className="flex items-center gap-1.5 min-h-[18px] overflow-hidden">
                    {hasChange && (
                        <>
                            <span
                                className={cn(
                                    'inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-xs font-medium shrink-0',
                                    isPositive && 'bg-emerald-50 text-emerald-700',
                                    isNegative && 'bg-rose-50 text-rose-700',
                                    !isPositive && !isNegative && 'bg-zinc-100 text-zinc-500',
                                )}
                                title={
                                    comparisonValue !== null && comparisonValue !== undefined
                                        ? `Prior: ${comparisonValue.toLocaleString()}`
                                        : undefined
                                }
                            >
                                <TrendIcon className="h-3 w-3" />
                                {Math.abs(change).toFixed(1)}%
                            </span>
                            <span className="text-xs truncate" style={{ color: 'var(--color-text-muted)' }}>
                                {comparisonLabel}
                            </span>
                        </>
                    )}
                    {subtext && !hasChange && (
                        <span className="text-xs truncate" style={{ color: 'var(--color-text-muted)' }}>
                            {subtext}
                        </span>
                    )}
                </div>

                {/* Target progress bar */}
                {hasTarget && (
                    <TargetProgress
                        current={numericValue!}
                        target={target!}
                        label={targetLabel}
                    />
                )}
            </div>

            {/* Sparkline — edge-to-edge at bottom.
                When comparisonSparklineData is provided, a muted dashed comparison
                line is drawn behind the primary series (Plausible/Polar pattern).  */}
            {hasSparkline && (
                <div
                    className="relative w-full shrink-0"
                    style={{ height: 'var(--metric-card-sparkline-h)' }}
                >
                    {/* Comparison sparkline — drawn first (behind primary) */}
                    {comparisonSparklineData && comparisonSparklineData.length >= 2 && (
                        <div className="absolute inset-0">
                            <ComparisonSparkline
                                data={comparisonSparklineData}
                                color={sparkColor}
                                height={40}
                            />
                        </div>
                    )}
                    {/* Primary sparkline */}
                    <Sparkline
                        data={sparklineData}
                        color={sparkColor}
                        height={40}
                        mode="area"
                        className="w-full"
                    />
                </div>
            )}
        </div>
    );

    if (href) {
        return (
            <Link href={href} className="block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-lg">
                {cardBody}
            </Link>
        );
    }

    return cardBody;
});

// ─── MetricCardCompact ─────────────────────────────────────────────────────────

export interface MetricCardCompactProps {
    label: string;
    value: string | null;
    change?: number | null;
    invertTrend?: boolean;
    activeSource?: MetricSource;
    availableSources?: MetricSource[];
    onSourceChange?: (source: MetricSource) => void;
    loading?: boolean;
    className?: string;
}

export function MetricCardCompact({
    label,
    value,
    change,
    invertTrend = false,
    activeSource = 'real',
    loading = false,
    className,
}: MetricCardCompactProps) {
    if (loading) {
        return (
            <div className={cn('rounded-lg bg-card p-4 space-y-2', className)}
                style={{
                    minHeight: 'var(--metric-card-min-h-compact)',
                    border: '1px solid var(--border-subtle)',
                }}>
                <div className="skeleton h-3 w-20" />
                <div className="skeleton h-6 w-24" />
                <div className="skeleton h-3 w-12" />
            </div>
        );
    }

    const hasChange = change !== undefined && change !== null;
    const isPositive = hasChange ? (invertTrend ? change < 0 : change > 0) : false;
    const isNegative = hasChange ? (invertTrend ? change > 0 : change < 0) : false;
    const TrendIcon = isPositive ? TrendingUp : isNegative ? TrendingDown : Minus;

    return (
        <div
            className={cn(
                'flex flex-col gap-1.5 rounded-lg bg-card p-4 overflow-hidden',
                'transition-colors duration-150',
                className,
            )}
            style={{
                minHeight: 'var(--metric-card-min-h-compact)',
                minWidth: '11rem',
                border: '1px solid var(--border-subtle)',
            }}
        >
            <div className="flex items-center justify-between gap-2">
                <p className="text-xs font-medium uppercase tracking-wide truncate"
                   style={{ color: 'var(--color-text-tertiary)' }}>{label}</p>
            </div>
            <div
                className="font-medium tabular-nums tracking-tight overflow-hidden text-ellipsis"
                style={{
                    fontSize: 'var(--text-2xl)',
                    fontVariantNumeric: 'tabular-nums',
                    color: 'var(--color-text)',
                }}
            >
                {value ?? 'N/A'}
            </div>
            {hasChange && (
                <span
                    className={cn(
                        'inline-flex items-center gap-0.5 w-fit rounded-full px-1.5 py-0.5 text-xs font-medium',
                        isPositive && 'bg-emerald-50 text-emerald-700',
                        isNegative && 'bg-rose-50 text-rose-700',
                        !isPositive && !isNegative && 'bg-zinc-100 text-zinc-500',
                    )}
                >
                    <TrendIcon className="h-3 w-3" />
                    {Math.abs(change).toFixed(1)}%
                </span>
            )}
        </div>
    );
}

// ─── MetricCardDetail ──────────────────────────────────────────────────────────

export interface MetricCardDetailProps {
    label: string;
    value: string | null;
    changeVsPeriod?: number | null;
    changeVsYear?: number | null;
    invertTrend?: boolean;
    activeSource?: MetricSource;
    availableSources?: MetricSource[];
    onSourceChange?: (source: MetricSource) => void;
    /** Per-source values shown in the compare popover. */
    sourceValues?: Partial<Record<MetricSource, string>>;
    sparklineData?: { value: number }[];
    /** Disagreement between Real and best platform — shown as a gap chip */
    disagreementPct?: number | null;
    loading?: boolean;
    className?: string;
}

export function MetricCardDetail({
    label,
    value,
    changeVsPeriod,
    changeVsYear,
    invertTrend = false,
    activeSource = 'real',
    availableSources = ['real', 'store'],
    onSourceChange,
    sourceValues,
    sparklineData,
    disagreementPct,
    loading = false,
    className,
}: MetricCardDetailProps) {
    const [localSource, setLocalSource] = useState<MetricSource>(activeSource);

    if (loading) {
        return (
            <div
                className={cn('rounded-lg bg-card overflow-hidden w-full', className)}
                style={{ border: '1px solid var(--border-subtle)' }}
            >
                <div className="p-6 space-y-3">
                    <div className="flex justify-between">
                        <div className="skeleton h-3 w-32" />
                        <div className="skeleton h-3 w-16" />
                    </div>
                    <div className="skeleton h-9 w-40" />
                    <div className="flex gap-3">
                        <div className="skeleton h-5 w-16" />
                        <div className="skeleton h-5 w-16" />
                    </div>
                </div>
                <div className="skeleton w-full h-10" />
            </div>
        );
    }

    const currentSource = onSourceChange ? activeSource : localSource;
    const sparkColor = SOURCE_COLOR[currentSource] ?? SOURCE_COLOR.real;
    const hasSparkline = sparklineData && sparklineData.length >= 2;

    const ALL_SOURCES: MetricSource[] = ['real', 'store', 'facebook', 'google', 'gsc', 'ga4'];
    const popoverSourceValues: SourceValue[] | undefined = sourceValues
        ? ALL_SOURCES.map((src) => ({
            source: src,
            value: sourceValues[src] ?? null,
            available: availableSources.includes(src),
        }))
        : undefined;

    const DeltaChip = ({ change, label: deltaLabel }: { change: number; label: string }) => {
        const isPositive = invertTrend ? change < 0 : change > 0;
        const isNegative = invertTrend ? change > 0 : change < 0;
        const TrendIcon = isPositive ? TrendingUp : isNegative ? TrendingDown : Minus;
        return (
            <div className="flex items-center gap-1">
                <span className={cn(
                    'inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-xs font-medium',
                    isPositive && 'bg-emerald-50 text-emerald-700',
                    isNegative && 'bg-rose-50 text-rose-700',
                    !isPositive && !isNegative && 'bg-zinc-100 text-zinc-500',
                )}>
                    <TrendIcon className="h-3 w-3" />
                    {Math.abs(change).toFixed(1)}%
                </span>
                <span className="text-xs text-zinc-400">{deltaLabel}</span>
            </div>
        );
    };

    return (
        <div
            className={cn('rounded-lg bg-card overflow-hidden w-full', className)}
            style={{ border: '1px solid var(--border-subtle)' }}
        >
            <div className="flex flex-col gap-2 p-6 pb-4">
                {/* Top row: label + source detail icon */}
                <div className="flex items-center justify-between gap-2">
                    <p className="text-xs font-medium uppercase tracking-wide"
                       style={{ color: 'var(--color-text-tertiary)' }}>{label}</p>
                    <div className="flex items-center gap-1.5 shrink-0">
                        {popoverSourceValues && (
                            <SourceComparePopover
                                sourceValues={popoverSourceValues}
                                activeSource={currentSource}
                                onSourceClick={(src) => {
                                    if (onSourceChange) onSourceChange(src);
                                    else setLocalSource(src);
                                }}
                            />
                        )}
                    </div>
                </div>
                <div className="flex items-baseline gap-3 overflow-hidden">
                    <div
                        className="font-medium tabular-nums tracking-tight overflow-hidden text-ellipsis"
                        style={{
                            fontSize: 'var(--text-3xl)',
                            fontVariantNumeric: 'tabular-nums',
                            color: 'var(--color-text)',
                        }}
                    >
                        {value ?? 'N/A'}
                    </div>
                </div>
                <div className="flex flex-wrap items-center gap-3">
                    {changeVsPeriod !== null && changeVsPeriod !== undefined && (
                        <DeltaChip change={changeVsPeriod} label="vs prior period" />
                    )}
                    {changeVsYear !== null && changeVsYear !== undefined && (
                        <DeltaChip change={changeVsYear} label="vs prior year" />
                    )}
                </div>
            </div>
            {hasSparkline && (
                <div className="w-full" style={{ height: 'var(--metric-card-sparkline-h)' }}>
                    <Sparkline
                        data={sparklineData}
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

// ─── MetricCardMultiValue ──────────────────────────────────────────────────────

export interface MetricCardMultiValueProps {
    label: string;
    /** Three values: e.g. ["$48", "$42", "$38"] for [Mean, Median, Mode] */
    values: [string, string, string];
    /** Labels for each value, e.g. ["Mean", "Median", "Mode"] */
    valueLabels?: [string, string, string];
    activeSource?: MetricSource;
    availableSources?: MetricSource[];
    loading?: boolean;
    className?: string;
}

export function MetricCardMultiValue({
    label,
    values,
    valueLabels = ['Mean', 'Median', 'Mode'],
    activeSource = 'real',
    loading = false,
    className,
}: MetricCardMultiValueProps) {
    if (loading) {
        return (
            <div className={cn('rounded-lg bg-card p-6', className)}
                style={{ minHeight: 'var(--metric-card-min-h)', border: '1px solid var(--border-subtle)' }}>
                <div className="skeleton h-3 w-28 mb-4" />
                <div className="grid grid-cols-3 gap-4">
                    {[0, 1, 2].map((i) => (
                        <div key={i} className="space-y-1.5">
                            <div className="skeleton h-3 w-12" />
                            <div className="skeleton h-6 w-16" />
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div
            className={cn('flex flex-col gap-4 rounded-lg bg-card p-6', className)}
            style={{ minHeight: 'var(--metric-card-min-h)', border: '1px solid var(--border-subtle)' }}
        >
            <div className="flex items-center justify-between">
                <p className="text-xs font-medium uppercase tracking-wide"
                   style={{ color: 'var(--color-text-tertiary)' }}>{label}</p>
            </div>
            <div className="grid grid-cols-3 gap-4">
                {values.map((val, i) => (
                    <div key={i}>
                        <p className="text-xs mb-1" style={{ color: 'var(--color-text-muted)' }}>{valueLabels[i]}</p>
                        <p
                            className="font-medium tabular-nums"
                            style={{
                                fontSize: 'var(--text-lg)',
                                fontVariantNumeric: 'tabular-nums',
                                color: 'var(--color-text)',
                            }}
                        >
                            {val}
                        </p>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ─── MetricCardPortfolio ───────────────────────────────────────────────────────

export interface WorkspaceContribution {
    id: number;
    name: string;
    value: number;
    formattedValue: string;
    sparklineData?: { value: number }[];
}

export interface MetricCardPortfolioProps {
    label: string;
    totalValue: string | null;
    workspaces: WorkspaceContribution[];
    change?: number | null;
    loading?: boolean;
    className?: string;
}

// Deterministic color palette for workspace segments (hashed by index).
const WORKSPACE_COLORS = [
    'oklch(0.53 0.18 60)',   /* amber  */
    'oklch(0.42 0.21 254)',  /* blue   */
    'oklch(0.40 0.12 182)',  /* teal   */
    'oklch(0.42 0.20 295)',  /* violet */
    'oklch(0.40 0.17 155)',  /* emerald*/
    'oklch(0.44 0.04 257)',  /* slate  */
    'oklch(0.50 0.19 25)',   /* rose   */
];

export function MetricCardPortfolio({
    label,
    totalValue,
    workspaces,
    change,
    loading = false,
    className,
}: MetricCardPortfolioProps) {
    if (loading) {
        return (
            <div className={cn('rounded-lg bg-card p-6', className)}
                style={{ minHeight: 140, border: '1px solid var(--border-subtle)' }}>
                <div className="skeleton h-3 w-28 mb-4" />
                <div className="skeleton h-9 w-32 mb-4" />
                <div className="skeleton h-3 w-full rounded-full" />
            </div>
        );
    }

    const total = workspaces.reduce((sum, w) => sum + w.value, 0) || 1;
    const visible = workspaces.slice(0, 6);
    const overflow = workspaces.slice(6);

    const hasChange = change !== undefined && change !== null;
    const isPositive = hasChange && change > 0;
    const isNegative = hasChange && change < 0;

    return (
        <div
            className={cn('flex flex-col gap-3 rounded-lg bg-card p-6', className)}
            style={{ minHeight: 140, border: '1px solid var(--border-subtle)' }}
        >
            <p className="text-xs font-medium uppercase tracking-wide"
               style={{ color: 'var(--color-text-tertiary)' }}>{label}</p>

            <div className="flex items-baseline gap-2">
                <div
                    className="font-medium tabular-nums tracking-tight overflow-hidden text-ellipsis"
                    style={{
                        fontSize: 'var(--text-3xl)',
                        fontVariantNumeric: 'tabular-nums',
                        color: 'var(--color-text)',
                    }}
                >
                    {totalValue ?? 'N/A'}
                </div>
                {hasChange && (
                    <span className={cn(
                        'text-xs font-medium',
                        isPositive && 'text-emerald-600',
                        isNegative && 'text-rose-600',
                    )}>
                        {isPositive ? '+' : ''}{change.toFixed(1)}%
                    </span>
                )}
            </div>

            {/* Contribution bar */}
            <div
                className="flex h-2 w-full overflow-hidden rounded-full gap-0.5"
                title="Workspace contributions"
            >
                {visible.map((w, i) => {
                    const pct = (w.value / total) * 100;
                    return (
                        <div
                            key={w.id}
                            style={{
                                width: `${pct}%`,
                                backgroundColor: WORKSPACE_COLORS[i % WORKSPACE_COLORS.length],
                                borderRadius: 2,
                            }}
                            title={`${w.name}: ${w.formattedValue} (${pct.toFixed(1)}%)`}
                        />
                    );
                })}
                {overflow.length > 0 && (
                    <div
                        style={{
                            flex: 1,
                            backgroundColor: 'oklch(0.87 0 0)',
                            borderRadius: 2,
                        }}
                        title={`Other (${overflow.length} more workspaces)`}
                    />
                )}
            </div>

            {/* Per-workspace micro sparklines */}
            <div className="flex gap-1">
                {visible.slice(0, 5).map((w, i) => {
                    if (!w.sparklineData || w.sparklineData.length < 2) return null;
                    return (
                        <div key={w.id} className="flex-1" style={{ height: 24 }}>
                            <Sparkline
                                data={w.sparklineData}
                                color={WORKSPACE_COLORS[i % WORKSPACE_COLORS.length]}
                                height={24}
                                mode="line"
                                className="w-full"
                            />
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

// Keep SourceBadge re-export for legacy callers.
export { SourceBadge };
