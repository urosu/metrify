// Table convention (canonical baseline — all hand-rolled tables in Pages/**/*.tsx must match):
//   Wrapper:  overflow-x-auto; table gets min-w-[Npx] to prevent viewport overflow
//   <thead>:  className="bg-muted/50 border-b border-border"   (sticky top-0 z-10 bg-card when inside DataTable)
//   <tr> hdr: className="text-xs font-semibold text-muted-foreground uppercase tracking-wide"
//   <th>:     className="px-4 py-2.5 text-left|text-right"     (compact: px-3 py-2; admin: px-2 py-1.5)
//   <tbody>:  className="divide-y divide-border"
//   <tr> row: className="hover:bg-muted/50 transition-colors"   (skip for heatmap/color-coded rows)
//   <td>:     className="px-4 py-2.5 text-sm text-foreground"  (numeric: + tabular-nums text-right)
//   Truncate: max-w-[Npx] on <td> + <span class="block truncate" title={fullText}>
//   Density:  normal px-4 py-2.5 | compact px-3 py-2 (Inventory, Orders) | ultra-dense px-2 py-1.5 (Admin)
import { useState, useMemo } from 'react';
import { ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { EmptyState } from '@/Components/shared/EmptyState';

export interface Column<T> {
    key: keyof T & string;
    header: string;
    sortable?: boolean;
    editable?: boolean;
    render?: (value: unknown, row: T) => React.ReactNode;
    width?: number;
}

interface DataTableProps<T extends { id: string | number }> {
    columns: Column<T>[];
    data: T[];
    loading?: boolean;
    emptyMessage?: string;
    emptyDescription?: string;
    onRowClick?: (row: T) => void;
    /** Return extra class names to apply to a given data row (e.g. for row-level tinting). */
    rowClassName?: (row: T) => string | undefined;
    onCellEdit?: (rowId: string | number, key: string, value: unknown) => void;
    defaultSort?: { key: string; dir: 'asc' | 'desc' };
    className?: string;
}

type SortState = { key: string; dir: 'asc' | 'desc' } | null;

export function DataTable<T extends { id: string | number }>({
    columns,
    data,
    loading,
    emptyMessage,
    emptyDescription,
    onRowClick,
    rowClassName,
    onCellEdit,
    defaultSort,
    className,
}: DataTableProps<T>) {
    const [sort, setSort] = useState<SortState>(defaultSort ?? null);

    const sortedData = useMemo(() => {
        if (!sort) return data;
        return [...data].sort((a, b) => {
            const av = a[sort.key as keyof T];
            const bv = b[sort.key as keyof T];
            if (av === bv) return 0;
            const cmp = av == null ? -1 : bv == null ? 1 : av < bv ? -1 : 1;
            return sort.dir === 'asc' ? cmp : -cmp;
        });
    }, [data, sort]);

    const handleSort = (key: string) => {
        setSort((prev) => {
            if (!prev || prev.key !== key) return { key, dir: 'asc' };
            if (prev.dir === 'asc') return { key, dir: 'desc' };
            return null;
        });
    };

    const SortIcon = ({ colKey }: { colKey: string }) => {
        if (!sort || sort.key !== colKey) return <ChevronsUpDown className="ml-1 inline h-3.5 w-3.5 text-muted-foreground/70" />;
        if (sort.dir === 'asc') return <ChevronUp className="ml-1 inline h-3.5 w-3.5 text-foreground" />;
        return <ChevronDown className="ml-1 inline h-3.5 w-3.5 text-foreground" />;
    };

    return (
        <div className={cn('w-full overflow-auto', className)}>
            <table className="w-full border-collapse">
                <thead className="sticky top-0 z-10 bg-card">
                    <tr className="border-b border-border">
                        {columns.map((col) => (
                            <th
                                key={col.key}
                                style={col.width ? { width: col.width } : undefined}
                                className={cn(
                                    'px-4 py-3 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide select-none',
                                )}
                                aria-sort={
                                    col.sortable
                                        ? sort?.key === col.key
                                            ? sort.dir === 'asc' ? 'ascending' : 'descending'
                                            : 'none'
                                        : undefined
                                }
                            >
                                {col.sortable ? (
                                    <button
                                        onClick={() => handleSort(col.key)}
                                        className="flex items-center hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded"
                                    >
                                        {col.header}
                                        <SortIcon colKey={col.key} />
                                    </button>
                                ) : (
                                    <>
                                        {col.header}
                                    </>
                                )}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-border">
                    {loading ? (
                        Array.from({ length: 5 }).map((_, i) => (
                            <tr key={i}>
                                {columns.map((col) => (
                                    <td key={col.key} className="px-4 py-3">
                                        <div className="h-4 w-full animate-pulse rounded bg-muted" />
                                    </td>
                                ))}
                            </tr>
                        ))
                    ) : sortedData.length === 0 ? (
                        <tr>
                            <td colSpan={columns.length} className="px-4 py-12">
                                <EmptyState title={emptyMessage ?? 'No results'} description={emptyDescription} />
                            </td>
                        </tr>
                    ) : (
                        sortedData.map((row) => (
                            <tr
                                key={row.id}
                                onClick={onRowClick ? () => onRowClick(row) : undefined}
                                className={cn(
                                    'hover:bg-muted/50 transition-colors',
                                    onRowClick && 'cursor-pointer',
                                    rowClassName?.(row),
                                )}
                            >
                                {columns.map((col) => (
                                    <td key={col.key} className="px-4 py-3 text-sm text-foreground">
                                        {col.render
                                            ? col.render(row[col.key as keyof T], row)
                                            : String(row[col.key as keyof T] ?? '')}
                                    </td>
                                ))}
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
