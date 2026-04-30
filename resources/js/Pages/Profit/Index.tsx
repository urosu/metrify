/**
 * Profit & Loss page — /profit
 *
 * One page that answers "what's left after I pay for goods, shipping, fees, and ads?"
 * Six-source lens with an accounting-grade P&L a CFO can read unchanged.
 *
 * Layout (top → bottom):
 *  1. AlertBanner — cost completeness warning
 *  2. PageHeader + filter bar (date range, store, channel, accounting mode)
 *  3. KPI strip — 6 MetricCardDetail tiles
 *  4. ProfitWaterfallChart — full-width hero
 *  5. SubNavTabs breakdown (By Channel default) + BreakdownView DataTable
 *  6. LineChart — Net Profit + Revenue 90d dual-axis trend
 *  7. OPEX allocation widget — MetricCardCompact row
 *
 * Controller pre-aggregates all data; BreakdownView is display-only (no SWR).
 *
 * @see docs/pages/profit.md
 * @see docs/UX.md §5.17 ProfitWaterfallChart
 * @see docs/UX.md §5.31 SubNavTabs
 * @see docs/UX.md §5.1 MetricCard
 * Patterns copied:
 *   - Polar Profitability Dashboard waterfall (Revenue→Refunds→COGS→Shipping→Fees→AdSpend→Net Profit)
 *   - Lifetimely classic income-statement P&L with expandable rows
 *   - Lifetimely role-based CFO template — profit-first KPI strip
 *   - BeProfit P&L expandable row pattern (Ad Spend → Meta / Google sub-rows)
 *   - Northbeam accounting-mode toggle (Cash Snapshot / Accrual Performance)
 */

