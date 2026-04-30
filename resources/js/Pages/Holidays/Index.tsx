/**
 * Holidays/Index — Campaign calendar planning tool.
 *
 * Gives merchants a list-first view of upcoming holidays and major sale events
 * worldwide, by country, with the ability to watch events for reminder delivery
 * (delivery preferences managed centrally at /settings/notifications).
 *
 * Default view: List (DataTable, chronological). Secondary: Calendar (month grid).
 * Country filter: searchable single-select combobox covering 200+ ISO countries.
 * Alert consolidation: per-event Watch toggle only; delivery config deep-links
 * to /settings/notifications. No inline delivery panel.
 *
 * @see docs/competitors/_research_holidays_calendar.md
 * @see docs/pages/holidays.md
 * @see docs/UX.md §5 Shared primitives
 */
import { useState, useMemo, useRef, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    List,
    Bell,
    BellOff,
    Plus,
    Clock,
    ChevronDown,
    Search,
    X,
    Check,
    ExternalLink,
} from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { MetricCardCompact } from '@/Components/shared/MetricCardCompact';
import { DrawerSidePanel } from '@/Components/shared/DrawerSidePanel';
import { DataTable } from '@/Components/shared/DataTable';
import { EmptyState } from '@/Components/shared/EmptyState';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import type { Column } from '@/Components/shared/DataTable';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';
import { CalendarGrid } from './CalendarGrid';
import type { HolidayEvent, AlertConfig } from './types';
import { ALL_COUNTRIES, TYPE_LABELS } from './types';

// ── Types ──────────────────────────────────────────────────────────────────────

type ViewMode = 'list' | 'calendar';

interface Props extends PageProps {
    defaultSelectedCountries: string[];
    events: HolidayEvent[];
    alertConfig: AlertConfig;
}

// ── Helpers ────────────────────────────────────────────────────────────────────

function daysUntil(dateStr: string): number {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const ev = new Date(dateStr);
    return Math.round((ev.getTime() - today.getTime()) / 86_400_000);
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day:   'numeric',
        year:  'numeric',
    });
}

// ── ImportancePill ─────────────────────────────────────────────────────────────

function ImportancePill({ importance }: { importance: string }) {
    const cls =
        importance === 'high'   ? 'bg-zinc-800 text-white' :
        importance === 'medium' ? 'bg-zinc-200 text-zinc-700' :
                                  'bg-zinc-100 text-zinc-500';
    return (
        <span className={cn('rounded px-1.5 py-0.5 text-xs font-semibold capitalize tabular-nums', cls)}>
            {importance}
        </span>
    );
}

// ── WatchToggle ────────────────────────────────────────────────────────────────
// Lightweight per-event subscription toggle (which events to watch).
// Delivery preferences (channel, timing) live at /settings/notifications.

function WatchToggle({
    watched,
    onChange,
}: {
    watched: boolean;
    onChange: (v: boolean) => void;
}) {
    return (
        <button
            onClick={(e) => { e.stopPropagation(); onChange(!watched); }}
            aria-label={watched ? 'Stop watching this event' : 'Watch this event'}
            className={cn(
                'flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400',
                watched
                    ? 'border-zinc-800 bg-zinc-900 text-white hover:bg-zinc-700'
                    : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50',
            )}
        >
            {watched ? (
                <><Bell className="h-3.5 w-3.5" /> Watching</>
            ) : (
                <><BellOff className="h-3.5 w-3.5" /> Watch</>
            )}
        </button>
    );
}

// ── CountryCombobox ────────────────────────────────────────────────────────────
// Searchable single-select covering 200+ ISO countries.
// Research: single-select + "All countries" sentinel is the correct pattern at 200+ scale.
// @see docs/competitors/_research_holidays_calendar.md §2

const ALL_SENTINEL = '';

