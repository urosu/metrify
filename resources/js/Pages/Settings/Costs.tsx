/**
 * Settings / Costs — COGS, Shipping, Transaction Fees, Platform Fees, Tax/VAT, OpEx.
 * Desktop-only (1280+); shows a "View on desktop" banner on narrower viewports.
 *
 * Patterns used:
 * - Glew: inline-editable COGS per product with InlineEditableCell
 * - Lifetimely: transaction fee honest-disclosure (seeded rates flagged "verify against your contract")
 * - Linear: SubNavTabs (vertical list), StatusDot per section, optimistic cell edits
 * - Klaviyo: Recomputing banner on any cost change
 *
 * @see docs/pages/settings.md §costs
 * @see docs/UX.md §5.5.1 InlineEditableCell, §6.2 Optimistic writes
 */
import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { SettingsLayout } from '@/Components/layouts/SettingsLayout';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import { SectionCard } from '@/Components/shared/SectionCard';
import { InlineEditableCell } from '@/Components/shared/InlineEditableCell';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';
import { Monitor, Plus, Trash2, AlertTriangle, RefreshCw } from 'lucide-react';
import { cn } from '@/lib/utils';

// ─── Mock data ─────────────────────────────────────────────────────────────────

const MOCK_COGS: CogsRule[] = [
    { id: 1,  scope: 'product',  product_sku: 'SHIRT-MW-CRW-NVY', product_name: 'Midweight Crew - Navy',        cogs: 22.50, currency: 'USD', source: 'Manual' },
    { id: 2,  scope: 'product',  product_sku: 'SHIRT-MW-CRW-GRY', product_name: 'Midweight Crew - Grey',        cogs: 22.50, currency: 'USD', source: 'Manual' },
    { id: 3,  scope: 'product',  product_sku: 'PKT-MSN-NVY',      product_name: 'Mason Pocket Tee - Navy',      cogs: 18.00, currency: 'USD', source: 'Shopify' },
    { id: 4,  scope: 'product',  product_sku: 'JCKT-TRAIL-L',     product_name: 'Trail Jacket - Large',         cogs: 64.00, currency: 'USD', source: 'Manual' },
    { id: 5,  scope: 'product',  product_sku: 'PANT-CARGO-32',    product_name: 'Cargo Pant - 32W',             cogs: 38.00, currency: 'USD', source: 'Manual' },
    { id: 6,  scope: 'category', product_sku: '',                  product_name: '',                            cogs: 0,     currency: 'USD', source: 'Manual', category: 'Apparel',    cogs_pct: 30.0 },
    { id: 7,  scope: 'category', product_sku: '',                  product_name: '',                            cogs: 0,     currency: 'USD', source: 'Manual', category: 'Footwear',   cogs_pct: 35.0 },
    { id: 8,  scope: 'category', product_sku: '',                  product_name: '',                            cogs: 0,     currency: 'USD', source: 'Manual', category: 'Accessories', cogs_pct: 40.0 },
    { id: 9,  scope: 'default',  product_sku: '',                  product_name: 'Default fallback',             cogs: 0,     currency: 'USD', source: 'Manual', cogs_pct: 25.0 },
    { id: 10, scope: 'product',  product_sku: 'HAT-CAMP-BLK',     product_name: 'Camp Hat - Black',             cogs: 12.00, currency: 'USD', source: 'Manual' },
    { id: 11, scope: 'product',  product_sku: 'SOCKS-3PK',        product_name: '3-Pack Socks',                 cogs: 6.00,  currency: 'USD', source: 'Woo meta' },
    { id: 12, scope: 'product',  product_sku: 'BTLE-32OZ',        product_name: 'Hydro Bottle 32oz',            cogs: 14.50, currency: 'USD', source: 'Manual' },
];

const MOCK_SHIPPING: ShippingRule[] = [
    { id: 1, zone: 'US Domestic', min_weight_g: 0,    max_weight_g: 500,  cost: 4.99,  carrier: 'USPS' },
    { id: 2, zone: 'US Domestic', min_weight_g: 501,  max_weight_g: 2000, cost: 7.99,  carrier: 'UPS' },
    { id: 3, zone: 'EU',          min_weight_g: 0,    max_weight_g: 1000, cost: 12.00, carrier: 'DHL' },
    { id: 4, zone: 'Rest of World', min_weight_g: 0,  max_weight_g: 999999, cost: 18.00, carrier: 'FedEx' },
];

