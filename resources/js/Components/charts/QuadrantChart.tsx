import React, { useMemo } from 'react';
import {
    ScatterChart,
    Scatter,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    ReferenceLine,
    ReferenceArea,
    Cell,
} from 'recharts';
import { formatCurrency, formatNumber } from '@/lib/formatters';

export interface QuadrantCampaign {
    id: number;
    name: string;
    platform: string;
    spend: number;
    real_roas: number | null;
    attributed_revenue: number | null;
    attributed_orders: number;
}

interface Props {
    campaigns: QuadrantCampaign[];
    currency: string;
    targetRoas?: number;
    // Override Y-axis label when using platform ROAS instead of real ROAS (adset/ad level)
    yLabel?: string;
    // Number of items hidden from this chart (no ROAS signal), shown as an extra counter
    hiddenCount?: number;
    // Label for the hidden items (e.g. "campaigns", "ad sets", "ads")
    hiddenLabel?: string;
}

const PLATFORM_COLORS: Record<string, string> = {
    facebook: '#1877f2',
    google:   '#ea4335',
};

type Quadrant = 'scale' | 'hidden_gem' | 'cut' | 'ignore' | 'no_attribution';

function getQuadrant(
    spend: number,
    roas: number | null,
    medianSpend: number,
    targetRoas: number,
): Quadrant {
    if (roas === null) return 'no_attribution';
    const highSpend = spend >= medianSpend;
    const highRoas  = roas >= targetRoas;
    if (highRoas  && highSpend) return 'scale';
    if (highRoas  && !highSpend) return 'hidden_gem';
    if (!highRoas && highSpend) return 'cut';
    return 'ignore';
}

const QUADRANT_COLORS: Record<Quadrant, string> = {
    scale:          '#16a34a', // green-600
    hidden_gem:     '#0d9488', // teal-600
    cut:            '#dc2626', // red-600
    ignore:         '#71717a', // zinc-500
    no_attribution: '#a1a1aa', // zinc-400
};

const QUADRANT_LABELS: Record<Quadrant, string> = {
    scale:          'Scale',
    hidden_gem:     'Hidden Gem',
    cut:            'Cut / Fix',
    ignore:         'Ignore',
    no_attribution: 'No attribution',
};

// Normalise bubble size between min and max area
function scaleBubble(value: number | null, min: number, max: number): number {
    if (value === null || max === min) return 200;
    const ratio = (value - min) / (max - min);
    return Math.max(40, Math.round(40 + ratio * 500));
}

interface TooltipPayload {
    payload: {
        name: string;
        platform: string;
        spend: number;
        real_roas: number | null;
        attributed_revenue: number | null;
        attributed_orders: number;
        _quadrant: Quadrant;
        r: number;
    };
}

function makeCustomTooltip(currency: string, yLabel: string) {
    return function CustomTooltip({ active, payload }: { active?: boolean; payload?: TooltipPayload[] }) {
        if (!active || !payload?.length) return null;
        const d = payload[0].payload;

        return (
            <div className="rounded-xl border border-zinc-200 bg-white px-4 py-3 shadow-lg text-sm min-w-[220px]">
                <div className="mb-2 flex items-center gap-2">
                    <span
                        className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white capitalize"
                        style={{ backgroundColor: PLATFORM_COLORS[d.platform] ?? '#71717a' }}
                    >
                        {d.platform}
                    </span>
                    <span className="font-semibold text-zinc-900 truncate max-w-[160px]" title={d.name}>
                        {d.name}
                    </span>
                </div>
                <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                    <span className="text-zinc-400">Quadrant</span>
                    <span className="font-medium text-zinc-700">{QUADRANT_LABELS[d._quadrant]}</span>

                    <span className="text-zinc-400">Spend</span>
                    <span className="font-medium tabular-nums text-zinc-700">
                        {formatCurrency(d.spend, currency)}
                    </span>

                    <span className="text-zinc-400">{yLabel}</span>
                    <span className={[
                        'font-medium tabular-nums',
                        d.real_roas != null
                            ? d.real_roas >= 1.5 ? 'text-green-700' : 'text-red-600'
                            : 'text-zinc-400',
                    ].join(' ')}>
                        {d.real_roas != null ? `${d.real_roas.toFixed(2)}×` : '—'}
                    </span>

                    {d.attributed_revenue !== null && (
                        <>
                            <span className="text-zinc-400">Attr. Revenue</span>
                            <span className="font-medium tabular-nums text-zinc-700">
                                {formatCurrency(d.attributed_revenue, currency)}
                            </span>
                        </>
                    )}

                    {d.attributed_orders > 0 && (
                        <>
                            <span className="text-zinc-400">Attr. Orders</span>
                            <span className="font-medium tabular-nums text-zinc-700">
                                {formatNumber(d.attributed_orders)}
                            </span>
                        </>
                    )}
                </div>
            </div>
        );
    };
}

