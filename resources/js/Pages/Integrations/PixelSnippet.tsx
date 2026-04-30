import { useState, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { Copy, Check, Code2, ShieldCheck, Zap, FlaskConical, RefreshCw } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { SectionCard } from '@/Components/shared/SectionCard';
import { SubNavTabs } from '@/Components/shared/SubNavTabs';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Props {
    workspaceSlug: string;
    pixelTrackingToken: string;
    endpointBase: string;
}

// ─── Snippet builder ──────────────────────────────────────────────────────────

/**
 * Generates the JS snippet that merchants paste into their storefront.
 *
 * The snippet:
 *  - Uses a queue-based loader so it is non-blocking.
 *  - Auto-fires a `page_view` on load.
 *  - Exposes `nx('event', type, payload)` for custom events.
 *  - Generates a UUID-based event_id per event for server-side dedup.
 *  - Persists a session_id in sessionStorage for session reconstruction.
 */
function buildSnippet(endpointBase: string, workspaceSlug: string, token: string): string {
    const endpoint = `${endpointBase}/pixel/${workspaceSlug}/event?token=${token}`;
    return `<!-- Nexstage Server-Side Pixel — paste before </body> -->
<script>
(function(w,d){
  // Queue: collects calls made before the snippet fires.
  w.nx = w.nx || function(){(w._nxq = w._nxq || []).push(arguments);};

  // Session ID — persisted for the browser session to correlate events.
  var sid = sessionStorage.getItem('_nx_sid');
  if (!sid) { sid = crypto.randomUUID(); sessionStorage.setItem('_nx_sid', sid); }

  // Fire a single pixel event to the Nexstage endpoint.
  function fire(type, payload) {
    var body = Object.assign({
      event_id:   crypto.randomUUID(),
      event_type: type,
      occurred_at: new Date().toISOString(),
      session_id: sid,
      url:        location.href,
      referrer:   document.referrer || null,
      utm_source:   new URLSearchParams(location.search).get('utm_source'),
      utm_medium:   new URLSearchParams(location.search).get('utm_medium'),
      utm_campaign: new URLSearchParams(location.search).get('utm_campaign'),
      utm_content:  new URLSearchParams(location.search).get('utm_content'),
      utm_term:     new URLSearchParams(location.search).get('utm_term'),
    }, payload || {});
    navigator.sendBeacon
      ? navigator.sendBeacon('${endpoint}', new Blob([JSON.stringify(body)], {type:'application/json'}))
      : fetch('${endpoint}', {method:'POST', body:JSON.stringify(body), headers:{'Content-Type':'application/json'}, keepalive:true});
  }

  // Drain the queue once the snippet is ready.
  w.nx = function(cmd, type, payload){ if (cmd === 'event') fire(type, payload); };
  (w._nxq || []).forEach(function(a){ w.nx.apply(w, a); }); w._nxq = [];

  // Auto page_view on load.
  fire('page_view', {});
})(window, document);
</script>`;
}

// ─── Copy button ──────────────────────────────────────────────────────────────

function CopyButton({ text, label = 'Copy' }: { text: string; label?: string }) {
    const [copied, setCopied] = useState(false);
    function handleCopy() {
        navigator.clipboard.writeText(text).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    }
    return (
        <button
            onClick={handleCopy}
            className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-white px-3 py-1.5 text-xs font-medium text-foreground shadow-sm transition hover:bg-muted/50 active:scale-95"
        >
            {copied
                ? <><Check className="h-3.5 w-3.5 text-green-600" /> Copied</>
                : <><Copy className="h-3.5 w-3.5" /> {label}</>
            }
        </button>
    );
}

// ─── Recent Events Feed ───────────────────────────────────────────────────────

interface RecentEvent {
    event_type: string;
    occurred_at: string | null;
    url_path: string;
    utm_source: string | null;
    utm_medium: string | null;
    session_id: string | null;
}

const EVENT_TYPE_COLORS: Record<string, string> = {
    page_view:      'bg-indigo-100 text-indigo-700',
    add_to_cart:    'bg-blue-100 text-blue-700',
    begin_checkout: 'bg-amber-100 text-amber-700',
    purchase:       'bg-emerald-100 text-emerald-700',
    custom:         'bg-purple-100 text-purple-700',
};

function RecentEventsFeed({ wsSlug }: { wsSlug: string | undefined }) {
    const [events, setEvents] = useState<RecentEvent[] | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const fetchEvents = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const url = wurl(wsSlug, '/integrations/pixel-snippet/recent-events');
            if (!url) { setError('Workspace not resolved.'); return; }
            const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data: { events: RecentEvent[] } = await res.json();
            setEvents(data.events);
        } catch (e) {
            setError('Failed to load recent events.');
        } finally {
            setLoading(false);
        }
    }, [wsSlug]);

    return (
        <SectionCard
            title="Recent events"
            action={
                <button
                    onClick={fetchEvents}
                    disabled={loading}
                    className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-white px-3 py-1.5 text-xs font-medium text-foreground shadow-sm transition hover:bg-muted/50 active:scale-95 disabled:opacity-60"
                >
                    <RefreshCw className={`h-3.5 w-3.5 ${loading ? 'animate-spin' : ''}`} />
                    {loading ? 'Loading…' : 'Refresh'}
                </button>
            }
        >
            <p className="mb-3 text-xs text-muted-foreground">
                Last 10 events received by the pixel endpoint for this workspace. Click Refresh to load.
            </p>
            {error && (
                <div className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{error}</div>
            )}
            {events === null && !loading && !error && (
                <div className="rounded-md border border-dashed border-border px-3 py-4 text-center text-xs text-muted-foreground/70">
                    Click Refresh to load recent events.
                </div>
            )}
            {events !== null && events.length === 0 && (
                <div className="rounded-md border border-dashed border-border px-3 py-4 text-center text-xs text-muted-foreground/70">
                    No events received yet. Paste the snippet into your storefront to start capturing.
                </div>
            )}
            {events !== null && events.length > 0 && (
                <div className="divide-y divide-border rounded-md border border-border overflow-hidden">
                    {events.map((ev, i) => (
                        <div key={i} className="flex items-center gap-3 px-3 py-2 text-xs">
                            <span className={`shrink-0 rounded-full px-2 py-0.5 font-medium ${EVENT_TYPE_COLORS[ev.event_type] ?? 'bg-muted text-muted-foreground'}`}>
                                {ev.event_type}
                            </span>
                            <span className="flex-1 truncate font-mono text-muted-foreground" title={ev.url_path}>
                                {ev.url_path}
                            </span>
                            {ev.utm_source && (
                                <span className="shrink-0 text-muted-foreground/70">
                                    {ev.utm_source}{ev.utm_medium ? `/${ev.utm_medium}` : ''}
                                </span>
                            )}
                            <span className="shrink-0 tabular-nums text-muted-foreground/50">
                                {ev.occurred_at ? new Date(ev.occurred_at).toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '—'}
                            </span>
                        </div>
                    ))}
                </div>
            )}
        </SectionCard>
    );
}

