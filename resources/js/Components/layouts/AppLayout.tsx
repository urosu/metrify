/**
 * AppLayout — primary application shell.
 *
 * Structure:
 *   - Sticky 56px TopBar with four zones:
 *       Far left:     WorkspaceSwitcher (hidden if single workspace)
 *       Center-left:  DateRangePicker / page filter slot (injected per-page)
 *       Center-right: deliberately empty — page-specific filters live in content
 *       Right cluster: SyncHealthIndicator · CommandPaletteTrigger · NotificationsBell · UserMenu
 *   - 240px expanded / 64px collapsed Sidebar (localStorage persisted)
 *   - max-w-1440 fluid content area with 24px padding
 *
 * Removed from TopBar (Wave 3A-2):
 *   - SourceToggle — source filters are in-content per feedback_in_page_filters.md
 *   - AttributionModelSelector / WindowSelector / AccountingModeSelector — pages own these
 *   - BreakdownSelector / ProfitModeToggle — pages own these
 *   - SyncHealth dot — replaced by SyncHealthIndicator in right cluster
 *
 * @see docs/UX.md §3 Global chrome
 * @see docs/planning/frontend.md §2 Layout shells
 * @see docs/competitors/_research_chrome_layout.md §3 TopBar zones
 */
import { Link, router, usePage } from '@inertiajs/react';
import { Toaster } from '@/Components/ui/sonner';
import { toast } from 'sonner';
import { AlertBanner } from '@/Components/shared/AlertBanner';
import { Sidebar } from '@/Components/layouts/Sidebar';
import { SyncHealthIndicator } from '@/Components/chrome/SyncHealthIndicator';
import {
    Bell,
    Menu,
    LogOut,
    User,
    Users,
    Puzzle,
    CreditCard,
    ShieldCheck,
    Building2,
    ScrollText,
    ListOrdered,
    Activity,
    Settings,
    ShieldAlert,
    GitBranch,
    CalendarDays,
    ChevronDown,
    Check,
    Plus,
    Search,
} from 'lucide-react';
import { ReactNode, useEffect, useState } from 'react';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import { PageProps } from '@/types';
import type { Workspace } from '@/types';

// ─── WorkspaceSwitcher (TopBar edition) ────────────────────────────────────────
/**
 * Hidden entirely when user has 1 workspace.
 * Lives in the TopBar far-left zone (Wave 3A-2: moved from sidebar bottom).
 * @see docs/UX.md §3 WorkspaceSwitcher
 */
function WorkspaceSwitcher({
    workspace,
    workspaces,
}: {
    workspace: Workspace | undefined;
    workspaces: Workspace[] | undefined;
}) {
    const [open, setOpen] = useState(false);

    if (!workspace || !workspaces || workspaces.length <= 1) return null;

    return (
        <div className="relative">
            <button
                onClick={() => setOpen((v) => !v)}
                className="flex items-center gap-1.5 rounded-md px-2 py-1 text-sm font-medium text-foreground hover:bg-zinc-100 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                title="Switch workspace"
            >
                <div className="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-zinc-900 text-[10px] font-bold text-white">
                    {workspace.name.charAt(0).toUpperCase()}
                </div>
                <span className="max-w-[120px] truncate">{workspace.name}</span>
                <ChevronDown
                    className={cn('h-3.5 w-3.5 text-zinc-400 transition-transform duration-150', open && 'rotate-180')}
                    aria-hidden="true"
                />
            </button>

            {open && (
                <>
                    <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} />
                    <div
                        className="absolute left-0 top-full z-20 mt-1 w-56 rounded-lg border border-border bg-popover py-1"
                        style={{ boxShadow: 'var(--shadow-raised)' }}
                    >
                        <div className="px-3 py-1.5 text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
                            Workspaces
                        </div>

                        {workspaces.length >= 3 && (
                            <button
                                onClick={() => {
                                    setOpen(false);
                                    router.get('/dashboard?view=portfolio');
                                }}
                                className="flex w-full items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors font-medium"
                            >
                                Portfolio view
                            </button>
                        )}

                        {workspaces.map((w) => (
                            <button
                                key={w.id}
                                onClick={() => {
                                    setOpen(false);
                                    router.post(`/workspaces/${w.id}/switch`);
                                }}
                                className="flex w-full items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors"
                            >
                                <div className="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-zinc-900/10 text-[10px] font-semibold text-zinc-900">
                                    {w.name.charAt(0).toUpperCase()}
                                </div>
                                <span className="flex-1 truncate text-left">{w.name}</span>
                                {w.id === workspace.id && (
                                    <Check className="h-3.5 w-3.5 text-zinc-900" aria-label="Current workspace" />
                                )}
                            </button>
                        ))}

                        <div className="my-1 border-t border-border" />
                        <button
                            onClick={() => {
                                setOpen(false);
                                router.post(route('workspaces.create'));
                            }}
                            className="flex w-full items-center gap-2 px-3 py-2 text-sm text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
                        >
                            <Plus className="h-3.5 w-3.5" aria-hidden="true" />
                            Add store
                        </button>
                    </div>
                </>
            )}
        </div>
    );
}

