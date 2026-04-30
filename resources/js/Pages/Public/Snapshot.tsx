import { Head, Link } from '@inertiajs/react';
import { LineChart } from '@/Components/charts/LineChart';
import { MetricCard } from '@/Components/shared/MetricCard';
import { SourceBadge, MetricSource } from '@/Components/shared/SourceBadge';
import { formatCurrency, formatNumber, formatDateOnly } from '@/lib/formatters';
import { buttonVariants } from '@/Components/ui/button';
import { cn } from '@/lib/utils';

// ── Types ─────────────────────────────────────────────────────────────────────

/**
 * Trust bar data — keyed by the 6 canonical Nexstage sources.
 * Canonical set: store · facebook · google · gsc · ga4 · real.
 */
interface TrustBarData {
    store:    number;
    facebook: number | null;
    google:   number | null;
    gsc:      number | null;
    ga4:      number | null;
    real:     number;
}

interface Props {
    token:          string;
    workspace_name: string;
    date_range:     { from: string; to: string; label: string };
    expires_at:     string | null;
    generated_at:   string;
    snapshot_data: {
        revenue:        number;
        orders:         number;
        ad_spend:       number | null;
        mer:            number | null;
        revenue_by_day: Array<{ date: string; revenue: number }>;
        trust_bar:      TrustBarData;
    };
    currency: string;
}

// ── Static filter chip ────────────────────────────────────────────────────────
// Chips are read-only — no close button, no interaction.

function StaticFilterChip({ label, value }: { label: string; value: string }) {
    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-border bg-card px-2.5 py-1 text-xs font-medium text-foreground">
            <span className="text-muted-foreground/70">{label}:</span>
            {value}
        </span>
    );
}

// ── Trust bar ─────────────────────────────────────────────────────────────────
// Six-source read-only strip. Uses shared SourceBadge — no duplicated icon/color logic.
// Static: no click-through in the snapshot context.

const TRUST_SOURCES: Array<{ key: keyof TrustBarData; source: MetricSource }> = [
    { key: 'store',    source: 'store'    },
    { key: 'facebook', source: 'facebook' },
    { key: 'google',   source: 'google'   },
    { key: 'gsc',      source: 'gsc'      },
    { key: 'ga4',      source: 'ga4'      },
    { key: 'real',     source: 'real'     },
];

function TrustBarStatic({ data, currency }: { data: TrustBarData; currency: string }) {
    return (
        <div className="overflow-hidden rounded-xl border border-border bg-card">
            <div className="border-b border-border px-4 py-2.5">
                <span className="text-sm font-semibold uppercase tracking-wide text-muted-foreground/70">
                    Source cross-check
                </span>
            </div>
            <div className="flex flex-wrap gap-4 px-4 py-4">
                {TRUST_SOURCES.map(({ key, source }) => {
                    const val = data[key];
                    return (
                        <div key={key} className="flex flex-col items-center gap-1.5">
                            {/* Active=true so badge shows in its source colour — read-only context */}
                            <SourceBadge source={source} active showLabel />
                            <span className="text-sm font-semibold tabular-nums text-foreground">
                                {val !== null
                                    ? formatCurrency(val, currency)
                                    : <span className="font-normal text-sm text-muted-foreground/50">N/A</span>}
                            </span>
                        </div>
                    );
                })}
            </div>
            <div className="border-t border-border px-4 py-2">
                <p className="text-sm text-muted-foreground/70">
                    "Real" is Nexstage&rsquo;s cross-source reconciliation using your actual store orders.
                    Discrepancies occur due to iOS14+ modeled conversions and attribution overlap.
                </p>
            </div>
        </div>
    );
}

// ── Public snapshot layout ────────────────────────────────────────────────────
// Minimal, unauthenticated shell — no sidebar, no top bar, no auth state.
// Branded header + constrained content column.

function PublicSnapshotLayout({
    workspaceName,
    children,
}: {
    workspaceName: string;
    children: React.ReactNode;
}) {
    return (
        <div className="min-h-screen bg-muted/50">
            {/* Branded header */}
            <header className="border-b border-border bg-card">
                <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-3">
                    <div className="flex items-center gap-2">
                        <span className="text-base font-bold text-foreground tracking-tight">
                            Nexstage
                        </span>
                        <span className="text-muted-foreground/50">/</span>
                        <span className="text-sm text-muted-foreground">{workspaceName}</span>
                    </div>
                    {/* "Snapshot" badge — text-xs is acceptable for a label pill (not body copy) */}
                    <span className="rounded-full border border-border bg-muted/50 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground/70">
                        Snapshot
                    </span>
                </div>
            </header>

            {/* Content */}
            <main className="mx-auto max-w-4xl px-4 py-8">
                {children}
            </main>
        </div>
    );
}

// ── Main page ─────────────────────────────────────────────────────────────────

