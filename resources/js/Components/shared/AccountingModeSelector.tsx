import { cn } from '@/lib/utils';

export type AccountingMode = 'accrual' | 'cash';

export interface AccountingModeSelectorProps {
  value: AccountingMode;
  onChange: (m: AccountingMode) => void;
  className?: string;
}

export function AccountingModeSelector({ value, onChange, className }: AccountingModeSelectorProps) {
  return (
    <div className={cn('inline-flex rounded-lg border border-border bg-muted p-0.5', className)}>
      {(['accrual', 'cash'] as const).map((mode) => (
        <button
          key={mode}
          onClick={() => onChange(mode)}
          className={cn(
            'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
            value === mode ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
          )}
        >
          {mode.charAt(0).toUpperCase() + mode.slice(1)}
        </button>
      ))}
    </div>
  );
}
