import { Head, usePage } from '@inertiajs/react';
import { CheckCircle, AlertCircle, XCircle } from 'lucide-react';
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

interface CampaignRow {
    id: number;
    name: string;
    platform: string;
    spend_30d: number;
    country: string | null;
    campaign: string | null;
    raw_target: string | null;
    target_type: string | null;
    target_slug: string | null;
    shape: string | null;
}

interface Props {
    buckets: {
        clean: CampaignRow[];
        partial: CampaignRow[];
        minimal: CampaignRow[];
    };
    coverage: {
        percent: number | null;
        numerator: number;
        denominator: number;
    };
}

// ─── Convention template reference ───────────────────────────────────────────

const CONVENTION_TEMPLATE = 'Country | Campaign | Target | Shape';

const CONVENTION_FIELDS = [
    { field: 'Country',  example: 'DE',         desc: '2-letter ISO country code. Drives country-level ROAS attribution.' },
    { field: 'Campaign', example: 'Prospecting', desc: 'Campaign objective or funnel stage (e.g. Prospecting, Retargeting, LTV).' },
    { field: 'Target',   example: 'ROAS_4',      desc: 'Target metric and value: ROAS_4, CPA_15, BUDGET, SCALE.' },
    { field: 'Shape',    example: 'Video',       desc: 'Creative format: Video, Image, Carousel, Collection, DPA.' },
];

// ─── Table columns ────────────────────────────────────────────────────────────

const CAMPAIGN_COLUMNS: Column<CampaignRow>[] = [
    { key: 'name',       header: 'Campaign name', sortable: true },
    {
        key: 'platform',
        header: 'Platform',
        render: (v) => <span className="capitalize text-muted-foreground text-xs">{String(v)}</span>,
    },
    {
        key: 'spend_30d',
        header: 'Spend (30d)',
        sortable: true,
        render: (v) => (
            <span className="tabular-nums font-medium text-foreground">
                {Number(v) > 0 ? `$${Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 })}` : '—'}
            </span>
        ),
    },
    {
        key: 'country',
        header: 'Country',
        render: (v) => <span className="font-mono text-sm text-muted-foreground">{v ? String(v) : <span className="text-red-400">missing</span>}</span>,
    },
    {
        key: 'campaign',
        header: 'Objective',
        render: (v) => <span className="text-sm text-muted-foreground">{v ? String(v) : <span className="text-red-400">missing</span>}</span>,
    },
    {
        key: 'raw_target',
        header: 'Target',
        render: (v) => <span className="font-mono text-sm text-muted-foreground">{v ? String(v) : <span className="text-amber-500">missing</span>}</span>,
    },
    {
        key: 'shape',
        header: 'Shape',
        render: (v) => <span className="text-sm text-muted-foreground">{v ? String(v) : <span className="text-amber-500">missing</span>}</span>,
    },
];

// ─── Bucket section ───────────────────────────────────────────────────────────

