/**
 * Settings / Workspace — workspace identity, attribution defaults, confidence thresholds,
 * advanced section (slug), and danger zone.
 *
 * Patterns used:
 * - Linear: inline-save, no page-level Save button (optimistic writes per UX §6.2)
 * - Vercel: collapsible "Advanced" section, danger zone with typed confirmation
 * - Stripe: two-column layout, section-card grouping, helper text per field
 *
 * @see docs/pages/settings.md §workspace
 * @see docs/UX.md §6.2 Optimistic writes
 */
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { SettingsLayout } from '@/Components/layouts/SettingsLayout';
import { SectionCard } from '@/Components/shared/SectionCard';
import { TimezoneSelect } from '@/Components/shared/TimezoneSelect';
import { Head, useForm, usePage } from '@inertiajs/react';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';
import { FormEventHandler, useState } from 'react';
import { ChevronDown, ChevronUp, AlertTriangle } from 'lucide-react';

interface WorkspaceSettingsProps {
    id: number;
    name: string;
    slug: string;
    reporting_currency: string;
    reporting_timezone: string;
    billing_plan: string | null;
    trial_ends_at: string | null;
    target_roas: number | null;
    target_cpo: number | null;
    holiday_lead_days: number;
    holiday_notification_days: number;
    commercial_notification_days: number;
    // Extended for new page spec
    primary_country_code?: string;
    fiscal_year_start_month?: number;
    default_attribution_model?: string;
    default_window?: string;
    default_accounting_mode?: string;
    default_profit_mode?: boolean;
    default_breakdown?: string;
    confidence_orders?: number;
    confidence_sessions?: number;
    confidence_impressions?: number;
    created_at?: string;
    stores_count?: number;
}

const CURRENCIES = [
    'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'CHF', 'JPY', 'SEK', 'NOK', 'DKK',
    'PLN', 'CZK', 'HUF', 'BRL', 'MXN', 'SGD', 'NZD',
];

const ATTRIBUTION_MODELS = [
    { value: 'last_non_direct', label: 'Last-non-direct (default)' },
    { value: 'last_touch',      label: 'Last-touch' },
    { value: 'first_touch',     label: 'First-touch' },
    { value: 'linear',          label: 'Linear' },
    { value: 'data_driven',     label: 'Data-driven' },
];

const WINDOWS = [
    { value: '1d',  label: '1 day' },
    { value: '7d',  label: '7 days (default)' },
    { value: '28d', label: '28 days' },
    { value: 'ltv', label: 'LTV' },
];

const BREAKDOWN_OPTIONS = [
    { value: 'none',             label: 'None (default)' },
    { value: 'country',          label: 'Country' },
    { value: 'channel',          label: 'Channel' },
    { value: 'campaign',         label: 'Campaign' },
    { value: 'product',          label: 'Product' },
    { value: 'device',           label: 'Device' },
    { value: 'customer_segment', label: 'Customer segment' },
];

const FISCAL_MONTHS = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="mt-1 text-sm text-rose-600">{message}</p>;
}

function HelperText({ children }: { children: React.ReactNode }) {
    return <p className="mt-1 text-sm text-zinc-500">{children}</p>;
}

