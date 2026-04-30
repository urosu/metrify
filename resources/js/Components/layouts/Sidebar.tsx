/**
 * Sidebar — 240px expanded / 64px collapsed. Persisted via AppLayout localStorage.
 *
 * Nav order per UX §2 / Wave 3A-2 spec:
 *   TOP (work pages):
 *     Dashboard · Orders · Ads · Attribution · SEO · Performance · Products ·
 *     Profit · Customers · Inventory
 *   — divider —
 *   BOTTOM (utility group):
 *     Tools (collapsible: Tag Generator, Channel Mappings, Naming Convention, Holidays/Events)
 *     Integrations
 *     Settings
 *
 * Sync health removed from sidebar bottom — moved to TopBar (SyncHealthIndicator).
 * WorkspaceSwitcher removed from sidebar — moved to TopBar.
 *
 * Active state: left border accent using var(--color-primary) + bold label.
 * Hover: 150ms color-only transition.
 * Collapsed: icons only, tooltip on hover.
 *
 * @see docs/UX.md §3 Sidebar
 * @see docs/UX.md §2 Information architecture
 * @see docs/competitors/_research_chrome_layout.md §4 Settings position
 */
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import {
    LayoutDashboard,
    ShoppingBag,
    BarChart2,
    GitBranch,
    Search,
    Package,
    TrendingUp,
    Users,
    Puzzle,
    Settings,
    ChevronDown,
    X,
    PanelLeftClose,
    PanelLeftOpen,
    Tags,
    Type,
    CalendarDays,
    Activity,
    Warehouse,
    Wrench,
    Filter,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { Workspace } from '@/types';

// ─── Nav item ─────────────────────────────────────────────────────────────────

interface FlatNavItem {
    label: string;
    href: string;
    icon?: React.ComponentType<{ className?: string }>;
    /** Status indicator dot (e.g. integration disconnected). */
    indicator?: boolean;
    exact?: boolean;
}

function isActiveFlat(href: string, pathname: string, exact = false): boolean {
    if (exact) return pathname === href;
    return pathname === href || pathname.startsWith(href + '/');
}

// ─── SidebarLink ──────────────────────────────────────────────────────────────

function SidebarLink({
    item,
    collapsed,
    onClick,
}: {
    item: FlatNavItem;
    collapsed: boolean;
    onClick?: () => void;
}) {
    const pathname = typeof window !== 'undefined' ? window.location.pathname : '';
    const active = isActiveFlat(item.href, pathname, item.exact);
    const Icon = item.icon;

    return (
        <Link
            href={item.href}
            onClick={onClick}
            title={collapsed ? item.label : undefined}
            className={cn(
                'group relative flex items-center rounded-md transition-colors duration-150',
                'focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:outline-none',
                collapsed ? 'h-9 w-9 justify-center mx-auto' : 'gap-2.5 px-3 py-1.5',
                active
                    ? 'text-foreground'
                    : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900',
            )}
            aria-current={active ? 'page' : undefined}
        >
            {/* Left accent bar for active item (expanded mode only) */}
            {active && !collapsed && (
                <span className="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-0.5 rounded-r bg-[--color-primary]" />
            )}

            {/* Active background tint */}
            {active && (
                <span className="absolute inset-0 rounded-md bg-[--color-primary] opacity-[0.08]" />
            )}

            {Icon && (
                <Icon
                    className={cn(
                        'relative shrink-0',
                        collapsed ? 'h-4.5 w-4.5' : 'h-4 w-4',
                        active
                            ? 'text-[--color-primary]'
                            : 'text-zinc-400 group-hover:text-zinc-600',
                    )}
                    aria-hidden="true"
                />
            )}

            {!collapsed && (
                <span
                    className={cn(
                        'relative flex-1 truncate text-sm',
                        active ? 'font-semibold text-[--color-primary]' : 'font-medium',
                    )}
                >
                    {item.label}
                </span>
            )}
        </Link>
    );
}

// ─── ToolsGroup ───────────────────────────────────────────────────────────────
/**
 * Collapsible Tools group. Default collapsed.
 * When sidebar is collapsed (icon rail), renders a single Tools icon link.
 */
function ToolsGroup({
    collapsed: sidebarCollapsed,
    w,
    onClose,
}: {
    collapsed: boolean;
    w: (path: string) => string;
    onClose?: () => void;
}) {
    const [open, setOpen] = useState(false);

    const toolItems: FlatNavItem[] = [
        { label: 'Tag Generator',     href: w('/tools/tag-generator'),     icon: Tags },
        { label: 'Channel Mappings',  href: w('/tools/channel-mappings'),  icon: GitBranch },
        { label: 'Naming Convention', href: w('/tools/naming-convention'), icon: Type },
        { label: 'Holidays & Events', href: w('/tools/holidays'),          icon: CalendarDays },
    ];

    if (sidebarCollapsed) {
        // In collapsed state: show the Wrench icon as a link to the first tool
        // Tooltip identifies the group
        return (
            <Link
                href={w('/tools/tag-generator')}
                title="Tools"
                onClick={onClose}
                className="flex h-9 w-9 items-center justify-center mx-auto rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:outline-none"
            >
                <Wrench className="h-4 w-4" aria-hidden="true" />
            </Link>
        );
    }

    return (
        <div>
            {/* Group toggle button */}
            <button
                onClick={() => setOpen((v) => !v)}
                className="flex w-full items-center gap-2.5 rounded-md px-3 py-1.5 text-sm font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:outline-none"
                aria-expanded={open}
            >
                <Wrench className="h-4 w-4 shrink-0 text-zinc-400" aria-hidden="true" />
                <span className="flex-1 truncate text-left">Tools</span>
                <ChevronDown
                    className={cn(
                        'h-3.5 w-3.5 text-zinc-400 transition-transform duration-150',
                        open && 'rotate-180',
                    )}
                    aria-hidden="true"
                />
            </button>

            {open && (
                <div className="mt-0.5 space-y-0.5 pl-4">
                    {toolItems.map((item) => (
                        <SidebarLink
                            key={item.href}
                            collapsed={false}
                            item={item}
                            onClick={onClose}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

// ─── Sidebar ──────────────────────────────────────────────────────────────────

interface SidebarProps {
    workspace: Workspace | undefined;
    workspaces?: Workspace[] | undefined;
    collapsed?: boolean;
    onToggleCollapse?: () => void;
    /** Mobile only — invoked when an item is clicked or the close button hit. */
    onClose?: () => void;
}

export function Sidebar({
    workspace,
    collapsed = false,
    onToggleCollapse,
    onClose,
}: SidebarProps) {
    const slug = workspace?.slug;
    const w = (path: string) => wurl(slug, path);

    return (
        <aside
            className={cn(
                'flex h-full flex-col border-r border-sidebar-border bg-white transition-all duration-200',
                collapsed ? 'w-16' : 'w-[240px]',
            )}
        >
            {/* Header — logo + close/collapse controls */}
            <div
                className={cn(
                    'flex h-14 shrink-0 items-center border-b border-sidebar-border',
                    collapsed ? 'justify-center px-0' : 'justify-between px-4',
                )}
            >
                {collapsed ? (
                    <Link
                        href={w('')}
                        className="flex h-8 w-8 items-center justify-center rounded-md text-sm font-bold text-zinc-900 hover:bg-zinc-100 transition-colors focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:outline-none"
                        title="Dashboard"
                    >
                        N
                    </Link>
                ) : (
                    <>
                        <Link
                            href={w('')}
                            className="text-base font-bold text-zinc-900 tracking-tight focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:outline-none rounded"
                        >
                            Nexstage
                        </Link>
                        <div className="flex items-center gap-1">
                            {onClose && (
                                <button
                                    onClick={onClose}
                                    className="flex h-7 w-7 items-center justify-center rounded text-zinc-400 hover:text-zinc-600 focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:outline-none lg:hidden"
                                    aria-label="Close navigation"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            )}
                            {onToggleCollapse && (
                                <button
                                    onClick={onToggleCollapse}
                                    className="hidden lg:flex h-7 w-7 items-center justify-center rounded text-zinc-400 hover:text-zinc-600 focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:outline-none"
                                    aria-label="Collapse sidebar"
                                    title="Collapse sidebar"
                                >
                                    <PanelLeftClose className="h-4 w-4" />
                                </button>
                            )}
                        </div>
                    </>
                )}
            </div>

            {/* Collapsed mode expand button */}
            {collapsed && onToggleCollapse && (
                <button
                    onClick={onToggleCollapse}
                    className="mx-auto my-2 flex h-7 w-7 items-center justify-center rounded text-zinc-400 hover:text-zinc-600 focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:outline-none"
                    aria-label="Expand sidebar"
                    title="Expand sidebar"
                >
                    <PanelLeftOpen className="h-4 w-4" />
                </button>
            )}

            {/* Main nav */}
            <nav
                className={cn('flex flex-col flex-1 overflow-y-auto py-3', collapsed ? 'px-2' : 'px-3')}
                aria-label="Main navigation"
            >
                {/* Top section — primary work pages */}
                <div className="space-y-0.5">
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Dashboard',   href: w(''),              icon: LayoutDashboard, exact: true }}
                        onClick={onClose}
                    />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Orders',      href: w('/orders'),       icon: ShoppingBag }}
                        onClick={onClose}
                    />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Ads',         href: w('/ads'),          icon: BarChart2, indicator: !(workspace?.has_ads ?? false) }}
                        onClick={onClose}
                    />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Attribution', href: w('/attribution'),  icon: GitBranch }}
                        onClick={onClose}
                    />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Flow',        href: w('/flow'),         icon: Filter }}
                        onClick={onClose}
                    />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'SEO',         href: w('/seo'),          icon: Search, indicator: !(workspace?.has_gsc ?? false) }}
                        onClick={onClose}
                    />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Performance', href: w('/performance'),  icon: Activity }}
                        onClick={onClose}
                    />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Products',    href: w('/products'),     icon: Package }}
                        onClick={onClose}
                    />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Profit',      href: w('/profit'),       icon: TrendingUp }}
                        onClick={onClose}
                    />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Customers',   href: w('/customers'),    icon: Users }}
                        onClick={onClose}
                    />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Inventory',   href: w('/inventory'),    icon: Warehouse }}
                        onClick={onClose}
                    />
                </div>

                {/* Divider between work pages and utility group */}
                <div className="my-3 border-t border-zinc-100" />

                {/* Bottom section — utility group */}
                <div className="space-y-0.5">
                    <ToolsGroup collapsed={collapsed} w={w} onClose={onClose} />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Integrations', href: w('/settings/integrations'), icon: Puzzle }}
                        onClick={onClose}
                    />
                    <SidebarLink
                        collapsed={collapsed}
                        item={{ label: 'Settings',     href: w('/settings'),              icon: Settings }}
                        onClick={onClose}
                    />
                </div>

                {/* Spacer — pushes utility group toward bottom when nav is short */}
                <div className="flex-1" />
            </nav>
        </aside>
    );
}