function BucketSection({
    title,
    icon,
    rows,
    emptyMessage,
    accentClass,
}: {
    title: string;
    icon: React.ReactNode;
    rows: CampaignRow[];
    emptyMessage: string;
    accentClass: string;
}) {
    return (
        <div className="rounded-xl border border-border bg-card overflow-hidden">
            <div className={`flex items-center gap-2 border-b border-border px-5 py-4 ${accentClass}`}>
                {icon}
                <p className="text-sm font-semibold text-foreground">{title}</p>
                <span className="ml-auto text-sm text-muted-foreground">{rows.length} campaigns</span>
            </div>
            {rows.length === 0 ? (
                <div className="px-5 py-6 text-sm text-muted-foreground/70">{emptyMessage}</div>
            ) : (
                <DataTable
                    columns={CAMPAIGN_COLUMNS}
                    data={rows}
                    emptyMessage={emptyMessage}
                    defaultSort={{ key: 'spend_30d', dir: 'desc' }}
                />
            )}
        </div>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function NamingConvention({ buckets, coverage }: Props) {
    const { workspace: ws } = usePage<PageProps>().props;
    const wsSlug = ws?.slug;
    const w = (path: string) => wurl(wsSlug, path);

    const tabs = [
        { label: 'Tag Generator',     value: 'tag-generator',    href: w('/tools/tag-generator'),     active: false },
        { label: 'Naming Convention', value: 'naming-convention', href: w('/tools/naming-convention'), active: true  },
        { label: 'Channel Mappings',  value: 'channel-mappings',  href: w('/tools/channel-mappings'),  active: false },
    ];

    const coveragePct = coverage.percent;

    return (
        <AppLayout>
            <Head title="Naming Convention" />

            <div className="space-y-6">
                <PageHeader
                    title="Naming Convention"
                    subtitle="Nexstage parses campaign names using the pipe-delimited template below. Fix names inside Facebook/Google Ads — changes sync on next import."
                />

                <SubNavTabs tabs={tabs} className="mb-6" />

                <div className="space-y-6">
                    {/* Convention reference */}
                    <SectionCard title="Template">
                        <div className="mb-4 rounded-md bg-foreground px-4 py-3 font-mono text-sm text-zinc-100">
                            {CONVENTION_TEMPLATE}
                        </div>
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 border-b border-border">
                                <tr className="text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                    <th className="px-3 py-2">Field</th>
                                    <th className="px-3 py-2">Example</th>
                                    <th className="px-3 py-2">Description</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {CONVENTION_FIELDS.map((f) => (
                                    <tr key={f.field}>
                                        <td className="py-2 pr-4 font-mono text-xs text-primary">{f.field}</td>
                                        <td className="py-2 pr-4 font-mono text-sm text-muted-foreground">{f.example}</td>
                                        <td className="py-2 text-sm text-muted-foreground">{f.desc}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </SectionCard>

                    {/* Coverage badge */}
                    <div className="flex items-center gap-4 rounded-xl border border-border bg-card p-5">
                        <div className="flex h-14 w-14 items-center justify-center rounded-full bg-muted">
                            <span className="text-lg font-bold text-foreground">
                                {coveragePct !== null ? `${coveragePct}%` : 'N/A'}
                            </span>
                        </div>
                        <div>
                            <p className="text-sm font-semibold text-foreground">Convention coverage (30-day active campaigns)</p>
                            <p className="text-sm text-muted-foreground">
                                {coverage.numerator} of {coverage.denominator} campaigns with spend are fully parsed.
                                {coveragePct !== null && coveragePct < 80 && (
                                    <span className="ml-1 text-amber-600">Fix campaign names in Facebook/Google Ads to improve this score.</span>
                                )}
                            </p>
                        </div>
                        <div className="ml-auto">
                            <div className="h-2 w-40 overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-primary/70 transition-all"
                                    style={{ width: `${coveragePct ?? 0}%` }}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Clean bucket */}
                    <BucketSection
                        title="Fully parsed (clean)"
                        icon={<CheckCircle className="h-4 w-4 text-primary" />}
                        rows={buckets.clean}
                        emptyMessage="No fully-parsed campaigns yet. Rename campaigns using the template above."
                        accentClass=""
                    />

                    {/* Partial bucket */}
                    <BucketSection
                        title="Partially parsed"
                        icon={<AlertCircle className="h-4 w-4 text-amber-500" />}
                        rows={buckets.partial}
                        emptyMessage="No partially-parsed campaigns."
                        accentClass=""
                    />

                    {/* Minimal bucket */}
                    <BucketSection
                        title="Not parsed (minimal)"
                        icon={<XCircle className="h-4 w-4 text-red-500" />}
                        rows={buckets.minimal}
                        emptyMessage="All campaigns are at least partially parsed."
                        accentClass=""
                    />

                    {buckets.clean.length === 0 && buckets.partial.length === 0 && buckets.minimal.length === 0 && (
                        <EmptyState
                            title="No campaigns found"
                            description="No campaigns with status active, paused, or archived were found for this workspace."
                        />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
