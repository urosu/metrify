import { Head, Link } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { formatDatetime } from '@/lib/formatters';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

interface AlertItem {
    id: number;
    workspace: { id: number; name: string } | null;
    severity: string;
    title: string;
    context_text: string | null;
    status: 'open' | 'snoozed' | 'closed';
    snoozed_until: string | null;
    created_at: string;
}

interface PaginatedAlerts {
    data: AlertItem[];
    current_page: number;
    last_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface TabCounts {
    open: number;
    snoozed: number;
    closed: number;
}

// ─── Severity badge ───────────────────────────────────────────────────────────

function SeverityBadge({ severity }: { severity: string }) {
    const map: Record<string, string> = {
        critical: 'bg-red-100 text-red-700',
        high:     'bg-orange-100 text-orange-700',
        medium:   'bg-amber-100 text-amber-700',
        low:      'bg-muted text-muted-foreground',
    };
    const cls = map[severity.toLowerCase()] ?? 'bg-muted text-muted-foreground';
    return <span className={`rounded px-1.5 py-0.5 text-xs font-medium ${cls}`}>{severity}</span>;
}

// ─── Status badge ─────────────────────────────────────────────────────────────

function StatusChip({ status }: { status: AlertItem['status'] }) {
    const map: Record<AlertItem['status'], string> = {
        open:    'bg-blue-50 text-blue-700 ring-blue-200',
        snoozed: 'bg-amber-50 text-amber-700 ring-amber-200',
        closed:  'bg-muted text-muted-foreground ring-border',
    };
    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset ${map[status]}`}>
            {status}
        </span>
    );
}

// ─── Tab bar ──────────────────────────────────────────────────────────────────

const TABS = ['snoozed', 'open', 'closed'] as const;
type Tab = typeof TABS[number];
const TAB_LABELS: Record<Tab, string> = {
    snoozed: 'Snoozed',
    open:    'Open',
    closed:  'Closed',
};

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function Alerts({
    alerts,
    tab,
    counts,
}: PageProps<{
    alerts: PaginatedAlerts;
    tab: Tab;
    counts: TabCounts;
}>) {
    return (
        <AppLayout>
            <Head title="Alerts" />

            <div className="mb-6 flex items-start justify-between gap-4">
                <PageHeader
                    title="Alerts"
                    subtitle="Triage inbox items across all workspaces. Status is managed per workspace."
                />
                <Link
                    href="/admin/system-health"
                    className="mt-1 rounded-lg border border-border bg-card px-3 py-1.5 text-sm text-muted-foreground hover:bg-muted/50"
                >
                    System health
                </Link>
            </div>

            {/* Tabs */}
            <div className="mb-4 flex items-center gap-1 border-b border-border">
                {TABS.map((t) => (
                    <Link
                        key={t}
                        href={`/admin/alerts?tab=${t}`}
                        className={`relative -mb-px flex items-center gap-1.5 px-3 py-2 text-sm transition-colors ${
                            tab === t
                                ? 'border-b-2 border-primary font-semibold text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        {TAB_LABELS[t]}
                        <span className={`rounded-full px-1.5 py-0.5 text-xs font-medium ${
                            tab === t ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'
                        }`}>
                            {counts[t]}
                        </span>
                    </Link>
                ))}
            </div>

            {/* Alert list */}
            {alerts.data.length === 0 ? (
                <div className="rounded-xl border border-border bg-card py-16 text-center">
                    <ShieldAlert className="mx-auto mb-3 h-8 w-8 text-muted-foreground" />
                    <p className="text-sm text-muted-foreground">No alerts in this category.</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {alerts.data.map((alert) => (
                        <div key={alert.id} className="rounded-xl border border-border bg-card p-4">
                            <div className="min-w-0">
                                <div className="mb-1.5 flex flex-wrap items-center gap-2">
                                    <SeverityBadge severity={alert.severity} />
                                    <StatusChip status={alert.status} />
                                    <span className="font-medium text-sm text-foreground">{alert.title}</span>
                                </div>
                                <div className="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                                    {alert.workspace && <span>Workspace: {alert.workspace.name}</span>}
                                    <span>{formatDatetime(alert.created_at)}</span>
                                    {alert.snoozed_until && (
                                        <span className="text-amber-600">Snoozed until {formatDatetime(alert.snoozed_until)}</span>
                                    )}
                                </div>
                                {alert.context_text && (
                                    <p className="text-sm text-muted-foreground">{alert.context_text}</p>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Pagination */}
            {alerts.last_page > 1 && (
                <div className="mt-6 flex justify-center gap-1">
                    {alerts.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            className={`rounded px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-primary text-white'
                                    : link.url
                                      ? 'border border-border text-muted-foreground hover:bg-muted/50'
                                      : 'cursor-default border border-border text-muted-foreground/50'
                            }`}
                        >
                            {link.label.replace(/&laquo;\s*/g, '«').replace(/\s*&raquo;/g, '»')}
                        </Link>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
