/**
 * Integrations — top-level tracking health + connection overview.
 *
 * Sections (per docs/pages/integrations.md):
 *   1. Global alert banners (critical → warning → info stacking order)
 *   2. Tracking Health gauge — hero composite KPI (Elevar Channel Accuracy Report pattern)
 *   3. Missing data warnings — AlertBanner stack
 *   4. Tab strip — Connected | Tracking Health | Historical Imports | Channel Mapping
 *   5. Connected tab: in-page filters + 6 connection cards + sync activity feed (last 50)
 *   6. Tracking Health tab: accuracy cards + error code directory + payload drawer
 *   7. Phased unlock panel — Northbeam day-0/7/30/90 framing
 *   8. Connection deep-dive DrawerSidePanel — OAuth scope, health checks, actions
 *   9. Sync event detail DrawerSidePanel — raw payload JSON (Vercel deployment analog)
 *
 * Competitor patterns used:
 *   - Elevar: Channel Accuracy Report (composite % + grade + breakdown), error code directory
 *     with plain-English remediations and raw JSON payload inspector.
 *   - Vercel: Entity card primitive (logo + name + StatusDot + metadata + kebab menu),
 *     per-check health dots, row-click → right-side drawer.
 *   - Shopify Polaris: account connection pattern, inline warning strip on card for expiring
 *     tokens, in-page filter strip (NOT TopBar).
 *   - Klaviyo: event count + last-event timestamp as minimum card signals, integration tab UX.
 *   - Northbeam: phased-unlock milestone strip.
 *
 * Token rules (hard):
 *   - CSS vars only via Tailwind semantic classes — no hardcoded hex.
 *   - Source colors via CSS custom properties (var(--color-source-*)).
 *   - Font floor: 14px. Body: 15px. WCAG AA enforced.
 *   - No shadows, no gradients, no glass, no gold, no emoji.
 *
 * @see docs/pages/integrations.md
 * @see docs/competitors/elevar.md#key-screens
 * @see docs/competitors/_research_integrations_page.md
 * @see docs/UX.md §5.1 MetricCard, §5.9 StatusDot, §5.37 Entity, §5.31 SubNavTabs
 */

import React, { useState, useMemo } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    CheckCircle2,
    XCircle,
    AlertTriangle,
    PlugZap,
    RefreshCw,
    ChevronRight,
    ExternalLink,
    ShoppingBag,
    Activity,
    Search,
    TrendingUp,
    Megaphone,
    Info,
    X,
    MoreHorizontal,
    Settings,
    Trash2,
    Eye,
    Filter,
    Clock,
    ChevronDown,
} from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { SubNavTabs } from '@/Components/shared/SubNavTabs';
import { KpiGrid } from '@/Components/shared/KpiGrid';
import { MetricCardCompact } from '@/Components/shared/MetricCardCompact';
import { DataTable } from '@/Components/shared/DataTable';
import { StatusDot } from '@/Components/shared/StatusDot';
import { SourceBadge } from '@/Components/shared/SourceBadge';
import { DrawerSidePanel } from '@/Components/shared/DrawerSidePanel';
import { Sparkline } from '@/Components/charts/Sparkline';
import { IntegrationActionsMenu } from '@/Components/shared/IntegrationActionsMenu';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';
import type { StatusType } from '@/Components/shared/StatusDot';
import type { MetricSource } from '@/Components/shared/SourceBadge';
import type { Column } from '@/Components/shared/DataTable';

// ─── Types ────────────────────────────────────────────────────────────────────

interface HealthBreakdownItem {
    name: string;
    score: number;
    weight: number;
}

interface TrackingHealth {
    score: number;
    grade: string;
    sparkline_30d: number[];
    breakdown: HealthBreakdownItem[];
}

interface Summary {
    connected_count: number;
    total_count: number;
    last_sync_at: string | null;
    sync_errors_24h: number;
    orders_attributed: number;
    tag_coverage: number;
}

interface HealthCheck {
    label: string;
    pass: boolean;
    note?: string;
}

interface Integration {
    id: string;
    type: 'shopify' | 'woocommerce' | 'facebook' | 'google' | 'gsc' | 'ga4';
    source_token: MetricSource;
    name: string;
    status: 'healthy' | 'warning' | 'error' | 'not_connected';
    connected_at: string | null;
    last_sync_at: string | null;
    token_expires_at: string | null;
    sync_sparkline_30d: number[];
    health_checks: HealthCheck[];
    account_info: string | null;
    oauth_scope: string | null;
}

interface SyncEvent {
    id: number;
    ts: string;
    integration: string;
    action: string;
    records: number;
    errors: number;
    duration_ms: number;
    status: 'success' | 'partial' | 'error';
    /** Raw JSON payload (Elevar pattern: full event payload inspector). */
    payload?: Record<string, unknown>;
}

interface MissingDataWarning {
    severity: 'info' | 'warning' | 'critical';
    message: string;
    action_label: string | null;
    action_type: string | null;
    integration_id: string;
}

interface ErrorCodeRow {
    id: number;
    code: string;
    destination: string;
    event: string;
    first_seen: string;
    last_seen: string;
    count: number;
    explanation: string;
    /** Raw JSON payloads for payload inspector drawer. */
    sample_payloads?: Record<string, unknown>[];
}

interface PhasedUnlock {
    current_day: number;
    unlocks: Array<{ day: number; feature: string; unlocked: boolean }>;
}

