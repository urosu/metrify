import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// Preserves current date range when switching between Campaigns / Ad Sets / Ads tabs.
// Why: user should not lose their selected period when drilling into ad sets or ads.
function buildHref(basePath: string): string {
    if (typeof window === 'undefined') return basePath;
    const p = new URLSearchParams(window.location.search);
    const parts: string[] = [];
    if (p.get('from')) parts.push(`from=${p.get('from')}`);
    if (p.get('to'))   parts.push(`to=${p.get('to')}`);
    if (p.get('platform')) parts.push(`platform=${p.get('platform')}`);
    return parts.length ? `${basePath}?${parts.join('&')}` : basePath;
}

const TABS = [
    { label: 'Campaigns', path: '/campaigns' },
    { label: 'Ad Sets',   path: '/campaigns/adsets' },
    { label: 'Ads',       path: '/campaigns/ads' },
] as const;

export function CampaignsTabBar() {
    const { workspace } = usePage<PageProps>().props;
    const w = (path: string) => wurl(workspace?.slug, path);
    const pathname = typeof window !== 'undefined' ? window.location.pathname : '';

    return (
        <div className="mb-6 flex gap-0 border-b border-zinc-200">
            {TABS.map((tab) => {
                const fullPath = w(tab.path);
                const active = pathname === fullPath;
                return (
                    <Link
                        key={tab.path}
                        href={buildHref(fullPath)}
                        className={cn(
                            'px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px',
                            active
                                ? 'border-primary text-primary'
                                : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300',
                        )}
                    >
                        {tab.label}
                    </Link>
                );
            })}
        </div>
    );
}
