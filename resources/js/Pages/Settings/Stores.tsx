import { Head, Link, usePage } from '@inertiajs/react';
import { SettingsLayout } from '@/Components/layouts/SettingsLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

interface StoreRow {
    id: number;
    slug: string;
    name: string;
    platform: string;
    currency: string;
    primary_country_code: string | null;
    status: string;
    last_synced_at: string | null;
}

interface Props extends PageProps {
    stores: StoreRow[];
}

// ─── Badges ───────────────────────────────────────────────────────────────────

function StoreStatusBadge({ status }: { status: string }) {
    const map: Record<string, { label: string; classes: string }> = {
        healthy:  { label: 'Healthy',  classes: 'bg-green-50 text-green-700' },
        syncing:  { label: 'Syncing',  classes: 'bg-blue-50 text-blue-700' },
        warning:  { label: 'Warning',  classes: 'bg-amber-50 text-amber-700' },
        failed:   { label: 'Failed',   classes: 'bg-red-50 text-red-700' },
        error:    { label: 'Error',    classes: 'bg-red-50 text-red-700' },
    };
    const config = map[status.toLowerCase()] ?? { label: status, classes: 'bg-muted text-muted-foreground' };
    return (
        <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', config.classes)}>
            {config.label}
        </span>
    );
}

function PlatformBadge({ platform }: { platform: string }) {
    const isShopify = platform.toLowerCase() === 'shopify';
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                isShopify
                    ? 'bg-green-50 text-green-700'
                    : 'bg-purple-50 text-purple-700',
            )}
        >
            {isShopify ? 'Shopify' : 'WooCommerce'}
        </span>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function StoresSettings({ stores }: Props) {
    const { workspace } = usePage<PageProps>().props;
    const w = (path: string) => wurl(workspace?.slug, path);

    return (
        <SettingsLayout>
            <Head title="Stores" />

            <PageHeader
                title="Stores"
                subtitle="Manage your connected stores. Configure costs, VAT, and performance monitoring per store."
            />

            <div className="mt-6 max-w-3xl">
                {stores.length === 0 ? (
                    <div className="overflow-hidden rounded-lg border border-border bg-card px-6 py-12 text-center">
                        <p className="text-sm text-muted-foreground">No stores connected.</p>
                        <Link
                            href={w('/stores/connect')}
                            className="mt-4 inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                        >
                            Connect a store
                        </Link>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-lg border border-border bg-card">
                        <table className="min-w-full text-sm">
                            <thead className="border-b border-border bg-muted/50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Name</th>
                                    <th className="px-4 py-3 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Platform</th>
                                    <th className="px-4 py-3 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Currency</th>
                                    <th className="px-4 py-3 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Country</th>
                                    <th className="px-4 py-3 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Status</th>
                                    <th className="px-4 py-3 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Last synced</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {stores.map((store) => (
                                    <tr key={store.id} className="hover:bg-muted/50 transition-colors">
                                        <td className="px-6 py-3 font-medium">
                                            <Link
                                                href={w(`/settings/stores/${store.slug}`)}
                                                className="text-primary hover:underline"
                                            >
                                                {store.name}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">
                                            <PlatformBadge platform={store.platform} />
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">{store.currency}</td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {store.primary_country_code ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <StoreStatusBadge status={store.status} />
                                        </td>
                                        <td className="px-4 py-3 text-sm text-muted-foreground">
                                            {store.last_synced_at
                                                ? new Date(store.last_synced_at).toLocaleString(undefined, {
                                                      dateStyle: 'medium',
                                                      timeStyle: 'short',
                                                  })
                                                : <span className="text-muted-foreground">—</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </SettingsLayout>
    );
}
