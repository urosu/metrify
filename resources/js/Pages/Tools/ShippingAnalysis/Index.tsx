/**
 * Tools/ShippingAnalysis/Index — Shipping cost by country analysis.
 *
 * Gives merchants per-country shipping economics: carrier cost vs customer-charged,
 * return rate, COD penetration, contribution margin, and a 4-knob what-if simulator
 * that recomputes contribution margin live as knobs change.
 *
 * Layout:
 *   PageHeader
 *   → 4 MetricCardCompact summary strip
 *   → WhatIfSimulator (4 knobs + global impact projection)
 *   → In-page filter bar (region, status, min orders, country search)
 *   → DataTable (12 columns, sortable)
 *   → DrawerSidePanel (per-country detail: daily orders, top products, channels,
 *       return reasons, carrier mix)
 *
 * Placement rationale: standalone tool route `/tools/shipping-analysis` rather than
 * embedding in /profit, because: (1) /profit already has a Breakdown by Country
 * BarChart; embedding a 12-column table + what-if simulator would overload it;
 * (2) the cognitive intent is "plan carrier contracts / campaign" not "review P&L";
 * (3) Shopify and Metorik both separate shipping reporting from the P&L page.
 *
 * @see docs/competitors/_research_shipping_country_analysis.md §5 Placement Decision
 * @see docs/pages/settings.md §settings/costs/shipping  — rule definition (not analysis)
 * @see docs/UX.md §5 shared primitives
 */

import { useState, useMemo, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    Package,
    TrendingDown,
    RotateCcw,
    CreditCard,
    Globe,
    ChevronDown,
    ChevronUp,
    ChevronsUpDown,
    Search,
    X,
    BarChart3,
    Truck,
    AlertTriangle,
    CheckCircle2,
    MinusCircle,
} from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { MetricCardCompact } from '@/Components/shared/MetricCardCompact';
import { DrawerSidePanel } from '@/Components/shared/DrawerSidePanel';
import { EmptyState } from '@/Components/shared/EmptyState';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ── Types ─────────────────────────────────────────────────────────────────────

interface CountryDetail {
    daily_orders: { date: string; orders: number }[];
    top_products: { name: string; orders: number; revenue: number }[];
    top_channels: { channel: string; orders: number; pct: number }[];
    return_reasons: { reason: string; pct: number }[];
    carrier_mix: { carrier: string; pct: number }[];
}

interface CountryRow {
    country_code: string;
    country_name: string;
    region: string;
    orders: number;
    revenue: number;
    avg_shipping_charged: number;
    avg_carrier_cost: number;
    returns: number;
    return_pct: number;
    cod_pct: number;
    cod_cost_per_order: number;
    avg_speed_days: number;
    contribution_margin: number;
    contribution_margin_pct: number;
    status: 'profitable' | 'marginal' | 'loss';
    detail: CountryDetail;
}

interface Summary {
    avg_shipping_charged: number;
    avg_carrier_cost: number;
    blended_return_rate: number;
    cod_penetration_pct: number;
    countries_profitable: number;
    countries_marginal: number;
    countries_loss: number;
    total_countries: number;
}

interface Filters {
    from: string;
    to: string;
    region: string | null;
    status: string | null;
    min_orders: number;
}

interface ShippingAnalysisProps extends PageProps {
    countries: CountryRow[];
    summary: Summary;
    filters: Filters;
}

