import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, Trash2, RotateCcw, FlaskConical } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { SectionCard } from '@/Components/shared/SectionCard';
import { SubNavTabs } from '@/Components/shared/SubNavTabs';
import { DataTable } from '@/Components/shared/DataTable';
import { EmptyState } from '@/Components/shared/EmptyState';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';
import type { Column } from '@/Components/shared/DataTable';

// ─── Types ────────────────────────────────────────────────────────────────────

interface MappingRow {
    id: number;
    utm_source_pattern: string;
    utm_medium_pattern: string | null;
    channel_name: string;
    channel_type: string;
    is_global: boolean;
}

interface UnrecognizedRow {
    source: string;
    medium: string | null;
    order_count: number;
    revenue: number;
}

interface Props {
    workspace_mappings: MappingRow[];
    global_mappings: MappingRow[];
    unrecognized: UnrecognizedRow[];
    is_recomputing: boolean;
}

const CHANNEL_TYPES = [
    'paid_social', 'paid_search', 'organic_search', 'organic_social',
    'email', 'direct', 'referral', 'affiliate', 'sms', 'other',
] as const;

// ─── Add Mapping Modal ────────────────────────────────────────────────────────

interface AddModalProps {
    open: boolean;
    processing: boolean;
    prefill?: { source: string; medium: string | null };
    onClose: () => void;
    onSubmit: (data: { utm_source_pattern: string; utm_medium_pattern: string; channel_name: string; channel_type: string }) => void;
}

