/**
 * Ads/Creatives — /ads/creatives  "Best Creatives" deep view.
 *
 * Feature #2 — gallery of top FB/Google creatives with:
 *   - Trophy strip (Triple Whale Creative Highlights pattern): top 6 cards by
 *     ROAS / Spend / CTR / Thumbstop / Hold Rate / Fastest Riser.
 *   - Gallery view (default): grid of CreativeGalleryCard cards.
 *   - List view (toggle): full DataTable with all metric columns.
 *   - In-page filters: platform / format / status / grade / sort — no TopBar additions.
 *   - Drawer: full performance + copy + audience context on click.
 *   - Klaviyo top performers section: 5 flows + 5 campaigns, collapsible.
 *
 * Design tokens: CSS vars only. No gold. WCAG AA. Polaris-style Shopify vibe.
 * Card surface: white, zinc-100/200 border, 20–24px padding, image fills top.
 * Grade dot: var(--color-success) / --color-warning / --color-danger / muted.
 * Hover border: var(--brand-primary)/40.
 *
 * @see docs/pages/ads.md §Creative Gallery view
 * @see docs/competitors/_research_best_creatives.md
 * @see docs/competitors/_teardown_triple-whale.md#screen-creative-cockpit
 * @see docs/competitors/_teardown_northbeam.md#screen-creative-analytics
 * @see docs/UX.md §5.1 MetricCard, §5.5 DataTable, §5.10 DrawerSidePanel
 */

import { memo, useMemo, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowRight,
    ArrowUp,
    Camera,
    ChevronDown,
    ChevronUp,
    Copy,
    ExternalLink,
    LayoutGrid,
    List,
    Mail,
    MessageSquare,
    MoreHorizontal,
    PauseCircle,
    Star,
    TrendingDown,
    TrendingUp,
    Trophy,
    X,
    Zap,
} from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { PlatformBadge } from '@/Components/shared/PlatformBadge';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { LetterGradeBadge } from '@/Components/shared/LetterGradeBadge';
import type { Grade } from '@/Components/shared/LetterGradeBadge';
import { SortButton } from '@/Components/shared/SortButton';
import { EmptyState } from '@/Components/shared/EmptyState';
import { formatCurrency, formatNumber } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type Format   = 'image' | 'video' | 'carousel' | 'email' | 'sms';
type ViewMode = 'grid' | 'list';

interface CreativeCard {
    ad_id: number;
    ad_name: string;
    platform: string;
    format: Format;
    status: string;
    effective_status: string | null;
    campaign_name: string;
    campaign_id: number;
    adset_id: number;
    days_running: number;
    ad_spend: number;
    ad_impressions: number;
    ad_clicks: number;
    ctr: number | null;
    cpc: number | null;
    real_roas: number | null;
    platform_cpa: number | null;
    thumbstop_pct: number | null;
    hold_rate_pct: number | null;
    hook_rate_pct: number | null;
    motion_score: number | null;
    motion_verdict: 'winner' | 'loser' | 'neutral' | null;
    target_roas: number | null;
    thumbnail_url: string | null;
    body_text: string | null;
    headline: string | null;
    ad_url: string | null;
    composite_score: number;
    triage_bucket: 'winners' | 'iteration' | 'candidates';
    prior_roas: number | null;
    rank_curr: number;
    rank_prev: number | null;
    momentum_dir: 'up' | 'down' | 'stable' | 'new';
    tags: Record<string, string>;
}

interface KlaviyoPerformer {
    id: string;
    name: string;
    revenue: number;
    orders: number;
    revenue_per_email: number;
    recipients: number;
}

interface KlaviyoPerformers {
    flows: KlaviyoPerformer[];
    campaigns: KlaviyoPerformer[];
}

interface AdAccount {
    id: number;
    platform: string;
    name: string;
    status: string;
}

