/**
 * BreakdownSelector — TopBar dropdown for grouping the entire page by a dimension.
 *
 * Canonical dimensions: None · Country · Channel · Campaign · Ad set · Ad · Product
 *   · Device · Customer segment · Platform · Search Appearance · Page
 *
 * Each page provides an `allowedBreakdowns` allowlist. Disabled dimensions show
 * a tooltip explaining why (e.g. "Not available on this page").
 *
 * URL-stateful via `?breakdown=country`. Changing triggers a full Inertia partial reload.
 *
 * @see docs/UX.md §5.15 BreakdownSelector
 */
import { useState } from 'react';
import { ChevronDown, Check } from 'lucide-react';
import { cn } from '@/lib/utils';

export type BreakdownDimension =
    | null
    | 'country'
    | 'channel'
    | 'campaign'
    | 'adset'
    | 'ad'
    | 'product'
    | 'device'
    | 'customer_segment'
    | 'platform'
    | 'search_appearance'
    | 'page';

const DIMENSION_LABELS: Record<NonNullable<BreakdownDimension>, string> = {
    country:            'Country',
    channel:            'Channel',
    campaign:           'Campaign',
    adset:              'Ad set',
    ad:                 'Ad',
    product:            'Product',
    device:             'Device',
    customer_segment:   'Customer segment',
    platform:           'Platform',
    search_appearance:  'Search Appearance',
    page:               'Page',
};

const ALL_DIMENSIONS: BreakdownDimension[] = [
    null, 'country', 'channel', 'campaign', 'adset', 'ad',
    'product', 'device', 'customer_segment', 'platform', 'search_appearance', 'page',
];

interface BreakdownSelectorProps {
    value: BreakdownDimension;
    onChange: (dimension: BreakdownDimension) => void;
    /** Only these dimensions are enabled. Others are shown disabled with tooltip. */
    allowedBreakdowns?: BreakdownDimension[];
    disabled?: boolean;
    className?: string;
}

export function BreakdownSelector({
    value,
    onChange,
    allowedBreakdowns,
    disabled = false,
    className,
}: BreakdownSelectorProps) {
    const [open, setOpen] = useState(false);

    const label = value ? DIMENSION_LABELS[value] : 'No breakdown';

    return (
        <div className={cn('relative', className)}>
            <button
                type="button"
                onClick={() => !disabled && setOpen((v) => !v)}
                disabled={disabled}
                className={cn(
                    'inline-flex items-center gap-1.5 rounded-md border border-border bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-700',
                    'hover:bg-zinc-50 transition-colors',
                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                    disabled && 'cursor-not-allowed opacity-50',
                    value && 'border-zinc-400 bg-zinc-50',
                )}
                aria-haspopup="listbox"
                aria-expanded={open}
            >
                <span>Breakdown:</span>
                <span className={cn('font-semibold', value && 'text-zinc-900')}>{label}</span>
                <ChevronDown
                    className={cn('h-3 w-3 text-zinc-400 transition-transform', open && 'rotate-180')}
                    aria-hidden="true"
                />
            </button>

            {open && (
                <>
                    <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} />
                    <div
                        className="absolute top-full left-0 z-20 mt-1 w-48 rounded-lg border border-border bg-white py-1"
                        style={{ boxShadow: 'var(--shadow-raised)' }}
                        role="listbox"
                        aria-label="Breakdown dimension"
                    >
                        {ALL_DIMENSIONS.map((dim) => {
                            const dimLabel = dim ? DIMENSION_LABELS[dim] : 'None';
                            const isAllowed = !allowedBreakdowns || allowedBreakdowns.includes(dim);
                            const isActive = value === dim;

                            return (
                                <button
                                    key={dim ?? 'none'}
                                    role="option"
                                    aria-selected={isActive}
                                    disabled={!isAllowed}
                                    onClick={() => {
                                        if (isAllowed) {
                                            onChange(dim);
                                            setOpen(false);
                                        }
                                    }}
                                    title={!isAllowed ? 'Not available on this page' : undefined}
                                    className={cn(
                                        'flex w-full items-center justify-between px-3 py-1.5 text-sm transition-colors',
                                        isActive
                                            ? 'bg-zinc-900 text-white font-medium'
                                            : 'text-zinc-700 hover:bg-zinc-50',
                                        !isAllowed && 'cursor-not-allowed opacity-40',
                                    )}
                                >
                                    {dimLabel}
                                    {isActive && <Check className="h-3.5 w-3.5" />}
                                </button>
                            );
                        })}
                    </div>
                </>
            )}
        </div>
    );
}