const MOCK_TX_FEES: TransactionFeeRule[] = [
    { id: 1, provider: 'Shopify Payments', pct: 2.9,  fixed: 0.30, applies_to: 'All orders', note: 'Seeded — verify against your contract' },
    { id: 2, provider: 'PayPal',           pct: 3.49, fixed: 0.49, applies_to: 'PayPal orders', note: 'Seeded — verify against your contract' },
    { id: 3, provider: 'Stripe',           pct: 2.9,  fixed: 0.30, applies_to: 'Stripe orders', note: 'Seeded — verify against your contract' },
];

const MOCK_PLATFORM_FEES: PlatformFeeRule[] = [
    { id: 1, item: 'Shopify Basic Plan',    monthly_cost: 39.00,  allocation: 'Per-day' },
    { id: 2, item: 'Shopify Apps (bundle)', monthly_cost: 68.00,  allocation: 'Per-day' },
    { id: 3, item: 'Nexstage',              monthly_cost: 39.00,  allocation: 'Per-day' },
    { id: 4, item: 'Klaviyo',               monthly_cost: 120.00, allocation: 'Per-day' },
];

const MOCK_TAX_RULES: TaxRule[] = [
    { id: 1, country: 'US', rate_pct: 0.0,  included: false, digital_override: false },
    { id: 2, country: 'DE', rate_pct: 19.0, included: true,  digital_override: false },
    { id: 3, country: 'FR', rate_pct: 20.0, included: true,  digital_override: false },
    { id: 4, country: 'GB', rate_pct: 20.0, included: true,  digital_override: false },
    { id: 5, country: 'CA', rate_pct: 5.0,  included: false, digital_override: false },
    { id: 6, country: 'AU', rate_pct: 10.0, included: true,  digital_override: false },
];

const MOCK_OPEX: OpexAllocation[] = [
    { id: 1, category: 'Salaries',    monthly_cost: 8500.00, allocation: 'Per-day' },
    { id: 2, category: 'Rent',        monthly_cost: 2200.00, allocation: 'Per-day' },
    { id: 3, category: 'Software',    monthly_cost: 580.00,  allocation: 'Per-day' },
    { id: 4, category: 'Fulfillment', monthly_cost: 1200.00, allocation: 'Per-order' },
];

// ─── Types ────────────────────────────────────────────────────────────────────

interface CogsRule {
    id: number;
    scope: 'product' | 'category' | 'default';
    product_sku: string;
    product_name: string;
    cogs: number;
    cogs_pct?: number;
    category?: string;
    currency: string;
    source: string;
}

interface ShippingRule {
    id: number;
    zone: string;
    min_weight_g: number;
    max_weight_g: number;
    cost: number;
    carrier: string;
}

interface TransactionFeeRule {
    id: number;
    provider: string;
    pct: number;
    fixed: number;
    applies_to: string;
    note?: string;
}

interface PlatformFeeRule {
    id: number;
    item: string;
    monthly_cost: number;
    allocation: string;
}

interface TaxRule {
    id: number;
    country: string;
    rate_pct: number;
    included: boolean;
    digital_override: boolean;
}

interface OpexAllocation {
    id: number;
    category: string;
    monthly_cost: number;
    allocation: string;
}

interface Props extends PageProps {
    shipping_rules?: ShippingRule[];
    transaction_fee_rules?: TransactionFeeRule[];
    tax_rules?: TaxRule[];
    platform_fee_rules?: PlatformFeeRule[];
    opex_allocations?: OpexAllocation[];
    cogs_configured_count?: number;
    total_skus?: number;
}

type CostTab = 'cogs' | 'shipping' | 'transaction' | 'platform' | 'tax' | 'opex';

const TABS: { id: CostTab; label: string }[] = [
    { id: 'cogs',        label: 'COGS' },
    { id: 'shipping',    label: 'Shipping' },
    { id: 'transaction', label: 'Transaction Fees' },
    { id: 'platform',    label: 'Platform Fees' },
    { id: 'tax',         label: 'Tax / VAT' },
    { id: 'opex',        label: 'OpEx' },
];

function fmt(n: number, currency = 'USD'): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency', currency, minimumFractionDigits: 2, maximumFractionDigits: 2,
    }).format(n);
}