interface Props {
    active_tab: string;
    tracking_health: TrackingHealth;
    summary: Summary;
    integrations: Integration[];
    sync_events: SyncEvent[];
    missing_data_warnings: MissingDataWarning[];
    accuracy: { facebook: number | null; google: number | null; gsc: number | null; ga4: number | null };
    error_codes: ErrorCodeRow[];
    channel_mappings: unknown[];
    import_jobs: unknown[];
    phased_unlock: PhasedUnlock;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatRelative(iso: string | null): string {
    if (!iso) return '—';
    const diff = Date.now() - new Date(iso).getTime();
    const s = Math.floor(diff / 1000);
    if (s < 60) return `${s}s ago`;
    const m = Math.floor(s / 60);
    if (m < 60) return `${m}m ago`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h}h ago`;
    return `${Math.floor(h / 24)}d ago`;
}

function formatAbsolute(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('en-GB', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

function formatMs(ms: number): string {
    if (ms < 1000) return `${ms}ms`;
    return `${(ms / 1000).toFixed(1)}s`;
}

function statusToType(status: Integration['status']): StatusType {
    const map: Record<Integration['status'], StatusType> = {
        healthy: 'success', warning: 'warning', error: 'error', not_connected: 'inactive',
    };
    return map[status];
}

function statusLabel(status: Integration['status']): string {
    return { healthy: 'Healthy', warning: 'Warning', error: 'Failed', not_connected: 'Not connected' }[status];
}

/** Map connector string → canonical MetricSource for SourceBadge. */
function connectorToSource(connector: string): MetricSource {
    const map: Record<string, MetricSource> = {
        shopify: 'store', woocommerce: 'store', store: 'store',
        facebook: 'facebook',
        google: 'google', google_ads: 'google',
        gsc: 'gsc',
        ga4: 'ga4',
    };
    return map[connector] ?? 'real';
}

/**
 * CSS custom property name for source brand color.
 * Used via inline style so no hardcoded hex values appear in the component.
 * @see app.css --color-source-* tokens
 */
function sourceCssVar(source: MetricSource): string {
    return `var(--color-source-${source})`;
}

function sourceBorderCssVar(source: MetricSource): string {
    return `var(--color-source-${source}-border)`;
}

const MILESTONE_DAYS = [0, 7, 30, 90] as const;

// Integration type → display name for CTA labelling (GA4 must say "GA4" per memory note)
const CONNECT_LABEL: Record<Integration['type'], string> = {
    shopify:     'Connect Shopify',
    woocommerce: 'Connect WooCommerce',
    facebook:    'Connect Facebook Ads',
    google:      'Connect Google Ads',
    gsc:         'Connect Search Console',
    ga4:         'Connect GA4',
};

// ─── Integration logos ────────────────────────────────────────────────────────

function IntegrationLogo({ type, size = 40 }: { type: Integration['type']; size?: number }) {
    const dim = `${size}px`;
    // Colors use Tailwind classes (not inline hex) to stay within CSS-vars constraint.
    const configs: Record<Integration['type'], { wrapClass: string; iconClass: string }> = {
        shopify:     { wrapClass: 'bg-slate-100',   iconClass: 'text-slate-600' },
        woocommerce: { wrapClass: 'bg-purple-100',  iconClass: 'text-purple-600' },
        facebook:    { wrapClass: 'bg-blue-100',    iconClass: 'text-blue-600' },
        google:      { wrapClass: 'bg-teal-100',    iconClass: 'text-teal-600' },
        gsc:         { wrapClass: 'bg-emerald-100', iconClass: 'text-emerald-600' },
        ga4:         { wrapClass: 'bg-orange-100',  iconClass: 'text-orange-600' },
    };
    const iconByType: Record<Integration['type'], React.ReactNode> = {
        shopify:     <ShoppingBag style={{ width: size * 0.5, height: size * 0.5 }} />,
        woocommerce: <ShoppingBag style={{ width: size * 0.5, height: size * 0.5 }} />,
        facebook:    <Megaphone   style={{ width: size * 0.5, height: size * 0.5 }} />,
        google:      <Search      style={{ width: size * 0.5, height: size * 0.5 }} />,
        gsc:         <TrendingUp  style={{ width: size * 0.5, height: size * 0.5 }} />,
        ga4:         <Activity    style={{ width: size * 0.5, height: size * 0.5 }} />,
    };
    const { wrapClass, iconClass } = configs[type];
    return (
        <div
            className={`flex shrink-0 items-center justify-center rounded-lg ${wrapClass} ${iconClass}`}
            style={{ width: dim, height: dim }}
        >
            {iconByType[type]}
        </div>
    );
}

// ─── Tracking Health Gauge ─────────────────────────────────────────────────────
// Elevar Channel Accuracy Report pattern: single composite % + grade + weighted breakdown.
// Colors: success ≥90, warning 75–89, danger <75 — all via CSS vars.

function TrackingHealthGauge({ health }: { health: TrackingHealth }) {
    const { score, grade, sparkline_30d, breakdown } = health;

    // Score band → semantic CSS var (no hardcoded hex)
    const scoreCssColor =
        score >= 90 ? 'var(--color-success)' :
        score >= 75 ? 'var(--color-warning)' :
        'var(--color-danger)';

    const gradeTextClass =
        score >= 90 ? 'text-emerald-700' :
        score >= 75 ? 'text-amber-700' :
        'text-rose-700';

    const sparkData = sparkline_30d.map((v) => ({ value: v }));
    const sparkMin = Math.min(...sparkline_30d);
    const sparkMax = Math.max(...sparkline_30d);

    return (
        <div className="rounded-xl border border-border bg-card overflow-hidden">
            {/* Three-panel layout: score | sparkline | breakdown */}
            <div className="flex flex-col sm:flex-row sm:items-stretch">

                {/* Left — circular gauge with score + grade */}
                <div className="flex flex-col items-center justify-center gap-1.5 px-8 py-6 sm:w-56 sm:border-r sm:border-border">
                    <span className="text-xs font-semibold text-muted-foreground uppercase tracking-widest">
                        Tracking Health
                    </span>
                    <div className="relative flex items-center justify-center" style={{ width: 104, height: 104 }}>
                        <svg width={104} height={104} viewBox="0 0 104 104" className="-rotate-90" aria-hidden="true">
                            <circle cx={52} cy={52} r={44} fill="none" stroke="var(--color-border)" strokeWidth={8} />
                            <circle
                                cx={52} cy={52} r={44}
                                fill="none"
                                stroke={scoreCssColor}
                                strokeWidth={8}
                                strokeLinecap="round"
                                strokeDasharray={`${2 * Math.PI * 44}`}
                                strokeDashoffset={`${2 * Math.PI * 44 * (1 - score / 100)}`}
                                style={{ transition: 'stroke-dashoffset 0.6s ease' }}
                            />
                        </svg>
                        <div className="absolute inset-0 flex flex-col items-center justify-center">
                            <span className="text-3xl font-semibold tabular-nums text-foreground leading-none">
                                {score}
                            </span>
                            <span className={`text-sm font-bold mt-0.5 ${gradeTextClass}`}>{grade}</span>
                        </div>
                    </div>
                    <span className="text-xs text-muted-foreground">out of 100</span>
                </div>

                {/* Middle — 30d sparkline (Elevar rolling-window trend) */}
                <div className="flex flex-col justify-between px-6 py-5 flex-1 border-t sm:border-t-0 sm:border-r border-border">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-xs font-semibold text-muted-foreground uppercase tracking-widest">
                            30-day trend
                        </span>
                        <span className="text-xs text-muted-foreground tabular-nums">
                            {sparkMin}–{sparkMax} range
                        </span>
                    </div>
                    <div className="flex-1" style={{ minHeight: 56 }}>
                        <Sparkline
                            data={sparkData}
                            color={scoreCssColor}
                            height={56}
                            mode="area"
                            className="w-full"
                        />
                    </div>
                </div>

                {/* Right — weighted score breakdown */}
                <div className="flex flex-col justify-center px-6 py-5 sm:w-80 border-t sm:border-t-0 border-border">
                    <span className="text-xs font-semibold text-muted-foreground uppercase tracking-widest mb-3">
                        Score breakdown
                    </span>
                    <ul className="space-y-2.5">
                        {breakdown.map((item) => {
                            const pct = item.score;
                            const barColor =
                                pct >= 90 ? 'var(--color-success)' :
                                pct >= 75 ? 'var(--color-warning)' :
                                'var(--color-danger)';
                            return (
                                <li key={item.name} className="flex items-center gap-3">
                                    <span className="min-w-0 flex-1 truncate text-sm text-foreground" title={item.name}>
                                        {item.name}
                                    </span>
                                    <div className="w-20 h-1.5 rounded-full bg-muted shrink-0">
                                        <div
                                            className="h-1.5 rounded-full transition-all"
                                            style={{ width: `${pct}%`, backgroundColor: barColor }}
                                        />
                                    </div>
                                    <span className="w-8 text-right text-sm tabular-nums font-semibold text-foreground shrink-0">
                                        {pct}
                                    </span>
                                    <span className="w-10 text-right text-xs text-muted-foreground shrink-0">
                                        ×{item.weight}%
                                    </span>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </div>

            {/* Bottom info bar */}
            <div className="flex items-center gap-2 border-t border-border bg-muted/40 px-6 py-2.5">
                <Info className="h-3.5 w-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                <p className="text-xs text-muted-foreground">
                    Composite of order attribution coverage, ad conversion data, UTM coverage, sync recency, and tag coverage.
                    Weighted average — lower-weight signals amplify high-weight failures. Recomputed daily.
                </p>
            </div>
        </div>
    );
}

// ─── Missing data warnings ────────────────────────────────────────────────────
// Polaris: critical → warning → info stacking. Info is dismissable per-session.

function MissingDataWarnings({ warnings }: { warnings: MissingDataWarning[] }) {
    const [dismissed, setDismissed] = useState<Set<number>>(new Set());

    // Sort: critical first, then warning, then info (Polaris stacking order)
    const sorted = [...warnings].sort((a, b) => {
        const rank = { critical: 0, warning: 1, info: 2 } as const;
        return rank[a.severity] - rank[b.severity];
    });

    const visible = sorted.filter((_, i) => !dismissed.has(i));
    if (visible.length === 0) return null;

    const styleMap: Record<MissingDataWarning['severity'], string> = {
        info:     'bg-blue-50 border-blue-200 text-blue-800',
        warning:  'bg-amber-50 border-amber-200 text-amber-900',
        critical: 'bg-red-50 border-red-200 text-red-900',
    };
    const IconMap = {
        info: Info,
        warning: AlertTriangle,
        critical: XCircle,
    };

    return (
        <div className="space-y-2" role="region" aria-label="Data quality warnings">
            {sorted.map((w, i) => {
                if (dismissed.has(i)) return null;
                const Icon = IconMap[w.severity];
                return (
                    <div
                        key={i}
                        className={`flex items-start gap-3 rounded-lg border px-4 py-3 text-sm ${styleMap[w.severity]}`}
                        role="alert"
                    >
                        <Icon className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                        <p className="flex-1">{w.message}</p>
                        {w.action_label && (
                            <button className="shrink-0 font-semibold underline underline-offset-2 text-sm hover:opacity-80 transition-opacity">
                                {w.action_label}
                            </button>
                        )}
                        {/* Only info severity is dismissable per Polaris convention */}
                        {w.severity === 'info' && (
                            <button
                                onClick={() => setDismissed((prev) => new Set([...prev, i]))}
                                className="shrink-0 rounded p-0.5 hover:opacity-70 transition-opacity"
                                aria-label="Dismiss"
                            >
                                <X className="h-3.5 w-3.5" aria-hidden="true" />
                            </button>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

// ─── In-page filter bar ───────────────────────────────────────────────────────
// Per memory rule: page-level filters go in-content, NOT in TopBar.
// Shopify Polaris account-connection pattern: filter strip contextually placed.

interface FilterState {
    integration: string;
    status: string;
    timeRange: string;
}

function InPageFilterBar({
    integrations,
    filter,
    onChange,
}: {
    integrations: Integration[];
    filter: FilterState;
    onChange: (f: FilterState) => void;
}) {
    const selectClass =
        'flex items-center gap-1.5 rounded-md border border-border bg-card px-3 py-1.5 text-sm text-foreground hover:bg-muted/60 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-ring';

    return (
        <div className="flex flex-wrap items-center gap-2">
            <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                <Filter className="h-3.5 w-3.5" aria-hidden="true" />
                Filter:
            </span>

            {/* Integration filter */}
            <div className="relative">
                <select
                    value={filter.integration}
                    onChange={(e) => onChange({ ...filter, integration: e.target.value })}
                    className={`${selectClass} pr-7 appearance-none`}
                    aria-label="Filter by integration"
                >
                    <option value="">All integrations</option>
                    {integrations.map((i) => (
                        <option key={i.id} value={i.id}>{i.name.split(' — ')[0]}</option>
                    ))}
                </select>
                <ChevronDown className="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" aria-hidden="true" />
            </div>

            {/* Status filter */}
            <div className="relative">
                <select
                    value={filter.status}
                    onChange={(e) => onChange({ ...filter, status: e.target.value })}
                    className={`${selectClass} pr-7 appearance-none`}
                    aria-label="Filter by status"
                >
                    <option value="">All statuses</option>
                    <option value="healthy">Healthy</option>
                    <option value="warning">Warning</option>
                    <option value="error">Failed</option>
                    <option value="not_connected">Not connected</option>
                </select>
                <ChevronDown className="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" aria-hidden="true" />
            </div>

            {/* Time range filter (for sync feed context) */}
            <div className="relative">
                <select
                    value={filter.timeRange}
                    onChange={(e) => onChange({ ...filter, timeRange: e.target.value })}
                    className={`${selectClass} pr-7 appearance-none`}
                    aria-label="Sync activity time range"
                >
                    <option value="24h">Last 24h</option>
                    <option value="7d">Last 7d</option>
                    <option value="30d">Last 30d</option>
                </select>
                <ChevronDown className="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" aria-hidden="true" />
            </div>

            {/* Clear filters */}
            {(filter.integration || filter.status || filter.timeRange !== '24h') && (
                <button
                    onClick={() => onChange({ integration: '', status: '', timeRange: '24h' })}
                    className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors"
                >
                    <X className="h-3.5 w-3.5" aria-hidden="true" />
                    Clear
                </button>
            )}
        </div>
    );
}

// ─── Connection Card ──────────────────────────────────────────────────────────
// Vercel Entity pattern + Elevar per-check health dots + Polaris action menu.
// GA4 card uses explicit "Connect GA4" CTA label (per memory: GA4 is a first-class source).

function ConnectionCard({
    integration,
    onViewDetails,
}: {
    integration: Integration;
    onViewDetails: (id: string) => void;
}) {
    const isConnected = integration.status !== 'not_connected';
    const hasError = integration.status === 'error';
    const hasWarning = integration.status === 'warning';
    const hasExpiringToken = integration.token_expires_at !== null;

    // Card border tinted by source brand color when connected (CSS var, not hex)
    const cardBorderStyle: React.CSSProperties = isConnected
        ? { borderColor: sourceBorderCssVar(integration.source_token) }
        : {};

    const sparkData = integration.sync_sparkline_30d.map((v) => ({ value: v }));
    const hasSparkData = sparkData.length > 1 && sparkData.some((d) => d.value > 0);

    // Kebab (···) action items for IntegrationActionsMenu — Vercel/Polaris pattern.
    // Destructive actions (Disconnect) hidden behind separator for safety.
    const menuItems = [
        {
            label: 'View details',
            icon: <Eye className="h-4 w-4" />,
            onClick: () => onViewDetails(integration.id),
        },
        {
            label: 'Sync now',
            icon: <RefreshCw className="h-4 w-4" />,
            onClick: () => { /* POST /integrations/.../sync — wired at route level */ },
        },
        {
            label: 'Open in platform',
            icon: <ExternalLink className="h-4 w-4" />,
            onClick: () => { /* Open platform URL */ },
        },
        {
            label: 'Configure',
            icon: <Settings className="h-4 w-4" />,
            onClick: () => onViewDetails(integration.id),
        },
        {
            label: 'Disconnect',
            icon: <Trash2 className="h-4 w-4" />,
            onClick: () => { /* DELETE /integrations/.../disconnect — optimistic with undo toast */ },
            variant: 'destructive' as const,
            separator: true,
        },
    ];

    return (
        <div
            className="flex flex-col rounded-xl border bg-card overflow-hidden transition-colors"
            style={isConnected ? cardBorderStyle : {}}
        >
            {/* Card header: logo | name + status | action menu */}
            <div className="flex items-start gap-3 p-4 pb-3">
                <IntegrationLogo type={integration.type} size={36} />
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-foreground truncate" title={integration.name}>
                        {integration.name}
                    </p>
                    <div className="mt-0.5 flex items-center gap-2">
                        <StatusDot status={statusToType(integration.status)} label={statusLabel(integration.status)} />
                    </div>
                </div>
                {/* Kebab menu (Vercel Entity trailing-action pattern) */}
                {isConnected && (
                    <IntegrationActionsMenu items={menuItems} />
                )}
                {/* ChevronRight for non-connected cards — click to view details */}
                {!isConnected && (
                    <button
                        onClick={() => onViewDetails(integration.id)}
                        className="shrink-0 rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                        aria-label="View details"
                    >
                        <ChevronRight className="h-4 w-4" aria-hidden="true" />
                    </button>
                )}
            </div>

            {/* Token expiry inline warning strip (Polaris account-connection pattern) */}
            {isConnected && hasExpiringToken && (
                <div className="mx-4 mb-2 flex items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
                    <AlertTriangle className="h-3.5 w-3.5 shrink-0 text-amber-600" aria-hidden="true" />
                    <span className="text-xs text-amber-800">
                        Token expires {formatRelative(integration.token_expires_at)} — reconnect to avoid data gaps.
                    </span>
                </div>
            )}

            {/* 30d sync sparkline — Klaviyo: event count + last-event timestamp minimum signals */}
            {isConnected && hasSparkData && (
                <div className="px-4 pb-2">
                    <div className="flex items-center justify-between mb-1">
                        <span className="text-xs text-muted-foreground">Sync activity (30d)</span>
                        <span className="text-xs text-muted-foreground tabular-nums flex items-center gap-1">
                            <Clock className="h-3 w-3" aria-hidden="true" />
                            {formatRelative(integration.last_sync_at)}
                        </span>
                    </div>
                    <Sparkline
                        data={sparkData}
                        color={sourceCssVar(integration.source_token)}
                        height={28}
                        mode="area"
                        className="w-full"
                    />
                </div>
            )}

            {/* Health checks (Elevar per-check dots pattern: 3–5 named checks) */}
            {isConnected && integration.health_checks.length > 0 && (
                <div className="border-t border-border/60 px-4 py-3 space-y-1.5">
                    {integration.health_checks.map((check) => (
                        <div key={check.label} className="flex items-start gap-2">
                            {check.pass ? (
                                <CheckCircle2 className="h-3.5 w-3.5 shrink-0 text-emerald-600 mt-0.5" aria-hidden="true" />
                            ) : (
                                <XCircle className="h-3.5 w-3.5 shrink-0 text-rose-600 mt-0.5" aria-hidden="true" />
                            )}
                            <span className="text-sm text-foreground/80">
                                {check.label}
                                {check.note && (
                                    <span className={`ml-1 font-medium text-sm ${check.pass ? 'text-muted-foreground' : 'text-amber-700'}`}>
                                        — {check.note}
                                    </span>
                                )}
                            </span>
                        </div>
                    ))}
                </div>
            )}

            {/* Account info row */}
            {isConnected && integration.account_info && (
                <div className="border-t border-border/60 px-4 py-2">
                    <span className="text-xs text-muted-foreground">{integration.account_info}</span>
                </div>
            )}

            {/* Not-connected empty state */}
            {!isConnected && (
                <div className="flex flex-col items-center justify-center gap-3 flex-1 px-4 py-6 text-center">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                        <PlugZap className="h-5 w-5 text-muted-foreground" aria-hidden="true" />
                    </div>
                    <div>
                        <p className="text-sm font-medium text-foreground">Not connected</p>
                        <p className="text-sm text-muted-foreground mt-0.5">Connect to unlock this source</p>
                    </div>
                </div>
            )}

            {/* Footer actions — mt-auto keeps cards uniform height */}
            <div className="flex items-center justify-end gap-2 border-t border-border/60 px-4 py-3 mt-auto">
                {/* Primary CTA for not_connected: explicit label per integration type */}
                {!isConnected && (
                    <button className="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-sm font-semibold text-primary-foreground hover:opacity-90 transition-opacity">
                        <PlugZap className="h-3.5 w-3.5" aria-hidden="true" />
                        {CONNECT_LABEL[integration.type]}
                    </button>
                )}

                {/* Reconnect CTA for error or expiring token */}
                {(hasError || (hasWarning && hasExpiringToken)) && (
                    <button className="inline-flex items-center gap-1.5 rounded-md border border-amber-200 bg-amber-50 px-3 py-1.5 text-sm font-semibold text-amber-800 hover:bg-amber-100 transition-colors">
                        <RefreshCw className="h-3.5 w-3.5" aria-hidden="true" />
                        Reconnect
                    </button>
                )}

                {/* View details CTA for connected integrations */}
                {isConnected && (
                    <button
                        onClick={() => onViewDetails(integration.id)}
                        className="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-sm font-medium text-foreground hover:bg-muted/60 transition-colors"
                    >
                        View details
                    </button>
                )}
            </div>
        </div>
    );
}

// ─── Connection Detail Drawer ─────────────────────────────────────────────────
// Vercel: row-click → DrawerSidePanel. Elevar: OAuth scope, account info, error log, manual sync.

function ConnectionDetailDrawer({
    integration,
    onClose,
}: {
    integration: Integration | null;
    onClose: () => void;
}) {
    if (!integration) return null;

    const isConnected = integration.status !== 'not_connected';
    const passCount = integration.health_checks.filter((c) => c.pass).length;
    const failCount = integration.health_checks.filter((c) => !c.pass).length;

    return (
        <DrawerSidePanel
            open={true}
            onClose={onClose}
            title={integration.name}
            subtitle={
                <div className="flex items-center gap-2 mt-0.5">
                    <SourceBadge source={integration.source_token} active showLabel />
                    <StatusDot status={statusToType(integration.status)} label={statusLabel(integration.status)} />
                </div>
            }
            width={480}
        >
            <div className="space-y-6 px-5 py-5">
                {/* Summary row: connected since + last sync */}
                <div className="grid grid-cols-2 gap-4">
                    <div className="rounded-lg border border-border bg-muted/40 p-3">
                        <p className="text-xs text-muted-foreground mb-1">Connected since</p>
                        <p className="text-sm font-semibold text-foreground">
                            {integration.connected_at ? formatAbsolute(integration.connected_at) : '—'}
                        </p>
                    </div>
                    <div className="rounded-lg border border-border bg-muted/40 p-3">
                        <p className="text-xs text-muted-foreground mb-1">Last sync</p>
                        <p className="text-sm font-semibold text-foreground">
                            {formatRelative(integration.last_sync_at)}
                        </p>
                    </div>
                    {integration.token_expires_at && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 col-span-2">
                            <p className="text-xs text-amber-700 mb-1">Token expires</p>
                            <p className="text-sm font-semibold text-amber-900">
                                {formatAbsolute(integration.token_expires_at)}
                            </p>
                        </div>
                    )}
                </div>

                {/* Health checks */}
                {integration.health_checks.length > 0 && (
                    <div>
                        <div className="flex items-center justify-between mb-3">
                            <p className="text-sm font-semibold text-foreground">Health checks</p>
                            <div className="flex items-center gap-3 text-xs">
                                <span className="text-emerald-700 font-semibold">{passCount} passing</span>
                                {failCount > 0 && (
                                    <span className="text-rose-700 font-semibold">{failCount} failing</span>
                                )}
                            </div>
                        </div>
                        <ul className="space-y-2">
                            {integration.health_checks.map((check) => (
                                <li
                                    key={check.label}
                                    className={`flex items-start gap-3 rounded-lg px-3 py-2.5 ${
                                        check.pass ? 'bg-emerald-50' : 'bg-rose-50'
                                    }`}
                                >
                                    {check.pass ? (
                                        <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600 mt-0.5" aria-hidden="true" />
                                    ) : (
                                        <XCircle className="h-4 w-4 shrink-0 text-rose-600 mt-0.5" aria-hidden="true" />
                                    )}
                                    <div>
                                        <p className={`text-sm font-medium ${check.pass ? 'text-emerald-900' : 'text-rose-900'}`}>
                                            {check.label}
                                        </p>
                                        {check.note && (
                                            <p className={`text-xs mt-0.5 ${check.pass ? 'text-emerald-700' : 'text-rose-700'}`}>
                                                {check.note}
                                            </p>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* Account info */}
                {integration.account_info && (
                    <div>
                        <p className="text-sm font-semibold text-foreground mb-2">Account</p>
                        <p className="text-sm text-foreground/70 bg-muted/40 border border-border rounded-lg px-3 py-2.5">
                            {integration.account_info}
                        </p>
                    </div>
                )}

                {/* OAuth scopes (monospace — Geist Mono equivalent via JetBrains Mono) */}
                {integration.oauth_scope && (
                    <div>
                        <p className="text-sm font-semibold text-foreground mb-2">OAuth scopes</p>
                        <p className="font-mono text-xs text-foreground/70 bg-muted/40 border border-border rounded-lg px-3 py-2.5 break-all">
                            {integration.oauth_scope}
                        </p>
                    </div>
                )}

                {/* Sync settings (placeholder for v2 sync interval config) */}
                <div className="rounded-lg border border-border bg-muted/20 px-3 py-3">
                    <p className="text-xs font-semibold text-muted-foreground uppercase tracking-widest mb-1.5">
                        Sync schedule
                    </p>
                    <p className="text-sm text-foreground">Every 15 minutes (automatic)</p>
                </div>

                {/* Actions */}
                <div className="flex flex-col gap-2 pt-2 border-t border-border">
                    {isConnected && (
                        <>
                            <button className="inline-flex items-center gap-2 rounded-md border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted/60 transition-colors">
                                <RefreshCw className="h-4 w-4" aria-hidden="true" />
                                Sync now
                            </button>
                            <button className="inline-flex items-center gap-2 rounded-md border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted/60 transition-colors">
                                <ExternalLink className="h-4 w-4" aria-hidden="true" />
                                Open in platform
                            </button>
                            <button className="inline-flex items-center gap-2 rounded-md border border-amber-200 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50 transition-colors">
                                <RefreshCw className="h-4 w-4" aria-hidden="true" />
                                Reconnect
                            </button>
                            <button className="inline-flex items-center gap-2 rounded-md border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 transition-colors">
                                <Trash2 className="h-4 w-4" aria-hidden="true" />
                                Disconnect
                            </button>
                        </>
                    )}
                    {!isConnected && (
                        <button className="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90 transition-opacity">
                            <PlugZap className="h-4 w-4" aria-hidden="true" />
                            {CONNECT_LABEL[integration.type]}
                        </button>
                    )}
                </div>
            </div>
        </DrawerSidePanel>
    );
}

// ─── Sync Event Detail Drawer ─────────────────────────────────────────────────
// Elevar event payload inspector: raw JSON of last payloads sent to a destination.
// Vercel deployment-detail analog: row-click → right-side drawer.

function SyncEventDrawer({
    event,
    onClose,
}: {
    event: SyncEvent | null;
    onClose: () => void;
}) {
    const [copied, setCopied] = useState(false);

    if (!event) return null;

    const payload = event.payload ?? {
        id: event.id,
        ts: event.ts,
        integration: event.integration,
        action: event.action,
        records: event.records,
        errors: event.errors,
        duration_ms: event.duration_ms,
        status: event.status,
    };

    const payloadStr = JSON.stringify(payload, null, 2);

    function copyPayload() {
        void navigator.clipboard.writeText(payloadStr);
        setCopied(true);
        setTimeout(() => setCopied(false), 1500);
    }

    const SYNC_STATUS_DOT: Record<SyncEvent['status'], StatusType> = {
        success: 'success', partial: 'warning', error: 'error',
    };

    return (
        <DrawerSidePanel
            open={true}
            onClose={onClose}
            title={`Sync event #${event.id}`}
            subtitle={
                <div className="flex items-center gap-2 mt-0.5">
                    <SourceBadge source={connectorToSource(event.integration)} active showLabel />
                    <StatusDot status={SYNC_STATUS_DOT[event.status]} label={event.status} />
                </div>
            }
            width={520}
        >
            <div className="space-y-5 px-5 py-5">
                {/* Metadata grid */}
                <div className="grid grid-cols-2 gap-3">
                    {[
                        { label: 'Time',       value: formatAbsolute(event.ts) },
                        { label: 'Action',     value: event.action },
                        { label: 'Records',    value: event.records.toLocaleString() },
                        { label: 'Errors',     value: event.errors > 0 ? String(event.errors) : '—' },
                        { label: 'Duration',   value: formatMs(event.duration_ms) },
                        { label: 'Integration',value: event.integration },
                    ].map(({ label, value }) => (
                        <div key={label} className="rounded-lg border border-border bg-muted/30 px-3 py-2.5">
                            <p className="text-xs text-muted-foreground mb-0.5">{label}</p>
                            <p className="text-sm font-medium text-foreground font-mono">{value}</p>
                        </div>
                    ))}
                </div>

