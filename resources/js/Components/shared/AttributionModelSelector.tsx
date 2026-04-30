/**
 * AttributionModelSelector — TopBar dropdown for picking the attribution model.
 *
 * Sits in the TopBar center filter stack, left of WindowSelector.
 * Changing this value triggers a retroactive recalc on every MetricCard, chart,
 * and table on the page (Klaviyo-style "Recomputing…" banner handled by the page).
 *
 * @see docs/UX.md §7.0.1 Global selector stack
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

export type AttributionModel =
  | 'last-click'
  | 'last-non-direct-click'
  | 'first-click'
  | 'linear'
  | 'position-based'
  | 'time-decay'
  | 'data-driven';

export interface AttributionModelSelectorProps {
  value: AttributionModel;
  onChange: (model: AttributionModel) => void;
  disabled?: boolean;
  className?: string;
}

const MODEL_LABELS: Record<AttributionModel, string> = {
  'last-click':            'Last Click',
  'last-non-direct-click': 'Last Non-Direct Click',
  'first-click':           'First Click',
  'linear':                'Linear',
  'position-based':        'Position Based',
  'time-decay':            'Time Decay',
  'data-driven':           'Data Driven',
};

const ALL_MODELS: AttributionModel[] = [
  'last-click',
  'last-non-direct-click',
  'first-click',
  'linear',
  'position-based',
  'time-decay',
  'data-driven',
];

export function AttributionModelSelector({
  value,
  onChange,
  disabled = false,
  className,
}: AttributionModelSelectorProps) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        disabled={disabled}
        className={cn(
          'inline-flex items-center gap-1.5 rounded-md border border-border bg-white px-2.5 py-1.5 text-xs font-medium text-foreground shadow-sm hover:bg-muted/50 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed',
          className,
        )}
      >
        {MODEL_LABELS[value]}
        <ChevronDown className="h-3.5 w-3.5 text-muted-foreground/60" />
      </DropdownMenuTrigger>
      <DropdownMenuContent className="w-52">
        {ALL_MODELS.map((model) => {
          const isActive = value === model;
          return (
            <DropdownMenuItem
              key={model}
              onClick={() => onChange(model)}
              className="cursor-pointer"
            >
              <span className="flex-1">{MODEL_LABELS[model]}</span>
              {isActive && <Check className="h-3.5 w-3.5 text-primary" />}
            </DropdownMenuItem>
          );
        })}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
