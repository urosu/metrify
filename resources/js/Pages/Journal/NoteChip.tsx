/**
 * NoteChip — small inline chip for a single JournalNote inside the Activities cell.
 *
 * Hover to see full note text in a tooltip. Click × to delete.
 *
 * @see docs/competitors/_research_daily_journal.md §3 CRUD UX
 */
import { useState } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { JournalNote } from './types';
import { NOTE_CATEGORY_LABELS, NOTE_CATEGORY_STYLE } from './types';

interface NoteChipProps {
    note: JournalNote;
    onDelete: (id: string) => void;
}

export function NoteChip({ note, onDelete }: NoteChipProps) {
    const [showTooltip, setShowTooltip] = useState(false);
    const style = NOTE_CATEGORY_STYLE[note.category];

    return (
        <span className="relative inline-flex items-center">
            <span
                className={cn(
                    'inline-flex max-w-[120px] cursor-default items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium leading-none',
                    style.bg,
                    style.text,
                    style.border,
                )}
                onMouseEnter={() => setShowTooltip(true)}
                onMouseLeave={() => setShowTooltip(false)}
            >
                <span className="truncate">
                    {NOTE_CATEGORY_LABELS[note.category]}
                </span>
                <button
                    onClick={(e) => { e.stopPropagation(); onDelete(note.id); }}
                    className={cn(
                        'ml-0.5 rounded-full transition-opacity hover:opacity-70 focus-visible:outline-none focus-visible:ring-1',
                        style.text,
                    )}
                    aria-label="Remove note"
                >
                    <X className="h-2.5 w-2.5" />
                </button>
            </span>

            {/* Tooltip with full note text */}
            {showTooltip && (
                <span
                    className="pointer-events-none absolute bottom-full left-0 z-50 mb-1 w-56 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-700 shadow-lg"
                    role="tooltip"
                >
                    <span className="block font-semibold text-zinc-900 mb-0.5">
                        {NOTE_CATEGORY_LABELS[note.category]}
                    </span>
                    <span className="block leading-relaxed">{note.text}</span>
                    {note.author && (
                        <span className="mt-1 block text-zinc-400">by {note.author}</span>
                    )}
                </span>
            )}
        </span>
    );
}
