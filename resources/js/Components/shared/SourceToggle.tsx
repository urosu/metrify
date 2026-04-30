/**
 * SourceToggle — TopBar chip-group for the 6 canonical sources.
 *
 * Each source renders as a pill (SourceBadge) that toggles on/off.
 * When a source is active (matches the `value` prop) its badge is filled;
 * when inactive it is outlined and dimmed.
 *
 * Unlike a multi-select, only one source can be active at a time — the active
 * source is the global *lens* that all MetricCards use. Clicking the already-
 * active source is a no-op (lens cannot be deselected; default is 'real').
 *
 * When `availableSources` is provided, only those sources are rendered as chips.
 * If only real+store are available, a single inert "+ Connect ads / GSC" chip is
 * appended so the merchant knows more lenses exist and where to enable them.
 *
 * When `availableSources` is NOT provided, the legacy behaviour applies: all 6
 * sources are shown, with disabled+tooltip chips for unconnected integrations.
 *
 * Canonical set: Store · Facebook · Google · GSC · GA4 · Real.
 *
 * @see docs/UX.md §7.0.1 Global selector stack
 * @see docs/UX.md §5.2 SourceBadge
 * @see docs/planning/frontend.md §4 Phase 4 — global chrome
 */
import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { SourceBadge, MetricSource } from '@/Components/shared/SourceBadge';
import { wurl } from '@/lib/workspace-url';

export type SourceKey = MetricSource;

/**
 * Canonical display order: Real first (default lens), then per-source drill-downs.
 * Real anchors the row because it is the active default lens — users land on the
 * reconciled view, then click a per-source badge to drill into one source's claim.
 *
 * Naming note: "Real" is the current label per CLAUDE.md and the source-disagreement
 * thesis. A future rename (e.g. "Nexstage") is on the table — see docs/decisions/
 * 001-source-disagreement-as-thesis.md for the open discussion.
 */
const ALL_SOURCES: SourceKey[] = ['real', 'store', 'facebook', 'google', 'gsc', 'ga4'];

export interface SourceToggleProps {
  value: SourceKey;
  onChange: (source: SourceKey) => void;
  /**
   * When provided, only these sources render as chips (filtered view).
   * The active source always renders regardless, so the current lens is visible.
   * If only real+store are in the list, an inert "+ Connect ads / GSC" chip is shown.
   * When omitted, falls back to the legacy behaviour: all 6 sources with disabled
   * chips for unconnected integrations.
   */
  availableSources?: MetricSource[];
  /** Availability flags from metricSources shared prop (legacy / fallback mode). */
  metricSources?: {
    has_ga4?: boolean;
    has_facebook?: boolean;
    has_google?: boolean;
    has_gsc?: boolean;
  } | null;
  /** When provided, clicking a disabled badge (legacy mode) or the connect chip navigates here. */
  connectHref?: string;
  disabled?: boolean;
  className?: string;
}

export function SourceToggle({
  value,
  onChange,
  availableSources,
  metricSources,
  connectHref,
  disabled = false,
  className,
}: SourceToggleProps) {
  // ── Filtered mode (availableSources provided) ───────────────────────────
  if (availableSources !== undefined) {
    // Always include the active source so the current lens is visible even if
    // the caller forgot to include it in availableSources.
    const visibleSet = new Set<SourceKey>(availableSources);
    if (value) visibleSet.add(value);

    // Preserve canonical display order (Real leftmost).
    const visibleSources = ALL_SOURCES.filter((s) => visibleSet.has(s));

    const onlyBaselineSources =
      availableSources.length <= 2 &&
      availableSources.every((s) => s === 'real' || s === 'store');

    return (
      <div
        className={cn('inline-flex items-center gap-1', className)}
        role="group"
        aria-label="Active source lens"
      >
        {visibleSources.map((source) => (
          <SourceBadge
            key={source}
            source={source}
            active={value === source}
            disabled={disabled}
            showLabel
            size="sm"
            onClick={disabled || value === source ? undefined : () => onChange(source)}
          />
        ))}

        {/* When only real+store are connected, nudge the merchant to add more lenses. */}
        {onlyBaselineSources && (
          <button
            type="button"
            onClick={connectHref ? () => router.visit(connectHref) : undefined}
            className={cn(
              'inline-flex items-center gap-1 rounded-full border border-dashed border-muted-foreground/40',
              'px-2.5 py-0.5 text-xs text-muted-foreground/60 transition-colors',
              connectHref
                ? 'cursor-pointer hover:border-muted-foreground/70 hover:text-muted-foreground'
                : 'cursor-default',
            )}
            title={connectHref ? 'Go to Integrations to add more data sources' : undefined}
          >
            + Connect ads / GSC
          </button>
        )}
      </div>
    );
  }

  // ── Legacy mode (availableSources NOT provided) ─────────────────────────
  // Shows all 6 sources with disabled+tooltip chips for unconnected integrations.
  function isSourceDisabled(source: SourceKey): boolean {
    if (disabled) return true;
    if (!metricSources) return false;
    switch (source) {
      case 'ga4':      return !metricSources.has_ga4;
      case 'facebook': return !metricSources.has_facebook;
      case 'google':   return !metricSources.has_google;
      case 'gsc':      return !metricSources.has_gsc;
      default:         return false;
    }
  }

  function disabledTooltip(source: SourceKey): string | undefined {
    switch (source) {
      case 'ga4':      return 'GA4 not connected — click to go to Integrations';
      case 'facebook': return 'Facebook Ads not connected — click to go to Integrations';
      case 'google':   return 'Google Ads not connected — click to go to Integrations';
      case 'gsc':      return 'Search Console not connected — click to go to Integrations';
      default:         return undefined;
    }
  }

  return (
    <div
      className={cn('inline-flex items-center gap-1', className)}
      role="group"
      aria-label="Active source lens"
    >
      {ALL_SOURCES.map((source) => {
        const sourceDisabled = isSourceDisabled(source);
        // Disabled badges navigate to integrations so the merchant can connect the source.
        const handleClick = sourceDisabled
          ? connectHref
            ? () => router.visit(connectHref)
            : undefined
          : value === source
          ? undefined
          : () => onChange(source);
        return (
          <SourceBadge
            key={source}
            source={source}
            active={value === source}
            disabled={sourceDisabled}
            disabledTooltip={disabledTooltip(source)}
            showLabel
            size="sm"
            onClick={handleClick}
          />
        );
      })}
    </div>
  );
}
