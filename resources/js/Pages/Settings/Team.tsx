/**
 * Settings / Team — member management, pending invitations, invite form.
 *
 * Patterns used:
 * - Linear: inline role editing via Select inside table row, optimistic remove with undo toast
 * - Vercel: Entity row layout (avatar · name/email · role badge · actions kebab)
 * - Stripe: invite modal with comma-separated bulk email input
 *
 * @see docs/pages/settings.md §team
 * @see docs/UX.md §5.5 DataTable, §5.37 Entity, §6.2 Optimistic writes
 */
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { SettingsLayout } from '@/Components/layouts/SettingsLayout';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';
import { FormEventHandler, useState } from 'react';
import { formatDateOnly } from '@/lib/formatters';
import {
    UserPlus, MoreHorizontal, Trash2, RefreshCw, ShieldCheck,
    Mail, Clock, Calendar,
} from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Member {
    id: number;
    user_id: number;
    name: string;
    email: string;
    role: string;
    joined_at: string;
    last_login: string | null;
    two_fa?: boolean;
}

interface Invitation {
    id: number;
    email: string;
    role: string;
    expires_at: string;
}

interface WorkspaceInfo {
    id: number;
    name: string;
    owner_id: number;
}

// ─── Constants ────────────────────────────────────────────────────────────────

const ROLE_LABELS: Record<string, string> = {
    owner:  'Owner',
    admin:  'Admin',
    member: 'Member',
};

const ROLE_BADGE: Record<string, string> = {
    owner:  'bg-teal-50 text-teal-700 ring-1 ring-teal-200',
    admin:  'bg-zinc-100 text-zinc-700 ring-1 ring-zinc-200',
    member: 'bg-zinc-50 text-zinc-500 ring-1 ring-zinc-100',
};

// Avatar initials from name
function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((n) => n[0].toUpperCase())
        .join('');
}

// Deterministic avatar background based on name (no external dependency)
const AVATAR_COLORS = [
    'bg-indigo-100 text-indigo-700',
    'bg-emerald-100 text-emerald-700',
    'bg-amber-100 text-amber-700',
    'bg-violet-100 text-violet-700',
    'bg-sky-100 text-sky-700',
    'bg-rose-100 text-rose-700',
];
function avatarColor(name: string): string {
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = (hash * 31 + name.charCodeAt(i)) | 0;
    return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
}

function RelativeTime({ date }: { date: string | null }) {
    if (!date) return <span className="text-zinc-400">Never</span>;
    const diff = Date.now() - new Date(date).getTime();
    const days = Math.floor(diff / 86400000);
    if (days === 0) return <span>Today</span>;
    if (days === 1) return <span>Yesterday</span>;
    if (days < 30) return <span>{days}d ago</span>;
    return <span>{formatDateOnly(date)}</span>;
}

// ─── Member row with inline role select ───────────────────────────────────────

function MemberRow({
    member,
    authUserId,
    currentUserRole,
    w,
    onRemove,
}: {
    member: Member;
    authUserId: number;
    currentUserRole: string;
    w: (p: string) => string;
    onRemove: (id: number, name: string) => void;
}) {
    const [roleValue, setRoleValue] = useState(member.role);
    const [menuOpen, setMenuOpen] = useState(false);
    const isSelf = member.user_id === authUserId;
    const isOwner = member.role === 'owner';
    const canChangeRole = (currentUserRole === 'owner' || currentUserRole === 'admin') && !isSelf && !isOwner;

    const handleRoleChange = (newRole: string) => {
        const prev = roleValue;
        setRoleValue(newRole);
        router.patch(w(`/settings/team/members/${member.id}`), { role: newRole }, {
            preserveScroll: true,
            onError: () => setRoleValue(prev),
        });
    };

    return (
        <tr className="hover:bg-zinc-50 transition-colors">
            {/* User */}
            <td className="px-4 py-3">
                <div className="flex items-center gap-3">
                    <div className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold ${avatarColor(member.name)}`}>
                        {initials(member.name)}
                    </div>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-zinc-900">
                            {member.name}
                            {isSelf && <span className="ml-1.5 text-xs text-zinc-400">(you)</span>}
                        </p>
                        <p className="truncate font-mono text-xs text-zinc-500">{member.email}</p>
                    </div>
                </div>
            </td>

            {/* Role */}
            <td className="px-4 py-3">
                {canChangeRole ? (
                    <select
                        value={roleValue}
                        onChange={(e) => handleRoleChange(e.target.value)}
                        className="rounded-md border border-zinc-200 bg-white px-2 py-1 text-sm text-zinc-700 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    >
                        <option value="admin">Admin</option>
                        <option value="member">Member</option>
                    </select>
                ) : (
                    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${ROLE_BADGE[member.role] ?? ROLE_BADGE.member}`}>
                        {ROLE_LABELS[member.role] ?? member.role}
                    </span>
                )}
            </td>

            {/* 2FA */}
            <td className="px-4 py-3 text-sm">
                {member.two_fa ? (
                    <span className="flex items-center gap-1 text-emerald-600">
                        <ShieldCheck className="h-3.5 w-3.5" /> On
                    </span>
                ) : (
                    <span className="text-zinc-400">Off</span>
                )}
            </td>

            {/* Last active */}
            <td className="px-4 py-3 text-sm text-zinc-600 tabular-nums">
                <RelativeTime date={member.last_login} />
            </td>

            {/* Joined */}
            <td className="px-4 py-3 text-sm text-zinc-500 tabular-nums">
                {formatDateOnly(member.joined_at)}
            </td>

            {/* Actions */}
            <td className="px-4 py-3 text-right">
                {!isSelf && !isOwner && (currentUserRole === 'owner' || currentUserRole === 'admin') && (
                    <div className="relative inline-block">
                        <button
                            type="button"
                            onClick={() => setMenuOpen((v) => !v)}
                            className="rounded p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 transition-colors"
                            aria-label="Member actions"
                        >
                            <MoreHorizontal className="h-4 w-4" />
                        </button>
                        {menuOpen && (
                            <>
                                <div
                                    className="fixed inset-0 z-10"
                                    onClick={() => setMenuOpen(false)}
                                />
                                <div className="absolute right-0 top-full z-20 mt-1 w-40 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg">
                                    <button
                                        type="button"
                                        onClick={() => { setMenuOpen(false); onRemove(member.id, member.name); }}
                                        className="flex w-full items-center gap-2 px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 transition-colors"
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />
                                        Remove
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                )}
            </td>
        </tr>
    );
}

