import { Head } from '@inertiajs/react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';

// ─── Section ─────────────────────────────────────────────────────────────────

function Section({ id, title, children }: { id: string; title: string; children: React.ReactNode }) {
    return (
        <section id={id} className="scroll-mt-6">
            <h2 className="mb-3 text-base font-semibold text-zinc-900">{title}</h2>
            <div className="space-y-2 text-sm leading-relaxed text-zinc-600">{children}</div>
        </section>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function DataAccuracy() {
    return (
        <AppLayout>
            <Head title="Data Accuracy FAQ" />
            <PageHeader
                title="Data Accuracy FAQ"
                subtitle="Why numbers sometimes don't match — and what to expect"
            />

            <div className="mx-auto max-w-2xl space-y-8 rounded-xl border border-zinc-200 bg-white p-6 sm:p-8">

                <Section id="order-counts" title="Why are my order counts different from WooCommerce?">
                    <p>
                        Nexstage receives new orders in real time via WooCommerce webhooks. Webhooks can
                        occasionally be delayed or missed — for example, if your server was briefly
                        unavailable or under heavy load at the moment the order was placed.
                    </p>
                    <p>
                        To catch any gaps, a nightly reconciliation job re-fetches all orders from the
                        last 7 days and backfills any that are missing. This means a discrepancy spotted
                        during the day will usually resolve itself overnight.
                    </p>
                    <p>
                        If a persistent gap remains after 24 hours, you can trigger a manual re-sync from{' '}
                        <strong>Settings → Integrations</strong>.
                    </p>
                </Section>

                <div className="border-t border-zinc-100" />

                <Section id="roas" title="Why is Platform ROAS different from Real ROAS?">
                    <p>
                        <strong>Platform ROAS</strong> is what Meta or Google report in their dashboards.
                        It uses pixel-based (last-touch) attribution — the platform claims credit for a
                        conversion whenever a user previously clicked or viewed an ad, regardless of
                        whether the order contains any UTM parameters.
                    </p>
                    <p>
                        <strong>Real ROAS</strong> is calculated from your actual WooCommerce orders that
                        contain <code className="rounded bg-zinc-100 px-1 py-0.5 font-mono text-xs">utm_campaign</code>{' '}
                        parameters matching this campaign (case-insensitive). This is the revenue you can
                        directly attribute to the campaign based on first-party order data.
                    </p>
                    <p>
                        The gap between the two is normal. Meta and Google often both claim credit for the
                        same order, so summing their Platform ROAS figures will overcount. Real ROAS is the
                        more conservative, reliable measure.
                    </p>
                    <p>
                        Note: UTM attribution only works if your ad links include{' '}
                        <code className="rounded bg-zinc-100 px-1 py-0.5 font-mono text-xs">utm_campaign</code>{' '}
                        parameters and your WooCommerce store captures them in the order meta.
                    </p>
                </Section>

                <div className="border-t border-zinc-100" />

                <Section id="gsc-lag" title="Why is Google Search Console data incomplete for recent days?">
                    <p>
                        Google Search Console has an inherent 2–3 day reporting lag. Data for the last
                        3 days is typically partial — Google is still collecting and processing clicks and
                        impressions for those dates.
                    </p>
                    <p>
                        Nexstage re-syncs the last 5 days on every run to capture updates as they become
                        available. Figures for dates older than 3 days are considered final and will not
                        change significantly.
                    </p>
                    <p>
                        When the date range you have selected includes the last 3 days, a warning banner
                        is shown on the SEO page as a reminder.
                    </p>
                </Section>

                <div className="border-t border-zinc-100" />

                <Section id="revenue" title="Why don't revenue totals match my WooCommerce dashboard?">
                    <p>
                        There are a few common reasons:
                    </p>
                    <ul className="ml-4 list-disc space-y-1.5">
                        <li>
                            <strong>Currency conversion.</strong> If your store uses a different currency
                            than your reporting currency, Nexstage converts amounts using end-of-day
                            exchange rates stored in our database. Orders where no exchange rate was
                            available for that day are excluded from totals (an amber warning will appear).
                        </li>
                        <li>
                            <strong>Order status.</strong> Nexstage counts orders in{' '}
                            <code className="rounded bg-zinc-100 px-1 py-0.5 font-mono text-xs">completed</code>{' '}
                            or{' '}
                            <code className="rounded bg-zinc-100 px-1 py-0.5 font-mono text-xs">processing</code>{' '}
                            status. Pending, on-hold, cancelled, and refunded orders are excluded.
                        </li>
                        <li>
                            <strong>Refunds.</strong> Partially refunded orders show the original order
                            total minus refunds. Fully refunded orders are excluded.
                        </li>
                        <li>
                            <strong>Snapshot timing.</strong> Revenue charts are built from daily snapshots
                            computed nightly. Orders placed today appear after the next snapshot run
                            (usually around 02:00 UTC).
                        </li>
                    </ul>
                </Section>

                <div className="border-t border-zinc-100" />

                <Section id="ad-metrics" title="Why do Facebook and Google ad metrics sometimes change retroactively?">
                    <p>
                        Both platforms revise recent ad metrics for up to 3 days after reporting. View
                        counts, conversion windows, and attribution adjustments can all cause numbers to
                        shift for the last 1–3 days.
                    </p>
                    <p>
                        Nexstage re-syncs the last 3 days of ad data on every run to capture these
                        corrections. If you see a number change on a recently-completed day, this is
                        expected behaviour.
                    </p>
                </Section>

            </div>
        </AppLayout>
    );
}
