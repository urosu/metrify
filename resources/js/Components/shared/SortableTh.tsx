import { SortButton } from './SortButton';
import { InfoTooltip } from './Tooltip';
import { cn } from '@/lib/utils';

interface SortableThProps {
    col: string;
    label: string;
    currentSort: string;
    currentDir: 'asc' | 'desc';
    onSort: (col: string) => void;
    /** Passed to the <th> element — use text-right / text-left for alignment. */
    className?: string;
    tooltip?: string;
}

/**
 * Sortable table header cell.
 * Renders a <th> containing a SortButton with optional InfoTooltip.
 * Pass text-right via className for right-aligned columns.
 */
export function SortableTh({
    col,
    label,
    currentSort,
    currentDir,
    onSort,
    className,
    tooltip,
}: SortableThProps) {
    return (
        <th className={cn('px-4 py-3 select-none', className)}>
            <span className="inline-flex items-center gap-1">
                <SortButton
                    col={col}
                    currentSort={currentSort}
                    currentDir={currentDir}
                    onSort={onSort}
                >
                    {label}
                </SortButton>
                {tooltip && <InfoTooltip content={tooltip} />}
            </span>
        </th>
    );
}
