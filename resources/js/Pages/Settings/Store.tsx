import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { SettingsLayout } from '@/Components/layouts/SettingsLayout';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import { SectionCard } from '@/Components/shared/SectionCard';
import { PageHeader } from '@/Components/shared/PageHeader';
import { CountrySelect } from '@/Components/shared/CountrySelect';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

interface StoreData {
    id: number;
    slug: string;
    name: string;
    domain: string | null;
    platform: 'woocommerce' | 'shopify';
    currency: string;
    timezone: string;
    primary_country_code: string | null;
    status: string;
    settings: { prices_include_vat: boolean | null };
}

interface CostSettings {
    shipping_mode: 'flat_rate' | 'per_order' | 'none';
    shipping_flat_rate_native: number | null;
    shipping_per_order_native: number | null;
    default_currency: string;
}

interface ShippingRule {
    id: number;
    min_weight: number | null;
    max_weight: number | null;
    country: string | null;
    cost: number;
    currency: string;
}

interface FeeRule {
    id: number;
    processor: string;
    rate_pct: number;
    fixed: number | null;
    currency: string;
}

interface MonitoredUrl {
    id: number;
    url: string;
    label: string | null;
    is_homepage: boolean;
}

interface Props extends PageProps {
    store: StoreData;
    workspace_costs: CostSettings | null;
    store_costs: CostSettings | null;
    workspace_shipping: ShippingRule[];
    store_shipping: ShippingRule[];
    workspace_fees: FeeRule[];
    store_fees: FeeRule[];
    monitored_urls: MonitoredUrl[];
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function InheritedBadge() {
    return (
        <span className="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
            Inherited
        </span>
    );
}

function OverrideBadge() {
    return (
        <span className="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700">
            Override
        </span>
    );
}

function shippingModeLabel(mode: string): string {
    if (mode === 'flat_rate') return 'Flat rate';
    if (mode === 'per_order') return 'Per order';
    return 'None';
}

function safePath(url: string): string {
    try {
        return new URL(url).pathname || url;
    } catch {
        return url;
    }
}

// ─── Tab definitions ──────────────────────────────────────────────────────────

type Tab = 'general' | 'costs' | 'performance';

// ─── General Tab ──────────────────────────────────────────────────────────────

function GeneralTab({
    store,
    wsSlug,
}: {
    store: StoreData;
    wsSlug: string | undefined;
}) {
    const [name, setName] = useState(store.name);
    const [country, setCountry] = useState(store.primary_country_code ?? '');
    // null = workspace default, true = included, false = added on top
    const [vatMode, setVatMode] = useState<'null' | 'true' | 'false'>(
        store.settings.prices_include_vat === null
            ? 'null'
            : store.settings.prices_include_vat
              ? 'true'
              : 'false',
    );
    const [saving, setSaving] = useState(false);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setSaving(true);
        router.patch(
            wurl(wsSlug, `/settings/stores/${store.slug}`),
            {
                section: 'general',
                name,
                primary_country_code: country || null,
                prices_include_vat: vatMode === 'null' ? null : vatMode === 'true',
            },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    }

    return (
        <form onSubmit={handleSubmit}>
            <SectionCard title="Store details" description="Basic information about this store.">
                <div className="space-y-4">
                    {/* Store name */}
                    <div>
                        <label className="block text-sm font-medium text-foreground mb-1">
                            Store name
                        </label>
                        <input
                            type="text"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            className="w-full rounded-md border border-input px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            required
                        />
                    </div>

                    {/* Platform — read-only */}
                    <div>
                        <label className="block text-sm font-medium text-foreground mb-1">
                            Platform
                        </label>
                        <span className="inline-flex items-center rounded-full bg-muted px-3 py-1 text-sm font-medium text-foreground">
                            {store.platform === 'woocommerce' ? 'WooCommerce' : 'Shopify'}
                        </span>
                    </div>

                    {/* Currency — read-only */}
                    <div>
                        <label className="block text-sm font-medium text-foreground mb-1">
                            Currency
                        </label>
                        <p className="text-sm text-muted-foreground">{store.currency}</p>
                    </div>

                    {/* Timezone — read-only */}
                    <div>
                        <label className="block text-sm font-medium text-foreground mb-1">
                            Timezone
                        </label>
                        <p className="text-sm text-muted-foreground">{store.timezone ?? '—'}</p>
                    </div>

                    {/* Primary country */}
                    <div>
                        <label className="block text-sm font-medium text-foreground mb-1">
                            Primary country
                        </label>
                        <CountrySelect
                            value={country}
                            onChange={setCountry}
                            className="border-input focus:border-primary focus:ring-primary"
                        />
                        <p className="mt-1 text-sm text-muted-foreground">
                            Used as a fallback when campaign names don't include a country code.
                        </p>
                    </div>

                    {/* Prices include VAT — three-way toggle */}
                    <div>
                        <label className="block text-sm font-medium text-foreground mb-1">
                            Prices include VAT
                        </label>
                        <div className="flex gap-2">
                            {(
                                [
                                    { value: 'null', label: 'Workspace default' },
                                    { value: 'true', label: 'Yes — included in price' },
                                    { value: 'false', label: 'No — added on top' },
                                ] as const
                            ).map((opt) => (
                                <button
                                    key={opt.value}
                                    type="button"
                                    onClick={() => setVatMode(opt.value)}
                                    className={[
                                        'rounded-md border px-3 py-1.5 text-xs font-medium transition-colors',
                                        vatMode === opt.value
                                            ? 'border-primary bg-primary/10 text-primary'
                                            : 'border-border text-muted-foreground hover:border-input hover:bg-muted/50',
                                    ].join(' ')}
                                >
                                    {opt.label}
                                </button>
                            ))}
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            When set, overrides the workspace-level VAT setting for this store.
                        </p>
                    </div>
                </div>

                <div className="mt-5 flex justify-end">
                    <button
                        type="submit"
                        disabled={saving}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors"
                    >
                        {saving ? 'Saving…' : 'Save changes'}
                    </button>
                </div>
            </SectionCard>
        </form>
    );
}

// ─── Costs Tab ────────────────────────────────────────────────────────────────

function CostsTab({
    store,
    wsSlug,
    workspaceCosts,
    storeCosts,
    workspaceShipping,
    storeShipping,
    workspaceFees,
    storeFees,
}: {
    store: StoreData;
    wsSlug: string | undefined;
    workspaceCosts: CostSettings | null;
    storeCosts: CostSettings | null;
    workspaceShipping: ShippingRule[];
    storeShipping: ShippingRule[];
    workspaceFees: FeeRule[];
    storeFees: FeeRule[];
}) {
    const [showOverrideForm, setShowOverrideForm] = useState(storeCosts !== null);
    const [shippingMode, setShippingMode] = useState<'flat_rate' | 'per_order' | 'none'>(
        storeCosts?.shipping_mode ?? workspaceCosts?.shipping_mode ?? 'none',
    );
    const [flatRate, setFlatRate] = useState<string>(
        storeCosts?.shipping_flat_rate_native?.toString() ?? '',
    );
    const [perOrder, setPerOrder] = useState<string>(
        storeCosts?.shipping_per_order_native?.toString() ?? '',
    );
    const [saving, setSaving] = useState(false);

    function handleSaveCosts(e: React.FormEvent) {
        e.preventDefault();
        setSaving(true);
        router.patch(
            wurl(wsSlug, `/settings/stores/${store.slug}`),
            {
                section: 'costs',
                shipping_mode: shippingMode,
                shipping_flat_rate_native: flatRate !== '' ? parseFloat(flatRate) : null,
                shipping_per_order_native: perOrder !== '' ? parseFloat(perOrder) : null,
            },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    }

    function handleRemoveOverride() {
        if (!confirm('Remove store cost override and revert to workspace defaults?')) return;
        router.delete(wurl(wsSlug, `/settings/stores/${store.slug}/costs`), {
            preserveScroll: true,
            onSuccess: () => setShowOverrideForm(false),
        });
    }

    return (
        <div className="space-y-6">
            {/* Workspace defaults — read-only */}
            <SectionCard
                title="Workspace defaults"
                action={<InheritedBadge />}
                description="These settings apply to all stores unless overridden below."
            >
                {workspaceCosts === null ? (
                    <p className="text-sm text-muted-foreground">
                        No workspace default configured.{' '}
                        <Link
                            href={wurl(wsSlug, '/settings/costs')}
                            className="text-primary underline hover:text-primary/80"
                        >
                            Go to Settings › Costs
                        </Link>{' '}
                        to set one.
                    </p>
                ) : (
                    <div className="rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-muted-foreground space-y-1">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Shipping mode</span>
                            <span className="font-medium text-foreground">{shippingModeLabel(workspaceCosts.shipping_mode)}</span>
                        </div>
                        {workspaceCosts.shipping_mode === 'flat_rate' && workspaceCosts.shipping_flat_rate_native !== null && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Flat rate</span>
                                <span className="font-medium text-foreground">
                                    {workspaceCosts.shipping_flat_rate_native} {workspaceCosts.default_currency}
                                </span>
                            </div>
                        )}
                        {workspaceCosts.shipping_mode === 'per_order' && workspaceCosts.shipping_per_order_native !== null && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Per-order rate</span>
                                <span className="font-medium text-foreground">
                                    {workspaceCosts.shipping_per_order_native} {workspaceCosts.default_currency}
                                </span>
                            </div>
                        )}
                    </div>
                )}
            </SectionCard>

            {/* Store override */}
            <SectionCard
                title="This store's override"
                action={storeCosts !== null ? <OverrideBadge /> : undefined}
                description="Override workspace shipping costs for this store only."
            >
                {!showOverrideForm ? (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            No override — using workspace default.
                        </p>
                        <button
                            type="button"
                            onClick={() => setShowOverrideForm(true)}
                            className="rounded-md border border-border px-3 py-1.5 text-sm font-medium text-foreground hover:bg-muted/50 transition-colors"
                        >
                            Add override
                        </button>
                    </div>
                ) : (
                    <form onSubmit={handleSaveCosts} className="space-y-4">
                        {/* Shipping mode */}
                        <div>
                            <label className="block text-sm font-medium text-foreground mb-1">
                                Shipping mode
                            </label>
                            <select
                                value={shippingMode}
                                onChange={(e) => setShippingMode(e.target.value as typeof shippingMode)}
                                className="w-full rounded-md border border-input px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            >
                                <option value="none">None</option>
                                <option value="flat_rate">Flat rate</option>
                                <option value="per_order">Per order</option>
                            </select>
                        </div>

                        {/* Flat rate amount */}
                        {shippingMode === 'flat_rate' && (
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1">
                                    Flat rate ({store.currency})
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={flatRate}
                                    onChange={(e) => setFlatRate(e.target.value)}
                                    placeholder="0.00"
                                    className="w-36 rounded-md border border-input px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>
                        )}

                        {/* Per-order amount */}
                        {shippingMode === 'per_order' && (
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1">
                                    Per-order rate ({store.currency})
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={perOrder}
                                    onChange={(e) => setPerOrder(e.target.value)}
                                    placeholder="0.00"
                                    className="w-36 rounded-md border border-input px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>
                        )}

                        <div className="flex items-center justify-between pt-1">
                            <button
                                type="button"
                                onClick={handleRemoveOverride}
                                className="text-sm text-red-500 hover:text-red-700 transition-colors"
                            >
                                Remove override (revert to workspace default)
                            </button>
                            <button
                                type="submit"
                                disabled={saving}
                                className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors"
                            >
                                {saving ? 'Saving…' : 'Save override'}
                            </button>
                        </div>
                    </form>
                )}
            </SectionCard>

            {/* Workspace shipping rules — read-only reference */}
            <SectionCard
                title="Workspace shipping rules"
                action={<InheritedBadge />}
            >
                <p className="mt-0.5 mb-3 text-sm text-muted-foreground">Managed in <Link href={wurl(wsSlug, '/settings/costs')} className="text-primary hover:underline">Settings › Costs</Link>. Add or remove rules there.</p>
                {workspaceShipping.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No workspace shipping rules configured.</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-muted/50 border-b border-border">
                                <tr>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Min weight (g)</th>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Max weight (g)</th>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Country</th>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Cost</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {workspaceShipping.map((r) => (
                                    <tr key={r.id} className="text-muted-foreground">
                                        <td className="py-2 pr-4">{r.min_weight ?? '—'}</td>
                                        <td className="py-2 pr-4">{r.max_weight ?? '—'}</td>
                                        <td className="py-2 pr-4">{r.country ?? 'All'}</td>
                                        <td className="py-2">{r.cost} {r.currency}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </SectionCard>

            {/* Store shipping rules — read-only reference */}
            <SectionCard
                title="Store shipping rules"
                action={<OverrideBadge />}
            >
                <p className="mt-0.5 mb-3 text-sm text-muted-foreground">Store-specific shipping rules. Manage in <Link href={wurl(wsSlug, '/settings/costs')} className="text-primary hover:underline">Settings › Costs</Link>.</p>
                {storeShipping.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No store-specific shipping rules.</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-muted/50 border-b border-border">
                                <tr>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Min weight (g)</th>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Max weight (g)</th>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Country</th>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Cost</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {storeShipping.map((r) => (
                                    <tr key={r.id} className="text-foreground">
                                        <td className="py-2 pr-4">{r.min_weight ?? '—'}</td>
                                        <td className="py-2 pr-4">{r.max_weight ?? '—'}</td>
                                        <td className="py-2 pr-4">{r.country ?? 'All'}</td>
                                        <td className="py-2">{r.cost} {r.currency}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </SectionCard>

            {/* Workspace transaction fees — read-only reference */}
            <SectionCard
                title="Workspace transaction fees"
                action={<InheritedBadge />}
            >
                <p className="mt-0.5 mb-3 text-sm text-muted-foreground">Managed in <Link href={wurl(wsSlug, '/settings/costs')} className="text-primary hover:underline">Settings › Costs</Link>. Add or remove fees there.</p>
                {workspaceFees.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No workspace transaction fee rules configured.</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-muted/50 border-b border-border">
                                <tr>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Processor</th>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Rate %</th>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Fixed fee</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {workspaceFees.map((r) => (
                                    <tr key={r.id} className="text-muted-foreground">
                                        <td className="py-2 pr-4">{r.processor}</td>
                                        <td className="py-2 pr-4">{r.rate_pct}%</td>
                                        <td className="py-2">{r.fixed !== null ? `${r.fixed} ${r.currency}` : '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </SectionCard>

            {/* Store transaction fees — read-only reference */}
            <SectionCard
                title="Store transaction fees"
                action={<OverrideBadge />}
            >
                <p className="mt-0.5 mb-3 text-sm text-muted-foreground">Store-specific transaction fee overrides. Manage in <Link href={wurl(wsSlug, '/settings/costs')} className="text-primary hover:underline">Settings › Costs</Link>.</p>
                {storeFees.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No store-specific transaction fee rules.</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-muted/50 border-b border-border">
                                <tr>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Processor</th>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Rate %</th>
                                    <th className="px-3 py-2 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Fixed fee</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {storeFees.map((r) => (
                                    <tr key={r.id} className="text-foreground">
                                        <td className="py-2 pr-4">{r.processor}</td>
                                        <td className="py-2 pr-4">{r.rate_pct}%</td>
                                        <td className="py-2">{r.fixed !== null ? `${r.fixed} ${r.currency}` : '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </SectionCard>
        </div>
    );
}

// ─── Performance Tab ──────────────────────────────────────────────────────────

function PerformanceTab({
    store,
    wsSlug,
    monitoredUrls,
}: {
    store: StoreData;
    wsSlug: string | undefined;
    monitoredUrls: MonitoredUrl[];
}) {
    const [adding, setAdding] = useState(false);
    const [newUrl, setNewUrl] = useState('');

    // Domain validation: check that the entered URL belongs to this store's domain.
    const domainError: string | null = (() => {
        if (!newUrl.trim() || !store.domain) return null;
        try {
            const parsed = new URL(newUrl.trim());
            // Strip leading "www." for a lenient match.
            const normalize = (host: string) => host.replace(/^www\./, '');
            if (normalize(parsed.hostname) !== normalize(store.domain)) {
                return `URL must be on ${store.domain}`;
            }
        } catch {
            // Invalid URL — let the browser's built-in `type="url"` validation handle it.
        }
        return null;
    })();

    const addDisabled = !newUrl.trim() || domainError !== null;

    function handleAdd(e: React.FormEvent) {
        e.preventDefault();
        if (!newUrl.trim() || domainError) return;
        router.post(
            wurl(wsSlug, `/settings/integrations/stores/${store.slug}/monitored-urls`),
            { url: newUrl.trim() },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNewUrl('');
                    setAdding(false);
                },
            },
        );
    }

    function handleRemove(urlId: number) {
        router.delete(
            wurl(wsSlug, `/settings/integrations/stores/${store.slug}/monitored-urls/${urlId}`),
            { preserveScroll: true },
        );
    }

    return (
        <SectionCard
            title="Monitored pages"
            description="Pages tracked by Lighthouse for Core Web Vitals and performance scores."
        >
            {/* Existing URLs */}
            <ul className="space-y-2 mb-4">
                {monitoredUrls.map((u) => (
                    <li key={u.id} className="flex items-center gap-3 rounded-lg border border-border bg-muted/50 px-3 py-2">
                        <span className="flex-1 truncate text-sm text-foreground" title={u.url}>
                            {u.label ?? safePath(u.url)}
                        </span>
                        <span className="shrink-0 text-sm text-muted-foreground">{u.url}</span>
                        {u.is_homepage && (
                            <span className="shrink-0 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                homepage
                            </span>
                        )}
                        {!u.is_homepage && (
                            <button
                                type="button"
                                onClick={() => handleRemove(u.id)}
                                className="shrink-0 text-muted-foreground hover:text-red-500 transition-colors"
                                title="Remove from monitoring"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        )}
                    </li>
                ))}
                {monitoredUrls.length === 0 && (
                    <li className="text-sm text-muted-foreground">No pages monitored yet.</li>
                )}
            </ul>

            {/* Add URL form */}
            {monitoredUrls.length >= 10 ? (
                <p className="text-sm text-muted-foreground">
                    Maximum 10 URLs monitored. Remove one to add another.
                </p>
            ) : adding ? (
                <form onSubmit={handleAdd} className="space-y-2">
                    <div>
                        <label className="block text-sm font-medium text-foreground mb-1">
                            Add a page to monitor
                        </label>
                        <p className="mb-2 text-sm text-muted-foreground">
                            Nexstage checks this page daily for Core Web Vitals.
                        </p>
                        <div className="flex gap-2">
                            <div className="flex-1">
                                <input
                                    type="url"
                                    value={newUrl}
                                    onChange={(e) => setNewUrl(e.target.value)}
                                    placeholder={store.domain ? `https://${store.domain}/page-path` : 'https://yourstore.com/page-path'}
                                    autoFocus
                                    className={[
                                        'w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-1',
                                        domainError
                                            ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
                                            : 'border-input focus:border-primary focus:ring-primary',
                                    ].join(' ')}
                                />
                                {domainError && (
                                    <p className="mt-1 text-sm text-red-600">{domainError}</p>
                                )}
                            </div>
                            <button
                                type="submit"
                                disabled={addDisabled}
                                className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                            >
                                Add
                            </button>
                            <button
                                type="button"
                                onClick={() => { setAdding(false); setNewUrl(''); }}
                                className="rounded-md border border-border px-3 py-2 text-sm text-muted-foreground hover:text-foreground transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            ) : (
                <button
                    type="button"
                    onClick={() => setAdding(true)}
                    className="flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-sm font-medium text-foreground hover:bg-muted/50 transition-colors"
                >
                    <Plus className="h-3.5 w-3.5" /> Add URL
                </button>
            )}
        </SectionCard>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function StoreSettings({
    store,
    workspace_costs,
    store_costs,
    workspace_shipping,
    store_shipping,
    workspace_fees,
    store_fees,
    monitored_urls,
}: Props) {
    const { workspace, flash } = usePage<PageProps>().props;
    const wsSlug = workspace?.slug;

    const [activeTab, setActiveTab] = useState<Tab>('general');

    const tabs: { key: Tab; label: string }[] = [
        { key: 'general', label: 'General' },
        { key: 'costs', label: 'Costs' },
        { key: 'performance', label: 'Performance' },
    ];

    return (
        <SettingsLayout>
            <Head title={`${store.name} — Store Settings`} />

            <PageHeader
                title={store.name}
                subtitle="Per-store configuration. Settings here override workspace defaults."
            />

            {flash?.success && (
                <AlertBanner severity="info" message={flash.success} />
            )}

            {/* Tab bar */}
            <div className="mt-4 mb-6 flex border-b border-border">
                {tabs.map((tab) => (
                    <button
                        key={tab.key}
                        type="button"
                        onClick={() => setActiveTab(tab.key)}
                        className={[
                            'px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px',
                            activeTab === tab.key
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        ].join(' ')}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {/* Tab content */}
            <div className="max-w-3xl">
                {activeTab === 'general' && (
                    <GeneralTab store={store} wsSlug={wsSlug} />
                )}
                {activeTab === 'costs' && (
                    <CostsTab
                        store={store}
                        wsSlug={wsSlug}
                        workspaceCosts={workspace_costs}
                        storeCosts={store_costs}
                        workspaceShipping={workspace_shipping}
                        storeShipping={store_shipping}
                        workspaceFees={workspace_fees}
                        storeFees={store_fees}
                    />
                )}
                {activeTab === 'performance' && (
                    <PerformanceTab
                        store={store}
                        wsSlug={wsSlug}
                        monitoredUrls={monitored_urls}
                    />
                )}
            </div>
        </SettingsLayout>
    );
}
