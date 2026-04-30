import React, { useState } from 'react';
import { AlertTriangle, TrendingDown, Activity, Lightbulb, MessageSquare, X, BellOff, HelpCircle, ExternalLink } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export type TriageItemType = 'sync_failure' | 'not_tracked' | 'anomaly' | 'opportunity' | 'note';

export interface TriageItem {
  id: number;
  type: TriageItemType;
  title: string;
  description?: string;
  severity: 'info' | 'warning' | 'critical';
  created_at: string;
  action_href?: string;
  action_label?: string;
  /** Entity context for grouping: "campaign", "product", "channel", etc. */
  entity_type?: string;
  entity_label?: string;
}

export interface TriageInboxProps {
  items: TriageItem[];
  compact?: boolean;
  onDismiss?: (id: number) => void;
  onSnooze?: (id: number) => void;
  className?: string;
  /** When true, related alerts are collapsed under a single entity heading. */
  groupByEntity?: boolean;
}

const TYPE_ICONS: Record<TriageItemType, React.ComponentType<{ className?: string }>> = {
  sync_failure: AlertTriangle,
  not_tracked:  TrendingDown,
  anomaly:      Activity,
  opportunity:  Lightbulb,
  note:         MessageSquare,
};

const SEVERITY_CLASSES: Record<string, { border: string; icon: string; bg: string }> = {
  info:     { border: 'border-blue-200',  icon: 'text-blue-500',  bg: 'bg-blue-50' },
  warning:  { border: 'border-amber-200', icon: 'text-amber-500', bg: 'bg-amber-50' },
  critical: { border: 'border-rose-200',  icon: 'text-rose-500',  bg: 'bg-rose-50' },
};

