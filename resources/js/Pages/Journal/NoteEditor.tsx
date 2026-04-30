/**
 * NoteEditor — inline note creation form shown inside the Activities cell.
 *
 * Appears when the user clicks the "+" button on a day row.
 * Saves via the journal.notes.store route (mock — no DB write).
 *
 * @see docs/competitors/_research_daily_journal.md §3 CRUD UX
 * @see docs/UX.md §6 interaction conventions (optimistic writes, Toast feedback)
 */
import { useState, useRef, useEffect } from 'react';
import { Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { NoteCategory } from './types';
import { NOTE_CATEGORY_LABELS } from './types';

interface NoteEditorProps {
    date: string;
    onSave: (text: string, category: NoteCategory) => Promise<void>;
    onCancel: () => void;
}

const CATEGORIES: NoteCategory[] = ['sale', 'promo', 'site_change', 'external', 'other'];

export function NoteEditor({ date: _date, onSave, onCancel }: NoteEditorProps) {
    const [text, setText] = useState('');
    const [category, setCategory] = useState<NoteCategory>('other');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        inputRef.current?.focus();
    }, []);

    const handleSave = async () => {
        const trimmed = text.trim();
        if (!trimmed) { setError('Note cannot be empty.'); return; }
        setSaving(true);
        setError('');
        try {
            await onSave(trimmed, category);
        } catch {
            setError('Could not save. Please try again.');
            setSaving(false);
        }
    };

    return (
        <div className="flex flex-col gap-2 rounded-lg border border-zinc-200 bg-white p-3 shadow-sm">
            {/* Category selector */}
            <div className="flex flex-wrap gap-1">
                {CATEGORIES.map((cat) => (
                    <button
                        key={cat}
                        onClick={() => setCategory(cat)}
                        className={cn(
                            'rounded-full border px-2 py-0.5 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-400',
                            category === cat
                                ? 'border-zinc-800 bg-zinc-900 text-white'
                                : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50',
                        )}
                    >
                        {NOTE_CATEGORY_LABELS[cat]}
                    </button>
                ))}
            </div>

            {/* Text input */}
            <input
                ref={inputRef}
                type="text"
                value={text}
                onChange={(e) => { setText(e.target.value); setError(''); }}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') { e.preventDefault(); handleSave(); }
                    if (e.key === 'Escape') { e.preventDefault(); onCancel(); }
                }}
                placeholder="What happened on this day?"
                maxLength={500}
                className={cn(
                    'w-full rounded-md border bg-white px-2.5 py-1.5 text-sm text-zinc-900 outline-none placeholder-zinc-400 focus:ring-1 focus:ring-zinc-900',
                    error ? 'border-red-300 ring-1 ring-red-200' : 'border-zinc-200',
                )}
            />

            {error && (
                <p className="text-xs text-red-500">{error}</p>
            )}

            {/* Actions */}
            <div className="flex items-center justify-end gap-2">
                <button
                    onClick={onCancel}
                    disabled={saving}
                    className="rounded-md px-2.5 py-1 text-xs font-medium text-zinc-500 hover:text-zinc-700 transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-400"
                >
                    Cancel
                </button>
                <button
                    onClick={handleSave}
                    disabled={saving || !text.trim()}
                    className="flex items-center gap-1.5 rounded-md bg-zinc-900 px-2.5 py-1 text-xs font-medium text-white hover:bg-zinc-700 disabled:opacity-50 transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-400"
                >
                    {saving && <Loader2 className="h-3 w-3 animate-spin" />}
                    Save
                </button>
            </div>
        </div>
    );
}
