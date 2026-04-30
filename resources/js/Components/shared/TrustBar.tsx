/**
 * TrustBar — compact per-source attribution strip.
 *
 * A slim horizontal strip showing each source's attributed value as a
 * clickable cell. Neutral framing — values speak for themselves.
 * Used on Attribution page as the per-platform overview section.
 *
 * Design v3: demoted from primary chrome. No gold/amber, no "vs Store" deltas,
 * no "reconciliation" headline. Visible only when explicitly invoked from a
 * page section's filter chip or the Attribution tab.
 *
 * @see docs/UX.md §5.14 TrustBar
 * @see docs/competitors/_research_polaris_pivot.md §6
 */
import { cn } from '@/lib/utils';
import { SourceBadge, MetricSource } from '@/Components/shared/SourceBadge';

export interface TrustBarSourceItem {
  source: MetricSource;
  value: number;
  formatted: string;
  available: boolean;
}

export interface TrustBarProps {
  revenue: TrustBarSourceItem[];
  /** Total orders across all sources (highest count wins). */
  orders: number;
  currency?: string;
  activeSource?: MetricSource;
  onSourceChange?: (s: MetricSource) => void;
  className?: string;
}

export function TrustBar({
  revenue,
  orders,
  activeSource,
  onSourceChange,
  className,
}: TrustBarProps) {
  return (
    <div
      className={cn(
        'flex items-stretch overflow-hidden rounded-lg bg-card',
        className,
      )}
      style={{
        border: '1px solid var(--border-subtle)',
        /* Dividers between cells are border-subtle */
      }}
      role="group"
      aria-label="Revenue by source"
    >
      {revenue.map(({ source, value: _value, formatted, available }, idx) => {
        const isActive = source === activeSource;

        return (
          <button
            key={source}
            type="button"
            onClick={available && onSourceChange ? () => onSourceChange(source) : undefined}
            className={cn(
              'flex-1 min-w-0 flex flex-col items-start px-3 py-2.5 transition-colors duration-150 text-left',
              available && onSourceChange ? 'cursor-pointer' : 'cursor-default',
            )}
            style={{
              backgroundColor: isActive ? 'var(--brand-primary-subtle)' : undefined,
              borderLeft: idx > 0 ? '1px solid var(--border-subtle)' : undefined,
            }}
            aria-pressed={isActive}
            disabled={!available}
          >
            <div className="mb-1">
              <SourceBadge
                source={source}
                active={isActive && available}
                disabled={!available}
                showLabel
                size="sm"
              />
            </div>
            <span
              className="text-sm font-medium tabular-nums"
              style={{
                fontVariantNumeric: 'tabular-nums',
                color: available ? 'var(--color-text)' : 'var(--color-text-muted)',
              }}
            >
              {available ? formatted : '—'}
            </span>
          </button>
        );
      })}

      {/* Orders total — trailing summary cell */}
      <div
        className="flex flex-col items-start px-3 py-2.5 shrink-0"
        style={{ borderLeft: '1px solid var(--border-subtle)' }}
      >
        <span className="text-xs font-medium mb-1" style={{ color: 'var(--color-text-muted)' }}>Orders</span>
        <span
          className="text-sm font-medium tabular-nums"
          style={{ fontVariantNumeric: 'tabular-nums', color: 'var(--color-text)' }}
        >
          {orders.toLocaleString()}
        </span>
      </div>
    </div>
  );
}
