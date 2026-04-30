import { useMemo, useState, useEffect, useCallback } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Users, ShoppingBag, RotateCcw, Zap, TrendingUp, TrendingDown, Minus, Clock, AlertTriangle } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import { MetricCard } from '@/Components/shared/MetricCard';
import type { MetricSource } from '@/Components/shared/MetricCard';
import { MetricCardDetail } from '@/Components/shared/MetricCardDetail';
import { MetricCardMultiValue } from '@/Components/shared/MetricCardMultiValue';
import { RFMGrid } from '@/Components/shared/RFMGrid';
import type { RFMCell } from '@/Components/shared/RFMGrid';
import { AudienceTraits } from '@/Components/shared/AudienceTraits';
import type { AudienceTrait } from '@/Components/shared/AudienceTraits';
import { SourceToggle } from '@/Components/shared/SourceToggle';
import { availableSources } from '@/lib/source-availability';
import { InfoTooltip } from '@/Components/shared/Tooltip';
import { DataTable } from '@/Components/shared/DataTable';
import type { Column } from '@/Components/shared/DataTable';
import { ExportMenu } from '@/Components/shared/ExportMenu';
import { PageHeader } from '@/Components/shared/PageHeader';
import { ViewToggle } from '@/Components/shared/ViewToggle';
import { KpiGrid } from '@/Components/shared/KpiGrid';
import { EmptyState } from '@/Components/shared/EmptyState';
import { LoadingState } from '@/Components/shared/LoadingState';
import type { SegmentDrilldownData } from '@/Components/shared/SegmentDrilldown';
import { DrawerSidePanel } from '@/Components/shared/DrawerSidePanel';
import { LetterGradeBadge } from '@/Components/shared/LetterGradeBadge';
import type { Grade } from '@/Components/shared/LetterGradeBadge';
import { StatusDot } from '@/Components/shared/StatusDot';
import { LayerCakeChart } from '@/Components/charts/LayerCakeChart';
import type { LayerCakeCohort, LayerCakeDataPoint } from '@/Components/charts/LayerCakeChart';
import { Sparkline } from '@/Components/charts/Sparkline';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { formatCurrency, formatNumber, maskEmail } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';


// ─── Types ────────────────────────────────────────────────────────────────────

type Tab        = 'all' | 'segments' | 'cohorts' | 'ltv' | 'audiences';
type ViewType   = 'heatmap' | 'curves' | 'pacing';
type MetricType = 'revenue' | 'orders' | 'customers' | 'ltv';

// Customer row (full list / mock)
interface CustomerRow {
    id: string;
    email: string;
    name?: string;
    first_purchase_at: string;
    last_purchase_at: string;
    order_count: number;
    total_revenue: number;
    predicted_ltv: number;
    rfm_recency: number;
    rfm_frequency: number;
    rfm_monetary: number;
    rfm_grade: Grade;
    rfm_segment: string;
    predicted_next_purchase: string | null;
    sparkline_freq: number[];
    churn_risk: 'low' | 'medium' | 'high';
    primary_source: string;
    country: string;
    first_product_category: string;
}

// KPI strip
interface Kpis {
    total_customers: number;
    new_customers_period: number;
    returning_pct: number | null;
    avg_ltv: number | null;
    ltv_cac: number | null;
    repeat_order_rate: number | null;
    avg_time_to_2nd_order: number | null;
    churn_rate: number | null;
    ltv_30d: number | null;
    ltv_90d: number | null;
    ltv_365d: number | null;
    cac: number | null;
}

// RFM grid cell (5×5)
interface RfmGridCell {
    recency: number;
    frequency: number;
    count: number;
    revenue: number;
    segment_label: string;
}

// Cohort heatmap row
interface CohortMonth {
    offset: number;
    retention_pct: number | null;
    customers: number | null;
    revenue: number | null;
    orders: number | null;
    is_future: boolean;
    is_incomplete: boolean;
}
interface HeatmapRow {
    acquisition_month: string;
    label: string;
    size: number;
    low_confidence: boolean;
    cells: CohortMonth[];
}

// LayerCake raw data
interface LayerCakePoint {
    date: string;
    first: number;
    second: number;
    third: number;
    fourth_plus: number;
}

// Audience traits grouped
interface AudienceTraitsData {
    top_products:  AudienceTrait[];
    top_channels:  AudienceTrait[];
    top_countries: AudienceTrait[];
    top_devices:   AudienceTrait[];
}

// LTV curve point
interface LtvCurvePoint  { month: number; ltv: number; }
interface LtvCurve       { channel: string; data: LtvCurvePoint[]; }
interface ChannelLtv     { channel: string; ltv_30d: number | null; ltv_90d: number | null; ltv_365d: number | null; payback_days: number | null; }
interface LtvDriver      { id: string; product_name: string; customers: number; avg_ltv: number | null; avg_aov: number | null; ltv_cac: number | null; repeat_rate: number | null; revenue_pct: number | null; }
interface ChannelCacPoint { month: string; cac_facebook: number | null; cac_google: number | null; }

// RFM segment tile
interface RfmSegment {
    name: string;
    slug: string;
    count: number;
    pct: number;
    avg_ltv: number | null;
    avg_aov: number | null;
    revenue_pct: number | null;
    trend: number | null;
    description: string;
    color: string;
}

// Cohort helpers
interface CohortSummary {
    cohort_count: number;
    avg_cohort_size: number;
    best_m1_cohort: string | null;
    best_m1_rate_pct: number;
}
interface CurvePoint   { offset: number; value: number; }
interface CurveSeries  { acquisition_month: string; label: string; size: number; points: CurvePoint[]; }
interface PacingPoint  { offset: number; value: number; }
interface PacingData   { current: PacingPoint[]; average: PacingPoint[]; current_label: string | null; }
interface AvailableChannel { value: string; label: string; low_data: boolean; }

interface Props extends PageProps {
    // KPI strip
    kpis?: Kpis;
    // Legacy compat (old controller shape — kept so existing data still renders)
    metrics?: {
        total: number;
        new_30d: number;
        ncr: number | null;
        ltv_30d: number | null;
        ltv_90d: number | null;
        ltv_365d: number | null;
        repeat_rate: number | null;
    };
    // All Customers tab
    customers?: CustomerRow[];
    // RFM
    rfm_segments?: RfmSegment[];
    rfm_cells?: RFMCell[];
    rfm_grid?: RfmGridCell[];
    segment_traits?: Record<string, AudienceTrait[]>;
    segment_drilldown?: SegmentDrilldownData | null;
    // Cohort (retention)
    heatmap_rows?: HeatmapRow[];
    curve_series?: CurveSeries[];
    pacing?: PacingData;
    max_offset?: number;
    cohort_summary?: CohortSummary;
    available_channels?: AvailableChannel[];
    low_confidence_threshold?: number;
    // LTV
    ltv_curves?: LtvCurve[];
    channel_ltv?: ChannelLtv[];
    ltv_drivers?: LtvDriver[];
    cac?: number | null;
    ltv_cac?: number | null;
    channel_cac_trend?: ChannelCacPoint[];
    median_days_to_second_order?: number | null;
    layercake?: LayerCakePoint[];
    // Audiences
    audience_traits?: AudienceTraitsData;
    // Page state
    active_tab?: Tab;
    ltv_calibrating?: boolean;
    ltv_calibration_day?: number;
    filters?: {
        source?: MetricSource;
        tab?: Tab;
        segment?: string | null;
        page?: number;
        cohort_period?: number;
        cohort_metric?: MetricType;
        cohort_view?: ViewType;
        cohort_channel?: string;
    };
}

// ─── Mock data (500 customers) ────────────────────────────────────────────────
// Distribution: ~30% one-and-done, ~50% repeat 2-5×, ~15% loyal 6-20×, ~5% champions 20+×
// Generated deterministically so SSR and CSR match.

const SEGMENT_LABELS = ['Champions', 'Loyal', 'Potential Loyalist', 'At Risk', 'About to Sleep', 'Needs Attention', 'New', 'One-time'] as const;
const FIRST_NAMES = ['James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda', 'William', 'Barbara', 'David', 'Elizabeth', 'Richard', 'Susan', 'Joseph', 'Jessica', 'Thomas', 'Sarah', 'Charles', 'Karen'];
const LAST_NAMES  = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Wilson', 'Anderson', 'Taylor', 'Thomas', 'Hernandez', 'Moore', 'Martin', 'Jackson', 'Thompson', 'White', 'Lopez', 'Lee'];
const DOMAINS     = ['gmail.com', 'yahoo.com', 'outlook.com', 'icloud.com', 'hotmail.com', 'example.com'];
const COUNTRIES   = ['US', 'US', 'US', 'US', 'GB', 'CA', 'AU', 'DE', 'FR', 'NL'];
const SOURCES     = ['facebook', 'google', 'store', 'facebook', 'google', 'store', 'facebook', 'real', 'gsc', 'ga4'];
const CATEGORIES  = ['Apparel', 'Footwear', 'Accessories', 'Home', 'Beauty', 'Electronics', 'Sports', 'Books'];
const GRADES      = ['A', 'A', 'B', 'B', 'B', 'C', 'C', 'D'] as Grade[];

function seededRand(seed: number): number {
    // Simple LCG for deterministic pseudo-random
    const x = Math.sin(seed + 1) * 10000;
    return x - Math.floor(x);
}