/** Modal that explains why the alert fired. */
function WhyFiredModal({ item, onClose }: { item: TriageItem; onClose: () => void }) {
  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      onClick={onClose}
    >
      <div
        className="relative w-full max-w-md rounded-xl border border-border bg-background shadow-xl"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-start justify-between gap-3 border-b border-border px-5 py-4">
          <div className="flex items-center gap-2">
            <HelpCircle className="h-4 w-4 text-primary shrink-0" />
            <h2 className="text-sm font-semibold text-foreground">Why this fired</h2>
          </div>
          <button
            onClick={onClose}
            className="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted/50 transition-colors"
            aria-label="Close"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        <div className="space-y-4 px-5 py-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">Alert</p>
            <p className="text-sm font-medium text-foreground">{item.title}</p>
          </div>

          {item.description && (
            <div>
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">Details</p>
              <p className="text-sm text-muted-foreground leading-relaxed">{item.description}</p>
            </div>
          )}

          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-1">Fired</p>
            <p className="text-sm text-muted-foreground">
              {new Date(item.created_at).toLocaleString(undefined, {
                dateStyle: 'medium', timeStyle: 'short',
              })}
            </p>
          </div>

          {item.action_href && item.action_label && (
            <div className="pt-2 border-t border-border">
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">What to do next</p>
              <Link
                href={item.action_href}
                className="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                onClick={onClose}
              >
                {item.action_label}
                <ExternalLink className="h-3 w-3" />
              </Link>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

function TriageItemRow({
  item,
  onDismiss,
  onSnooze,
}: {
  item: TriageItem;
  onDismiss?: (id: number) => void;
  onSnooze?: (id: number) => void;
}) {
  const Icon = TYPE_ICONS[item.type];
  const sev = SEVERITY_CLASSES[item.severity];
  const [whyOpen, setWhyOpen] = useState(false);

  return (
    <>
      <div className={cn('flex items-start gap-3 rounded-lg border p-3', sev.border, sev.bg)}>
        <Icon className={cn('mt-1 h-4 w-4 shrink-0', sev.icon)} aria-hidden="true" />
        <div className="min-w-0 flex-1">
          <p className="text-sm font-medium text-foreground">{item.title}</p>
          {item.description && (
            <p className="mt-0.5 text-sm text-muted-foreground">{item.description}</p>
          )}
          <div className="mt-1.5 flex flex-wrap items-center gap-3">
            {item.action_href && item.action_label && (
              <Link
                href={item.action_href}
                className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
              >
                {item.action_label}
                <ExternalLink className="h-3 w-3" />
              </Link>
            )}
            {/* "Why this fired" — always available, gives context even without a deep link */}
            <button
              onClick={() => setWhyOpen(true)}
              className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors"
            >
              <HelpCircle className="h-3 w-3" />
              Why this fired
            </button>
          </div>
        </div>
        {/* Action buttons — min 44px touch target via p-2 */}
        <div className="flex shrink-0 items-center gap-0.5">
          {onSnooze && (
            <button
              onClick={() => onSnooze(item.id)}
              className="rounded p-2 text-muted-foreground hover:text-foreground hover:bg-black/5 transition-colors"
              aria-label="Snooze for 24 hours"
            >
              <BellOff className="h-4 w-4" />
            </button>
          )}
          {onDismiss && (
            <button
              onClick={() => onDismiss(item.id)}
              className="rounded p-2 text-muted-foreground hover:text-foreground hover:bg-black/5 transition-colors"
              aria-label="Dismiss alert"
            >
              <X className="h-4 w-4" />
            </button>
          )}
        </div>
      </div>
      {whyOpen && <WhyFiredModal item={item} onClose={() => setWhyOpen(false)} />}
    </>
  );
}

/** Group key for an item — uses entity_type+entity_label when present, else "ungrouped". */
function groupKey(item: TriageItem): string {
  if (item.entity_type && item.entity_label) {
    return `${item.entity_type}::${item.entity_label}`;
  }
  return '';
}

/** Grouped row: collapses ≥2 same-entity alerts behind an expandable header. */
function EntityGroup({
  entityLabel,
  entityType,
  items,
  onDismiss,
  onSnooze,
}: {
  entityLabel: string;
  entityType: string;
  items: TriageItem[];
  onDismiss?: (id: number) => void;
  onSnooze?: (id: number) => void;
}) {
  const [expanded, setExpanded] = useState(true);
  const criticalCount = items.filter((i) => i.severity === 'critical').length;

  return (
    <div className="rounded-lg border border-border overflow-hidden">
      {/* Group header */}
      <button
        type="button"
        onClick={() => setExpanded((v) => !v)}
        className="w-full flex items-center justify-between gap-2 bg-muted/40 px-3 py-2 text-left hover:bg-muted/60 transition-colors"
      >
        <div className="flex items-center gap-2">
          <span className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{entityType}</span>
          <span className="text-sm font-medium text-foreground">{entityLabel}</span>
          {criticalCount > 0 && (
            <span className="rounded-full bg-rose-100 px-1.5 py-0.5 text-xs font-semibold text-rose-700">
              {criticalCount} critical
            </span>
          )}
          <span className="rounded-full bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">
            {items.length} alert{items.length !== 1 ? 's' : ''}
          </span>
        </div>
        <span className="text-muted-foreground text-xs">{expanded ? '▲' : '▼'}</span>
      </button>
      {/* Group items */}
      {expanded && (
        <div className="divide-y divide-border">
          {items.map((item) => (
            <div key={item.id} className="p-2">
              <TriageItemRow item={item} onDismiss={onDismiss} onSnooze={onSnooze} />
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

export function TriageInbox({ items, compact = false, onDismiss, onSnooze, className, groupByEntity = false }: TriageInboxProps) {
  const displayed = compact ? items.slice(0, 3) : items;
  const remaining = compact ? items.length - 3 : 0;

  if (items.length === 0) return null;

  // When groupByEntity is active, partition items by entity key.
  if (groupByEntity && !compact) {
    // Build groups — preserve insertion order (items are already severity-sorted by server).
    const groups: Map<string, TriageItem[]> = new Map();
    for (const item of displayed) {
      const key = groupKey(item);
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key)!.push(item);
    }

    return (
      <div className={cn('space-y-3', className)}>
        {Array.from(groups.entries()).map(([key, groupItems]) => {
          if (key === '' || groupItems.length === 1) {
            // Single items / ungrouped render as flat rows.
            return groupItems.map((item) => (
              <TriageItemRow key={item.id} item={item} onDismiss={onDismiss} onSnooze={onSnooze} />
            ));
          }
          const first = groupItems[0];
          return (
            <EntityGroup
              key={key}
              entityType={first.entity_type!}
              entityLabel={first.entity_label!}
              items={groupItems}
              onDismiss={onDismiss}
              onSnooze={onSnooze}
            />
          );
        })}
      </div>
    );
  }

  return (
    <div className={cn('space-y-2', className)}>
      {displayed.map((item) => (
        <TriageItemRow key={item.id} item={item} onDismiss={onDismiss} onSnooze={onSnooze} />
      ))}
      {compact && remaining > 0 && (
        <p className="text-sm text-muted-foreground">
          +{remaining} more item{remaining !== 1 ? 's' : ''}
        </p>
      )}
    </div>
  );
}
