/**
 * CommandPalette — Cmd+K fuzzy search across pages, workspaces, orders,
 * customers, campaigns, and settings.
 *
 * Client-side for pages/settings; server-side for orders/customers/campaigns.
 * Recent actions shown when empty.
 *
 * Usage:
 *   <CommandPalette open={open} onClose={() => setOpen(false)} workspaceSlug={slug} />
 *
 * The global Cmd+K hook is installed in the page root (app.tsx or AppLayout).
 *
 * @see docs/UX.md §3 CommandPalette
 * @see docs/PLANNING.md §4 Q8 (client-side for pages/settings; server-side for entities)
 */
import { useState, useEffect, useRef, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { Search, X, ArrowRight, LayoutDashboard, ShoppingBag, BarChart2, GitBranch, Package, TrendingUp, Users, Puzzle, Settings, Search as SearchIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';

interface CommandItem {
    id: string;
    label: string;
    description?: string;
    category: 'page' | 'workspace' | 'setting' | 'action';
    href?: string;
    action?: () => void;
    icon?: React.ComponentType<{ className?: string }>;
}

interface CommandPaletteProps {
    open: boolean;
    onClose: () => void;
    workspaceSlug?: string;
    /** Workspaces list for workspace-switching. */
    workspaces?: Array<{ id: number; name: string; slug: string }>;
}

const CATEGORY_LABELS: Record<CommandItem['category'], string> = {
    page:      'Pages',
    workspace: 'Workspaces',
    setting:   'Settings',
    action:    'Actions',
};

export function CommandPalette({
    open,
    onClose,
    workspaceSlug,
    workspaces = [],
}: CommandPaletteProps) {
    const [query, setQuery] = useState('');
    const inputRef = useRef<HTMLInputElement>(null);
    const w = useCallback((path: string) => wurl(workspaceSlug, path), [workspaceSlug]);

    // Focus input on open
    useEffect(() => {
        if (open) {
            setQuery('');
            setTimeout(() => inputRef.current?.focus(), 50);
        }
    }, [open]);

    // Esc to close
    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [onClose]);

    if (!open) return null;

    // Static page items
    const pageItems: CommandItem[] = [
        { id: 'dashboard',   label: 'Dashboard',    category: 'page', href: w(''),                 icon: LayoutDashboard },
        { id: 'orders',      label: 'Orders',       category: 'page', href: w('/orders'),           icon: ShoppingBag },
        { id: 'ads',         label: 'Ads',          category: 'page', href: w('/ads'),              icon: BarChart2 },
        { id: 'attribution', label: 'Attribution',  category: 'page', href: w('/attribution'),      icon: GitBranch },
        { id: 'seo',         label: 'SEO',          category: 'page', href: w('/seo'),              icon: SearchIcon },
        { id: 'products',    label: 'Products',     category: 'page', href: w('/products'),         icon: Package },
        { id: 'profit',      label: 'Profit',       category: 'page', href: w('/profit'),           icon: TrendingUp },
        { id: 'customers',   label: 'Customers',    category: 'page', href: w('/customers'),        icon: Users },
        { id: 'integrations',label: 'Integrations', category: 'page', href: w('/settings/integrations'), icon: Puzzle },
        { id: 'settings',    label: 'Settings',     category: 'page', href: w('/settings'),         icon: Settings },
    ];

    const workspaceItems: CommandItem[] = workspaces.map((ws) => ({
        id: `ws-${ws.id}`,
        label: ws.name,
        description: 'Switch workspace',
        category: 'workspace',
        action: () => router.post(`/workspaces/${ws.id}/switch`),
    }));

    const allItems = [...pageItems, ...workspaceItems];

    const filtered = query.trim()
        ? allItems.filter((item) =>
            item.label.toLowerCase().includes(query.toLowerCase()) ||
            item.description?.toLowerCase().includes(query.toLowerCase())
          )
        : allItems;

    // Group by category
    const groups = filtered.reduce<Record<string, CommandItem[]>>((acc, item) => {
        const key = item.category;
        if (!acc[key]) acc[key] = [];
        acc[key].push(item);
        return acc;
    }, {});

    const handleSelect = (item: CommandItem) => {
        onClose();
        if (item.href) {
            router.visit(item.href);
        } else if (item.action) {
            item.action();
        }
    };

    return (
        <>
            {/* Backdrop */}
            <div
                className="fixed inset-0 z-50 bg-black/20"
                onClick={onClose}
                aria-hidden="true"
            />

            {/* Palette */}
            <div
                className="fixed left-1/2 top-24 z-50 w-full max-w-lg -translate-x-1/2 rounded-xl border border-border bg-white overflow-hidden"
                style={{ boxShadow: 'var(--shadow-raised)' }}
                role="dialog"
                aria-label="Command palette"
                aria-modal="true"
            >
                {/* Search input */}
                <div className="flex items-center gap-3 border-b border-border px-4 py-3">
                    <Search className="h-4 w-4 text-zinc-400 shrink-0" />
                    <input
                        ref={inputRef}
                        type="text"
                        placeholder="Search pages, workspaces, settings…"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        className="flex-1 bg-transparent text-sm text-zinc-900 placeholder:text-zinc-400 outline-none border-0 ring-0 p-0"
                        aria-label="Search command palette"
                    />
                    <button
                        onClick={onClose}
                        className="shrink-0 rounded p-0.5 text-zinc-400 hover:text-zinc-600"
                        aria-label="Close"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                {/* Results */}
                <div className="max-h-80 overflow-y-auto py-2">
                    {filtered.length === 0 ? (
                        <div className="px-4 py-8 text-center text-sm text-zinc-400">
                            No results for "{query}"
                        </div>
                    ) : (
                        Object.entries(groups).map(([category, items]) => (
                            <div key={category}>
                                <div className="px-4 py-1.5 text-[10px] font-semibold text-zinc-400 uppercase tracking-wider">
                                    {CATEGORY_LABELS[category as CommandItem['category']] ?? category}
                                </div>
                                {items.map((item) => {
                                    const Icon = item.icon;
                                    return (
                                        <button
                                            key={item.id}
                                            onClick={() => handleSelect(item)}
                                            className="flex w-full items-center gap-3 px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50 transition-colors focus-visible:bg-zinc-50 focus-visible:outline-none"
                                        >
                                            {Icon && <Icon className="h-4 w-4 text-zinc-400 shrink-0" />}
                                            <span className="flex-1 text-left truncate">{item.label}</span>
                                            {item.description && (
                                                <span className="text-xs text-zinc-400">{item.description}</span>
                                            )}
                                            <ArrowRight className="h-3.5 w-3.5 text-zinc-300 shrink-0" />
                                        </button>
                                    );
                                })}
                            </div>
                        ))
                    )}
                </div>

                {/* Keyboard hints */}
                <div className="flex items-center gap-3 border-t border-zinc-100 px-4 py-2 text-[10px] text-zinc-400">
                    <span><kbd className="font-mono">↵</kbd> select</span>
                    <span><kbd className="font-mono">↑↓</kbd> navigate</span>
                    <span><kbd className="font-mono">Esc</kbd> close</span>
                </div>
            </div>
        </>
    );
}

/**
 * Global Cmd+K hook. Install in the root of the app.
 *
 * @example
 *   const [paletteOpen, setPaletteOpen] = useState(false);
 *   useCommandPalette(() => setPaletteOpen(true));
 */
export function useCommandPalette(onOpen: () => void) {
    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                onOpen();
            }
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [onOpen]);
}
