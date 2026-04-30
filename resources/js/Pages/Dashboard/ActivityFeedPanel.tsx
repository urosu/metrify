/**
 * ActivityFeedPanel — Dashboard chronological commerce event stream.
 *
 * Wraps ActivityFeed with a section header showing auto-refresh cadence
 * (pattern from Triple Whale Live Orders / UX §5.24).
 *
 * Shows up to 10 events: orders, refunds, syncs, alerts.
 * Each row: type dot · title · subtitle · value · relative timestamp.
 *
 * @see docs/UX.md §5.24 ActivityFeed
 * @see docs/competitors/_teardown_triple-whale.md Screen: Pixel / Live Orders
 * @see docs/pages/dashboard.md ActivityFeed
 */
import { RefreshCw } from 'lucide-react';
import { cn } from '@/lib/utils';
import { ActivityFeed, type ActivityEvent } from '@/Components/shared/ActivityFeed';

interface ActivityFeedPanelProps {
    events: ActivityEvent[];
    className?: string;
}

export function ActivityFeedPanel({ events, className }: ActivityFeedPanelProps) {
    return (
        <div className={cn('rounded-xl border border-border bg-card p-4', className)}>
            {/* Header with auto-refresh indicator (Triple Whale Live Orders pattern) */}
            <div className="flex items-center justify-between mb-4">
                <p className="text-sm font-semibold text-foreground">Activity</p>
                <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <RefreshCw className="h-3 w-3" aria-hidden="true" />
                    <span>Auto-refresh · every 10s</span>
                </div>
            </div>

            <ActivityFeed
                events={events}
                maxItems={10}
            />
        </div>
    );
}
