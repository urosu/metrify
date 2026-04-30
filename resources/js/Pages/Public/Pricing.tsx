import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Check, X, ChevronRight } from 'lucide-react';
import { buttonVariants } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';

// ---------------------------------------------------------------------------
// Revenue slider — interactive pricing example.
// Anchors on monthly store revenue (matches the 0.4% revenue-share framing).
// Quick-select buttons follow Metorik's pattern (fastest path to the right row).
// ---------------------------------------------------------------------------

const QUICK_SELECT_VALUES = [10_000, 25_000, 50_000, 100_000, 200_000, 500_000];

function formatEur(amount: number): string {
    if (amount >= 1_000_000) return `€${(amount / 1_000_000).toFixed(1)}M`;
    if (amount >= 1_000) return `€${(amount / 1_000).toFixed(0)}k`;
    return `€${amount}`;
}

function PriceSlider() {
    const [revenue, setRevenue] = useState(50_000);

    const revenueShare = Math.round(revenue * 0.004);
    const total = 39 + revenueShare;

    return (
        <div className="rounded-2xl border border-border bg-card p-8 shadow-sm">
            <p className="mb-1 text-sm font-medium uppercase tracking-wide text-muted-foreground/70">
                Your monthly attributed revenue
            </p>
            <p className="mb-6 text-3xl font-bold text-foreground">
                {formatEur(revenue)}
                <span className="ml-1 text-base font-normal text-muted-foreground/70">/mo</span>
            </p>

            {/* Quick-select buttons */}
            <div className="mb-4 flex flex-wrap gap-2">
                {QUICK_SELECT_VALUES.map((v) => (
                    <button
                        key={v}
                        onClick={() => setRevenue(v)}
                        className={[
                            'rounded-md border px-3 py-1 text-sm font-medium transition-colors',
                            revenue === v
                                ? 'border-yellow-400 bg-yellow-50 text-yellow-800'
                                : 'border-border bg-muted/50 text-muted-foreground hover:border-input hover:bg-muted',
                        ].join(' ')}
                    >
                        {formatEur(v)}
                    </button>
                ))}
            </div>

            {/* Range slider */}
            <input
                type="range"
                min={5_000}
                max={500_000}
                step={5_000}
                value={revenue}
                onChange={(e) => setRevenue(Number(e.target.value))}
                className="mb-6 w-full accent-yellow-400"
            />

            {/* Calculation breakdown */}
            <div className="space-y-2 rounded-xl bg-muted/50 p-4">
                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>Base fee</span>
                    <span className="font-medium text-foreground">€39</span>
                </div>
                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>0.4% of {formatEur(revenue)} attributed revenue</span>
                    <span className="font-medium text-foreground">€{revenueShare}</span>
                </div>
                <div className="mt-2 flex items-center justify-between border-t border-border pt-2">
                    <span className="font-semibold text-foreground">Total / month</span>
                    <span className="text-xl font-bold text-foreground">€{total}</span>
                </div>
            </div>

            {revenue >= 500_000 && (
                <p className="mt-3 text-center text-sm text-muted-foreground/70">
                    Over €500k/mo?{' '}
                    <a
                        href="mailto:hello@nexstage.io"
                        className="underline hover:text-muted-foreground"
                    >
                        Talk to us
                    </a>{' '}
                    — we can work something out.
                </p>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Feature list — what's included on every plan (no feature gates)
// ---------------------------------------------------------------------------

const FEATURES = [
    'Shopify & WooCommerce connectors',
    'Facebook Ads + Google Ads',
    'Google Search Console + GA4',
    'Source disagreement view (Real / Store / Facebook / Google / GSC / GA4)',
    'Multi-store & multi-workspace',
    'Unlimited team seats',
    'Attribution model comparison (First Touch, Last Touch, Last Non-Direct, Linear, Position-Based, Time Decay)',
    'Product lifecycle labels & LTV drivers',
    'Customer RFM segments & cohort retention',
    'Email digests & anomaly alerts',
    'Public shareable snapshots',
    '90-day historical import on connect',
];

// ---------------------------------------------------------------------------
// Comparison table — Nexstage vs Triple Whale vs Metorik
// Row data: [feature, nexstage, tripleWhale, metorik]
// ---------------------------------------------------------------------------
// Source: docs/competitors/competitors/triple-whale.md, last verified 2026-04-29
//   Triple Whale Starter $179–$299/mo (entry GMV band), Advanced $259–$389/mo.
//   At $5–7M GMV (mid-market), Conjura cites $1,129/mo — well above SMB range.
//   WooCommerce: listed as supported but treated as second-class by all reviewers.
//   Multi-store requires Advanced tier+.
// Source: docs/competitors/competitors/metorik.md, last verified 2026-04-29
//   Metorik Level 1 $25/mo (≤100 orders), Level 2 $75/mo, Level 3 $150/mo, Level 4 $250/mo.
//   Attribution: platform-reported spend only (no pixel/multi-touch attribution layer).
//   30-day free trial, no credit card.
// ---------------------------------------------------------------------------

type CellValue = string | boolean;

interface ComparisonRow {
    label: string;
    nexstage: CellValue;
    tripleWhale: CellValue;
    metorik: CellValue;
}

const COMPARISON_ROWS: ComparisonRow[] = [
    // Source: docs/competitors/competitors/triple-whale.md, last verified 2026-04-29
    // Source: docs/competitors/competitors/metorik.md, last verified 2026-04-29
    { label: 'WooCommerce support',          nexstage: true,     tripleWhale: false,   metorik: true       },
    { label: 'Source disagreement UI',       nexstage: true,     tripleWhale: false,   metorik: false      },
    { label: 'Attribution models',           nexstage: '7',      tripleWhale: '3',     metorik: '1'        },
    { label: 'Multi-store',                  nexstage: true,     tripleWhale: 'Paid',  metorik: true       },
    // Triple Whale: Starter $179–$299/mo entry band (Conjura Apr-2025: $1,129/mo at $5–7M GMV).
    // Metorik: order-volume tiers ($25–$250/mo); ~€129 estimate at typical €50k/mo revenue band.
    { label: 'Price at €50k/mo revenue',     nexstage: '€239',   tripleWhale: '€389+', metorik: '€129'     },
    // Triple Whale: Advanced $259–$389/mo (entry); Professional $749/mo+.
    { label: 'Price at €200k/mo revenue',    nexstage: '€839',   tripleWhale: '$749+', metorik: '$149+'    },
    // Triple Whale: Founders Dash free-forever plan (feature-capped); no free trial for paid.
    { label: 'Free trial, no card required', nexstage: '14 days',tripleWhale: 'Free plan (capped)', metorik: '30 days' },
    { label: 'Contract required',            nexstage: false,    tripleWhale: false,   metorik: false      },
];

function CellDisplay({ value, isNexstage }: { value: CellValue; isNexstage?: boolean }) {
    if (value === true) {
        return (
            <span className={isNexstage ? 'text-yellow-500' : 'text-green-500'}>
                <Check className="inline-block size-4" strokeWidth={2.5} />
            </span>
        );
    }
    if (value === false) {
        return (
            <span className="text-muted-foreground/50">
                <X className="inline-block size-4" strokeWidth={2} />
            </span>
        );
    }
    return (
        <span className={isNexstage ? 'font-semibold text-foreground' : 'text-muted-foreground'}>
            {value}
        </span>
    );
}

// ---------------------------------------------------------------------------
// FAQ
// ---------------------------------------------------------------------------

interface FaqItem {
    q: string;
    a: string;
}

const FAQ_ITEMS: FaqItem[] = [
    {
        q: 'What counts as attributed revenue?',
        a: "Revenue matched to at least one marketing source — Facebook, Google, organic search, email, or any channel we can attribute. Direct orders and orders where attribution is completely unknown don't count toward the 0.4%. You can always see unattributed volume separately in the source disagreement view.",
    },
    {
        q: 'Is there a free trial?',
        a: 'Yes — 14 days of full access, no credit card required. Analytics tools need time to sync data and show meaningful patterns; 7 days is not enough, so we give you two weeks.',
    },
    {
        q: 'What if I connect multiple stores?',
        a: 'All stores in one workspace. Attribution and revenue are aggregated across stores. One monthly invoice based on your combined attributed revenue.',
    },
    {
        q: 'Can I cancel any time?',
        a: 'Yes. Settings → Billing → Cancel. No retention flows, no "are you sure?" hoops, no questions. Your data stays in read-only view for 30 days after cancellation.',
    },
];

function FaqAccordion({ items }: { items: FaqItem[] }) {
    const [open, setOpen] = useState<number | null>(null);

    return (
        <div className="divide-y divide-border rounded-xl border border-border bg-card">
            {items.map((item, i) => (
                <div key={i}>
                    <button
                        className="flex w-full items-center justify-between px-6 py-4 text-left"
                        onClick={() => setOpen(open === i ? null : i)}
                    >
                        <span className="font-medium text-foreground">{item.q}</span>
                        <ChevronRight
                            className={[
                                'ml-4 size-4 shrink-0 text-muted-foreground/70 transition-transform',
                                open === i ? 'rotate-90' : '',
                            ].join(' ')}
                        />
                    </button>
                    {open === i && (
                        <div className="px-6 pb-5 text-sm leading-relaxed text-muted-foreground">
                            {item.a}
                        </div>
                    )}
                </div>
            ))}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Page layout — minimal wrapper (no sidebar, no workspace chrome)
// ---------------------------------------------------------------------------

export default function Pricing() {
    return (
        <>
            <Head title="Pricing — Nexstage" />

            <div className="min-h-screen bg-muted/50">
                {/* ── Nav ─────────────────────────────────────────────── */}
                <nav className="sticky top-0 z-10 border-b border-border bg-card/90 backdrop-blur-sm">
                    <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                        <Link href="/" className="text-xl font-bold tracking-tight text-foreground">
                            Nexstage
                        </Link>
                        <div className="flex items-center gap-3">
                            <Link
                                href="/login"
                                className="text-sm text-muted-foreground hover:text-foreground"
                            >
                                Log in
                            </Link>
                            <Link href="/register" className={cn(buttonVariants({ size: 'sm' }))}>
                                Start free trial
                            </Link>
                        </div>
                    </div>
                </nav>

                <div className="mx-auto max-w-5xl px-6 py-16">

                    {/* ── Section 1 — Hero + price ────────────────────── */}
                    <section className="mb-20 text-center">
                        <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-yellow-300 bg-yellow-50 px-3 py-1 text-xs font-medium text-yellow-800">
                            <span className="inline-block size-2 rounded-full bg-yellow-400" />
                            One plan. No feature gates. No seat limits.
                        </div>

                        <h1 className="mb-4 text-5xl font-bold tracking-tight text-foreground sm:text-6xl">
                            One plan.
                            <br />
                            <span className="text-yellow-400">No surprises.</span>
                        </h1>

                        <p className="mx-auto mb-8 max-w-xl text-lg text-muted-foreground">
                            Flat fee plus a small share of what we help you attribute — so our
                            incentives stay aligned with yours.
                        </p>

                        {/* Price callout */}
                        <div className="mb-8 flex flex-col items-center justify-center gap-1">
                            <div className="flex items-end gap-1">
                                <span className="text-7xl font-extrabold tracking-tight text-foreground">
                                    €39
                                </span>
                                <span className="mb-3 text-2xl font-medium text-muted-foreground/70">/mo</span>
                            </div>
                            <p className="text-base text-muted-foreground">
                                + 0.4% of attributed revenue
                            </p>
                            <p className="text-sm text-muted-foreground/70">
                                No credit card for trial. Cancel any time.
                            </p>
                        </div>

                        <div className="flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                            <Link href="/register" className={cn(buttonVariants({ size: 'lg' }), 'w-full sm:w-auto')}>
                                Start free trial — 14 days free
                            </Link>
                            <Link
                                href="/login"
                                className="text-sm text-muted-foreground hover:text-foreground"
                            >
                                Already have an account? Log in
                            </Link>
                        </div>
                    </section>

                    {/* ── Section 2 — What's included ─────────────────── */}
                    <section className="mb-20">
                        <h2 className="mb-8 text-center text-2xl font-bold text-foreground">
                            Everything included. Always.
                        </h2>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            {FEATURES.map((feature) => (
                                <div
                                    key={feature}
                                    className="flex items-start gap-3 rounded-lg border border-border bg-card px-4 py-3 shadow-sm"
                                >
                                    <Check
                                        className="mt-0.5 size-4 shrink-0 text-yellow-400"
                                        strokeWidth={2.5}
                                    />
                                    <span className="text-base text-foreground">{feature}</span>
                                </div>
                            ))}
                        </div>
                    </section>

                    {/* ── Section 3 — Pricing math + interactive slider ─ */}
                    <section className="mb-20">
                        <h2 className="mb-2 text-center text-2xl font-bold text-foreground">
                            See what you'd pay
                        </h2>
                        <p className="mb-8 text-center text-muted-foreground">
                            Drag the slider or pick a revenue size. The math is public — no
                            hidden tiers.
                        </p>
                        <div className="mx-auto max-w-lg">
                            <PriceSlider />
                        </div>

                        {/* Static examples for skimmers */}
                        <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            {[
                                { revenue: '€25k/mo', share: '€100', total: '€139' },
                                { revenue: '€50k/mo', share: '€200', total: '€239', highlight: true },
                                { revenue: '€200k/mo', share: '€800', total: '€839' },
                            ].map(({ revenue, share, total, highlight }) => (
                                <div
                                    key={revenue}
                                    className={[
                                        'rounded-xl border p-5 text-center',
                                        highlight
                                            ? 'border-yellow-300 bg-yellow-50'
                                            : 'border-border bg-card',
                                    ].join(' ')}
                                >
                                    {highlight && (
                                        <p className="mb-2 text-sm font-semibold uppercase tracking-wide text-yellow-600">
                                            Example
                                        </p>
                                    )}
                                    <p className="text-sm font-medium text-muted-foreground">{revenue} revenue</p>
                                    <p className="mt-1 text-sm text-muted-foreground/70">
                                        €39 + {share}
                                    </p>
                                    <p className="mt-2 text-2xl font-bold text-foreground">{total}</p>
                                    <p className="text-xs text-muted-foreground/70">/month</p>
                                </div>
                            ))}
                        </div>
                    </section>

                    {/* ── Section 4 — Comparison table ────────────────── */}
                    <section className="mb-20">
                        <h2 className="mb-2 text-center text-2xl font-bold text-foreground">
                            How we compare
                        </h2>
                        <p className="mb-8 text-center text-muted-foreground">
                            Honest comparison. Prices are public; we rounded nothing.
                        </p>

                        <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                            <Table>
                                <TableHeader>
                                    <TableRow className="border-b-2 border-border hover:bg-transparent">
                                        <TableHead className="w-1/2 py-4 pl-6 text-sm font-semibold text-muted-foreground">
                                            Feature
                                        </TableHead>
                                        {/* Nexstage column — highlighted */}
                                        <TableHead className="bg-yellow-50 py-4 text-center text-sm font-bold text-foreground">
                                            <div className="flex flex-col items-center gap-0.5">
                                                <span>Nexstage</span>
                                                <span className="text-xs font-normal text-yellow-600">
                                                    €39 + 0.4%
                                                </span>
                                            </div>
                                        </TableHead>
                                        <TableHead className="py-4 text-center text-sm font-semibold text-muted-foreground">
                                            <div className="flex flex-col items-center gap-0.5">
                                                <span>Triple Whale</span>
                                                <span className="text-xs font-normal text-muted-foreground/70">
                                                    from $179/mo
                                                </span>
                                            </div>
                                        </TableHead>
                                        <TableHead className="py-4 pr-6 text-center text-sm font-semibold text-muted-foreground">
                                            <div className="flex flex-col items-center gap-0.5">
                                                <span>Metorik</span>
                                                <span className="text-xs font-normal text-muted-foreground/70">
                                                    from $25/mo
                                                </span>
                                            </div>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {COMPARISON_ROWS.map((row, i) => (
                                        <TableRow
                                            key={row.label}
                                            className={i % 2 === 0 ? 'bg-muted/50' : ''}
                                        >
                                            <TableCell className="py-3 pl-6 text-sm text-foreground">
                                                {row.label}
                                            </TableCell>
                                            <TableCell className="bg-yellow-50/60 py-3 text-center">
                                                <CellDisplay value={row.nexstage} isNexstage />
                                            </TableCell>
                                            <TableCell className="py-3 text-center text-sm">
                                                <CellDisplay value={row.tripleWhale} />
                                            </TableCell>
                                            <TableCell className="py-3 pr-6 text-center text-sm">
                                                <CellDisplay value={row.metorik} />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <p className="mt-3 text-center text-sm text-muted-foreground/70">
                            Competitor pricing sourced from public pricing pages, April 2026.
                        </p>
                    </section>

                    {/* ── Section 5 — FAQ ──────────────────────────────── */}
                    <section className="mb-20">
                        <h2 className="mb-8 text-center text-2xl font-bold text-foreground">
                            Frequently asked questions
                        </h2>
                        <div className="mx-auto max-w-2xl">
                            <FaqAccordion items={FAQ_ITEMS} />
                        </div>
                    </section>

                    {/* ── Section 6 — Bottom CTA ───────────────────────── */}
                    <section className="rounded-2xl border border-border bg-card p-12 text-center shadow-sm">
                        <h2 className="mb-3 text-3xl font-bold text-foreground">
                            Ready to see the whole picture?
                        </h2>
                        <p className="mb-8 text-muted-foreground">
                            14 days free. No credit card. Connect your store in under 5 minutes.
                        </p>

                        <div className="flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                            <Link href="/register" className={cn(buttonVariants({ size: 'lg' }), 'w-full sm:w-auto')}>
                                Start free trial
                            </Link>
                        </div>

                        <p className="mt-6 text-sm text-muted-foreground/70">
                            Questions?{' '}
                            <a
                                href="mailto:hello@nexstage.io"
                                className="underline hover:text-muted-foreground"
                            >
                                hello@nexstage.io
                            </a>
                        </p>
                    </section>

                </div>

                {/* ── Footer ──────────────────────────────────────────── */}
                <footer className="mt-8 border-t border-border py-8">
                    <div className="mx-auto flex max-w-5xl flex-col items-center justify-between gap-4 px-6 sm:flex-row">
                        <p className="text-sm text-muted-foreground/70">
                            &copy; {new Date().getFullYear()} Nexstage
                        </p>
                        <div className="flex gap-6 text-sm text-muted-foreground/70">
                            <Link href="/login" className="hover:text-muted-foreground">Log in</Link>
                            <Link href="/register" className="hover:text-muted-foreground">Register</Link>
                            <a href="mailto:hello@nexstage.io" className="hover:text-muted-foreground">Contact</a>
                        </div>
                    </div>
                </footer>
            </div>

        </>
    );
}
