import { useState } from 'react';
import { Trash2, ChevronDown, Bookmark } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

interface SavedViewItem {
    id: number;
    name: string;
    is_shared: boolean;
    is_pinned?: boolean;
}

interface SavedViewProps {
    views: SavedViewItem[];
    currentViewId?: number;
    onSelect: (id: number) => void;
    onSaveCurrent: (name: string, shared: boolean) => void;
    onDelete: (id: number) => void;
    className?: string;
}

export function SavedView({
    views,
    currentViewId,
    onSelect,
    onSaveCurrent,
    onDelete,
    className,
}: SavedViewProps) {
    const [open, setOpen] = useState(false);
    const [showSaveForm, setShowSaveForm] = useState(false);
    const [saveName, setSaveName] = useState('');
    const [saveShared, setSaveShared] = useState(false);

    const handleSave = () => {
        if (!saveName.trim()) return;
        onSaveCurrent(saveName.trim(), saveShared);
        setSaveName('');
        setSaveShared(false);
        setShowSaveForm(false);
        setOpen(false);
    };

    return (
        <DropdownMenu open={open} onOpenChange={setOpen}>
            <DropdownMenuTrigger
                className={cn(
                    'inline-flex items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-1.5 text-sm font-medium text-foreground hover:bg-muted/50 transition-colors',
                    className,
                )}
            >
                <Bookmark className="h-3.5 w-3.5 text-muted-foreground/70" />
                Views
                <ChevronDown className="h-3.5 w-3.5 text-muted-foreground/70" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-60">
                {views.length > 0 ? (
                    views.map((view) => (
                        <DropdownMenuItem
                            key={view.id}
                            className={cn(
                                'flex items-center justify-between gap-2',
                                view.id === currentViewId && 'font-medium text-primary',
                            )}
                            onSelect={(e) => e.preventDefault()}
                        >
                            <button
                                className="flex-1 text-left"
                                onClick={() => { onSelect(view.id); setOpen(false); }}
                            >
                                {view.name}
                                {view.is_shared && (
                                    <span className="ml-1.5 text-xs text-muted-foreground/70">shared</span>
                                )}
                            </button>
                            <button
                                onClick={(e) => { e.stopPropagation(); onDelete(view.id); }}
                                className="shrink-0 rounded p-0.5 text-muted-foreground/70 hover:text-red-500 transition-colors"
                                aria-label={`Delete ${view.name}`}
                            >
                                <Trash2 className="h-3.5 w-3.5" />
                            </button>
                        </DropdownMenuItem>
                    ))
                ) : (
                    <div className="px-3 py-2 text-xs text-muted-foreground/70">No saved views</div>
                )}
                <DropdownMenuSeparator />
                {showSaveForm ? (
                    <div className="px-3 py-2 space-y-2" onClick={(e) => e.stopPropagation()}>
                        <input
                            autoFocus
                            type="text"
                            placeholder="View name"
                            value={saveName}
                            onChange={(e) => setSaveName(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleSave()}
                            className="w-full rounded border border-input px-2 py-1 text-sm outline-none focus:border-ring focus:ring-1 focus:ring-ring"
                        />
                        <label className="flex items-center gap-2 text-sm text-muted-foreground cursor-pointer">
                            <input
                                type="checkbox"
                                checked={saveShared}
                                onChange={(e) => setSaveShared(e.target.checked)}
                                className="rounded"
                            />
                            Share with workspace
                        </label>
                        <div className="flex gap-2">
                            <button
                                onClick={handleSave}
                                className="flex-1 rounded bg-primary px-2 py-1 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                            >
                                Save
                            </button>
                            <button
                                onClick={() => setShowSaveForm(false)}
                                className="flex-1 rounded border border-border px-2 py-1 text-sm text-muted-foreground hover:bg-muted/50 transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                ) : (
                    <DropdownMenuItem
                        onSelect={(e) => { e.preventDefault(); setShowSaveForm(true); }}
                        className="text-muted-foreground"
                    >
                        Save current view...
                    </DropdownMenuItem>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
