/**
 * Types for the Daily Journal page.
 * @see docs/competitors/_research_daily_journal.md
 */

export type NoteCategory = 'sale' | 'promo' | 'site_change' | 'external' | 'other';

export interface JournalNote {
    id: string;
    date: string;       // ISO date YYYY-MM-DD
    text: string;
    category: NoteCategory;
    author: string;
}

export interface DayRow {
    date: string;           // YYYY-MM-DD
    day_of_week: string;    // Mon, Tue, …
    day_num: number;
    ad_spend: number;
    revenue: number;
    orders: number;
    items: number;
    ipo: number;            // items / orders
    pct_marketing: number;  // ad_spend / revenue × 100
    aov: number;            // revenue / orders
    roas: number;           // revenue / ad_spend
}

export type ChartMetric = 'revenue' | 'aov' | 'roas' | 'ad_spend' | 'orders';

export const NOTE_CATEGORY_LABELS: Record<NoteCategory, string> = {
    sale:        'Sale',
    promo:       'Promo',
    site_change: 'Site change',
    external:    'External event',
    other:       'Other',
};

/**
 * Category → CSS variable / class mapping.
 * All colors come from existing CSS variables — no hardcoded hex.
 * @see docs/competitors/_research_daily_journal.md §4
 */
export const NOTE_CATEGORY_STYLE: Record<NoteCategory, { bg: string; text: string; border: string; chartStroke: string }> = {
    sale:        { bg: 'bg-amber-50',   text: 'text-amber-800',  border: 'border-amber-200',  chartStroke: 'var(--color-warning, #f59e0b)' },
    promo:       { bg: 'bg-indigo-50',  text: 'text-indigo-700', border: 'border-indigo-200', chartStroke: 'var(--brand-primary-subtle, #818cf8)' },
    site_change: { bg: 'bg-blue-50',    text: 'text-blue-700',   border: 'border-blue-200',   chartStroke: 'var(--color-info, #3b82f6)' },
    external:    { bg: 'bg-emerald-50', text: 'text-emerald-700',border: 'border-emerald-200',chartStroke: 'var(--color-success, #10b981)' },
    other:       { bg: 'bg-zinc-100',   text: 'text-zinc-600',   border: 'border-zinc-200',   chartStroke: 'var(--color-text-tertiary, #a1a1aa)' },
};

export const CHART_METRIC_LABELS: Record<ChartMetric, string> = {
    revenue:   'Revenue',
    aov:       'AOV',
    roas:      'ROAS',
    ad_spend:  'Ad Spend',
    orders:    'Orders',
};
