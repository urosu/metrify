/**
 * Settings / Audit — workspace audit log. Who changed what, when, before/after.
 * Row click opens a DrawerSidePanel with full diff.
 *
 * Patterns used:
 * - Linear: dense table, click-to-drawer for detail, JetBrains Mono for diff values
 * - Stripe: filter by user + event type, clear filter chips
 * - Vercel: relative timestamps, concise change summaries
 *
 * @see docs/pages/settings.md — audit log panel
 * @see docs/UX.md §5.5 DataTable, §5.10 DrawerSidePanel
 */
import { useState, useMemo } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { SettingsLayout } from '@/Components/layouts/SettingsLayout';
import { DrawerSidePanel } from '@/Components/shared/DrawerSidePanel';
import type { PageProps } from '@/types';
import { Search, Filter } from 'lucide-react';
import { cn } from '@/lib/utils';

// ─── Mock data ─────────────────────────────────────────────────────────────────

interface AuditEntry {
    id: string;
    user_email: string;
    action: 'created' | 'updated' | 'deleted';
    subject: string;
    subject_label: string;
    field?: string;
    before?: string | number | boolean | null;
    after?: string | number | boolean | null;
    at: string;
    reversible: boolean;
}

const MOCK_AUDIT: AuditEntry[] = [
    { id: 'evt_01', user_email: 'owner@acme.co',   action: 'updated', subject: 'cogs_rule',            subject_label: 'COGS Rule',            field: 'cogs',                   before: 22.00, after: 22.50,          at: '2026-04-29T08:12:00Z', reversible: true },
    { id: 'evt_02', user_email: 'cfo@acme.co',     action: 'updated', subject: 'billing_details',      subject_label: 'Billing Details',      field: 'vat_number',             before: null,  after: 'DE123456789',  at: '2026-04-28T15:34:00Z', reversible: false },
    { id: 'evt_03', user_email: 'owner@acme.co',   action: 'created', subject: 'workspace_target',     subject_label: 'Target',               field: 'Revenue (monthly)',      before: null,  after: 150000,         at: '2026-04-28T10:02:00Z', reversible: true },
    { id: 'evt_04', user_email: 'admin@acme.co',   action: 'updated', subject: 'team_member_role',     subject_label: 'Team Member',          field: 'role (alice@acme.co)',   before: 'member', after: 'admin',   at: '2026-04-27T17:45:00Z', reversible: true },
    { id: 'evt_05', user_email: 'owner@acme.co',   action: 'updated', subject: 'workspace',            subject_label: 'Workspace',            field: 'reporting_currency',     before: 'EUR', after: 'USD',          at: '2026-04-27T09:00:00Z', reversible: true },
    { id: 'evt_06', user_email: 'cfo@acme.co',     action: 'updated', subject: 'opex_allocation',      subject_label: 'OpEx',                 field: 'Salaries',               before: 7500,  after: 8500,           at: '2026-04-26T14:20:00Z', reversible: true },
    { id: 'evt_07', user_email: 'owner@acme.co',   action: 'deleted', subject: 'shipping_rule',        subject_label: 'Shipping Rule',        field: 'US Priority Express',    before: 14.99, after: null,           at: '2026-04-25T11:15:00Z', reversible: false },
    { id: 'evt_08', user_email: 'admin@acme.co',   action: 'updated', subject: 'notification_prefs',   subject_label: 'Notifications',        field: 'high.email',             before: false, after: true,           at: '2026-04-24T09:30:00Z', reversible: true },
    { id: 'evt_09', user_email: 'owner@acme.co',   action: 'created', subject: 'workspace_invitation', subject_label: 'Invitation',           field: 'bob@acme.co (member)',   before: null,  after: 'invited',      at: '2026-04-23T16:00:00Z', reversible: false },
    { id: 'evt_10', user_email: 'owner@acme.co',   action: 'updated', subject: 'cogs_rule',            subject_label: 'COGS Rule',            field: 'cogs (JCKT-TRAIL-L)',    before: 58.00, after: 64.00,          at: '2026-04-22T13:40:00Z', reversible: true },
    { id: 'evt_11', user_email: 'cfo@acme.co',     action: 'updated', subject: 'tax_rule',             subject_label: 'Tax Rule',             field: 'DE rate',                before: 19.0,  after: 19.0,           at: '2026-04-21T10:10:00Z', reversible: true },
    { id: 'evt_12', user_email: 'owner@acme.co',   action: 'updated', subject: 'workspace',            subject_label: 'Workspace',            field: 'name',                   before: 'Acme Co', after: 'Acme Outdoor Co', at: '2026-04-20T08:05:00Z', reversible: true },
    { id: 'evt_13', user_email: 'admin@acme.co',   action: 'created', subject: 'platform_fee',         subject_label: 'Platform Fee',         field: 'Klaviyo',                before: null,  after: 120.00,         at: '2026-04-18T11:22:00Z', reversible: true },
    { id: 'evt_14', user_email: 'owner@acme.co',   action: 'updated', subject: 'workspace',            subject_label: 'Workspace',            field: 'fiscal_year_start_month', before: 4, after: 1,               at: '2026-04-16T09:00:00Z', reversible: true },
    { id: 'evt_15', user_email: 'cfo@acme.co',     action: 'updated', subject: 'transaction_fee',      subject_label: 'Transaction Fee',      field: 'Stripe pct',             before: 3.0, after: 2.9,             at: '2026-04-14T14:00:00Z', reversible: true },
    { id: 'evt_16', user_email: 'owner@acme.co',   action: 'updated', subject: 'workspace',            subject_label: 'Workspace',            field: 'default_attribution_model', before: 'last_touch', after: 'last_non_direct', at: '2026-04-12T10:30:00Z', reversible: true },
    { id: 'evt_17', user_email: 'admin@acme.co',   action: 'created', subject: 'cogs_rule',            subject_label: 'COGS Rule',            field: 'SOCKS-3PK',              before: null,  after: 6.00,           at: '2026-04-10T15:45:00Z', reversible: true },
    { id: 'evt_18', user_email: 'owner@acme.co',   action: 'updated', subject: 'team_member_role',     subject_label: 'Team Member',          field: 'role (dave@acme.co)',    before: 'admin', after: 'member',    at: '2026-04-08T09:20:00Z', reversible: true },
    { id: 'evt_19', user_email: 'cfo@acme.co',     action: 'updated', subject: 'billing_details',      subject_label: 'Billing Details',      field: 'billing_email',          before: 'old@acme.co', after: 'cfo@acme.co', at: '2026-04-06T12:00:00Z', reversible: false },
    { id: 'evt_20', user_email: 'owner@acme.co',   action: 'deleted', subject: 'cogs_rule',            subject_label: 'COGS Rule',            field: 'PANT-CARGO-OLD',         before: 35.00, after: null,           at: '2026-04-04T16:30:00Z', reversible: false },
    // 30 more entries for realism
    ...Array.from({ length: 30 }, (_, i) => ({
        id: `evt_${21 + i}`,
        user_email: ['owner@acme.co', 'cfo@acme.co', 'admin@acme.co'][i % 3],
        action: (['updated', 'created', 'updated', 'updated', 'deleted'] as const)[i % 5],
        subject: (['cogs_rule', 'workspace', 'notification_prefs', 'team_member_role', 'opex_allocation'] as const)[i % 5],
        subject_label: ['COGS Rule', 'Workspace', 'Notifications', 'Team Member', 'OpEx'][i % 5],
        field: `field_${i}`,
        before: i * 2.5,
        after: i * 2.5 + 1,
        at: new Date(Date.now() - (i + 20) * 86400000 * 2).toISOString(),
        reversible: i % 3 !== 0,
    })),
];

