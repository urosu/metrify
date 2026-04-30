/**
 * Journal/Index — Monthly daily-grain KPI log + activity annotation chart.
 *
 * Replaces the customer's Google Sheets model (AD SPEND / TOTAL SALES /
 * # PACKAGES / # ITEMS / IPO / % MARKETING / AOV / ROAS / AKTIVNOSTI).
 *
 * Layout (top → bottom):
 *   PageHeader (title + month navigation)
 *   Summary KPI strip (4 MetricCardCompact: total spend / revenue / orders / ROAS)
 *   Filters row (metric selector + annotation category toggle + store selector)
 *   JournalChart (annotated line chart — category-colored vertical lines)
 *   DailyGrid (30-row table with inline NoteEditor in Activities column)
 *
 * Placement justification: top-level /journal route (not a Dashboard tab).
 * Full rationale in docs/competitors/_research_daily_journal.md §5.
 *
 * @see docs/competitors/_research_daily_journal.md
 * @see docs/UX.md §5 shared primitives
 * @see docs/planning/backend.md §JournalController
 */
import { useState, useMemo } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Calendar } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { MetricCardCompact } from '@/Components/shared/MetricCardCompact';
import { toastSuccess, toastError } from '@/Components/shared/Toast';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';
import { DailyGrid } from './DailyGrid';
import { JournalChart } from './JournalChart';
import { AnnotationToggle } from './AnnotationToggle';
import type { DayRow, JournalNote, ChartMetric, NoteCategory } from './types';
import { CHART_METRIC_LABELS } from './types';

// ── Props ──────────────────────────────────────────────────────────────────────

interface JournalStore {
    id: number;
    name: string;
    slug: string;
}

interface Props extends PageProps {
    month: string;       // YYYY-MM
    prev_month: string;
    next_month: string;
    days: DayRow[];
    notes: JournalNote[];
    journal_stores: JournalStore[];
}

// ── Helpers ────────────────────────────────────────────────────────────────────

function formatMonthLabel(ym: string): string {
    const [year, month] = ym.split('-').map(Number);
    return new Date(year, month - 1, 1).toLocaleDateString('en-US', {
        month: 'long', year: 'numeric',
    });
}

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

function fmtCurrency(v: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency', currency: 'USD', maximumFractionDigits: 0,
    }).format(v);
}

// ── Metric selector ────────────────────────────────────────────────────────────

const CHART_METRICS: ChartMetric[] = ['revenue', 'aov', 'roas', 'ad_spend', 'orders'];

function MetricSelector({
    value,
    onChange,
}: {
    value: ChartMetric;
    onChange: (m: ChartMetric) => void;
}) {
    return (
        <div className="flex items-center gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-0.5">
            {CHART_METRICS.map((m) => (
                <button
                    key={m}
                    onClick={() => onChange(m)}
                    className={cn(
                        'rounded-md px-2.5 py-1 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-400',
                        value === m
                            ? 'bg-white text-zinc-900 shadow-sm'
                            : 'text-zinc-500 hover:text-zinc-700',
                    )}
                >
                    {CHART_METRIC_LABELS[m]}
                </button>
            ))}
        </div>
    );
}

// ── Month navigation ────────────────────────────────────────────────────────────

function MonthNav({
    month,
    prevMonth,
    nextMonth,
    slug,
}: {
    month: string;
    prevMonth: string;
    nextMonth: string;
    slug: string;
}) {
    function navigate(ym: string) {
        router.get(wurl(slug, '/journal'), { month: ym }, { preserveScroll: false });
    }

    const [jumpValue, setJumpValue] = useState(month);

    function handleJump(e: React.ChangeEvent<HTMLInputElement>) {
        const v = e.target.value; // YYYY-MM from <input type="month">
        setJumpValue(v);
        if (/^\d{4}-\d{2}$/.test(v)) navigate(v);
    }

    return (
        <div className="flex items-center gap-2">
            <button
                onClick={() => navigate(prevMonth)}
                className="rounded-md border border-zinc-200 bg-white p-1.5 text-zinc-600 hover:bg-zinc-50 transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-400"
                aria-label="Previous month"
            >
                <ChevronLeft className="h-4 w-4" />
            </button>

            <div className="relative">
                <span className="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-zinc-400">
                    <Calendar className="h-3.5 w-3.5" />
                </span>
                <input
                    type="month"
                    value={jumpValue}
                    onChange={handleJump}
                    className="rounded-md border border-zinc-200 bg-white py-1.5 pl-8 pr-2.5 text-sm font-medium text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-400"
                    aria-label="Jump to month"
                />
            </div>

            <button
                onClick={() => navigate(nextMonth)}
                className="rounded-md border border-zinc-200 bg-white p-1.5 text-zinc-600 hover:bg-zinc-50 transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-zinc-400"
                aria-label="Next month"
            >
                <ChevronRight className="h-4 w-4" />
            </button>
        </div>
    );
}

// ── Main page ──────────────────────────────────────────────────────────────────

