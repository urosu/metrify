/**
 * AnnotationToggle — filter bar for chart annotations by note category.
 *
 * Renders a set of toggle buttons. When all categories are deselected,
 * all annotations are shown. When specific categories are selected, only
 * those category lines show on the chart.
 *
 * @see docs/competitors/_research_daily_journal.md §4 Chart integration
 */
import { cn } from '@/lib/utils';
import type { NoteCategory } from './types';
import { NOTE_CATEGORY_LABELS, NOTE_CATEGORY_STYLE } from './types';

interface AnnotationToggleProps {
    activeCategories: NoteCategory[];
    onChange: (cats: NoteCategory[]) => void;
}

const ALL_CATEGORIES: NoteCategory[] = ['sale', 'promo', 'site_change', 'external', 'other'];

export function AnnotationToggle({ activeCategories, onChange }: AnnotationToggleProps) {
    const allActive = activeCategories.length === 0;

    function toggle(cat: NoteCategory) {
        if (activeCategories.includes(cat)) {
            onChange(activeCategories.filter((c) => c !== cat));
        } else {
            onChange([...activeCategories, cat]);
        }
    }

    return (
        <div className="flex flex-wrap items-center gap-1.5">
            <span className="text-xs font-medium text-zinc-500">Show annotations:</span>
            <button
                onClick={() => onChange([])}
                className={cn(
                    'rounded-full border px-2 py-0.5 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-400',
                    allActive
                        ? 'border-zinc-800 bg-zinc-900 text-white'
                        : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50',
                )}
            >
                All
            </button>
            {ALL_CATEGORIES.map((cat) => {
                const style = NOTE_CATEGORY_STYLE[cat];
                const active = activeCategories.includes(cat);
                return (
                    <button
                        key={cat}
                        onClick={() => toggle(cat)}
                        className={cn(
                            'rounded-full border px-2 py-0.5 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-400',
                            active
                                ? cn(style.bg, style.text, style.border)
                                : 'border-zinc-200 bg-white text-zinc-500 hover:bg-zinc-50',
                        )}
                    >
                        {NOTE_CATEGORY_LABELS[cat]}
                    </button>
                );
            })}
        </div>
    );
}