function buildMockCustomers(): CustomerRow[] {
    const customers: CustomerRow[] = [];
    const base = new Date('2026-04-28');

    for (let i = 0; i < 500; i++) {
        const r1 = seededRand(i * 7 + 1);
        const r2 = seededRand(i * 7 + 2);
        const r3 = seededRand(i * 7 + 3);
        const r4 = seededRand(i * 7 + 4);
        const r5 = seededRand(i * 7 + 5);
        const r6 = seededRand(i * 7 + 6);
        const r7 = seededRand(i * 7 + 7);

        // Order count distribution: 30% 1, 50% 2-5, 15% 6-20, 5% 21+
        let orderCount: number;
        if (r1 < 0.30)      orderCount = 1;
        else if (r1 < 0.80) orderCount = Math.floor(r2 * 4) + 2;
        else if (r1 < 0.95) orderCount = Math.floor(r2 * 15) + 6;
        else                 orderCount = Math.floor(r2 * 30) + 21;

        const avgOrderValue = 60 + r3 * 200;
        const totalRevenue  = +(avgOrderValue * orderCount * (0.85 + r4 * 0.3)).toFixed(2);
        const predictedLtv  = +(totalRevenue * (1 + r5 * 0.8)).toFixed(2);

        // RFM scores (1–5)
        const rfmR = Math.min(5, Math.max(1, Math.ceil(r1 * 5)));
        const rfmF = Math.min(5, Math.max(1, Math.ceil(seededRand(i * 3 + 11) * 5)));
        const rfmM = Math.min(5, Math.max(1, Math.ceil(seededRand(i * 3 + 22) * 5)));

        // Segment from RFM composite
        let segment: string;
        const composite = rfmR + rfmF + rfmM;
        if (composite >= 13)         segment = 'Champions';
        else if (composite >= 10)    segment = 'Loyal';
        else if (rfmR >= 4)          segment = 'Potential Loyalist';
        else if (rfmR <= 2 && rfmF >= 3) segment = 'At Risk';
        else if (rfmR <= 2)          segment = 'About to Sleep';
        else if (orderCount === 1)   segment = 'New';
        else                         segment = 'Needs Attention';

        const grade = rfmR >= 4 && rfmF >= 4 ? 'A'
            : composite >= 10 ? 'B'
            : composite >= 7  ? 'C' : 'D';

        // Dates
        const daysSinceFirst = Math.floor(30 + r2 * 500);
        const firstDate = new Date(base);
        firstDate.setDate(firstDate.getDate() - daysSinceFirst);
        const daysSinceLast = Math.floor(r3 * Math.min(daysSinceFirst, 120));
        const lastDate = new Date(base);
        lastDate.setDate(lastDate.getDate() - daysSinceLast);

        // Predicted next purchase
        const daysToNext = Math.floor(14 + r4 * 60);
        const nextDate = new Date(base);
        nextDate.setDate(nextDate.getDate() + daysToNext);

        // Sparkline (12-point purchase frequency per month)
        const sparkline: number[] = [];
        for (let m = 0; m < 12; m++) {
            const prob = orderCount > 10 ? 0.7 : orderCount > 5 ? 0.5 : orderCount > 2 ? 0.3 : 0.15;
            sparkline.push(seededRand(i * 100 + m) < prob ? Math.ceil(seededRand(i * 100 + m + 50) * 2) : 0);
        }

        const churnRisk: 'low' | 'medium' | 'high' =
            rfmR >= 4 ? 'low' : rfmR >= 2 ? 'medium' : 'high';

        const firstName = FIRST_NAMES[Math.floor(r5 * FIRST_NAMES.length)];
        const lastName  = LAST_NAMES[Math.floor(r6 * LAST_NAMES.length)];
        const domain    = DOMAINS[Math.floor(r7 * DOMAINS.length)];

        customers.push({
            id: `cust_${String(i).padStart(3, '0')}`,
            email: `${firstName.toLowerCase()}.${lastName.toLowerCase()}${i}@${domain}`,
            name: `${firstName} ${lastName}`,
            first_purchase_at: firstDate.toISOString(),
            last_purchase_at:  lastDate.toISOString(),
            order_count:       orderCount,
            total_revenue:     totalRevenue,
            predicted_ltv:     predictedLtv,
            rfm_recency:   rfmR,
            rfm_frequency: rfmF,
            rfm_monetary:  rfmM,
            rfm_grade: grade as Grade,
            rfm_segment: segment,
            predicted_next_purchase: rfmR >= 3 ? nextDate.toISOString() : null,
            sparkline_freq: sparkline,
            churn_risk: churnRisk,
            primary_source: SOURCES[Math.floor(r4 * SOURCES.length)],
            country: COUNTRIES[Math.floor(r5 * COUNTRIES.length)],
            first_product_category: CATEGORIES[Math.floor(r6 * CATEGORIES.length)],
        });
    }
    return customers;
}

// Build 5×5 RFM grid cells
function buildMockRfmGrid(): RfmGridCell[] {
    const ZONE_MAP: Record<string, string> = {
        '5-5': 'Champions', '5-4': 'Champions', '4-5': 'Loyal',
        '4-4': 'Loyal',     '5-3': 'Loyal',     '3-5': 'Potential Loyalist',
        '3-4': 'Potential Loyalist', '4-3': 'Potential Loyalist',
        '3-3': 'Potential Loyalist', '5-2': 'Needs Attention',
        '2-5': 'At Risk',   '2-4': 'At Risk',   '1-5': 'At Risk',
        '1-4': 'At Risk',   '2-3': 'About to Sleep', '1-3': 'About to Sleep',
        '2-2': 'About to Sleep', '1-2': 'About to Sleep',
        '1-1': 'About to Sleep', '2-1': 'About to Sleep',
        '3-2': 'Needs Attention', '3-1': 'Needs Attention',
        '4-2': 'Needs Attention', '4-1': 'Needs Attention',
        '5-1': 'Needs Attention',
    };
    const cells: RfmGridCell[] = [];
    for (let r = 5; r >= 1; r--) {
        for (let f = 1; f <= 5; f++) {
            const key = `${r}-${f}`;
            const baseCnt = r * f * 2 + Math.floor(seededRand(r * 10 + f) * 15);
            cells.push({
                recency: r,
                frequency: f,
                count: baseCnt,
                revenue: baseCnt * (50 + r * 20 + f * 10) * (1 + seededRand(r + f * 7) * 0.5),
                segment_label: ZONE_MAP[key] ?? 'Needs Attention',
            });
        }
    }
    return cells;
}

// Build 12-month cohort heatmap
function buildMockHeatmap(): HeatmapRow[] {
    const rows: HeatmapRow[] = [];
    const base = new Date('2026-04-01');
    for (let mo = 11; mo >= 0; mo--) {
        const cohortDate = new Date(base);
        cohortDate.setMonth(cohortDate.getMonth() - mo);
        const label = cohortDate.toLocaleString('en-US', { month: 'short', year: 'numeric' });
        const size  = 40 + Math.floor(seededRand(mo * 3) * 80);
        const maxOffset = 11 - mo; // older cohorts have more months observed
        const cells: CohortMonth[] = [];
        for (let offset = 0; offset <= 11; offset++) {
            const isFuture = offset > maxOffset;
            const isIncomplete = offset === maxOffset && mo === 0;
            if (isFuture) {
                cells.push({ offset, retention_pct: null, customers: null, revenue: null, orders: null, is_future: true, is_incomplete: false });
            } else {
                // Decay function: 100% at 0, ~30% at 11
                const decayBase = 1.0 - offset * 0.063;
                const noise = (seededRand(mo * 20 + offset) - 0.5) * 0.08;
                const retPct = Math.max(3, Math.min(100, Math.round((decayBase + noise) * 100)));
                const custCount = Math.round(size * retPct / 100);
                cells.push({
                    offset,
                    retention_pct: retPct,
                    customers: custCount,
                    revenue: custCount * (80 + seededRand(mo + offset) * 60),
                    orders: custCount,
                    is_future: false,
                    is_incomplete: isIncomplete,
                });
            }
        }
        rows.push({ acquisition_month: cohortDate.toISOString().slice(0, 7), label, size, low_confidence: size < 15, cells });
    }
    return rows;
}

// Build LayerCake data (90-day daily, 4 ordinal layers)
function buildMockLayerCake(): LayerCakePoint[] {
    const points: LayerCakePoint[] = [];
    const base = new Date('2026-01-28');
    for (let d = 89; d >= 0; d--) {
        const date = new Date(base);
        date.setDate(date.getDate() - d);
        const label = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const trend = 1 + (89 - d) * 0.003; // slight upward trend
        const noise = () => (seededRand(d * 13 + Math.random() * 100) - 0.5) * 0.2;
        points.push({
            date: label,
            first:       Math.round(3800 * trend * (1 + noise())),
            second:      Math.round(1600 * trend * (1 + noise())),
            third:       Math.round(800  * trend * (1 + noise())),
            fourth_plus: Math.round(400  * trend * (1 + noise())),
        });
    }
    return points;
}

const MOCK_CUSTOMERS   = buildMockCustomers();
const MOCK_RFM_GRID    = buildMockRfmGrid();
const MOCK_HEATMAP     = buildMockHeatmap();
const MOCK_LAYERCAKE   = buildMockLayerCake();

// Convert layercake to LayerCakeChart format
function toLayerCakeProps(raw: LayerCakePoint[]): { cohorts: LayerCakeCohort[]; data: LayerCakeDataPoint[] } {
    const cohorts: LayerCakeCohort[] = [
        { id: 'first',       label: '1st Order'   },
        { id: 'second',      label: '2nd Order'   },
        { id: 'third',       label: '3rd Order'   },
        { id: 'fourth_plus', label: '4th+ Orders' },
    ];
    // Downsample to every 7th point for readability (weekly-ish)
    const sampled = raw.filter((_, i) => i % 7 === 0 || i === raw.length - 1);
    const data: LayerCakeDataPoint[] = sampled.map((p) => ({
        period:      p.date,
        first:       p.first,
        second:      p.second,
        third:       p.third,
        fourth_plus: p.fourth_plus,
    }));
    return { cohorts, data };
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function relativeDate(iso: string): string {
    const diff = Date.now() - new Date(iso).getTime();
    const days = Math.floor(diff / 86400000);
    if (days === 0) return 'Today';
    if (days === 1) return 'Yesterday';
    if (days < 30)  return `${days}d ago`;
    if (days < 365) return `${Math.floor(days / 30)}mo ago`;
    return `${Math.floor(days / 365)}y ago`;
}

function shortDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: '2-digit' });
}

function ucFirst(str: string): string {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : str;
}

// ─── Churn risk badge ─────────────────────────────────────────────────────────

function ChurnRiskBadge({ risk }: { risk: 'low' | 'medium' | 'high' }) {
    return (
        <span className={cn(
            'inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium',
            risk === 'low'    && 'bg-emerald-50 text-emerald-700',
            risk === 'medium' && 'bg-amber-50 text-amber-700',
            risk === 'high'   && 'bg-rose-50 text-rose-700',
        )}>
            {ucFirst(risk)}
        </span>
    );
}

// ─── Source dot (inline, for table cells) ─────────────────────────────────────

const SOURCE_COLORS: Record<string, string> = {
    store:    '#64748b',
    facebook: '#6366f1',
    google:   '#f59e0b',
    gsc:      '#10b981',
    ga4:      '#8b5cf6',
    real:     '#facc15',
};

function SourceDot({ source }: { source: string }) {
    return (
        <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
            <span
                className="inline-block h-2 w-2 rounded-full"
                style={{ backgroundColor: SOURCE_COLORS[source] ?? '#94a3b8' }}
            />
            {ucFirst(source)}
        </span>
    );
}

// ─── Trend arrow ──────────────────────────────────────────────────────────────

function TrendArrow({ value }: { value: number | null }) {
    if (value === null) return null;
    if (value > 0) return (
        <span className="inline-flex items-center gap-0.5 text-sm font-medium text-emerald-600">
            <TrendingUp className="h-3 w-3" />+{formatNumber(value)}
        </span>
    );
    if (value < 0) return (
        <span className="inline-flex items-center gap-0.5 text-sm font-medium text-rose-600">
            <TrendingDown className="h-3 w-3" />{formatNumber(value)}
        </span>
    );
    return (
        <span className="inline-flex items-center gap-0.5 text-sm font-medium text-muted-foreground">
            <Minus className="h-3 w-3" />0
        </span>
    );
}

// ─── Tab bar ─────────────────────────────────────────────────────────────────