const SUBJECT_OPTIONS = [
    { value: '', label: 'All types' },
    { value: 'workspace', label: 'Workspace' },
    { value: 'team_member_role', label: 'Team' },
    { value: 'cogs_rule', label: 'COGS' },
    { value: 'shipping_rule', label: 'Shipping' },
    { value: 'transaction_fee', label: 'Transaction fees' },
    { value: 'opex_allocation', label: 'OpEx' },
    { value: 'tax_rule', label: 'Tax' },
    { value: 'billing_details', label: 'Billing' },
    { value: 'notification_prefs', label: 'Notifications' },
    { value: 'workspace_target', label: 'Targets' },
];

const ACTION_COLORS = {
    created: 'bg-emerald-50 text-emerald-700',
    updated: 'bg-sky-50 text-sky-700',
    deleted: 'bg-rose-50 text-rose-700',
};

function RelTime({ iso }: { iso: string }) {
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    if (mins < 2) return <span title={iso}>just now</span>;
    if (mins < 60) return <span title={iso}>{mins}m ago</span>;
    if (hours < 24) return <span title={iso}>{hours}h ago</span>;
    if (days < 7) return <span title={iso}>{days}d ago</span>;
    return <span title={iso}>{new Date(iso).toLocaleDateString()}</span>;
}