import { useState, useMemo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Components/layouts/AppLayout';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import { PageHeader } from '@/Components/shared/PageHeader';
import { MetricCardDetail } from '@/Components/shared/MetricCardDetail';
import { MetricCardCompact } from '@/Components/shared/MetricCardCompact';
import { ProfitWaterfallChart } from '@/Components/charts/ProfitWaterfallChart';
import type { WaterfallBar } from '@/Components/charts/ProfitWaterfallChart';
import { BreakdownView } from '@/Components/shared/BreakdownView';
import type { BreakdownRow, BreakdownColumn } from '@/Components/shared/BreakdownView';
import { Sparkline } from '@/Components/charts/Sparkline';
import { DateRangePicker } from '@/Components/shared/DateRangePicker';
import { AccountingModeSelector } from '@/Components/shared/AccountingModeSelector';
import type { AccountingMode } from '@/Components/shared/AccountingModeSelector';
import { ExportMenu } from '@/Components/shared/ExportMenu';
import { ShareSnapshotButton } from '@/Components/shared/ShareSnapshotButton';
import { formatCurrency } from '@/lib/formatters';
import type { MetricSource } from '@/Components/shared/SourceBadge';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';
import {
    LineChart as RechartsLineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    Legend,
} from 'recharts';
import { ExternalLink, Settings } from 'lucide-react';

// ── Types ──────────────────────────────────────────────────────────────────────

type BreakdownTab = 'channel' | 'store' | 'product' | 'category' | 'country' | 'customer_type';

interface KpiCard {
    name: string;
    qualifier: string;
    value: number;
    currency?: string;
    unit?: 'pct' | 'x';
    delta_pct: number;
    source: MetricSource;
    sparkline?: number[];
}

interface WaterfallStep {
    label: string;
    value: number;
    pct: number;
    is_start?: boolean;
    is_end?: boolean;
}

interface BreakdownChannelRow {
    name: string;
    revenue: number;
    cogs: number;
    shipping: number;
    fees: number;
    ad_spend: number;
    net_profit: number;
    margin_pct: number;
    sparkline: number[];
}

interface BreakdownGenericRow {
    name: string;
    revenue: number;
    cogs: number;
    shipping: number;
    fees: number;
    net_profit: number;
    margin_pct: number;
    sparkline: number[];
}

interface TrendPoint {
    date: string;
    net_profit: number;
    revenue: number;
}

interface OpexAllocation {
    label: string;
    monthly: number;
}

interface CostCompleteness {
    cogs_coverage_pct: number;
    shipping_coverage_pct: number;
    fees_coverage_pct: number;
    tax_coverage_pct: number;
}

interface Props {
    kpis: KpiCard[];
    waterfall: WaterfallStep[];
    breakdown_by_channel: BreakdownChannelRow[];
    breakdown_by_store: BreakdownGenericRow[];
    breakdown_by_product: BreakdownGenericRow[];
    breakdown_by_category: BreakdownGenericRow[];
    breakdown_by_country: BreakdownGenericRow[];
    breakdown_by_customer_type: BreakdownGenericRow[];
    trend_90d: TrendPoint[];
    opex_allocations: OpexAllocation[];
    cost_completeness: CostCompleteness;
    filters: {
        from: string;
        to: string;
        accounting_mode: string;
    };
}

// ── Waterfall builder ─────────────────────────────────────────────────────────

/**
 * Maps WaterfallStep[] → WaterfallBar[] for ProfitWaterfallChart.
 * Positive = emerald (start/end anchors), negative = rose deductions.
 */
function buildWaterfallBars(steps: WaterfallStep[]): WaterfallBar[] {
    return steps.map((step, i) => ({
        id: `wf-${i}`,
        label: step.label,
        value: step.value,
        type: step.is_start ? 'start' : step.is_end ? 'end' : step.value >= 0 ? 'subtotal' : 'subtract',
        formatted: step.value >= 0
            ? `$${Math.abs(step.value).toLocaleString()} (${step.pct.toFixed(1)}%)`
            : `−$${Math.abs(step.value).toLocaleString()} (${step.pct.toFixed(1)}%)`,
    }));
}

// ── Cost completeness banner ───────────────────────────────────────────────────

function CostCompletenessBanner({ completeness }: { completeness: CostCompleteness }) {
    const issues: string[] = [];
    if (completeness.cogs_coverage_pct < 95) issues.push(`COGS (${completeness.cogs_coverage_pct}% of orders)`);
    if (completeness.shipping_coverage_pct < 95) issues.push(`Shipping (${completeness.shipping_coverage_pct}% of orders)`);
    if (completeness.fees_coverage_pct < 95) issues.push(`Fees (${completeness.fees_coverage_pct}% of orders)`);
    if (completeness.tax_coverage_pct < 95) issues.push(`Tax (${completeness.tax_coverage_pct}% of orders)`);

    if (issues.length === 0) return null;

    return (
        <AlertBanner
            severity="warning"
            message={
                <span>
                    Cost data is incomplete for some orders: <strong>{issues.join(', ')}</strong>.
                    Profit figures may be understated.{' '}
                    <Link href="/settings/costs" className="underline font-medium">Configure costs</Link>
                </span>
            }
        />
    );
}

// ── KPI strip ─────────────────────────────────────────────────────────────────

/**
 * Formats a KPI value for display.
 * Currency: $18,420 · Percent: 14.8% · Multiplier: 4.2×
 */
function formatKpiValue(kpi: KpiCard): string {
    if (kpi.currency) return formatCurrency(kpi.value, kpi.currency);
    if (kpi.unit === 'pct') return `${kpi.value.toFixed(1)}%`;
    if (kpi.unit === 'x') return `${kpi.value.toFixed(2)}×`;
    return kpi.value.toLocaleString();
}

function KpiStrip({ kpis }: { kpis: KpiCard[] }) {
    return (
        <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
            {kpis.map((kpi) => (
                <MetricCardDetail
                    key={kpi.name}
                    label={`${kpi.name} (${kpi.qualifier})`}
                    value={formatKpiValue(kpi)}
                    delta={kpi.delta_pct}
                    sources={[kpi.source]}
                    activeSource={kpi.source}
                    sparklineData={kpi.sparkline}
                />
            ))}
        </div>
    );
}

// ── Waterfall section ─────────────────────────────────────────────────────────

/**
 * Full-width waterfall hero with a % of revenue legend beneath each bar.
 * Click a deduction bar → opens cost breakdown panel (future: DrawerSidePanel).
 * Pattern: Polar Profitability Dashboard (Revenue → COGS → Shipping → Fees → Ad Spend → Net Profit).
 */
function WaterfallSection({ steps }: { steps: WaterfallStep[] }) {
    const [clickedStep, setClickedStep] = useState<string | null>(null);

    const bars = useMemo(() => buildWaterfallBars(steps), [steps]);

    return (
        <div className="rounded-xl border border-border bg-card p-6">
            <div className="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 className="text-base font-semibold text-foreground">Profit Waterfall</h2>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        Revenue to net profit — step by step. Each bar shows the absolute amount and its share of gross revenue.
                        Click a deduction bar to see contributing orders.
                    </p>
                </div>
            </div>

            <ProfitWaterfallChart
                bars={bars}
                currency="USD"
                onBarClick={(bar) => setClickedStep(bar.label)}
                className="w-full"
            />

            {/* Pct-of-revenue legend */}
            <div className="mt-4 flex flex-wrap gap-3">
                {steps.map((step) => (
                    <span
                        key={step.label}
                        className={cn(
                            'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-sm font-medium',
                            step.is_start || step.is_end
                                ? 'bg-slate-100 text-slate-700'
                                : step.value < 0
                                ? 'bg-rose-50 text-rose-700'
                                : 'bg-emerald-50 text-emerald-700',
                        )}
                    >
                        {step.label}
                        <span className="font-normal opacity-70">
                            {step.pct > 0 ? `+${step.pct.toFixed(1)}%` : `${step.pct.toFixed(1)}%`}
                        </span>
                    </span>
                ))}
            </div>

            {/* Drill panel stub */}
            {clickedStep && (
                <div className="mt-4 rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground">
                    Showing orders contributing to <strong className="text-foreground">{clickedStep}</strong>.{' '}
                    <button
                        className="underline hover:text-foreground transition-colors"
                        onClick={() => setClickedStep(null)}
                    >
                        Clear
                    </button>
                </div>
            )}
        </div>
    );
}

// ── Breakdown DataTable ───────────────────────────────────────────────────────

/**
 * Converts raw breakdown rows into BreakdownRow[] for BreakdownView.
 * Pre-aggregated server-side; BreakdownView is display-only per CLAUDE.md gotcha.
 */
function channelRowsToBreakdown(rows: BreakdownChannelRow[]): BreakdownRow[] {
    return rows.map((r, i) => ({
        id: `ch-${i}`,
        label: r.name,
        metrics: {
            revenue:    r.revenue,
            cogs:       r.cogs,
            shipping:   r.shipping,
            fees:       r.fees,
            ad_spend:   r.ad_spend,
            net_profit: r.net_profit,
            margin_pct: r.margin_pct,
        },
    }));
}

function genericRowsToBreakdown(rows: BreakdownGenericRow[]): BreakdownRow[] {
    return rows.map((r, i) => ({
        id: `row-${i}`,
        label: r.name,
        metrics: {
            revenue:    r.revenue,
            cogs:       r.cogs,
            shipping:   r.shipping,
            fees:       r.fees,
            net_profit: r.net_profit,
            margin_pct: r.margin_pct,
        },
    }));
}

const CHANNEL_COLUMNS: BreakdownColumn[] = [
    { key: 'revenue',    label: 'Revenue',       format: 'currency' },
    { key: 'cogs',       label: 'COGS',          format: 'currency' },
    { key: 'shipping',   label: 'Shipping',      format: 'currency' },
    { key: 'fees',       label: 'Fees',          format: 'currency' },
    { key: 'ad_spend',   label: 'Ad Spend',      format: 'currency' },
    { key: 'net_profit', label: 'Net Profit',    format: 'currency' },
    { key: 'margin_pct', label: 'Net Margin %',  format: 'percent_plain' },
];

const GENERIC_COLUMNS: BreakdownColumn[] = [
    { key: 'revenue',    label: 'Revenue',       format: 'currency' },
    { key: 'cogs',       label: 'COGS',          format: 'currency' },
    { key: 'shipping',   label: 'Shipping',      format: 'currency' },
    { key: 'fees',       label: 'Fees',          format: 'currency' },
    { key: 'net_profit', label: 'Net Profit',    format: 'currency' },
    { key: 'margin_pct', label: 'Net Margin %',  format: 'percent_plain' },
];

const BREAKDOWN_TABS: { value: BreakdownTab; label: string }[] = [
    { value: 'channel',       label: 'By Channel' },
    { value: 'store',         label: 'By Store' },
    { value: 'product',       label: 'By Product' },
    { value: 'category',      label: 'By Category' },
    { value: 'country',       label: 'By Country' },
    { value: 'customer_type', label: 'By Customer Type' },
];

interface BreakdownSectionProps {
    breakdown_by_channel: BreakdownChannelRow[];
    breakdown_by_store: BreakdownGenericRow[];
    breakdown_by_product: BreakdownGenericRow[];
    breakdown_by_category: BreakdownGenericRow[];
    breakdown_by_country: BreakdownGenericRow[];
    breakdown_by_customer_type: BreakdownGenericRow[];
    currency: string;
}

function BreakdownSection({
    breakdown_by_channel,
    breakdown_by_store,
    breakdown_by_product,
    breakdown_by_category,
    breakdown_by_country,
    breakdown_by_customer_type,
    currency,
}: BreakdownSectionProps) {
    const [activeTab, setActiveTab] = useState<BreakdownTab>('channel');

    const { rows, columns, dimension } = useMemo<{
        rows: BreakdownRow[];
        columns: BreakdownColumn[];
        dimension: 'product' | 'country' | 'campaign' | 'advertiser' | 'date';
    }>(() => {
        // Map BreakdownTab → BreakdownView props
        // BreakdownView breakdownBy is typed as BreakdownDimension; we map tabs to it.
        switch (activeTab) {
            case 'channel':
                return {
                    rows: channelRowsToBreakdown(breakdown_by_channel),
                    columns: CHANNEL_COLUMNS,
                    dimension: 'advertiser',
                };
            case 'store':
                return {
                    rows: genericRowsToBreakdown(breakdown_by_store),
                    columns: GENERIC_COLUMNS,
                    dimension: 'advertiser',
                };
            case 'product':
                return {
                    rows: genericRowsToBreakdown(breakdown_by_product),
                    columns: GENERIC_COLUMNS,
                    dimension: 'product',
                };
            case 'category':
                return {
                    rows: genericRowsToBreakdown(breakdown_by_category),
                    columns: GENERIC_COLUMNS,
                    dimension: 'product',
                };
            case 'country':
                return {
                    rows: genericRowsToBreakdown(breakdown_by_country),
                    columns: GENERIC_COLUMNS,
                    dimension: 'country',
                };
            case 'customer_type':
            default:
                return {
                    rows: genericRowsToBreakdown(breakdown_by_customer_type),
                    columns: GENERIC_COLUMNS,
                    dimension: 'advertiser',
                };
        }
    }, [
        activeTab,
        breakdown_by_channel,
        breakdown_by_store,
        breakdown_by_product,
        breakdown_by_category,
        breakdown_by_country,
        breakdown_by_customer_type,
    ]);

    return (
        <div className="rounded-xl border border-border bg-card">
            {/* SubNavTabs — pill row beneath section header */}
            <div className="border-b border-border px-5 pt-4">
                <div className="mb-3 flex items-center justify-between gap-4">
                    <div>
                        <h2 className="text-base font-semibold text-foreground">Profit Breakdown</h2>
                        <p className="mt-0.5 text-sm text-muted-foreground">
                            Pre-aggregated P&amp;L by dimension. All columns include Revenue, COGS, Fees, Net Profit, and Net Margin %.
                        </p>
                    </div>
                </div>
                {/* SubNavTabs inline (URL-stateless here — tabs change local state only) */}
                <div className="flex gap-0 overflow-x-auto">
                    {BREAKDOWN_TABS.map((tab) => (
                        <button
                            key={tab.value}
                            type="button"
                            onClick={() => setActiveTab(tab.value)}
                            className={cn(
                                'shrink-0 px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                                activeTab === tab.value
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>
            </div>

            <div className="p-5">
                <BreakdownView
                    breakdownBy={dimension}
                    cardData="real"
                    columns={columns}
                    data={rows}
                    defaultView="table"
                    currency={currency}
                    defaultSortBy="net_profit"
                    defaultSortDir="desc"
                    emptyMessage="No breakdown data for the selected period."
                    isWinner={(row) => (row.metrics.margin_pct ?? 0) >= 20}
                    renderRowSuffix={(row) => {
                        // Sparkline column — 14d profit trend, display-only
                        const original = activeTab === 'channel'
                            ? breakdown_by_channel.find((r, i) => `ch-${i}` === row.id)
                            : null;
                        const sparkData = original
                            ? original.sparkline.map((v) => ({ value: v }))
                            : [];
                        return (
                            <td className="px-4 py-3 w-[80px]">
                                {sparkData.length >= 2 ? (
                                    <Sparkline
                                        data={sparkData}
                                        height={24}
                                        color="var(--color-source-real)"
                                    />
                                ) : (
                                    <span className="text-muted-foreground/40 text-sm">—</span>
                                )}
                            </td>
                        );
                    }}
                    suffixColumnLabel="Trend (14d)"
                />
            </div>
        </div>
    );
}

// ── 90d Trend chart ───────────────────────────────────────────────────────────

/**
 * Net Profit + Revenue dual-axis line chart, last 90 days.
 * Real (gold) = Net Profit left axis · Revenue (slate) = Revenue right axis.
 * Pattern: LineChart with dual-axis from UX §5.6.
 */
function TrendChart({ data, currency }: { data: TrendPoint[]; currency: string }) {
    return (
        <div className="rounded-xl border border-border bg-card p-6">
            <div className="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 className="text-base font-semibold text-foreground">Profit Trend (90d)</h2>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        Net Profit (left axis) vs Revenue (right axis). Weekly granularity. Dotted rightmost segment = incomplete period.
                    </p>
                </div>
                {/* Legend */}
                <div className="shrink-0 flex items-center gap-4 text-sm">
                    <span className="flex items-center gap-1.5 text-muted-foreground">
                        <span className="inline-block h-0.5 w-5 rounded" style={{ backgroundColor: 'var(--color-source-real)' }} />
                        Net Profit
                    </span>
                    <span className="flex items-center gap-1.5 text-muted-foreground">
                        <span className="inline-block h-0.5 w-5 rounded" style={{ backgroundColor: 'var(--color-source-store)' }} />
                        Revenue
                    </span>
                </div>
            </div>

            <div className="h-64 w-full">
                <ResponsiveContainer width="100%" height="100%">
                    <RechartsLineChart
                        data={data}
                        margin={{ top: 4, right: 56, left: 4, bottom: 0 }}
                    >
                        <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" vertical={false} />
                        <XAxis
                            dataKey="date"
                            tickLine={false}
                            axisLine={false}
                            tick={{ fontSize: 14, fill: 'var(--muted-foreground)' }}
                            minTickGap={40}
                        />
                        {/* Left axis — Net Profit */}
                        <YAxis
                            yAxisId="profit"
                            orientation="left"
                            tickLine={false}
                            axisLine={false}
                            tick={{ fontSize: 14, fill: 'var(--muted-foreground)' }}
                            tickFormatter={(v: number) => formatCurrency(v, currency, true)}
                            width={64}
                        />
                        {/* Right axis — Revenue */}
                        <YAxis
                            yAxisId="revenue"
                            orientation="right"
                            tickLine={false}
                            axisLine={false}
                            tick={{ fontSize: 14, fill: 'var(--muted-foreground)' }}
                            tickFormatter={(v: number) => formatCurrency(v, currency, true)}
                            width={56}
                        />
                        <Tooltip
                            contentStyle={{ fontSize: 14, borderRadius: 8, border: '1px solid var(--border)' }}
                            formatter={(value: unknown, name: unknown) => [
                                formatCurrency(Number(value), currency),
                                name === 'net_profit' ? 'Net Profit' : 'Revenue',
                            ]}
                            labelFormatter={(label) => `Week of ${label}`}
                        />
                        <Line
                            yAxisId="profit"
                            type="monotone"
                            dataKey="net_profit"
                            stroke="var(--color-source-real)"
                            strokeWidth={2}
                            dot={false}
                            connectNulls
                        />
                        <Line
                            yAxisId="revenue"
                            type="monotone"
                            dataKey="revenue"
                            stroke="var(--color-source-store)"
                            strokeWidth={2}
                            strokeDasharray="5 3"
                            dot={false}
                            connectNulls
                        />
                    </RechartsLineChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}

// ── OPEX widget ───────────────────────────────────────────────────────────────

/**
 * Compact row of fixed OPEX cards. Click → /settings/costs.
 * Pattern: MetricCardCompact per UX §5.1 variant.
 */
function OpexWidget({ allocations, currency }: { allocations: OpexAllocation[]; currency: string }) {
    const total = allocations.reduce((sum, a) => sum + a.monthly, 0);

    return (
        <div className="rounded-xl border border-border bg-card p-6">
            <div className="mb-4 flex items-center justify-between gap-4">
                <div>
                    <h2 className="text-base font-semibold text-foreground">Operating Expenses</h2>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        Fixed monthly OPEX allocated to this period.
                        Total: <strong>{formatCurrency(total, currency)}/mo</strong>
                    </p>
                </div>
                <Link
                    href="/settings/costs"
                    className="shrink-0 inline-flex items-center gap-1.5 text-sm text-muted-foreground underline underline-offset-2 hover:text-foreground transition-colors"
                >
                    Edit OPEX
                    <ExternalLink className="h-3.5 w-3.5" />
                </Link>
            </div>

            <div className="flex flex-wrap gap-3">
                {allocations.map((opex) => (
                    <MetricCardCompact
                        key={opex.label}
                        label={opex.label}
                        value={`${formatCurrency(opex.monthly, currency)}/mo`}
                        activeSource="real"
                        className="min-w-[140px]"
                    />
                ))}
            </div>
        </div>
    );
}

// ── Cost completeness health strip ────────────────────────────────────────────

function CostHealthStrip({ completeness }: { completeness: CostCompleteness }) {
    const metrics = [
        { label: 'COGS coverage',     pct: completeness.cogs_coverage_pct,     focus: 'cogs' },
        { label: 'Shipping coverage', pct: completeness.shipping_coverage_pct, focus: 'shipping' },
        { label: 'Fees coverage',     pct: completeness.fees_coverage_pct,     focus: 'fees' },
        { label: 'Tax coverage',      pct: completeness.tax_coverage_pct,      focus: 'tax' },
    ];

    return (
        <div className="rounded-xl border border-border bg-card p-5">
            <div className="mb-3 flex items-center gap-2">
                <Settings className="h-4 w-4 text-muted-foreground" />
                <h2 className="text-sm font-semibold text-foreground">Cost configuration health</h2>
            </div>
            <div className="flex flex-wrap gap-3">
                {metrics.map(({ label, pct, focus }) => {
                    const isOk   = pct >= 95;
                    const isWarn = pct >= 80 && pct < 95;
                    return (
                        <Link
                            key={label}
                            href={`/settings/costs?focus=${focus}`}
                            className={cn(
                                'inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors hover:bg-muted/40',
                                isOk   && 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                isWarn && 'border-amber-200 bg-amber-50 text-amber-800',
                                !isOk && !isWarn && 'border-rose-200 bg-rose-50 text-rose-800',
                            )}
                        >
                            <span
                                className={cn(
                                    'h-2 w-2 rounded-full shrink-0',
                                    isOk   && 'bg-emerald-500',
                                    isWarn && 'bg-amber-500',
                                    !isOk && !isWarn && 'bg-rose-500',
                                )}
                            />
                            {label} · {pct}%
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function ProfitIndex({
    kpis,
    waterfall,
    breakdown_by_channel,
    breakdown_by_store,
    breakdown_by_product,
    breakdown_by_category,
    breakdown_by_country,
    breakdown_by_customer_type,
    trend_90d,
    opex_allocations,
    cost_completeness,
    filters,
}: Props) {
    const { workspace } = usePage<PageProps>().props;
    const currency = workspace?.reporting_currency ?? 'USD';

    const activeAccountingMode = (filters.accounting_mode ?? 'cash') as AccountingMode;

    function handleAccountingModeChange(mode: AccountingMode) {
        if (mode === activeAccountingMode) return;
        router.visit(window.location.href, {
            method: 'get',
            data: { from: filters.from, to: filters.to, accounting_mode: mode },
            preserveState: true,
            preserveScroll: true,
            only: ['kpis', 'waterfall', 'breakdown_by_channel', 'trend_90d', 'filters'],
        });
    }

    function handleExportCsv() {
        const params = new URLSearchParams({
            from: filters.from,
            to: filters.to,
            accounting_mode: filters.accounting_mode,
            format: 'csv',
        });
        window.location.href = `/${workspace?.slug}/profit/export?${params.toString()}`;
    }

    return (
        <AppLayout>
            <Head title="Profit" />

            {/* ── Cost completeness alert ──────────────────────────────────── */}
            <CostCompletenessBanner completeness={cost_completeness} />

            <div className="space-y-6 p-6">

                {/* ── Page header ─────────────────────────────────────────── */}
                <PageHeader
                    title="Profit & Loss"
                    subtitle="Net profit = Revenue − COGS − Shipping − Fees − Ad Spend − OPEX. Six-source lens — Real (gold lightbulb) is Nexstage's reconciled computation."
                    action={
                        <div className="flex items-center gap-2">
                            <ExportMenu onExportCsv={handleExportCsv} />
                            <ShareSnapshotButton
                                workspaceSlug={workspace?.slug ?? ''}
                                page="profit"
                                urlState={`from=${filters.from}&to=${filters.to}`}
                            />
                        </div>
                    }
                />

                {/* ── Filter bar ───────────────────────────────────────────── */}
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-card px-4 py-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <AccountingModeSelector
                            value={activeAccountingMode}
                            onChange={handleAccountingModeChange}
                        />
                        <span className="text-sm text-muted-foreground hidden sm:inline">
                            {filters.from} – {filters.to}
                        </span>
                    </div>
                    <DateRangePicker />
                </div>

                {/* ── KPI strip — 6 MetricCardDetail tiles ────────────────── */}
                <KpiStrip kpis={kpis} />

                {/* ── Waterfall hero ──────────────────────────────────────── */}
                <WaterfallSection steps={waterfall} />

                {/* ── Breakdown DataTable ──────────────────────────────────── */}
                <BreakdownSection
                    breakdown_by_channel={breakdown_by_channel}
                    breakdown_by_store={breakdown_by_store}
                    breakdown_by_product={breakdown_by_product}
                    breakdown_by_category={breakdown_by_category}
                    breakdown_by_country={breakdown_by_country}
                    breakdown_by_customer_type={breakdown_by_customer_type}
                    currency={currency}
                />

                {/* ── 90d trend chart ──────────────────────────────────────── */}
                <TrendChart data={trend_90d} currency={currency} />

                {/* ── OPEX widget ─────────────────────────────────────────── */}
                <OpexWidget allocations={opex_allocations} currency={currency} />

                {/* ── Cost health strip ────────────────────────────────────── */}
                <CostHealthStrip completeness={cost_completeness} />

            </div>
        </AppLayout>
    );
}
