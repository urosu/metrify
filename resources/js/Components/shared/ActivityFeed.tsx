import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { formatRelativeTime } from '@/lib/formatters';
import { StatusDot, StatusType } from '@/Components/shared/StatusDot';

export type ActivityEventType = 'order' | 'refund' | 'annotation' | 'sync' | 'alert';

export interface ActivityEvent {
  id: string | number;
  type: ActivityEventType | string;
  /** Primary label — accepts both `title` (shared) and `description` (Dashboard inline). */
  title?: string;
  /** Alias for title used in Dashboard's inline shape. */
  description?: string;
  subtitle?: string;
  value?: string;
  /** ISO timestamp — accepts both `timestamp` (shared) and `occurred_at` (Dashboard inline). */
  timestamp?: string;
  occurred_at?: string;
  href?: string;
  /** Order-specific fields carried from Dashboard's inline shape. */
  amount?: number | null;
  attribution_source?: string | null;
  payment_method?: string | null;
}

export interface ActivityFeedProps {
  events: ActivityEvent[];
  maxItems?: number;
  loading?: boolean;
  className?: string;
}

const TYPE_STATUS: Record<ActivityEventType, StatusType> = {
  order:      'success',
  refund:     'error',
  annotation: 'inactive',
  sync:       'pending',
  alert:      'warning',
};

export function ActivityFeed({ events, maxItems = 20, loading = false, className }: ActivityFeedProps) {
  const displayed = events.slice(0, maxItems);

  if (loading) {
    return (
      <div className={cn('space-y-3', className)}>
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="flex items-start gap-3 animate-pulse">
            <div className="mt-1 h-2 w-2 rounded-full bg-muted shrink-0" />
            <div className="flex-1 space-y-1.5">
              <div className="h-3 w-3/4 rounded bg-muted/50" />
              <div className="h-3 w-1/2 rounded bg-muted/50" />
            </div>
          </div>
        ))}
      </div>
    );
  }

  return (
    <div className={cn('space-y-1', className)}>
      {displayed.map((event) => {
        const label = event.title ?? event.description ?? '';
        const ts = event.timestamp ?? event.occurred_at ?? '';
        const statusKey = (event.type as ActivityEventType) in TYPE_STATUS
          ? (event.type as ActivityEventType)
          : 'annotation';

        const content = (
          <div className="flex items-start gap-3 rounded-md px-2 py-1.5 hover:bg-muted/50 transition-colors">
            <div className="mt-1 shrink-0">
              <StatusDot status={TYPE_STATUS[statusKey]} />
            </div>
            <div className="min-w-0 flex-1">
              <p className="text-sm font-medium text-foreground truncate">{label}</p>
              {event.subtitle && (
                <p className="text-sm text-muted-foreground truncate">{event.subtitle}</p>
              )}
            </div>
            <div className="shrink-0 text-right">
              {event.value && (
                <p className="text-sm font-medium text-foreground">{event.value}</p>
              )}
              {ts && <p className="text-sm text-muted-foreground/70">{formatRelativeTime(ts)}</p>}
            </div>
          </div>
        );

        return event.href ? (
          <Link key={event.id} href={event.href} className="block">
            {content}
          </Link>
        ) : (
          <div key={event.id}>{content}</div>
        );
      })}
    </div>
  );
}
