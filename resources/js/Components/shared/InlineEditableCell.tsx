import { useState, useRef, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Pencil, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

interface InlineEditableCellProps {
    value: string | number;
    /**
     * Callback-based save (Promise variant).
     * Mutually exclusive with `routerPatch`.
     */
    onSave?: (newValue: string) => Promise<void>;
    /**
     * Router-native variant — calls `router.patch(url, data)` directly.
     * `dataKey` is the field name in the PATCH body; defaults to 'value'.
     * Mutually exclusive with `onSave`.
     */
    routerPatch?: { url: string; dataKey?: string };
    type?: 'text' | 'number';
    prefix?: string;
    className?: string;
}

type CellState = 'view' | 'editing' | 'saving' | 'error';

export function InlineEditableCell({
    value,
    onSave,
    routerPatch,
    type = 'text',
    prefix,
    className,
}: InlineEditableCellProps) {
    const [state, setState] = useState<CellState>('view');
    const [draft, setDraft] = useState(String(value));
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (state === 'editing') {
            inputRef.current?.select();
        }
    }, [state]);

    const startEditing = () => {
        setDraft(String(value));
        setState('editing');
    };

    const commit = async () => {
        if (draft === String(value)) {
            setState('view');
            return;
        }
        setState('saving');

        if (routerPatch) {
            const dataKey = routerPatch.dataKey ?? 'value';
            router.patch(routerPatch.url, { [dataKey]: draft }, {
                preserveScroll: true,
                onSuccess: () => setState('view'),
                onError: () => {
                    setState('error');
                    setTimeout(() => { setDraft(String(value)); setState('view'); }, 1200);
                },
            });
            return;
        }

        if (!onSave) { setState('view'); return; }

        try {
            await onSave(draft);
            setState('view');
        } catch {
            setState('error');
            setTimeout(() => {
                setDraft(String(value));
                setState('view');
            }, 1200);
        }
    };

    const cancel = () => {
        setDraft(String(value));
        setState('view');
    };

    if (state === 'saving') {
        return (
            <span className={cn('inline-flex items-center gap-1.5 text-sm text-muted-foreground', className)}>
                {prefix && <span>{prefix}</span>}
                <span>{draft}</span>
                <Loader2 className="h-3.5 w-3.5 animate-spin text-muted-foreground/70" />
            </span>
        );
    }

    if (state === 'editing' || state === 'error') {
        return (
            <span className={cn('inline-flex items-center gap-1', className)}>
                {prefix && <span className="text-sm text-muted-foreground">{prefix}</span>}
                <input
                    ref={inputRef}
                    type={type}
                    value={draft}
                    onChange={(e) => setDraft(e.target.value)}
                    onBlur={commit}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') { e.preventDefault(); commit(); }
                        if (e.key === 'Escape') { e.preventDefault(); cancel(); }
                    }}
                    className={cn(
                        'rounded border px-2 py-0.5 text-sm outline-none focus:ring-1 focus:ring-ring',
                        state === 'error' ? 'border-red-400 ring-1 ring-red-300' : 'border-input',
                    )}
                />
            </span>
        );
    }

    return (
        <span
            role="button"
            tabIndex={0}
            onClick={startEditing}
            onKeyDown={(e) => e.key === 'Enter' && startEditing()}
            className={cn('group inline-flex cursor-pointer items-center gap-1.5 text-sm text-foreground', className)}
        >
            {prefix && <span>{prefix}</span>}
            <span>{value}</span>
            <Pencil className="h-3 w-3 text-muted-foreground/50 opacity-0 transition-opacity group-hover:opacity-100" />
        </span>
    );
}
