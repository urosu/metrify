import { cn } from '@/lib/utils';
import { SourceBadge, MetricSource } from './SourceBadge';

interface TouchpointStringProps {
    sources: MetricSource[];
    maxVisible?: number;
    className?: string;
}

export function TouchpointString({ sources, maxVisible = 4, className }: TouchpointStringProps) {
    const visible = sources.slice(0, maxVisible);
    const overflow = sources.length - visible.length;

    return (
        <span className={cn('inline-flex items-center gap-1 flex-wrap', className)}>
            {visible.map((source, i) => (
                <span key={`${source}-${i}`} className="inline-flex items-center gap-1">
                    {i > 0 && (
                        <span className="text-muted-foreground/60 text-xs select-none">→</span>
                    )}
                    <SourceBadge source={source} size="sm" active showLabel={false} />
                </span>
            ))}
            {overflow > 0 && (
                <span className="text-xs text-muted-foreground/60">+{overflow}</span>
            )}
        </span>
    );
}