// ─── Test Pixel Button ────────────────────────────────────────────────────────

function TestPixelButton({ endpointBase, workspaceSlug, token }: { endpointBase: string; workspaceSlug: string; token: string }) {
    const [status, setStatus] = useState<'idle' | 'firing' | 'ok' | 'error'>('idle');

    async function fire() {
        setStatus('firing');
        const endpoint = `${endpointBase}/pixel/${workspaceSlug}/event?token=${token}`;
        const body = {
            event_id:    crypto.randomUUID(),
            event_type:  'page_view',
            occurred_at: new Date().toISOString(),
            session_id:  'test-session',
            url:         window.location.href,
            referrer:    null,
            utm_source:  'nexstage_test',
            utm_medium:  'pixel_test',
        };
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                body: JSON.stringify(body),
                headers: { 'Content-Type': 'application/json' },
            });
            setStatus(res.ok || res.status === 204 ? 'ok' : 'error');
        } catch {
            setStatus('error');
        }
        setTimeout(() => setStatus('idle'), 3000);
    }

    const label = { idle: 'Test pixel', firing: 'Firing…', ok: 'Event sent!', error: 'Failed — check token' }[status];
    const cls = {
        idle:    'border-border bg-white text-foreground hover:bg-muted/50',
        firing:  'border-border bg-white text-muted-foreground',
        ok:      'border-emerald-200 bg-emerald-50 text-emerald-700',
        error:   'border-red-200 bg-red-50 text-red-700',
    }[status];

    return (
        <button
            onClick={fire}
            disabled={status === 'firing'}
            className={`inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium shadow-sm transition active:scale-95 disabled:opacity-60 ${cls}`}
        >
            <FlaskConical className="h-3.5 w-3.5" />
            {label}
        </button>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function PixelSnippet({ workspaceSlug, pixelTrackingToken, endpointBase }: Props) {
    const { workspace } = usePage<PageProps>().props;
    const w = (path: string) => wurl(workspace?.slug, path);

    const snippet = buildSnippet(endpointBase, workspaceSlug, pixelTrackingToken);
    const endpointUrl = `${endpointBase}/pixel/${workspaceSlug}/event`;

    const tabs = [
        { label: 'Tag Generator',     value: 'tag-generator',    href: w('/integrations/tag-generator'),    active: false },
        { label: 'Naming Convention', value: 'naming-convention', href: w('/integrations/naming-convention'), active: false },
        { label: 'Channel Mappings',  value: 'channel-mappings',  href: w('/integrations/channel-mappings'),  active: false },
        { label: 'Server-side Pixel', value: 'pixel-snippet',     href: w('/integrations/pixel-snippet'),     active: true  },
    ];

    return (
        <AppLayout>
            <Head title="Server-side Pixel" />

            <div className="space-y-6">
                <PageHeader
                    title="Server-side Pixel"
                    subtitle="Capture page views and conversion events server-side — unaffected by ad-blockers or iOS tracking restrictions."
                />

                <SubNavTabs tabs={tabs} className="mb-6" />

                {/* How it works */}
                <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    {[
                        {
                            icon: <Code2 className="h-5 w-5 text-indigo-600" />,
                            title: 'Paste once',
                            body: 'Add the snippet to your storefront. It auto-fires a page_view and exposes nx() for custom events.',
                        },
                        {
                            icon: <ShieldCheck className="h-5 w-5 text-green-600" />,
                            title: 'Block-proof capture',
                            body: 'Events POST directly to Nexstage — bypassing browser ad-blockers and Safari ITP restrictions.',
                        },
                        {
                            icon: <Zap className="h-5 w-5 text-amber-500" />,
                            title: 'CAPI-ready',
                            body: 'Each event is deduplicated by event_id so it can be forwarded to Facebook CAPI or Google Enhanced Conversions without double-counting.',
                        },
                    ].map((card) => (
                        <div key={card.title} className="rounded-xl border border-border bg-white p-4 shadow-sm">
                            <div className="mb-2 flex items-center gap-2">
                                {card.icon}
                                <span className="text-sm font-semibold text-foreground">{card.title}</span>
                            </div>
                            <p className="text-xs text-muted-foreground">{card.body}</p>
                        </div>
                    ))}
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Left: snippet */}
                    <div className="lg:col-span-2 space-y-5">
                        <SectionCard
                            title="Installation snippet"
                            action={<CopyButton text={snippet} label="Copy snippet" />}
                        >
                            <p className="mb-3 text-xs text-muted-foreground">
                                Paste this before the <code className="font-mono">&lt;/body&gt;</code> tag of every storefront page.
                                For Shopify, add it to <strong>theme.liquid</strong>. For WooCommerce, use the <em>header/footer scripts</em> plugin or a child-theme hook.
                            </p>
                            <pre className="overflow-x-auto rounded-lg bg-zinc-900 p-4 text-sm leading-relaxed text-zinc-200 whitespace-pre-wrap break-all">
                                {snippet}
                            </pre>
                        </SectionCard>

                        {/* Custom event API */}
                        <SectionCard title="Custom event API">
                            <p className="mb-3 text-xs text-muted-foreground">
                                Call <code className="font-mono">nx()</code> anywhere in your storefront JS to send additional events.
                            </p>
                            <pre className="overflow-x-auto rounded-lg bg-zinc-900 p-4 text-sm leading-relaxed text-zinc-200 whitespace-pre-wrap">
{`// Add to cart
nx('event', 'add_to_cart', {
  payload: { product_id: 123, value: 49.99, currency: 'EUR' }
});

// Begin checkout
nx('event', 'begin_checkout', {
  payload: { value: 99.00, currency: 'EUR', num_items: 2 }
});

// Purchase (if not using webhook confirmation)
nx('event', 'purchase', {
  payload: { order_id: '1234', value: 99.00, currency: 'EUR' }
});`}
                            </pre>
                        </SectionCard>
                    </div>

                    {/* Right: config details */}
                    <div className="space-y-5">
                        <SectionCard
                            title="Endpoint"
                            action={
                                <TestPixelButton
                                    endpointBase={endpointBase}
                                    workspaceSlug={workspaceSlug}
                                    token={pixelTrackingToken}
                                />
                            }
                        >
                            <dl className="space-y-3 text-xs">
                                <div>
                                    <dt className="mb-0.5 font-medium text-muted-foreground">URL</dt>
                                    <dd className="break-all font-mono text-foreground">{endpointUrl}</dd>
                                </div>
                                <div>
                                    <dt className="mb-0.5 font-medium text-muted-foreground">Method</dt>
                                    <dd className="font-mono text-foreground">POST</dd>
                                </div>
                                <div>
                                    <dt className="mb-0.5 font-medium text-muted-foreground">Auth token</dt>
                                    <dd className="flex items-center gap-2">
                                        <code className="break-all font-mono text-foreground">{pixelTrackingToken}</code>
                                        <CopyButton text={pixelTrackingToken} label="Copy" />
                                    </dd>
                                </div>
                                <div>
                                    <dt className="mb-0.5 font-medium text-muted-foreground">Content-Type</dt>
                                    <dd className="font-mono text-foreground">application/json</dd>
                                </div>
                            </dl>
                        </SectionCard>

                        <SectionCard title="Event types">
                            <ul className="space-y-1 text-xs text-muted-foreground">
                                {['page_view', 'add_to_cart', 'begin_checkout', 'purchase', 'custom'].map((t) => (
                                    <li key={t} className="flex items-center gap-2">
                                        <span className="h-1.5 w-1.5 rounded-full bg-indigo-400 flex-shrink-0" />
                                        <code className="font-mono">{t}</code>
                                    </li>
                                ))}
                            </ul>
                        </SectionCard>

                        <SectionCard title="Privacy">
                            <p className="text-xs text-muted-foreground">
                                IP addresses are stored as-is and used only for session correlation.
                                A future <em>privacy-strict</em> mode will truncate the last IPv4 octet before storage.
                                No third-party scripts are loaded by the snippet.
                            </p>
                        </SectionCard>
                    </div>
                </div>

                {/* Recent events feed — full width below the grid */}
                <RecentEventsFeed wsSlug={workspace?.slug} />
            </div>
        </AppLayout>
    );
}
