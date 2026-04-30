/**
 * EventTimeline — horizontal 90-day timeline view for the Holidays page.
 *
 * Events are shown as markers with prep-window bars behind them.
 * Lanes are stacked by event type to avoid vertical collision.
 * Horizontally scrollable container.
 *
 * @see docs/pages/holidays.md
 */
import { useMemo, useRef } from 'react';
import { cn } from '@/lib/utils';
import type { HolidayEvent } from './types';

interface EventTimelineProps {
    events: HolidayEvent[];
    selectedCountries: string[];
    onEventClick: (event: HolidayEvent) => void;
}

const TYPE_COLORS: Record<string, { marker: string; prep: string; label: string }> = {
    shopping_event:    { marker: 'bg-zinc-800', prep: 'bg-zinc-800/10', label: 'text-zinc-900' },
    statutory_holiday: { marker: 'bg-zinc-500', prep: 'bg-zinc-500/10', label: 'text-zinc-700' },
    cultural:          { marker: 'bg-zinc-400', prep: 'bg-zinc-400/10', label: 'text-zinc-600' },
    back_to_school:    { marker: 'bg-zinc-600', prep: 'bg-zinc-600/10', label: 'text-zinc-800' },
};

const LANE_LABELS: Record<string, string> = {
    shopping_event:    'Shopping events',
    statutory_holiday: 'Statutory holidays',
    cultural:          'Cultural events',
    back_to_school:    'Back to school',
};

const TYPE_ORDER = ['shopping_event', 'statutory_holiday', 'cultural', 'back_to_school'];

const TOTAL_DAYS = 90;
const DAY_WIDTH  = 14; // px per day
const TOTAL_WIDTH = TOTAL_DAYS * DAY_WIDTH; // 1260px

function addDays(date: Date, n: number): Date {
    const d = new Date(date);
    d.setDate(d.getDate() + n);
    return d;
}

function daysBetween(a: Date, b: Date): number {
    return Math.round((b.getTime() - a.getTime()) / 86_400_000);
}

