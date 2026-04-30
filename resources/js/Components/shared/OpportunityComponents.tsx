import { TrendingUp, AlertTriangle } from 'lucide-react';
import { cn } from '@/lib/utils';

/** A single GSC query opportunity row. @see docs/pages/seo.md */
export interface OpportunityItem {
    query: string;
    clicks: number;
    impressions: number;
    ctr: number | null;
    position: number | null;
    opportunity: string | null;
}

interface OpportunityPanelProps {
    trendingUp: OpportunityItem[];
    needsAttention: OpportunityItem[];
    className?: string;
}

/** Two-column panel showing rising queries and queries needing attention. */
export function OpportunityPanel({ trendingUp, needsAttention, className }: OpportunityPanelProps) {
    if (trendingUp.length === 0 && needsAttention.length === 0) return null;

    return (
        <div className={cn('mb-6 grid gap-4 sm:grid-cols-2', className)}>
            {trendingUp.length > 0 && (
                <div className="rounded-xl border border-border bg-card p-4">
                    <div className="mb-3 flex items-center gap-1.5 text-sm font-medium text-emerald-700">
                        <TrendingUp className="h-4 w-4" />
                        Trending up
                    </div>
                    <ul className="space-y-2">
                        {trendingUp.slice(0, 5).map((item) => (
                            <OpportunityRow key={item.query} item={item} />
                        ))}
                    </ul>
                </div>
            )}
            {needsAttention.length > 0 && (
                <div className="rounded-xl border border-border bg-card p-4">
                    <div className="mb-3 flex items-center gap-1.5 text-sm font-medium text-amber-700">
                        <AlertTriangle className="h-4 w-4" />
                        Needs attention
                    </div>
                    <ul className="space-y-2">
                        {needsAttention.slice(0, 5).map((item) => (
                            <OpportunityRow key={item.query} item={item} />
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}

function OpportunityRow({ item }: { item: OpportunityItem }) {
    return (
        <li className="flex items-start justify-between gap-2 text-xs">
            <span className="truncate text-foreground" title={item.query}>{item.query}</span>
            {item.opportunity && <OpportunityBadge type={item.opportunity} />}
        </li>
    );
}

interface OpportunityBadgeProps {
    type: string | null;
    className?: string;
}

const OPPORTUNITY_CONFIG: Record<string, { label: string; className: string }> = {
    trending_up:      { label: 'Trending up',      className: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    needs_attention:  { label: 'Needs attention',  className: 'bg-amber-50 text-amber-700 border-amber-200' },
    low_hanging:      { label: 'Low hanging fruit', className: 'bg-sky-50 text-sky-700 border-sky-200' },
    high_impression:  { label: 'High impressions',  className: 'bg-violet-50 text-violet-700 border-violet-200' },
};

/** Small pill badge indicating the opportunity type for a GSC query. */
export function OpportunityBadge({ type, className }: OpportunityBadgeProps) {
    if (!type) return null;
    const cfg = OPPORTUNITY_CONFIG[type] ?? { label: type, className: 'bg-muted/50 text-muted-foreground border-border' };
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border px-1.5 py-px text-[10px] font-medium whitespace-nowrap',
                cfg.className,
                className,
            )}
        >
            {cfg.label}
        </span>
    );
}