export default function JournalIndex() {
    const { month, prev_month, next_month, days, notes: initialNotes, journal_stores, workspace } =
        usePage<Props>().props;

    const slug = workspace?.slug ?? '';

    // Local note state — optimistic writes
    const [localNotes, setLocalNotes] = useState<JournalNote[]>(initialNotes);

    // Chart controls
    const [chartMetric, setChartMetric] = useState<ChartMetric>('revenue');
    const [activeCategories, setActiveCategories] = useState<NoteCategory[]>([]);

    // Store filter (UI only in mock; URL-stateful in L3)
    const [selectedStore, setSelectedStore] = useState<number | 'all'>('all');

    // Suppress unused variable warning — will be used in real data layer
    void selectedStore;

    // ── Summary totals ─────────────────────────────────────────────────────────

    const totals = useMemo(() => {
        return days.reduce(
            (acc, r) => ({
                ad_spend: acc.ad_spend + r.ad_spend,
                revenue:  acc.revenue  + r.revenue,
                orders:   acc.orders   + r.orders,
            }),
            { ad_spend: 0, revenue: 0, orders: 0 },
        );
    }, [days]);

    const monthRoas = totals.ad_spend > 0
        ? (totals.revenue / totals.ad_spend).toFixed(2) + '×'
        : 'N/A';

    // ── Note handlers ──────────────────────────────────────────────────────────

    async function handleAddNote(date: string, text: string, category: NoteCategory) {
        try {
            const res = await fetch(wurl(slug, '/journal/notes'), {
                method:  'POST',
                headers: {
                    'Content-Type':    'application/json',
                    'X-CSRF-TOKEN':    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':          'application/json',
                },
                body: JSON.stringify({ date, text, category }),
            });

            if (!res.ok) throw new Error('Save failed');

            const saved: JournalNote = await res.json();
            setLocalNotes((prev) => [...prev, saved]);
            toastSuccess('Note saved');
        } catch {
            toastError('Could not save note', 'Please try again.');
            throw new Error('save failed');   // re-throw so NoteEditor shows error state
        }
    }

    async function handleDeleteNote(id: string) {
        // Optimistic remove
        setLocalNotes((prev) => prev.filter((n) => n.id !== id));

        try {
            await fetch(wurl(slug, `/journal/notes/${id}`), {
                method:  'DELETE',
                headers: {
                    'X-CSRF-TOKEN':    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':          'application/json',
                },
            });
            toastSuccess('Note removed');
        } catch {
            // Re-add on failure (no original note in scope; show error only)
            toastError('Could not delete note');
        }
    }

    function handleDrillDown(date: string, destination: 'orders' | 'ads') {
        const path = destination === 'orders'
            ? `/orders?from=${date}&to=${date}`
            : `/ads?from=${date}&to=${date}`;
        router.visit(wurl(slug, path));
    }

    // ── Render ─────────────────────────────────────────────────────────────────

    return (
        <AppLayout>
            <Head title={`Daily Journal · ${formatMonthLabel(month)}`} />

            <PageHeader
                title="Daily Journal"
                subtitle={`Monthly KPI log for ${formatMonthLabel(month)}`}
                action={
                    <MonthNav
                        month={month}
                        prevMonth={prev_month}
                        nextMonth={next_month}
                        slug={slug}
                    />
                }
            />

            {/* Summary KPI strip */}
            <div className="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <MetricCardCompact
                    label="Total Ad Spend"
                    value={fmtCurrency(totals.ad_spend)}
                />
                <MetricCardCompact
                    label="Total Revenue"
                    value={fmtCurrency(totals.revenue)}
                />
                <MetricCardCompact
                    label="Total Orders"
                    value={new Intl.NumberFormat('en-US').format(totals.orders)}
                />
                <MetricCardCompact
                    label="Month ROAS"
                    value={monthRoas}
                />
            </div>

            {/* Chart section */}
            <div className="mb-5 rounded-xl border border-zinc-200 bg-white">
                {/* Filters bar */}
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <MetricSelector value={chartMetric} onChange={setChartMetric} />
                        <AnnotationToggle
                            activeCategories={activeCategories}
                            onChange={setActiveCategories}
                        />
                    </div>

                    {/* Store filter (only shown when multi-store) */}
                    {journal_stores.length > 1 && (
                        <div className="flex items-center gap-1.5">
                            <span className="text-xs font-medium text-zinc-500">Store:</span>
                            <select
                                value={selectedStore === 'all' ? 'all' : String(selectedStore)}
                                onChange={(e) =>
                                    setSelectedStore(e.target.value === 'all' ? 'all' : Number(e.target.value))
                                }
                                className="rounded-md border border-zinc-200 bg-white px-2.5 py-1 text-xs text-zinc-700 focus:outline-none focus:ring-1 focus:ring-zinc-400"
                            >
                                <option value="all">All stores</option>
                                {journal_stores.map((s) => (
                                    <option key={s.id} value={String(s.id)}>
                                        {s.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}
                </div>

                {/* Chart */}
                <div className="px-5 py-4">
                    <JournalChart
                        days={days}
                        notes={localNotes}
                        metric={chartMetric}
                        activeCategories={activeCategories}
                    />
                </div>

                {/* Annotation legend */}
                {localNotes.length > 0 && (
                    <div className="border-t border-zinc-100 px-5 py-3">
                        <p className="text-xs text-zinc-400">
                            Vertical dashed lines mark days with activity notes.
                            Hover a line to see the note. Category colors:
                            <span className="ml-1 font-medium text-amber-700">Sale</span> ·
                            <span className="ml-1 font-medium text-indigo-700">Promo</span> ·
                            <span className="ml-1 font-medium text-blue-700">Site change</span> ·
                            <span className="ml-1 font-medium text-emerald-700">External</span> ·
                            <span className="ml-1 font-medium text-zinc-500">Other</span>
                        </p>
                    </div>
                )}
            </div>

            {/* Daily grid */}
            <div className="mb-8">
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="text-base font-semibold text-zinc-900">
                        Daily breakdown — {formatMonthLabel(month)}
                    </h2>
                    <span className="text-xs text-zinc-400">
                        Click any revenue or orders cell to drill down to that day.
                    </span>
                </div>

                <DailyGrid
                    days={days}
                    notes={localNotes}
                    onAddNote={handleAddNote}
                    onDeleteNote={handleDeleteNote}
                    onDrillDown={handleDrillDown}
                    today={today()}
                />
            </div>
        </AppLayout>
    );
}
