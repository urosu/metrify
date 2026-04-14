import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip as RechartTooltip,
    ResponsiveContainer,
    ReferenceLine,
    ReferenceArea,
} from 'recharts';
import { ChevronDown } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { DateRangePicker } from '@/Components/shared/DateRangePicker';
import { PageHeader } from '@/Components/shared/PageHeader';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';
import type { HolidayOverlay, WorkspaceEventOverlay } from '@/Components/charts/MultiSeriesLineChart';

// ─── Types ────────────────────────────────────────────────────────────────────

interface StoreUrlItem {
    id: number;
    url: string;
    label: string | null;
    is_homepage: boolean;
    store_id: number;
    store_name: string | null;
    store_slug: string | null;
}

interface LatestScores {
    performance_score: number | null;
    seo_score: number | null;
    accessibility_score: number | null;
    best_practices_score: number | null;
    lcp_ms: number | null;
    fcp_ms: number | null;
    cls_score: number | null;
    inp_ms: number | null;
    ttfb_ms: number | null;
    tbt_ms: number | null;
    checked_at: string | null;
}

interface HistoryPoint {
    date: string;
    checked_at: string;
    performance_score: number | null;
    seo_score: number | null;
    accessibility_score: number | null;
    best_practices_score: number | null;
    lcp_ms: number | null;
    cls_score: number | null;
    inp_ms: number | null;
}

interface UrlSummaryRow extends StoreUrlItem {
    mobile_performance_score: number | null;
    mobile_seo_score: number | null;
    mobile_lcp_ms: number | null;
    desktop_performance_score: number | null;
    desktop_seo_score: number | null;
    desktop_lcp_ms: number | null;
    last_checked_at: string | null;
}

interface Props extends PageProps {
    store_urls: StoreUrlItem[];
    selected_url_id: number | null;
    mobile_latest: LatestScores | null;
    desktop_latest: LatestScores | null;
    mobile_history: HistoryPoint[];
    desktop_history: HistoryPoint[];
    url_summary: UrlSummaryRow[];
    holiday_overlays: HolidayOverlay[];
    workspace_event_overlays: WorkspaceEventOverlay[];
    from: string;
    to: string;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

type ScoreGrade = 'good' | 'needs-improvement' | 'poor' | 'unknown';
type CwvGrade   = ScoreGrade;

function scoreGrade(score: number | null): ScoreGrade {
    if (score === null) return 'unknown';
    if (score >= 90)   return 'good';
    if (score >= 50)   return 'needs-improvement';
    return 'poor';
}

function scoreColor(grade: ScoreGrade): string {
    switch (grade) {
        case 'good':              return 'text-green-600';
        case 'needs-improvement': return 'text-amber-600';
        case 'poor':              return 'text-red-600';
        default:                  return 'text-zinc-400';
    }
}

function scoreBg(grade: ScoreGrade): string {
    switch (grade) {
        case 'good':              return 'bg-green-50  border-green-200';
        case 'needs-improvement': return 'bg-amber-50  border-amber-200';
        case 'poor':              return 'bg-red-50    border-red-200';
        default:                  return 'bg-zinc-50   border-zinc-200';
    }
}

function lcpGrade(ms: number | null): CwvGrade {
    if (ms === null) return 'unknown';
    if (ms <= 2500)  return 'good';
    if (ms <= 4000)  return 'needs-improvement';
    return 'poor';
}

function clsGrade(cls: number | null): CwvGrade {
    if (cls === null) return 'unknown';
    if (cls <= 0.1)  return 'good';
    if (cls <= 0.25) return 'needs-improvement';
    return 'poor';
}

function inpGrade(ms: number | null): CwvGrade {
    if (ms === null) return 'unknown';
    if (ms <= 200)   return 'good';
    if (ms <= 500)   return 'needs-improvement';
    return 'poor';
}

function cwvBadgeClass(grade: CwvGrade): string {
    switch (grade) {
        case 'good':              return 'bg-green-100 text-green-700';
        case 'needs-improvement': return 'bg-amber-100 text-amber-700';
        case 'poor':              return 'bg-red-100   text-red-700';
        default:                  return 'bg-zinc-100  text-zinc-400';
    }
}

function cwvGradeLabel(grade: CwvGrade): string {
    switch (grade) {
        case 'good':              return 'Good';
        case 'needs-improvement': return 'Improve';
        case 'poor':              return 'Poor';
        default:                  return '—';
    }
}

function fmtMs(ms: number | null): string {
    if (ms === null) return '—';
    if (ms >= 1000)  return `${(ms / 1000).toFixed(2)} s`;
    return `${ms} ms`;
}

function fmtCls(cls: number | null): string {
    if (cls === null) return '—';
    return cls.toFixed(3);
}

function fmtDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en', { month: 'short', day: 'numeric' });
}

