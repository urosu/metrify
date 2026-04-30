import OnboardingLayout from '@/Components/layouts/OnboardingLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { formatGscProperty } from '@/lib/gsc';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';
import { useEffect, useRef, useState } from 'react';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface AdAccount {
    id: string;
    name: string;
    currency: string;
}

interface Pending<T> {
    key: string;
    items: T;
}

interface ImportStatus {
    status: 'pending' | 'running' | 'completed' | 'failed' | null;
    progress: number | null;
    total_orders: number | null;
    started_at: string | null;
    completed_at: string | null;
    duration_seconds: number | null;
    error_message: string | null;
}

interface Props {
    step: 0 | 1 | 2 | 3 | 4 | 5 | 6;
    is_trial?: boolean;
    // step 0
    workspace_name?: string;
    workspace_currency?: string;
    workspace_timezone?: string;
    // step 1
    has_ads?: boolean;
    has_gsc?: boolean;
    fb_pending?: Pending<AdAccount[]> | null;
    gads_pending?: Pending<AdAccount[]> | null;
    gsc_pending?: Pending<string[]> | null;
    oauth_error?: string | null;
    oauth_platform?: string | null;
    has_other_workspaces?: boolean;
    is_workspace_owner?: boolean;
    current_workspace_id?: number;
    // step 2 (country prompt)
    store_id?: number;
    store_name?: string;
    website_url?: string | null;
    country?: string | null;
    ip_detected_country?: string | null;
    // step 6 (progress)
    store_slug?: string;
    workspace_slug?: string;
}

// ---------------------------------------------------------------------------
// Per-connector import windows
// Sources: docs/competitors/_research_onboarding_flow.md §2
// ---------------------------------------------------------------------------

interface ConnectorLimit {
    name: string;
    cap: number;   // months
    note: string;
}

const CONNECTOR_LIMITS: ConnectorLimit[] = [
    { name: 'Store (WooCommerce / Shopify)', cap: 36, note: 'No API ceiling — 36 months default' },
    { name: 'Facebook Ads',                  cap: 36, note: 'API ceiling: 37 months' },
    { name: 'Google Ads',                    cap: 36, note: 'No hard API ceiling — 36 months default' },
    { name: 'Google Search Console',         cap: 16, note: 'API ceiling: 16 months (Google hard limit)' },
    { name: 'GA4',                           cap: 14, note: 'API ceiling: 14 months (free properties)' },
];

// ---------------------------------------------------------------------------
// Shared UI
// ---------------------------------------------------------------------------

function ConnectedBadge() {
    return (
        <span
            className="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
            style={{ background: 'oklch(0.97 0.03 160)', color: 'oklch(0.38 0.10 160)' }}
        >
            <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
            </svg>
            Connected
        </span>
    );
}

