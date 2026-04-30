import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

interface FilterChip {
    key: string;
    label: string;
    value: string;
    removable?: boolean;
}

interface FilterChipSentenceProps {
    entity?: string;
    chips: FilterChip[];
    onRemove?: (key: string) => void;
    onAdd?: () => void;
    className?: string;
}

export function FilterChipSentence({
    entity,
    chips,
    onRemove,
    onAdd,
    className,
}: FilterChipSentenceProps) {
    const dateChips = chips.filter((c) => c.key === 'date' || c.key === 'range');
    const filterChips = chips.filter((c) => c.key !== 'date' && c.key !== 'range');

    return (
        <div className={cn('flex flex-wrap items-center gap-1 text-sm text-muted-foreground', className)}>
            <span>Showing</span>

            {entity && (
                <span className="font-medium text-foreground">{entity}</span>
            )}

            {dateChips.length > 0 && (
                <>
                    <span>from</span>
                    {dateChips.map((chip) => (
                        <Chip key={chip.key} chip={chip} onRemove={onRemove} />
                    ))}
                </>
            )}

            {filterChips.length > 0 && (
                <>
                    <span>where</span>
                    {filterChips.map((chip, i) => (
                        <span key={chip.key} className="inline-flex items-center gap-1">
                            {i > 0 && <span className="text-muted-foreground/70">and</span>}
                            <Chip chip={chip} onRemove={onRemove} />
                        </span>
                    ))}
                </>
            )}

            {onAdd && (
                <button
                    onClick={onAdd}
                    className="text-xs text-primary hover:text-primary/80 font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded"
                >
                    + Add filter
                </button>
            )}
        </div>
    );
}

function Chip({ chip, onRemove }: { chip: FilterChip; onRemove?: (key: string) => void }) {
    return (
        <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-muted text-xs font-medium text-foreground">
            <span>{chip.label}: {chip.value}</span>
            {chip.removable && onRemove && (
                <button
                    onClick={() => onRemove(chip.key)}
                    className="flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-0 sm:min-w-0 text-muted-foreground/70 hover:text-muted-foreground transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded"
                    aria-label={`Remove ${chip.label} filter`}
                >
                    <X className="h-3 w-3" />
                </button>
            )}
        </span>
    );
}