const QuadrantChart = React.memo(function QuadrantChart({
    campaigns,
    currency,
    targetRoas = 1.5,
    yLabel = 'Real ROAS',
    hiddenCount = 0,
    hiddenLabel = 'items',
}: Props) {
    const { points, medianSpend, minSpend, maxSpend, minRoas, maxRoas } = useMemo(() => {
        const withSpend = campaigns.filter((c) => c.spend > 0);
        if (withSpend.length === 0) return { points: [], medianSpend: 0, minSpend: 0, maxSpend: 0, minRoas: 0, maxRoas: 0 };

        const sorted = [...withSpend].sort((a, b) => a.spend - b.spend);
        const mid    = Math.floor(sorted.length / 2);
        const median = sorted.length % 2 === 0
            ? (sorted[mid - 1].spend + sorted[mid].spend) / 2
            : sorted[mid].spend;

        const revenues = withSpend.map((c) => c.attributed_revenue ?? 0);
        const minRev   = Math.min(...revenues);
        const maxRev   = Math.max(...revenues);
        const minS     = Math.min(...withSpend.map((c) => c.spend));
        const maxS     = Math.max(...withSpend.map((c) => c.spend));
        const withRoas = withSpend.filter((c) => c.real_roas != null);
        const minR     = withRoas.length > 0 ? Math.min(...withRoas.map((c) => c.real_roas!)) : 0;
        const maxR     = withRoas.length > 0 ? Math.max(...withRoas.map((c) => c.real_roas!)) : 0;

        const pts = withSpend.map((c) => ({
            ...c,
            y:         c.real_roas ?? 0.01,
            r:         scaleBubble(c.attributed_revenue, minRev, maxRev),
            _quadrant: getQuadrant(c.spend, c.real_roas, median, targetRoas),
        }));

        return { points: pts, medianSpend: median, minSpend: minS, maxSpend: maxS, minRoas: minR, maxRoas: maxR };
    }, [campaigns, targetRoas]);

    if (points.length === 0) {
        return (
            <div className="flex h-[460px] flex-col items-center justify-center gap-2 text-center">
                <p className="text-sm text-zinc-400">No spend data for this period.</p>
            </div>
        );
    }

    const yMax  = Math.max(targetRoas * 2, maxRoas * 1.1, 3);
    const xMax  = maxSpend * 2;
    const xMin  = minSpend * 0.5;
    // Y lower bound: just below the lowest data point, but never below targetRoas/4
    // so the target reference line always stays visible near the bottom.
    const yMin  = Math.min(minRoas * 0.7, targetRoas * 0.5);

    return (
        <div>
            <ResponsiveContainer width="100%" height={460} minWidth={0}>
                <ScatterChart margin={{ top: 16, right: 24, bottom: 32, left: 8 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#f4f4f5" />

                    {/* Quadrant backgrounds — use xMin/yMin as lower bounds (log scale excludes 0) */}
                    <ReferenceArea x1={medianSpend} x2={xMax} y1={targetRoas} y2={yMax}
                        fill="#f0fdf4" fillOpacity={0.6}
                        label={{ value: 'Scale', position: 'insideTopRight', fontSize: 11, fill: '#16a34a', fontWeight: 600 }} />
                    <ReferenceArea x1={xMin} x2={medianSpend} y1={targetRoas} y2={yMax}
                        fill="#f0fdfa" fillOpacity={0.6}
                        label={{ value: 'Hidden Gem', position: 'insideTopLeft', fontSize: 11, fill: '#0d9488', fontWeight: 600 }} />
                    <ReferenceArea x1={medianSpend} x2={xMax} y1={yMin} y2={targetRoas}
                        fill="#fef2f2" fillOpacity={0.6}
                        label={{ value: 'Cut / Fix', position: 'insideBottomRight', fontSize: 11, fill: '#dc2626', fontWeight: 600 }} />
                    <ReferenceArea x1={xMin} x2={medianSpend} y1={yMin} y2={targetRoas}
                        fill="#fafafa" fillOpacity={0.6}
                        label={{ value: 'Ignore', position: 'insideBottomLeft', fontSize: 11, fill: '#71717a', fontWeight: 600 }} />

                    {/* Quadrant dividers */}
                    <ReferenceLine x={medianSpend} stroke="#d4d4d8" strokeDasharray="4 4"
                        label={{ value: 'Median spend', position: 'insideTopRight', fontSize: 10, fill: '#a1a1aa' }} />
                    <ReferenceLine y={targetRoas} stroke="#4f46e5" strokeDasharray="4 4" strokeWidth={1.5}
                        label={{ value: `Target ROAS (${targetRoas}×)`, position: 'insideTopLeft', fontSize: 10, fill: '#4f46e5' }} />

                    {/* Log scale on X: spreads out power-law spend distributions so small
                        campaigns don't cluster invisibly near the Y-axis */}
                    <XAxis
                        type="number"
                        dataKey="spend"
                        name="Spend"
                        scale="log"
                        domain={[xMin, xMax]}
                        tickFormatter={(v) => formatCurrency(v, currency, true)}
                        tick={{ fontSize: 11, fill: '#a1a1aa' }}
                        label={{ value: 'Ad Spend', position: 'insideBottom', offset: -16, fontSize: 11, fill: '#71717a' }}
                    />
                    {/* Log scale on Y: spreads out campaigns clustered near ROAS=0
                        (no attribution) from high performers. Floor at 0.01 for nulls. */}
                    <YAxis
                        type="number"
                        dataKey="y"
                        name={yLabel}
                        scale="log"
                        domain={[yMin, yMax]}
                        tickFormatter={(v) => v < 0.1 ? '' : `${v.toFixed(1)}×`}
                        tick={{ fontSize: 11, fill: '#a1a1aa' }}
                        label={{ value: yLabel, angle: -90, position: 'insideLeft', offset: 12, fontSize: 11, fill: '#71717a' }}
                        width={56}
                    />
                    <Tooltip content={React.createElement(makeCustomTooltip(currency, yLabel))} />

                    <Scatter data={points} shape="circle">
                        {points.map((p, idx) => (
                            <Cell
                                key={`cell-${idx}`}
                                fill={p._quadrant === 'no_attribution'
                                    ? QUADRANT_COLORS.no_attribution
                                    : (PLATFORM_COLORS[p.platform] ?? QUADRANT_COLORS[p._quadrant])}
                                fillOpacity={0.75}
                                stroke={QUADRANT_COLORS[p._quadrant]}
                                strokeWidth={1.5}
                                r={Math.sqrt(p.r / Math.PI)}
                            />
                        ))}
                    </Scatter>
                </ScatterChart>
            </ResponsiveContainer>

            {/* Quadrant breakdown counts */}
            {(() => {
                const counts: Record<Quadrant, number> = { scale: 0, hidden_gem: 0, cut: 0, ignore: 0, no_attribution: 0 };
                for (const p of points) counts[p._quadrant]++;
                const visible: { q: Exclude<Quadrant, 'no_attribution'>; label: string; color: string }[] = [
                    { q: 'scale',      label: 'Scale',       color: '#16a34a' },
                    { q: 'hidden_gem', label: 'Hidden Gem',  color: '#0d9488' },
                    { q: 'cut',        label: 'Cut / Fix',   color: '#dc2626' },
                    { q: 'ignore',     label: 'Ignore',      color: '#71717a' },
                ];
                return (
                    <div className="mt-4 flex flex-wrap items-center justify-center gap-x-5 gap-y-1.5 text-xs">
                        {visible.map(({ q, label, color }) => (
                            <div key={q} className="flex items-center gap-1.5">
                                <span className="h-2 w-2 rounded-full flex-shrink-0" style={{ backgroundColor: color }} />
                                <span className="text-zinc-500">{label}</span>
                                <span className="tabular-nums font-semibold text-zinc-800">{counts[q]}</span>
                            </div>
                        ))}
                        {hiddenCount > 0 && (
                            <div className="flex items-center gap-1.5" title={`${hiddenCount} ${hiddenLabel} have no ROAS signal — switch to Platform ROAS to see them`}>
                                <span className="h-2 w-2 rounded-full flex-shrink-0 bg-zinc-300" />
                                <span className="text-zinc-400">No attribution</span>
                                <span className="tabular-nums font-semibold text-zinc-400">{hiddenCount}</span>
                            </div>
                        )}
                    </div>
                );
            })()}

            {/* Quadrant guide — 2×2 grid explaining each zone */}
            <div className="mt-5 grid grid-cols-2 gap-2 text-xs">
                <div className="rounded-lg border border-teal-100 bg-teal-50 px-3 py-2.5">
                    <div className="flex items-center gap-1.5 font-semibold text-teal-700 mb-0.5">
                        <span className="h-2 w-2 rounded-full bg-teal-600" />
                        Hidden Gem — grow budget now
                    </div>
                    <p className="text-zinc-500 leading-snug">High ROAS, low spend. These are your best opportunities — increase budget before competitors notice them.</p>
                </div>
                <div className="rounded-lg border border-green-100 bg-green-50 px-3 py-2.5">
                    <div className="flex items-center gap-1.5 font-semibold text-green-700 mb-0.5">
                        <span className="h-2 w-2 rounded-full bg-green-600" />
                        Scale — keep investing
                    </div>
                    <p className="text-zinc-500 leading-snug">High ROAS, high spend. Already working at scale. Maintain or increase budget while ROAS holds.</p>
                </div>
                <div className="rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-2.5">
                    <div className="flex items-center gap-1.5 font-semibold text-zinc-500 mb-0.5">
                        <span className="h-2 w-2 rounded-full bg-zinc-400" />
                        Ignore — low priority
                    </div>
                    <p className="text-zinc-500 leading-snug">Low ROAS, low spend. Not worth fixing yet — the impact is small. Revisit if spend grows.</p>
                </div>
                <div className="rounded-lg border border-red-100 bg-red-50 px-3 py-2.5">
                    <div className="flex items-center gap-1.5 font-semibold text-red-700 mb-0.5">
                        <span className="h-2 w-2 rounded-full bg-red-600" />
                        Cut / Fix — act now
                    </div>
                    <p className="text-zinc-500 leading-snug">Low ROAS, high spend. Burning budget. Pause and test new creative, or reduce budget while you diagnose.</p>
                </div>
            </div>
            <p className="mt-2 text-center text-[11px] text-zinc-400">Bubble size = attributed revenue · X axis = ad spend · Y axis = {yLabel}</p>
        </div>
    );
});

export { QuadrantChart };
