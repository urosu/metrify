import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import type { PageProps } from '@/types';
import { CalendarDays, Pencil, Trash2, X, Check, Plus } from 'lucide-react';

// ── Types ─────────────────────────────────────────────────────────────────────

interface WorkspaceEvent {
    id: number;
    name: string;
    event_type: string;
    date_from: string;
    date_to: string;
    suppress_anomalies: boolean;
    is_auto_detected: boolean;
    needs_review: boolean;
}

interface Props extends PageProps {
    events: WorkspaceEvent[];
    eventTypes: string[];
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const EVENT_TYPE_LABELS: Record<string, string> = {
    promotion:       'Promotion / Sale',
    expected_spike:  'Expected Spike',
    expected_drop:   'Expected Drop',
};

const EVENT_TYPE_COLORS: Record<string, string> = {
    promotion:      'bg-blue-100 text-blue-700',
    expected_spike: 'bg-green-100 text-green-700',
    expected_drop:  'bg-amber-100 text-amber-700',
};

function formatDateRange(from: string, to: string): string {
    if (from === to) return from;
    return `${from} → ${to}`;
}

// ── Event form (shared by create + edit) ──────────────────────────────────────

interface EventFormProps {
    initial?: Partial<WorkspaceEvent>;
    eventTypes: string[];
    onSubmit: (data: EventFormData) => void;
    onCancel: () => void;
    processing: boolean;
    errors: Partial<Record<keyof EventFormData, string>>;
    submitLabel: string;
}

interface EventFormData {
    name: string;
    event_type: string;
    date_from: string;
    date_to: string;
    suppress_anomalies: boolean;
    [key: string]: string | boolean;
}

function EventForm({ initial, eventTypes, onSubmit, onCancel, processing, errors, submitLabel }: EventFormProps) {
    const [name, setName]                       = useState(initial?.name ?? '');
    const [eventType, setEventType]             = useState(initial?.event_type ?? 'promotion');
    const [dateFrom, setDateFrom]               = useState(initial?.date_from ?? '');
    const [dateTo, setDateTo]                   = useState(initial?.date_to ?? '');
    const [suppressAnomalies, setSuppressAnomalies] = useState(initial?.suppress_anomalies ?? true);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        onSubmit({ name, event_type: eventType, date_from: dateFrom, date_to: dateTo, suppress_anomalies: suppressAnomalies });
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {/* Name */}
                <div className="sm:col-span-2">
                    <Label htmlFor="event-name">Name</Label>
                    <Input
                        id="event-name"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        placeholder="e.g. Black Friday Sale, Valentine's Campaign"
                        className="mt-1"
                        required
                    />
                    {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                </div>

                {/* Event type */}
                <div>
                    <Label htmlFor="event-type">Type</Label>
                    <select
                        id="event-type"
                        value={eventType}
                        onChange={(e) => setEventType(e.target.value)}
                        className="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        required
                    >
                        {eventTypes.map((t) => (
                            <option key={t} value={t}>{EVENT_TYPE_LABELS[t] ?? t}</option>
                        ))}
                    </select>
                    {errors.event_type && <p className="mt-1 text-xs text-red-600">{errors.event_type}</p>}
                </div>

                {/* Suppress anomalies */}
                <div className="flex items-end pb-0.5">
                    <label className="flex cursor-pointer items-center gap-2.5 text-sm text-zinc-700">
                        <input
                            type="checkbox"
                            checked={suppressAnomalies}
                            onChange={(e) => setSuppressAnomalies(e.target.checked)}
                            className="h-4 w-4 rounded border-zinc-300 text-primary focus:ring-primary"
                        />
                        Suppress anomaly alerts during this period
                    </label>
                </div>

                {/* Date from */}
                <div>
                    <Label htmlFor="event-date-from">Start date</Label>
                    <Input
                        id="event-date-from"
                        type="date"
                        value={dateFrom}
                        onChange={(e) => setDateFrom(e.target.value)}
                        className="mt-1"
                        required
                    />
                    {errors.date_from && <p className="mt-1 text-xs text-red-600">{errors.date_from}</p>}
                </div>

                {/* Date to */}
                <div>
                    <Label htmlFor="event-date-to">End date</Label>
                    <Input
                        id="event-date-to"
                        type="date"
                        value={dateTo}
                        min={dateFrom || undefined}
                        onChange={(e) => setDateTo(e.target.value)}
                        className="mt-1"
                        required
                    />
                    {errors.date_to && <p className="mt-1 text-xs text-red-600">{errors.date_to}</p>}
                </div>
            </div>

            <div className="flex items-center gap-3">
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors"
                >
                    {submitLabel}
                </button>
                <button
                    type="button"
                    onClick={onCancel}
                    className="rounded-md border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 transition-colors"
                >
                    Cancel
                </button>
            </div>
        </form>
    );
}

// ── Event row ─────────────────────────────────────────────────────────────────

function EventRow({ event, eventTypes }: { event: WorkspaceEvent; eventTypes: string[] }) {
    const [editing, setEditing] = useState(false);

    const { processing, errors, clearErrors } = useForm<EventFormData>({
        name:               event.name,
        event_type:         event.event_type,
        date_from:          event.date_from,
        date_to:            event.date_to,
        suppress_anomalies: event.suppress_anomalies,
    });

    function handleUpdate(data: EventFormData) {
        router.patch(`/settings/events/${event.id}`, data, {
            preserveScroll: true,
            onSuccess:      () => setEditing(false),
        });
    }

    function handleDelete() {
        if (!confirm(`Delete "${event.name}"? This will remove the overlay marker from all charts.`)) return;
        router.delete(`/settings/events/${event.id}`, { preserveScroll: true });
    }

    if (editing) {
        return (
            <div className="border-t border-zinc-100 px-6 py-4">
                <EventForm
                    initial={event}
                    eventTypes={eventTypes}
                    onSubmit={handleUpdate}
                    onCancel={() => { setEditing(false); clearErrors(); }}
                    processing={processing}
                    errors={errors}
                    submitLabel="Save changes"
                />
            </div>
        );
    }

    return (
        <div className="flex items-start gap-3 border-t border-zinc-100 px-6 py-3.5">
            <CalendarDays className="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />

            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="font-medium text-zinc-900 text-sm">{event.name}</span>
                    <span className={`shrink-0 rounded-full px-2 py-0.5 text-xs font-medium ${EVENT_TYPE_COLORS[event.event_type] ?? 'bg-zinc-100 text-zinc-600'}`}>
                        {EVENT_TYPE_LABELS[event.event_type] ?? event.event_type}
                    </span>
                    {event.is_auto_detected && (
                        <span className="shrink-0 rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-700">
                            Auto-detected
                        </span>
                    )}
                    {event.needs_review && (
                        <span className="shrink-0 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">
                            Needs review
                        </span>
                    )}
                </div>
                <div className="mt-0.5 flex items-center gap-3 text-xs text-zinc-500">
                    <span>{formatDateRange(event.date_from, event.date_to)}</span>
                    {event.suppress_anomalies && (
                        <span className="flex items-center gap-1">
                            <Check className="h-3 w-3 text-green-500" />
                            Alerts suppressed
                        </span>
                    )}
                </div>
            </div>

            <div className="flex shrink-0 items-center gap-1">
                <button
                    type="button"
                    onClick={() => setEditing(true)}
                    className="flex h-7 w-7 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 transition-colors"
                    title="Edit"
                >
                    <Pencil className="h-3.5 w-3.5" />
                </button>
                <button
                    type="button"
                    onClick={handleDelete}
                    className="flex h-7 w-7 items-center justify-center rounded text-zinc-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                    title="Delete"
                >
                    <Trash2 className="h-3.5 w-3.5" />
                </button>
            </div>
        </div>
    );
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function EventsSettings({ events, eventTypes }: Props) {
    const [showCreate, setShowCreate] = useState(false);
    const { processing, errors, clearErrors } = useForm<EventFormData>({
        name:               '',
        event_type:         'promotion',
        date_from:          '',
        date_to:            '',
        suppress_anomalies: true,
    });

    function handleCreate(data: EventFormData) {
        router.post('/settings/events', data, {
            preserveScroll: true,
            onSuccess:      () => setShowCreate(false),
        });
    }

    return (
        <AppLayout>
            <Head title="Events" />

            <PageHeader
                title="Events"
                subtitle="Mark promotions, sales, and expected traffic changes. They appear as overlay markers on all charts and suppress anomaly alerts during those periods."
            />

            <div className="mt-6 max-w-2xl space-y-4">

                {/* Events list */}
                <div className="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                    <div className="flex items-center justify-between border-b border-zinc-200 px-6 py-4">
                        <div>
                            <h3 className="text-base font-semibold text-zinc-900">Promotions & Events</h3>
                            <p className="mt-0.5 text-xs text-zinc-500">
                                Events show as blue vertical markers on all time-series charts.
                            </p>
                        </div>
                        {!showCreate && (
                            <button
                                type="button"
                                onClick={() => setShowCreate(true)}
                                className="flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                            >
                                <Plus className="h-3.5 w-3.5" />
                                Add event
                            </button>
                        )}
                    </div>

                    {/* Create form */}
                    {showCreate && (
                        <div className="border-b border-zinc-200 bg-zinc-50 px-6 py-5">
                            <h4 className="mb-4 text-sm font-medium text-zinc-700">New event</h4>
                            <EventForm
                                eventTypes={eventTypes}
                                onSubmit={handleCreate}
                                onCancel={() => { setShowCreate(false); clearErrors(); }}
                                processing={processing}
                                errors={errors}
                                submitLabel="Create event"
                            />
                        </div>
                    )}

                    {/* Event rows */}
                    {events.length === 0 && !showCreate ? (
                        <div className="px-6 py-8 text-center">
                            <CalendarDays className="mx-auto h-8 w-8 text-zinc-300" />
                            <p className="mt-2 text-sm text-zinc-500">No events yet.</p>
                            <p className="mt-1 text-xs text-zinc-400">
                                Add a promotion or expected spike so charts show context around unusual days.
                            </p>
                            <button
                                type="button"
                                onClick={() => setShowCreate(true)}
                                className="mt-4 flex items-center gap-1.5 mx-auto rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                            >
                                <Plus className="h-3.5 w-3.5" />
                                Add your first event
                            </button>
                        </div>
                    ) : (
                        <div>
                            {events.map((event) => (
                                <EventRow key={event.id} event={event} eventTypes={eventTypes} />
                            ))}
                        </div>
                    )}
                </div>

                {/* Context info */}
                <div className="rounded-lg border border-zinc-200 bg-white">
                    <div className="border-b border-zinc-100 px-5 py-3">
                        <h4 className="text-sm font-semibold text-zinc-700">How events work</h4>
                    </div>
                    <div className="divide-y divide-zinc-100">
                        <div className="flex gap-3 px-5 py-3.5">
                            <div className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-blue-100">
                                <span className="h-2 w-2 rounded-full bg-blue-500" />
                            </div>
                            <p className="text-xs text-zinc-500 leading-relaxed">
                                Events appear as <span className="font-medium text-zinc-700">blue vertical markers</span> on all time-series charts — Dashboard, Paid Ads, Organic Search, and Site Performance.
                            </p>
                        </div>
                        <div className="flex gap-3 px-5 py-3.5">
                            <div className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-violet-100">
                                <span className="h-2 w-2 rounded-full bg-violet-500" />
                            </div>
                            <p className="text-xs text-zinc-500 leading-relaxed">
                                With <span className="font-medium text-zinc-700">Suppress anomaly alerts</span> enabled, the anomaly detection system won't fire during the event period — preventing false positives from planned promotions.
                            </p>
                        </div>
                        <div className="flex gap-3 px-5 py-3.5">
                            <div className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-green-100">
                                <span className="h-2 w-2 rounded-full bg-green-500" />
                            </div>
                            <p className="text-xs text-zinc-500 leading-relaxed">
                                You can mark events <span className="font-medium text-zinc-700">retroactively</span> — baselines will be recalculated on the next nightly run, improving future anomaly accuracy.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