function formatShortDate(d: Date): string {
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

export function EventTimeline({ events, selectedCountries, onEventClick }: EventTimelineProps) {
    const today = useMemo(() => {
        const d = new Date();
        d.setHours(0, 0, 0, 0);
        return d;
    }, []);
    const endDate = addDays(today, TOTAL_DAYS - 1);
    const scrollRef = useRef<HTMLDivElement>(null);

    // Filter + group events by type, keeping only those in the next 90 days
    const grouped = useMemo(() => {
        const filtered = events.filter((ev) => {
            if (selectedCountries.length > 0 && !selectedCountries.includes(ev.country_code)) return false;
            const evDate = new Date(ev.date);
            return evDate >= today && evDate <= endDate;
        });

        const map: Record<string, HolidayEvent[]> = {};
        for (const ev of filtered) {
            if (!map[ev.type]) map[ev.type] = [];
            map[ev.type].push(ev);
        }
        return map;
    }, [events, selectedCountries, today, endDate]);

    // Build week markers for the header ruler
    const weekMarkers = useMemo(() => {
        const marks: { day: number; label: string }[] = [];
        for (let d = 0; d < TOTAL_DAYS; d += 7) {
            marks.push({ day: d, label: formatShortDate(addDays(today, d)) });
        }
        return marks;
    }, [today]);

    const lanes = TYPE_ORDER.filter((t) => (grouped[t] ?? []).length > 0);

    if (lanes.length === 0) {
        return (
            <div className="flex items-center justify-center rounded-xl border border-zinc-200 bg-white px-6 py-16 text-sm text-zinc-500">
                No events in the next 90 days for the selected countries.
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-zinc-200 bg-white overflow-hidden">
            <div ref={scrollRef} className="overflow-x-auto">
                <div style={{ width: TOTAL_WIDTH + 160, minWidth: TOTAL_WIDTH + 160 }}>

                    {/* Ruler header */}
                    <div className="flex sticky top-0 bg-white z-10 border-b border-zinc-200">
                        {/* Lane label column */}
                        <div className="w-40 shrink-0 border-r border-zinc-200 bg-zinc-50" />
                        {/* Week ticks */}
                        <div className="relative flex-1" style={{ height: 32 }}>
                            {weekMarkers.map(({ day, label }) => (
                                <div
                                    key={day}
                                    className="absolute top-0 flex h-full items-center"
                                    style={{ left: day * DAY_WIDTH }}
                                >
                                    <div className="h-full border-l border-zinc-200" />
                                    <span className="ml-1 whitespace-nowrap text-[11px] font-medium text-zinc-400">
                                        {label}
                                    </span>
                                </div>
                            ))}
                            {/* Today line */}
                            <div
                                className="absolute top-0 h-full border-l-2 border-zinc-800"
                                style={{ left: 0 }}
                            >
                                <span className="absolute -top-px left-1 text-[10px] font-semibold text-zinc-900">Today</span>
                            </div>
                        </div>
                    </div>

                    {/* Lanes */}
                    {lanes.map((type) => {
                        const laneEvents = grouped[type] ?? [];
                        const colors = TYPE_COLORS[type] ?? TYPE_COLORS.cultural;

                        return (
                            <div key={type} className="flex border-b border-zinc-100 last:border-b-0">
                                {/* Lane label */}
                                <div className="flex w-40 shrink-0 items-center border-r border-zinc-200 bg-zinc-50 px-3 py-3">
                                    <span className="text-xs font-semibold text-zinc-600 leading-tight">
                                        {LANE_LABELS[type] ?? type}
                                    </span>
                                </div>

                                {/* Events in lane */}
                                <div
                                    className="relative"
                                    style={{ width: TOTAL_WIDTH, height: 64 }}
                                >
                                    {/* Background stripe */}
                                    <div className="absolute inset-0 bg-white" />

                                    {laneEvents.map((ev) => {
                                        const evDate = new Date(ev.date);
                                        const evDay = daysBetween(today, evDate);
                                        if (evDay < 0 || evDay >= TOTAL_DAYS) return null;

                                        const prepStart = Math.max(0, evDay - ev.recommended_prep_days);
                                        const prepWidth = (evDay - prepStart) * DAY_WIDTH;
                                        const markerLeft = evDay * DAY_WIDTH;

                                        return (
                                            <div key={ev.id}>
                                                {/* Prep window bar */}
                                                {prepWidth > 0 && (
                                                    <div
                                                        className={cn('absolute top-1/2 -translate-y-1/2 h-5 rounded-l-sm', colors.prep)}
                                                        style={{ left: prepStart * DAY_WIDTH, width: prepWidth }}
                                                        title={`${ev.recommended_prep_days}-day prep window for ${ev.name}`}
                                                    />
                                                )}

                                                {/* Event marker + label */}
                                                <button
                                                    onClick={() => onEventClick(ev)}
                                                    className="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 flex flex-col items-center group focus-visible:outline-none"
                                                    style={{ left: markerLeft }}
                                                    title={`${ev.name} (${ev.country_code}) — ${ev.date}`}
                                                >
                                                    <span
                                                        className={cn(
                                                            'mb-0.5 whitespace-nowrap rounded px-1 py-px text-[10px] font-semibold leading-tight opacity-0 group-hover:opacity-100 transition-opacity',
                                                            colors.label,
                                                            'bg-white border border-zinc-200 shadow-sm',
                                                        )}
                                                        style={{ position: 'absolute', bottom: '100%', marginBottom: 2, whiteSpace: 'nowrap', maxWidth: 120 }}
                                                    >
                                                        {ev.name}
                                                    </span>
                                                    <div
                                                        className={cn(
                                                            'h-3 w-3 rounded-full border-2 border-white shadow transition-transform group-hover:scale-125',
                                                            colors.marker,
                                                        )}
                                                    />
                                                    <span
                                                        className={cn(
                                                            'mt-0.5 whitespace-nowrap text-[10px] font-medium leading-tight',
                                                            colors.label,
                                                        )}
                                                        style={{ maxWidth: 80, overflow: 'hidden', textOverflow: 'ellipsis' }}
                                                    >
                                                        {ev.name.length > 10 ? ev.name.slice(0, 10) + '…' : ev.name}
                                                    </span>
                                                    <span className="text-[9px] text-zinc-400">{ev.country_code}</span>
                                                </button>
                                            </div>
                                        );
                                    })}

                                    {/* Today vertical line */}
                                    <div className="absolute top-0 h-full border-l-2 border-zinc-800 opacity-20" style={{ left: 0 }} />

                                    {/* Week dividers */}
                                    {weekMarkers.map(({ day }) => (
                                        <div
                                            key={day}
                                            className="absolute top-0 h-full border-l border-zinc-100"
                                            style={{ left: day * DAY_WIDTH }}
                                        />
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Legend */}
            <div className="flex flex-wrap items-center gap-4 border-t border-zinc-100 px-4 py-2.5 text-xs text-zinc-500">
                <span className="font-medium text-zinc-600">Type:</span>
                {TYPE_ORDER.map((t) => {
                    const c = TYPE_COLORS[t];
                    return (
                        <span key={t} className="inline-flex items-center gap-1.5">
                            <span className={cn('inline-block h-2 w-2 rounded-full', c.marker)} />
                            {LANE_LABELS[t]}
                        </span>
                    );
                })}
                <span className="ml-2 border-l border-zinc-200 pl-3 font-medium text-zinc-600">
                    Shaded bar = recommended prep window
                </span>
            </div>
        </div>
    );
}