/**
 * Public read-only snapshot — shared via token, no auth required.
 *
 * All data is frozen at generation time (snapshot_data). No mutations:
 * filters are display-only, chart has no granularity selector, TrustBar
 * has no click-through. MetricCard sources are locked (no badge toggle).
 *
 * Expires gracefully — the controller returns a 404 / expired page
 * when expires_at is in the past; this component only handles rendering.
 *
 * NOTE: PublicSnapshotController.render() currently passes a different prop
 * shape (token, page, url_state, date_range_locked, snapshot_data) rather
 * than what this component expects. Until the controller is updated to pass
 * workspace_name, date_range, generated_at, expires_at, and currency, the
 * page will receive undefined for those props.
 * See NEEDS_ORCHESTRATOR note in the audit report.
 *
 * @see app/Http/Controllers/PublicSnapshotController.php
 * @see app/Services/Workspace/ShareSnapshotTokenService.php
 */
export default function Snapshot({
    workspace_name,
    date_range,
    expires_at,
    generated_at,
    snapshot_data,
    currency,
}: Props) {
    const { revenue, orders, ad_spend, mer, revenue_by_day, trust_bar } = snapshot_data;

    // Normalise revenue_by_day to the shape LineChart expects.
    const chartData = revenue_by_day.map(d => ({ date: d.date, value: d.revenue }));

    const generatedLabel = new Date(generated_at).toLocaleDateString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric',
    });
    const expiresLabel = expires_at
        ? new Date(expires_at).toLocaleDateString(undefined, {
            year: 'numeric', month: 'short', day: 'numeric',
        })
        : null;

    return (
        <PublicSnapshotLayout workspaceName={workspace_name}>
            <Head title={`${workspace_name} — Snapshot ${date_range.label}`} />

            {/* Date range chips — read-only, no close button */}
            <div className="mb-6 flex flex-wrap items-center gap-2">
                <StaticFilterChip label="Period" value={date_range.label} />
                <StaticFilterChip label="From"   value={formatDateOnly(date_range.from)} />
                <StaticFilterChip label="To"     value={formatDateOnly(date_range.to)} />
                <span className="ml-auto text-sm text-muted-foreground/70">
                    Snapshot taken on {generatedLabel}
                    {expiresLabel && <> · Expires {expiresLabel}</>}
                </span>
            </div>

            {/* ── KPI grid ─────────────────────────────────────────────────── */}
            <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                {/* Revenue — Real source: Nexstage cross-source reconciliation */}
                <MetricCard
                    label="Revenue"
                    value={formatCurrency(revenue, currency)}
                    source="real"
                    tooltip="Cross-source reconciled revenue. Based on store orders, not platform pixel attribution."
                />
                <MetricCard
                    label="Orders"
                    value={formatNumber(orders)}
                    source="store"
                />
                <MetricCard
                    label="Ad Spend"
                    value={ad_spend !== null ? formatCurrency(ad_spend, currency) : null}
                    source="real"
                    tooltip="Total ad spend across paid channels for the period."
                />
                {/* MER = revenue ÷ ad spend. Null-safe: UI renders "N/A" per divide-by-zero discipline. */}
                <MetricCard
                    label="MER"
                    value={mer !== null ? `${mer.toFixed(2)}x` : null}
                    source="real"
                    tooltip="Marketing Efficiency Ratio: total revenue ÷ total ad spend."
                />
            </div>

            {/* ── Revenue chart ─────────────────────────────────────────────── */}
            <div className="mb-6 overflow-hidden rounded-xl border border-border bg-card p-4">
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="text-sm font-semibold text-foreground">Revenue over time</h2>
                    <span className="text-sm text-muted-foreground/70">{date_range.label}</span>
                </div>
                {chartData.length > 0 ? (
                    <div className="h-56">
                        <LineChart
                            data={chartData}
                            granularity="daily"
                            currency={currency}
                            valueType="currency"
                            seriesLabel="Revenue"
                            className="h-full w-full"
                        />
                    </div>
                ) : (
                    <div className="flex h-40 items-center justify-center text-sm text-muted-foreground/70">
                        No revenue data for this period
                    </div>
                )}
            </div>

            {/* ── Trust bar ─────────────────────────────────────────────────── */}
            <div className="mb-8">
                <TrustBarStatic data={trust_bar} currency={currency} />
            </div>

            {/* ── "View live data" CTA — gated on signup (Notion/Looker pattern) ── */}
            <div className="mb-8 rounded-xl border border-border bg-card p-6 text-center">
                <p className="mb-1 text-sm font-semibold text-foreground">
                    This is a frozen snapshot.
                </p>
                <p className="mb-4 text-sm text-muted-foreground">
                    Sign up to see live data, explore attribution models, and connect your ad accounts.
                </p>
                <Link href="/register" className={cn(buttonVariants({ size: 'sm' }))}>
                    View live data — free 14-day trial
                </Link>
            </div>

            {/* ── Footer ───────────────────────────────────────────────────── */}
            <footer className="text-center text-sm text-muted-foreground/70 space-y-0.5">
                <p>
                    Powered by{' '}
                    <span className="font-semibold text-muted-foreground">Nexstage</span>
                    {' '}— ecommerce analytics reconciliation
                </p>
            </footer>
        </PublicSnapshotLayout>
    );
}