function DiffValue({ value }: { value: string | number | boolean | null | undefined }) {
    if (value === null || value === undefined) return <span className="text-zinc-400">—</span>;
    return <span className="font-mono text-xs">{String(value)}</span>;
}

export default function Audit() {
    const { props } = usePage<PageProps>();

    const [search, setSearch] = useState('');
    const [userFilter, setUserFilter] = useState('');
    const [subjectFilter, setSubjectFilter] = useState('');
    const [selectedEntry, setSelectedEntry] = useState<AuditEntry | null>(null);

    const uniqueUsers = useMemo(
        () => Array.from(new Set(MOCK_AUDIT.map((e) => e.user_email))).sort(),
        [],
    );

    const filtered = useMemo(() => {
        return MOCK_AUDIT.filter((e) => {
            const matchSearch =
                !search ||
                e.user_email.toLowerCase().includes(search.toLowerCase()) ||
                e.subject_label.toLowerCase().includes(search.toLowerCase()) ||
                (e.field ?? '').toLowerCase().includes(search.toLowerCase());
            const matchUser    = !userFilter    || e.user_email === userFilter;
            const matchSubject = !subjectFilter || e.subject === subjectFilter;
            return matchSearch && matchUser && matchSubject;
        });
    }, [search, userFilter, subjectFilter]);

    return (
        <SettingsLayout>
            <Head title="Audit Log" />

            <div className="mb-6">
                <h2 className="text-xl font-semibold text-zinc-900">Audit log</h2>
                <p className="mt-1 text-sm text-zinc-500">
                    Recent settings changes — who, what, when, before and after.
                </p>
            </div>

            {/* Filters */}
            <div className="mb-4 flex flex-wrap items-center gap-3">
                <div className="relative flex-1 min-w-48">
                    <Search className="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400" />
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search changes…"
                        className="w-full rounded-md border border-zinc-200 bg-white py-2 pl-9 pr-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    />
                </div>
                <select
                    value={userFilter}
                    onChange={(e) => setUserFilter(e.target.value)}
                    className="rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                >
                    <option value="">All users</option>
                    {uniqueUsers.map((u) => (
                        <option key={u} value={u}>{u}</option>
                    ))}
                </select>
                <select
                    value={subjectFilter}
                    onChange={(e) => setSubjectFilter(e.target.value)}
                    className="rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                >
                    {SUBJECT_OPTIONS.map((o) => (
                        <option key={o.value} value={o.value}>{o.label}</option>
                    ))}
                </select>
                {(search || userFilter || subjectFilter) && (
                    <button
                        type="button"
                        onClick={() => { setSearch(''); setUserFilter(''); setSubjectFilter(''); }}
                        className="rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-500 hover:bg-zinc-50 transition-colors"
                    >
                        Clear filters
                    </button>
                )}
            </div>

            {/* Table */}
            <div className="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                <div className="overflow-x-auto">
                    <table className="min-w-full">
                        <thead className="bg-zinc-50 border-b border-zinc-200">
                            <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                <th className="px-4 py-2.5 text-left">When</th>
                                <th className="px-4 py-2.5 text-left">Who</th>
                                <th className="px-4 py-2.5 text-left">Action</th>
                                <th className="px-4 py-2.5 text-left">Subject</th>
                                <th className="px-4 py-2.5 text-left">Field</th>
                                <th className="px-4 py-2.5 text-left">Before → After</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-100">
                            {filtered.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-sm text-zinc-400">
                                        No events match your filters.
                                    </td>
                                </tr>
                            ) : (
                                filtered.map((entry) => (
                                    <tr
                                        key={entry.id}
                                        onClick={() => setSelectedEntry(entry)}
                                        className="cursor-pointer hover:bg-zinc-50 transition-colors"
                                    >
                                        <td className="px-4 py-2.5 text-sm text-zinc-500 tabular-nums whitespace-nowrap">
                                            <RelTime iso={entry.at} />
                                        </td>
                                        <td className="px-4 py-2.5">
                                            <span className="font-mono text-xs text-zinc-700">{entry.user_email}</span>
                                        </td>
                                        <td className="px-4 py-2.5">
                                            <span className={cn(
                                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                ACTION_COLORS[entry.action],
                                            )}>
                                                {entry.action}
                                            </span>
                                        </td>
                                        <td className="px-4 py-2.5 text-sm text-zinc-700">
                                            {entry.subject_label}
                                        </td>
                                        <td className="px-4 py-2.5 font-mono text-xs text-zinc-600 max-w-[180px] truncate">
                                            {entry.field ?? '—'}
                                        </td>
                                        <td className="px-4 py-2.5 text-sm">
                                            {entry.before !== null && entry.before !== undefined && (
                                                <>
                                                    <span className="font-mono text-xs text-rose-600 line-through opacity-70">
                                                        <DiffValue value={entry.before} />
                                                    </span>
                                                    <span className="mx-1 text-zinc-400">→</span>
                                                </>
                                            )}
                                            {entry.after !== null && entry.after !== undefined && (
                                                <span className="font-mono text-xs text-emerald-700">
                                                    <DiffValue value={entry.after} />
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
                {filtered.length > 0 && (
                    <div className="border-t border-zinc-100 bg-zinc-50 px-4 py-2.5 text-xs text-zinc-400">
                        Showing {filtered.length} of {MOCK_AUDIT.length} events. Full audit export available in v2.
                    </div>
                )}
            </div>

            {/* Detail drawer */}
            <DrawerSidePanel
                open={selectedEntry !== null}
                onClose={() => setSelectedEntry(null)}
                title="Audit event"
                subtitle={selectedEntry ? <span className="font-mono text-xs">{selectedEntry.id}</span> : undefined}
            >
                {selectedEntry && (
                    <div className="px-5 py-5 space-y-5">
                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-zinc-400">When</p>
                                <p className="mt-1 text-zinc-800">{new Date(selectedEntry.at).toLocaleString()}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-zinc-400">Who</p>
                                <p className="mt-1 font-mono text-xs text-zinc-700">{selectedEntry.user_email}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-zinc-400">Action</p>
                                <p className="mt-1">
                                    <span className={cn(
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        ACTION_COLORS[selectedEntry.action],
                                    )}>
                                        {selectedEntry.action}
                                    </span>
                                </p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-zinc-400">Subject</p>
                                <p className="mt-1 text-zinc-800">{selectedEntry.subject_label}</p>
                            </div>
                        </div>

                        {selectedEntry.field && (
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-zinc-400">Field changed</p>
                                <p className="mt-1 font-mono text-sm text-zinc-700">{selectedEntry.field}</p>
                            </div>
                        )}

                        {/* Before/after diff */}
                        <div className="rounded-lg border border-zinc-200 overflow-hidden">
                            <div className="bg-zinc-50 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                Change diff
                            </div>
                            <div className="divide-y divide-zinc-100">
                                <div className="flex items-center gap-3 px-4 py-3">
                                    <span className="w-14 text-xs font-semibold text-rose-500">Before</span>
                                    <span className={cn(
                                        'font-mono text-sm',
                                        selectedEntry.before === null ? 'text-zinc-400 italic' : 'text-zinc-800',
                                    )}>
                                        {selectedEntry.before === null ? 'empty' : String(selectedEntry.before)}
                                    </span>
                                </div>
                                <div className="flex items-center gap-3 px-4 py-3">
                                    <span className="w-14 text-xs font-semibold text-emerald-600">After</span>
                                    <span className={cn(
                                        'font-mono text-sm',
                                        selectedEntry.after === null ? 'text-zinc-400 italic' : 'text-zinc-800',
                                    )}>
                                        {selectedEntry.after === null ? 'deleted' : String(selectedEntry.after)}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {selectedEntry.reversible && (
                            <div className="pt-2">
                                <button
                                    type="button"
                                    className="w-full rounded-md border border-zinc-200 py-2 text-sm text-zinc-600 hover:bg-zinc-50 transition-colors"
                                >
                                    Revert this change
                                </button>
                            </div>
                        )}
                        {!selectedEntry.reversible && (
                            <p className="text-xs text-zinc-400">This change cannot be reverted automatically.</p>
                        )}
                    </div>
                )}
            </DrawerSidePanel>
        </SettingsLayout>
    );
}