interface Props {
    has_ad_accounts: boolean;
    ad_accounts: AdAccount[];
    creative_cards: CreativeCard[];
    workspace_target_roas: number | null;
    from: string;
    to: string;
    platform: string;
    campaign_id: number | null;
    adset_id: number | null;
    limit: number;
    sort: string;
    status: string;
    view: string;
    roas_threshold: number | null;
    format: string;
    grade: string;
    klaviyo_performers: KlaviyoPerformers | null;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function roasColor(roas: number | null, target: number): string {
    if (roas === null) return 'text-zinc-400';
    if (roas >= target)         return 'text-emerald-700 font-semibold';
    if (roas >= target * 0.5)   return 'text-amber-600 font-medium';
    return 'text-rose-600 font-semibold';
}

/**
 * Grade dot color — maps composite score to a CSS var token.
 * score ≥ 60 → success (green), 35–59 → warning (amber), < 35 → danger (red).
 */
function gradeDotClass(score: number): string {
    if (score >= 60) return 'bg-[var(--color-success,#16a34a)]';
    if (score >= 35) return 'bg-[var(--color-warning,#d97706)]';
    return 'bg-[var(--color-danger,#dc2626)]';
}

/**
 * Derive a letter grade from composite score (not CTR median for this view).
 * A: ≥ 80, B: ≥ 60, C: ≥ 35, D: ≥ 15, F: < 15.
 */
function scoreToGrade(score: number): Grade {
    if (score >= 80) return 'A';
    if (score >= 60) return 'B';
    if (score >= 35) return 'C';
    if (score >= 15) return 'D';
    return 'F';
}

function formatPct(v: number | null): string {
    if (v === null) return '—';
    return `${v.toFixed(1)}%`;
}

// ─── Format chip ──────────────────────────────────────────────────────────────

const FORMAT_COLORS: Record<string, string> = {
    image:    'bg-blue-50 text-blue-700 border-blue-200',
    video:    'bg-violet-50 text-violet-700 border-violet-200',
    carousel: 'bg-amber-50 text-amber-700 border-amber-200',
    email:    'bg-emerald-50 text-emerald-700 border-emerald-200',
    sms:      'bg-pink-50 text-pink-700 border-pink-200',
};

const FORMAT_LABELS: Record<string, string> = {
    image: 'Image', video: 'Video', carousel: 'Carousel', email: 'Email', sms: 'SMS',
};

function FormatChip({ format }: { format: string }) {
    return (
        <span className={cn(
            'inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium',
            FORMAT_COLORS[format] ?? 'bg-zinc-100 text-zinc-500 border-zinc-200',
        )}>
            {FORMAT_LABELS[format] ?? format}
        </span>
    );
}

// ─── Momentum arrow ───────────────────────────────────────────────────────────

function MomentumArrow({ dir, rankCurr, rankPrev }: {
    dir: CreativeCard['momentum_dir'];
    rankCurr: number;
    rankPrev: number | null;
}) {
    if (dir === 'new') {
        return (
            <span className="inline-flex items-center rounded border border-violet-200 bg-violet-50 px-1.5 py-0.5 text-xs font-semibold text-violet-700">
                NEW
            </span>
        );
    }
    const delta = rankPrev !== null ? rankPrev - rankCurr : 0;
    if (dir === 'up') {
        return (
            <span className="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 px-1.5 py-0.5 text-xs font-semibold text-emerald-700">
                <ArrowUp className="h-2.5 w-2.5" />+{delta}
            </span>
        );
    }
    if (dir === 'down') {
        return (
            <span className="inline-flex items-center gap-0.5 rounded-full bg-rose-50 px-1.5 py-0.5 text-xs font-semibold text-rose-600">
                <ArrowDown className="h-2.5 w-2.5" />{delta}
            </span>
        );
    }
    return (
        <span className="inline-flex items-center gap-0.5 rounded-full bg-zinc-100 px-1.5 py-0.5 text-xs font-semibold text-zinc-500">
            <ArrowRight className="h-2.5 w-2.5" />#{rankCurr}
        </span>
    );
}

// ─── Trophy strip ─────────────────────────────────────────────────────────────

/**
 * TrophyStrip — "Creative Highlights" horizontal strip.
 * 6 trophy cards: Top ROAS · Top Spend · Top CTR · Top Thumbstop · Top Hold Rate · Fastest Riser.
 * Triple Whale Creative Cockpit pattern: thumbnail + metric label + bold value.
 *
 * @see docs/competitors/_teardown_triple-whale.md#screen-creative-cockpit
 */
function TrophyStrip({ cards, currency }: { cards: CreativeCard[]; currency: string }) {
    if (cards.length === 0) return null;

    const topRoas = [...cards].filter((c) => c.real_roas !== null).sort((a, b) => (b.real_roas ?? 0) - (a.real_roas ?? 0))[0];
    const topSpend = [...cards].sort((a, b) => b.ad_spend - a.ad_spend)[0];
    const topCtr   = [...cards].filter((c) => c.ctr !== null).sort((a, b) => (b.ctr ?? 0) - (a.ctr ?? 0))[0];
    const topThumb = [...cards].filter((c) => c.thumbstop_pct !== null).sort((a, b) => (b.thumbstop_pct ?? 0) - (a.thumbstop_pct ?? 0))[0];
    const topHold  = [...cards].filter((c) => c.hold_rate_pct !== null).sort((a, b) => (b.hold_rate_pct ?? 0) - (a.hold_rate_pct ?? 0))[0];
    const fastRise = [...cards].filter((c) => c.momentum_dir === 'up').sort((a, b) => {
        const da = a.rank_prev !== null ? a.rank_prev - a.rank_curr : 0;
        const db = b.rank_prev !== null ? b.rank_prev - b.rank_curr : 0;
        return db - da;
    })[0] ?? [...cards].find((c) => c.momentum_dir === 'new');

    const trophies = [
        { label: 'Top ROAS',     card: topRoas,  value: topRoas  ? `${topRoas.real_roas!.toFixed(2)}×` : null,          icon: <Trophy className="h-3 w-3 text-emerald-600" /> },
        { label: 'Top Spend',    card: topSpend, value: topSpend ? formatCurrency(topSpend.ad_spend, currency, true) : null, icon: <Trophy className="h-3 w-3 text-blue-600" />    },
        { label: 'Top CTR',      card: topCtr,   value: topCtr   ? `${topCtr.ctr!.toFixed(2)}%` : null,                  icon: <Trophy className="h-3 w-3 text-violet-600" />   },
        { label: 'Top Thumbstop',card: topThumb, value: topThumb ? `${topThumb.thumbstop_pct!.toFixed(1)}%` : null,       icon: <Trophy className="h-3 w-3 text-amber-600" />    },
        { label: 'Top Hold Rate',card: topHold,  value: topHold  ? `${topHold.hold_rate_pct!.toFixed(1)}%` : null,        icon: <Trophy className="h-3 w-3 text-teal-600" />     },
        { label: 'Fastest Riser',card: fastRise, value: fastRise ? (fastRise.momentum_dir === 'new' ? 'NEW' : `+${(fastRise.rank_prev ?? 0) - fastRise.rank_curr} rank`) : null, icon: <TrendingUp className="h-3 w-3 text-rose-500" /> },
    ].filter((t) => t.card && t.value);

    if (trophies.length === 0) return null;

    return (
        <div className="mb-6 overflow-x-auto">
            <div className="flex items-center gap-2 mb-2">
                <Trophy className="h-4 w-4 text-zinc-400" />
                <span className="text-xs font-semibold text-zinc-500 uppercase tracking-wide">Creative Highlights</span>
            </div>
            <div className="flex gap-3 min-w-0 pb-1">
                {trophies.map(({ label, card, value, icon }) => card ? (
                    <div
                        key={label}
                        className="flex-shrink-0 flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 min-w-[200px]"
                    >
                        {/* Thumbnail placeholder */}
                        <div className="h-10 w-10 overflow-hidden rounded-lg bg-zinc-100 flex items-center justify-center shrink-0">
                            {card.thumbnail_url ? (
                                <img src={card.thumbnail_url} alt="" className="h-full w-full object-cover" />
                            ) : (
                                <Camera className="h-4 w-4 text-zinc-400" />
                            )}
                        </div>
                        <div className="min-w-0">
                            <div className="flex items-center gap-1 mb-0.5">
                                {icon}
                                <span className="text-xs text-zinc-500">{label}</span>
                            </div>
                            <p className="text-sm font-bold text-zinc-900 tabular-nums">{value}</p>
                            <p className="text-xs text-zinc-400 truncate max-w-[120px]" title={card.ad_name}>
                                {card.ad_name}
                            </p>
                        </div>
                    </div>
                ) : null)}
            </div>
        </div>
    );
}

// ─── Creative Gallery Card ────────────────────────────────────────────────────

/**
 * CreativeGalleryCard — individual card in the gallery view.
 *
 * Top: aspect-video thumbnail with overlaid momentum arrow (top-left),
 *      grade dot + letter grade (top-right), status badge (bottom-right).
 * Body: platform chip + format chip + name.
 * Metrics stripe: ROAS (hero, big) · Spend · Impressions · CTR · CPA.
 * Footer: days running, status.
 *
 * Border: zinc-200, hover → border-[var(--brand-primary)]/40.
 * No card shadows.
 *
 * @see docs/competitors/_research_best_creatives.md §Gallery layout decision
 */
const CreativeGalleryCard = memo(function CreativeGalleryCard({
    card, currency, roasTarget, onClick,
}: { card: CreativeCard; currency: string; roasTarget: number; onClick: (c: CreativeCard) => void }) {
    const [thumbErr, setThumbErr] = useState(false);
    const showThumb = card.thumbnail_url && !thumbErr;
    const grade = scoreToGrade(card.composite_score);
    const isActive = (card.effective_status ?? card.status) === 'active';

    return (
        <div
            role="button"
            tabIndex={0}
            onClick={() => onClick(card)}
            onKeyDown={(e) => e.key === 'Enter' && onClick(card)}
            className={cn(
                'relative flex flex-col rounded-xl border border-zinc-200 bg-white cursor-pointer',
                'transition-colors hover:border-[var(--brand-primary,#0d9488)]/40',
                'focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-primary,#0d9488)]',
                !isActive && 'opacity-60',
            )}
        >
            {/* Thumbnail */}
            <div className="mx-0 mt-0 aspect-video overflow-hidden rounded-t-xl bg-zinc-100 flex items-center justify-center">
                {showThumb ? (
                    <img
                        src={card.thumbnail_url!}
                        alt={card.ad_name}
                        className="h-full w-full object-cover"
                        onError={() => setThumbErr(true)}
                    />
                ) : (
                    <div className="flex flex-col items-center gap-1.5 text-zinc-300">
                        <Camera className="h-8 w-8" />
                        <span className="text-xs text-zinc-400">No preview</span>
                    </div>
                )}

                {/* Momentum arrow — top-left overlay */}
                <div className="absolute top-2 left-2 z-10">
                    <MomentumArrow dir={card.momentum_dir} rankCurr={card.rank_curr} rankPrev={card.rank_prev} />
                </div>

                {/* Grade dot + letter grade — top-right */}
                <div className="absolute top-2 right-2 z-10 flex items-center gap-1">
                    <span
                        className={cn('h-2 w-2 rounded-full', gradeDotClass(card.composite_score))}
                        title={`Composite score: ${card.composite_score.toFixed(0)}`}
                        aria-label={`Grade dot: ${card.triage_bucket}`}
                    />
                    <LetterGradeBadge grade={grade} size="sm" />
                </div>

                {/* Status badge — bottom-right */}
                <div className="absolute bottom-2 right-2 z-10">
                    <StatusBadge status={card.effective_status ?? card.status} />
                </div>
            </div>

            {/* Platform + format chips */}
            <div className="flex items-center gap-1.5 px-3 pt-3 pb-1 flex-wrap">
                <PlatformBadge platform={card.platform} />
                <FormatChip format={card.format} />
            </div>

            {/* Name */}
            <div className="px-3 pb-1.5">
                <p className="text-sm font-medium text-zinc-900 line-clamp-2 leading-snug" title={card.ad_name}>
                    {card.ad_name}
                </p>
                <p className="text-xs text-zinc-400 truncate mt-0.5" title={card.campaign_name}>
                    {card.campaign_name}
                </p>
            </div>

            {/* Hero metric — ROAS (Real), larger text */}
            <div className="px-3 pb-2 flex items-baseline gap-1.5">
                <span className={cn('text-xl font-bold tabular-nums', roasColor(card.real_roas, roasTarget))}>
                    {card.real_roas != null ? `${card.real_roas.toFixed(2)}×` : '—'}
                </span>
                <span className="text-xs text-zinc-400 font-normal">ROAS</span>
            </div>

            {/* Secondary metrics strip */}
            <div className="border-t border-zinc-100 px-3 py-2 space-y-1">
                {([
                    { label: 'Spend',       value: formatCurrency(card.ad_spend, currency) },
                    { label: 'Impressions', value: formatNumber(card.ad_impressions) },
                    { label: 'CTR',         value: formatPct(card.ctr) },
                    { label: 'CPA',         value: card.platform_cpa != null ? formatCurrency(card.platform_cpa, currency) : '—' },
                ] as const).map(({ label, value }) => (
                    <div key={label} className="flex items-center justify-between text-xs">
                        <span className="text-zinc-400">{label}</span>
                        <span className="font-medium text-zinc-800 tabular-nums">{value}</span>
                    </div>
                ))}
                {card.thumbstop_pct != null && (
                    <div className="flex items-center justify-between text-xs">
                        <span className="text-zinc-400">Thumbstop</span>
                        <span className="font-medium text-zinc-800 tabular-nums">{formatPct(card.thumbstop_pct)}</span>
                    </div>
                )}
            </div>

            {/* Footer: days running */}
            <div className="border-t border-zinc-100 px-3 py-2 flex items-center justify-between text-xs text-zinc-400">
                <span>{card.days_running}d running</span>
                {card.motion_verdict === 'winner' && (
                    <span className="flex items-center gap-0.5 text-emerald-600 font-medium">
                        <TrendingUp className="h-3 w-3" /> Winner
                    </span>
                )}
                {card.motion_verdict === 'loser' && (
                    <span className="flex items-center gap-0.5 text-rose-500 font-medium">
                        <TrendingDown className="h-3 w-3" /> Loser
                    </span>
                )}
            </div>
        </div>
    );
});

// ─── Creative Drawer ──────────────────────────────────────────────────────────

/**
 * CreativeDrawer — right-side drawer with full performance + copy + context.
 *
 * Sections: thumbnail, KPI grid, video metrics (if video), campaign context,
 * ad copy, tags, quick actions.
 *
 * @see docs/UX.md §5.10 DrawerSidePanel
 * @see docs/competitors/_teardown_northbeam.md#screen-creative-analytics (6-ad compare)
 */
const CreativeDrawer = memo(function CreativeDrawer({
    card, currency, roasTarget, onClose,
}: { card: CreativeCard | null; currency: string; roasTarget: number; onClose: () => void }) {
    if (!card) return null;

    const grade = scoreToGrade(card.composite_score);
    const roasChange = card.prior_roas !== null && card.real_roas !== null
        ? card.real_roas - card.prior_roas : null;

    function handleStubAction(action: string) {
        alert(`${action}: API not yet connected.`);
    }

    return (
        <>
            <div className="fixed inset-0 z-40 bg-black/30" onClick={onClose} aria-hidden="true" />
            <div
                role="dialog"
                aria-modal="true"
                aria-label="Creative detail"
                className="fixed inset-y-0 right-0 z-50 flex w-full max-w-[480px] flex-col overflow-y-auto bg-white border-l border-zinc-200"
            >
                {/* Header */}
                <div className="flex items-start justify-between border-b border-zinc-200 px-5 py-4">
                    <div className="min-w-0 flex-1 pr-3">
                        <div className="flex flex-wrap items-center gap-2 mb-1.5">
                            <PlatformBadge platform={card.platform} />
                            <FormatChip format={card.format} />
                            <StatusBadge status={card.effective_status ?? card.status} />
                            <LetterGradeBadge grade={grade} size="sm" />
                        </div>
                        <p className="text-sm font-semibold text-zinc-900 line-clamp-2 leading-snug" title={card.ad_name}>
                            {card.ad_name}
                        </p>
                        <p className="text-xs text-zinc-400 mt-0.5 truncate">{card.campaign_name}</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="shrink-0 rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600"
                        aria-label="Close"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                {/* Thumbnail */}
                {card.thumbnail_url ? (
                    <div className="border-b border-zinc-200 px-5 py-3">
                        <div className="aspect-video w-full overflow-hidden rounded-xl bg-zinc-100">
                            <img src={card.thumbnail_url} alt={card.ad_name} className="h-full w-full object-cover" />
                        </div>
                    </div>
                ) : (
                    <div className="border-b border-zinc-200 px-5 py-3">
                        <div className="aspect-video w-full overflow-hidden rounded-xl bg-zinc-50 flex items-center justify-center">
                            <Camera className="h-10 w-10 text-zinc-300" />
                        </div>
                    </div>
                )}

                {/* Momentum + running */}
                <div className="border-b border-zinc-200 px-5 py-3 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <MomentumArrow dir={card.momentum_dir} rankCurr={card.rank_curr} rankPrev={card.rank_prev} />
                        <span className="text-xs text-zinc-400">{card.days_running}d running</span>
                    </div>
                    <span className={cn(
                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                        card.triage_bucket === 'winners'   ? 'bg-emerald-100 text-emerald-700' :
                        card.triage_bucket === 'iteration' ? 'bg-amber-100 text-amber-700' :
                                                              'bg-rose-100 text-rose-700',
                    )}>
                        {card.triage_bucket === 'winners' ? 'Winner'
                            : card.triage_bucket === 'iteration' ? 'Iteration'
                            : 'Candidate'}
                    </span>
                </div>

                {/* Hero ROAS */}
                <div className="border-b border-zinc-200 px-5 py-4">
                    <p className="text-xs font-medium uppercase tracking-wide text-zinc-400 mb-1">ROAS (Real)</p>
                    <div className="flex items-baseline gap-2">
                        <span className={cn('text-3xl font-bold tabular-nums', roasColor(card.real_roas, roasTarget))}>
                            {card.real_roas != null ? `${card.real_roas.toFixed(2)}×` : 'N/A'}
                        </span>
                        {roasChange !== null && (
                            <span className={cn('text-sm font-medium', roasChange >= 0 ? 'text-emerald-600' : 'text-rose-500')}>
                                {roasChange >= 0 ? '▲' : '▼'}{Math.abs(roasChange).toFixed(2)}× vs prior
                            </span>
                        )}
                    </div>
                </div>

                {/* KPI grid — 2 cols */}
                <div className="grid grid-cols-2 gap-3 border-b border-zinc-200 px-5 py-4">
                    {([
                        { label: 'Spend',       value: formatCurrency(card.ad_spend, currency) },
                        { label: 'Impressions', value: formatNumber(card.ad_impressions) },
                        { label: 'Clicks',      value: formatNumber(card.ad_clicks) },
                        { label: 'CTR',         value: formatPct(card.ctr) },
                        { label: 'CPC',         value: card.cpc != null ? formatCurrency(card.cpc, currency) : '—' },
                        { label: 'CPA',         value: card.platform_cpa != null ? formatCurrency(card.platform_cpa, currency) : '—' },
                        ...(card.thumbstop_pct != null ? [{ label: 'Thumbstop', value: formatPct(card.thumbstop_pct) }] : []),
                        ...(card.hold_rate_pct != null ? [{ label: 'Hold Rate', value: formatPct(card.hold_rate_pct) }] : []),
                        ...(card.hook_rate_pct != null ? [{ label: 'Hook Rate', value: formatPct(card.hook_rate_pct) }] : []),
                        ...(card.motion_score  != null ? [{ label: 'Motion Score', value: String(card.motion_score) }] : []),
                    ] as const).map(({ label, value }) => (
                        <div key={label} className="rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-2">
                            <p className="text-xs text-zinc-400">{label}</p>
                            <p className="mt-0.5 text-sm font-semibold text-zinc-900 tabular-nums">{value}</p>
                        </div>
                    ))}
                </div>

                {/* Composite score */}
                <div className="border-b border-zinc-200 px-5 py-3">
                    <p className="mb-1 text-xs font-medium uppercase tracking-wide text-zinc-400">Composite score</p>
                    <div className="flex items-center gap-2">
                        <span className={cn(
                            'inline-flex items-center rounded-full px-2.5 py-1 text-sm font-bold tabular-nums',
                            card.composite_score >= 60 ? 'bg-emerald-100 text-emerald-800'
                                : card.composite_score >= 35 ? 'bg-amber-100 text-amber-800'
                                : 'bg-rose-100 text-rose-700',
                        )}>
                            {card.composite_score.toFixed(0)}
                        </span>
                        <span className="text-xs text-zinc-400">ROAS 50% + CTR 25% + CPA 25%</span>
                    </div>
                </div>

                {/* Ad copy */}
                {(card.headline || card.body_text) && (
                    <div className="border-b border-zinc-200 px-5 py-3">
                        <p className="mb-1 text-xs font-medium uppercase tracking-wide text-zinc-400">Ad copy</p>
                        {card.headline  && <p className="text-sm font-semibold text-zinc-900">{card.headline}</p>}
                        {card.body_text && <p className="mt-1 text-xs text-zinc-500 line-clamp-3">{card.body_text}</p>}
                    </div>
                )}

                {/* Tags */}
                {Object.keys(card.tags).length > 0 && (
                    <div className="border-b border-zinc-200 px-5 py-3">
                        <p className="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-400">Tags</p>
                        <div className="flex flex-wrap gap-1.5">
                            {Object.entries(card.tags).map(([k, v]) => v && v !== 'none' ? (
                                <span
                                    key={k}
                                    className="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600"
                                >
                                    {k}: {v}
                                </span>
                            ) : null)}
                        </div>
                    </div>
                )}

                {/* Quick actions */}
                <div className="border-b border-zinc-200 px-5 py-4">
                    <p className="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-400">Quick actions</p>
                    <div className="flex flex-wrap gap-2">
                        {card.ad_url && (
                            <a
                                href={card.ad_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
                            >
                                <ExternalLink className="h-3 w-3" /> View ad
                            </a>
                        )}
                        <button
                            onClick={() => { void navigator.clipboard.writeText(card.ad_id.toString()); }}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
                        >
                            <Copy className="h-3 w-3" /> Copy ID
                        </button>
                        <button
                            onClick={() => handleStubAction('Pause')}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
                        >
                            <PauseCircle className="h-3 w-3" /> Pause
                        </button>
                        <button
                            onClick={() => handleStubAction('Add note')}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
                        >
                            <MessageSquare className="h-3 w-3" /> Note
                        </button>
                        <button
                            onClick={() => handleStubAction('Pin creative')}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
                        >
                            <Star className="h-3 w-3" /> Pin
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
});

// ─── List view (DataTable) ────────────────────────────────────────────────────

/**
 * CreativeListView — sortable DataTable for the list-view toggle.
 *
 * Columns: Thumbnail + Name · Platform · Format · Spend · Impressions · CTR ·
 *          CPM · CPC · CPA · ROAS · Days Running · Status · Actions.
 * Red→green gradient on ROAS (Northbeam pattern).
 *
 * @see docs/competitors/_teardown_northbeam.md#screen-creative-analytics
 */
function CreativeListView({
    cards, currency, roasTarget, onRowClick,
}: { cards: CreativeCard[]; currency: string; roasTarget: number; onRowClick: (c: CreativeCard) => void }) {
    const [sort, setSort]           = useState<string>('composite_score');
    const [direction, setDirection] = useState<'asc' | 'desc'>('desc');

    const sorted = useMemo(() => {
        return [...cards].sort((a, b) => {
            const getV = (r: CreativeCard): number => {
                switch (sort) {
                    case 'ad_spend':         return r.ad_spend;
                    case 'ad_impressions':   return r.ad_impressions;
                    case 'ctr':              return r.ctr ?? -Infinity;
                    case 'cpc':              return r.cpc ?? -Infinity;
                    case 'platform_cpa':     return r.platform_cpa ?? -Infinity;
                    case 'real_roas':        return r.real_roas ?? -Infinity;
                    case 'days_running':     return r.days_running;
                    case 'composite_score':  return r.composite_score;
                    default:                 return r.composite_score;
                }
            };
            const av = getV(a), bv = getV(b);
            return direction === 'desc' ? bv - av : av - bv;
        });
    }, [cards, sort, direction]);

    function handleSort(col: string) {
        setDirection(sort === col && direction === 'desc' ? 'asc' : 'desc');
        setSort(col);
    }

    const sb = (col: string, label: string) => (
        <SortButton col={col} label={label} currentSort={sort} currentDir={direction} onSort={handleSort} />
    );

    if (cards.length === 0) {
        return (
            <div className="flex h-48 items-center justify-center rounded-xl border border-zinc-200 bg-white text-sm text-zinc-400">
                No creatives match the current filters.
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-zinc-200 bg-white overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full min-w-[1100px] text-sm">
                    <thead className="bg-zinc-50 border-b border-zinc-200 sticky top-0 z-10">
                        <tr className="text-left">
                            <th className="w-12 px-3 py-3" aria-label="Thumbnail" />
                            <th className="sticky left-0 bg-zinc-50 px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide min-w-[200px]">
                                Creative
                            </th>
                            <th className="px-3 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Platform</th>
                            <th className="px-3 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Format</th>
                            <th className="px-3 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide text-right">{sb('ad_spend', 'Spend')}</th>
                            <th className="px-3 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide text-right">{sb('ad_impressions', 'Impr.')}</th>
                            <th className="px-3 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide text-right">{sb('ctr', 'CTR')}</th>
                            <th className="px-3 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide text-right">CPM</th>
                            <th className="px-3 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide text-right">{sb('cpc', 'CPC')}</th>
                            <th className="px-3 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide text-right">{sb('platform_cpa', 'CPA')}</th>
                            <th className="px-3 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide text-right">{sb('real_roas', 'ROAS')}</th>
                            <th className="px-3 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide text-right">{sb('days_running', 'Days')}</th>
                            <th className="px-3 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Status</th>
                            <th className="px-3 py-3" aria-label="Actions" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-100">
                        {sorted.map((row) => {
                            const cpm = row.ad_impressions > 0
                                ? (row.ad_spend / row.ad_impressions) * 1000 : null;
                            return (
                                <tr
                                    key={row.ad_id}
                                    className="cursor-pointer hover:bg-zinc-50 transition-colors"
                                    onClick={() => onRowClick(row)}
                                >
                                    {/* Thumbnail */}
                                    <td className="px-3 py-2">
                                        <div className="h-10 w-14 overflow-hidden rounded-lg bg-zinc-100 flex items-center justify-center shrink-0">
                                            {row.thumbnail_url ? (
                                                <img src={row.thumbnail_url} alt="" className="h-full w-full object-cover" />
                                            ) : (
                                                <Camera className="h-4 w-4 text-zinc-400" />
                                            )}
                                        </div>
                                    </td>

                                    {/* Name — sticky */}
                                    <td className="sticky left-0 bg-white px-4 py-3 min-w-[200px] border-r border-zinc-100">
                                        <div className="flex items-center gap-2 min-w-0">
                                            <span className="block truncate font-medium text-zinc-900 text-sm" title={row.ad_name}>
                                                {row.ad_name}
                                            </span>
                                        </div>
                                        <span className="block truncate text-xs text-zinc-400" title={row.campaign_name}>
                                            {row.campaign_name}
                                        </span>
                                    </td>

                                    <td className="px-3 py-3"><PlatformBadge platform={row.platform} /></td>
                                    <td className="px-3 py-3"><FormatChip format={row.format} /></td>

                                    <td className="px-3 py-3 text-right tabular-nums text-zinc-900">
                                        {formatCurrency(row.ad_spend, currency)}
                                    </td>
                                    <td className="px-3 py-3 text-right tabular-nums text-zinc-500">
                                        {formatNumber(row.ad_impressions)}
                                    </td>
                                    <td className="px-3 py-3 text-right tabular-nums text-zinc-900">
                                        {formatPct(row.ctr)}
                                    </td>
                                    <td className="px-3 py-3 text-right tabular-nums text-zinc-500">
                                        {cpm != null ? formatCurrency(cpm, currency) : '—'}
                                    </td>
                                    <td className="px-3 py-3 text-right tabular-nums text-zinc-900">
                                        {row.cpc != null ? formatCurrency(row.cpc, currency) : '—'}
                                    </td>
                                    <td className="px-3 py-3 text-right tabular-nums text-zinc-900">
                                        {row.platform_cpa != null ? formatCurrency(row.platform_cpa, currency) : '—'}
                                    </td>

                                    {/* ROAS — red→green gradient (Northbeam) */}
                                    <td className={cn('px-3 py-3 text-right tabular-nums', roasColor(row.real_roas, roasTarget))}>
                                        {row.real_roas != null ? `${row.real_roas.toFixed(2)}×` : '—'}
                                    </td>
                                    <td className="px-3 py-3 text-right tabular-nums text-zinc-500">
                                        {row.days_running}d
                                    </td>
                                    <td className="px-3 py-3">
                                        <StatusBadge status={row.effective_status ?? row.status} />
                                    </td>
                                    <td className="px-3 py-3">
                                        <button
                                            onClick={(e) => { e.stopPropagation(); }}
                                            className="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600"
                                            aria-label="Row actions"
                                        >
                                            <MoreHorizontal className="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ─── Klaviyo section ──────────────────────────────────────────────────────────

/**
 * KlaviyoSection — collapsible section showing top 5 flows + top 5 campaigns
 * by attributed revenue. Light treatment — separate from the main ad gallery.
 *
 * @see docs/competitors/_research_best_creatives.md §Klaviyo top performers
 */
function KlaviyoSection({ performers, currency }: { performers: KlaviyoPerformers; currency: string }) {
    const [open, setOpen] = useState(false);

    return (
        <div className="mt-8 rounded-xl border border-zinc-200 bg-white">
            <button
                onClick={() => setOpen((o) => !o)}
                className="flex w-full items-center justify-between px-5 py-4 text-left"
                aria-expanded={open}
            >
                <div className="flex items-center gap-2">
                    <Mail className="h-4 w-4 text-emerald-600" />
                    <span className="text-sm font-semibold text-zinc-900">Klaviyo Top Performers</span>
                    <span className="rounded-full bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-xs font-medium text-emerald-700">
                        5 flows · 5 campaigns
                    </span>
                </div>
                {open
                    ? <ChevronUp className="h-4 w-4 text-zinc-400" />
                    : <ChevronDown className="h-4 w-4 text-zinc-400" />
                }
            </button>

            {open && (
                <div className="border-t border-zinc-200 px-5 py-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                    {/* Flows */}
                    <div>
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Top Flows</p>
                        <div className="overflow-hidden rounded-lg border border-zinc-100">
                            <table className="w-full text-xs">
                                <thead className="bg-zinc-50 border-b border-zinc-100">
                                    <tr className="text-left">
                                        <th className="px-3 py-2 font-semibold text-zinc-500 uppercase tracking-wide">Flow</th>
                                        <th className="px-3 py-2 text-right font-semibold text-zinc-500 uppercase tracking-wide">Revenue</th>
                                        <th className="px-3 py-2 text-right font-semibold text-zinc-500 uppercase tracking-wide">Orders</th>
                                        <th className="px-3 py-2 text-right font-semibold text-zinc-500 uppercase tracking-wide">Rev/Email</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-100">
                                    {performers.flows.map((f) => (
                                        <tr key={f.id} className="hover:bg-zinc-50">
                                            <td className="px-3 py-2 font-medium text-zinc-800 truncate max-w-[140px]" title={f.name}>
                                                {f.name}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums text-zinc-900 font-semibold">
                                                {formatCurrency(f.revenue, currency, true)}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums text-zinc-500">
                                                {f.orders}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums text-zinc-700">
                                                {formatCurrency(f.revenue_per_email, currency)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Campaigns */}
                    <div>
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Top Campaigns</p>
                        <div className="overflow-hidden rounded-lg border border-zinc-100">
                            <table className="w-full text-xs">
                                <thead className="bg-zinc-50 border-b border-zinc-100">
                                    <tr className="text-left">
                                        <th className="px-3 py-2 font-semibold text-zinc-500 uppercase tracking-wide">Campaign</th>
                                        <th className="px-3 py-2 text-right font-semibold text-zinc-500 uppercase tracking-wide">Revenue</th>
                                        <th className="px-3 py-2 text-right font-semibold text-zinc-500 uppercase tracking-wide">Orders</th>
                                        <th className="px-3 py-2 text-right font-semibold text-zinc-500 uppercase tracking-wide">Rev/Email</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-100">
                                    {performers.campaigns.map((c) => (
                                        <tr key={c.id} className="hover:bg-zinc-50">
                                            <td className="px-3 py-2 font-medium text-zinc-800 truncate max-w-[140px]" title={c.name}>
                                                {c.name}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums text-zinc-900 font-semibold">
                                                {formatCurrency(c.revenue, currency, true)}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums text-zinc-500">
                                                {c.orders}
                                            </td>
                                            <td className="px-3 py-2 text-right tabular-nums text-zinc-700">
                                                {formatCurrency(c.revenue_per_email, currency)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

// ─── In-page filter bar ───────────────────────────────────────────────────────

interface FilterState {
    platform: string;
    format: string;
    status: string;
    grade: string;
    sort: string;
}

/**
 * FilterBar — all filters live in-page, not in the global TopBar.
 * Platform · Format · Status · Grade (top/middling/bottom) · Sort.
 *
 * @see docs/UX.md §6 (in-page filters, contextual)
 * @see feedback_in_page_filters.md
 */
function FilterBar({ filters, onChange }: {
    filters: FilterState;
    onChange: (k: keyof FilterState, v: string) => void;
}) {
    const pill = (k: keyof FilterState, v: string, label: string) => (
        <button
            key={v}
            onClick={() => onChange(k, v)}
            className={cn(
                'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                filters[k] === v
                    ? 'border-teal-600 bg-teal-50 text-teal-700'
                    : 'border-zinc-200 bg-white text-zinc-500 hover:border-zinc-300 hover:text-zinc-700',
            )}
        >
            {label}
        </button>
    );

    return (
        <div className="mb-5 flex flex-wrap items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
            {/* Platform */}
            <span className="text-xs font-medium text-zinc-400 mr-1">Platform:</span>
            {(['all', 'facebook', 'google'] as const).map((v) => pill('platform', v, v === 'all' ? 'All' : v === 'facebook' ? 'Facebook' : 'Google'))}

            <span className="mx-1 text-zinc-200">|</span>

            {/* Format */}
            <span className="text-xs font-medium text-zinc-400 mr-1">Format:</span>
            {(['all', 'image', 'video', 'carousel'] as const).map((v) => pill('format', v, v === 'all' ? 'All' : FORMAT_LABELS[v]))}

            <span className="mx-1 text-zinc-200">|</span>

            {/* Status */}
            <span className="text-xs font-medium text-zinc-400 mr-1">Status:</span>
            {(['all', 'active', 'paused', 'archived'] as const).map((v) => pill('status', v, v === 'all' ? 'All' : v.charAt(0).toUpperCase() + v.slice(1)))}

            <span className="mx-1 text-zinc-200">|</span>

            {/* Grade */}
            <span className="text-xs font-medium text-zinc-400 mr-1">Grade:</span>
            {(['all', 'top', 'middling', 'bottom'] as const).map((v) => pill('grade', v, v === 'all' ? 'All' : v.charAt(0).toUpperCase() + v.slice(1)))}

            <div className="ml-auto flex items-center gap-2">
                <span className="text-xs font-medium text-zinc-400">Sort:</span>
                <select
                    value={filters.sort}
                    onChange={(e) => onChange('sort', e.target.value)}
                    className="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs text-zinc-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
                >
                    <option value="composite_score">Score</option>
                    <option value="real_roas">ROAS</option>
                    <option value="ad_spend">Spend</option>
                    <option value="ctr">CTR</option>
                    <option value="days_running">Recency</option>
                </select>
            </div>
        </div>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function Creatives({
    has_ad_accounts,
    creative_cards,
    workspace_target_roas,
    from,
    to,
    klaviyo_performers,
}: Props) {
    const { workspace: ws } = usePage<PageProps>().props;
    const wsSlug   = ws?.slug;
    const currency = ws?.reporting_currency ?? 'USD';
    const roasTarget = workspace_target_roas ?? 2.0;

    const [viewMode, setViewMode] = useState<ViewMode>('grid');
    const [selectedCard, setSelectedCard] = useState<CreativeCard | null>(null);
    const [filters, setFilters] = useState<FilterState>({
        platform: 'all',
        format:   'all',
        status:   'all',
        grade:    'all',
        sort:     'composite_score',
    });

    function handleFilterChange(k: keyof FilterState, v: string) {
        setFilters((prev) => ({ ...prev, [k]: v }));
    }

    // Apply local filters (no server round-trip — all 20 creatives are pre-loaded)
    const visibleCards = useMemo(() => {
        let r = creative_cards;
        if (filters.platform !== 'all') r = r.filter((c) => c.platform === filters.platform);
        if (filters.format   !== 'all') r = r.filter((c) => c.format   === filters.format);
        if (filters.status   !== 'all') r = r.filter((c) => (c.effective_status ?? c.status) === filters.status);
        if (filters.grade    === 'top')      r = r.filter((c) => c.composite_score >= 60);
        if (filters.grade    === 'middling') r = r.filter((c) => c.composite_score >= 35 && c.composite_score < 60);
        if (filters.grade    === 'bottom')   r = r.filter((c) => c.composite_score < 35);

        return [...r].sort((a, b) => {
            switch (filters.sort) {
                case 'real_roas':   return (b.real_roas ?? -Infinity) - (a.real_roas ?? -Infinity);
                case 'ad_spend':    return b.ad_spend - a.ad_spend;
                case 'ctr':         return (b.ctr ?? -Infinity) - (a.ctr ?? -Infinity);
                case 'days_running':return a.days_running - b.days_running; // newest first = lowest days_running
                default:            return b.composite_score - a.composite_score;
            }
        });
    }, [creative_cards, filters]);

    const counts = useMemo(() => ({
        total:      visibleCards.length,
        winners:    visibleCards.filter((c) => c.triage_bucket === 'winners').length,
        iteration:  visibleCards.filter((c) => c.triage_bucket === 'iteration').length,
        candidates: visibleCards.filter((c) => c.triage_bucket === 'candidates').length,
    }), [visibleCards]);

    return (
        <AppLayout>
            <Head title="Best Creatives" />

            <div className="space-y-0">
                <PageHeader
                    title="Best Creatives"
                    subtitle={`${from} — ${to} · Top FB &amp; Google creative performance`}
                />

                {/* ── Trophy strip (Triple Whale Creative Highlights) ── */}
                <TrophyStrip cards={creative_cards} currency={currency} />

                {/* ── In-page filter bar ── */}
                <FilterBar filters={filters} onChange={handleFilterChange} />

                {/* ── Toolbar row: view toggle + summary counts ── */}
                <div className="mb-4 flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3 text-xs text-zinc-500">
                        <span>
                            <span className="font-semibold text-zinc-900">{counts.total}</span> creatives
                        </span>
                        <span className="text-emerald-600 font-medium">{counts.winners} winners</span>
                        <span className="text-amber-600 font-medium">{counts.iteration} iteration</span>
                        <span className="text-rose-500 font-medium">{counts.candidates} candidates</span>
                    </div>

                    {/* View toggle: Gallery | List */}
                    <div className="flex items-center rounded-lg border border-zinc-200 bg-white overflow-hidden text-xs font-medium">
                        {([
                            { value: 'grid' as ViewMode, icon: <LayoutGrid className="h-3.5 w-3.5" />, label: 'Gallery' },
                            { value: 'list' as ViewMode, icon: <List className="h-3.5 w-3.5" />,       label: 'List'    },
                        ]).map((opt) => (
                            <button
                                key={opt.value}
                                onClick={() => setViewMode(opt.value)}
                                className={cn(
                                    'flex items-center gap-1.5 px-3 py-1.5 border-r border-zinc-200 last:border-r-0 transition-colors',
                                    viewMode === opt.value
                                        ? 'bg-zinc-900 text-white'
                                        : 'text-zinc-500 hover:text-zinc-700',
                                )}
                                aria-pressed={viewMode === opt.value}
                            >
                                {opt.icon}
                                <span className="hidden sm:inline">{opt.label}</span>
                            </button>
                        ))}
                    </div>
                </div>

                {/* ── Content ── */}
                {!has_ad_accounts && creative_cards.length === 0 ? (
                    <EmptyState
                        title="No ad accounts connected"
                        description="Connect a Facebook Ads or Google Ads account to see your creative performance."
                        action={{ label: 'Connect ad account', href: wurl(wsSlug, '/settings/integrations') }}
                    />
                ) : visibleCards.length === 0 ? (
                    <EmptyState
                        title="No creatives match your filters"
                        description="Try widening the platform, format, or grade filter."
                    />
                ) : viewMode === 'grid' ? (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        {visibleCards.map((card) => (
                            <CreativeGalleryCard
                                key={card.ad_id}
                                card={card}
                                currency={currency}
                                roasTarget={roasTarget}
                                onClick={setSelectedCard}
                            />
                        ))}
                    </div>
                ) : (
                    <CreativeListView
                        cards={visibleCards}
                        currency={currency}
                        roasTarget={roasTarget}
                        onRowClick={setSelectedCard}
                    />
                )}

                {/* ── Legend ── */}
                {visibleCards.length > 0 && (
                    <div className="mt-6 flex flex-wrap items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-xs text-zinc-500">
                        <div className="flex items-center gap-1.5">
                            <span className="h-2 w-2 rounded-full bg-[var(--color-success,#16a34a)]" />
                            Top (score ≥ 60)
                        </div>
                        <div className="flex items-center gap-1.5">
                            <span className="h-2 w-2 rounded-full bg-[var(--color-warning,#d97706)]" />
                            Middling (35–59)
                        </div>
                        <div className="flex items-center gap-1.5">
                            <span className="h-2 w-2 rounded-full bg-[var(--color-danger,#dc2626)]" />
                            Bottom (&lt;35)
                        </div>
                        <span className="text-zinc-300" aria-hidden>|</span>
                        <span>ROAS = store-attributed (Real)</span>
                        <span className="text-zinc-300" aria-hidden>|</span>
                        <span>Score = ROAS 50% + CTR 25% + CPA 25%</span>
                        <span className="text-zinc-300" aria-hidden>|</span>
                        <div className="flex items-center gap-1">
                            <Zap className="h-3 w-3 text-violet-500" />
                            Momentum = rank vs prior equal-length window
                        </div>
                    </div>
                )}

                {/* ── Klaviyo top performers section ── */}
                {klaviyo_performers && (
                    <KlaviyoSection performers={klaviyo_performers} currency={currency} />
                )}
            </div>

            {/* ── Drawer ── */}
            <CreativeDrawer
                card={selectedCard}
                currency={currency}
                roasTarget={roasTarget}
                onClose={() => setSelectedCard(null)}
            />
        </AppLayout>
    );
}