// ─── Invite modal ─────────────────────────────────────────────────────────────

function InviteModal({
    onClose,
    workspaceSlug,
}: {
    onClose: () => void;
    workspaceSlug: string;
}) {
    const w = (p: string) => wurl(workspaceSlug, p);
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        role: 'member',
        message: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(w('/settings/team/invite'), {
            onSuccess: () => onClose(),
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-md rounded-xl border border-zinc-200 bg-white shadow-xl">
                <div className="flex items-center justify-between border-b border-zinc-200 px-6 py-4">
                    <h3 className="text-base font-semibold text-zinc-900">Invite team member</h3>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded p-1 text-zinc-400 hover:text-zinc-700 transition-colors"
                    >
                        &times;
                    </button>
                </div>
                <form onSubmit={submit} className="space-y-4 px-6 py-5">
                    <div>
                        <Label htmlFor="invite_email">Email address</Label>
                        <Input
                            id="invite_email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="colleague@company.com"
                            className="mt-1.5"
                            required
                            autoFocus
                        />
                        {errors.email && <p className="mt-1 text-sm text-rose-600">{errors.email}</p>}
                        <p className="mt-1 text-sm text-zinc-500">Comma-separate for bulk invite.</p>
                    </div>

                    <div>
                        <Label htmlFor="invite_role">Role</Label>
                        <select
                            id="invite_role"
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                            className="mt-1.5 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        >
                            <option value="admin">Admin — can edit all settings</option>
                            <option value="member">Member — read + limited actions</option>
                        </select>
                        {errors.role && <p className="mt-1 text-sm text-rose-600">{errors.role}</p>}
                    </div>

                    <div>
                        <Label htmlFor="invite_message">Personal message (optional)</Label>
                        <textarea
                            id="invite_message"
                            value={data.message}
                            onChange={(e) => setData('message', e.target.value)}
                            rows={2}
                            placeholder="Hey! I'm inviting you to our Nexstage workspace…"
                            className="mt-1.5 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 resize-none"
                        />
                    </div>

                    <div className="flex gap-3 pt-1">
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex-1 rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                        >
                            {processing ? 'Sending…' : 'Send invite'}
                        </button>
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-md border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50 transition-colors"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function Team({
    workspaceInfo,
    members,
    invitations,
    userRole,
    authUser,
}: {
    workspaceInfo: WorkspaceInfo;
    members: Member[];
    invitations: Invitation[];
    userRole: string;
    authUser: { id: number };
}) {
    const { props } = usePage<PageProps>();
    const workspaceSlug = props.workspace?.slug ?? '';
    const w = (path: string) => wurl(workspaceSlug, path);

    const [showInvite, setShowInvite] = useState(false);
    const [search, setSearch] = useState('');
    const [roleFilter, setRoleFilter] = useState('any');

    const canManage = userRole === 'owner' || userRole === 'admin';

    const filtered = members.filter((m) => {
        const matchSearch =
            !search ||
            m.name.toLowerCase().includes(search.toLowerCase()) ||
            m.email.toLowerCase().includes(search.toLowerCase());
        const matchRole = roleFilter === 'any' || m.role === roleFilter;
        return matchSearch && matchRole;
    });

    const handleRemove = (id: number, name: string) => {
        if (!confirm(`Remove ${name} from this workspace?`)) return;
        router.delete(w(`/settings/team/members/${id}`), { preserveScroll: true });
    };

    const handleRevokeInvite = (invId: number) => {
        router.delete(w(`/settings/team/invitations/${invId}`), { preserveScroll: true });
    };

    return (
        <SettingsLayout>
            <Head title="Team" />

            {showInvite && (
                <InviteModal onClose={() => setShowInvite(false)} workspaceSlug={workspaceSlug} />
            )}

            {/* Header */}
            <div className="mb-6 flex items-center justify-between gap-4">
                <div>
                    <h2 className="text-xl font-semibold text-zinc-900">Team</h2>
                    <p className="mt-1 text-sm text-zinc-500">
                        {members.length} member{members.length !== 1 ? 's' : ''} in this workspace.
                    </p>
                </div>
                {canManage && (
                    <button
                        type="button"
                        onClick={() => setShowInvite(true)}
                        className="flex items-center gap-2 rounded-md bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
                    >
                        <UserPlus className="h-4 w-4" />
                        Invite member
                    </button>
                )}
            </div>

            {/* Filter toolbar */}
            <div className="mb-4 flex items-center gap-3">
                <div className="relative flex-1 max-w-xs">
                    <Mail className="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400" />
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by name or email…"
                        className="w-full rounded-md border border-zinc-200 bg-white py-2 pl-9 pr-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    />
                </div>
                <select
                    value={roleFilter}
                    onChange={(e) => setRoleFilter(e.target.value)}
                    className="rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                >
                    <option value="any">All roles</option>
                    <option value="owner">Owner</option>
                    <option value="admin">Admin</option>
                    <option value="member">Member</option>
                </select>
            </div>

            {/* Members table */}
            <div className="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                <div className="overflow-x-auto">
                    <table className="min-w-full">
                        <thead className="bg-zinc-50 border-b border-zinc-200">
                            <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                <th className="px-4 py-2.5 text-left">User</th>
                                <th className="px-4 py-2.5 text-left">Role</th>
                                <th className="px-4 py-2.5 text-left">2FA</th>
                                <th className="px-4 py-2.5 text-left">Last active</th>
                                <th className="px-4 py-2.5 text-left">Joined</th>
                                <th className="px-4 py-2.5 text-right"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-zinc-100">
                            {filtered.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-sm text-zinc-400">
                                        No members match your search.
                                    </td>
                                </tr>
                            ) : (
                                filtered.map((m) => (
                                    <MemberRow
                                        key={m.id}
                                        member={m}
                                        authUserId={authUser.id}
                                        currentUserRole={userRole}
                                        w={w}
                                        onRemove={handleRemove}
                                    />
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Pending invitations */}
            {invitations.length > 0 && (
                <div className="mt-8">
                    <h3 className="mb-3 text-sm font-semibold text-zinc-700">Pending invitations</h3>
                    <div className="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                        <table className="min-w-full">
                            <thead className="bg-zinc-50 border-b border-zinc-200">
                                <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                    <th className="px-4 py-2.5 text-left">Email</th>
                                    <th className="px-4 py-2.5 text-left">Role</th>
                                    <th className="px-4 py-2.5 text-left">Expires</th>
                                    <th className="px-4 py-2.5 text-right"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-100">
                                {invitations.map((inv) => (
                                    <tr key={inv.id} className="hover:bg-zinc-50 transition-colors">
                                        <td className="px-4 py-3 font-mono text-sm text-zinc-700">{inv.email}</td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${ROLE_BADGE[inv.role] ?? ROLE_BADGE.member}`}>
                                                {ROLE_LABELS[inv.role] ?? inv.role}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-zinc-500 tabular-nums">
                                            <span className="flex items-center gap-1">
                                                <Clock className="h-3.5 w-3.5 text-zinc-400" />
                                                {formatDateOnly(inv.expires_at)}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {canManage && (
                                                <div className="flex items-center justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            router.post(
                                                                w('/settings/team/invite'),
                                                                { email: inv.email, role: inv.role, message: '' },
                                                                { preserveScroll: true }
                                                            );
                                                        }}
                                                        className="flex items-center gap-1 rounded px-2 py-1 text-xs text-zinc-500 hover:bg-zinc-100 transition-colors"
                                                    >
                                                        <RefreshCw className="h-3 w-3" />
                                                        Resend
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleRevokeInvite(inv.id)}
                                                        className="flex items-center gap-1 rounded px-2 py-1 text-xs text-rose-500 hover:bg-rose-50 transition-colors"
                                                    >
                                                        <Trash2 className="h-3 w-3" />
                                                        Revoke
                                                    </button>
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* SSO placeholder */}
            <div className="mt-8 flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-5 py-4">
                <ShieldCheck className="h-5 w-5 shrink-0 text-zinc-400" />
                <p className="text-sm text-zinc-600">
                    SAML SSO available on Enterprise.{' '}
                    <a href="#" className="font-medium text-teal-600 hover:underline">Talk to us</a>
                </p>
            </div>
        </SettingsLayout>
    );
}
