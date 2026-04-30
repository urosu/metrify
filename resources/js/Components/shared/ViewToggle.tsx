import { cn } from '@/lib/utils';

interface Option<T extends string> {
    label: string;
    value: T;
}

interface ViewToggleProps<T extends string> {
    options: Option<T>[];
    value: T;
    onChange: (v: T) => void;
}

export function ViewToggle<T extends string>({ options, value, onChange }: ViewToggleProps<T>) {
    return (
        <div className="inline-flex rounded-lg border border-border bg-muted p-0.5">
            {options.map((opt) => (
                <button
                    key={opt.value}
                    onClick={() => onChange(opt.value)}
                    className={cn(
                        'rounded-md px-3 py-1.5 text-xs font-medium transition-colors min-h-[44px] sm:min-h-0',
                        value === opt.value
                            ? 'bg-card text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    {opt.label}
                </button>
            ))}
        </div>
    );
}

export { ViewToggle as ToggleGroup };
