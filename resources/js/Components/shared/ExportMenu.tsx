import { Download, FileText, Table } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export interface ExportMenuLinks {
  csv?: string;
  xlsx?: string;
  pdf?: string;
}

export interface ExportMenuProps {
  /** Callback-based variant — call a handler on click. */
  onExportCsv?: () => void;
  onExportXlsx?: () => void;
  /** Href-based variant — direct download links (no Promise wrap required). */
  links?: ExportMenuLinks;
  className?: string;
}

export function ExportMenu({ onExportCsv, onExportXlsx, links, className }: ExportMenuProps) {
  const hasCsv  = !!(onExportCsv  || links?.csv);
  const hasXlsx = !!(onExportXlsx || links?.xlsx);
  const hasPdf  = !!(links?.pdf);

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        className={cn(
          'inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-2.5 py-1.5 text-sm font-medium text-foreground shadow-sm hover:bg-muted focus:outline-none',
          className,
        )}
      >
        <Download className="h-3.5 w-3.5" />
        Export
      </DropdownMenuTrigger>
      <DropdownMenuContent className="w-44">
        {hasCsv && (
          <DropdownMenuItem
            onClick={() => {
              if (links?.csv) window.location.href = links.csv;
              else onExportCsv?.();
            }}
            className={cn(!hasCsv && 'opacity-40 cursor-not-allowed')}
          >
            <FileText className="mr-2 h-4 w-4" />
            Export CSV
          </DropdownMenuItem>
        )}
        {hasXlsx && (
          <DropdownMenuItem
            onClick={() => {
              if (links?.xlsx) window.location.href = links.xlsx;
              else onExportXlsx?.();
            }}
            className={cn(!hasXlsx && 'opacity-40 cursor-not-allowed')}
          >
            <Table className="mr-2 h-4 w-4" />
            Export Excel (.xlsx)
          </DropdownMenuItem>
        )}
        {hasPdf && (
          <DropdownMenuItem
            onClick={() => {
              if (links?.pdf) window.location.href = links.pdf;
            }}
          >
            <FileText className="mr-2 h-4 w-4" />
            Export PDF
          </DropdownMenuItem>
        )}
        {!hasCsv && !hasXlsx && !hasPdf && (
          <DropdownMenuItem disabled className="opacity-40 cursor-not-allowed">
            <FileText className="mr-2 h-4 w-4" />
            No exports available
          </DropdownMenuItem>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
