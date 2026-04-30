/**
 * SourceBadge — a single pill representing one of the 6 canonical data sources.
 *
 * Sources: Store · Facebook · Google (Ads) · GSC · GA4 · Real
 *
 * Active:   filled bg (source tint) + source border + source fg text.
 * Inactive: source border + source fg text, transparent bg.
 * Disabled: dashed muted border, muted bg, muted-foreground text.
 *
 * Source colours are canonical — see UX §4. Do NOT reassign.
 *
 * @see docs/UX.md §4 Source colors
 * @see docs/UX.md §5.2 SourceBadge
 * @see docs/UX.md §5.1 MetricCard (source badge row)
 */
import { Activity, Lightbulb, Megaphone, Search, ShoppingCart, TrendingUp } from 'lucide-react';
import { cn } from '@/lib/utils';

/** Canonical v1 sources: store · facebook · google · gsc · ga4 · real. */
export type MetricSource = 'store' | 'facebook' | 'google' | 'gsc' | 'ga4' | 'real';

interface SourceBadgeProps {
    source: MetricSource;
    active?: boolean;
    /** When true, the badge renders greyed-out. */
    disabled?: boolean;
    /** Tooltip shown on hover when disabled. */
    disabledTooltip?: string;
    showLabel?: boolean;
    size?: 'sm' | 'md';
    onClick?: () => void;
    className?: string;
}

const SOURCE_TOOLTIP: Record<MetricSource, string> = {
    store:    'Store — what your Shopify/WooCommerce database recorded.',
    facebook: 'Facebook — what Meta Marketing API claims it drove.',
    google:   'Google Ads — what Google Ads API claims it drove.',
    gsc:      'GSC — search clicks reported by Google Search Console.',
    ga4:      'GA4 — sessions and attribution from Google Analytics 4 / first-party data.',
    real:     "Real — Nexstage's reconciled lens. Anchors on store-recorded orders, cross-referenced with platform data.",
};

const SOURCE_CONFIG: Record<
    MetricSource,
    {
        label: string;
        color: string;
        fgColor: string;
        borderColor: string;
        Icon: React.ComponentType<{ className?: string }>;
    }
> = {
    store:    { label: 'Store',    color: 'var(--color-source-store)',    fgColor: 'var(--color-source-store-fg)',    borderColor: 'var(--color-source-store-border)',    Icon: ShoppingCart },
    facebook: { label: 'Facebook', color: 'var(--color-source-facebook)', fgColor: 'var(--color-source-facebook-fg)', borderColor: 'var(--color-source-facebook-border)', Icon: Megaphone },
    google:   { label: 'Google',   color: 'var(--color-source-google)',   fgColor: 'var(--color-source-google-fg)',   borderColor: 'var(--color-source-google-border)',   Icon: Search },
    gsc:      { label: 'GSC',      color: 'var(--color-source-gsc)',      fgColor: 'var(--color-source-gsc-fg)',      borderColor: 'var(--color-source-gsc-border)',      Icon: TrendingUp },
    ga4:      { label: 'GA4',      color: 'var(--color-source-ga4)',      fgColor: 'var(--color-source-ga4-fg)',      borderColor: 'var(--color-source-ga4-border)',      Icon: Activity },
    real:     { label: 'Real',     color: 'var(--color-source-real)',     fgColor: 'var(--color-source-real-fg)',     borderColor: 'var(--color-source-real-border)',     Icon: Lightbulb },
};

/** Maps arbitrary source strings to canonical MetricSource values. */
const SOURCE_STRING_MAP: Record<string, MetricSource> = {
    store:                 'store',
    shopify:               'store',
    woocommerce:           'store',
    facebook:              'facebook',
    facebook_ads:          'facebook',
    meta:                  'facebook',
    google:                'google',
    google_ads:            'google',
    gsc:                   'gsc',
    google_search_console: 'gsc',
    ga4:                   'ga4',
    google_analytics:      'ga4',
    first_party:           'ga4',
    real:                  'real',
};

export function SourceBadgeFromString({
    source,
    ...props
}: Omit<SourceBadgeProps, 'source'> & { source: string }) {
    const canonical = SOURCE_STRING_MAP[source.toLowerCase().replace(/-/g, '_')];
    if (!canonical) return null;
    return <SourceBadge source={canonical} {...props} />;
}

export function SourceBadge({
    source,
    active = false,
    disabled = false,
    disabledTooltip,
    showLabel = true,
    size = 'sm',
    onClick,
    className,
}: SourceBadgeProps) {
    const { label, color, fgColor, borderColor, Icon } = SOURCE_CONFIG[source];

    const sizeClasses = size === 'sm'
        ? 'px-2 py-1 text-xs min-h-[28px]'
        : 'px-2.5 py-1 text-xs min-h-[28px]';

    const iconClass = size === 'sm' ? 'h-3 w-3' : 'h-3.5 w-3.5';
    const isClickable = !!onClick;
    const isDisabledClickable = disabled && isClickable;

    const inlineStyle: React.CSSProperties = (() => {
        if (disabled) return {};
        if (active) {
            return {
                backgroundColor: color,
                borderColor: borderColor,
                color: fgColor,
            };
        }
        return {
            borderColor: borderColor,
            color: fgColor,
        };
    })();

    return (
        <span
            role={isClickable ? 'button' : undefined}
            tabIndex={isClickable ? 0 : undefined}
            onClick={isClickable ? onClick : undefined}
            onKeyDown={isClickable ? (e) => { if (e.key === 'Enter' || e.key === ' ') onClick!(); } : undefined}
            title={disabled ? (disabledTooltip ?? `${label} not connected`) : SOURCE_TOOLTIP[source]}
            aria-disabled={disabled || undefined}
            className={cn(
                'inline-flex items-center gap-1 rounded-full border select-none transition-colors',
                sizeClasses,
                disabled && 'border-dashed border-border bg-muted text-muted-foreground',
                disabled && !isDisabledClickable && 'cursor-not-allowed',
                isDisabledClickable && 'cursor-pointer hover:bg-muted/80',
                active && !disabled && 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1',
                isClickable && !disabled && 'cursor-pointer hover:bg-muted/60',
                className,
            )}
            style={inlineStyle}
        >
            <Icon className={iconClass} />
            {showLabel && label}
        </span>
    );
}