// ─── CommandPaletteTrigger ─────────────────────────────────────────────────────

function CommandPaletteTrigger({ onClick }: { onClick: () => void }) {
    return (
        <button
            onClick={onClick}
            className="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs text-muted-foreground hover:bg-zinc-100 hover:text-foreground transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            aria-label="Open command palette (Cmd+K)"
            title="Command palette (Cmd+K)"
        >
            <Search className="h-4 w-4" aria-hidden="true" />
            <kbd className="hidden font-mono text-[10px] sm:inline-flex items-center gap-0.5 rounded border border-border bg-zinc-100 px-1.5 py-0.5 text-zinc-500">
                <span className="text-[11px]">⌘</span>K
            </kbd>
        </button>
    );
}

// ─── UserMenu ─────────────────────────────────────────────────────────────────

function UserMenu({
    name,
    email,
    isSuperAdmin,
    workspaceRole,
    workspaceSlug,
}: {
    name: string;
    email: string;
    isSuperAdmin: boolean;
    workspaceRole: 'owner' | 'admin' | 'member' | null | undefined;
    workspaceSlug: string | undefined;
}) {
    const [open, setOpen] = useState(false);
    const w = (path: string) => wurl(workspaceSlug, path);
    const isOwnerOrAdmin = isSuperAdmin || workspaceRole === 'owner' || workspaceRole === 'admin';
    const isOwner        = isSuperAdmin || workspaceRole === 'owner';
    const close = () => setOpen(false);

    return (
        <div className="relative">
            <button
                onClick={() => setOpen((v) => !v)}
                className="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-900/10 text-xs font-semibold text-zinc-900 hover:bg-zinc-900/15 transition-colors"
                title={name}
                aria-label="User menu"
            >
                {name.charAt(0).toUpperCase()}
            </button>

            {open && (
                <>
                    <div className="fixed inset-0 z-10" onClick={close} />
                    <div
                        className="absolute right-0 top-full z-20 mt-1 w-56 rounded-lg border border-border bg-popover py-1"
                        style={{ boxShadow: 'var(--shadow-raised)' }}
                    >
                        <div className="border-b border-border px-3 py-2">
                            <div className="text-sm font-medium text-foreground truncate">{name}</div>
                            <div className="text-xs text-muted-foreground truncate">{email}</div>
                        </div>

                        <Link href={w('/settings/profile')} onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                            <User className="h-4 w-4 text-muted-foreground" /> Profile
                        </Link>
                        <Link href={w('/settings/notifications')} onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                            <Bell className="h-4 w-4 text-muted-foreground" /> Notifications
                        </Link>

                        {isOwnerOrAdmin && (
                            <div className="border-t border-border mt-1 pt-1">
                                <div className="px-3 py-1 text-[10px] font-semibold text-muted-foreground uppercase tracking-wider">Workspace</div>
                                <Link href={w('/settings/workspace')} onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <Settings className="h-4 w-4 text-muted-foreground" /> Settings
                                </Link>
                                <Link href={w('/settings/integrations')} onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <Puzzle className="h-4 w-4 text-muted-foreground" /> Integrations
                                </Link>
                                <Link href={w('/settings/team')} onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <Users className="h-4 w-4 text-muted-foreground" /> Team
                                </Link>
                                <Link href={w('/settings/events')} onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <CalendarDays className="h-4 w-4 text-muted-foreground" /> Events
                                </Link>
                                {isOwner && (
                                    <Link href={w('/settings/billing')} onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                        <CreditCard className="h-4 w-4 text-muted-foreground" /> Billing
                                    </Link>
                                )}
                            </div>
                        )}

                        {isSuperAdmin && (
                            <div className="border-t border-border mt-1 pt-1">
                                <div className="px-3 py-1 text-[10px] font-semibold text-muted-foreground uppercase tracking-wider">Admin</div>
                                <Link href="/admin/overview" onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <ShieldCheck className="h-4 w-4 text-muted-foreground" /> Overview
                                </Link>
                                <Link href="/admin/workspaces" onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <Building2 className="h-4 w-4 text-muted-foreground" /> Workspaces
                                </Link>
                                <Link href="/admin/users" onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <Users className="h-4 w-4 text-muted-foreground" /> Users
                                </Link>
                                <Link href="/admin/logs" onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <ScrollText className="h-4 w-4 text-muted-foreground" /> Logs
                                </Link>
                                <Link href="/admin/queue" onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <ListOrdered className="h-4 w-4 text-muted-foreground" /> Queue
                                </Link>
                                <Link href="/admin/system-health" onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <Activity className="h-4 w-4 text-muted-foreground" /> System Health
                                </Link>
                                <Link href="/admin/silent-alerts" onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <ShieldAlert className="h-4 w-4 text-muted-foreground" /> Silent Alerts
                                </Link>
                                <Link href="/admin/channel-mappings" onClick={close} className="flex items-center gap-2 px-3 py-2 text-sm text-popover-foreground hover:bg-accent transition-colors">
                                    <GitBranch className="h-4 w-4 text-muted-foreground" /> Channels
                                </Link>
                                {/* Dev routes (/admin/dev/snippets, /admin/dev/debug) are non-production only — omitted from menu to avoid 404 in production */}
                            </div>
                        )}

                        <div className="border-t border-border mt-1 pt-1">
                            <Link href="/logout" method="post" as="button" onClick={close} className="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 transition-colors">
                                <LogOut className="h-4 w-4" /> Log out
                            </Link>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}

// ─── NotificationsBell ────────────────────────────────────────────────────────

function NotificationsBell({ count, workspaceSlug }: { count: number; workspaceSlug: string | undefined }) {
    // /inbox does not exist — notifications preferences live at /{workspace}/settings/notifications
    const href = wurl(workspaceSlug, '/settings/notifications');
    return (
        <Link
            href={href}
            className="relative flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-zinc-100 hover:text-foreground transition-colors"
            aria-label="Notification preferences"
            title="Notification preferences"
        >
            <Bell className="h-4 w-4" />
            {count > 0 && (
                <span className="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-[10px] font-bold text-white">
                    {count > 9 ? '9+' : count}
                </span>
            )}
        </Link>
    );
}

// ─── AppLayout ────────────────────────────────────────────────────────────────

interface AppLayoutProps {
    children: ReactNode;
    /**
     * Slot for the TopBar center-left filter stack (DateRangePicker is the primary
     * global filter that lives here; everything else moves in-content).
     */
    topBarFilters?: ReactNode;
    /** @deprecated Use topBarFilters instead. */
    dateRangePicker?: ReactNode;
    /**
     * @deprecated SourceToggle removed from TopBar per feedback_in_page_filters.md.
     * Prop accepted but ignored for backwards compatibility.
     */
    availableSources?: string[];
    /**
     * @deprecated Extra right-side TopBar content no longer supported.
     * Page-specific controls belong in page content, not the TopBar.
     * Prop accepted but ignored for backwards compatibility.
     */
    topBarRight?: ReactNode;
}

const SIDEBAR_COLLAPSED_KEY = 'nexstage_sidebar_collapsed';

export default function AppLayout({
    children,
    topBarFilters,
    dateRangePicker,
}: AppLayoutProps) {
    const {
        auth,
        workspace,
        workspaces,
        unread_alerts_count,
        workspace_role,
        impersonating,
        impersonated_user_name,
        flash,
    } = usePage<PageProps>().props;

    const isSuperAdmin = auth.user.is_super_admin;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [cmdOpen, setCmdOpen] = useState(false);

    // Persist sidebar collapsed state in localStorage
    const [sidebarCollapsed, setSidebarCollapsed] = useState(() => {
        if (typeof window === 'undefined') return false;
        return window.localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1';
    });

    const slug = workspace?.slug;
    const w = (path: string) => wurl(slug, path);

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error)   toast.error(flash.error);
    }, [flash?.success, flash?.error]);

    // Cmd+K global shortcut
    useEffect(() => {
        function onKeyDown(e: KeyboardEvent) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setCmdOpen((v) => !v);
            }
        }
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    const toggleSidebarCollapsed = () => {
        setSidebarCollapsed((v) => {
            const next = !v;
            window.localStorage.setItem(SIDEBAR_COLLAPSED_KEY, next ? '1' : '0');
            return next;
        });
    };

    // Payment method / trial warning banner
    const paymentBanner = (() => {
        if (!workspace) return null;
        const hasPaymentMethod = !!workspace.pm_type;
        if (hasPaymentMethod) return null;
        const now = Date.now();
        const trialEnd = workspace.trial_ends_at ? new Date(workspace.trial_ends_at).getTime() : null;
        const daysLeft = trialEnd ? Math.ceil((trialEnd - now) / 86_400_000) : null;
        const trialActive = daysLeft !== null && daysLeft > 0;
        const hasPlan = !!workspace.billing_plan;

        if (hasPlan) {
            return {
                message: 'No payment method on file. Your subscription may be cancelled. Please add one in billing settings.',
                severity: 'warning' as const,
                action: { label: 'Go to billing', href: w('/settings/billing') },
            };
        }
        if (trialActive && daysLeft !== null && daysLeft <= 7) {
            return {
                message: `Your trial ends in ${daysLeft} day${daysLeft === 1 ? '' : 's'}. Add a payment method to keep access.`,
                severity: daysLeft <= 3 ? 'critical' as const : 'warning' as const,
                action: { label: 'Add payment method', href: w('/settings/billing') },
            };
        }
        return null;
    })();

    // Legacy dateRangePicker slot merges into topBarFilters
    const filterContent = topBarFilters ?? dateRangePicker;

    return (
        <div className="flex h-screen bg-zinc-50 overflow-hidden">
            {/* Mobile overlay */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-20 bg-black/30 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Sidebar — 240px expanded / 64px collapsed, persisted */}
            <div
                className={cn(
                    'fixed inset-y-0 left-0 z-30 transition-all duration-200 lg:static lg:translate-x-0 lg:z-auto',
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                    sidebarCollapsed ? 'w-16' : 'w-[240px]',
                )}
            >
                <Sidebar
                    workspace={workspace}
                    workspaces={workspaces}
                    collapsed={sidebarCollapsed}
                    onToggleCollapse={toggleSidebarCollapsed}
                    onClose={() => setSidebarOpen(false)}
                />
            </div>

            {/* Main column */}
            <div className="flex flex-1 min-w-0 flex-col overflow-hidden">
                {/* Impersonation banner */}
                {impersonating && (
                    <div className="flex shrink-0 items-center justify-between bg-rose-600 px-4 py-2 text-sm text-white">
                        <span>Impersonating <strong>{impersonated_user_name}</strong></span>
                        <Link href="/admin/impersonation/stop" method="post" as="button" className="ml-4 underline font-medium hover:no-underline">
                            Stop impersonating
                        </Link>
                    </div>
                )}

                {/* Payment / trial banner */}
                {paymentBanner && (
                    <AlertBanner
                        message={paymentBanner.message}
                        severity={paymentBanner.severity}
                        action={paymentBanner.action}
                    />
                )}

                {/* TopBar — 56px sticky
                 *
                 * Zones (left → right):
                 *   [mobile hamburger] [WorkspaceSwitcher] | [DateRangePicker / filterContent] → flex-1 | [SyncHealthIndicator] [Cmd+K] [Notifications] [UserMenu]
                 */}
                <header className="flex h-14 shrink-0 items-center gap-2 border-b border-border bg-white px-4 sticky top-0 z-10">
                    {/* Mobile hamburger */}
                    <button
                        onClick={() => setSidebarOpen(true)}
                        className="flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-zinc-100 lg:hidden"
                        aria-label="Open navigation"
                    >
                        <Menu className="h-4 w-4" />
                    </button>

                    {/* Far left — WorkspaceSwitcher (hidden if single workspace) */}
                    <WorkspaceSwitcher workspace={workspace} workspaces={workspaces} />

                    {/* Center-left — DateRangePicker / global filter slot */}
                    <div className="flex flex-1 items-center gap-2 min-w-0 overflow-x-auto">
                        {filterContent}
                    </div>

                    {/* Right cluster — SyncHealth · Cmd+K · Notifications · UserMenu */}
                    <div className="flex shrink-0 items-center gap-1">
                        <SyncHealthIndicator />

                        <div className="mx-1 h-4 w-px bg-border" aria-hidden="true" />

                        <CommandPaletteTrigger onClick={() => setCmdOpen(true)} />
                        <NotificationsBell count={unread_alerts_count ?? 0} workspaceSlug={slug} />
                        <UserMenu
                            name={auth.user.name}
                            email={auth.user.email}
                            isSuperAdmin={isSuperAdmin}
                            workspaceRole={workspace_role}
                            workspaceSlug={slug}
                        />
                    </div>
                </header>

                {/* Page content */}
                <main className="flex-1 overflow-y-auto bg-zinc-50">
                    <div className="mx-auto max-w-[1440px] px-6 py-6">
                        {children}
                    </div>
                </main>
            </div>

            <Toaster />
        </div>
    );
}
