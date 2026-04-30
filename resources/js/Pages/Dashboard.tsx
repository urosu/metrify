/**
 * Dashboard — cross-channel command center.
 *
 * One glance at how the business is performing: revenue, profit, orders,
 * key ratios, today's live trend, targets, and recent activity.
 *
 * Layout (top → bottom):
 *   1. AlertBanners / TriageInbox — integration issues, anomalies
 *   2. KpiCardGrid — 8 MetricCard cards (4 cols desktop)
 *   3. Two-column (2/3 + 1/3): RevenueTrendChart + TodaySoFarPanel
 *   4. TargetsRow — Monthly Revenue · ROAS · New Customers
 *   5. ActivityFeedPanel — last 10 commerce events
 *
 * @see docs/pages/dashboard.md
 * @see docs/UX.md §5.1 MetricCard
 * @see docs/UX.md §5.24 ActivityFeed
 * @see docs/UX.md §5.25 TodaySoFar
 */

import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/Components/layouts/AppLayout';
import { TriageInbox, type TriageItem } from '@/Components/shared/TriageInbox';
import type { PageProps } from '@/types';

// Page-private subcomponents
import { KpiCardGrid, type KpiCardData } from '@/Pages/Dashboard/KpiCardGrid';
import { TodaySoFarPanel } from '@/Pages/Dashboard/TodaySoFarPanel';
import { RevenueTrendChart, type ComparisonTrendPoint } from '@/Pages/Dashboard/RevenueTrendChart';
import { TargetsRow } from '@/Pages/Dashboard/TargetsRow';
import { ActivityFeedPanel } from '@/Pages/Dashboard/ActivityFeedPanel';
import type { ActivityEvent } from '@/Components/shared/ActivityFeed';
import { useDateRange } from '@/Hooks/useDateRange';

// ─── Prop types ───────────────────────────────────────────────────────────────

interface TodayData {
    revenue: number;
    revenue_formatted: string;
    orders: number;
    projected_revenue: number;
    projected_revenue_formatted: string;
    hourly_data: { hour: number; revenue: number }[];
    baseline_revenue: number;
}

interface TrendPoint {
    date: string;
    real: number;
    store: number;
    facebook: number;
    google: number;
}

interface TargetData {
    label: string;
    metric: string;
    current: number;
    target: number;
    unit: string;
    deadline: string;
    status: 'on_track' | 'at_risk' | 'missed';
}

interface AlertData {
    id: number;
    type: string;
    title: string;
    description: string;
    severity: 'info' | 'warning' | 'critical';
    created_at: string;
    action_href?: string;
    action_label?: string;
}

interface Props {
    // trust_bar kept in props for API compat but not rendered
    trust_bar?: unknown;
    kpis: KpiCardData[];
    today_so_far: TodayData;
    trend: TrendPoint[];
    /**
     * Comparison trend series — populated by the controller when compare_from/compare_to
     * are in the URL. Aligned by index with `trend`. Null when comparison is off.
     */
    comparison_trend?: ComparisonTrendPoint[] | null;
    /** Human-readable label for the comparison period, e.g. "vs prior period". */
    comparison_label?: string | null;
    targets: TargetData[];
    activity: ActivityEvent[];
    alerts: AlertData[];
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function Dashboard({
    kpis,
    today_so_far,
    trend,
    comparison_trend,
    comparison_label,
    targets,
    activity,
    alerts,
}: Props) {
    const { workspace } = usePage<PageProps>().props;
    const slug = workspace?.slug ?? '';
    const { range } = useDateRange();
    const isComparing = !!(range.compare_from && range.compare_to);

    // Map alert data to TriageItem shape
    const triageItems: TriageItem[] = alerts.map((a) => ({
        id:           a.id,
        type:         a.type as TriageItem['type'],
        title:        a.title,
        description:  a.description,
        severity:     a.severity,
        created_at:   a.created_at,
        action_href:  a.action_href,
        action_label: a.action_label,
    }));

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-screen-xl px-6 py-6 space-y-5">

                {/* ── Alerts / TriageInbox ──────────────────────────────── */}
                {triageItems.length > 0 && (
                    <TriageInbox
                        items={triageItems}
                        compact
                    />
                )}

                {/* ── KpiGrid — 8 MetricCard (4 cols desktop) ───────────── */}
                <KpiCardGrid cards={kpis} />

                {/* ── Two-column: Revenue trend (2/3) + Today so far (1/3) */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    {/* Revenue trend chart — 2/3 width */}
                    <div className="lg:col-span-2 rounded-lg border border-zinc-200 bg-white p-5">
                        <div className="flex items-center justify-between mb-4">
                            <div className="flex items-center gap-2">
                                <p className="text-sm font-semibold text-zinc-900">
                                    Revenue
                                </p>
                                {/* Comparison active indicator */}
                                {isComparing && comparison_label && (
                                    <span
                                        className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        style={{
                                            backgroundColor: 'var(--brand-primary-subtle)',
                                            color: 'var(--color-primary)',
                                        }}
                                    >
                                        {comparison_label}
                                    </span>
                                )}
                            </div>
                            {/* Source legend */}
                            <div className="flex items-center gap-3">
                                {[
                                    { label: 'Store',    color: 'var(--color-source-store-fg)' },
                                    { label: 'Facebook', color: 'var(--color-source-facebook-fg)' },
                                    { label: 'Google',   color: 'var(--color-source-google-fg)' },
                                ].map(({ label, color }) => (
                                    <div key={label} className="flex items-center gap-1">
                                        <div
                                            className="h-2 w-2 rounded-full"
                                            style={{ backgroundColor: color }}
                                            aria-hidden="true"
                                        />
                                        <span className="text-xs text-zinc-400">{label}</span>
                                    </div>
                                ))}
                                {isComparing && (
                                    <div className="flex items-center gap-1">
                                        <svg width="14" height="6" aria-hidden="true">
                                            <line
                                                x1="0" y1="3" x2="14" y2="3"
                                                stroke="#71717a"
                                                strokeWidth="1.5"
                                                strokeDasharray="4 2"
                                                strokeOpacity="0.6"
                                            />
                                        </svg>
                                        <span className="text-xs text-zinc-400">
                                            {comparison_label ?? 'Prior period'}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                        <RevenueTrendChart
                            data={trend}
                            comparisonData={isComparing ? comparison_trend : null}
                            comparisonLabel={comparison_label ?? 'Prior period'}
                        />
                    </div>

                    {/* Today so far — 1/3 width */}
                    <TodaySoFarPanel data={today_so_far} />
                </div>

                {/* ── Targets row ───────────────────────────────────────── */}
                <TargetsRow targets={targets} />

                {/* ── Activity feed ─────────────────────────────────────── */}
                <ActivityFeedPanel events={activity} />

            </div>
        </AppLayout>
    );
}