// What-if simulator state
interface SimulatorState {
    freeShippingThreshold: number;      // $ threshold; 0 = off
    freeReturnsCampaign: boolean;
    codSurcharge: number;               // $ per COD order
    carrierCostAdjustPct: number;       // % adjustment to avg_carrier_cost (negative = savings)
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function fmt(value: number, decimals = 2): string {
    if (Math.abs(value) >= 1_000_000) return `$${(value / 1_000_000).toFixed(1)}M`;
    if (Math.abs(value) >= 1_000)     return `$${(value / 1_000).toFixed(1)}k`;
    return `$${value.toFixed(decimals)}`;
}

function fmtPct(value: number): string {
    return `${value.toFixed(1)}%`;
}

function fmtCurrency(value: number): string {
    return `$${value.toFixed(2)}`;
}

function fmtDays(value: number): string {
    return `${value.toFixed(1)}d`;
}

/**
 * Recompute contribution margin for a row given what-if knobs.
 * The simplifications here are intentional mock-tier math — real implementation
 * will call RecomputeAttributionJob with updated cost inputs.
 *
 * Logic:
 *   - freeShippingThreshold: if avg order value (revenue/orders) > threshold,
 *     carrier cost absorbed = avg_carrier_cost (customer no longer charged).
 *     CM decreases by avg_shipping_charged × orders (revenue side loses shipping).
 *   - freeReturnsCampaign: adds avg return cost (return_pct × orders × $8 handling)
 *     to carrier cost side. Returns increase by 20% (more returns when free).
 *   - codSurcharge: adds (cod_pct/100 × orders × surcharge) to revenue.
 *   - carrierCostAdjustPct: multiplies avg_carrier_cost by (1 + pct/100).
 */
function recomputeMargin(row: CountryRow, sim: SimulatorState): number {
    const { orders, revenue, avg_shipping_charged, avg_carrier_cost, return_pct, cod_pct, cod_cost_per_order } = row;
    const aov = orders > 0 ? revenue / orders : 0;

    // Base contribution margin components (simplified)
    let shippingChargedRevenue = avg_shipping_charged * orders;
    let carrierCost = avg_carrier_cost * orders;
    let returnCost = (return_pct / 100) * orders * 8; // $8 avg handling per return
    let codCostTotal = (cod_pct / 100) * orders * cod_cost_per_order;
    let codRevenue = 0;

    // Apply: free shipping threshold
    if (sim.freeShippingThreshold > 0 && aov >= sim.freeShippingThreshold) {
        shippingChargedRevenue = 0; // absorb shipping; customer no longer pays
    }

    // Apply: free returns campaign — handling cost + 20% more returns
    if (sim.freeReturnsCampaign) {
        const extraReturns = (return_pct / 100) * orders * 0.2;
        returnCost += extraReturns * 8;
    }

    // Apply: COD surcharge (added to revenue)
    if (sim.codSurcharge > 0) {
        codRevenue = (cod_pct / 100) * orders * sim.codSurcharge;
    }

    // Apply: carrier cost adjustment
    const adjustedCarrierCost = carrierCost * (1 + sim.carrierCostAdjustPct / 100);

    // Reconstruct CM from base CM removing old shipping/cod cost and applying new
    const baseCarrierCostDelta = adjustedCarrierCost - carrierCost;
    const baseReturnCostDelta = returnCost - ((return_pct / 100) * orders * 8);
    const baseCodCostDelta = 0; // cod cost doesn't change unless surcharge passed through
    const baseShippingRevenueDelta = shippingChargedRevenue - (avg_shipping_charged * orders);

    return row.contribution_margin
        + baseShippingRevenueDelta
        - baseCarrierCostDelta
        - baseReturnCostDelta
        + codRevenue;
}

// ── Sub-components ─────────────────────────────────────────────────────────────

function StatusChip({ status }: { status: CountryRow['status'] }) {
    const map = {
        profitable: {
            label: 'Profitable',
            cls: 'bg-green-50 text-green-700',
            Icon: CheckCircle2,
        },
        marginal: {
            label: 'Marginal',
            cls: 'bg-amber-50 text-amber-700',
            Icon: MinusCircle,
        },
        loss: {
            label: 'Loss',
            cls: 'bg-red-50 text-red-700',
            Icon: AlertTriangle,
        },
    };
    const { label, cls, Icon } = map[status];
    return (
        <span className={cn('inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-semibold', cls)}>
            <Icon className="h-3 w-3" />
            {label}
        </span>
    );
}

function CountryCodeChip({ code }: { code: string }) {
    return (
        <span className="inline-flex items-center justify-center rounded border border-border bg-muted px-1.5 py-0.5 text-xs font-semibold tabular-nums text-muted-foreground leading-none">
            {code}
        </span>
    );
}

interface KnobSliderProps {
    label: string;
    value: number;
    min: number;
    max: number;
    step: number;
    onChange: (v: number) => void;
    format?: (v: number) => string;
    hint?: string;
}

function KnobSlider({ label, value, min, max, step, onChange, format, hint }: KnobSliderProps) {
    const display = format ? format(value) : String(value);
    return (
        <div className="flex flex-col gap-1.5">
            <div className="flex items-center justify-between gap-2">
                <label className="text-sm font-medium text-foreground">{label}</label>
                <span className="text-sm tabular-nums font-semibold text-foreground min-w-[48px] text-right">{display}</span>
            </div>
            <input
                type="range"
                min={min}
                max={max}
                step={step}
                value={value}
                onChange={(e) => onChange(Number(e.target.value))}
                className="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-border accent-primary"
            />
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
        </div>
    );
}

interface WhatIfSimulatorProps {
    simulator: SimulatorState;
    onChange: (s: SimulatorState) => void;
    globalImpactPct: number;
    globalImpactAbs: number;
}

function WhatIfSimulator({ simulator, onChange, globalImpactPct, globalImpactAbs }: WhatIfSimulatorProps) {
    const [expanded, setExpanded] = useState(true);

    const isModified = simulator.freeShippingThreshold > 0
        || simulator.freeReturnsCampaign
        || simulator.codSurcharge > 0
        || simulator.carrierCostAdjustPct !== 0;

    const reset = useCallback(() => {
        onChange({ freeShippingThreshold: 0, freeReturnsCampaign: false, codSurcharge: 0, carrierCostAdjustPct: 0 });
    }, [onChange]);

    return (
        <div className="rounded-xl border border-border bg-card shadow-sm">
            {/* Header */}
            <button
                type="button"
                onClick={() => setExpanded((v) => !v)}
                className="flex w-full items-center justify-between gap-3 px-5 py-4 text-left"
            >
                <div className="flex items-center gap-2">
                    <BarChart3 className="h-4 w-4 text-muted-foreground" />
                    <span className="text-sm font-semibold text-foreground">What-if simulator</span>
                    {isModified && (
                        <span className="rounded bg-primary/10 px-1.5 py-0.5 text-xs font-semibold text-primary">
                            Active
                        </span>
                    )}
                </div>
                <div className="flex items-center gap-3">
                    {isModified && (
                        <div className={cn(
                            'rounded px-2 py-1 text-xs font-semibold tabular-nums',
                            globalImpactAbs >= 0
                                ? 'bg-green-50 text-green-700'
                                : 'bg-red-50 text-red-700'
                        )}>
                            {globalImpactAbs >= 0 ? '+' : ''}{fmtPct(globalImpactPct)} margin impact
                        </div>
                    )}
                    {expanded
                        ? <ChevronUp className="h-4 w-4 text-muted-foreground" />
                        : <ChevronDown className="h-4 w-4 text-muted-foreground" />
                    }
                </div>
            </button>

            {/* Body */}
            {expanded && (
                <div className="border-t border-border px-5 py-4">
                    <div className="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
                        {/* Knob 1: Free shipping threshold */}
                        <KnobSlider
                            label="Free shipping threshold"
                            value={simulator.freeShippingThreshold}
                            min={0}
                            max={200}
                            step={5}
                            onChange={(v) => onChange({ ...simulator, freeShippingThreshold: v })}
                            format={(v) => v === 0 ? 'Off' : `$${v}+`}
                            hint="Orders above this amount get free shipping. $0 = off."
                        />

                        {/* Knob 2: Free returns campaign */}
                        <div className="flex flex-col gap-1.5">
                            <div className="flex items-center justify-between gap-2">
                                <label className="text-sm font-medium text-foreground">Free returns campaign</label>
                                <button
                                    type="button"
                                    onClick={() => onChange({ ...simulator, freeReturnsCampaign: !simulator.freeReturnsCampaign })}
                                    className={cn(
                                        'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors',
                                        simulator.freeReturnsCampaign ? 'bg-primary' : 'bg-muted-foreground/30'
                                    )}
                                >
                                    <span className={cn(
                                        'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition-transform',
                                        simulator.freeReturnsCampaign ? 'translate-x-4' : 'translate-x-0'
                                    )} />
                                </button>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Models return handling cost + 20% return rate increase.
                            </p>
                        </div>

                        {/* Knob 3: COD surcharge */}
                        <KnobSlider
                            label="COD surcharge"
                            value={simulator.codSurcharge}
                            min={0}
                            max={10}
                            step={0.50}
                            onChange={(v) => onChange({ ...simulator, codSurcharge: v })}
                            format={(v) => v === 0 ? 'Off' : `$${v.toFixed(2)}/order`}
                            hint="Added to COD order revenue (passed to customer)."
                        />

                        {/* Knob 4: Carrier cost adjustment */}
                        <KnobSlider
                            label="Carrier cost adjustment"
                            value={simulator.carrierCostAdjustPct}
                            min={-40}
                            max={40}
                            step={5}
                            onChange={(v) => onChange({ ...simulator, carrierCostAdjustPct: v })}
                            format={(v) => v === 0 ? '±0%' : `${v > 0 ? '+' : ''}${v}%`}
                            hint="Model a carrier rate renegotiation or surcharge."
                        />
                    </div>

                    {/* Global projection + reset */}
                    {isModified && (
                        <div className="mt-5 flex flex-col gap-3 rounded-lg bg-muted/50 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex flex-wrap gap-x-6 gap-y-1">
                                <div>
                                    <span className="text-xs text-muted-foreground">Global margin impact</span>
                                    <p className={cn(
                                        'text-sm font-semibold tabular-nums',
                                        globalImpactAbs >= 0 ? 'text-green-700' : 'text-red-700'
                                    )}>
                                        {globalImpactAbs >= 0 ? '+' : ''}{fmt(globalImpactAbs)} ({globalImpactAbs >= 0 ? '+' : ''}{fmtPct(globalImpactPct)})
                                    </p>
                                </div>
                                <div>
                                    <span className="text-xs text-muted-foreground">Contribution margin column</span>
                                    <p className="text-sm font-medium text-foreground">Recomputed live below</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={reset}
                                className="inline-flex items-center gap-1.5 rounded-md border border-border bg-background px-3 py-1.5 text-sm font-medium text-foreground hover:bg-muted transition-colors"
                            >
                                <RotateCcw className="h-3.5 w-3.5" />
                                Reset
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

interface CountryDetailPanelProps {
    row: CountryRow;
    simulatedMargin: number;
    onClose: () => void;
}

function CountryDetailPanel({ row, simulatedMargin, onClose }: CountryDetailPanelProps) {
    const { detail } = row;
    const marginChanged = Math.abs(simulatedMargin - row.contribution_margin) > 0.5;
    const maxOrders = Math.max(...detail.daily_orders.map((d) => d.orders), 1);

    return (
        <DrawerSidePanel
            open
            onClose={onClose}
            title={row.country_name}
            subtitle={
                <div className="flex items-center gap-2">
                    <CountryCodeChip code={row.country_code} />
                    <span>{row.region}</span>
                    <StatusChip status={row.status} />
                </div>
            }
            width={560}
        >
            <div className="flex flex-col gap-6 px-5 py-4">
                {/* Key metrics strip */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    {[
                        { label: 'Orders', value: row.orders.toLocaleString() },
                        { label: 'Revenue', value: fmt(row.revenue) },
                        { label: 'Avg shipping charged', value: fmtCurrency(row.avg_shipping_charged) },
                        { label: 'Avg carrier cost', value: fmtCurrency(row.avg_carrier_cost) },
                        { label: 'Return rate', value: fmtPct(row.return_pct) },
                        { label: 'COD %', value: fmtPct(row.cod_pct) },
                    ].map(({ label, value }) => (
                        <div key={label} className="rounded-lg border border-border bg-muted/30 p-3">
                            <p className="text-xs text-muted-foreground">{label}</p>
                            <p className="mt-0.5 text-sm font-semibold tabular-nums text-foreground">{value}</p>
                        </div>
                    ))}
                </div>

                {/* Contribution margin (with simulator highlight) */}
                <div className="rounded-lg border border-border bg-muted/30 p-4">
                    <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2">Contribution margin</p>
                    <div className="flex items-baseline gap-3">
                        <span className={cn(
                            'text-xl font-bold tabular-nums',
                            row.contribution_margin < 0 ? 'text-red-600' : 'text-foreground'
                        )}>
                            {fmt(row.contribution_margin)}
                        </span>
                        <span className="text-sm text-muted-foreground">({fmtPct(row.contribution_margin_pct)})</span>
                        {marginChanged && (
                            <span className={cn(
                                'ml-auto rounded px-2 py-0.5 text-xs font-semibold tabular-nums',
                                simulatedMargin >= row.contribution_margin ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'
                            )}>
                                With what-if: {fmt(simulatedMargin)}
                            </span>
                        )}
                    </div>
                </div>

                {/* Daily orders sparkline (7d) */}
                <div>
                    <h3 className="mb-3 text-sm font-semibold text-foreground">Orders — last 7 days</h3>
                    <div className="flex h-16 items-end gap-1">
                        {detail.daily_orders.map((d) => (
                            <div key={d.date} className="flex flex-1 flex-col items-center gap-1">
                                <div
                                    className="w-full rounded-t bg-primary/60"
                                    style={{ height: `${Math.round((d.orders / maxOrders) * 52)}px`, minHeight: '4px' }}
                                    title={`${d.date}: ${d.orders} orders`}
                                />
                                <span className="text-xs text-muted-foreground tabular-nums">
                                    {new Date(d.date).toLocaleDateString('en-US', { weekday: 'narrow' })}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Top products */}
                <div>
                    <h3 className="mb-3 text-sm font-semibold text-foreground">Top products</h3>
                    <div className="divide-y divide-border rounded-lg border border-border overflow-hidden">
                        {detail.top_products.map((p) => (
                            <div key={p.name} className="flex items-center justify-between gap-3 px-3 py-2.5">
                                <span className="truncate text-sm text-foreground">{p.name}</span>
                                <div className="flex shrink-0 items-center gap-3 text-sm tabular-nums text-muted-foreground">
                                    <span>{p.orders} orders</span>
                                    <span className="font-medium text-foreground">{fmt(p.revenue)}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Top channels */}
                <div>
                    <h3 className="mb-3 text-sm font-semibold text-foreground">Top channels</h3>
                    <div className="space-y-2">
                        {detail.top_channels.map((c) => (
                            <div key={c.channel} className="flex items-center gap-3">
                                <span className="w-32 shrink-0 truncate text-sm text-foreground">{c.channel}</span>
                                <div className="flex-1 rounded-full bg-muted h-2">
                                    <div
                                        className="h-2 rounded-full bg-primary/70"
                                        style={{ width: `${c.pct}%` }}
                                    />
                                </div>
                                <span className="w-8 text-right text-xs tabular-nums text-muted-foreground">{c.pct}%</span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Return reasons */}
                <div>
                    <h3 className="mb-3 text-sm font-semibold text-foreground">Return reasons</h3>
                    <div className="space-y-2">
                        {detail.return_reasons.map((r) => (
                            <div key={r.reason} className="flex items-center gap-3">
                                <span className="flex-1 truncate text-sm text-foreground">{r.reason}</span>
                                <div className="w-24 rounded-full bg-muted h-1.5 shrink-0">
                                    <div
                                        className="h-1.5 rounded-full bg-rose-400"
                                        style={{ width: `${r.pct}%` }}
                                    />
                                </div>
                                <span className="w-8 text-right text-xs tabular-nums text-muted-foreground">{r.pct}%</span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Carrier mix */}
                <div>
                    <h3 className="mb-3 text-sm font-semibold text-foreground">Carrier mix</h3>
                    <div className="grid grid-cols-2 gap-2">
                        {detail.carrier_mix.map((c) => (
                            <div key={c.carrier} className="flex items-center justify-between rounded-md border border-border bg-muted/30 px-3 py-2">
                                <span className="text-sm text-foreground flex items-center gap-1.5">
                                    <Truck className="h-3.5 w-3.5 text-muted-foreground" />
                                    {c.carrier}
                                </span>
                                <span className="text-sm tabular-nums font-medium text-foreground">{c.pct}%</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </DrawerSidePanel>
    );
}

// ── Sort state ─────────────────────────────────────────────────────────────────

type SortKey = keyof Omit<CountryRow, 'country_code' | 'country_name' | 'region' | 'status' | 'detail'>;
type SortDir = 'asc' | 'desc';

function SortIcon({ active, dir }: { active: boolean; dir: SortDir }) {
    if (!active) return <ChevronsUpDown className="h-3.5 w-3.5 opacity-40" />;
    return dir === 'asc'
        ? <ChevronUp className="h-3.5 w-3.5 text-primary" />
        : <ChevronDown className="h-3.5 w-3.5 text-primary" />;
}

// ── Main component ─────────────────────────────────────────────────────────────

export default function ShippingAnalysis({ countries, summary, filters }: ShippingAnalysisProps) {
    const { props } = usePage<ShippingAnalysisProps>();
    const slug = props.workspace?.slug;

    // Simulator state
    const [simulator, setSimulator] = useState<SimulatorState>({
        freeShippingThreshold: 0,
        freeReturnsCampaign: false,
        codSurcharge: 0,
        carrierCostAdjustPct: 0,
    });

    // Filter state (in-page, no Inertia reload)
    const [regionFilter, setRegionFilter] = useState<string>(filters.region ?? '');
    const [statusFilter, setStatusFilter] = useState<string>(filters.status ?? '');
    const [minOrdersFilter, setMinOrdersFilter] = useState<number>(filters.min_orders ?? 0);
    const [countrySearch, setCountrySearch] = useState('');
    const [drillCountry, setDrillCountry] = useState<CountryRow | null>(null);
    const [sortKey, setSortKey] = useState<SortKey>('contribution_margin');
    const [sortDir, setSortDir] = useState<SortDir>('desc');

    // Extract unique regions
    const regions = useMemo(
        () => Array.from(new Set(countries.map((c) => c.region))).sort(),
        [countries]
    );

    // Filtered + sorted rows
    const filteredRows = useMemo(() => {
        let rows = countries.filter((c) => {
            if (regionFilter && c.region !== regionFilter) return false;
            if (statusFilter && c.status !== statusFilter) return false;
            if (c.orders < minOrdersFilter) return false;
            if (countrySearch) {
                const q = countrySearch.toLowerCase();
                if (!c.country_name.toLowerCase().includes(q) && !c.country_code.toLowerCase().includes(q)) return false;
            }
            return true;
        });

        rows = [...rows].sort((a, b) => {
            const av = a[sortKey] as number;
            const bv = b[sortKey] as number;
            return sortDir === 'asc' ? av - bv : bv - av;
        });

        return rows;
    }, [countries, regionFilter, statusFilter, minOrdersFilter, countrySearch, sortKey, sortDir]);

    // Simulated margins (keyed by country_code)
    const simulatedMargins = useMemo(() => {
        const map: Record<string, number> = {};
        countries.forEach((row) => {
            map[row.country_code] = recomputeMargin(row, simulator);
        });
        return map;
    }, [countries, simulator]);

    // Global impact projection
    const { globalImpactAbs, globalImpactPct } = useMemo(() => {
        const baseTotal = countries.reduce((s, c) => s + c.contribution_margin, 0);
        const simTotal = countries.reduce((s, c) => s + simulatedMargins[c.country_code], 0);
        const diff = simTotal - baseTotal;
        const pct = baseTotal !== 0 ? (diff / Math.abs(baseTotal)) * 100 : 0;
        return { globalImpactAbs: diff, globalImpactPct: pct };
    }, [countries, simulatedMargins]);

    function handleSort(key: SortKey) {
        if (key === sortKey) {
            setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
        } else {
            setSortKey(key);
            setSortDir('desc');
        }
    }

    const isSimulatorActive = simulator.freeShippingThreshold > 0
        || simulator.freeReturnsCampaign
        || simulator.codSurcharge > 0
        || simulator.carrierCostAdjustPct !== 0;

    type Col = {
        key: SortKey;
        label: string;
        hint?: string;
        align?: 'right';
        render: (row: CountryRow) => React.ReactNode;
    };

    const cols: Col[] = [
        {
            key: 'orders',
            label: 'Orders',
            align: 'right',
            render: (r) => r.orders.toLocaleString(),
        },
        {
            key: 'revenue',
            label: 'Revenue',
            align: 'right',
            render: (r) => fmt(r.revenue),
        },
        {
            key: 'avg_shipping_charged',
            label: 'Avg ship charged',
            hint: 'Avg shipping amount the customer paid',
            align: 'right',
            render: (r) => fmtCurrency(r.avg_shipping_charged),
        },
        {
            key: 'avg_carrier_cost',
            label: 'Avg carrier cost',
            hint: 'Avg carrier invoice cost per order',
            align: 'right',
            render: (r) => (
                <span className={cn(
                    'tabular-nums',
                    r.avg_carrier_cost > r.avg_shipping_charged ? 'text-rose-600 font-semibold' : 'text-foreground'
                )}>
                    {fmtCurrency(r.avg_carrier_cost)}
                </span>
            ),
        },
        {
            key: 'return_pct',
            label: 'Return %',
            align: 'right',
            render: (r) => (
                <span className={cn('tabular-nums', r.return_pct > 15 ? 'text-rose-600 font-semibold' : 'text-foreground')}>
                    {fmtPct(r.return_pct)}
                </span>
            ),
        },
        {
            key: 'cod_pct',
            label: 'COD %',
            align: 'right',
            render: (r) => (
                <span className={cn('tabular-nums', r.cod_pct > 30 ? 'text-amber-600 font-semibold' : 'text-muted-foreground')}>
                    {r.cod_pct > 0 ? fmtPct(r.cod_pct) : <span className="text-muted-foreground/50">—</span>}
                </span>
            ),
        },
        {
            key: 'cod_cost_per_order',
            label: 'COD cost/order',
            align: 'right',
            render: (r) => (
                r.cod_cost_per_order > 0
                    ? fmtCurrency(r.cod_cost_per_order)
                    : <span className="text-muted-foreground/50">—</span>
            ),
        },
        {
            key: 'avg_speed_days',
            label: 'Avg speed',
            align: 'right',
            render: (r) => fmtDays(r.avg_speed_days),
        },
        {
            key: 'contribution_margin',
            label: isSimulatorActive ? 'Contrib. margin (simulated)' : 'Contrib. margin',
            align: 'right',
            render: (r) => {
                const simVal = simulatedMargins[r.country_code];
                const changed = isSimulatorActive && Math.abs(simVal - r.contribution_margin) > 0.5;
                const pct = r.orders > 0 && r.revenue > 0
                    ? (simVal / r.revenue) * 100
                    : r.contribution_margin_pct;
                return (
                    <div className="text-right">
                        <span className={cn(
                            'tabular-nums font-semibold',
                            simVal < 0 ? 'text-red-600' : 'text-foreground'
                        )}>
                            {fmt(simVal)}
                        </span>
                        <span className="ml-1 text-xs text-muted-foreground">
                            ({fmtPct(pct)})
                        </span>
                        {changed && (
                            <div className={cn(
                                'text-xs font-medium',
                                simVal > r.contribution_margin ? 'text-green-600' : 'text-rose-600'
                            )}>
                                {simVal > r.contribution_margin ? '+' : ''}{fmt(simVal - r.contribution_margin)}
                            </div>
                        )}
                    </div>
                );
            },
        },
    ];

    return (
        <AppLayout>
            <Head title="Shipping Analysis" />

            <div className="mx-auto max-w-[1400px] space-y-6 p-6">
                {/* Page header */}
                <PageHeader
                    title="Shipping Analysis"
                    subtitle="Carrier cost vs charged, return rates, COD penetration, and contribution margin by country"
                    action={
                        <a
                            href={wurl(slug, '/settings/costs') + '?section=shipping'}
                            className="inline-flex items-center gap-1.5 rounded-md border border-border bg-background px-3 py-2 text-sm font-medium text-foreground hover:bg-muted transition-colors"
                        >
                            <Package className="h-4 w-4" />
                            Edit shipping rules
                        </a>
                    }
                />

                {/* Summary KPI strip */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <MetricCardCompact
                        label="Avg shipping charged"
                        value={fmtCurrency(summary.avg_shipping_charged)}
                    />
                    <MetricCardCompact
                        label="Avg carrier cost"
                        value={fmtCurrency(summary.avg_carrier_cost)}
                        delta={summary.avg_carrier_cost > summary.avg_shipping_charged ? -1 : 0}
                    />
                    <MetricCardCompact
                        label="Blended return rate"
                        value={fmtPct(summary.blended_return_rate)}
                    />
                    <MetricCardCompact
                        label="COD penetration"
                        value={fmtPct(summary.cod_penetration_pct)}
                    />
                </div>

                {/* Country status overview */}
                <div className="flex flex-wrap items-center gap-3 rounded-lg border border-border bg-muted/30 px-4 py-3">
                    <Globe className="h-4 w-4 text-muted-foreground shrink-0" />
                    <span className="text-sm text-muted-foreground">{summary.total_countries} countries:</span>
                    <div className="flex flex-wrap gap-2">
                        <span className="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold bg-green-50 text-green-700">
                            <CheckCircle2 className="h-3 w-3" />
                            {summary.countries_profitable} Profitable
                        </span>
                        <span className="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold bg-amber-50 text-amber-700">
                            <MinusCircle className="h-3 w-3" />
                            {summary.countries_marginal} Marginal
                        </span>
                        <span className="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold bg-red-50 text-red-700">
                            <AlertTriangle className="h-3 w-3" />
                            {summary.countries_loss} Loss
                        </span>
                    </div>
                </div>

                {/* What-if simulator */}
                <WhatIfSimulator
                    simulator={simulator}
                    onChange={setSimulator}
                    globalImpactPct={globalImpactPct}
                    globalImpactAbs={globalImpactAbs}
                />

                {/* In-page filters */}
                <div className="flex flex-wrap items-center gap-3">
                    {/* Country search */}
                    <div className="relative min-w-[200px] max-w-[280px] flex-1">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            type="text"
                            placeholder="Search country..."
                            value={countrySearch}
                            onChange={(e) => setCountrySearch(e.target.value)}
                            className="w-full rounded-md border border-border bg-background py-2 pl-9 pr-8 text-sm outline-none focus:ring-2 focus:ring-primary/30 placeholder:text-muted-foreground"
                        />
                        {countrySearch && (
                            <button
                                type="button"
                                onClick={() => setCountrySearch('')}
                                className="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <X className="h-3.5 w-3.5" />
                            </button>
                        )}
                    </div>

                    {/* Region filter */}
                    <select
                        value={regionFilter}
                        onChange={(e) => setRegionFilter(e.target.value)}
                        className="rounded-md border border-border bg-background py-2 pl-3 pr-8 text-sm text-foreground outline-none focus:ring-2 focus:ring-primary/30"
                    >
                        <option value="">All regions</option>
                        {regions.map((r) => (
                            <option key={r} value={r}>{r}</option>
                        ))}
                    </select>

                    {/* Status filter */}
                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="rounded-md border border-border bg-background py-2 pl-3 pr-8 text-sm text-foreground outline-none focus:ring-2 focus:ring-primary/30"
                    >
                        <option value="">All statuses</option>
                        <option value="profitable">Profitable</option>
                        <option value="marginal">Marginal</option>
                        <option value="loss">Loss</option>
                    </select>

                    {/* Min orders */}
                    <div className="flex items-center gap-2">
                        <label className="text-sm text-muted-foreground whitespace-nowrap">Min orders:</label>
                        <input
                            type="number"
                            min={0}
                            value={minOrdersFilter}
                            onChange={(e) => setMinOrdersFilter(Number(e.target.value))}
                            className="w-20 rounded-md border border-border bg-background py-2 px-3 text-sm outline-none focus:ring-2 focus:ring-primary/30"
                        />
                    </div>

                    {/* Result count */}
                    <span className="ml-auto text-sm text-muted-foreground">
                        {filteredRows.length} of {countries.length} countries
                    </span>
                </div>

                {/* Data table */}
                <div className="rounded-xl border border-border bg-card shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-[1100px] w-full text-sm">
                            <thead className="sticky top-0 z-10 bg-muted/60 border-b border-border">
                                <tr className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                                    {/* Country column — not sortable (string) */}
                                    <th className="px-4 py-2.5 text-left w-[180px]">Country</th>
                                    {cols.map((col) => (
                                        <th
                                            key={col.key}
                                            className={cn('px-4 py-2.5 cursor-pointer select-none hover:text-foreground transition-colors', col.align === 'right' ? 'text-right' : 'text-left')}
                                            onClick={() => handleSort(col.key)}
                                            title={col.hint}
                                        >
                                            <span className={cn('inline-flex items-center gap-1', col.align === 'right' ? 'flex-row-reverse' : 'flex-row')}>
                                                {col.label}
                                                <SortIcon active={sortKey === col.key} dir={sortDir} />
                                            </span>
                                        </th>
                                    ))}
                                    <th className="px-4 py-2.5 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {filteredRows.length === 0 ? (
                                    <tr>
                                        <td colSpan={cols.length + 2} className="px-4 py-12">
                                            <EmptyState
                                                title="No countries match your filters"
                                                description="Try adjusting the region, status, or min orders filter."
                                            />
                                        </td>
                                    </tr>
                                ) : filteredRows.map((row) => (
                                    <tr
                                        key={row.country_code}
                                        className="hover:bg-muted/40 cursor-pointer transition-colors"
                                        onClick={() => setDrillCountry(row)}
                                    >
                                        {/* Country cell */}
                                        <td className="px-4 py-2.5">
                                            <div className="flex items-center gap-2">
                                                <CountryCodeChip code={row.country_code} />
                                                <span className="truncate max-w-[120px] text-foreground" title={row.country_name}>
                                                    {row.country_name}
                                                </span>
                                            </div>
                                        </td>
                                        {/* Data columns */}
                                        {cols.map((col) => (
                                            <td
                                                key={col.key}
                                                className={cn('px-4 py-2.5 tabular-nums', col.align === 'right' ? 'text-right' : 'text-left')}
                                            >
                                                {col.render(row)}
                                            </td>
                                        ))}
                                        {/* Status chip */}
                                        <td className="px-4 py-2.5">
                                            <StatusChip status={row.status} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Footer note */}
                <p className="text-xs text-muted-foreground">
                    Contribution margin = Revenue &minus; COGS &minus; carrier cost &minus; transaction fees &minus; return handling cost.
                    Ad spend and OpEx excluded (those live in{' '}
                    <a href={wurl(slug, '/profit')} className="underline underline-offset-2 hover:text-foreground transition-colors">Profit &amp; Loss</a>).
                    Shipping rules are configured in{' '}
                    <a href={wurl(slug, '/settings/costs') + '?section=shipping'} className="underline underline-offset-2 hover:text-foreground transition-colors">Settings &rarr; Costs &rarr; Shipping</a>.
                </p>
            </div>

            {/* Drawer */}
            {drillCountry && (
                <CountryDetailPanel
                    row={drillCountry}
                    simulatedMargin={simulatedMargins[drillCountry.country_code]}
                    onClose={() => setDrillCountry(null)}
                />
            )}
        </AppLayout>
    );
}