                {/* JSON payload inspector (Elevar event payload inspector pattern) */}
                <div>
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm font-semibold text-foreground">Event payload</p>
                        <button
                            onClick={copyPayload}
                            className="text-xs text-muted-foreground hover:text-foreground transition-colors font-medium"
                        >
                            {copied ? 'Copied!' : 'Copy payload'}
                        </button>
                    </div>
                    <pre className="font-mono text-xs text-foreground/80 bg-muted/40 border border-border rounded-lg p-4 overflow-x-auto max-h-96 leading-relaxed">
                        {payloadStr}
                    </pre>
                </div>
            </div>
        </DrawerSidePanel>
    );
}

// ─── Error Code Payload Drawer ─────────────────────────────────────────────────
// Elevar error code directory row-click: last 5 raw JSON payloads for the error.

function ErrorPayloadDrawer({
    error,
    onClose,
}: {
    error: ErrorCodeRow | null;
    onClose: () => void;
}) {
    const [copied, setCopied] = useState<number | null>(null);

    if (!error) return null;

    const payloads = error.sample_payloads ?? [
        { error_code: error.code, destination: error.destination, event: error.event, explanation: error.explanation, sample: true },
    ];

    function copyPayload(i: number) {
        void navigator.clipboard.writeText(JSON.stringify(payloads[i], null, 2));
        setCopied(i);
        setTimeout(() => setCopied(null), 1500);
    }

    return (
        <DrawerSidePanel
            open={true}
            onClose={onClose}
            title={`Error ${error.code}`}
            subtitle={
                <div className="flex items-center gap-2 mt-0.5">
                    <SourceBadge source={connectorToSource(error.destination)} active showLabel />
                    <span className="text-xs text-muted-foreground">{error.event}</span>
                </div>
            }
            width={560}
        >
            <div className="space-y-5 px-5 py-5">
                {/* Explanation — plain English (Elevar's differentiator) */}
                <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                    <p className="text-xs font-semibold text-amber-700 uppercase tracking-widest mb-1.5">
                        What went wrong
                    </p>
                    <p className="text-sm text-amber-900 leading-relaxed">{error.explanation}</p>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-3 gap-3">
                    {[
                        { label: 'Count (7d)', value: error.count.toLocaleString() },
                        { label: 'First seen',  value: formatRelative(error.first_seen) },
                        { label: 'Last seen',   value: formatRelative(error.last_seen) },
                    ].map(({ label, value }) => (
                        <div key={label} className="rounded-lg border border-border bg-muted/30 px-3 py-2.5">
                            <p className="text-xs text-muted-foreground mb-0.5">{label}</p>
                            <p className="text-sm font-semibold text-foreground tabular-nums">{value}</p>
                        </div>
                    ))}
                </div>

                {/* Raw JSON payloads (up to 5 — Elevar event payload inspector pattern) */}
                <div>
                    <p className="text-sm font-semibold text-foreground mb-3">
                        Sample payloads ({payloads.length})
                    </p>
                    <div className="space-y-3">
                        {payloads.map((payload, i) => (
                            <div key={i} className="rounded-lg border border-border overflow-hidden">
                                <div className="flex items-center justify-between px-3 py-2 bg-muted/40 border-b border-border">
                                    <span className="text-xs text-muted-foreground font-mono">Payload #{i + 1}</span>
                                    <button
                                        onClick={() => copyPayload(i)}
                                        className="text-xs text-muted-foreground hover:text-foreground transition-colors"
                                    >
                                        {copied === i ? 'Copied!' : 'Copy'}
                                    </button>
                                </div>
                                <pre className="font-mono text-xs text-foreground/80 p-3 overflow-x-auto max-h-48 leading-relaxed">
                                    {JSON.stringify(payload, null, 2)}
                                </pre>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </DrawerSidePanel>
    );
}

// ─── Sync Activity Table ──────────────────────────────────────────────────────
// Last 50 events — Vercel deployment list analog. Row click → SyncEventDrawer.

const SYNC_STATUS_DOT: Record<SyncEvent['status'], StatusType> = {
    success: 'success', partial: 'warning', error: 'error',
};

function buildSyncColumns(onRowClick: (row: SyncEvent) => void): Column<SyncEvent>[] {
    return [
        {
            key: 'ts',
            header: 'Time',
            sortable: true,
            render: (v) => (
                <span className="text-sm text-muted-foreground tabular-nums whitespace-nowrap">
                    {formatRelative(String(v))}
                </span>
            ),
        },
        {
            key: 'integration',
            header: 'Source',
            render: (v) => <SourceBadge source={connectorToSource(String(v))} active showLabel />,
        },
        {
            key: 'action',
            header: 'Action',
            render: (v) => (
                <code className="rounded bg-muted px-1.5 py-0.5 text-xs font-mono text-foreground/80">
                    {String(v)}
                </code>
            ),
        },
        {
            key: 'records',
            header: 'Records',
            sortable: true,
            render: (v) => (
                <span className="text-sm tabular-nums text-foreground">{Number(v).toLocaleString()}</span>
            ),
        },
        {
            key: 'errors',
            header: 'Errors',
            sortable: true,
            render: (v) => (
                <span className={`text-sm tabular-nums font-semibold ${Number(v) > 0 ? 'text-rose-600' : 'text-muted-foreground'}`}>
                    {Number(v) > 0 ? Number(v) : '—'}
                </span>
            ),
        },
        {
            key: 'duration_ms',
            header: 'Duration',
            sortable: true,
            render: (v) => (
                <span className="text-sm tabular-nums text-muted-foreground">{formatMs(Number(v))}</span>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (v) => (
                <StatusDot status={SYNC_STATUS_DOT[v as SyncEvent['status']]} label={String(v)} />
            ),
        },
    ];
}

// ─── Accuracy Cards ───────────────────────────────────────────────────────────
// Elevar Channel Accuracy Report: per-destination % + sparkline + source badge.

function AccuracyCard({
    label,
    value,
    source,
    tooltip,
}: {
    label: string;
    value: number | null;
    source: MetricSource;
    tooltip?: string;
}) {
    const color =
        value === null ? 'text-muted-foreground' :
        value >= 95 ? 'text-emerald-700' :
        value >= 80 ? 'text-amber-700' :
        'text-rose-700';

    return (
        <div className="rounded-xl border border-border bg-card p-4 flex flex-col gap-2" title={tooltip}>
            <div className="flex items-center justify-between">
                <span className="text-xs font-semibold text-muted-foreground uppercase tracking-widest">{label}</span>
                <SourceBadge source={source} active showLabel={false} />
            </div>
            <span className={`text-3xl font-semibold tabular-nums ${color}`}>
                {value !== null ? `${value.toFixed(1)}%` : 'N/A'}
            </span>
            {tooltip && <p className="text-xs text-muted-foreground">{tooltip}</p>}
        </div>
    );
}

// ─── Error Code Table ─────────────────────────────────────────────────────────
// Elevar Error Code Directory: code | destination | event | count | last seen | explanation.

function buildErrorCodeCols(onRowClick: (row: ErrorCodeRow) => void): Column<ErrorCodeRow>[] {
    return [
        {
            key: 'code',
            header: 'Code',
            render: (v) => (
                <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-xs text-foreground/80">
                    {String(v)}
                </code>
            ),
        },
        {
            key: 'destination',
            header: 'Destination',
            render: (v) => <SourceBadge source={connectorToSource(String(v))} active showLabel />,
        },
        {
            key: 'event',
            header: 'Event',
            render: (v) => <span className="text-sm text-foreground">{String(v)}</span>,
        },
        {
            key: 'count',
            header: 'Count (7d)',
            sortable: true,
            render: (v) => (
                <span className="text-sm font-semibold tabular-nums text-rose-600">
                    {Number(v).toLocaleString()}
                </span>
            ),
        },
        {
            key: 'last_seen',
            header: 'Last seen',
            sortable: true,
            render: (v) => (
                <span className="text-xs text-muted-foreground whitespace-nowrap">
                    {formatRelative(String(v))}
                </span>
            ),
        },
        {
            key: 'explanation',
            header: 'Explanation',
            render: (v) => (
                <span
                    className="block max-w-xs truncate text-sm text-muted-foreground"
                    title={String(v)}
                >
                    {String(v)}
                </span>
            ),
        },
    ];
}

// ─── Phased Unlock Panel ──────────────────────────────────────────────────────
// Northbeam day-0/7/30/90 pattern: milestone strip + per-feature unlock list.

function PhasedUnlockPanel({ current_day, unlocks }: PhasedUnlock) {
    const total = MILESTONE_DAYS[MILESTONE_DAYS.length - 1];
    const clampedDay = Math.min(current_day, total);
    const pct = (clampedDay / total) * 100;

    return (
        <div className="rounded-xl border border-border bg-card p-5">
            <p className="mb-4 text-sm font-semibold text-foreground">Feature milestones</p>
            <div className="relative mb-8">
                <div className="h-1.5 w-full rounded-full bg-muted">
                    <div
                        className="h-1.5 rounded-full bg-primary transition-all"
                        style={{ width: `${pct}%` }}
                    />
                </div>
                {MILESTONE_DAYS.map((day) => {
                    const milePct = (day / total) * 100;
                    const reached = clampedDay >= day;
                    return (
                        <div
                            key={day}
                            className="absolute top-1/2 -translate-x-1/2 -translate-y-1/2"
                            style={{ left: `${milePct}%` }}
                        >
                            <span
                                className={`flex h-3.5 w-3.5 items-center justify-center rounded-full border-2 ${
                                    reached
                                        ? 'border-primary bg-primary'
                                        : 'border-border bg-card'
                                }`}
                            />
                            <span className="absolute left-1/2 top-5 -translate-x-1/2 whitespace-nowrap text-xs text-muted-foreground">
                                Day {day}
                            </span>
                        </div>
                    );
                })}
            </div>
            <ul className="space-y-2">
                {unlocks.map((u) => (
                    <li key={`${u.day}-${u.feature}`} className="flex items-center gap-2 text-sm">
                        {u.unlocked ? (
                            <CheckCircle2 className="h-3.5 w-3.5 shrink-0 text-primary" aria-hidden="true" />
                        ) : (
                            <span className="h-3.5 w-3.5 shrink-0 rounded-full border-2 border-border" />
                        )}
                        <span className={u.unlocked ? 'text-foreground' : 'text-muted-foreground'}>
                            {u.feature}
                        </span>
                        {!u.unlocked && (
                            <span className="ml-auto text-xs text-muted-foreground">Day {u.day}</span>
                        )}
                    </li>
                ))}
            </ul>
        </div>
    );
}

// ─── Tab: Connected ───────────────────────────────────────────────────────────

function ConnectedTab({
    summary,
    integrations,
    syncEvents,
    onViewDetails,
}: {
    summary: Summary;
    integrations: Integration[];
    syncEvents: SyncEvent[];
    onViewDetails: (id: string) => void;
}) {
    const [filter, setFilter] = useState<FilterState>({ integration: '', status: '', timeRange: '24h' });
    const [activeSyncEvent, setActiveSyncEvent] = useState<SyncEvent | null>(null);

    const healthyCount = integrations.filter((i) => i.status === 'healthy').length;
    const allHealthy = healthyCount === summary.total_count;

    // Apply in-page filters to the connection grid
    const filteredIntegrations = useMemo(() => {
        return integrations.filter((i) => {
            if (filter.integration && i.id !== filter.integration) return false;
            if (filter.status && i.status !== filter.status) return false;
            return true;
        });
    }, [integrations, filter]);

    // Apply integration filter to sync feed
    const filteredSyncEvents = useMemo(() => {
        if (!filter.integration) return syncEvents;
        const integration = integrations.find((i) => i.id === filter.integration);
        if (!integration) return syncEvents;
        return syncEvents.filter((e) => e.integration === integration.type);
    }, [syncEvents, integrations, filter]);

    const SYNC_COLS = buildSyncColumns(setActiveSyncEvent);

    return (
        <div className="space-y-6">
            {/* KPI strip — 5 compact cards */}
            <KpiGrid cols={5}>
                <MetricCardCompact
                    label="Connected integrations"
                    value={`${summary.connected_count} / ${summary.total_count}`}
                    activeSource={allHealthy ? 'gsc' : 'facebook'}
                />
                <MetricCardCompact
                    label="Last sync"
                    value={formatRelative(summary.last_sync_at)}
                    activeSource="real"
                />
                <MetricCardCompact
                    label="Sync errors (24h)"
                    value={String(summary.sync_errors_24h)}
                    activeSource={summary.sync_errors_24h > 0 ? 'facebook' : 'gsc'}
                />
                <MetricCardCompact
                    label="Orders attributed"
                    value={`${summary.orders_attributed.toFixed(1)}%`}
                    activeSource="real"
                />
                <MetricCardCompact
                    label="Tag coverage"
                    value={`${summary.tag_coverage.toFixed(0)}%`}
                    activeSource="ga4"
                />
            </KpiGrid>

            {/* In-page filter bar (Polaris: contextual placement, NOT TopBar) */}
            <InPageFilterBar
                integrations={integrations}
                filter={filter}
                onChange={setFilter}
            />

            {/* Connections grid — 3-col on desktop (Vercel Entity pattern per card) */}
            {filteredIntegrations.length === 0 ? (
                <div className="rounded-xl border border-border bg-card p-8 text-center">
                    <p className="text-sm text-muted-foreground">No integrations match the current filters.</p>
                    <button
                        onClick={() => setFilter({ integration: '', status: '', timeRange: '24h' })}
                        className="mt-2 text-sm text-primary hover:underline"
                    >
                        Clear filters
                    </button>
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {filteredIntegrations.map((integration) => (
                        <ConnectionCard
                            key={integration.id}
                            integration={integration}
                            onViewDetails={onViewDetails}
                        />
                    ))}
                </div>
            )}

            {/* Sync activity feed — last 50 events; row click → payload drawer */}
            <div className="rounded-xl border border-border bg-card overflow-hidden">
                <div className="flex items-center justify-between border-b border-border/60 px-5 py-4">
                    <div>
                        <p className="text-sm font-semibold text-foreground">Sync activity</p>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Last {filteredSyncEvents.length} events — click a row to inspect the payload
                        </p>
                    </div>
                    <span className="text-xs text-muted-foreground tabular-nums">
                        {filteredSyncEvents.filter((e) => e.errors > 0).length > 0 && (
                            <span className="text-rose-600 font-semibold">
                                {filteredSyncEvents.filter((e) => e.errors > 0).length} with errors
                            </span>
                        )}
                    </span>
                </div>
                <DataTable
                    columns={SYNC_COLS}
                    data={filteredSyncEvents}
                    emptyMessage="No sync events yet"
                    defaultSort={{ key: 'ts', dir: 'desc' }}
                    onRowClick={setActiveSyncEvent}
                />
            </div>

            {/* Sync event detail drawer (Elevar payload inspector, Vercel deployment detail) */}
            {activeSyncEvent && (
                <SyncEventDrawer
                    event={activeSyncEvent}
                    onClose={() => setActiveSyncEvent(null)}
                />
            )}
        </div>
    );
}

// ─── Tab: Tracking Health ─────────────────────────────────────────────────────
// Elevar Channel Accuracy Report: per-source accuracy % + error code directory.

function TrackingHealthTab({
    accuracy,
    error_codes,
}: {
    accuracy: Props['accuracy'];
    error_codes: ErrorCodeRow[];
}) {
    const [activeError, setActiveError] = useState<ErrorCodeRow | null>(null);

    const ERROR_COLS = buildErrorCodeCols(setActiveError);

    return (
        <div className="space-y-6">
            {/* Accuracy cards — 4-col (Elevar Channel Accuracy Report per-channel %) */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <AccuracyCard
                    label="Facebook CAPI (7d)"
                    source="facebook"
                    value={accuracy.facebook}
                    tooltip="Server-side events matched against Shopify canonical orders. Threshold: 80% (Elevar industry standard)."
                />
                <AccuracyCard
                    label="Google Enhanced Conv. (7d)"
                    source="google"
                    value={accuracy.google}
                    tooltip="Enhanced Conversions matched against store purchase events."
                />
                <AccuracyCard
                    label="GSC delivery (7d)"
                    source="gsc"
                    value={accuracy.gsc}
                    tooltip="GSC has a 48h native data lag — this reflects delivered queries only, not a sync failure."
                />
                <AccuracyCard
                    label="GA4 events (7d)"
                    source="ga4"
                    value={accuracy.ga4}
                    tooltip="purchase events received vs Shopify order count. Null = no events in last 7d."
                />
            </div>

            {/* Error Code Directory — Elevar pattern with payload inspector row-click */}
            <div className="rounded-xl border border-border bg-card overflow-hidden">
                <div className="border-b border-border/60 px-5 py-4">
                    <p className="text-sm font-semibold text-foreground">Error code directory</p>
                    <p className="text-sm text-muted-foreground mt-0.5">
                        Platform-native error codes with plain-English remediations.
                        Click a row to inspect the raw JSON payload.
                    </p>
                </div>
                <DataTable
                    columns={ERROR_COLS}
                    data={error_codes}
                    emptyMessage="No error codes in the last 7 days"
                    defaultSort={{ key: 'count', dir: 'desc' }}
                    onRowClick={setActiveError}
                />
            </div>

            {/* Error payload drawer */}
            {activeError && (
                <ErrorPayloadDrawer
                    error={activeError}
                    onClose={() => setActiveError(null)}
                />
            )}
        </div>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function IntegrationsIndex({
    active_tab,
    tracking_health,
    summary,
    integrations,
    sync_events,
    missing_data_warnings,
    accuracy,
    error_codes,
    phased_unlock,
}: Props) {
    const { workspace: ws } = usePage<PageProps>().props;
    const wsSlug = ws?.slug;
    const w = (path: string) => wurl(wsSlug, path);

    const [detailId, setDetailId] = useState<string | null>(null);
    const detailIntegration = integrations.find((i) => i.id === detailId) ?? null;

    const tabs = [
        {
            label: 'Connected',
            value: 'connected',
            href: w('/integrations?tab=connected'),
            active: active_tab === 'connected' || !active_tab,
        },
        {
            label: 'Tracking Health',
            value: 'tracking',
            href: w('/integrations?tab=tracking'),
            active: active_tab === 'tracking',
        },
        {
            label: 'Historical Imports',
            value: 'imports',
            href: w('/integrations?tab=imports'),
            active: active_tab === 'imports',
        },
        {
            label: 'Channel Mapping',
            value: 'mapping',
            href: w('/integrations?tab=mapping'),
            active: active_tab === 'mapping',
        },
    ];

    // Global alert banners — critical → warning → info (Polaris stacking order)
    const failedIntegrations = integrations.filter((i) => i.status === 'error');
    const warningIntegrations = integrations.filter((i) => i.status === 'warning');

    return (
        <AppLayout>
            <Head title="Integrations" />

            {/* ── Global alert banners ──────────────────────────────────────── */}
            {failedIntegrations.length > 0 && (
                <div
                    className="flex items-start gap-3 border-b border-red-200 bg-red-50 px-6 py-3 text-sm text-red-800"
                    role="alert"
                >
                    <XCircle className="mt-0.5 h-4 w-4 shrink-0 text-red-600" aria-hidden="true" />
                    <p className="flex-1">
                        <span className="font-semibold">
                            {failedIntegrations.length} integration{failedIntegrations.length > 1 ? 's are' : ' is'} failing
                        </span>
                        {' — '}
                        {failedIntegrations.map((i) => i.name).join(', ')}.
                    </p>
                    <button className="shrink-0 font-semibold underline underline-offset-2 hover:opacity-80 transition-opacity">
                        Reconnect
                    </button>
                </div>
            )}
            {warningIntegrations.length > 0 && failedIntegrations.length === 0 && (
                <div
                    className="flex items-start gap-3 border-b border-amber-200 bg-amber-50 px-6 py-3 text-sm text-amber-900"
                    role="alert"
                >
                    <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" aria-hidden="true" />
                    <p className="flex-1">
                        {warningIntegrations.map((i) => i.name).join(', ')} — token expiring soon or match rate below threshold.
                    </p>
                </div>
            )}

            <div className="space-y-6 px-6 py-6">
                <PageHeader
                    title="Integrations"
                    subtitle="Connected sources, tracking accuracy, and data quality."
                />

                {/* ── 1. Tracking Health gauge — hero (Elevar Channel Accuracy Report) */}
                <TrackingHealthGauge health={tracking_health} />

                {/* ── 2. Missing data warnings — above tabs */}
                {missing_data_warnings.length > 0 && (
                    <MissingDataWarnings warnings={missing_data_warnings} />
                )}

                {/* ── 3. Tab strip */}
                <SubNavTabs tabs={tabs} />

                {/* ── 4. Tab content */}
                {(active_tab === 'connected' || !active_tab) && (
                    <ConnectedTab
                        summary={summary}
                        integrations={integrations}
                        syncEvents={sync_events}
                        onViewDetails={setDetailId}
                    />
                )}

                {active_tab === 'tracking' && (
                    <TrackingHealthTab
                        accuracy={accuracy}
                        error_codes={error_codes}
                    />
                )}

                {active_tab === 'imports' && (
                    <div className="rounded-xl border border-border bg-card p-8 text-center">
                        <p className="text-sm font-semibold text-foreground mb-1">No imports in progress</p>
                        <p className="text-sm text-muted-foreground">
                            Historical imports run automatically when you first connect an integration.
                        </p>
                    </div>
                )}

                {active_tab === 'mapping' && (
                    <div className="rounded-xl border border-border bg-card p-8 text-center">
                        <p className="text-sm font-semibold text-foreground mb-1">Channel mapping</p>
                        <p className="text-sm text-muted-foreground">
                            Configure UTM source/medium → channel rules at{' '}
                            <a href={w('/manage/channel-mappings')} className="text-primary underline underline-offset-2 hover:opacity-80">
                                Manage › Channel Mappings
                            </a>
                            .
                        </p>
                    </div>
                )}

                {/* ── 5. Phased unlock panel — Northbeam pattern */}
                <PhasedUnlockPanel
                    current_day={phased_unlock.current_day}
                    unlocks={phased_unlock.unlocks}
                />
            </div>

            {/* ── Connection detail drawer */}
            {detailIntegration && (
                <ConnectionDetailDrawer
                    integration={detailIntegration}
                    onClose={() => setDetailId(null)}
                />
            )}
        </AppLayout>
    );
}
