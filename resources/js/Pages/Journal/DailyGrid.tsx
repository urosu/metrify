/**
 * DailyGrid — 30-row × 11-column table showing all daily KPIs for the month.
 *
 * Columns: Date | Day | Ad Spend | Revenue | # Packages | # Items | IPO
 *          | % Marketing | AOV | ROAS | Activities (notes)
 *
 * All numeric cells use tabular-nums for alignment. Clicking a numeric
 * cell fires the onDrillDown callback (deep-links to /orders or /ads).
 *
 * The Activities column shows NoteChip instances + a "+" button to add a note.
 * The NoteEditor appears inline when "+" is clicked.
 *
 * @see docs/competitors/_research_daily_journal.md §2 table-grid pattern
 * @see docs/UX.md §5 shared primitives
 */
import { useState } from 'react';
import { Plus } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { DayRow, JournalNote } from './types';
import { NoteChip } from './NoteChip';
import { NoteEditor } from './NoteEditor';
import type { NoteCategory } from './types';

interface DailyGridProps {
    days: DayRow[];
    notes: JournalNote[];
    onAddNote: (date: string, text: string, category: NoteCategory) => Promise<void>;
    onDeleteNote: (id: string) => void;
    onDrillDown: (date: string, destination: 'orders' | 'ads') => void;
    /** Today's date string YYYY-MM-DD for row highlight */
    today: string;
}

function fmt(value: number, type: 'currency' | 'number' | 'percent' | 'decimal'): string {
    if (type === 'currency') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency', currency: 'USD',
            minimumFractionDigits: 0, maximumFractionDigits: 0,
        }).format(value);
    }
    if (type === 'percent') return `${value.toFixed(1)}%`;
    if (type === 'decimal') return value.toFixed(2);
    return new Intl.NumberFormat('en-US').format(value);
}

interface ThProps {
    children: React.ReactNode;
    className?: string;
    title?: string;
}
function Th({ children, className, title }: ThProps) {
    return (
        <th
            scope="col"
            title={title}
            className={cn(
                'whitespace-nowrap border-b border-zinc-200 bg-zinc-50 px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500',
                className,
            )}
        >
            {children}
        </th>
    );
}

interface TdProps {
    children: React.ReactNode;
    className?: string;
    onClick?: () => void;
    title?: string;
}
function Td({ children, className, onClick, title }: TdProps) {
    return (
        <td
            title={title}
            onClick={onClick}
            className={cn(
                'border-b border-zinc-100 px-3 py-2 text-sm',
                onClick && 'cursor-pointer hover:bg-zinc-50 transition-colors',
                className,
            )}
        >
            {children}
        </td>
    );
}