export default function WorkspaceSettings({
    workspace,
    userRole,
}: {
    workspace: WorkspaceSettingsProps;
    userRole: string;
}) {
    const { props } = usePage<PageProps>();
    const workspaceSlug = props.workspace?.slug ?? workspace.slug;
    const w = (path: string) => wurl(workspaceSlug, path);

    const canEdit = userRole === 'owner' || userRole === 'admin';
    const isOwner = userRole === 'owner';

    const [advancedOpen, setAdvancedOpen] = useState(false);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [recomputeBanner, setRecomputeBanner] = useState(false);

    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        name: workspace.name,
        reporting_currency: workspace.reporting_currency,
        reporting_timezone: workspace.reporting_timezone,
        primary_country_code: workspace.primary_country_code ?? 'US',
        fiscal_year_start_month: workspace.fiscal_year_start_month ?? 1,
        default_attribution_model: workspace.default_attribution_model ?? 'last_non_direct',
        default_window: workspace.default_window ?? '7d',
        default_accounting_mode: workspace.default_accounting_mode ?? 'cash',
        default_profit_mode: workspace.default_profit_mode ?? false,
        default_breakdown: workspace.default_breakdown ?? 'none',
        confidence_orders: workspace.confidence_orders ?? 30,
        confidence_sessions: workspace.confidence_sessions ?? 100,
        confidence_impressions: workspace.confidence_impressions ?? 1000,
        target_roas: workspace.target_roas !== null ? String(workspace.target_roas) : '',
        target_cpo:  workspace.target_cpo  !== null ? String(workspace.target_cpo)  : '',
        holiday_lead_days: String(workspace.holiday_lead_days),
        holiday_notification_days: String(workspace.holiday_notification_days),
        commercial_notification_days: String(workspace.commercial_notification_days),
        // Slug is in advanced only, owner-only
        slug: workspace.slug,
    });

    const advancedForm = useForm({ slug: workspace.slug });
    const deleteForm = useForm({ confirmation: '' });

    const handleCurrencyChange = (val: string) => {
        setData('reporting_currency', val);
        setRecomputeBanner(true);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(w('/settings/workspace'), {
            onSuccess: () => setRecomputeBanner(false),
        });
    };

    const submitDelete: FormEventHandler = (e) => {
        e.preventDefault();
        deleteForm.delete(w('/settings/workspace'));
    };

    return (
        <SettingsLayout>
            <Head title="Workspace Settings" />

            {/* Recomputing banner — shown when currency is changed before save */}
            {recomputeBanner && (
                <div className="mb-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <AlertTriangle className="h-4 w-4 shrink-0" />
                    <span>Changing reporting currency will recompute all historical revenue figures. Save to apply.</span>
                </div>
            )}

            <div className="mb-6">
                <h2 className="text-xl font-semibold text-zinc-900">Workspace</h2>
                <p className="mt-1 text-sm text-zinc-500">Identity, defaults, and configuration for this workspace.</p>
            </div>

            <form onSubmit={submit} className="space-y-6">

                {/* Identity */}
                <SectionCard title="Identity" description="Name and regional settings for this workspace.">
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <Label htmlFor="name">Workspace name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="mt-1.5"
                                disabled={!canEdit}
                                maxLength={60}
                                required
                            />
                            <FieldError message={errors.name} />
                        </div>

                        <div>
                            <Label htmlFor="reporting_currency">Reporting currency</Label>
                            <select
                                id="reporting_currency"
                                value={data.reporting_currency}
                                onChange={(e) => handleCurrencyChange(e.target.value)}
                                disabled={!canEdit}
                                className="mt-1.5 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {CURRENCIES.map((c) => (
                                    <option key={c} value={c}>{c}</option>
                                ))}
                            </select>
                            <HelperText>Changing this triggers a background recomputation of all revenue figures.</HelperText>
                            <FieldError message={errors.reporting_currency} />
                        </div>

                        <div>
                            <Label htmlFor="reporting_timezone">Reporting timezone</Label>
                            <TimezoneSelect
                                id="reporting_timezone"
                                value={data.reporting_timezone}
                                onChange={(tz) => setData('reporting_timezone', tz)}
                                disabled={!canEdit}
                                className="mt-1.5"
                            />
                            <HelperText>Affects all date groupings and report periods.</HelperText>
                            <FieldError message={errors.reporting_timezone} />
                        </div>

                        <div>
                            <Label htmlFor="fiscal_year_start_month">Fiscal year start</Label>
                            <select
                                id="fiscal_year_start_month"
                                value={data.fiscal_year_start_month}
                                onChange={(e) => setData('fiscal_year_start_month', Number(e.target.value))}
                                disabled={!canEdit}
                                className="mt-1.5 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {FISCAL_MONTHS.map((month, i) => (
                                    <option key={i + 1} value={i + 1}>{month}</option>
                                ))}
                            </select>
                            <HelperText>Used for YTD and QTD calculations.</HelperText>
                        </div>

                        <div>
                            <Label htmlFor="primary_country_code">Primary country</Label>
                            <Input
                                id="primary_country_code"
                                value={data.primary_country_code}
                                onChange={(e) => setData('primary_country_code', e.target.value.toUpperCase().slice(0, 2))}
                                className="mt-1.5 font-mono uppercase"
                                disabled={!canEdit}
                                maxLength={2}
                                placeholder="US"
                            />
                            <HelperText>ISO 2-letter code. Used for tax VAT seeding and ad country fallback.</HelperText>
                        </div>
                    </div>
                </SectionCard>

                {/* Attribution defaults */}
                <SectionCard
                    title="Attribution defaults"
                    description="Starting values for every page in this workspace. Per-card overrides still work."
                >
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="default_attribution_model">Default attribution model</Label>
                            <select
                                id="default_attribution_model"
                                value={data.default_attribution_model}
                                onChange={(e) => setData('default_attribution_model', e.target.value)}
                                disabled={!canEdit}
                                className="mt-1.5 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {ATTRIBUTION_MODELS.map((m) => (
                                    <option key={m.value} value={m.value}>{m.label}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <Label htmlFor="default_window">Default attribution window</Label>
                            <select
                                id="default_window"
                                value={data.default_window}
                                onChange={(e) => setData('default_window', e.target.value)}
                                disabled={!canEdit}
                                className="mt-1.5 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {WINDOWS.map((w) => (
                                    <option key={w.value} value={w.value}>{w.label}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <Label htmlFor="default_accounting_mode">Default accounting mode</Label>
                            <select
                                id="default_accounting_mode"
                                value={data.default_accounting_mode}
                                onChange={(e) => setData('default_accounting_mode', e.target.value)}
                                disabled={!canEdit}
                                className="mt-1.5 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="cash">Cash Snapshot (default)</option>
                                <option value="accrual">Accrual Performance</option>
                            </select>
                            <HelperText>Cash Snapshot matches Shopify's P&L. Accrual aligns with ad platform reporting.</HelperText>
                        </div>

                        <div>
                            <Label htmlFor="default_breakdown">Default breakdown</Label>
                            <select
                                id="default_breakdown"
                                value={data.default_breakdown}
                                onChange={(e) => setData('default_breakdown', e.target.value)}
                                disabled={!canEdit}
                                className="mt-1.5 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {BREAKDOWN_OPTIONS.map((b) => (
                                    <option key={b.value} value={b.value}>{b.label}</option>
                                ))}
                            </select>
                        </div>

                        <div className="sm:col-span-2">
                            <div className="flex items-center gap-3">
                                <input
                                    id="default_profit_mode"
                                    type="checkbox"
                                    checked={data.default_profit_mode}
                                    onChange={(e) => setData('default_profit_mode', e.target.checked)}
                                    disabled={!canEdit}
                                    className="h-4 w-4 rounded border-zinc-300 text-teal-600 focus:ring-teal-500 disabled:cursor-not-allowed"
                                />
                                <Label htmlFor="default_profit_mode" className="mb-0 cursor-pointer font-normal">
                                    Enable Profit mode by default
                                </Label>
                            </div>
                            <HelperText>When on, every page opens in profit view. Useful for CFO-style dashboards.</HelperText>
                        </div>
                    </div>
                </SectionCard>

                {/* Confidence thresholds */}
                <SectionCard
                    title="Confidence thresholds"
                    description="Minimum sample sizes before metrics are shown without a low-confidence warning."
                >
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <div>
                            <Label htmlFor="confidence_orders">Orders threshold</Label>
                            <Input
                                id="confidence_orders"
                                type="number"
                                min={1}
                                max={10000}
                                value={data.confidence_orders}
                                onChange={(e) => setData('confidence_orders', Number(e.target.value))}
                                className="mt-1.5 tabular-nums"
                                disabled={!canEdit}
                            />
                            <HelperText>Default: 30 orders</HelperText>
                        </div>
                        <div>
                            <Label htmlFor="confidence_sessions">Sessions threshold</Label>
                            <Input
                                id="confidence_sessions"
                                type="number"
                                min={1}
                                max={100000}
                                value={data.confidence_sessions}
                                onChange={(e) => setData('confidence_sessions', Number(e.target.value))}
                                className="mt-1.5 tabular-nums"
                                disabled={!canEdit}
                            />
                            <HelperText>Default: 100 sessions</HelperText>
                        </div>
                        <div>
                            <Label htmlFor="confidence_impressions">Impressions threshold</Label>
                            <Input
                                id="confidence_impressions"
                                type="number"
                                min={1}
                                max={10000000}
                                value={data.confidence_impressions}
                                onChange={(e) => setData('confidence_impressions', Number(e.target.value))}
                                className="mt-1.5 tabular-nums"
                                disabled={!canEdit}
                            />
                            <HelperText>Default: 1,000 impressions</HelperText>
                        </div>
                    </div>
                </SectionCard>

                {/* Performance targets (existing fields) */}
                <SectionCard title="Performance targets" description="Used to classify campaigns as Winners or Losers. Leave blank to use break-even defaults (1.0× ROAS).">
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="target_roas">Target ROAS (×)</Label>
                            <Input
                                id="target_roas"
                                type="number"
                                min="0"
                                max="100"
                                step="0.1"
                                value={data.target_roas}
                                onChange={(e) => setData('target_roas', e.target.value)}
                                className="mt-1.5 tabular-nums"
                                disabled={!canEdit}
                                placeholder="e.g. 3.5"
                            />
                            <FieldError message={errors.target_roas} />
                        </div>
                        <div>
                            <Label htmlFor="target_cpo">Target CPO ({workspace.reporting_currency})</Label>
                            <Input
                                id="target_cpo"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.target_cpo}
                                onChange={(e) => setData('target_cpo', e.target.value)}
                                className="mt-1.5 tabular-nums"
                                disabled={!canEdit}
                                placeholder="e.g. 25.00"
                            />
                            <FieldError message={errors.target_cpo} />
                        </div>
                    </div>
                </SectionCard>

                {/* Save */}
                {canEdit && (
                    <div className="flex items-center gap-4">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
                        >
                            {processing ? 'Saving…' : 'Save changes'}
                        </button>
                        {recentlySuccessful && (
                            <span className="text-sm text-emerald-600">Saved.</span>
                        )}
                        {!canEdit && (
                            <span className="text-sm text-zinc-400">Only Admins can change workspace settings.</span>
                        )}
                    </div>
                )}
            </form>

            {/* Advanced (collapsible) — slug */}
            <div className="mt-6">
                <button
                    type="button"
                    onClick={() => setAdvancedOpen((v) => !v)}
                    className="flex w-full items-center justify-between rounded-lg border border-zinc-200 bg-white px-6 py-4 text-left text-sm font-semibold text-zinc-700 hover:bg-zinc-50 transition-colors"
                >
                    <span>Advanced</span>
                    {advancedOpen ? (
                        <ChevronUp className="h-4 w-4 text-zinc-400" />
                    ) : (
                        <ChevronDown className="h-4 w-4 text-zinc-400" />
                    )}
                </button>

                {advancedOpen && (
                    <div className="rounded-b-lg border border-t-0 border-zinc-200 bg-white px-6 py-5 space-y-4">
                        <div>
                            <Label htmlFor="slug">Workspace slug</Label>
                            <Input
                                id="slug"
                                value={advancedForm.data.slug}
                                onChange={(e) =>
                                    advancedForm.setData('slug', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '-'))
                                }
                                className="mt-1.5 font-mono"
                                disabled={!isOwner}
                            />
                            {isOwner ? (
                                <HelperText>Changing this updates all workspace URLs and invalidates bookmarks.</HelperText>
                            ) : (
                                <HelperText>Only the workspace owner can change the slug.</HelperText>
                            )}
                        </div>
                        <div className="text-sm text-zinc-500 space-y-1">
                            <p>Workspace ID: <span className="font-mono text-zinc-700">{workspace.id}</span></p>
                            {workspace.created_at && (
                                <p>Created: <span className="text-zinc-700">{new Date(workspace.created_at).toLocaleDateString()}</span></p>
                            )}
                            {workspace.stores_count !== undefined && (
                                <p>Stores: <span className="text-zinc-700">{workspace.stores_count}</span></p>
                            )}
                        </div>
                        {isOwner && (
                            <button
                                type="button"
                                onClick={() => {
                                    advancedForm.setData({ ...advancedForm.data, slug: advancedForm.data.slug });
                                    advancedForm.patch(w('/settings/workspace'));
                                }}
                                disabled={advancedForm.processing}
                                className="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                            >
                                {advancedForm.processing ? 'Saving…' : 'Update slug'}
                            </button>
                        )}
                    </div>
                )}
            </div>

            {/* Danger zone */}
            {isOwner && (
                <div className="mt-6 overflow-hidden rounded-lg border border-rose-200 bg-white">
                    <div className="border-b border-rose-200 bg-rose-50 px-6 py-4">
                        <h3 className="text-base font-semibold text-rose-700">Danger zone</h3>
                    </div>
                    <div className="px-6 py-5">
                        <p className="text-sm text-zinc-600">
                            Deleting this workspace permanently removes all data after 30 days. You can restore it within that window.
                        </p>
                        <p className="mt-1 text-sm text-zinc-600">
                            Cancel any active subscription in{' '}
                            <a href={w('/settings/billing')} className="text-teal-600 hover:underline">
                                Billing settings
                            </a>{' '}
                            before deleting.
                        </p>

                        {!showDeleteConfirm ? (
                            <button
                                type="button"
                                onClick={() => setShowDeleteConfirm(true)}
                                className="mt-4 rounded-md border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50 transition-colors"
                            >
                                Delete workspace
                            </button>
                        ) : (
                            <form onSubmit={submitDelete} className="mt-4 space-y-4">
                                <div>
                                    <Label htmlFor="delete_confirmation">
                                        Type &ldquo;{workspace.name}&rdquo; to confirm
                                    </Label>
                                    <Input
                                        id="delete_confirmation"
                                        value={deleteForm.data.confirmation}
                                        onChange={(e) => deleteForm.setData('confirmation', e.target.value)}
                                        className="mt-1.5"
                                        placeholder={workspace.name}
                                        autoComplete="off"
                                    />
                                    {deleteForm.errors.confirmation && (
                                        <FieldError message={deleteForm.errors.confirmation} />
                                    )}
                                </div>
                                <div className="flex gap-3">
                                    <button
                                        type="submit"
                                        disabled={
                                            deleteForm.processing ||
                                            deleteForm.data.confirmation !== workspace.name
                                        }
                                        className="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-50 transition-colors"
                                    >
                                        Confirm deletion
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowDeleteConfirm(false)}
                                        className="text-sm text-zinc-500 hover:text-zinc-700"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            )}
        </SettingsLayout>
    );
}