function CountryCombobox({
    value,
    onChange,
}: {
    value: string; // '' = All countries
    onChange: (code: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const inputRef = useRef<HTMLInputElement>(null);
    const containerRef = useRef<HTMLDivElement>(null);

    const selected = value === ALL_SENTINEL
        ? null
        : ALL_COUNTRIES.find((c) => c.code === value) ?? null;

    const filtered = useMemo(() => {
        if (!search.trim()) return ALL_COUNTRIES;
        const q = search.toLowerCase();
        return ALL_COUNTRIES.filter(
            (c) => c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q),
        );
    }, [search]);

    // Close on outside click
    useEffect(() => {
        if (!open) return;
        function handle(e: MouseEvent) {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
                setSearch('');
            }
        }
        document.addEventListener('mousedown', handle);
        return () => document.removeEventListener('mousedown', handle);
    }, [open]);

    // Focus search input when popover opens
    useEffect(() => {
        if (open) {
            setTimeout(() => inputRef.current?.focus(), 30);
        }
    }, [open]);

    function select(code: string) {
        onChange(code);
        setOpen(false);
        setSearch('');
    }

    return (
        <div ref={containerRef} className="relative">
            {/* Trigger button */}
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                className="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 min-w-[180px] justify-between"
                aria-haspopup="listbox"
                aria-expanded={open}
            >
                <span className="flex items-center gap-1.5 truncate">
                    {selected ? (
                        <>
                            <span aria-hidden="true">{selected.flag}</span>
                            <span className="truncate">{selected.name}</span>
                        </>
                    ) : (
                        <span className="text-zinc-500">All countries</span>
                    )}
                </span>
                <ChevronDown className={cn('h-4 w-4 text-zinc-400 shrink-0 transition-transform', open && 'rotate-180')} />
            </button>

            {/* Popover */}
            {open && (
                <div className="absolute left-0 top-full z-50 mt-1 w-72 rounded-xl border border-zinc-200 bg-white shadow-lg">
                    {/* Search input */}
                    <div className="flex items-center gap-2 border-b border-zinc-100 px-3 py-2">
                        <Search className="h-4 w-4 shrink-0 text-zinc-400" />
                        <input
                            ref={inputRef}
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search countries…"
                            className="flex-1 bg-transparent text-sm text-zinc-800 outline-none placeholder:text-zinc-400"
                        />
                        {search && (
                            <button onClick={() => setSearch('')} className="text-zinc-400 hover:text-zinc-600 focus-visible:outline-none">
                                <X className="h-3.5 w-3.5" />
                            </button>
                        )}
                    </div>

                    {/* Options list */}
                    <ul
                        role="listbox"
                        className="max-h-64 overflow-y-auto py-1"
                        aria-label="Countries"
                    >
                        {/* All countries option */}
                        {!search && (
                            <li>
                                <button
                                    role="option"
                                    aria-selected={value === ALL_SENTINEL}
                                    onClick={() => select(ALL_SENTINEL)}
                                    className={cn(
                                        'flex w-full items-center gap-2 px-3 py-1.5 text-sm text-left hover:bg-zinc-50 focus-visible:bg-zinc-50 focus-visible:outline-none',
                                        value === ALL_SENTINEL ? 'text-zinc-900 font-medium' : 'text-zinc-700',
                                    )}
                                >
                                    <span className="w-5 shrink-0 text-center">🌐</span>
                                    <span className="flex-1">All countries</span>
                                    {value === ALL_SENTINEL && <Check className="h-3.5 w-3.5 text-zinc-500" />}
                                </button>
                            </li>
                        )}

                        {filtered.length === 0 && (
                            <li className="px-3 py-3 text-sm text-zinc-400 text-center">No countries found</li>
                        )}

                        {filtered.map((country) => (
                            <li key={country.code}>
                                <button
                                    role="option"
                                    aria-selected={value === country.code}
                                    onClick={() => select(country.code)}
                                    className={cn(
                                        'flex w-full items-center gap-2 px-3 py-1.5 text-sm text-left hover:bg-zinc-50 focus-visible:bg-zinc-50 focus-visible:outline-none',
                                        value === country.code ? 'text-zinc-900 font-medium' : 'text-zinc-700',
                                    )}
                                >
                                    <span className="w-5 shrink-0 text-center" aria-hidden="true">{country.flag}</span>
                                    <span className="flex-1 truncate">{country.name}</span>
                                    <span className="shrink-0 text-[11px] font-mono text-zinc-400">{country.code}</span>
                                    {value === country.code && <Check className="h-3.5 w-3.5 text-zinc-500 shrink-0" />}
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}

// ── ViewToggleTabs ─────────────────────────────────────────────────────────────

function ViewToggleTabs({
    view,
    onChange,
}: {
    view: ViewMode;
    onChange: (v: ViewMode) => void;
}) {
    const tabs: { value: ViewMode; label: string; icon: React.ComponentType<{ className?: string }> }[] = [
        { value: 'list',     label: 'List',     icon: List },
        { value: 'calendar', label: 'Calendar', icon: CalendarDays },
    ];

    return (
        <div className="flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5">
            {tabs.map(({ value, label, icon: Icon }) => (
                <button
                    key={value}
                    onClick={() => onChange(value)}
                    className={cn(
                        'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400',
                        view === value
                            ? 'bg-white text-zinc-900 shadow-sm'
                            : 'text-zinc-500 hover:text-zinc-700',
                    )}
                >
                    <Icon className="h-3.5 w-3.5" />
                    {label}
                </button>
            ))}
        </div>
    );
}

// ── EventTypeFilter ────────────────────────────────────────────────────────────

const EVENT_TYPES = [
    { value: '', label: 'All types' },
    { value: 'shopping_event', label: 'Shopping' },
    { value: 'statutory_holiday', label: 'Statutory' },
    { value: 'cultural', label: 'Cultural' },
    { value: 'back_to_school', label: 'Back to school' },
] as const;

type EventTypeFilter = '' | 'shopping_event' | 'statutory_holiday' | 'cultural' | 'back_to_school';

// ── EventDetailPanel ──────────────────────────────────────────────────────────

function EventDetailPanel({
    event,
    onClose,
    onToggleWatch,
    notificationsUrl,
}: {
    event: HolidayEvent;
    onClose: () => void;
    onToggleWatch: (id: string, watched: boolean) => void;
    notificationsUrl: string;
}) {
    const days = daysUntil(event.date);
    const isPast = days < 0;

    return (
        <DrawerSidePanel
            open={true}
            onClose={onClose}
            title={event.name}
            subtitle={`${event.country_name} · ${formatDate(event.date)} · ${isPast ? 'Past' : days === 0 ? 'Today' : `${days} days away`}`}
            headerActions={
                <WatchToggle
                    watched={event.is_subscribed}
                    onChange={(v) => onToggleWatch(event.id, v)}
                />
            }
            width={480}
        >
            <div className="space-y-5">
                {/* Key metadata */}
                <div className="grid grid-cols-2 gap-3">
                    <div className="rounded-lg border border-zinc-200 bg-zinc-50 p-3">
                        <div className="text-xs font-medium text-zinc-500">Type</div>
                        <div className="mt-1 text-sm font-semibold text-zinc-900">{TYPE_LABELS[event.type] ?? event.type}</div>
                    </div>
                    <div className="rounded-lg border border-zinc-200 bg-zinc-50 p-3">
                        <div className="text-xs font-medium text-zinc-500">Importance</div>
                        <div className="mt-1"><ImportancePill importance={event.importance} /></div>
                    </div>
                    <div className="rounded-lg border border-zinc-200 bg-zinc-50 p-3">
                        <div className="text-xs font-medium text-zinc-500">Recommended prep</div>
                        <div className="mt-1 text-sm font-semibold text-zinc-900 tabular-nums">{event.recommended_prep_days} days</div>
                    </div>
                    {event.historical_sales_lift_pct !== null && (
                        <div className="rounded-lg border border-zinc-200 bg-zinc-50 p-3">
                            <div className="text-xs font-medium text-zinc-500">Avg sales lift</div>
                            <div className="mt-1 text-sm font-semibold text-zinc-900 tabular-nums">
                                +{event.historical_sales_lift_pct}%
                            </div>
                        </div>
                    )}
                </div>

                {/* Description */}
                <div>
                    <div className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-500">About this event</div>
                    <p className="text-[15px] leading-relaxed text-zinc-700">{event.description}</p>
                </div>

                {/* Prep window guidance */}
                <div className="rounded-lg border border-zinc-200 bg-white p-4">
                    <div className="mb-2 flex items-center gap-2">
                        <Clock className="h-4 w-4 text-zinc-500" />
                        <span className="text-sm font-semibold text-zinc-800">Preparation timeline</span>
                    </div>
                    <ul className="space-y-1.5 text-sm text-zinc-600">
                        {event.recommended_prep_days >= 30 && (
                            <li className="flex items-start gap-2">
                                <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-zinc-400" />
                                <span><strong>{event.recommended_prep_days} days before:</strong> Plan campaign strategy, create assets, set budget.</span>
                            </li>
                        )}
                        {event.recommended_prep_days >= 14 && (
                            <li className="flex items-start gap-2">
                                <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-zinc-500" />
                                <span><strong>14 days before:</strong> Launch awareness campaigns, increase ad spend.</span>
                            </li>
                        )}
                        <li className="flex items-start gap-2">
                            <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-zinc-700" />
                            <span><strong>7 days before:</strong> Email reminder to list, retargeting push.</span>
                        </li>
                        <li className="flex items-start gap-2">
                            <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-zinc-900" />
                            <span><strong>Day of:</strong> Flash sale email, social push, extend offer window.</span>
                        </li>
                    </ul>
                </div>

                {/* Tags */}
                {event.tags.length > 0 && (
                    <div>
                        <div className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-500">Tags</div>
                        <div className="flex flex-wrap gap-1.5">
                            {event.tags.map((tag) => (
                                <span key={tag} className="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600">
                                    {tag}
                                </span>
                            ))}
                        </div>
                    </div>
                )}

                {/* Reminder delivery callout — directs to /settings/notifications */}
                <div className="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                    <div className="mb-1 flex items-center gap-2">
                        <Bell className="h-4 w-4 text-zinc-500" />
                        <span className="text-sm font-semibold text-zinc-800">
                            {event.is_subscribed ? 'Watching — reminders active' : 'Not watching'}
                        </span>
                    </div>
                    <p className="text-sm text-zinc-600 leading-relaxed">
                        {event.is_subscribed
                            ? 'You\'ll receive reminders for this event based on your notification preferences.'
                            : 'Click Watch above to receive reminders before this event.'}
                    </p>
                    <a
                        href={notificationsUrl}
                        className="mt-2 inline-flex items-center gap-1 text-xs font-medium text-zinc-500 underline underline-offset-2 hover:text-zinc-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 rounded"
                    >
                        Manage reminder timing &amp; delivery
                        <ExternalLink className="h-3 w-3" />
                    </a>
                </div>
            </div>
        </DrawerSidePanel>
    );
}

// ── Main page ──────────────────────────────────────────────────────────────────

export default function HolidaysIndex() {
    const { defaultSelectedCountries, events, workspace } = usePage<Props>().props;

    // List view is default per research findings
    // @see docs/competitors/_research_holidays_calendar.md §1
    const [view, setView] = useState<ViewMode>('list');

    // Single-select country filter ('' = All countries)
    // @see docs/competitors/_research_holidays_calendar.md §2
    const [selectedCountry, setSelectedCountry] = useState<string>(
        defaultSelectedCountries[0] ?? ALL_SENTINEL,
    );
    const [typeFilter, setTypeFilter] = useState<EventTypeFilter>('');
    const [selectedEvent, setSelectedEvent] = useState<HolidayEvent | null>(null);
    const [localEvents, setLocalEvents] = useState<HolidayEvent[]>(events);

    const slug = workspace?.slug;
    const w = (path: string) => wurl(slug, path);
    const notificationsUrl = w('/settings/notifications');

    // ── Derived state ──────────────────────────────────────────────────────────

    const filtered = useMemo(
        () => localEvents.filter((ev) => {
            const countryMatch = selectedCountry === ALL_SENTINEL || ev.country_code === selectedCountry;
            const typeMatch = typeFilter === '' || ev.type === typeFilter;
            return countryMatch && typeMatch;
        }),
        [localEvents, selectedCountry, typeFilter],
    );

    const next30 = filtered.filter((ev) => {
        const d = daysUntil(ev.date);
        return d >= 0 && d <= 30;
    });
    const next30High = next30.filter((ev) => ev.importance === 'high');
    const watched = filtered.filter((ev) => ev.is_subscribed);

    const nextHigh = useMemo(() => {
        return [...filtered]
            .filter((ev) => ev.importance === 'high' && daysUntil(ev.date) >= 0)
            .sort((a, b) => a.date.localeCompare(b.date))[0] ?? null;
    }, [filtered]);

    // ── Handlers ───────────────────────────────────────────────────────────────

    function handleToggleWatch(id: string, watched: boolean) {
        setLocalEvents((prev) =>
            prev.map((ev) => ev.id === id ? { ...ev, is_subscribed: watched } : ev),
        );
        if (selectedEvent?.id === id) {
            setSelectedEvent((prev) => prev ? { ...prev, is_subscribed: watched } : prev);
        }
    }

    // ── List view columns ──────────────────────────────────────────────────────

    interface ListRow {
        id: string;
        date: string;
        days_away: number;
        name: string;
        country_code: string;
        country_name: string;
        type: string;
        importance: string;
        lift: number | null;
        prep: number;
        watched: boolean;
    }

    const listRows: ListRow[] = useMemo(
        () =>
            filtered
                .filter((ev) => daysUntil(ev.date) >= 0)
                .sort((a, b) => a.date.localeCompare(b.date))
                .map((ev) => ({
                    id:           ev.id,
                    date:         ev.date,
                    days_away:    daysUntil(ev.date),
                    name:         ev.name,
                    country_code: ev.country_code,
                    country_name: ev.country_name,
                    type:         ev.type,
                    importance:   ev.importance,
                    lift:         ev.historical_sales_lift_pct,
                    prep:         ev.recommended_prep_days,
                    watched:      ev.is_subscribed,
                })),
        [filtered],
    );

    const listColumns: Column<ListRow>[] = [
        {
            key:      'date',
            header:   'Date',
            sortable: true,
            render:   (_, row) => (
                <span className="tabular-nums text-zinc-700 text-sm">{formatDate(row.date)}</span>
            ),
        },
        {
            key:      'days_away',
            header:   'Days away',
            sortable: true,
            render:   (_, row) => (
                <span className={cn(
                    'tabular-nums font-medium text-sm',
                    row.days_away <= 7 ? 'text-zinc-900' : row.days_away <= 30 ? 'text-zinc-700' : 'text-zinc-500',
                )}>
                    {row.days_away === 0 ? 'Today' : `${row.days_away}d`}
                </span>
            ),
        },
        {
            key:      'name',
            header:   'Event',
            sortable: true,
            render:   (_, row) => (
                <button
                    onClick={() => {
                        const ev = localEvents.find((e) => e.id === row.id);
                        if (ev) setSelectedEvent(ev);
                    }}
                    className="font-medium text-zinc-900 hover:underline focus-visible:outline-none text-left text-sm"
                >
                    {row.name}
                </button>
            ),
        },
        {
            key:      'country_code',
            header:   'Country',
            sortable: true,
            render:   (_, row) => {
                const country = ALL_COUNTRIES.find((c) => c.code === row.country_code);
                return (
                    <span className="flex items-center gap-1 text-sm text-zinc-700">
                        {country && <span aria-hidden="true">{country.flag}</span>}
                        <span>{row.country_name}</span>
                    </span>
                );
            },
        },
        {
            key:      'type',
            header:   'Type',
            sortable: true,
            render:   (_, row) => (
                <span className="text-sm text-zinc-600">{TYPE_LABELS[row.type] ?? row.type}</span>
            ),
        },
        {
            key:      'importance',
            header:   'Importance',
            sortable: true,
            render:   (_, row) => <ImportancePill importance={row.importance} />,
        },
        {
            key:      'lift',
            header:   'Avg lift',
            sortable: true,
            render:   (_, row) => (
                <span className="tabular-nums text-sm text-zinc-600">
                    {row.lift !== null ? `+${row.lift}%` : '—'}
                </span>
            ),
        },
        {
            key:      'prep',
            header:   'Prep',
            sortable: true,
            render:   (_, row) => (
                <span className="tabular-nums text-sm text-zinc-500">{row.prep}d</span>
            ),
        },
        {
            key:      'watched',
            header:   'Watch',
            render:   (_, row) => (
                <WatchToggle
                    watched={row.watched}
                    onChange={(v) => handleToggleWatch(row.id, v)}
                />
            ),
        },
    ];

    // ── Render ─────────────────────────────────────────────────────────────────

    return (
        <AppLayout>
            <Head title="Holidays & Sale Events" />

            {/* Next-event reminder banner */}
            {nextHigh && daysUntil(nextHigh.date) <= 30 && (
                <div className="mb-4">
                    <AlertBanner
                        severity="info"
                        message={
                            <span>
                                <strong>{nextHigh.name}</strong> is{' '}
                                <strong>{daysUntil(nextHigh.date) === 0 ? 'today' : `${daysUntil(nextHigh.date)} days away`}</strong>
                                {nextHigh.recommended_prep_days > daysUntil(nextHigh.date) && (
                                    <span> — prep window has started</span>
                                )}
                                .{' '}
                                {nextHigh.country_name}
                            </span>
                        }
                        onDismiss={() => {}}
                        persistence={{ key: `holiday-banner-${nextHigh.id}`, storage: 'session' }}
                    />
                </div>
            )}

            {/* Page header */}
            <PageHeader
                title="Holidays & sale events"
                subtitle="Plan campaigns around the dates that matter in your customers' countries."
                action={
                    <button className="flex items-center gap-1.5 rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500">
                        <Plus className="h-4 w-4" />
                        Add custom event
                    </button>
                }
            />

            {/* Summary cards */}
            <div className="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <MetricCardCompact
                    label="Events in next 30 days"
                    value={next30.length}
                />
                <MetricCardCompact
                    label="High-importance this month"
                    value={next30High.length}
                />
                <MetricCardCompact
                    label="Events watched"
                    value={watched.length}
                />
                <MetricCardCompact
                    label="Countries in data"
                    value={11}
                />
            </div>

            {/* In-page filters + view toggle */}
            <div className="mb-5 flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                {/* Left: country picker + type filter */}
                <div className="flex flex-wrap items-center gap-3">
                    <div className="flex items-center gap-2">
                        <label className="text-sm font-medium text-zinc-600 shrink-0">Country</label>
                        <CountryCombobox
                            value={selectedCountry}
                            onChange={setSelectedCountry}
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <label className="text-sm font-medium text-zinc-600 shrink-0">Type</label>
                        <div className="flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5">
                            {EVENT_TYPES.map(({ value, label }) => (
                                <button
                                    key={value}
                                    onClick={() => setTypeFilter(value as EventTypeFilter)}
                                    className={cn(
                                        'rounded-md px-2.5 py-1 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400',
                                        typeFilter === value
                                            ? 'bg-white text-zinc-900 shadow-sm'
                                            : 'text-zinc-500 hover:text-zinc-700',
                                    )}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Right: view toggle */}
                <ViewToggleTabs view={view} onChange={setView} />
            </div>

            {/* Main view */}
            <div className="rounded-xl border border-zinc-200 bg-white p-5">
                {view === 'list' && (
                    listRows.length === 0 ? (
                        <EmptyState
                            icon={CalendarDays}
                            title="No upcoming events"
                            description="Try selecting a different country or clearing the type filter."
                            action={{
                                label:   'Show all countries',
                                onClick: () => {
                                    setSelectedCountry(ALL_SENTINEL);
                                    setTypeFilter('');
                                },
                            }}
                        />
                    ) : (
                        <DataTable
                            columns={listColumns}
                            data={listRows}
                            defaultSort={{ key: 'days_away', dir: 'asc' }}
                            onRowClick={(row) => {
                                const ev = localEvents.find((e) => e.id === row.id);
                                if (ev) setSelectedEvent(ev);
                            }}
                            emptyMessage="No upcoming events"
                            emptyDescription="Select a different country or adjust filters."
                        />
                    )
                )}

                {view === 'calendar' && (
                    <CalendarGrid
                        events={localEvents}
                        selectedCountries={selectedCountry === ALL_SENTINEL ? [] : [selectedCountry]}
                        onEventClick={setSelectedEvent}
                    />
                )}
            </div>

            {/* Notifications consolidation callout */}
            <div className="mt-4 flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-3">
                <Bell className="h-4 w-4 text-zinc-400 shrink-0" />
                <p className="text-sm text-zinc-600">
                    Reminder timing and delivery channel (email, Slack) are configured in{' '}
                    <a
                        href={notificationsUrl}
                        className="font-medium text-zinc-900 underline underline-offset-2 hover:text-zinc-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 rounded"
                    >
                        Notification settings
                    </a>
                    . Use the Watch button on each event to subscribe.
                </p>
            </div>

            {/* Event detail panel */}
            {selectedEvent && (
                <EventDetailPanel
                    event={selectedEvent}
                    onClose={() => setSelectedEvent(null)}
                    onToggleWatch={handleToggleWatch}
                    notificationsUrl={notificationsUrl}
                />
            )}
        </AppLayout>
    );
}