function SkipButton({ onClick, disabled, label = 'Skip for now' }: { onClick: () => void; disabled?: boolean; label?: string }) {
    return (
        <div className="mt-4 text-center">
            <button
                type="button"
                onClick={onClick}
                disabled={disabled}
                className="text-sm hover:underline"
                style={{ color: 'var(--color-text-tertiary)' }}
            >
                {label}
            </button>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Step 0 — Welcome: workspace name, currency, timezone
// ---------------------------------------------------------------------------

const COMMON_CURRENCIES = [
    'USD', 'EUR', 'GBP', 'AUD', 'CAD', 'CHF', 'SEK', 'NOK', 'DKK',
    'PLN', 'CZK', 'HUF', 'RON', 'BGN', 'HRK', 'NZD', 'SGD', 'HKD',
    'JPY', 'KRW', 'BRL', 'MXN', 'ZAR', 'INR',
];

const COMMON_TIMEZONES = [
    'Europe/London', 'Europe/Berlin', 'Europe/Paris', 'Europe/Amsterdam',
    'Europe/Stockholm', 'Europe/Warsaw', 'Europe/Bucharest', 'Europe/Athens',
    'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
    'America/Toronto', 'America/Vancouver', 'America/Sao_Paulo', 'America/Mexico_City',
    'Australia/Sydney', 'Australia/Melbourne', 'Pacific/Auckland',
    'Asia/Tokyo', 'Asia/Singapore', 'Asia/Hong_Kong', 'Asia/Dubai',
    'Africa/Johannesburg',
];

function StepWelcome({
    initialName,
    initialCurrency,
    initialTimezone,
    isTrial,
}: {
    initialName: string;
    initialCurrency: string;
    initialTimezone: string;
    isTrial: boolean;
}) {
    const { data, setData, post, processing, errors } = useForm({
        name: initialName,
        reporting_currency: initialCurrency,
        reporting_timezone: initialTimezone,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('onboarding.workspace'));
    }

    return (
        <>
            <Head title="Welcome to Nexstage" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold" style={{ color: 'var(--color-text)' }}>
                    Welcome — let's set up your workspace
                </h1>
                <p className="mt-1.5 text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                    Takes 30 seconds. You can change any of this later in Settings.
                </p>
                {isTrial && (
                    <div
                        className="mt-4 flex items-start gap-2.5 rounded-lg border px-4 py-3 text-sm"
                        style={{ borderColor: 'var(--color-border)', background: 'var(--color-primary-subtle)', color: 'var(--color-primary)' }}
                    >
                        <svg className="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>You're on a <strong>14-day free trial</strong> — no credit card required. Full access to all features.</span>
                    </div>
                )}
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <Label htmlFor="ws-name" className="text-sm font-medium" style={{ color: 'var(--color-text-secondary)' }}>
                        Workspace name
                    </Label>
                    <Input
                        id="ws-name"
                        type="text"
                        placeholder="Acme Coffee Co"
                        value={data.name}
                        className="mt-1.5 h-10 text-sm"
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        autoFocus
                        maxLength={80}
                    />
                    {errors.name && <p className="mt-1 text-sm" style={{ color: 'var(--color-destructive)' }}>{errors.name}</p>}
                    <p className="mt-1 text-sm" style={{ color: 'var(--color-text-muted)' }}>
                        Usually your store or brand name.
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="ws-currency" className="text-sm font-medium" style={{ color: 'var(--color-text-secondary)' }}>
                            Reporting currency
                        </Label>
                        <select
                            id="ws-currency"
                            value={data.reporting_currency}
                            onChange={(e) => setData('reporting_currency', e.target.value)}
                            className="mt-1.5 w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-1"
                            style={{
                                borderColor: 'var(--color-border)',
                                background: 'var(--color-surface)',
                                color: 'var(--color-text)',
                            }}
                        >
                            {COMMON_CURRENCIES.map((c) => (
                                <option key={c} value={c}>{c}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label htmlFor="ws-tz" className="text-sm font-medium" style={{ color: 'var(--color-text-secondary)' }}>
                            Timezone
                        </Label>
                        <select
                            id="ws-tz"
                            value={data.reporting_timezone}
                            onChange={(e) => setData('reporting_timezone', e.target.value)}
                            className="mt-1.5 w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-1"
                            style={{
                                borderColor: 'var(--color-border)',
                                background: 'var(--color-surface)',
                                color: 'var(--color-text)',
                            }}
                        >
                            {COMMON_TIMEZONES.map((tz) => (
                                <option key={tz} value={tz}>{tz.replace('_', ' ')}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <Button type="submit" disabled={processing || !data.name.trim()} className="w-full">
                    {processing ? 'Saving…' : 'Continue →'}
                </Button>
            </form>
        </>
    );
}

// ---------------------------------------------------------------------------
// Step 1 — Connect integrations (store + optional ad platforms + optional GSC/GA4)
// ---------------------------------------------------------------------------

function WooCommerceForm() {
    const { data, setData, post, processing, errors } = useForm({
        domain: '',
        consumer_key: '',
        consumer_secret: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('onboarding.store'));
    }

    return (
        <>
            <p className="mb-4 text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                Go to <strong>WooCommerce → Settings → Advanced → REST API</strong> and create
                a key with <strong>Read/Write</strong> permissions.
            </p>

            <form onSubmit={submit} className="space-y-3">
                <div>
                    <Label htmlFor="domain" className="text-sm">Store URL</Label>
                    <Input
                        id="domain"
                        type="url"
                        placeholder="https://yourstore.com"
                        value={data.domain}
                        className="mt-1.5 h-10 text-sm"
                        onChange={(e) => setData('domain', e.target.value)}
                        required
                        autoFocus
                    />
                    {errors.domain && <p className="mt-1 text-sm" style={{ color: 'var(--color-destructive)' }}>{errors.domain}</p>}
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <Label htmlFor="consumer_key" className="text-sm">Consumer key</Label>
                        <Input
                            id="consumer_key"
                            placeholder="ck_…"
                            value={data.consumer_key}
                            className="mt-1.5 h-10 font-mono text-sm"
                            onChange={(e) => setData('consumer_key', e.target.value)}
                            required
                        />
                        {errors.consumer_key && <p className="mt-1 text-sm" style={{ color: 'var(--color-destructive)' }}>{errors.consumer_key}</p>}
                    </div>
                    <div>
                        <Label htmlFor="consumer_secret" className="text-sm">Consumer secret</Label>
                        <Input
                            id="consumer_secret"
                            type="password"
                            placeholder="cs_…"
                            value={data.consumer_secret}
                            className="mt-1.5 h-10 font-mono text-sm"
                            onChange={(e) => setData('consumer_secret', e.target.value)}
                            required
                        />
                        {errors.consumer_secret && <p className="mt-1 text-sm" style={{ color: 'var(--color-destructive)' }}>{errors.consumer_secret}</p>}
                    </div>
                </div>

                <Button type="submit" disabled={processing} className="w-full">
                    {processing ? 'Connecting…' : 'Connect store'}
                </Button>
            </form>
        </>
    );
}

function ShopifyForm({ workspaceId }: { workspaceId?: number }) {
    const [shop, setShop] = useState('');
    const wsParam = workspaceId ? `&workspace_id=${workspaceId}` : '';

    return (
        <>
            <p className="mb-4 text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                Enter your myshopify.com domain. You'll be redirected to Shopify to approve the
                connection.
            </p>
            <div className="space-y-3">
                <div>
                    <Label htmlFor="shopify-domain" className="text-sm">Shopify domain</Label>
                    <Input
                        id="shopify-domain"
                        type="text"
                        placeholder="my-store.myshopify.com"
                        value={shop}
                        className="mt-1.5 h-10 text-sm"
                        onChange={(e) => setShop(e.target.value)}
                        autoFocus
                    />
                </div>
                <a
                    href={route('shopify.install') + '?shop=' + encodeURIComponent(shop) + '&from=onboarding' + wsParam}
                    className={[
                        'flex w-full items-center justify-center rounded-md px-4 py-2.5 text-sm font-medium transition-colors',
                        shop.trim()
                            ? 'text-white'
                            : 'pointer-events-none cursor-not-allowed',
                    ].join(' ')}
                    style={shop.trim()
                        ? { background: '#008060' }
                        : { background: 'var(--color-muted)', color: 'var(--color-text-muted)' }
                    }
                    aria-disabled={!shop.trim()}
                >
                    Connect with Shopify
                </a>
            </div>
        </>
    );
}

function StoreTile({ workspaceId }: { workspaceId?: number }) {
    const [platform, setPlatform] = useState<'woocommerce' | 'shopify'>('woocommerce');

    return (
        <div className="rounded-xl border bg-white p-5" style={{ borderColor: 'var(--color-border)' }}>
            <div className="mb-4 flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg" style={{ background: 'var(--color-muted)' }}>
                    <svg className="h-5 w-5" style={{ color: 'var(--color-text-secondary)' }} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 2.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                    </svg>
                </div>
                <div>
                    <div className="text-sm font-semibold" style={{ color: 'var(--color-text)' }}>Ecommerce Store</div>
                    <div className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>WooCommerce or Shopify</div>
                </div>
            </div>

            {/* Platform toggle */}
            <div className="mb-4 flex rounded-lg border p-0.5" style={{ borderColor: 'var(--color-border)' }}>
                <button
                    type="button"
                    onClick={() => setPlatform('woocommerce')}
                    className={[
                        'flex flex-1 items-center justify-center gap-1.5 rounded-md py-1.5 text-sm font-medium transition-colors',
                        platform === 'woocommerce' ? 'text-white' : '',
                    ].join(' ')}
                    style={platform === 'woocommerce' ? { background: '#7f54b3' } : { color: 'var(--color-text-secondary)' }}
                >
                    <svg className="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2.047 5.651C2.622 4.866 3.497 4.47 4.674 4.47h14.652c1.177 0 2.052.396 2.627 1.181.574.786.707 1.759.396 2.918l-2.363 9.48c-.265 1.018-.795 1.808-1.59 2.369-.796.562-1.712.844-2.75.844H8.354c-1.037 0-1.953-.282-2.75-.844-.795-.561-1.325-1.35-1.59-2.369L1.651 8.569c-.31-1.16-.178-2.132.396-2.918z" />
                    </svg>
                    WooCommerce
                </button>
                <button
                    type="button"
                    onClick={() => setPlatform('shopify')}
                    className={[
                        'flex flex-1 items-center justify-center gap-1.5 rounded-md py-1.5 text-sm font-medium transition-colors',
                        platform === 'shopify' ? 'text-white' : '',
                    ].join(' ')}
                    style={platform === 'shopify' ? { background: '#008060' } : { color: 'var(--color-text-secondary)' }}
                >
                    <svg className="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M15.337 2.088c-.054-.028-.19-.056-.352-.056-.163 0-.38.027-.569.082C14.225 1.406 13.576.78 12.715.78c-.027 0-.055 0-.081.002C12.443.296 12.036 0 11.575 0c-1.795 0-2.659 2.245-2.93 3.386-.692.214-1.182.366-1.234.383-.383.12-.394.132-.444.495C6.93 4.493 5 18.92 5 18.92L16.754 21 21 19.686S15.391 2.116 15.337 2.088z"/>
                    </svg>
                    Shopify
                </button>
            </div>

            {platform === 'woocommerce' ? <WooCommerceForm /> : <ShopifyForm workspaceId={workspaceId} />}
        </div>
    );
}

function AccountPicker({
    pending,
    pendingField,
    connectRoute,
}: {
    pending: Pending<AdAccount[]>;
    pendingField: string;
    connectRoute: string;
}) {
    const [selected, setSelected] = useState<string[]>(pending.items.map((a) => a.id));
    const [submitting, setSubmitting] = useState(false);

    function toggle(id: string) {
        setSelected((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (selected.length === 0) return;
        setSubmitting(true);
        router.post(
            route(connectRoute),
            { [pendingField]: pending.key, account_ids: selected },
            { onFinish: () => setSubmitting(false) },
        );
    }

    return (
        <form onSubmit={submit} className="mt-3 space-y-2">
            <p className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>Select the accounts to connect:</p>
            {pending.items.map((account) => (
                <label
                    key={account.id}
                    className="flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2 text-sm transition-colors"
                    style={selected.includes(account.id)
                        ? { borderColor: 'var(--color-primary)', background: 'var(--color-primary-subtle)' }
                        : { borderColor: 'var(--color-border)' }
                    }
                >
                    <input
                        type="checkbox"
                        checked={selected.includes(account.id)}
                        onChange={() => toggle(account.id)}
                        className="h-4 w-4 accent-primary"
                    />
                    <span className="flex-1 font-medium" style={{ color: 'var(--color-text)' }}>{account.name}</span>
                    <span className="text-sm" style={{ color: 'var(--color-text-muted)' }}>{account.currency}</span>
                </label>
            ))}
            <Button type="submit" disabled={submitting || selected.length === 0} className="mt-2 w-full" size="sm">
                {submitting ? 'Connecting…' : `Connect ${selected.length} account${selected.length !== 1 ? 's' : ''}`}
            </Button>
        </form>
    );
}

function AdAccountsTile({
    hasAds,
    fbPending,
    gadsPending,
}: {
    hasAds: boolean;
    fbPending: Pending<AdAccount[]> | null | undefined;
    gadsPending: Pending<AdAccount[]> | null | undefined;
}) {
    return (
        <div className="rounded-xl border bg-white p-5" style={{ borderColor: 'var(--color-border)' }}>
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg" style={{ background: 'oklch(0.97 0.02 240)' }}>
                        <svg className="h-5 w-5" style={{ color: 'oklch(0.46 0.15 240)' }} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <div>
                        <div className="text-sm font-semibold" style={{ color: 'var(--color-text)' }}>Ad Accounts</div>
                        <div className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>Facebook & Google Ads — optional</div>
                    </div>
                </div>
                {hasAds && !fbPending && !gadsPending && <ConnectedBadge />}
            </div>

            {fbPending && (
                <div className="mt-3 rounded-lg px-3 py-2" style={{ background: 'oklch(0.97 0.02 240)' }}>
                    <p className="text-sm font-medium" style={{ color: 'oklch(0.35 0.12 240)' }}>Facebook — select accounts to connect</p>
                    <AccountPicker pending={fbPending} pendingField="fb_pending_key" connectRoute="oauth.facebook.connect" />
                </div>
            )}

            {gadsPending && (
                <div className="mt-3 rounded-lg px-3 py-2" style={{ background: 'oklch(0.97 0.02 240)' }}>
                    <p className="text-sm font-medium" style={{ color: 'oklch(0.35 0.12 240)' }}>Google Ads — select accounts to connect</p>
                    <AccountPicker pending={gadsPending} pendingField="gads_pending_key" connectRoute="oauth.google.ads.connect" />
                </div>
            )}

            {(!hasAds || fbPending || gadsPending) && !fbPending && !gadsPending && (
                <div className="mt-4 flex flex-col gap-2">
                    <a
                        href={route('oauth.facebook.redirect') + '?from=onboarding'}
                        className="flex items-center justify-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium transition-colors hover:bg-zinc-50"
                        style={{ borderColor: 'var(--color-border)', color: 'var(--color-text)' }}
                    >
                        <svg className="h-4 w-4" style={{ color: '#1877F2' }} fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                        Connect Facebook Ads
                    </a>
                    <a
                        href={route('oauth.google.ads.redirect') + '?from=onboarding'}
                        className="flex items-center justify-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium transition-colors hover:bg-zinc-50"
                        style={{ borderColor: 'var(--color-border)', color: 'var(--color-text)' }}
                    >
                        <svg className="h-4 w-4" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                        </svg>
                        Connect Google Ads
                    </a>
                </div>
            )}

            {hasAds && !fbPending && !gadsPending && (
                <div className="mt-3 flex gap-3">
                    <a href={route('oauth.facebook.redirect') + '?from=onboarding'} className="text-sm hover:underline" style={{ color: 'var(--color-text-tertiary)' }}>
                        + Add Facebook
                    </a>
                    <span style={{ color: 'var(--color-text-muted)' }}>·</span>
                    <a href={route('oauth.google.ads.redirect') + '?from=onboarding'} className="text-sm hover:underline" style={{ color: 'var(--color-text-tertiary)' }}>
                        + Add Google Ads
                    </a>
                </div>
            )}
        </div>
    );
}

function GscPropertyPicker({ pending }: { pending: Pending<string[]> }) {
    const [selected, setSelected] = useState(pending.items[0] ?? '');
    const [submitting, setSubmitting] = useState(false);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!selected) return;
        setSubmitting(true);
        router.post(
            route('oauth.gsc.connect'),
            { property_url: selected, gsc_pending_key: pending.key },
            { onFinish: () => setSubmitting(false) },
        );
    }

    return (
        <form onSubmit={submit} className="mt-3 space-y-2">
            <p className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>Select a property to connect:</p>
            {pending.items.map((url) => (
                <label
                    key={url}
                    className="flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2 text-sm transition-colors"
                    style={selected === url
                        ? { borderColor: 'var(--color-primary)', background: 'var(--color-primary-subtle)' }
                        : { borderColor: 'var(--color-border)' }
                    }
                >
                    <input
                        type="radio"
                        name="property_url"
                        checked={selected === url}
                        onChange={() => setSelected(url)}
                        className="h-4 w-4 accent-primary"
                    />
                    <span className="flex-1 break-all font-medium" style={{ color: 'var(--color-text)' }}>{formatGscProperty(url)}</span>
                </label>
            ))}
            <Button type="submit" disabled={submitting || !selected} className="mt-2 w-full" size="sm">
                {submitting ? 'Connecting…' : 'Connect property'}
            </Button>
        </form>
    );
}

function GscGa4Tile({
    hasGsc,
    gscPending,
}: {
    hasGsc: boolean;
    gscPending: Pending<string[]> | null | undefined;
}) {
    return (
        <div className="rounded-xl border bg-white p-5" style={{ borderColor: 'var(--color-border)' }}>
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg" style={{ background: 'oklch(0.97 0.03 160)' }}>
                        <svg className="h-5 w-5" style={{ color: 'oklch(0.42 0.12 160)' }} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </div>
                    <div>
                        <div className="text-sm font-semibold" style={{ color: 'var(--color-text)' }}>Organic & Analytics</div>
                        <div className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>Google Search Console & GA4 — optional</div>
                    </div>
                </div>
                {hasGsc && !gscPending && <ConnectedBadge />}
            </div>

            {gscPending && (
                <div className="mt-3 rounded-lg px-3 py-2" style={{ background: 'oklch(0.97 0.03 160)' }}>
                    <p className="text-sm font-medium" style={{ color: 'oklch(0.35 0.10 160)' }}>Select a Search Console property</p>
                    <GscPropertyPicker pending={gscPending} />
                </div>
            )}

            {!hasGsc && !gscPending && (
                <div className="mt-4 flex flex-col gap-2">
                    <a
                        href={route('oauth.google.gsc.redirect') + '?from=onboarding'}
                        className="flex items-center justify-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium transition-colors hover:bg-zinc-50"
                        style={{ borderColor: 'var(--color-border)', color: 'var(--color-text)' }}
                    >
                        <svg className="h-4 w-4" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                        </svg>
                        Connect Google Search Console
                    </a>
                </div>
            )}

            {hasGsc && !gscPending && (
                <div className="mt-3">
                    <a href={route('oauth.google.gsc.redirect') + '?from=onboarding'} className="text-sm hover:underline" style={{ color: 'var(--color-text-tertiary)' }}>
                        + Add another property
                    </a>
                </div>
            )}
        </div>
    );
}

function StepConnect({
    hasAds,
    hasGsc,
    fbPending,
    gadsPending,
    gscPending,
    oauthError,
    hasOtherWorkspaces,
    isWorkspaceOwner,
    currentWorkspaceId,
    workspaceName,
}: {
    hasAds: boolean;
    hasGsc: boolean;
    fbPending: Pending<AdAccount[]> | null | undefined;
    gadsPending: Pending<AdAccount[]> | null | undefined;
    gscPending: Pending<string[]> | null | undefined;
    oauthError: string | null | undefined;
    hasOtherWorkspaces: boolean;
    isWorkspaceOwner: boolean;
    currentWorkspaceId: number | undefined;
    workspaceName: string | undefined;
}) {
    const hasAnyConnection = hasAds || hasGsc;
    const { workspace } = usePage<PageProps>().props;
    const w = (path: string) => wurl(workspace?.slug, path);

    return (
        <>
            <Head title="Connect your integrations" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold" style={{ color: 'var(--color-text)' }}>
                    Connect {workspaceName ? `${workspaceName} to` : 'your store to'} Nexstage
                </h1>
                <p className="mt-1.5 text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                    Start with your store. Ad platforms and analytics are optional — add them any time from Integrations.
                </p>
            </div>

            {oauthError && (
                <div className="mb-4 rounded-lg border px-4 py-3 text-sm" style={{ borderColor: 'var(--color-destructive)', background: 'oklch(0.97 0.02 25)', color: 'var(--color-destructive)' }}>
                    {oauthError}
                </div>
            )}

            <div className="space-y-4">
                <StoreTile workspaceId={currentWorkspaceId} />
                <AdAccountsTile hasAds={hasAds} fbPending={fbPending} gadsPending={gadsPending} />
                <GscGa4Tile hasGsc={hasGsc} gscPending={gscPending} />
            </div>

            {hasAnyConnection && (
                <div className="mt-6 border-t pt-5 text-center" style={{ borderColor: 'var(--color-border)' }}>
                    <Button variant="outline" className="w-full" onClick={() => router.visit(w('/dashboard'))}>
                        Continue to dashboard →
                    </Button>
                    <p className="mt-2 text-sm" style={{ color: 'var(--color-text-muted)' }}>
                        Connect a store later from Settings → Integrations.
                    </p>
                </div>
            )}

            {hasOtherWorkspaces && isWorkspaceOwner && !hasAnyConnection && (
                <div className="mt-6 border-t pt-5 text-center" style={{ borderColor: 'var(--color-border)' }}>
                    <button
                        type="button"
                        onClick={() => router.delete(route('workspaces.discard', { workspace: currentWorkspaceId }))}
                        className="text-sm hover:underline"
                        style={{ color: 'var(--color-text-tertiary)' }}
                    >
                        ← Cancel and go back
                    </button>
                </div>
            )}
        </>
    );
}

// ---------------------------------------------------------------------------
// Step 2 — Store country prompt
// ---------------------------------------------------------------------------

const CCTLD_TO_COUNTRY: Record<string, string> = {
    ac: 'GB', ad: 'AD', ae: 'AE', at: 'AT', au: 'AU', be: 'BE', bg: 'BG',
    br: 'BR', ca: 'CA', ch: 'CH', cn: 'CN', cy: 'CY', cz: 'CZ', de: 'DE',
    dk: 'DK', ee: 'EE', es: 'ES', fi: 'FI', fr: 'FR', gb: 'GB', gr: 'GR',
    hr: 'HR', hu: 'HU', ie: 'IE', it: 'IT', jp: 'JP', kr: 'KR', lt: 'LT',
    lu: 'LU', lv: 'LV', mt: 'MT', mx: 'MX', nl: 'NL', no: 'NO', nz: 'NZ',
    pl: 'PL', pt: 'PT', ro: 'RO', ru: 'RU', se: 'SE', si: 'SI', sk: 'SK',
    tr: 'TR', ua: 'UA', uk: 'GB',
};

const COUNTRY_OPTIONS: { code: string; name: string }[] = [
    { code: 'AD', name: 'Andorra' },       { code: 'AE', name: 'UAE' },
    { code: 'AT', name: 'Austria' },        { code: 'AU', name: 'Australia' },
    { code: 'BE', name: 'Belgium' },        { code: 'BG', name: 'Bulgaria' },
    { code: 'BR', name: 'Brazil' },         { code: 'CA', name: 'Canada' },
    { code: 'CH', name: 'Switzerland' },    { code: 'CN', name: 'China' },
    { code: 'CY', name: 'Cyprus' },         { code: 'CZ', name: 'Czech Republic' },
    { code: 'DE', name: 'Germany' },        { code: 'DK', name: 'Denmark' },
    { code: 'EE', name: 'Estonia' },        { code: 'ES', name: 'Spain' },
    { code: 'FI', name: 'Finland' },        { code: 'FR', name: 'France' },
    { code: 'GB', name: 'United Kingdom' }, { code: 'GR', name: 'Greece' },
    { code: 'HR', name: 'Croatia' },        { code: 'HU', name: 'Hungary' },
    { code: 'IE', name: 'Ireland' },        { code: 'IT', name: 'Italy' },
    { code: 'JP', name: 'Japan' },          { code: 'KR', name: 'South Korea' },
    { code: 'LT', name: 'Lithuania' },      { code: 'LU', name: 'Luxembourg' },
    { code: 'LV', name: 'Latvia' },         { code: 'MT', name: 'Malta' },
    { code: 'MX', name: 'Mexico' },         { code: 'NL', name: 'Netherlands' },
    { code: 'NO', name: 'Norway' },         { code: 'NZ', name: 'New Zealand' },
    { code: 'PL', name: 'Poland' },         { code: 'PT', name: 'Portugal' },
    { code: 'RO', name: 'Romania' },        { code: 'RU', name: 'Russia' },
    { code: 'SE', name: 'Sweden' },         { code: 'SI', name: 'Slovenia' },
    { code: 'SK', name: 'Slovakia' },       { code: 'TR', name: 'Turkey' },
    { code: 'UA', name: 'Ukraine' },        { code: 'US', name: 'United States' },
];

function detectCountryFromUrl(url: string | null): string | null {
    if (!url) return null;
    try {
        const hostname = new URL(url.startsWith('http') ? url : `https://${url}`).hostname;
        const parts = hostname.split('.');
        const tld = parts[parts.length - 1].toLowerCase();
        if (parts.length >= 3 && parts[parts.length - 2].toLowerCase() === 'co') {
            if (CCTLD_TO_COUNTRY[tld]) return CCTLD_TO_COUNTRY[tld];
        }
        return CCTLD_TO_COUNTRY[tld] ?? null;
    } catch {
        return null;
    }
}

function StepCountry({
    storeId,
    storeName,
    websiteUrl,
    initialCountry,
    ipDetectedCountry,
}: {
    storeId: number;
    storeName: string;
    websiteUrl: string | null;
    initialCountry: string | null;
    ipDetectedCountry: string | null;
}) {
    const detected = detectCountryFromUrl(websiteUrl);
    const [selected, setSelected] = useState<string>(initialCountry ?? detected ?? ipDetectedCountry ?? '');
    const [processing, setProcessing] = useState(false);

    function handleSave(e: React.FormEvent) {
        e.preventDefault();
        setProcessing(true);
        router.post(
            route('onboarding.country'),
            { store_id: storeId, country_code: selected || null },
            { onFinish: () => setProcessing(false) },
        );
    }

    function handleSkip() {
        setProcessing(true);
        router.post(
            route('onboarding.country'),
            { store_id: storeId, country_code: null },
            { onFinish: () => setProcessing(false) },
        );
    }

    return (
        <>
            <Head title="Store country" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold" style={{ color: 'var(--color-text)' }}>
                    Where does {storeName} mainly sell?
                </h1>
                <p className="mt-1.5 text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                    Used as a fallback when ad campaign names don't include a country code.
                    Multi-country stores can skip this.
                </p>
            </div>

            {detected && !initialCountry && (
                <p className="mb-4 text-sm" style={{ color: 'var(--color-text-muted)' }}>
                    Detected from <span className="font-mono" style={{ color: 'var(--color-text-tertiary)' }}>{websiteUrl}</span>
                </p>
            )}

            <form onSubmit={handleSave} className="space-y-5">
                <div>
                    <label className="mb-1.5 block text-sm font-medium" style={{ color: 'var(--color-text-secondary)' }}>
                        Primary country
                    </label>
                    <select
                        value={selected}
                        onChange={(e) => setSelected(e.target.value)}
                        className="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-1"
                        style={{
                            borderColor: 'var(--color-border)',
                            background: 'var(--color-surface)',
                            color: 'var(--color-text)',
                        }}
                    >
                        <option value="">Select a country…</option>
                        {COUNTRY_OPTIONS.map((c) => (
                            <option key={c.code} value={c.code}>{c.name} ({c.code})</option>
                        ))}
                    </select>
                </div>

                <Button type="submit" className="w-full" disabled={!selected || processing}>
                    Continue →
                </Button>
            </form>

            <SkipButton onClick={handleSkip} disabled={processing} />
        </>
    );
}

// ---------------------------------------------------------------------------
// Step 4 — Set costs (COGS + margin) — optional, deferrable
// ---------------------------------------------------------------------------

function StepCosts({ storeId, storeName }: { storeId: number; storeName: string }) {
    const [cogsPct, setCogsPct] = useState('');
    const [processing, setProcessing] = useState(false);

    function handleSave(e: React.FormEvent) {
        e.preventDefault();
        setProcessing(true);
        router.post(
            route('onboarding.costs'),
            { default_cogs_pct: cogsPct !== '' ? Number(cogsPct) : null },
            { onFinish: () => setProcessing(false) },
        );
    }

    function handleSkip() {
        setProcessing(true);
        router.post(
            route('onboarding.costs'),
            { default_cogs_pct: null },
            { onFinish: () => setProcessing(false) },
        );
    }

    return (
        <>
            <Head title="Set costs" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold" style={{ color: 'var(--color-text)' }}>
                    Set a default product cost
                </h1>
                <p className="mt-1.5 text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                    A COGS estimate unlocks profit and margin metrics for{' '}
                    <strong style={{ color: 'var(--color-text)' }}>{storeName}</strong>. You can
                    set per-product costs later in Products → Cost Setup.
                </p>
            </div>

            <div
                className="mb-5 flex items-start gap-2.5 rounded-lg border px-4 py-3 text-sm"
                style={{ borderColor: 'var(--color-border)', background: 'var(--color-muted)', color: 'var(--color-text-secondary)' }}
            >
                <svg className="mt-0.5 h-4 w-4 shrink-0" style={{ color: 'var(--color-text-tertiary)' }} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>
                    A rough estimate (e.g. 40% COGS) is better than nothing. Gross profit
                    and contribution margin will populate immediately. You can refine per-SKU later.
                </span>
            </div>

            <form onSubmit={handleSave} className="space-y-5">
                <div>
                    <Label htmlFor="cogs-pct" className="text-sm font-medium" style={{ color: 'var(--color-text-secondary)' }}>
                        Default COGS % of revenue
                    </Label>
                    <div className="relative mt-1.5">
                        <Input
                            id="cogs-pct"
                            type="number"
                            min={0}
                            max={100}
                            step={1}
                            placeholder="e.g. 40"
                            value={cogsPct}
                            className="h-10 pr-8 text-sm"
                            onChange={(e) => setCogsPct(e.target.value)}
                        />
                        <span
                            className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm"
                            style={{ color: 'var(--color-text-tertiary)' }}
                        >
                            %
                        </span>
                    </div>
                    <p className="mt-1 text-sm" style={{ color: 'var(--color-text-muted)' }}>
                        Leave blank to skip — profit metrics will show "N/A" until set.
                    </p>
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    {processing ? 'Saving…' : 'Save and continue →'}
                </Button>
            </form>

            <SkipButton onClick={handleSkip} disabled={processing} label="Skip — I'll set costs later" />
        </>
    );
}

// ---------------------------------------------------------------------------
// Step 5 — Choose historical import window
// ---------------------------------------------------------------------------

// Per-connector ceiling info shown in the UI disclosure.
// Sources: docs/competitors/_research_onboarding_flow.md §2
const PERIOD_OPTIONS = [
    {
        months: 6,
        label: '6 months',
        description: 'Quick start — covers recent peaks and troughs.',
        trialOnly: false,
    },
    {
        months: 12,
        label: '12 months',
        description: 'Full year for seasonal comparisons (YoY).',
        trialOnly: false,
    },
    {
        months: 24,
        label: '24 months',
        description: 'Two years — solid cohort and LTV data.',
        trialOnly: false,
    },
    {
        months: 36,
        label: '36 months (recommended)',
        description: 'Maximum for Facebook & store data. Best for attribution.',
        trialOnly: false,
    },
] as const;

function StepImportWindow({
    storeId,
    storeName,
    isTrial,
}: {
    storeId: number;
    storeName: string;
    isTrial: boolean;
}) {
    // Default: 36 months for paid, 6 months for trial.
    const defaultMonths = isTrial ? 6 : 36;
    const [selectedMonths, setSelectedMonths] = useState(defaultMonths);
    const [processing, setProcessing] = useState(false);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        setProcessing(true);
        router.post(
            route('onboarding.import'),
            { store_id: storeId, months: selectedMonths },
            { onFinish: () => setProcessing(false) },
        );
    }

    const trialCap = 6; // months, matches controller cap

    return (
        <>
            <Head title="Choose import range" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold" style={{ color: 'var(--color-text)' }}>
                    How much history should we import?
                </h1>
                <p className="mt-1.5 text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                    Importing for <strong style={{ color: 'var(--color-text)' }}>{storeName}</strong>.
                    Larger ranges take longer but give more accurate cohort and attribution data.
                </p>
            </div>

            {/* Trial notice */}
            {isTrial && (
                <div
                    className="mb-5 flex items-start gap-2.5 rounded-lg border px-4 py-3 text-sm"
                    style={{ borderColor: 'var(--color-border)', background: 'var(--color-muted)', color: 'var(--color-text-secondary)' }}
                >
                    <svg className="mt-0.5 h-4 w-4 shrink-0" style={{ color: 'var(--color-text-tertiary)' }} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>
                        <strong>Free trial imports up to {trialCap} months.</strong>{' '}
                        Upgrade to a paid plan to import up to 36 months of store and ad data.
                    </span>
                </div>
            )}

            <form onSubmit={submit} className="space-y-3">
                {PERIOD_OPTIONS.filter((p) => !isTrial || p.months <= trialCap).map((p) => {
                    const active = selectedMonths === p.months;
                    return (
                        <label
                            key={p.months}
                            className="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors"
                            style={active
                                ? { borderColor: 'var(--color-primary)', background: 'var(--color-primary-subtle)' }
                                : { borderColor: 'var(--color-border)' }
                            }
                        >
                            <input
                                type="radio"
                                name="months"
                                value={p.months}
                                checked={active}
                                onChange={() => setSelectedMonths(p.months)}
                                className="mt-0.5 h-4 w-4 accent-primary"
                            />
                            <div>
                                <div className="text-sm font-medium" style={{ color: 'var(--color-text)' }}>
                                    {p.label}
                                </div>
                                <div className="mt-0.5 text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                                    {p.description}
                                </div>
                            </div>
                        </label>
                    );
                })}

                {/* Per-connector cap disclosure */}
                <details className="mt-4">
                    <summary
                        className="cursor-pointer select-none text-sm"
                        style={{ color: 'var(--color-text-tertiary)' }}
                    >
                        Per-connector API limits
                    </summary>
                    <div className="mt-2 space-y-1 rounded-lg border p-3" style={{ borderColor: 'var(--color-border)', background: 'var(--color-muted)' }}>
                        {CONNECTOR_LIMITS.map((c) => (
                            <div key={c.name} className="flex items-baseline justify-between gap-2 text-sm">
                                <span style={{ color: 'var(--color-text-secondary)' }}>{c.name}</span>
                                <span className="shrink-0 tabular-nums" style={{ color: 'var(--color-text-tertiary)' }}>
                                    up to {c.cap}mo
                                </span>
                            </div>
                        ))}
                        <p className="mt-2 text-sm" style={{ color: 'var(--color-text-muted)' }}>
                            GSC is hard-capped at 16 months by Google. GA4 free properties store up to
                            14 months. Facebook's ceiling is 37 months — we default to 36 for safety.
                        </p>
                    </div>
                </details>

                <div className="pt-2">
                    <Button type="submit" disabled={processing} className="w-full">
                        {processing ? 'Starting import…' : `Import ${selectedMonths} months →`}
                    </Button>
                </div>
            </form>

            <div className="mt-4 border-t pt-4 text-center" style={{ borderColor: 'var(--color-border)' }}>
                <button
                    type="button"
                    onClick={() => router.post(route('onboarding.reset'))}
                    className="text-sm hover:underline"
                    style={{ color: 'var(--color-text-tertiary)' }}
                >
                    ← Start over
                </button>
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// Step 6 — Import progress polling (Lifetimely pattern: explicit count + %)
// ---------------------------------------------------------------------------

function formatDuration(seconds: number): string {
    if (seconds < 60) return `${seconds}s`;
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return s > 0 ? `${m}m ${s}s` : `${m}m`;
}

function formatEstimate(totalOrders: number, startedAt: string, progress: number): string {
    if (progress <= 0) {
        // Very rough: 1 min per 1000 orders
        const est = Math.ceil(totalOrders / 1000);
        return est <= 1 ? '~1 min' : `~${est} min`;
    }
    const elapsed = (Date.now() - new Date(startedAt).getTime()) / 1000;
    const totalEst = (elapsed / progress) * 100;
    const remaining = Math.max(0, totalEst - elapsed);
    if (remaining < 60) return 'less than a minute';
    return `~${Math.ceil(remaining / 60)} min`;
}

function StepProgress({ storeSlug, workspaceSlug }: { storeSlug: string; workspaceSlug: string }) {
    const w = (path: string) => wurl(workspaceSlug, path);
    const [status, setStatus] = useState<ImportStatus>({
        status: null,
        progress: null,
        total_orders: null,
        started_at: null,
        completed_at: null,
        duration_seconds: null,
        error_message: null,
    });
    const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

    useEffect(() => {
        function poll() {
            fetch(w(`/api/stores/${storeSlug}/import-status`), {
                headers: { Accept: 'application/json' },
            })
                .then((r) => r.json())
                .then((data: ImportStatus) => {
                    setStatus(data);
                    if (data.status === 'completed') {
                        if (intervalRef.current) clearInterval(intervalRef.current);
                        // Small delay before redirect so user sees 100%
                        setTimeout(() => router.visit(w('/dashboard')), 1200);
                    } else if (data.status === 'failed') {
                        if (intervalRef.current) clearInterval(intervalRef.current);
                    }
                })
                .catch(() => {
                    // Network error — keep polling
                });
        }

        poll();
        intervalRef.current = setInterval(poll, 5000);
        return () => { if (intervalRef.current) clearInterval(intervalRef.current); };
    }, [storeSlug]);

    const progress = status.progress ?? 0;
    const isFailed = status.status === 'failed';
    const isCompleted = status.status === 'completed';

    return (
        <>
            <Head title="Importing data" />

            <div className="mb-6">
                <h1 className="text-xl font-semibold" style={{ color: 'var(--color-text)' }}>
                    {isFailed ? 'Import failed' : isCompleted ? 'Import complete!' : 'Importing your history…'}
                </h1>
                {!isFailed && !isCompleted && (
                    <p className="mt-1.5 text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                        The import runs in the background. You can close this tab — your data will be ready when you return.
                    </p>
                )}
            </div>

            {isFailed ? (
                <div className="space-y-4">
                    <div className="rounded-lg border px-4 py-3 text-sm" style={{ borderColor: 'var(--color-destructive)', background: 'oklch(0.97 0.02 25)', color: 'var(--color-destructive)' }}>
                        {status.error_message ?? 'An unexpected error occurred. Please try again.'}
                    </div>
                    <Button onClick={() => router.post(route('onboarding.import.reset'))} className="w-full">
                        Try again
                    </Button>
                    <div className="text-center">
                        <button
                            onClick={() => router.post(route('onboarding.reset'))}
                            className="text-sm hover:underline"
                            style={{ color: 'var(--color-text-tertiary)' }}
                        >
                            ← Start over
                        </button>
                    </div>
                </div>
            ) : (
                <div className="space-y-5">
                    {/* Progress bar — Lifetimely explicit count pattern */}
                    <div>
                        <div className="mb-1.5 flex items-center justify-between text-sm">
                            <span style={{ color: 'var(--color-text-secondary)' }}>
                                {status.status === 'pending'
                                    ? 'Starting…'
                                    : isCompleted
                                      ? 'Done!'
                                      : progress >= 80
                                        ? 'Computing snapshots…'
                                        : 'Importing orders'}
                            </span>
                            <span className="tabular-nums font-medium" style={{ color: 'var(--color-text)' }}>
                                {progress}%
                            </span>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full" style={{ background: 'var(--color-muted)' }}>
                            <div
                                className="h-full rounded-full transition-all duration-500"
                                style={{ width: `${progress}%`, background: 'var(--color-primary)' }}
                            />
                        </div>
                    </div>

                    {/* Stats grid */}
                    <div className="grid grid-cols-2 gap-4">
                        {status.total_orders != null && (
                            <div className="rounded-lg border p-3" style={{ borderColor: 'var(--color-border)' }}>
                                <div className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>Orders to import</div>
                                <div className="mt-0.5 text-base font-medium tabular-nums" style={{ color: 'var(--color-text)' }}>
                                    {status.total_orders.toLocaleString()}
                                </div>
                            </div>
                        )}
                        {status.started_at && status.total_orders && !isCompleted && progress < 100 && (
                            <div className="rounded-lg border p-3" style={{ borderColor: 'var(--color-border)' }}>
                                <div className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>Est. time remaining</div>
                                <div className="mt-0.5 text-base font-medium" style={{ color: 'var(--color-text)' }}>
                                    {formatEstimate(status.total_orders, status.started_at, progress)}
                                </div>
                            </div>
                        )}
                        {status.duration_seconds !== null && (
                            <div className="rounded-lg border p-3" style={{ borderColor: 'var(--color-border)' }}>
                                <div className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>Duration</div>
                                <div className="mt-0.5 text-base font-medium tabular-nums" style={{ color: 'var(--color-text)' }}>
                                    {formatDuration(status.duration_seconds)}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Demo data nudge — Putler pattern */}
                    <div
                        className="flex items-start gap-2.5 rounded-lg border px-4 py-3 text-sm"
                        style={{ borderColor: 'var(--color-border)', background: 'var(--color-primary-subtle)', color: 'var(--color-primary)' }}
                    >
                        <svg className="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <span>
                            Your dashboard is available now with demo data. Your real data will populate as it imports.
                        </span>
                    </div>

                    <button
                        onClick={() => router.visit(w('/dashboard'))}
                        className="w-full rounded-lg border py-2.5 text-sm font-medium transition-colors hover:bg-zinc-50"
                        style={{ borderColor: 'var(--color-border)', color: 'var(--color-text)' }}
                    >
                        Go to dashboard →
                    </button>
                </div>
            )}
        </>
    );
}

// ---------------------------------------------------------------------------
// Root page component
// ---------------------------------------------------------------------------

// Step labels for the progress indicator in OnboardingLayout.
// Steps 0-6 map to the 7 steps; the layout shows a condensed 5-step indicator.
const STEP_META: Record<number, { layoutStep: 1 | 2 | 3 | 4 | 5; label: string }> = {
    0: { layoutStep: 1, label: 'Welcome' },
    1: { layoutStep: 2, label: 'Connect store' },
    2: { layoutStep: 3, label: 'Store setup' },
    4: { layoutStep: 4, label: 'Costs' },
    5: { layoutStep: 4, label: 'Import window' },
    6: { layoutStep: 5, label: 'Importing' },
};

export default function OnboardingIndex({
    step,
    is_trial,
    workspace_name,
    workspace_currency,
    workspace_timezone,
    has_ads,
    has_gsc,
    fb_pending,
    gads_pending,
    gsc_pending,
    oauth_error,
    oauth_platform: _oauth_platform,
    store_id,
    store_slug,
    workspace_slug,
    store_name,
    website_url,
    country,
    ip_detected_country,
    has_other_workspaces,
    is_workspace_owner,
    current_workspace_id,
}: Props) {
    const meta = STEP_META[step] ?? { layoutStep: 1 as const, label: 'Welcome' };

    return (
        <OnboardingLayout currentStep={meta.layoutStep}>
            {step === 0 && (
                <StepWelcome
                    initialName={workspace_name ?? ''}
                    initialCurrency={workspace_currency ?? 'EUR'}
                    initialTimezone={workspace_timezone ?? 'Europe/Berlin'}
                    isTrial={is_trial ?? false}
                />
            )}
            {step === 1 && (
                <StepConnect
                    hasAds={has_ads ?? false}
                    hasGsc={has_gsc ?? false}
                    fbPending={fb_pending}
                    gadsPending={gads_pending}
                    gscPending={gsc_pending}
                    oauthError={oauth_error}
                    hasOtherWorkspaces={has_other_workspaces ?? false}
                    isWorkspaceOwner={is_workspace_owner ?? false}
                    currentWorkspaceId={current_workspace_id}
                    workspaceName={workspace_name}
                />
            )}
            {step === 2 && store_id && (
                <StepCountry
                    storeId={store_id}
                    storeName={store_name ?? ''}
                    websiteUrl={website_url ?? null}
                    initialCountry={country ?? null}
                    ipDetectedCountry={ip_detected_country ?? null}
                />
            )}
            {step === 4 && store_id && (
                <StepCosts storeId={store_id} storeName={store_name ?? ''} />
            )}
            {step === 5 && store_id && (
                <StepImportWindow
                    storeId={store_id}
                    storeName={store_name ?? ''}
                    isTrial={is_trial ?? false}
                />
            )}
            {step === 6 && store_slug && workspace_slug && (
                <StepProgress storeSlug={store_slug} workspaceSlug={workspace_slug} />
            )}
        </OnboardingLayout>
    );
}
