/**
 * DateCompareToolbar — wraps DateRangePicker and surfaces the active comparison
 * mode as a dismissable badge next to the date trigger.
 *
 * Responsibilities:
 *   1. Render <DateRangePicker /> (owns comparison chip UI internally).
 *   2. Show a "vs <label>" dismissable pill when a comparison range is active.
 *   3. Expose the active `cmp_mode` via URL param (`previous_period | yoy | custom`).
 *      The mode is inferred from the URL params when the page loads; when the
 *      user applies via DateRangePicker the hook writes `compare_from`/`compare_to`.
 *
 * URL params owned:
 *   from, to, granularity — primary range (via useDateRange)
 *   compare_from, compare_to — comparison range dates
 *   cmp_mode — 'previous_period' | 'yoy' | 'custom' (informational; display label)
 *
 * Backward-compat: existing DateRangePicker callers are unaffected. This component
 * is an opt-in wrapper; it does NOT replace DateRangePicker.
 *
 * Competitor patterns:
 *   - Polar Analytics: dismissable "vs Previous period ×" pill next to date trigger.
 *   - Northbeam: "vs Prior 7d" chip appended to primary date in toolbar.
 *
 * @see docs/competitors/_research_date_compare.md
 * @see docs/UX.md §3 Global chrome — DateRangePicker
 */

import React, { useCallback } from 'react';
import { X } from 'lucide-react';
import { format } from 'date-fns';
import { useDateRange } from '@/Hooks/useDateRange';
import { DateRangePicker } from '@/Components/shared/DateRangePicker';
import { cn } from '@/lib/utils';

// ─── helpers ──────────────────────────────────────────────────────────────────

function parseIso(str: string): Date {
    const [y, m, d] = str.split('-').map(Number);
    return new Date(y, m - 1, d);
}

function formatShort(dateStr: string): string {
    return format(parseIso(dateStr), 'MMM d');
}

/**
 * Derive a human-readable comparison label from the URL's compare dates.
 * We infer the mode from the date math rather than storing a separate param,
 * so the label is always accurate even after preset changes.
 */
function deriveCompareLabel(
    primaryFrom: string,
    primaryTo: string,
    compareFrom: string,
    compareTo: string,
): string {
    const pf = parseIso(primaryFrom);
    const pt = parseIso(primaryTo);
    const cf = parseIso(compareFrom);
    const ct = parseIso(compareTo);

    const primaryDays = Math.round((pt.getTime() - pf.getTime()) / 86400000) + 1;
    const compareDays = Math.round((ct.getTime() - cf.getTime()) / 86400000) + 1;

    // YoY: compare range is exactly 1 year before primary
    const isYoy =
        cf.getFullYear() === pf.getFullYear() - 1 &&
        cf.getMonth() === pf.getMonth() &&
        cf.getDate() === pf.getDate();

    if (isYoy) return 'vs prior year';

    // Previous period: same length, immediately before
    const isPrevPeriod =
        compareDays === primaryDays &&
        Math.round((pf.getTime() - cf.getTime()) / 86400000) === primaryDays;

    if (isPrevPeriod) return 'vs prior period';

    // Custom: show date range
    return `vs ${formatShort(compareFrom)}–${formatShort(compareTo)}`;
}

// ─── ComparisonBadge ──────────────────────────────────────────────────────────

interface ComparisonBadgeProps {
    label: string;
    onDismiss: () => void;
}

function ComparisonBadge({ label, onDismiss }: ComparisonBadgeProps) {
    return (
        <span
            className="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium"
            style={{
                borderColor: 'var(--color-primary)',
                backgroundColor: 'var(--brand-primary-subtle)',
                color: 'var(--color-primary)',
            }}
            aria-label={`Comparison active: ${label}`}
        >
            {label}
            <button
                type="button"
                onClick={onDismiss}
                className="flex items-center justify-center rounded-full transition-opacity hover:opacity-70"
                aria-label="Remove comparison"
                style={{ color: 'var(--color-primary)' }}
            >
                <X className="h-3 w-3" />
            </button>
        </span>
    );
}

// ─── DateCompareToolbar ───────────────────────────────────────────────────────

export interface DateCompareToolbarProps {
    className?: string;
}

/**
 * Full date-and-comparison toolbar for the global TopBar.
 *
 * Renders:
 *   <DateRangePicker /> · [ComparisonBadge when active]
 *
 * Dismiss button on the badge clears compare_from / compare_to from URL + storage.
 */
export function DateCompareToolbar({ className }: DateCompareToolbarProps) {
    const { range, setRange } = useDateRange();
    const hasComparison = !!(range.compare_from && range.compare_to);

    const comparisonLabel = hasComparison
        ? deriveCompareLabel(
              range.from,
              range.to,
              range.compare_from!,
              range.compare_to!,
          )
        : null;

    const handleDismiss = useCallback(() => {
        setRange({
            from: range.from,
            to: range.to,
            granularity: range.granularity,
            compare_from: undefined,
            compare_to: undefined,
        });
    }, [range, setRange]);

    return (
        <div className={cn('flex items-center gap-2', className)}>
            <DateRangePicker />
            {hasComparison && comparisonLabel && (
                <ComparisonBadge
                    label={comparisonLabel}
                    onDismiss={handleDismiss}
                />
            )}
        </div>
    );
}
