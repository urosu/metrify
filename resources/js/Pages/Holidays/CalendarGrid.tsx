/**
 * CalendarGrid — month-grid calendar view for the Holidays page.
 *
 * Renders 3 consecutive months. Each day cell shows event chips for holidays
 * and sale events falling on that day. Clicking a chip fires onEventClick to
 * open the DrawerSidePanel with event detail.
 *
 * Layout: CSS grid, 7 columns, each day cell min-h-[80px].
 * Colors: chip bg tinted by event type (all neutral — no gold/amber/yellow).
 *
 * @see docs/pages/holidays.md
 */
import { useMemo } from 'react';
import { cn } from '@/lib/utils';
import type { HolidayEvent } from './types';

interface CalendarGridProps {
    events: HolidayEvent[];
    selectedCountries: string[];
    onEventClick: (event: HolidayEvent) => void;
}

// ── helpers ──────────────────────────────────────────────────────────────────

function getDaysInMonth(year: number, month: number): number {
    return new Date(year, month + 1, 0).getDate();
}

function getFirstDayOfWeek(year: number, month: number): number {
    // 0 = Sunday
    return new Date(year, month, 1).getDay();
}

function buildMonthGrid(year: number, month: number): (number | null)[] {
    const days = getDaysInMonth(year, month);
    const firstDay = getFirstDayOfWeek(year, month);
    const cells: (number | null)[] = [];
    for (let i = 0; i < firstDay; i++) cells.push(null);
    for (let d = 1; d <= days; d++) cells.push(d);
    return cells;
}

const TYPE_CHIP: Record<string, string> = {
    shopping_event:   'bg-zinc-800 text-white',
    statutory_holiday:'bg-zinc-200 text-zinc-800',
    cultural:         'bg-zinc-100 text-zinc-700',
    back_to_school:   'bg-zinc-300 text-zinc-800',
};

const IMPORTANCE_DOT: Record<string, string> = {
    high:   'bg-zinc-800',
    medium: 'bg-zinc-500',
    low:    'bg-zinc-300',
};

const MONTH_NAMES = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December',
];
const DOW_LABELS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

// ── component ─────────────────────────────────────────────────────────────────

