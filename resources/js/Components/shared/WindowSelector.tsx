/**
 * WindowSelector — TopBar dropdown for picking the attribution window.
 *
 * Sits in the TopBar center filter stack, right of AttributionModelSelector
 * and left of AccountingModeSelector.
 * Default: '7d-click-1d-view' (matches Meta default; avoids complaints).
 * Options follow Facebook/Google Ads conventions.
 *
 * @see docs/UX.md §7.0.1 Global selector stack
 * @see docs/UX.md §7 Defaults
 * @see docs/planning/frontend.md §4 Phase 4 — global chrome
 */
import { ChevronDown, Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export type AttributionWindow =
  | '1d-click'
  | '7d-click'
  | '28d-click'
  | '1d-view'
  | '7d-click-1d-view'
  | '28d-click-1d-view';

export interface WindowSelectorProps {
  value: AttributionWindow;
  onChange: (window: AttributionWindow) => void;
  disabled?: boolean;
  className?: string;
}

const WINDOW_LABELS: Record<AttributionWindow, string> = {
  '1d-click':           '1d click',
  '7d-click':           '7d click',
  '28d-click':          '28d click',
  '1d-view':            '1d view',
  '7d-click-1d-view':   '7d click / 1d view',
  '28d-click-1d-view':  '28d click / 1d view',
};

const ALL_WINDOWS: AttributionWindow[] = [
  '1d-click',
  '7d-click',
  '28d-click',
  '1d-view',
  '7d-click-1d-view',
  '28d-click-1d-view',
];

export function WindowSelector({
  value,
  onChange,
  disabled = false,
  className,
}: WindowSelectorProps) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        disabled={disabled}
        className={cn(
          'inline-flex items-center gap-1.5 rounded-md border border-border bg-white px-2.5 py-1.5 text-xs font-medium text-foreground shadow-sm hover:bg-muted/50 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed',
          className,
        )}
      >
        {WINDOW_LABELS[value]}
        <ChevronDown className="h-3.5 w-3.5 text-muted-foreground/60" />
      </DropdownMenuTrigger>
      <DropdownMenuContent className="w-48">
        {ALL_WINDOWS.map((win) => {
          const isActive = value === win;
          return (
            <DropdownMenuItem
              key={win}
              onClick={() => onChange(win)}
              className="cursor-pointer"
            >
              <span className="flex-1">{WINDOW_LABELS[win]}</span>
              {isActive && <Check className="h-3.5 w-3.5 text-primary" />}
            </DropdownMenuItem>
          );
        })}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