function navigate(params: Record<string, string | number | undefined>) {
    router.get('/performance', params as Record<string, string>, { preserveState: true, replace: true });
}

// ─── Score card ───────────────────────────────────────────────────────────────

function ScoreCard({ label, score }: { label: string; score: number | null }) {
    const grade = scoreGrade(score);
    return (
        <div className={cn('rounded-xl border p-4 space-y-1', scoreBg(grade))}>
            <div className="text-xs font-medium text-zinc-500">{label}</div>
            <div className={cn('text-2xl font-bold tabular-nums', scoreColor(grade))}>
                {score !== null ? score : '—'}
            </div>
            <div className="text-xs text-zinc-400">/ 100</div>
        </div>
    );
}

// ─── CWV card ─────────────────────────────────────────────────────────────────

function CwvCard({
    label,
    value,
    grade,
    description,
}: {
    label: string;
    value: string;
    grade: CwvGrade;
    description: string;
}) {
    return (
        <div className="rounded-xl border border-zinc-200 bg-white p-4 space-y-1">
            <div className="flex items-center justify-between">
                <span className="text-xs font-medium text-zinc-500">{label}</span>
                <span className={cn('rounded-full px-2 py-0.5 text-xs font-semibold', cwvBadgeClass(grade))}>
                    {cwvGradeLabel(grade)}
                </span>
            </div>
            <div className="text-xl font-semibold text-zinc-900 tabular-nums">{value}</div>
            <div className="text-xs text-zinc-400">{description}</div>
        </div>
    );
}

// ─── Strategy column ──────────────────────────────────────────────────────────
// Renders scores + CWV for one strategy (mobile or desktop).

function StrategyColumn({
    label,
    scores,
}: {
    label: string;
    scores: LatestScores | null;
}) {
    const lastChecked = scores?.checked_at
        ? new Date(scores.checked_at).toLocaleString('en', {
              month: 'short', day: 'numeric',
              hour: '2-digit', minute: '2-digit',
          })
        : null;

    return (
        <div className="space-y-4 flex-1 min-w-0">
            {/* Strategy label + checked timestamp */}
            <div className="flex items-baseline gap-3">
                <h2 className="text-sm font-semibold text-zinc-700">{label}</h2>
                {lastChecked && (
                    <span className="text-xs text-zinc-400">checked {lastChecked}</span>
                )}
            </div>

            {scores === null ? (
                <div className="rounded-xl border border-zinc-200 bg-zinc-50 p-6 text-center text-sm text-zinc-400">
                    No data yet
                </div>
            ) : (
                <>
                    {/* Lighthouse score cards */}
                    <div className="grid grid-cols-2 gap-3">
                        <ScoreCard label="Performance"    score={scores.performance_score} />
                        <ScoreCard label="Accessibility"  score={scores.accessibility_score} />
                        <ScoreCard label="SEO"            score={scores.seo_score} />
                        <ScoreCard label="Best Practices" score={scores.best_practices_score} />
                    </div>

                    {/* Core Web Vitals */}
                    <div className="grid grid-cols-2 gap-3">
                        <CwvCard
                            label="LCP"
                            value={fmtMs(scores.lcp_ms)}
                            grade={lcpGrade(scores.lcp_ms)}
                            description="Largest Contentful Paint · ≤ 2.5 s"
                        />
                        <CwvCard
                            label="CLS"
                            value={fmtCls(scores.cls_score)}
                            grade={clsGrade(scores.cls_score)}
                            description="Layout Shift · ≤ 0.10"
                        />
                        <CwvCard
                            label="INP"
                            value={fmtMs(scores.inp_ms)}
                            grade={inpGrade(scores.inp_ms)}
                            description="Interaction to Next Paint · ≤ 200 ms"
                        />
                        <CwvCard
                            label="TTFB"
                            value={fmtMs(scores.ttfb_ms)}
                            grade="unknown"
                            description="Time to First Byte · lab"
                        />
                    </div>
                </>
            )}
        </div>
    );
}