function AddMappingModal({ open, processing, prefill, onClose, onSubmit }: AddModalProps) {
    const [source,      setSource]      = useState(prefill?.source ?? '');
    const [medium,      setMedium]      = useState(prefill?.medium ?? '');
    const [channelName, setChannelName] = useState('');
    const [channelType, setChannelType] = useState<string>('other');
    const [errors, setErrors]           = useState<Record<string, string>>({});

    if (!open) return null;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const next: Record<string, string> = {};
        if (!source.trim())      next.utm_source_pattern = 'Source is required.';
        if (!channelName.trim()) next.channel_name = 'Channel name is required.';
        if (Object.keys(next).length) { setErrors(next); return; }
        setErrors({});
        onSubmit({ utm_source_pattern: source.trim(), utm_medium_pattern: medium.trim(), channel_name: channelName.trim(), channel_type: channelType });
    };

    return (
        <>
            <div className="fixed inset-0 z-40 bg-black/40" onClick={onClose} />
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                <form
                    onSubmit={handleSubmit}
                    className="w-full max-w-md rounded-xl border border-border bg-card p-6 shadow-lg"
                >
                    <h2 className="mb-4 text-base font-semibold text-foreground">Add channel mapping</h2>
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-foreground mb-1">
                                UTM source <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={source}
                                onChange={(e) => setSource(e.target.value)}
                                placeholder="e.g. facebook"
                                className="w-full rounded-md border border-input px-3 py-2 text-sm focus:border-primary/70 focus:outline-none focus:ring-1 focus:ring-primary/50"
                            />
                            {errors.utm_source_pattern && <p className="mt-1 text-sm text-red-600">{errors.utm_source_pattern}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-foreground mb-1">UTM medium (optional)</label>
                            <input
                                type="text"
                                value={medium}
                                onChange={(e) => setMedium(e.target.value)}
                                placeholder="e.g. cpc (leave blank to match all mediums)"
                                className="w-full rounded-md border border-input px-3 py-2 text-sm focus:border-primary/70 focus:outline-none focus:ring-1 focus:ring-primary/50"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-foreground mb-1">
                                Channel name <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={channelName}
                                onChange={(e) => setChannelName(e.target.value)}
                                placeholder="e.g. Facebook Ads"
                                className="w-full rounded-md border border-input px-3 py-2 text-sm focus:border-primary/70 focus:outline-none focus:ring-1 focus:ring-primary/50"
                            />
                            {errors.channel_name && <p className="mt-1 text-sm text-red-600">{errors.channel_name}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-foreground mb-1">Channel type</label>
                            <select
                                value={channelType}
                                onChange={(e) => setChannelType(e.target.value)}
                                className="w-full rounded-md border border-input px-3 py-2 text-sm focus:border-primary/70 focus:outline-none focus:ring-1 focus:ring-primary/50"
                            >
                                {CHANNEL_TYPES.map((t) => (
                                    <option key={t} value={t}>{t.replace(/_/g, ' ')}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-md border border-input px-4 py-2 text-sm font-medium text-foreground hover:bg-muted/50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-50 transition-colors"
                        >
                            {processing ? 'Saving…' : 'Add mapping'}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}

// ─── Test Mapping Widget ──────────────────────────────────────────────────────

/**
 * Client-side mapping tester — runs the same priority logic as
 * ChannelClassifierService without a round-trip.
 * Priority: workspace rows first (ordered by id desc), then global rows.
 * Match: source matches if utm_source_pattern === '*' OR equals input source.
 *        medium matches if utm_medium_pattern is null/'' OR equals input medium.
 */
function TestMappingWidget({
    workspaceMappings,
    globalMappings,
}: {
    workspaceMappings: MappingRow[];
    globalMappings: MappingRow[];
}) {
    const [testSource, setTestSource] = useState('');
    const [testMedium, setTestMedium] = useState('');
    const [result, setResult] = useState<{ channel_name: string; channel_type: string; tier: 'workspace' | 'global' } | null | 'no-match'>(null);

    function runTest() {
        const src = testSource.trim().toLowerCase();
        const med = testMedium.trim().toLowerCase();

        const allRows = [
            ...workspaceMappings.map(r => ({ ...r, tier: 'workspace' as const })),
            ...globalMappings.map(r => ({ ...r, tier: 'global' as const })),
        ];

        for (const row of allRows) {
            const sourceMatch = row.utm_source_pattern === '*' || row.utm_source_pattern.toLowerCase() === src;
            const mediumPattern = row.utm_medium_pattern ? row.utm_medium_pattern.toLowerCase() : null;
            const mediumMatch = mediumPattern === null || mediumPattern === '' || mediumPattern === med;
            if (sourceMatch && mediumMatch) {
                setResult({ channel_name: row.channel_name, channel_type: row.channel_type, tier: row.tier });
                return;
            }
        }
        setResult('no-match');
    }

    return (
        <SectionCard title="Test mapping">
            <p className="mb-3 text-sm text-muted-foreground">
                Enter a UTM source and medium to see which rule would match.
            </p>
            <div className="flex items-end gap-3 flex-wrap">
                <div className="flex flex-col gap-1">
                    <label className="text-xs font-medium text-muted-foreground">utm_source</label>
                    <input
                        type="text"
                        value={testSource}
                        onChange={e => setTestSource(e.target.value)}
                        placeholder="e.g. facebook"
                        className="rounded-md border border-input px-3 py-2 text-sm focus:border-primary/70 focus:outline-none focus:ring-1 focus:ring-primary/50 w-44"
                    />
                </div>
                <div className="flex flex-col gap-1">
                    <label className="text-xs font-medium text-muted-foreground">utm_medium</label>
                    <input
                        type="text"
                        value={testMedium}
                        onChange={e => setTestMedium(e.target.value)}
                        placeholder="e.g. cpc (optional)"
                        className="rounded-md border border-input px-3 py-2 text-sm focus:border-primary/70 focus:outline-none focus:ring-1 focus:ring-primary/50 w-48"
                    />
                </div>
                <button
                    onClick={runTest}
                    disabled={!testSource.trim()}
                    className="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-50 transition-colors"
                >
                    <FlaskConical className="h-4 w-4" />
                    Test
                </button>
            </div>
            {result !== null && (
                <div className="mt-3">
                    {result === 'no-match' ? (
                        <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                            No matching rule found — this traffic will be unclassified. Add a mapping above to fix it.
                        </div>
                    ) : (
                        <div className="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                            Matched <strong>{result.channel_name}</strong>{' '}
                            <span className="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 capitalize">
                                {result.channel_type.replace(/_/g, ' ')}
                            </span>{' '}
                            via{' '}
                            <span className="font-medium">{result.tier === 'workspace' ? 'workspace override' : 'global default'}</span>.
                        </div>
                    )}
                </div>
            )}
        </SectionCard>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function ChannelMappings({ workspace_mappings, global_mappings, unrecognized, is_recomputing }: Props) {
    const { workspace: ws } = usePage<PageProps>().props;
    const wsSlug = ws?.slug;
    const w = (path: string) => wurl(wsSlug, path);

    const [modalOpen,   setModalOpen]   = useState(false);
    const [prefill,     setPrefill]     = useState<{ source: string; medium: string | null } | undefined>();
    const [processing,  setProcessing]  = useState(false);
    const [deletingId,  setDeletingId]  = useState<number | null>(null);

    const tabs = [
        { label: 'Tag Generator',     value: 'tag-generator',    href: w('/tools/tag-generator'),     active: false },
        { label: 'Naming Convention', value: 'naming-convention', href: w('/tools/naming-convention'), active: false },
        { label: 'Channel Mappings',  value: 'channel-mappings',  href: w('/tools/channel-mappings'),  active: true  },
    ];

    const handleAdd = (data: { utm_source_pattern: string; utm_medium_pattern: string; channel_name: string; channel_type: string }) => {
        setProcessing(true);
        router.post(w('/tools/channel-mappings'), data, {
            onFinish: () => { setProcessing(false); setModalOpen(false); setPrefill(undefined); },
        });
    };

    const handleDelete = (id: number) => {
        if (!confirm('Delete this mapping? Orders will be reclassified in the background.')) return;
        setDeletingId(id);
        router.delete(w(`/tools/channel-mappings/${id}`), {
            onFinish: () => setDeletingId(null),
        });
    };

    const handleImportDefaults = () => {
        if (!confirm('Re-seed global defaults? This replaces all default-tier rows but keeps your custom overrides.')) return;
        router.post(w('/tools/channel-mappings/import-defaults'), {}, {});
    };

    const WORKSPACE_COLUMNS: Column<MappingRow>[] = [
        { key: 'utm_source_pattern', header: 'UTM source', sortable: true },
        {
            key: 'utm_medium_pattern',
            header: 'UTM medium',
            render: (v) => <span className="text-muted-foreground">{v ? String(v) : <em className="text-muted-foreground/70">any</em>}</span>,
        },
        { key: 'channel_name', header: 'Channel name', sortable: true },
        {
            key: 'channel_type',
            header: 'Type',
            render: (v) => (
                <span className="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground capitalize">
                    {String(v).replace(/_/g, ' ')}
                </span>
            ),
        },
        {
            key: 'id',
            header: '',
            width: 48,
            render: (_v, row) => (
                <button
                    onClick={() => handleDelete(row.id)}
                    disabled={deletingId === row.id}
                    className="rounded p-1 text-muted-foreground/70 hover:bg-red-50 hover:text-red-600 disabled:opacity-40 transition-colors"
                    aria-label="Delete mapping"
                >
                    <Trash2 className="h-3.5 w-3.5" />
                </button>
            ),
        },
    ];

    const GLOBAL_COLUMNS: Column<MappingRow>[] = [
        { key: 'utm_source_pattern', header: 'UTM source', sortable: true },
        {
            key: 'utm_medium_pattern',
            header: 'UTM medium',
            render: (v) => <span className="text-muted-foreground">{v ? String(v) : <em className="text-muted-foreground/70">any</em>}</span>,
        },
        { key: 'channel_name', header: 'Channel name', sortable: true },
        {
            key: 'channel_type',
            header: 'Type',
            render: (v) => (
                <span className="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground capitalize">
                    {String(v).replace(/_/g, ' ')}
                </span>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Channel Mappings" />

            <AddMappingModal
                open={modalOpen}
                processing={processing}
                prefill={prefill}
                onClose={() => { setModalOpen(false); setPrefill(undefined); }}
                onSubmit={handleAdd}
            />

            <div className="space-y-6">
                <PageHeader
                    title="Channel Mappings"
                    subtitle="Map UTM source/medium pairs to channels. Workspace overrides take priority over global defaults."
                />

                <SubNavTabs tabs={tabs} className="mb-6" />

                <div className="space-y-6">
                    {/* Recomputing banner — shown while RecomputeAttributionJob is running */}
                    {is_recomputing && (
                        <div className="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <span className="h-2 w-2 rounded-full bg-amber-500 animate-pulse shrink-0" />
                            <span>
                                <strong>Recomputing attribution…</strong> — Historical orders are being reclassified in the background.
                                Data will update once complete.
                            </span>
                        </div>
                    )}

                    {/* Test mapping widget */}
                    <TestMappingWidget
                        workspaceMappings={workspace_mappings}
                        globalMappings={global_mappings}
                    />

                    {/* Hint: tag generator uses these source values */}
                    <div className="rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground">
                        The <strong>Tag Generator</strong> suggests source values that match these mappings. Keep them in sync so attribution resolves correctly.
                    </div>

                    {/* Unrecognized pairs */}
                    {unrecognized.length > 0 && (
                        <div id="unmatched"><SectionCard title="Unrecognized pairs (last 90 days)">
                            <p className="mb-3 text-sm text-muted-foreground">
                                These source/medium combinations have not been mapped. Click a row to add a mapping.
                            </p>
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 border-b border-border">
                                    <tr className="text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                        <th className="px-3 py-2">Source</th>
                                        <th className="px-3 py-2">Medium</th>
                                        <th className="px-3 py-2 text-right">Orders</th>
                                        <th className="px-3 py-2 text-right">Revenue</th>
                                        <th className="px-3 py-2" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {unrecognized.map((r) => (
                                        <tr key={`${r.source}-${r.medium}`} className="hover:bg-muted/50">
                                            <td className="py-1.5 pr-4 font-mono text-sm text-foreground">{r.source}</td>
                                            <td className="py-1.5 pr-4 font-mono text-sm text-muted-foreground">{r.medium ?? '—'}</td>
                                            <td className="py-1.5 pr-4 text-right tabular-nums text-foreground">{r.order_count}</td>
                                            <td className="py-1.5 pr-4 text-right tabular-nums text-foreground">
                                                {r.revenue.toLocaleString(undefined, { maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-1.5 text-right">
                                                <button
                                                    onClick={() => {
                                                        setPrefill({ source: r.source, medium: r.medium });
                                                        setModalOpen(true);
                                                    }}
                                                    className="text-xs text-primary hover:underline"
                                                >
                                                    Map
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </SectionCard></div>
                    )}

                    {/* Workspace overrides */}
                    <div className="rounded-xl border border-border bg-card overflow-hidden">
                        <div className="flex items-center justify-between border-b border-border px-5 py-4">
                            <div>
                                <p className="text-sm font-semibold text-foreground">Workspace mappings</p>
                                <p className="text-sm text-muted-foreground">Custom overrides for this workspace — highest priority.</p>
                            </div>
                            <button
                                onClick={() => { setPrefill(undefined); setModalOpen(true); }}
                                className="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-2 text-sm font-medium text-white hover:bg-primary/90 transition-colors"
                            >
                                <Plus className="h-4 w-4" />
                                Add mapping
                            </button>
                        </div>
                        {workspace_mappings.length === 0 ? (
                            <EmptyState
                                title="No custom mappings yet"
                                description="Add a mapping to override or extend the global defaults."
                                action={{ label: 'Add mapping', onClick: () => setModalOpen(true) }}
                            />
                        ) : (
                            <DataTable
                                columns={WORKSPACE_COLUMNS}
                                data={workspace_mappings}
                                emptyMessage="No custom mappings"
                                defaultSort={{ key: 'utm_source_pattern', dir: 'asc' }}
                            />
                        )}
                    </div>

                    {/* Global defaults */}
                    <div className="rounded-xl border border-border bg-card overflow-hidden">
                        <div className="flex items-center justify-between border-b border-border px-5 py-4">
                            <div>
                                <p className="text-sm font-semibold text-foreground">Global defaults</p>
                                <p className="text-sm text-muted-foreground">Seeded defaults shared across all workspaces. Read-only — override per workspace above.</p>
                            </div>
                            <button
                                onClick={handleImportDefaults}
                                className="inline-flex items-center gap-1.5 rounded-md border border-input bg-card px-3 py-2 text-sm font-medium text-foreground hover:bg-muted/50 transition-colors"
                            >
                                <RotateCcw className="h-4 w-4" />
                                Re-seed defaults
                            </button>
                        </div>
                        <DataTable
                            columns={GLOBAL_COLUMNS}
                            data={global_mappings}
                            emptyMessage="No global defaults loaded"
                            defaultSort={{ key: 'utm_source_pattern', dir: 'asc' }}
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