export function CalendarGrid({ events, selectedCountries, onEventClick }: CalendarGridProps) {
    const today = new Date();

    // Build 3 months starting from current month
    const months = useMemo(() => {
        return [0, 1, 2].map((offset) => {
            const d = new Date(today.getFullYear(), today.getMonth() + offset, 1);
            return { year: d.getFullYear(), month: d.getMonth() };
        });
    }, []);

    // Index events by date string 'YYYY-MM-DD' → filtered events
    const eventsByDate = useMemo(() => {
        const map: Record<string, HolidayEvent[]> = {};
        for (const ev of events) {
            if (selectedCountries.length > 0 && !selectedCountries.includes(ev.country_code)) continue;
            if (!map[ev.date]) map[ev.date] = [];
            map[ev.date].push(ev);
        }
        return map;
    }, [events, selectedCountries]);

    const todayStr = today.toISOString().slice(0, 10);

    return (
        <div className="space-y-8">
            {months.map(({ year, month }) => {
                const cells = buildMonthGrid(year, month);

                return (
                    <div key={`${year}-${month}`}>
                        {/* Month heading */}
                        <div className="mb-3 flex items-center gap-2">
                            <h2 className="text-[18px] font-semibold text-zinc-900">
                                {MONTH_NAMES[month]} {year}
                            </h2>
                        </div>

                        {/* Day-of-week header */}
                        <div className="grid grid-cols-7 border-l border-t border-zinc-200">
                            {DOW_LABELS.map((d) => (
                                <div
                                    key={d}
                                    className="border-b border-r border-zinc-200 bg-zinc-50 px-2 py-1.5 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500"
                                >
                                    {d}
                                </div>
                            ))}
                        </div>

                        {/* Day cells */}
                        <div className="grid grid-cols-7 border-l border-zinc-200">
                            {cells.map((day, idx) => {
                                if (day === null) {
                                    return (
                                        <div
                                            key={`empty-${idx}`}
                                            className="min-h-[80px] border-b border-r border-zinc-200 bg-zinc-50/60"
                                        />
                                    );
                                }

                                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                                const dayEvents = eventsByDate[dateStr] ?? [];
                                const isToday = dateStr === todayStr;
                                const isPast = dateStr < todayStr;

                                return (
                                    <div
                                        key={dateStr}
                                        className={cn(
                                            'min-h-[80px] border-b border-r border-zinc-200 p-1.5',
                                            isPast && 'bg-zinc-50/60',
                                            isToday && 'ring-1 ring-inset ring-zinc-900',
                                        )}
                                    >
                                        {/* Day number */}
                                        <div
                                            className={cn(
                                                'mb-1 flex h-5 w-5 items-center justify-center rounded-full text-xs font-medium tabular-nums',
                                                isToday
                                                    ? 'bg-zinc-900 text-white font-semibold'
                                                    : isPast
                                                    ? 'text-zinc-400'
                                                    : 'text-zinc-700',
                                            )}
                                        >
                                            {day}
                                        </div>

                                        {/* Event chips — max 3 visible, rest collapsed */}
                                        <div className="space-y-0.5">
                                            {dayEvents.slice(0, 3).map((ev) => (
                                                <button
                                                    key={ev.id}
                                                    onClick={() => onEventClick(ev)}
                                                    className={cn(
                                                        'flex w-full items-center gap-1 truncate rounded px-1 py-0.5 text-left text-[11px] font-medium leading-tight transition-opacity hover:opacity-80 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-500',
                                                        TYPE_CHIP[ev.type] ?? 'bg-zinc-100 text-zinc-700',
                                                    )}
                                                    title={`${ev.name} (${ev.country_code})`}
                                                >
                                                    {/* Importance dot */}
                                                    <span
                                                        className={cn(
                                                            'inline-block h-1.5 w-1.5 shrink-0 rounded-full',
                                                            IMPORTANCE_DOT[ev.importance] ?? 'bg-zinc-400',
                                                        )}
                                                    />
                                                    <span className="truncate">{ev.name}</span>
                                                    <span className="shrink-0 text-[9px] opacity-70">{ev.country_code}</span>
                                                </button>
                                            ))}
                                            {dayEvents.length > 3 && (
                                                <button
                                                    onClick={() => onEventClick(dayEvents[3])}
                                                    className="w-full rounded px-1 py-0.5 text-left text-[11px] font-medium text-zinc-500 hover:text-zinc-700 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-400"
                                                >
                                                    +{dayEvents.length - 3} more
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                );
            })}

            {/* Legend */}
            <div className="flex flex-wrap items-center gap-4 pt-2 text-xs text-zinc-500">
                <span className="font-medium text-zinc-600">Key:</span>
                {[
                    { label: 'Shopping event', cls: 'bg-zinc-800 text-white' },
                    { label: 'Statutory holiday', cls: 'bg-zinc-200 text-zinc-800' },
                    { label: 'Cultural', cls: 'bg-zinc-100 text-zinc-700 border border-zinc-200' },
                    { label: 'Back to school', cls: 'bg-zinc-300 text-zinc-800' },
                ].map(({ label, cls }) => (
                    <span key={label} className="inline-flex items-center gap-1.5">
                        <span className={cn('rounded px-1.5 py-0.5 text-[11px] font-medium', cls)}>{label}</span>
                    </span>
                ))}
                <span className="inline-flex items-center gap-1.5">
                    <span className="inline-block h-2 w-2 rounded-full bg-zinc-800" /> High importance
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="inline-block h-2 w-2 rounded-full bg-zinc-500" /> Medium
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="inline-block h-2 w-2 rounded-full bg-zinc-300" /> Low
                </span>
            </div>
        </div>
    );
}