// ─── Score trend chart ────────────────────────────────────────────────────────

type ChartStrategy = 'mobile' | 'desktop';

function ScoreTrendChart({
    mobileHistory,
    desktopHistory,
    holidays,
    workspaceEvents,
}: {
    mobileHistory: HistoryPoint[];
    desktopHistory: HistoryPoint[];
    holidays: HolidayOverlay[];
    workspaceEvents: WorkspaceEventOverlay[];
}) {
    const [strategy, setStrategy] = useState<ChartStrategy>('mobile');
    const data = strategy === 'mobile' ? mobileHistory : desktopHistory;

    if (mobileHistory.length === 0 && desktopHistory.length === 0) {
        return (
            <div className="flex h-48 items-center justify-center text-sm text-zinc-400">
                No history data for the selected date range.
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {/* Mobile / Desktop toggle for the chart */}
            <div className="flex items-center gap-3">
                <span className="text-xs text-zinc-500">Showing:</span>
                <div className="flex rounded-lg border border-zinc-200 bg-white text-xs font-medium overflow-hidden">
                    {(['mobile', 'desktop'] as const).map((s) => (
                        <button
                            key={s}
                            onClick={() => setStrategy(s)}
                            className={cn(
                                'px-3 py-1.5 capitalize transition-colors',
                                strategy === s
                                    ? 'bg-zinc-800 text-white'
                                    : 'text-zinc-500 hover:bg-zinc-50'
                            )}
                        >
                            {s}
                        </button>
                    ))}
                </div>
            </div>

            {data.length === 0 ? (
                <div className="flex h-36 items-center justify-center text-sm text-zinc-400">
                    No {strategy} history in this date range.
                </div>
            ) : (
                <ResponsiveContainer width="100%" height={220}>
                    <LineChart data={data} margin={{ top: 4, right: 16, bottom: 0, left: 0 }}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#f4f4f5" />
                        <XAxis
                            dataKey="date"
                            tick={{ fontSize: 11, fill: '#a1a1aa' }}
                            tickFormatter={fmtDate}
                            minTickGap={40}
                        />
                        <YAxis domain={[0, 100]} tick={{ fontSize: 11, fill: '#a1a1aa' }} width={32} />
                        <RechartTooltip
                            contentStyle={{ fontSize: 12, borderRadius: 8, border: '1px solid #e4e4e7' }}
                            labelFormatter={(v) => fmtDate(String(v))}
                            formatter={(value: unknown) => [`${value} / 100`]}
                        />

                        {holidays.map((h) => (
                            <ReferenceLine key={`h-${h.date}`} x={h.date} stroke="#a1a1aa" strokeDasharray="4 2" strokeWidth={1} />
                        ))}
                        {workspaceEvents.map((e) => {
                            const isSingle = e.date_from === e.date_to;
                            return isSingle ? (
                                <ReferenceLine key={`e-${e.date_from}`} x={e.date_from} stroke="#3b82f6" strokeDasharray="4 2" strokeWidth={1} />
                            ) : (
                                <ReferenceArea key={`ea-${e.date_from}`} x1={e.date_from} x2={e.date_to} fill="#3b82f6" fillOpacity={0.06} />
                            );
                        })}

                        <Line type="monotone" dataKey="performance_score"    stroke="var(--chart-1)" strokeWidth={2} dot={false} connectNulls={false} name="Performance" />
                        <Line type="monotone" dataKey="seo_score"            stroke="var(--chart-2)" strokeWidth={2} dot={false} connectNulls={false} name="SEO" />
                        <Line type="monotone" dataKey="accessibility_score"  stroke="var(--chart-3)" strokeWidth={2} dot={false} connectNulls={false} name="Accessibility" />
                        <Line type="monotone" dataKey="best_practices_score" stroke="var(--chart-4)" strokeWidth={2} dot={false} connectNulls={false} name="Best Practices" />
                    </LineChart>
                </ResponsiveContainer>
            )}
        </div>
    );
}

// ─── Score badge for table ────────────────────────────────────────────────────

function ScoreBadge({ score }: { score: number | null }) {
    if (score === null) return <span className="text-zinc-300">—</span>;
    return <span className={cn('font-semibold tabular-nums', scoreColor(scoreGrade(score)))}>{score}</span>;
}

// ─── URL selector dropdown ────────────────────────────────────────────────────

function UrlSelector({
    storeUrls,
    selectedId,
    from,
    to,
}: {
    storeUrls: StoreUrlItem[];
    selectedId: number | null;
    from: string;
    to: string;
}) {
    const [open, setOpen] = useState(false);
    const selected = storeUrls.find((u) => u.id === selectedId);

    if (storeUrls.length <= 1) return null;

    return (
        <div className="relative">
            <button
                onClick={() => setOpen((v) => !v)}
                className="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50"
            >
                <span className="max-w-[240px] truncate">
                    {selected?.label ?? selected?.url ?? 'Select URL'}
                </span>
                <ChevronDown className="h-4 w-4 text-zinc-400" />
            </button>
            {open && (
                <>
                    <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} />
                    <div className="absolute left-0 z-20 mt-1 w-80 rounded-xl border border-zinc-200 bg-white py-1 shadow-lg">
                        {storeUrls.map((u) => (
                            <button
                                key={u.id}
                                onClick={() => { setOpen(false); navigate({ url_id: u.id, from, to }); }}
                                className={cn(
                                    'flex w-full flex-col items-start gap-0.5 px-4 py-2 text-left hover:bg-zinc-50',
                                    u.id === selectedId && 'bg-zinc-50'
                                )}
                            >
                                <span className="text-sm font-medium text-zinc-800 truncate w-full">
                                    {u.label ?? u.url}
                                    {u.is_homepage && <span className="ml-1.5 text-xs text-zinc-400">(homepage)</span>}
                                </span>
                                {u.label && (
                                    <span className="text-xs text-zinc-400 truncate w-full">{u.url}</span>
                                )}
                            </button>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function PerformancePage({
    store_urls,
    selected_url_id,
    mobile_latest,
    desktop_latest,
    mobile_history,
    desktop_history,
    url_summary,
    holiday_overlays,
    workspace_event_overlays,
    from,
    to,
}: Props) {
    const selectedUrl = store_urls.find((u) => u.id === selected_url_id);
    const hasAnyData  = mobile_latest !== null || desktop_latest !== null;

    // ── Empty state — no store connected ─────────────────────────────────────
    if (store_urls.length === 0) {
        return (
            <AppLayout>
                <Head title="Site Performance" />
                <div className="mx-auto max-w-5xl px-6 py-10 space-y-6">
                    <PageHeader title="Site Performance" />
                    <div className="rounded-2xl border border-zinc-200 bg-white p-10 text-center">
                        <div className="text-zinc-400 text-sm">
                            Connect a store to start monitoring page speed and Core Web Vitals.
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Site Performance" />
            <div className="mx-auto max-w-5xl px-6 py-10 space-y-8">

                {/* Header + controls */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <PageHeader title="Site Performance" subtitle={selectedUrl?.url} />
                    <div className="flex flex-wrap items-center gap-3">
                        <UrlSelector storeUrls={store_urls} selectedId={selected_url_id} from={from} to={to} />
                        <DateRangePicker />
                    </div>
                </div>

                {/* No data yet */}
                {!hasAnyData ? (
                    <div className="rounded-2xl border border-zinc-200 bg-white p-10 text-center space-y-3">
                        <div className="text-zinc-900 font-medium">Lighthouse check in progress</div>
                        <div className="text-sm text-zinc-400 max-w-sm mx-auto">
                            A PageSpeed Insights check was queued when this URL was added.
                            Results typically appear within 2–5 minutes — refresh to check.
                        </div>
                        <button
                            onClick={() => window.location.reload()}
                            className="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-100 transition-colors"
                        >
                            Refresh
                        </button>
                    </div>
                ) : (
                    <>
                        {/* Mobile + Desktop columns */}
                        <section className="space-y-3">
                            <h2 className="text-sm font-semibold text-zinc-500 uppercase tracking-wide">
                                Lighthouse Scores &amp; Core Web Vitals
                            </h2>
                            <div className="flex gap-6">
                                <StrategyColumn label="Mobile"  scores={mobile_latest} />
                                <div className="w-px bg-zinc-100 self-stretch" />
                                <StrategyColumn label="Desktop" scores={desktop_latest} />
                            </div>
                        </section>

                        {/* Score trend chart */}
                        {(mobile_history.length > 0 || desktop_history.length > 0) && (
                            <section className="rounded-2xl border border-zinc-200 bg-white p-6 space-y-4">
                                <h2 className="text-sm font-semibold text-zinc-500 uppercase tracking-wide">
                                    Score Trend
                                </h2>
                                <ScoreTrendChart
                                    mobileHistory={mobile_history}
                                    desktopHistory={desktop_history}
                                    holidays={holiday_overlays}
                                    workspaceEvents={workspace_event_overlays}
                                />
                                {(holiday_overlays.length > 0 || workspace_event_overlays.length > 0) && (
                                    <div className="flex flex-wrap gap-4 pt-1 text-xs text-zinc-400">
                                        {holiday_overlays.length > 0 && (
                                            <span className="flex items-center gap-1.5">
                                                <span className="inline-block h-px w-4 border-t-2 border-dashed border-zinc-400" />
                                                Holidays
                                            </span>
                                        )}
                                        {workspace_event_overlays.length > 0 && (
                                            <span className="flex items-center gap-1.5">
                                                <span className="inline-block h-px w-4 border-t-2 border-dashed border-blue-400" />
                                                Promotions
                                            </span>
                                        )}
                                    </div>
                                )}
                            </section>
                        )}
                    </>
                )}

                {/* URL summary table */}
                {url_summary.length > 1 && (
                    <section className="space-y-3">
                        <h2 className="text-sm font-semibold text-zinc-500 uppercase tracking-wide">
                            All Monitored URLs
                        </h2>
                        <div className="rounded-2xl border border-zinc-200 bg-white overflow-hidden">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-zinc-100 bg-zinc-50 text-left">
                                        <th className="px-5 py-3 font-medium text-zinc-500">URL</th>
                                        <th className="px-4 py-3 font-medium text-zinc-500 text-center" colSpan={2}>Mobile</th>
                                        <th className="px-4 py-3 font-medium text-zinc-500 text-center" colSpan={2}>Desktop</th>
                                        <th className="px-4 py-3 font-medium text-zinc-500 text-right">Checked</th>
                                    </tr>
                                    <tr className="border-b border-zinc-100 bg-zinc-50/50 text-left">
                                        <th />
                                        <th className="px-4 pb-2 text-xs font-normal text-zinc-400 text-center">Perf</th>
                                        <th className="px-4 pb-2 text-xs font-normal text-zinc-400 text-center">LCP</th>
                                        <th className="px-4 pb-2 text-xs font-normal text-zinc-400 text-center">Perf</th>
                                        <th className="px-4 pb-2 text-xs font-normal text-zinc-400 text-center">LCP</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-100">
                                    {url_summary.map((row) => (
                                        <tr
                                            key={row.id}
                                            className={cn('hover:bg-zinc-50 cursor-pointer', row.id === selected_url_id && 'bg-zinc-50')}
                                            onClick={() => navigate({ url_id: row.id, from, to })}
                                        >
                                            <td className="px-5 py-3 max-w-[240px]">
                                                <div className="font-medium text-zinc-800 truncate">
                                                    {row.label ?? row.url}
                                                    {row.is_homepage && <span className="ml-1.5 text-xs text-zinc-400">homepage</span>}
                                                </div>
                                                {row.label && (
                                                    <div className="text-xs text-zinc-400 truncate">{row.url}</div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-center"><ScoreBadge score={row.mobile_performance_score} /></td>
                                            <td className="px-4 py-3 text-center text-zinc-600 tabular-nums text-xs">{fmtMs(row.mobile_lcp_ms)}</td>
                                            <td className="px-4 py-3 text-center"><ScoreBadge score={row.desktop_performance_score} /></td>
                                            <td className="px-4 py-3 text-center text-zinc-600 tabular-nums text-xs">{fmtMs(row.desktop_lcp_ms)}</td>
                                            <td className="px-4 py-3 text-right text-zinc-400 text-xs">
                                                {row.last_checked_at ? fmtDate(row.last_checked_at) : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <p className="text-xs text-zinc-400">Uptime monitoring — Phase 2+</p>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