// ─── COGS tab ──────────────────────────────────────────────────────────────────

function CogsTab({ rules }: { rules: CogsRule[] }) {
    return (
        <div>
            <div className="mb-3 flex items-center justify-between gap-3">
                <p className="text-sm text-zinc-600">
                    Per-product and per-category cost rules. Products without a rule fall back to the
                    default % of price.
                </p>
                <div className="flex items-center gap-2">
                    <button className="flex items-center gap-1.5 rounded-md border border-zinc-200 px-3 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 transition-colors">
                        Upload CSV
                    </button>
                    <button className="flex items-center gap-1.5 rounded-md bg-teal-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-teal-700 transition-colors">
                        <Plus className="h-3.5 w-3.5" />
                        Add rule
                    </button>
                </div>
            </div>
            <div className="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
                <table className="min-w-full">
                    <thead className="bg-zinc-50 border-b border-zinc-200">
                        <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            <th className="px-4 py-2.5 text-left">Scope</th>
                            <th className="px-4 py-2.5 text-left">Product / Category</th>
                            <th className="px-4 py-2.5 text-left">SKU</th>
                            <th className="px-4 py-2.5 text-right">Cost per unit</th>
                            <th className="px-4 py-2.5 text-right">% of price</th>
                            <th className="px-4 py-2.5 text-left">Source</th>
                            <th className="px-4 py-2.5 text-right"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-100">
                        {rules.map((r) => (
                            <tr key={r.id} className="hover:bg-zinc-50 transition-colors">
                                <td className="px-4 py-2.5">
                                    <span className={cn(
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        r.scope === 'product'  ? 'bg-teal-50 text-teal-700' :
                                        r.scope === 'category' ? 'bg-indigo-50 text-indigo-700' :
                                                                  'bg-zinc-100 text-zinc-600',
                                    )}>
                                        {r.scope}
                                    </span>
                                </td>
                                <td className="px-4 py-2.5 text-sm text-zinc-800">
                                    {r.product_name || r.category || '—'}
                                </td>
                                <td className="px-4 py-2.5 font-mono text-xs text-zinc-500">
                                    {r.product_sku || '—'}
                                </td>
                                <td className="px-4 py-2.5 text-right">
                                    {r.scope !== 'category' && r.scope !== 'default' ? (
                                        <InlineEditableCell
                                            value={r.cogs}
                                            type="number"
                                            prefix="$"
                                            onSave={async (v) => {
                                                await new Promise((res) => setTimeout(res, 300));
                                                console.log('save cogs', r.id, v);
                                            }}
                                        />
                                    ) : (
                                        <span className="text-sm text-zinc-400">—</span>
                                    )}
                                </td>
                                <td className="px-4 py-2.5 text-right">
                                    {r.cogs_pct !== undefined ? (
                                        <InlineEditableCell
                                            value={r.cogs_pct}
                                            type="number"
                                            onSave={async (v) => {
                                                await new Promise((res) => setTimeout(res, 300));
                                                console.log('save pct', r.id, v);
                                            }}
                                        />
                                    ) : (
                                        <span className="text-sm text-zinc-400">—</span>
                                    )}
                                </td>
                                <td className="px-4 py-2.5 text-sm text-zinc-500">{r.source}</td>
                                <td className="px-4 py-2.5 text-right">
                                    <button className="rounded p-1 text-zinc-400 hover:text-rose-500 transition-colors">
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ─── Shipping tab ─────────────────────────────────────────────────────────────

function ShippingTab({ rules }: { rules: ShippingRule[] }) {
    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <p className="text-sm text-zinc-600">Weight-tiered rules by shipping zone.</p>
                <button className="flex items-center gap-1.5 rounded-md bg-teal-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-teal-700 transition-colors">
                    <Plus className="h-3.5 w-3.5" />
                    Add rule
                </button>
            </div>
            <div className="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
                <table className="min-w-full">
                    <thead className="bg-zinc-50 border-b border-zinc-200">
                        <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            <th className="px-4 py-2.5 text-left">Zone</th>
                            <th className="px-4 py-2.5 text-right">Min weight (g)</th>
                            <th className="px-4 py-2.5 text-right">Max weight (g)</th>
                            <th className="px-4 py-2.5 text-right">Cost</th>
                            <th className="px-4 py-2.5 text-left">Carrier</th>
                            <th className="px-4 py-2.5 text-right"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-100">
                        {rules.map((r) => (
                            <tr key={r.id} className="hover:bg-zinc-50 transition-colors">
                                <td className="px-4 py-2.5 text-sm font-medium text-zinc-800">{r.zone}</td>
                                <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-600">{r.min_weight_g}</td>
                                <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-600">
                                    {r.max_weight_g === 999999 ? '∞' : r.max_weight_g}
                                </td>
                                <td className="px-4 py-2.5 text-right">
                                    <InlineEditableCell
                                        value={r.cost}
                                        type="number"
                                        prefix="$"
                                        onSave={async () => { await new Promise((res) => setTimeout(res, 300)); }}
                                    />
                                </td>
                                <td className="px-4 py-2.5 text-sm text-zinc-500">{r.carrier}</td>
                                <td className="px-4 py-2.5 text-right">
                                    <button className="rounded p-1 text-zinc-400 hover:text-rose-500 transition-colors">
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ─── Transaction fees tab ─────────────────────────────────────────────────────

function TransactionFeesTab({ rules }: { rules: TransactionFeeRule[] }) {
    return (
        <div>
            <div className="mb-3">
                <p className="text-sm text-zinc-600">
                    Fees per payment processor. Seeded rates are flagged — always verify against your contract.
                </p>
            </div>
            <div className="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
                <table className="min-w-full">
                    <thead className="bg-zinc-50 border-b border-zinc-200">
                        <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            <th className="px-4 py-2.5 text-left">Processor</th>
                            <th className="px-4 py-2.5 text-right">Percentage</th>
                            <th className="px-4 py-2.5 text-right">Fixed fee</th>
                            <th className="px-4 py-2.5 text-left">Applies to</th>
                            <th className="px-4 py-2.5 text-left">Note</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-100">
                        {rules.map((r) => (
                            <tr key={r.id} className="hover:bg-zinc-50 transition-colors">
                                <td className="px-4 py-2.5 text-sm font-medium text-zinc-800">{r.provider}</td>
                                <td className="px-4 py-2.5 text-right">
                                    <InlineEditableCell
                                        value={r.pct}
                                        type="number"
                                        onSave={async () => { await new Promise((res) => setTimeout(res, 300)); }}
                                    />
                                    <span className="ml-0.5 text-sm text-zinc-500">%</span>
                                </td>
                                <td className="px-4 py-2.5 text-right tabular-nums text-sm text-zinc-600">
                                    ${r.fixed.toFixed(2)}
                                </td>
                                <td className="px-4 py-2.5 text-sm text-zinc-500">{r.applies_to}</td>
                                <td className="px-4 py-2.5 text-sm">
                                    {r.note && (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-700">
                                            <AlertTriangle className="h-3 w-3" />
                                            {r.note}
                                        </span>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ─── Platform fees tab ────────────────────────────────────────────────────────

function PlatformFeesTab({ rules }: { rules: PlatformFeeRule[] }) {
    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <p className="text-sm text-zinc-600">Monthly platform subscriptions spread across the period.</p>
                <button className="flex items-center gap-1.5 rounded-md bg-teal-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-teal-700 transition-colors">
                    <Plus className="h-3.5 w-3.5" />
                    Add fee
                </button>
            </div>
            <div className="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
                <table className="min-w-full">
                    <thead className="bg-zinc-50 border-b border-zinc-200">
                        <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            <th className="px-4 py-2.5 text-left">Item</th>
                            <th className="px-4 py-2.5 text-right">Monthly cost</th>
                            <th className="px-4 py-2.5 text-left">Allocation</th>
                            <th className="px-4 py-2.5 text-right"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-100">
                        {rules.map((r) => (
                            <tr key={r.id} className="hover:bg-zinc-50 transition-colors">
                                <td className="px-4 py-2.5 text-sm font-medium text-zinc-800">{r.item}</td>
                                <td className="px-4 py-2.5 text-right">
                                    <InlineEditableCell
                                        value={r.monthly_cost}
                                        type="number"
                                        prefix="$"
                                        onSave={async () => { await new Promise((res) => setTimeout(res, 300)); }}
                                    />
                                </td>
                                <td className="px-4 py-2.5 text-sm text-zinc-500">{r.allocation}</td>
                                <td className="px-4 py-2.5 text-right">
                                    <button className="rounded p-1 text-zinc-400 hover:text-rose-500 transition-colors">
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                        <tr className="bg-zinc-50">
                            <td className="px-4 py-2.5 text-sm font-semibold text-zinc-700">Total</td>
                            <td className="px-4 py-2.5 text-right tabular-nums text-sm font-semibold text-zinc-900">
                                {fmt(rules.reduce((s, r) => s + r.monthly_cost, 0))}
                            </td>
                            <td colSpan={2} />
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ─── Tax tab ──────────────────────────────────────────────────────────────────

function TaxTab({ rules }: { rules: TaxRule[] }) {
    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <p className="text-sm text-zinc-600">VAT / GST rates by country. Seeded from EU rates at workspace creation; editable.</p>
                <button className="flex items-center gap-1.5 rounded-md bg-teal-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-teal-700 transition-colors">
                    <Plus className="h-3.5 w-3.5" />
                    Add country
                </button>
            </div>
            <div className="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
                <table className="min-w-full">
                    <thead className="bg-zinc-50 border-b border-zinc-200">
                        <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            <th className="px-4 py-2.5 text-left">Country</th>
                            <th className="px-4 py-2.5 text-right">Rate %</th>
                            <th className="px-4 py-2.5 text-left">Included in price</th>
                            <th className="px-4 py-2.5 text-left">Digital override</th>
                            <th className="px-4 py-2.5 text-right"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-100">
                        {rules.map((r) => (
                            <tr key={r.id} className="hover:bg-zinc-50 transition-colors">
                                <td className="px-4 py-2.5 font-mono text-sm font-medium text-zinc-800">{r.country}</td>
                                <td className="px-4 py-2.5 text-right">
                                    <InlineEditableCell
                                        value={r.rate_pct}
                                        type="number"
                                        onSave={async () => { await new Promise((res) => setTimeout(res, 300)); }}
                                    />
                                    <span className="ml-0.5 text-sm text-zinc-500">%</span>
                                </td>
                                <td className="px-4 py-2.5">
                                    <input
                                        type="checkbox"
                                        defaultChecked={r.included}
                                        className="h-4 w-4 rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                                    />
                                </td>
                                <td className="px-4 py-2.5">
                                    <input
                                        type="checkbox"
                                        defaultChecked={r.digital_override}
                                        className="h-4 w-4 rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                                    />
                                </td>
                                <td className="px-4 py-2.5 text-right">
                                    <button className="rounded p-1 text-zinc-400 hover:text-rose-500 transition-colors">
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ─── OpEx tab ─────────────────────────────────────────────────────────────────

function OpexTab({ allocations }: { allocations: OpexAllocation[] }) {
    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <p className="text-sm text-zinc-600">Monthly fixed operating costs spread across the reporting period.</p>
                <button className="flex items-center gap-1.5 rounded-md bg-teal-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-teal-700 transition-colors">
                    <Plus className="h-3.5 w-3.5" />
                    Add item
                </button>
            </div>
            <div className="overflow-x-auto rounded-lg border border-zinc-200 bg-white">
                <table className="min-w-full">
                    <thead className="bg-zinc-50 border-b border-zinc-200">
                        <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            <th className="px-4 py-2.5 text-left">Category</th>
                            <th className="px-4 py-2.5 text-right">Monthly cost</th>
                            <th className="px-4 py-2.5 text-left">Allocation</th>
                            <th className="px-4 py-2.5 text-right"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-100">
                        {allocations.map((a) => (
                            <tr key={a.id} className="hover:bg-zinc-50 transition-colors">
                                <td className="px-4 py-2.5 text-sm font-medium text-zinc-800">{a.category}</td>
                                <td className="px-4 py-2.5 text-right">
                                    <InlineEditableCell
                                        value={a.monthly_cost}
                                        type="number"
                                        prefix="$"
                                        onSave={async () => { await new Promise((res) => setTimeout(res, 300)); }}
                                    />
                                </td>
                                <td className="px-4 py-2.5 text-sm text-zinc-500">{a.allocation}</td>
                                <td className="px-4 py-2.5 text-right">
                                    <button className="rounded p-1 text-zinc-400 hover:text-rose-500 transition-colors">
                                        <Trash2 className="h-3.5 w-3.5" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                        <tr className="bg-zinc-50">
                            <td className="px-4 py-2.5 text-sm font-semibold text-zinc-700">Total</td>
                            <td className="px-4 py-2.5 text-right tabular-nums text-sm font-semibold text-zinc-900">
                                {fmt(allocations.reduce((s, a) => s + a.monthly_cost, 0))}
                            </td>
                            <td colSpan={2} />
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function Costs(serverProps: Props) {
    const { props } = usePage<Props>();
    const workspaceSlug = props.workspace?.slug ?? '';

    // Use server data if present, fall back to mock
    const cogsRules = MOCK_COGS;
    const shippingRules = serverProps.shipping_rules ?? MOCK_SHIPPING;
    const txFeeRules = serverProps.transaction_fee_rules ?? MOCK_TX_FEES;
    const platformRules = serverProps.platform_fee_rules ?? MOCK_PLATFORM_FEES;
    const taxRules = serverProps.tax_rules ?? MOCK_TAX_RULES;
    const opexAllocations = serverProps.opex_allocations ?? MOCK_OPEX;

    const [activeTab, setActiveTab] = useState<CostTab>('cogs');
    const [showRecompute, setShowRecompute] = useState(false);

    const triggerRecompute = () => setShowRecompute(true);

    return (
        <SettingsLayout>
            <Head title="Cost Settings" />

            {/* Desktop-only banner on narrow viewports */}
            <div className="block xl:hidden">
                <div className="flex flex-col items-center justify-center py-16 text-center">
                    <Monitor className="mb-4 h-12 w-12 text-zinc-300" />
                    <h3 className="text-base font-semibold text-zinc-700">Desktop required</h3>
                    <p className="mt-2 max-w-sm text-sm text-zinc-500">
                        The cost rules editor uses inline-editable tables that require a wider screen. Open on a desktop (1280px+) to configure costs.
                    </p>
                </div>
            </div>

            <div className="hidden xl:block">
                {/* First-visit info banner */}
                <AlertBanner
                    message="Nexstage never estimates costs. Missing inputs degrade ProfitMode per-metric with amber dots — they never silently default to zero."
                    severity="info"
                    onDismiss={() => {}}
                    persistence={{ key: 'costs_info_banner', storage: 'local' }}
                />

                {/* Recomputing banner */}
                {showRecompute && (
                    <div className="mt-3 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <RefreshCw className="h-4 w-4 shrink-0 animate-spin" />
                        <span>Recomputing profit metrics across all pages… This may take a few minutes.</span>
                        <button
                            onClick={() => setShowRecompute(false)}
                            className="ml-auto text-amber-600 hover:text-amber-800"
                        >
                            &times;
                        </button>
                    </div>
                )}

                <div className="mt-4 mb-6">
                    <h2 className="text-xl font-semibold text-zinc-900">Costs</h2>
                    <p className="mt-1 text-sm text-zinc-500">
                        Configure COGS, shipping, fees, and taxes. Changes retroactively recompute profit metrics.
                    </p>
                </div>

                {/* Sub-nav tabs (vertical left list + right content) */}
                <div className="flex gap-6">
                    {/* Vertical tab nav */}
                    <nav className="w-44 shrink-0">
                        <ul className="space-y-0.5">
                            {TABS.map((tab) => (
                                <li key={tab.id}>
                                    <button
                                        type="button"
                                        onClick={() => setActiveTab(tab.id)}
                                        className={cn(
                                            'w-full rounded-md px-3 py-2 text-left text-sm font-medium transition-colors',
                                            activeTab === tab.id
                                                ? 'bg-zinc-900 text-white'
                                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900',
                                        )}
                                    >
                                        {tab.label}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    {/* Tab content */}
                    <div className="min-w-0 flex-1">
                        {activeTab === 'cogs'        && <CogsTab rules={cogsRules} />}
                        {activeTab === 'shipping'    && <ShippingTab rules={shippingRules} />}
                        {activeTab === 'transaction' && <TransactionFeesTab rules={txFeeRules} />}
                        {activeTab === 'platform'    && <PlatformFeesTab rules={platformRules} />}
                        {activeTab === 'tax'         && <TaxTab rules={taxRules} />}
                        {activeTab === 'opex'        && <OpexTab allocations={opexAllocations} />}
                    </div>
                </div>
            </div>
        </SettingsLayout>
    );
}