export function DailyGrid({
    days,
    notes,
    onAddNote,
    onDeleteNote,
    onDrillDown,
    today,
}: DailyGridProps) {
    // Track which date has an open NoteEditor
    const [editingDate, setEditingDate] = useState<string | null>(null);

    // Group notes by date for O(1) lookup
    const notesByDate = notes.reduce<Record<string, JournalNote[]>>((acc, note) => {
        if (!acc[note.date]) acc[note.date] = [];
        acc[note.date].push(note);
        return acc;
    }, {});

    async function handleSave(date: string, text: string, category: NoteCategory) {
        await onAddNote(date, text, category);
        setEditingDate(null);
    }

    return (
        <div className="overflow-x-auto rounded-xl border border-zinc-200 bg-white">
            <table className="w-full min-w-[960px] border-collapse">
                <thead>
                    <tr>
                        <Th className="sticky left-0 z-10 min-w-[90px]">Date</Th>
                        <Th className="min-w-[48px]">Day</Th>
                        <Th className="min-w-[90px] text-right" title="Total ad spend across all platforms">Ad Spend</Th>
                        <Th className="min-w-[90px] text-right" title="Total store revenue">Revenue</Th>
                        <Th className="min-w-[80px] text-right" title="Number of orders (packages shipped)"># Pkgs</Th>
                        <Th className="min-w-[80px] text-right" title="Number of individual line items"># Items</Th>
                        <Th className="min-w-[60px] text-right" title="Items per order = Items / Packages">IPO</Th>
                        <Th className="min-w-[80px] text-right" title="% in marketing = Ad Spend / Revenue × 100">% Mktg</Th>
                        <Th className="min-w-[80px] text-right" title="Average Order Value = Revenue / Orders">AOV</Th>
                        <Th className="min-w-[70px] text-right" title="Return on Ad Spend = Revenue / Ad Spend">ROAS</Th>
                        <Th className="min-w-[200px]" title="Activity notes for this day">Activities</Th>
                    </tr>
                </thead>
                <tbody>
                    {days.map((row) => {
                        const isToday = row.date === today;
                        const dayNotes = notesByDate[row.date] ?? [];

                        return (
                            <tr
                                key={row.date}
                                className={cn(
                                    'group',
                                    isToday && 'bg-indigo-50/40',
                                )}
                            >
                                {/* Date — sticky for horizontal scroll */}
                                <Td
                                    className={cn(
                                        'sticky left-0 z-10 bg-white font-medium tabular-nums text-zinc-800',
                                        isToday && 'bg-indigo-50/40 font-semibold text-indigo-700',
                                        'group-hover:bg-zinc-50',
                                    )}
                                >
                                    {row.date.slice(5)}
                                    {isToday && (
                                        <span className="ml-1.5 rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700">
                                            Today
                                        </span>
                                    )}
                                </Td>

                                {/* Day of week */}
                                <Td className="text-zinc-500">{row.day_of_week}</Td>

                                {/* Ad Spend */}
                                <Td
                                    className="text-right tabular-nums text-zinc-700"
                                    onClick={() => onDrillDown(row.date, 'ads')}
                                    title="Click to view ads for this date"
                                >
                                    {fmt(row.ad_spend, 'currency')}
                                </Td>

                                {/* Revenue */}
                                <Td
                                    className="text-right tabular-nums font-medium text-zinc-900"
                                    onClick={() => onDrillDown(row.date, 'orders')}
                                    title="Click to view orders for this date"
                                >
                                    {fmt(row.revenue, 'currency')}
                                </Td>

                                {/* # Packages (orders) */}
                                <Td
                                    className="text-right tabular-nums text-zinc-700"
                                    onClick={() => onDrillDown(row.date, 'orders')}
                                    title="Click to view orders for this date"
                                >
                                    {fmt(row.orders, 'number')}
                                </Td>

                                {/* # Items */}
                                <Td className="text-right tabular-nums text-zinc-600">
                                    {fmt(row.items, 'number')}
                                </Td>

                                {/* IPO */}
                                <Td className="text-right tabular-nums text-zinc-600">
                                    {fmt(row.ipo, 'decimal')}
                                </Td>

                                {/* % in marketing */}
                                <Td className={cn(
                                    'text-right tabular-nums',
                                    row.pct_marketing > 30 ? 'text-amber-700 font-medium' : 'text-zinc-700',
                                )}>
                                    {fmt(row.pct_marketing, 'percent')}
                                </Td>

                                {/* AOV */}
                                <Td className="text-right tabular-nums text-zinc-700">
                                    {fmt(row.aov, 'currency')}
                                </Td>

                                {/* ROAS */}
                                <Td className={cn(
                                    'text-right tabular-nums font-medium',
                                    row.roas >= 4 ? 'text-emerald-700' :
                                    row.roas >= 2 ? 'text-zinc-800' :
                                    'text-red-600',
                                )}>
                                    {fmt(row.roas, 'decimal')}×
                                </Td>

                                {/* Activities */}
                                <Td className="min-w-[200px]">
                                    <div className="flex flex-col gap-1.5">
                                        {/* Existing note chips */}
                                        {dayNotes.length > 0 && (
                                            <div className="flex flex-wrap gap-1">
                                                {dayNotes.map((note) => (
                                                    <NoteChip
                                                        key={note.id}
                                                        note={note}
                                                        onDelete={onDeleteNote}
                                                    />
                                                ))}
                                            </div>
                                        )}

                                        {/* Inline editor — shown when this date is being edited */}
                                        {editingDate === row.date ? (
                                            <NoteEditor
                                                date={row.date}
                                                onSave={(text, cat) => handleSave(row.date, text, cat)}
                                                onCancel={() => setEditingDate(null)}
                                            />
                                        ) : (
                                            <button
                                                onClick={() => setEditingDate(row.date)}
                                                className="flex w-fit items-center gap-1 rounded-md border border-dashed border-zinc-200 px-2 py-0.5 text-xs text-zinc-400 transition-colors hover:border-zinc-400 hover:text-zinc-600 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-400"
                                                aria-label={`Add note for ${row.date}`}
                                            >
                                                <Plus className="h-3 w-3" />
                                                Add note
                                            </button>
                                        )}
                                    </div>
                                </Td>
                            </tr>
                        );
                    })}
                </tbody>

                {/* Summary footer */}
                {days.length > 0 && (() => {
                    const totals = days.reduce(
                        (acc, r) => ({
                            ad_spend: acc.ad_spend + r.ad_spend,
                            revenue:  acc.revenue  + r.revenue,
                            orders:   acc.orders   + r.orders,
                            items:    acc.items    + r.items,
                        }),
                        { ad_spend: 0, revenue: 0, orders: 0, items: 0 },
                    );
                    const avgIpo  = totals.orders > 0 ? totals.items / totals.orders : 0;
                    const avgPct  = totals.revenue > 0 ? (totals.ad_spend / totals.revenue) * 100 : 0;
                    const avgAov  = totals.orders  > 0 ? totals.revenue  / totals.orders  : 0;
                    const avgRoas = totals.ad_spend > 0 ? totals.revenue  / totals.ad_spend : 0;
                    return (
                        <tfoot>
                            <tr className="bg-zinc-50">
                                <td className="sticky left-0 z-10 bg-zinc-50 px-3 py-2.5 text-xs font-semibold text-zinc-600">
                                    Total / Avg
                                </td>
                                <td className="border-t border-zinc-200 px-3 py-2.5" />
                                <td className="border-t border-zinc-200 px-3 py-2.5 text-right text-xs font-semibold tabular-nums text-zinc-800">
                                    {fmt(totals.ad_spend, 'currency')}
                                </td>
                                <td className="border-t border-zinc-200 px-3 py-2.5 text-right text-xs font-semibold tabular-nums text-zinc-800">
                                    {fmt(totals.revenue, 'currency')}
                                </td>
                                <td className="border-t border-zinc-200 px-3 py-2.5 text-right text-xs tabular-nums text-zinc-700">
                                    {fmt(totals.orders, 'number')}
                                </td>
                                <td className="border-t border-zinc-200 px-3 py-2.5 text-right text-xs tabular-nums text-zinc-700">
                                    {fmt(totals.items, 'number')}
                                </td>
                                <td className="border-t border-zinc-200 px-3 py-2.5 text-right text-xs tabular-nums text-zinc-700">
                                    {fmt(avgIpo, 'decimal')}
                                </td>
                                <td className="border-t border-zinc-200 px-3 py-2.5 text-right text-xs tabular-nums text-zinc-700">
                                    {fmt(avgPct, 'percent')}
                                </td>
                                <td className="border-t border-zinc-200 px-3 py-2.5 text-right text-xs tabular-nums text-zinc-700">
                                    {fmt(avgAov, 'currency')}
                                </td>
                                <td className="border-t border-zinc-200 px-3 py-2.5 text-right text-xs tabular-nums text-zinc-700">
                                    {fmt(avgRoas, 'decimal')}×
                                </td>
                                <td className="border-t border-zinc-200 px-3 py-2.5" />
                            </tr>
                        </tfoot>
                    );
                })()}
            </table>
        </div>
    );
}