function TabBar({ activeTab, onTabChange }: { activeTab: Tab; onTabChange: (t: Tab) => void }) {
    const tabs: { key: Tab; label: string }[] = [
        { key: 'all',       label: 'All Customers'  },
        { key: 'segments',  label: 'RFM Segments'   },
        { key: 'cohorts',   label: 'Cohorts'        },
        { key: 'ltv',       label: 'Lifetime Value' },
        { key: 'audiences', label: 'Audience Traits'},
    ];
    return (
        <div className="flex overflow-x-auto border-b border-border scrollbar-none">
            {tabs.map((t) => (
                <button
                    key={t.key}
                    type="button"
                    onClick={() => onTabChange(t.key)}
                    className={cn(
                        'whitespace-nowrap px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px',
                        activeTab === t.key
                            ? 'border-teal-600 text-teal-600'
                            : 'border-transparent text-muted-foreground hover:text-foreground',
                    )}
                >
                    {t.label}
                </button>
            ))}
        </div>
    );
}

// ─── Customer detail drawer ───────────────────────────────────────────────────

function CustomerDrawer({
    customer,
    currency,
    onClose,
}: {
    customer: CustomerRow | null;
    currency: string;
    onClose: () => void;
}) {
    if (!customer) return null;

    const statusColor: 'emerald' | 'amber' | 'rose' =
        customer.churn_risk === 'low' ? 'emerald' :
        customer.churn_risk === 'medium' ? 'amber' : 'rose';

    // Mock order history (3-7 orders)
    const orders = Array.from({ length: Math.min(customer.order_count, 7) }, (_, i) => {
        const daysAgo = Math.floor((i + 1) * (365 / Math.max(customer.order_count, 1)));
        const d = new Date('2026-04-28');
        d.setDate(d.getDate() - daysAgo);
        return {
            id: `ord_${customer.id}_${i}`,
            date: d.toISOString(),
            amount: +(customer.total_revenue / customer.order_count * (0.8 + seededRand(i * 13) * 0.4)).toFixed(2),
            source: customer.primary_source,
        };
    });

    // Mock touchpoints
    const touchpoints = [
        { channel: 'facebook', type: 'Click', daysAgo: 45, value: null },
        { channel: 'google', type: 'Session', daysAgo: 30, value: null },
        { channel: customer.primary_source, type: 'Purchase', daysAgo: Math.floor(seededRand(parseInt(customer.id.slice(-3)) * 7) * 30), value: orders[orders.length - 1]?.amount ?? null },
    ].filter((_, i) => i < 3);

    return (
        <DrawerSidePanel open onClose={onClose} title={customer.email} subtitle={
            <span className="flex items-center gap-2">
                <StatusDot status={customer.churn_risk === 'low' ? 'success' : customer.churn_risk === 'medium' ? 'warning' : 'error'} />
                <span className="text-sm text-muted-foreground">{customer.name} · {customer.country}</span>
            </span>
        } width={480}>
            {/* Metrics strip */}
            <div className="grid grid-cols-3 gap-3 mb-6">
                {[
                    { label: 'Orders',    value: String(customer.order_count) },
                    { label: 'Total LTV', value: formatCurrency(customer.total_revenue, currency, true) },
                    { label: 'Pred. LTV', value: formatCurrency(customer.predicted_ltv, currency, true) },
                    { label: 'RFM Grade', value: customer.rfm_grade },
                    { label: 'Segment',   value: customer.rfm_segment },
                    { label: 'Churn Risk', value: ucFirst(customer.churn_risk) },
                ].map(({ label, value }) => (
                    <div key={label} className="rounded-lg border border-border bg-card p-3">
                        <p className="text-xs text-muted-foreground">{label}</p>
                        <p className="mt-0.5 text-sm font-semibold text-foreground tabular-nums">{value}</p>
                    </div>
                ))}
            </div>

            {/* RFM scores */}
            <div className="mb-6">
                <h4 className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">RFM Scores</h4>
                <div className="flex gap-3">
                    {[
                        { label: 'Recency',   value: customer.rfm_recency },
                        { label: 'Frequency', value: customer.rfm_frequency },
                        { label: 'Monetary',  value: customer.rfm_monetary },
                    ].map(({ label, value }) => (
                        <div key={label} className="flex-1 rounded-lg border border-border bg-muted/40 p-2 text-center">
                            <p className="text-xs text-muted-foreground">{label}</p>
                            <p className="mt-0.5 text-xl font-bold tabular-nums text-foreground">{value}</p>
                            <div className="mt-1 flex justify-center gap-0.5">
                                {[1,2,3,4,5].map((pip) => (
                                    <span key={pip} className={cn('h-1.5 w-1.5 rounded-full', pip <= value ? 'bg-teal-500' : 'bg-muted')} />
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Order history */}
            <div className="mb-6">
                <h4 className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Order History</h4>
                <div className="rounded-lg border border-border overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 border-b border-border">
                            <tr>
                                <th className="px-3 py-2 text-left text-xs font-semibold text-muted-foreground">Date</th>
                                <th className="px-3 py-2 text-right text-xs font-semibold text-muted-foreground">Amount</th>
                                <th className="px-3 py-2 text-right text-xs font-semibold text-muted-foreground">Source</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {orders.map((o) => (
                                <tr key={o.id} className="hover:bg-muted/30 transition-colors">
                                    <td className="px-3 py-2 text-muted-foreground">{shortDate(o.date)}</td>
                                    <td className="px-3 py-2 text-right tabular-nums font-medium text-foreground">{formatCurrency(o.amount, currency, true)}</td>
                                    <td className="px-3 py-2 text-right"><SourceDot source={o.source} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {customer.order_count > 7 && (
                        <p className="px-3 py-2 text-xs text-muted-foreground border-t border-border">Showing 7 of {customer.order_count} orders.</p>
                    )}
                </div>
            </div>

            {/* Touchpoint journey */}
            <div className="mb-6">
                <h4 className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Touchpoint Journey</h4>
                <div className="flex items-center gap-2">
                    {touchpoints.map((tp, idx) => (
                        <div key={idx} className="flex items-center gap-2">
                            <div className="rounded-md border border-border bg-card px-2.5 py-1.5 text-xs">
                                <p className="font-medium text-foreground">{ucFirst(tp.channel)}</p>
                                <p className="text-muted-foreground">{tp.type} · {tp.daysAgo}d ago</p>
                            </div>
                            {idx < touchpoints.length - 1 && (
                                <span className="text-muted-foreground text-xs">→</span>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            {/* Audience traits */}
            <div>
                <h4 className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Segment: {customer.rfm_segment}</h4>
                <div className="flex flex-wrap gap-1.5">
                    {[customer.rfm_segment, customer.country, customer.first_product_category].map((chip) => (
                        <span key={chip} className="rounded-full border border-border bg-muted px-2.5 py-0.5 text-xs font-medium text-foreground">
                            {chip}
                        </span>
                    ))}
                </div>
            </div>
        </DrawerSidePanel>
    );
}

// ─── All Customers tab ────────────────────────────────────────────────────────

function AllCustomersTab({
    customers,
    currency,
}: {
    customers: CustomerRow[];
    currency: string;
}) {
    const [selectedCustomer, setSelectedCustomer] = useState<CustomerRow | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [segmentFilter, setSegmentFilter] = useState('all');
    const [riskFilter, setRiskFilter] = useState('all');

    const filtered = useMemo(() => {
        return customers.filter((c) => {
            if (searchQuery && !c.email.toLowerCase().includes(searchQuery.toLowerCase()) && !c.name?.toLowerCase().includes(searchQuery.toLowerCase())) return false;
            if (segmentFilter !== 'all' && c.rfm_segment !== segmentFilter) return false;
            if (riskFilter !== 'all' && c.churn_risk !== riskFilter) return false;
            return true;
        });
    }, [customers, searchQuery, segmentFilter, riskFilter]);

    const columns: Column<CustomerRow>[] = [
        {
            key: 'email',
            header: 'Customer',
            sortable: true,
            render: (value, row) => (
                <div className="min-w-0">
                    <p className="truncate font-medium text-foreground text-sm">{row.name}</p>
                    <p className="truncate font-mono text-xs text-muted-foreground">{String(value)}</p>
                </div>
            ),
        },
        {
            key: 'first_purchase_at',
            header: '1st Purchase',
            sortable: true,
            render: (value) => <span className="text-sm text-muted-foreground tabular-nums">{shortDate(String(value))}</span>,
        },
        {
            key: 'last_purchase_at',
            header: 'Last Purchase',
            sortable: true,
            render: (value) => <span className="text-sm text-muted-foreground tabular-nums">{relativeDate(String(value))}</span>,
        },
        {
            key: 'order_count',
            header: 'Orders',
            sortable: true,
            render: (value) => <span className="tabular-nums text-sm font-medium text-foreground">{formatNumber(Number(value))}</span>,
        },
        {
            key: 'total_revenue',
            header: 'Lifetime Rev.',
            sortable: true,
            render: (value) => <span className="tabular-nums text-sm font-medium text-foreground">{formatCurrency(Number(value), currency, true)}</span>,
        },
        {
            key: 'predicted_ltv',
            header: 'Pred. LTV',
            sortable: true,
            render: (value) => <span className="tabular-nums text-sm text-muted-foreground">{formatCurrency(Number(value), currency, true)}</span>,
        },
        {
            key: 'rfm_grade',
            header: 'RFM Grade',
            sortable: true,
            render: (value) => <LetterGradeBadge grade={value as Grade} size="sm" />,
        },
        {
            key: 'rfm_segment',
            header: 'Segment',
            sortable: true,
            render: (value) => (
                <span className="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-muted text-foreground">
                    {String(value)}
                </span>
            ),
        },
        {
            key: 'predicted_next_purchase',
            header: 'Next Purchase',
            sortable: false,
            render: (value) => value
                ? <span className="text-sm text-muted-foreground tabular-nums">{shortDate(String(value))}</span>
                : <span className="text-sm text-muted-foreground/50">—</span>,
        },
        {
            key: 'sparkline_freq',
            header: 'Freq.',
            sortable: false,
            render: (value) => (
                <Sparkline
                    data={(value as number[]).map((v) => ({ value: v }))}
                    height={28}
                    color="var(--color-teal-600, #0d9488)"
                    className="w-16"
                />
            ),
        },
        {
            key: 'churn_risk',
            header: 'Churn Risk',
            sortable: true,
            render: (value) => <ChurnRiskBadge risk={value as 'low' | 'medium' | 'high'} />,
        },
        {
            key: 'primary_source',
            header: 'Source',
            sortable: true,
            render: (value) => <SourceDot source={String(value)} />,
        },
    ];

    const uniqueSegments = useMemo(() =>
        [...new Set(customers.map((c) => c.rfm_segment))].sort(),
    [customers]);

    return (
        <div className="space-y-4">
            {/* Filter bar */}
            <div className="flex flex-wrap items-center gap-3">
                <input
                    type="search"
                    placeholder="Search by email or name…"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="min-h-[36px] w-56 rounded-md border border-border bg-card px-3 py-1.5 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-teal-500"
                    aria-label="Search customers"
                />
                <select
                    value={segmentFilter}
                    onChange={(e) => setSegmentFilter(e.target.value)}
                    className="min-h-[36px] rounded-md border border-border bg-card px-3 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-teal-500"
                    aria-label="Filter by segment"
                >
                    <option value="all">All Segments</option>
                    {uniqueSegments.map((s) => <option key={s} value={s}>{s}</option>)}
                </select>
                <select
                    value={riskFilter}
                    onChange={(e) => setRiskFilter(e.target.value)}
                    className="min-h-[36px] rounded-md border border-border bg-card px-3 py-1.5 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-teal-500"
                    aria-label="Filter by churn risk"
                >
                    <option value="all">Any Churn Risk</option>
                    <option value="low">Low Risk</option>
                    <option value="medium">Medium Risk</option>
                    <option value="high">High Risk</option>
                </select>
                <span className="ml-auto text-sm text-muted-foreground tabular-nums">
                    {formatNumber(filtered.length)} of {formatNumber(customers.length)} customers
                </span>
            </div>

            {/* Table */}
            <DataTable
                data={filtered}
                columns={columns}
                onRowClick={(row) => setSelectedCustomer(row)}
                emptyMessage="No customers match"
                emptyDescription="Try widening your filters."
            />

            {/* Customer drawer */}
            {selectedCustomer && (
                <CustomerDrawer
                    customer={selectedCustomer}
                    currency={currency}
                    onClose={() => setSelectedCustomer(null)}
                />
            )}
        </div>
    );
}

// ─── RFM Segments tab ────────────────────────────────────────────────────────

const SEGMENT_COLORS: Record<string, { bg: string; text: string; border: string }> = {
    Champions:            { bg: 'bg-emerald-50', text: 'text-emerald-800', border: 'border-emerald-200' },
    Loyal:                { bg: 'bg-sky-50',     text: 'text-sky-800',     border: 'border-sky-200'     },
    'Potential Loyalist': { bg: 'bg-violet-50',  text: 'text-violet-800',  border: 'border-violet-200'  },
    'At Risk':            { bg: 'bg-amber-50',   text: 'text-amber-800',   border: 'border-amber-200'   },
    'About to Sleep':     { bg: 'bg-orange-50',  text: 'text-orange-800',  border: 'border-orange-200'  },
    'Needs Attention':    { bg: 'bg-rose-50',    text: 'text-rose-800',    border: 'border-rose-200'    },
};

function SegmentTile({
    segment,
    currency,
    active,
    onClick,
}: {
    segment: RfmSegment;
    currency: string;
    active: boolean;
    onClick: () => void;
}) {
    const colors = SEGMENT_COLORS[segment.name] ?? { bg: 'bg-muted', text: 'text-foreground', border: 'border-border' };
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'w-full rounded-lg border p-4 text-left transition-all',
                colors.bg, colors.border,
                active ? 'ring-2 ring-teal-500 ring-offset-1' : 'hover:ring-1 hover:ring-teal-400/50',
            )}
        >
            <p className={cn('text-sm font-semibold', colors.text)}>{segment.name}</p>
            <p className="mt-0.5 text-xl font-bold tabular-nums text-foreground">{formatNumber(segment.count)}</p>
            <div className="mt-2 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
                <span>{segment.pct.toFixed(1)}% of customers</span>
                {segment.avg_ltv != null && (
                    <span>LTV: {formatCurrency(segment.avg_ltv, currency, true)}</span>
                )}
                {segment.trend != null && (
                    <TrendArrow value={segment.trend} />
                )}
            </div>
            <p className="mt-2 text-xs text-muted-foreground/70 line-clamp-2 leading-snug">{segment.description}</p>
        </button>
    );
}

function SegmentsTab({
    rfmSegments,
    rfmCells,
    rfmGrid,
    currency,
    workspaceSlug,
    activeSegmentSlug,
    segmentDrilldown,
    customers,
    onSelectSegment,
    onDeselectSegment,
    onPageChange,
}: {
    rfmSegments: RfmSegment[];
    rfmCells: RFMCell[];
    rfmGrid: RfmGridCell[];
    currency: string;
    workspaceSlug: string | undefined;
    activeSegmentSlug: string | null;
    segmentDrilldown: SegmentDrilldownData | null;
    customers: CustomerRow[];
    onSelectSegment: (slug: string) => void;
    onDeselectSegment: () => void;
    onPageChange: (page: number) => void;
}) {
    const [selectedCustomer, setSelectedCustomer] = useState<CustomerRow | null>(null);

    // SegmentTiles from mock customers when rfmSegments is empty
    const tiles = useMemo<RfmSegment[]>(() => {
        if (rfmSegments && rfmSegments.length > 0) return rfmSegments;
        // Build from mock customers
        const bySegment: Record<string, CustomerRow[]> = {};
        customers.forEach((c) => {
            if (!bySegment[c.rfm_segment]) bySegment[c.rfm_segment] = [];
            bySegment[c.rfm_segment].push(c);
        });
        const total = customers.length;
        return Object.entries(bySegment).map(([name, rows]) => {
            const avgLtv = rows.reduce((s, c) => s + c.total_revenue, 0) / rows.length;
            return {
                name,
                slug: name.toLowerCase().replace(/\s+/g, '_'),
                count: rows.length,
                pct: (rows.length / total) * 100,
                avg_ltv: avgLtv,
                avg_aov: avgLtv / (rows.reduce((s, c) => s + c.order_count, 0) / rows.length),
                revenue_pct: null,
                trend: null,
                description: SEGMENT_DESCRIPTIONS[name] ?? '',
                color: '',
            };
        }).sort((a, b) => b.count - a.count);
    }, [rfmSegments, customers]);

    // RFM grid from mock data when rfmCells is empty
    const gridCells = useMemo<RFMCell[]>(() => {
        if (rfmCells && rfmCells.length > 0) return rfmCells;
        return rfmGrid.map((g) => ({
            r: g.recency,
            fm: g.frequency,
            count: g.count,
            segment: g.segment_label,
        }));
    }, [rfmCells, rfmGrid]);

    function handleCellClick(cell: RFMCell) {
        const slug = cell.segment.toLowerCase().replace(/\s+/g, '_');
        onSelectSegment(slug);
    }

    // Filtered customers for selected segment
    const segmentCustomers = useMemo(() => {
        if (!activeSegmentSlug) return [];
        return customers.filter((c) => c.rfm_segment.toLowerCase().replace(/\s+/g, '_') === activeSegmentSlug);
    }, [activeSegmentSlug, customers]);

    return (
        <div className="space-y-6">
            <p className="text-sm text-muted-foreground">
                RFM segments group customers by how recently they purchased, how often, and how much. Use Champions for VIP outreach and At Risk for win-back campaigns.
            </p>

            {/* Segment tiles (Segments Analytics pattern) */}
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                {tiles.slice(0, 6).map((seg) => (
                    <SegmentTile
                        key={seg.slug}
                        segment={seg}
                        currency={currency}
                        active={activeSegmentSlug === seg.slug}
                        onClick={() => activeSegmentSlug === seg.slug ? onDeselectSegment() : onSelectSegment(seg.slug)}
                    />
                ))}
            </div>

            {/* RFM Grid (Peel / Klaviyo pattern) */}
            <div className="rounded-xl border border-border bg-card p-5">
                <h3 className="mb-1 text-sm font-semibold text-foreground">RFM Matrix</h3>
                <p className="mb-4 text-sm text-muted-foreground">
                    5×5 grid: rows = Recency (5 = most recent), columns = Frequency. Color intensity = Monetary value.
                    Champions (top-right) bought recently, buy often, spend most. Click a cell to drill down.
                </p>
                {gridCells.length > 0
                    ? <RFMGrid cells={gridCells} onCellClick={handleCellClick} />
                    : <EmptyState title="No RFM data" description="Requires at least 30 days of orders." />
                }
            </div>

            {/* Segment drill-down — real data preferred, mock fallback */}
            {activeSegmentSlug && (
                <div className="rounded-xl border border-border bg-card overflow-hidden">
                    <div className="flex items-center justify-between gap-3 border-b border-border px-5 py-3">
                        <div>
                            <h3 className="text-sm font-semibold text-foreground">
                                {ucFirst(activeSegmentSlug.replace(/_/g, ' '))} — {formatNumber(segmentCustomers.length)} customers
                            </h3>
                            <p className="text-xs text-muted-foreground mt-0.5">Click a row to open full customer detail.</p>
                        </div>
                        <button
                            type="button"
                            onClick={onDeselectSegment}
                            className="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Clear
                        </button>
                    </div>
                    <div className="p-5">
                        {false /* SegmentDrilldown requires segment meta — shown via mock list below */
                            ? null
                            : (
                                <div className="space-y-2">
                                    {segmentCustomers.slice(0, 10).map((c) => (
                                        <button
                                            key={c.id}
                                            type="button"
                                            onClick={() => setSelectedCustomer(c)}
                                            className="flex w-full items-center gap-4 rounded-lg border border-border bg-card px-4 py-2.5 text-left text-sm hover:bg-muted/40 transition-colors"
                                        >
                                            <span className="flex-1 min-w-0">
                                                <span className="block font-medium text-foreground truncate">{c.name}</span>
                                                {/* maskEmail: j***@domain — full email in drawer title @see docs/competitors/_research_pii_masking.md */}
                                                <span className="block font-mono text-xs text-muted-foreground truncate">{maskEmail(c.email)}</span>
                                            </span>
                                            <span className="tabular-nums text-muted-foreground">{formatCurrency(c.total_revenue, currency, true)}</span>
                                            <LetterGradeBadge grade={c.rfm_grade} size="sm" />
                                        </button>
                                    ))}
                                    {segmentCustomers.length > 10 && (
                                        <p className="text-xs text-muted-foreground text-center pt-1">+{formatNumber(segmentCustomers.length - 10)} more customers</p>
                                    )}
                                </div>
                            )
                        }
                    </div>
                </div>
            )}

            {selectedCustomer && (
                <CustomerDrawer customer={selectedCustomer} currency={currency} onClose={() => setSelectedCustomer(null)} />
            )}
        </div>
    );
}

const SEGMENT_DESCRIPTIONS: Record<string, string> = {
    Champions:            'Bought recently, buy often, spend the most. Reward them, ask for reviews, and enroll in loyalty programs.',
    Loyal:                'Frequent buyers with good spend. Upsell higher tiers and invite to referral programs.',
    'Potential Loyalist': 'Recent customers who could become loyal with the right nudge. Send a second-purchase incentive.',
    'At Risk':            'Once-good customers who haven\'t returned. A win-back email with a compelling offer is the playbook.',
    'About to Sleep':     'Haven\'t purchased in a while and frequency is low. Low-cost re-engagement attempt before they churn.',
    'Needs Attention':    'Mid-tier customers who need re-activation. Targeted promotions can move them up.',
    New:                  'First-time buyers. First impression matters — nail the post-purchase experience.',
    'One-time':           'Purchased once, never returned. Understanding why they left is the first step.',
};

// ─── Cohort heatmap colour helpers ────────────────────────────────────────────

function heatmapBgStyle(
    value: number | null,
    rowMax: number,
    globalMax: number,
    metric: MetricType,
    isFuture: boolean,
    isIncomplete: boolean,
): React.CSSProperties {
    if (isFuture || value === null) return {};
    const ref     = (metric === 'customers' || metric === 'ltv') ? rowMax : globalMax;
    const ratio   = ref > 0 ? Math.min(value / ref, 1) : 0;
    const opacity = isIncomplete ? 0.55 : 1;
    if (ratio >= 0.7)  return { backgroundColor: `rgba(22,163,74,${0.85 * opacity})`,   color: '#fff' };
    if (ratio >= 0.5)  return { backgroundColor: `rgba(22,163,74,${0.6 * opacity})`,    color: '#fff' };
    if (ratio >= 0.3)  return { backgroundColor: `rgba(132,204,22,${0.55 * opacity})`,  color: '#365314' };
    if (ratio >= 0.15) return { backgroundColor: `rgba(234,179,8,${0.45 * opacity})`,   color: '#713f12' };
    return { backgroundColor: `rgba(249,115,22,${0.35 * opacity})`, color: '#7c2d12' };
}

function rawCellValue(cell: CohortMonth, metric: MetricType, cohortSize?: number): number | null {
    if (metric === 'revenue')   return cell.revenue;
    if (metric === 'ltv') {
        if (cell.revenue === null || !cohortSize || cohortSize <= 0) return null;
        return cell.revenue / cohortSize;
    }
    if (metric === 'orders')    return cell.orders;
    return cell.customers;
}

function formatCellValue(cell: CohortMonth, metric: MetricType, currency: string, cohortSize: number): string {
    if (metric === 'revenue') return cell.revenue !== null ? formatCurrency(cell.revenue, currency, true) : '—';
    if (metric === 'ltv') {
        if (cell.revenue === null || cohortSize <= 0) return '—';
        return formatCurrency(cell.revenue / cohortSize, currency, true);
    }
    if (metric === 'orders') return cell.orders !== null ? formatNumber(cell.orders) : '—';
    if (cell.customers === null) return '—';
    if (cohortSize > 0) return `${((cell.customers / cohortSize) * 100).toFixed(0)}%`;
    return `${cell.customers}`;
}

// ─── CohortHeatmap (standalone) ───────────────────────────────────────────────

function CohortHeatmap({ rows, maxOffset, metric, currency, lowConfidenceThreshold }: {
    rows: HeatmapRow[];
    maxOffset: number;
    metric: MetricType;
    currency: string;
    lowConfidenceThreshold: number;
}) {
    const globalMax = useMemo(() => {
        let max = 0;
        for (const row of rows) {
            for (const cell of row.cells) {
                if (!cell.is_future) {
                    const v = rawCellValue(cell, metric, row.size);
                    if (v !== null && v > max) max = v;
                }
            }
        }
        return max;
    }, [rows, metric]);

    const offsets = Array.from({ length: maxOffset + 1 }, (_, i) => i);

    if (rows.length === 0) {
        return <EmptyState title="No cohort data yet" description="Requires at least 2 months of orders." />;
    }

    return (
        <div className="relative overflow-x-auto">
            <table className="border-separate border-spacing-0 text-sm">
                <thead>
                    <tr>
                        <th scope="col" className="py-2 pr-3 text-left text-sm font-medium text-muted-foreground whitespace-nowrap w-20">Cohort</th>
                        <th scope="col" className="py-2 pr-3 text-right text-sm font-medium text-muted-foreground whitespace-nowrap w-14">Size</th>
                        {offsets.map((o) => (
                            <th key={o} scope="col" className="py-2 text-center text-sm font-medium text-muted-foreground whitespace-nowrap w-[72px]">
                                {o === 0 ? 'M0 (acq)' : `M${o}`}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-border">
                    {rows.map((row) => {
                        const m0 = row.cells.find((c) => c.offset === 0);
                        const rowMax = m0 ? (rawCellValue(m0, metric, row.size) ?? 0) : 0;
                        return (
                            <tr key={row.acquisition_month}>
                                <td className="py-1 pr-3 text-sm font-medium text-foreground whitespace-nowrap">{row.label}</td>
                                <td className="py-1 pr-3 text-right text-sm tabular-nums text-muted-foreground">
                                    {row.low_confidence
                                        ? <span className="text-amber-600 font-medium" title={`Low confidence (<${lowConfidenceThreshold} customers)`}>{formatNumber(row.size)}</span>
                                        : formatNumber(row.size)
                                    }
                                </td>
                                {row.cells.map((cell) => {
                                    const value    = rawCellValue(cell, metric, row.size);
                                    const isEmpty  = cell.is_future || value === null;
                                    const bgStyle  = heatmapBgStyle(value, rowMax, globalMax, metric, cell.is_future, cell.is_incomplete);
                                    const formatted = formatCellValue(cell, metric, currency, row.size);
                                    return (
                                        <td key={cell.offset} className="py-1 px-0.5">
                                            <span
                                                className={cn(
                                                    'flex min-h-[44px] w-[68px] items-center justify-center rounded text-sm font-medium tabular-nums transition-colors',
                                                    isEmpty && 'bg-muted/50 text-muted-foreground',
                                                    cell.is_incomplete && !isEmpty && 'outline-2 outline-offset-[-2px] outline-dashed outline-muted-foreground/30',
                                                    row.low_confidence && !isEmpty && 'opacity-60',
                                                )}
                                                style={isEmpty ? {} : bgStyle}
                                                aria-label={formatted}
                                            >
                                                {cell.is_future ? '—' : formatted}
                                            </span>
                                        </td>
                                    );
                                })}
                            </tr>
                        );
                    })}
                </tbody>
            </table>
            {/* Color legend */}
            <div className="mt-4 flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                <span className="font-medium">Scale:</span>
                {[
                    { label: 'High',    bg: 'rgba(22,163,74,0.85)' },
                    { label: 'Good',    bg: 'rgba(22,163,74,0.6)'  },
                    { label: 'Medium',  bg: 'rgba(132,204,22,0.55)'},
                    { label: 'Low',     bg: 'rgba(234,179,8,0.45)' },
                    { label: 'Minimal', bg: 'rgba(249,115,22,0.35)'},
                ].map(({ label, bg }) => (
                    <span key={label} className="flex items-center gap-1.5">
                        <span className="inline-block h-4 w-4 rounded" style={{ backgroundColor: bg }} aria-hidden="true" />
                        {label}
                    </span>
                ))}
            </div>
            <p className="mt-2 text-sm text-muted-foreground">
                M0 = acquisition month. Each column = months since first purchase.
                {metric === 'customers' && ' Values are retention % (returning ÷ cohort size). Scale is row-relative.'}
            </p>
        </div>
    );
}

// ─── Period selector ──────────────────────────────────────────────────────────

function PeriodSelector({ value, onChange }: { value: number; onChange: (p: number) => void }) {
    const options = [{ label: '6 months', value: 6 }, { label: '12 months', value: 12 }, { label: '24 months', value: 24 }];
    return (
        <div className="flex items-center gap-1.5">
            <span className="text-sm text-muted-foreground">History:</span>
            {options.map((opt) => (
                <button
                    key={opt.value}
                    type="button"
                    onClick={() => onChange(opt.value)}
                    className={cn(
                        'min-h-[36px] rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                        value === opt.value ? 'bg-foreground text-background' : 'bg-muted text-foreground/70 hover:text-foreground',
                    )}
                >
                    {opt.label}
                </button>
            ))}
        </div>
    );
}

// ─── Cohorts tab ──────────────────────────────────────────────────────────────

function CohortsTab({
    heatmapRows,
    curveSeries,
    pacing,
    maxOffset,
    summary,
    availableChannels,
    lowConfidenceThreshold,
    filters,
    currency,
    workspaceSlug,
    onNavigate,
}: {
    heatmapRows: HeatmapRow[];
    curveSeries: CurveSeries[];
    pacing: PacingData;
    maxOffset: number;
    summary: CohortSummary;
    availableChannels: AvailableChannel[];
    lowConfidenceThreshold: number;
    filters: { cohort_period: number; cohort_metric: MetricType; cohort_view: ViewType; cohort_channel: string };
    currency: string;
    workspaceSlug: string | undefined;
    onNavigate: (overrides: Partial<typeof filters>) => void;
}) {
    const metricLabel = { revenue: 'Revenue', orders: 'Orders', customers: 'Retention %', ltv: 'LTV / Customer' }[filters.cohort_metric];

    // Use mock heatmap if no real data
    const effectiveRows    = heatmapRows.length > 0 ? heatmapRows : MOCK_HEATMAP;
    const effectiveMaxOff  = maxOffset > 0 ? maxOffset : 11;

    return (
        <div className="space-y-6">
            <p className="text-sm text-muted-foreground">
                Monthly acquisition cohorts — repeat-purchase behaviour by month since first order.
                Heatmap = at-a-glance retention; Curves = per-cohort trajectory; Pacing = newest cohort vs historical baseline.
            </p>

            {/* Summary KPIs */}
            <KpiGrid cols={3}>
                <MetricCard label="Cohorts analysed" value={String(summary.cohort_count || effectiveRows.length)} tooltip="Monthly acquisition cohorts in the selected period." />
                <MetricCard label="Avg cohort size" value={formatNumber(summary.avg_cohort_size || Math.round(effectiveRows.reduce((s, r) => s + r.size, 0) / Math.max(effectiveRows.length, 1)))} tooltip="Average new customers per acquisition month." />
                {summary.best_m1_cohort && (
                    <MetricCard label="Best M1 retention" value={summary.best_m1_cohort} subtext={`${summary.best_m1_rate_pct}% returned in month 1`} tooltip="Acquisition month with the highest month-1 return rate." />
                )}
            </KpiGrid>

            {/* Cohort card */}
            <div className="rounded-lg border border-border bg-card overflow-hidden">
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <ViewToggle<ViewType>
                            options={[{ label: 'Heatmap', value: 'heatmap' }, { label: 'Curves', value: 'curves' }, { label: 'Pacing', value: 'pacing' }]}
                            value={filters.cohort_view}
                            onChange={(v) => onNavigate({ cohort_view: v })}
                        />
                        <ViewToggle<MetricType>
                            options={[
                                { label: 'LTV / Customer', value: 'ltv'       },
                                { label: 'Retention %',    value: 'customers' },
                                { label: 'Revenue',        value: 'revenue'   },
                                { label: 'Orders',         value: 'orders'    },
                            ]}
                            value={filters.cohort_metric}
                            onChange={(m) => onNavigate({ cohort_metric: m })}
                        />
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        {availableChannels.length > 0 && (
                            <div className="flex items-center gap-1.5">
                                <span className="text-sm text-muted-foreground">Channel:</span>
                                <select
                                    value={filters.cohort_channel}
                                    onChange={(e) => onNavigate({ cohort_channel: e.target.value })}
                                    className="min-h-[36px] rounded-md border border-border bg-card px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-teal-500"
                                >
                                    <option value="all">All Channels</option>
                                    {availableChannels.map((ch) => (
                                        <option key={ch.value} value={ch.value}>{ch.label}{ch.low_data ? ' (low data)' : ''}</option>
                                    ))}
                                </select>
                            </div>
                        )}
                        <PeriodSelector value={filters.cohort_period} onChange={(p) => onNavigate({ cohort_period: p })} />
                        <ExportMenu
                            onExportCsv={() => {
                                const params = new URLSearchParams({
                                    tab: 'cohorts', cohort_period: String(filters.cohort_period),
                                    cohort_metric: filters.cohort_metric === 'ltv' ? 'revenue' : filters.cohort_metric,
                                    cohort_view: filters.cohort_view, cohort_channel: filters.cohort_channel, export: 'csv',
                                });
                                window.location.href = `${window.location.pathname}?${params}`;
                            }}
                        />
                    </div>
                </div>
                <div className="p-5">
                    <div className="mb-4">
                        <h2 className="text-sm font-semibold text-foreground">
                            {filters.cohort_view === 'heatmap' && `Cohort Heatmap — ${metricLabel}`}
                            {filters.cohort_view === 'curves'  && `Retention Curves — Cumulative ${metricLabel}`}
                            {filters.cohort_view === 'pacing'  && `Pacing — ${metricLabel} vs. Baseline`}
                        </h2>
                    </div>
                    {filters.cohort_view === 'heatmap' && (
                        <CohortHeatmap
                            rows={effectiveRows}
                            maxOffset={effectiveMaxOff}
                            metric={filters.cohort_metric}
                            currency={currency}
                            lowConfidenceThreshold={lowConfidenceThreshold}
                        />
                    )}
                    {(filters.cohort_view === 'curves' || filters.cohort_view === 'pacing') && (
                        <EmptyState
                            title="View unavailable"
                            description="This cohort view is being rebuilt."
                        />
                    )}
                </div>
            </div>
        </div>
    );
}

// ─── LTV tab ──────────────────────────────────────────────────────────────────

const LTV_COLORS = ['#4f46e5', '#16a34a', '#f59e0b', '#dc2626', '#0891b2', '#9333ea'];

function LtvCurveChart({ curves, currency }: { curves: LtvCurve[]; currency: string }) {
    const chartData = useMemo(() => {
        const allMonths = [...new Set(curves.flatMap((c) => c.data.map((d) => d.month)))].sort((a, b) => a - b);
        return allMonths.map((m) => {
            const point: Record<string, number> = { month: m };
            curves.forEach((c) => {
                const found = c.data.find((d) => d.month === m);
                if (found) point[c.channel] = found.ltv;
            });
            return point;
        });
    }, [curves]);

    if (curves.length === 0) {
        return (
            <div className="flex h-40 items-center justify-center text-sm text-muted-foreground/70">
                No LTV curve data — connect ad platforms to see acquisition cost trends by channel.
            </div>
        );
    }

    return (
        <ResponsiveContainer width="100%" height={280}>
            <LineChart data={chartData} margin={{ top: 8, right: 16, left: 8, bottom: 24 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="var(--border)" />
                <XAxis
                    dataKey="month"
                    tickFormatter={(v: number) => String(v)}
                    label={{ value: 'Months since first purchase', position: 'insideBottom', offset: -5, fontSize: 12, fill: 'var(--muted-foreground)' }}
                    tick={{ fontSize: 12, fill: 'var(--muted-foreground)' }}
                />
                <YAxis
                    tickFormatter={(v) => formatCurrency(v, currency, true)}
                    tick={{ fontSize: 12, fill: 'var(--muted-foreground)' }}
                    width={56}
                />
                <Tooltip
                    formatter={(value) => [formatCurrency(Number(value), currency, true), '']}
                    labelFormatter={(label) => `Month ${label}`}
                />
                <Legend verticalAlign="bottom" wrapperStyle={{ paddingTop: 8, fontSize: 12 }} />
                {curves.map((curve, ci) => (
                    <Line
                        key={curve.channel}
                        type="monotone"
                        dataKey={curve.channel}
                        stroke={LTV_COLORS[ci % LTV_COLORS.length]}
                        strokeWidth={2}
                        dot={false}
                        activeDot={{ r: 4 }}
                    />
                ))}
            </LineChart>
        </ResponsiveContainer>
    );
}

function LtvDriversTable({ drivers, currency, workspaceSlug }: {
    drivers: LtvDriver[];
    currency: string;
    workspaceSlug: string | undefined;
}) {
    if (drivers.length === 0) {
        return (
            <div className="flex h-40 items-center justify-center text-sm text-muted-foreground/70">
                No product LTV data yet.
            </div>
        );
    }
    const maxLtv = Math.max(...drivers.map((d) => d.avg_ltv ?? 0), 1);

    const columns: Column<LtvDriver>[] = [
        {
            key: 'product_name',
            header: 'Product',
            sortable: true,
            render: (value) => <span className="font-medium text-foreground text-sm line-clamp-1">{String(value)}</span>,
        },
        {
            key: 'customers',
            header: 'Customers',
            sortable: true,
            render: (value) => <span className="tabular-nums text-sm text-muted-foreground">{formatNumber(Number(value))}</span>,
        },
        {
            key: 'avg_ltv',
            header: 'Avg LTV',
            sortable: true,
            render: (value, row) => {
                const pct = maxLtv > 0 ? ((row.avg_ltv ?? 0) / maxLtv) * 100 : 0;
                return (
                    <div className="relative flex items-center gap-2">
                        <div className="absolute left-0 top-1/2 -translate-y-1/2 h-4 rounded-sm bg-indigo-50" style={{ width: `${pct}%` }} />
                        <span className="relative z-10 tabular-nums text-sm font-medium text-foreground">
                            {value != null ? formatCurrency(Number(value), currency, true) : '—'}
                        </span>
                    </div>
                );
            },
        },
        {
            key: 'avg_aov',
            header: 'AOV',
            sortable: true,
            render: (value) => <span className="tabular-nums text-sm text-muted-foreground">{value != null ? formatCurrency(Number(value), currency, true) : '—'}</span>,
        },
        {
            key: 'ltv_cac',
            header: 'LTV:CAC',
            sortable: true,
            render: (value) => {
                const v = Number(value);
                if (value == null || isNaN(v)) return <span className="text-sm text-muted-foreground">N/A</span>;
                return (
                    <span className={cn('text-sm font-medium tabular-nums', v >= 3 ? 'text-emerald-600' : v >= 1 ? 'text-amber-600' : 'text-rose-600')}>
                        {v.toFixed(1)}×
                    </span>
                );
            },
        },
        {
            key: 'repeat_rate',
            header: 'Repeat Rate',
            sortable: true,
            render: (value) => <span className="tabular-nums text-sm text-muted-foreground">{value != null ? `${(Number(value) * 100).toFixed(0)}%` : '—'}</span>,
        },
    ];

    return (
        <DataTable
            data={drivers}
            columns={columns}
            onRowClick={(row) => workspaceSlug && router.visit(wurl(workspaceSlug, '/products'), { data: { search: row.product_name } })}
        />
    );
}

function LtvTab({
    ltv_curves,
    channel_ltv,
    ltv_drivers,
    channel_cac_trend,
    layercake,
    currency,
    workspaceSlug,
    onSourceChange,
}: {
    ltv_curves: LtvCurve[];
    channel_ltv: ChannelLtv[];
    ltv_drivers: LtvDriver[];
    channel_cac_trend: ChannelCacPoint[];
    layercake: LayerCakePoint[];
    currency: string;
    workspaceSlug: string | undefined;
    onSourceChange: (src: MetricSource) => void;
}) {
    // Convert layercake to chart format
    const { cohorts, data: lcData } = useMemo(() => toLayerCakeProps(layercake.length > 0 ? layercake : MOCK_LAYERCAKE), [layercake]);

    const currSym = currency === 'EUR' ? '€' : '$';

    return (
        <div className="space-y-8">
            <p className="text-sm text-muted-foreground">
                Lifetime Value analysis — LayerCake shows whether growth is driven by new acquisition or repeat orders.
                Steeper LTV curves by channel tell you where to put your next dollar of ad spend.
            </p>

            {/* LayerCakeChart — Daasity pattern */}
            <div className="rounded-lg border border-border bg-card overflow-hidden">
                <div className="border-b border-border px-5 py-3">
                    <h3 className="text-sm font-semibold text-foreground">Revenue by Customer Ordinal (90d)</h3>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        Stacked area: each layer = revenue from customers in that order-number bucket.
                        Growing 4th+ layer = strong repeat business. Dominant 1st-order layer = acquisition-dependent.
                    </p>
                </div>
                <div className="p-5">
                    <LayerCakeChart
                        cohorts={cohorts}
                        data={lcData}
                        yFormatter={(v) => `${currSym}${(v / 1000).toFixed(1)}k`}
                        height={260}
                    />
                </div>
            </div>

            {/* LTV curves by channel */}
            <div className="rounded-lg border border-border bg-card overflow-hidden">
                <div className="border-b border-border px-5 py-3">
                    <h3 className="text-sm font-semibold text-foreground">LTV Curves by Channel</h3>
                    <p className="mt-0.5 text-sm text-muted-foreground">Cumulative LTV per customer over months since first purchase, by acquisition channel.</p>
                </div>
                <div className="p-5">
                    <LtvCurveChart curves={ltv_curves} currency={currency} />
                </div>
            </div>

            {/* Channel LTV table */}
            {channel_ltv.length > 0 && (
                <div className="rounded-lg border border-border bg-card overflow-hidden">
                    <div className="border-b border-border px-5 py-3">
                        <h3 className="text-sm font-semibold text-foreground">Channel LTV Table</h3>
                        <p className="mt-0.5 text-sm text-muted-foreground">Click a channel to filter this page to customers acquired from that source.</p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-border text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    {['Channel', 'LTV 30d', 'LTV 90d', 'LTV 365d', 'Payback Days'].map((h) => (
                                        <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border bg-card">
                                {[...channel_ltv].sort((a, b) => (b.ltv_365d ?? 0) - (a.ltv_365d ?? 0)).map((row) => {
                                    const slug = row.channel.toLowerCase();
                                    const clickable = ['facebook', 'google', 'gsc', 'ga4'].includes(slug);
                                    return (
                                        <tr
                                            key={row.channel}
                                            className={cn('hover:bg-muted/40 transition-colors', clickable && 'cursor-pointer')}
                                            onClick={clickable ? () => onSourceChange(slug as MetricSource) : undefined}
                                        >
                                            <td className="px-4 py-2.5 font-medium text-foreground">{row.channel}</td>
                                            <td className="px-4 py-2.5 tabular-nums text-muted-foreground">{row.ltv_30d != null ? formatCurrency(row.ltv_30d, currency, true) : '—'}</td>
                                            <td className="px-4 py-2.5 tabular-nums text-muted-foreground">{row.ltv_90d != null ? formatCurrency(row.ltv_90d, currency, true) : '—'}</td>
                                            <td className="px-4 py-2.5 tabular-nums font-medium text-foreground">{row.ltv_365d != null ? formatCurrency(row.ltv_365d, currency, true) : '—'}</td>
                                            <td className="px-4 py-2.5 tabular-nums text-muted-foreground">{row.payback_days != null ? `${row.payback_days}d` : '—'}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* LTV Drivers — Lifetimely pattern */}
            <div className="rounded-lg border border-border bg-card overflow-hidden">
                <div className="border-b border-border px-5 py-3">
                    <h3 className="text-sm font-semibold text-foreground">LTV Drivers</h3>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                        Products whose first-time buyers generate the highest LTV. LTV:CAC ≥ 3× is healthy.
                    </p>
                </div>
                <div className="p-5">
                    <LtvDriversTable drivers={ltv_drivers} currency={currency} workspaceSlug={workspaceSlug} />
                </div>
            </div>
        </div>
    );
}

// ─── Audiences tab ────────────────────────────────────────────────────────────

const SYSTEM_AUDIENCES = [
    { name: 'Champions',            slug: 'champions',            description: 'Highest RFM composite' },
    { name: 'Loyal',                slug: 'loyal',                description: 'Frequent + recent buyers' },
    { name: 'Potential Loyalists',  slug: 'potential_loyalist',   description: 'Early-signal repeat buyers' },
    { name: 'At Risk',              slug: 'at_risk',              description: 'Lapsing high-value customers' },
    { name: 'About to Sleep',       slug: 'about_to_sleep',       description: 'Low recency + frequency' },
    { name: 'Needs Attention',      slug: 'needs_attention',      description: 'Mid-tier re-engagement candidates' },
];

function AudiencesTab({
    audienceTraits,
    customers,
    currency,
}: {
    audienceTraits: AudienceTraitsData;
    customers: CustomerRow[];
    currency: string;
}) {
    const [selectedAudience, setSelectedAudience] = useState<string>('champions');

    // Compute traits per selected audience from mock customers
    const segCustomers = useMemo(() => {
        return customers.filter((c) =>
            c.rfm_segment.toLowerCase().replace(/\s+/g, '_') === selectedAudience
        );
    }, [customers, selectedAudience]);

    const segCount     = segCustomers.length;
    const segAvgLtv    = segCount > 0 ? segCustomers.reduce((s, c) => s + c.total_revenue, 0) / segCount : 0;
    const segAvgOrders = segCount > 0 ? segCustomers.reduce((s, c) => s + c.order_count, 0) / segCount : 0;

    // Compute live traits from mock customers
    const liveTraits = useMemo<AudienceTraitsData>(() => {
        if (segCount === 0) return audienceTraits;
        // Top channels
        const chanCounts: Record<string, number> = {};
        segCustomers.forEach((c) => { chanCounts[c.primary_source] = (chanCounts[c.primary_source] ?? 0) + 1; });
        const topChannels: AudienceTrait[] = Object.entries(chanCounts).sort((a,b) => b[1]-a[1]).slice(0,4).map(([ch, cnt]) => ({
            label: ucFirst(ch), value: `${formatNumber(cnt)} customers`, share: cnt / segCount, color: SOURCE_COLORS[ch],
        }));

        // Top countries
        const ctCounts: Record<string, number> = {};
        segCustomers.forEach((c) => { ctCounts[c.country] = (ctCounts[c.country] ?? 0) + 1; });
        const topCountries: AudienceTrait[] = Object.entries(ctCounts).sort((a,b) => b[1]-a[1]).slice(0,4).map(([ct, cnt]) => ({
            label: ct, value: `${((cnt / segCount) * 100).toFixed(0)}%`, share: cnt / segCount,
        }));

        // Top categories as proxy for top products
        const catCounts: Record<string, number> = {};
        segCustomers.forEach((c) => { catCounts[c.first_product_category] = (catCounts[c.first_product_category] ?? 0) + 1; });
        const topProducts: AudienceTrait[] = Object.entries(catCounts).sort((a,b) => b[1]-a[1]).slice(0,4).map(([cat, cnt]) => ({
            label: cat, value: `${((cnt / segCount) * 100).toFixed(0)}% of segment`, share: cnt / segCount,
        }));

        // Device (mock — not tracked in CustomerRow)
        const topDevices = audienceTraits.top_devices.length > 0 ? audienceTraits.top_devices : [
            { label: 'Mobile',  value: '58%', share: 0.58 },
            { label: 'Desktop', value: '36%', share: 0.36 },
            { label: 'Tablet',  value: '6%',  share: 0.06 },
        ];

        return { top_products: topProducts, top_channels: topChannels, top_countries: topCountries, top_devices: topDevices };
    }, [segCustomers, segCount, audienceTraits]);

    const selectedDef = SYSTEM_AUDIENCES.find((a) => a.slug === selectedAudience);

    return (
        <div className="flex gap-6">
            {/* Left pane — audience list */}
            <div className="w-64 shrink-0 space-y-1">
                <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">System Segments</h3>
                {SYSTEM_AUDIENCES.map((aud) => {
                    const cnt = customers.filter((c) => c.rfm_segment.toLowerCase().replace(/\s+/g, '_') === aud.slug).length;
                    return (
                        <button
                            key={aud.slug}
                            type="button"
                            onClick={() => setSelectedAudience(aud.slug)}
                            className={cn(
                                'w-full rounded-lg px-3 py-2.5 text-left transition-colors',
                                selectedAudience === aud.slug
                                    ? 'bg-teal-50 border border-teal-200'
                                    : 'border border-transparent hover:bg-muted/50',
                            )}
                        >
                            <div className="flex items-center justify-between gap-2">
                                <span className={cn('text-sm font-medium', selectedAudience === aud.slug ? 'text-teal-800' : 'text-foreground')}>
                                    {aud.name}
                                </span>
                                <span className="tabular-nums text-xs text-muted-foreground">{formatNumber(cnt)}</span>
                            </div>
                            <p className="mt-0.5 text-xs text-muted-foreground">{aud.description}</p>
                        </button>
                    );
                })}
            </div>

            {/* Right pane — segment detail */}
            <div className="flex-1 min-w-0 space-y-6">
                {/* KPI mini-strip */}
                <div className="grid grid-cols-3 gap-3">
                    <div className="rounded-lg border border-border bg-card p-4">
                        <p className="text-xs text-muted-foreground">Customers</p>
                        <p className="mt-0.5 text-xl font-bold tabular-nums text-foreground">{formatNumber(segCount)}</p>
                    </div>
                    <div className="rounded-lg border border-border bg-card p-4">
                        <p className="text-xs text-muted-foreground">Avg LTV</p>
                        <p className="mt-0.5 text-xl font-bold tabular-nums text-foreground">{formatCurrency(segAvgLtv, currency, true)}</p>
                    </div>
                    <div className="rounded-lg border border-border bg-card p-4">
                        <p className="text-xs text-muted-foreground">Avg Orders</p>
                        <p className="mt-0.5 text-xl font-bold tabular-nums text-foreground">{segAvgOrders.toFixed(1)}</p>
                    </div>
                </div>

                {/* AudienceTraits grid (Peel pattern) */}
                <div className="grid grid-cols-2 gap-4">
                    <div className="rounded-lg border border-border bg-card p-4">
                        <AudienceTraits traits={liveTraits.top_channels} title="Top Acquisition Channels" />
                    </div>
                    <div className="rounded-lg border border-border bg-card p-4">
                        <AudienceTraits traits={liveTraits.top_countries} title="Top Countries" />
                    </div>
                    <div className="rounded-lg border border-border bg-card p-4">
                        <AudienceTraits traits={liveTraits.top_products} title="First Product Category" />
                    </div>
                    <div className="rounded-lg border border-border bg-card p-4">
                        <AudienceTraits traits={liveTraits.top_devices} title="Device Breakdown" />
                    </div>
                </div>

                {/* Stubbed "Convert to Audience" button — v2 activation */}
                <div className="flex items-center gap-3">
                    <button
                        type="button"
                        disabled
                        className="inline-flex items-center gap-2 rounded-md border border-border bg-muted px-4 py-2 text-sm font-medium text-muted-foreground cursor-not-allowed"
                        title="Segment activation (push to Klaviyo / Meta) is coming in v2"
                    >
                        Send to Klaviyo
                        <span className="rounded-full bg-muted-foreground/20 px-1.5 py-0.5 text-xs">v2</span>
                    </button>
                    <button
                        type="button"
                        disabled
                        className="inline-flex items-center gap-2 rounded-md border border-border bg-muted px-4 py-2 text-sm font-medium text-muted-foreground cursor-not-allowed"
                        title="Meta Custom Audiences integration is coming in v2"
                    >
                        Push to Meta
                        <span className="rounded-full bg-muted-foreground/20 px-1.5 py-0.5 text-xs">v2</span>
                    </button>
                </div>
            </div>
        </div>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function CustomersIndex({
    kpis,
    metrics,
    customers: propCustomers,
    rfm_segments,
    rfm_cells,
    rfm_grid,
    segment_traits,
    segment_drilldown,
    heatmap_rows,
    curve_series,
    pacing,
    max_offset,
    cohort_summary,
    available_channels,
    low_confidence_threshold,
    ltv_curves,
    channel_ltv,
    ltv_drivers,
    cac,
    ltv_cac,
    channel_cac_trend,
    median_days_to_second_order,
    layercake,
    audience_traits,
    active_tab: propActiveTab,
    ltv_calibrating,
    ltv_calibration_day,
    filters,
}: Props) {
    const { workspace, metricSources } = usePage<PageProps>().props;
    const currency = workspace?.reporting_currency ?? 'EUR';

    // Tab state — read from browser URL (which may have 'all' | 'cohorts' | 'audiences')
    // because the server only knows about 'segments' | 'retention' | 'ltv'.
    const urlTab = typeof window !== 'undefined'
        ? (new URLSearchParams(window.location.search).get('tab') as Tab | null)
        : null;
    // Map server-returned tab to frontend tab when cohorts/all/audiences not in URL
    const serverToUiTab: Record<string, Tab> = { retention: 'cohorts', segments: 'segments', ltv: 'ltv' };
    const activeTab: Tab = urlTab ?? (filters?.tab ? serverToUiTab[filters.tab] ?? 'segments' : propActiveTab ?? 'all');

    // Merge source from filters
    const activeSource = (filters?.source ?? 'real') as MetricSource;

    // Customers — prefer server data, fall back to mock
    const customers = (propCustomers && propCustomers.length > 0) ? propCustomers : MOCK_CUSTOMERS;
    // RFM grid — prefer server data, fall back to mock
    const rfmGrid   = rfm_grid && rfm_grid.length > 0 ? rfm_grid : MOCK_RFM_GRID;

    // Normalise kpis — build from legacy `metrics` if new kpis prop absent
    const resolvedKpis: Kpis = kpis ?? {
        total_customers:      metrics?.total ?? MOCK_CUSTOMERS.length,
        new_customers_period: metrics?.new_30d ?? 47,
        returning_pct:        metrics?.repeat_rate ?? 58.3,
        avg_ltv:              metrics?.ltv_90d ?? 284,
        ltv_cac:              ltv_cac ?? 3.2,
        repeat_order_rate:    metrics?.repeat_rate ?? 41.7,
        avg_time_to_2nd_order: median_days_to_second_order ?? 38,
        churn_rate:           6.4,
        ltv_30d:              metrics?.ltv_30d ?? 94,
        ltv_90d:              metrics?.ltv_90d ?? 284,
        ltv_365d:             metrics?.ltv_365d ?? 860,
        cac:                  cac ?? 88,
    };

    // Audience traits — fallback mock
    const resolvedTraits: AudienceTraitsData = audience_traits ?? {
        top_products:  [
            { label: 'Apparel',      value: '38% of segment', share: 0.38 },
            { label: 'Footwear',     value: '24% of segment', share: 0.24 },
            { label: 'Accessories',  value: '20% of segment', share: 0.20 },
        ],
        top_channels:  [
            { label: 'Facebook',    value: '44% of segment', share: 0.44, color: '#6366f1' },
            { label: 'Google',      value: '28% of segment', share: 0.28, color: '#f59e0b' },
            { label: 'Store',       value: '18% of segment', share: 0.18, color: '#64748b' },
        ],
        top_countries: [
            { label: 'US',  value: '51%', share: 0.51 },
            { label: 'GB',  value: '18%', share: 0.18 },
            { label: 'CA',  value: '12%', share: 0.12 },
            { label: 'AU',  value: '9%',  share: 0.09 },
        ],
        top_devices: [
            { label: 'Mobile',  value: '62%', share: 0.62 },
            { label: 'Desktop', value: '32%', share: 0.32 },
            { label: 'Tablet',  value: '6%',  share: 0.06 },
        ],
    };

    const activeSegmentSlug = (filters?.segment ?? null) as string | null;

    function handleSourceChange(src: MetricSource) {
        if (src === activeSource) return;
        router.visit(window.location.href, {
            method: 'get',
            data: { tab: activeTab, source: src },
            preserveState: true,
            preserveScroll: true,
        });
    }

    function changeTab(tab: Tab) {
        // Controller only understands 'segments' | 'retention' | 'ltv'.
        // We pass the UI tab directly in the URL; the server maps unknown tabs to 'segments'.
        // The frontend reads the tab from window.location.search (urlTab) so the right
        // panel renders client-side regardless of what the server returned.
        router.get(
            wurl(workspace?.slug, '/customers'),
            { tab, source: activeSource },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function selectSegment(slug: string) {
        router.get(
            wurl(workspace?.slug, '/customers'),
            { tab: 'segments', source: activeSource, segment: slug, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true, only: ['segment_drilldown', 'filters'] },
        );
    }

    function deselectSegment() {
        router.get(
            wurl(workspace?.slug, '/customers'),
            { tab: 'segments', source: activeSource },
            { preserveState: true, preserveScroll: true, replace: true, only: ['segment_drilldown', 'filters'] },
        );
    }

    function changeSegmentPage(page: number) {
        if (!activeSegmentSlug) return;
        router.get(
            wurl(workspace?.slug, '/customers'),
            { tab: 'segments', source: activeSource, segment: activeSegmentSlug, page },
            { preserveState: true, preserveScroll: true, replace: true, only: ['segment_drilldown', 'filters'] },
        );
    }

    function navigateCohort(overrides: Partial<{
        cohort_period: number;
        cohort_metric: MetricType;
        cohort_view: ViewType;
        cohort_channel: string;
    }>) {
        const current = {
            cohort_period:  filters?.cohort_period  ?? 12,
            cohort_metric:  filters?.cohort_metric  ?? 'customers',
            cohort_view:    filters?.cohort_view    ?? 'heatmap',
            cohort_channel: filters?.cohort_channel ?? 'all',
        };
        // Controller accepts 'retention' — map our UX tab slug 'cohorts' to it.
        router.visit(wurl(workspace?.slug, '/customers'), {
            method: 'get',
            data: { tab: 'retention', source: activeSource, ...current, ...overrides },
            preserveState: true,
            preserveScroll: true,
        });
    }

    return (
        <AppLayout>
            <Head title="Customers" />

            <div className="space-y-6">

                {/* Calibration banner */}
                {ltv_calibrating && (
                    <AlertBanner
                        severity="info"
                        message={`LTV model is calibrating — day ${ltv_calibration_day ?? 0} of 90. Predicted LTV figures will stabilise as more repeat-purchase data accrues.`}
                    />
                )}

                {/* Page header */}
                <PageHeader
                    title="Customers"
                    subtitle="RFM segments, cohort retention, lifetime value, and audience traits"
                    action={
                        <SourceToggle
                            value={activeSource}
                            onChange={handleSourceChange}
                            availableSources={availableSources(metricSources)}
                        />
                    }
                />

                {/* KPI strip — MetricCardDetail per spec; 4 per row at lg+, 2 per row on sm */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <MetricCardDetail
                        label="Customers (period)"
                        value={formatNumber(resolvedKpis.total_customers)}
                        sources={['store', 'real']}
                        activeSource="store"
                        delta={4.2}
                        sparklineData={[420, 435, 442, 458, 461, 470, 483, 491]}
                    />
                    <MetricCardDetail
                        label="New Customers"
                        value={formatNumber(resolvedKpis.new_customers_period)}
                        sources={['store', 'facebook', 'google', 'real']}
                        activeSource={activeSource}
                        delta={7.1}
                        sparklineData={[38, 41, 35, 44, 47, 43, 51, 47]}
                    />
                    <MetricCardDetail
                        label="Returning %"
                        value={resolvedKpis.returning_pct != null ? `${resolvedKpis.returning_pct.toFixed(1)}%` : '—'}
                        sources={['store']}
                        activeSource="store"
                        delta={1.4}
                        sparklineData={[54, 55, 56, 57, 57, 58, 59, 58]}
                    />
                    <MetricCardDetail
                        label="Avg LTV (90d)"
                        value={resolvedKpis.avg_ltv != null ? formatCurrency(resolvedKpis.avg_ltv, currency, true) : '—'}
                        sources={['real']}
                        activeSource="real"
                        delta={3.8}
                        sparklineData={[264, 268, 271, 274, 278, 281, 283, 284]}
                    />
                    <MetricCardDetail
                        label="LTV:CAC"
                        value={resolvedKpis.ltv_cac != null ? `${resolvedKpis.ltv_cac.toFixed(1)}×` : 'N/A'}
                        sources={['real', 'facebook', 'google']}
                        activeSource="real"
                        delta={-0.4}
                        sparklineData={[3.5, 3.4, 3.3, 3.3, 3.2, 3.1, 3.2, 3.2]}
                    />
                    <MetricCardDetail
                        label="Repeat Order Rate"
                        value={resolvedKpis.repeat_order_rate != null ? `${resolvedKpis.repeat_order_rate.toFixed(1)}%` : '—'}
                        sources={['store']}
                        activeSource="store"
                        delta={2.1}
                        sparklineData={[38, 39, 40, 40, 41, 42, 42, 42]}
                    />
                    <MetricCardDetail
                        label="Avg Time to 2nd Order"
                        value={resolvedKpis.avg_time_to_2nd_order != null ? `${resolvedKpis.avg_time_to_2nd_order}d` : '—'}
                        sources={['store']}
                        activeSource="store"
                        delta={-3.2}
                        sparklineData={[42, 41, 40, 40, 39, 39, 38, 38]}
                    />
                    <MetricCardDetail
                        label="Churn Rate"
                        value={resolvedKpis.churn_rate != null ? `${resolvedKpis.churn_rate.toFixed(1)}%` : '—'}
                        sources={['real']}
                        activeSource="real"
                        delta={-0.8}
                        sparklineData={[7.2, 7.0, 6.9, 6.8, 6.7, 6.5, 6.4, 6.4]}
                    />
                </div>

                {/* Tab strip + content */}
                <div className="rounded-xl border border-border bg-card">
                    <TabBar activeTab={activeTab} onTabChange={changeTab} />

                    <div className="p-5">

                        {/* ── All Customers tab ── */}
                        {activeTab === 'all' && (
                            <AllCustomersTab customers={customers} currency={currency} />
                        )}

                        {/* ── RFM Segments tab ── */}
                        {activeTab === 'segments' && (
                            <SegmentsTab
                                rfmSegments={rfm_segments ?? []}
                                rfmCells={rfm_cells ?? []}
                                rfmGrid={rfmGrid}
                                currency={currency}
                                workspaceSlug={workspace?.slug}
                                activeSegmentSlug={activeSegmentSlug}
                                segmentDrilldown={segment_drilldown ?? null}
                                customers={customers}
                                onSelectSegment={selectSegment}
                                onDeselectSegment={deselectSegment}
                                onPageChange={changeSegmentPage}
                            />
                        )}

                        {/* ── Cohorts tab (3-view: heatmap / curves / pacing) ── */}
                        {activeTab === 'cohorts' && (
                            <CohortsTab
                                heatmapRows={heatmap_rows ?? []}
                                curveSeries={curve_series ?? []}
                                pacing={pacing ?? { current: [], average: [], current_label: null }}
                                maxOffset={max_offset ?? 11}
                                summary={cohort_summary ?? { cohort_count: 12, avg_cohort_size: 60, best_m1_cohort: 'Jan 2026', best_m1_rate_pct: 32 }}
                                availableChannels={available_channels ?? []}
                                lowConfidenceThreshold={low_confidence_threshold ?? 10}
                                filters={{
                                    cohort_period:  filters?.cohort_period  ?? 12,
                                    cohort_metric:  filters?.cohort_metric  ?? 'customers',
                                    cohort_view:    filters?.cohort_view    ?? 'heatmap',
                                    cohort_channel: filters?.cohort_channel ?? 'all',
                                }}
                                currency={currency}
                                workspaceSlug={workspace?.slug}
                                onNavigate={navigateCohort}
                            />
                        )}

                        {/* ── LTV tab ── */}
                        {activeTab === 'ltv' && (
                            <LtvTab
                                ltv_curves={ltv_curves ?? []}
                                channel_ltv={channel_ltv ?? []}
                                ltv_drivers={ltv_drivers ?? []}
                                channel_cac_trend={channel_cac_trend ?? []}
                                layercake={layercake ?? []}
                                currency={currency}
                                workspaceSlug={workspace?.slug}
                                onSourceChange={handleSourceChange}
                            />
                        )}

                        {/* ── Audiences tab ── */}
                        {activeTab === 'audiences' && (
                            <AudiencesTab
                                audienceTraits={resolvedTraits}
                                customers={customers}
                                currency={currency}
                            />
                        )}

                    </div>
                </div>

            </div>
        </AppLayout>
    );
}
