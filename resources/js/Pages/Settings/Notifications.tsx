/**
 * Settings / Notifications — email digest, Slack webhook, anomaly alert thresholds,
 * and member × channel notification matrix.
 *
 * Patterns used:
 * - Elevar: configurable threshold per alert rule (real↔store delta, platform over-report, etc.)
 * - Crosscut export/sharing UX: recipients don't need Nexstage account, send-test-before-save
 * - Linear: optimistic saves with subtle saving ring
 *
 * @see docs/pages/settings.md §notifications
 * @see docs/UX.md §5.30 ExportMenu, §6.2 Optimistic writes
 */
import { useForm, Head, usePage } from '@inertiajs/react';
import { SettingsLayout } from '@/Components/layouts/SettingsLayout';
import { SectionCard } from '@/Components/shared/SectionCard';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';
import { FormEventHandler, useState } from 'react';
import { Mail, Bell, Send, AlertTriangle, Check, MessageSquare as Slack } from 'lucide-react';
import { cn } from '@/lib/utils';

// ─── Types ────────────────────────────────────────────────────────────────────

type Channel      = 'email' | 'in_app';
type Severity     = 'critical' | 'high' | 'medium' | 'low';
type DeliveryMode = 'immediate' | 'daily_digest' | 'weekly_digest';

interface Preference {
    channel:       Channel;
    severity:      Severity;
    enabled:       boolean;
    delivery_mode: DeliveryMode;
}

interface FormData {
    preferences:       Preference[];
    quiet_hours_start: string;
    quiet_hours_end:   string;
}

interface Props extends PageProps {
    preferences:       Preference[];
    quiet_hours_start: string | null;
    quiet_hours_end:   string | null;
}

// ─── Constants ────────────────────────────────────────────────────────────────

const SEVERITIES: Severity[] = ['critical', 'high', 'medium', 'low'];

const SEVERITY_LABELS: Record<Severity, string> = {
    critical: 'Critical',
    high:     'High',
    medium:   'Medium',
    low:      'Low',
};

const SEVERITY_DESCRIPTIONS: Record<Severity, string> = {
    critical: 'Site down, cart broken, payment gateway failure',
    high:     'Severe revenue drop, checkout errors, webhook failures',
    medium:   'Moderate anomalies, sync issues, performance regressions',
    low:      'Minor signals, informational updates',
};

const SEVERITY_COLORS: Record<Severity, string> = {
    critical: 'bg-rose-100 text-rose-700',
    high:     'bg-orange-100 text-orange-700',
    medium:   'bg-amber-100 text-amber-700',
    low:      'bg-zinc-100 text-zinc-600',
};

const DELIVERY_MODE_LABELS: Record<DeliveryMode, string> = {
    immediate:    'Immediately',
    daily_digest: 'Daily digest',
    weekly_digest: 'Weekly digest',
};

// Default prefs when no server data
const DEFAULT_PREFS: Preference[] = [
    { channel: 'email',  severity: 'critical', enabled: true,  delivery_mode: 'immediate' },
    { channel: 'email',  severity: 'high',     enabled: true,  delivery_mode: 'daily_digest' },
    { channel: 'email',  severity: 'medium',   enabled: false, delivery_mode: 'daily_digest' },
    { channel: 'email',  severity: 'low',      enabled: false, delivery_mode: 'daily_digest' },
    { channel: 'in_app', severity: 'critical', enabled: true,  delivery_mode: 'immediate' },
    { channel: 'in_app', severity: 'high',     enabled: true,  delivery_mode: 'immediate' },
    { channel: 'in_app', severity: 'medium',   enabled: true,  delivery_mode: 'immediate' },
    { channel: 'in_app', severity: 'low',      enabled: true,  delivery_mode: 'immediate' },
];

// Mock digest settings
const DIGEST_DEFAULTS = {
    frequency: 'weekly' as 'off' | 'daily' | 'weekly' | 'monthly',
    day_of_week: 'Monday',
    time_of_day: '08:00',
    recipients: 'owner@acme.co, cfo@acme.co',
    content: {
        dashboard: true,
        ads: true,
        seo: false,
        attribution: false,
        orders: true,
    },
};

// Anomaly alert defaults (Elevar pattern)
const ANOMALY_DEFAULTS = {
    real_store_delta_pct: 15,
    platform_over_report_pct: 20,
    ad_spend_dod_pct: 40,
    integration_down_hours: 6,
};

export default function Notifications(serverProps: Props) {
    const { props } = usePage<Props>();
    const workspaceSlug = props.workspace?.slug ?? '';
    const w = (path: string) => wurl(workspaceSlug, path);

    const prefs = serverProps.preferences?.length ? serverProps.preferences : DEFAULT_PREFS;

    const { data, setData, post, processing, recentlySuccessful } = useForm<FormData>({
        preferences: prefs,
        quiet_hours_start: serverProps.quiet_hours_start ?? '22:00',
        quiet_hours_end:   serverProps.quiet_hours_end   ?? '08:00',
    });

    // Digest form (client-only for now — mock)
    const [digest, setDigest] = useState(DIGEST_DEFAULTS);
    const [anomaly, setAnomaly] = useState(ANOMALY_DEFAULTS);
    const [slackConnected] = useState(false);
    const [testSent, setTestSent] = useState(false);
    const [digestSaved, setDigestSaved] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(w('/settings/notifications'), {
            onSuccess: () => {},
        });
    };

    const prefFor = (severity: Severity, channel: Channel): Preference | undefined =>
        data.preferences.find((p) => p.severity === severity && p.channel === channel);

    const toggleEnabled = (severity: Severity, channel: Channel) => {
        setData('preferences', data.preferences.map((p) =>
            p.severity === severity && p.channel === channel
                ? { ...p, enabled: !p.enabled }
                : p
        ));
    };

    const setDeliveryMode = (severity: Severity, channel: Channel, mode: DeliveryMode) => {
        setData('preferences', data.preferences.map((p) =>
            p.severity === severity && p.channel === channel
                ? { ...p, delivery_mode: mode }
                : p
        ));
    };

    const handleSendTest = () => {
        setTestSent(true);
        setTimeout(() => setTestSent(false), 4000);
    };

    return (
        <SettingsLayout>
            <Head title="Notifications" />

            <div className="mb-6">
                <h2 className="text-xl font-semibold text-zinc-900">Notifications</h2>
                <p className="mt-1 text-sm text-zinc-500">Email digest, Slack, and anomaly alert settings.</p>
            </div>

            <div className="space-y-6">

                {/* Email digest */}
                <SectionCard
                    title="Email digest"
                    description="Regular reports delivered to any email address — recipients don't need a Nexstage account."
                >
                    <div className="space-y-4">
                        {/* Frequency */}
                        <div>
                            <Label>Frequency</Label>
                            <div className="mt-2 flex flex-wrap gap-3">
                                {(['off', 'daily', 'weekly', 'monthly'] as const).map((f) => (
                                    <label key={f} className="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="radio"
                                            name="digest_frequency"
                                            value={f}
                                            checked={digest.frequency === f}
                                            onChange={() => setDigest({ ...digest, frequency: f })}
                                            className="h-4 w-4 border-zinc-300 text-teal-600 focus:ring-teal-500"
                                        />
                                        <span className="text-sm text-zinc-700 capitalize">{f === 'off' ? 'Off' : f}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {digest.frequency !== 'off' && (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {digest.frequency === 'weekly' && (
                                    <div>
                                        <Label htmlFor="day_of_week">Day of week</Label>
                                        <select
                                            id="day_of_week"
                                            value={digest.day_of_week}
                                            onChange={(e) => setDigest({ ...digest, day_of_week: e.target.value })}
                                            className="mt-1.5 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                        >
                                            {['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'].map((d) => (
                                                <option key={d} value={d}>{d}</option>
                                            ))}
                                        </select>
                                    </div>
                                )}

                                <div>
                                    <Label htmlFor="time_of_day">Time (workspace timezone)</Label>
                                    <Input
                                        id="time_of_day"
                                        type="time"
                                        value={digest.time_of_day}
                                        onChange={(e) => setDigest({ ...digest, time_of_day: e.target.value })}
                                        className="mt-1.5"
                                    />
                                </div>
                            </div>
                        )}

                        {digest.frequency !== 'off' && (
                            <>
                                <div>
                                    <Label htmlFor="recipients">Recipients</Label>
                                    <Input
                                        id="recipients"
                                        type="text"
                                        value={digest.recipients}
                                        onChange={(e) => setDigest({ ...digest, recipients: e.target.value })}
                                        placeholder="email@company.com, another@company.com"
                                        className="mt-1.5 font-mono text-sm"
                                    />
                                    <p className="mt-1 text-sm text-zinc-500">
                                        Comma-separated. Recipients do not need a Nexstage account.
                                    </p>
                                </div>

                                {/* Content checkboxes */}
                                <div>
                                    <Label>Content to include</Label>
                                    <div className="mt-2 space-y-2">
                                        {Object.entries(digest.content).map(([key, checked]) => (
                                            <label key={key} className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={checked}
                                                    onChange={(e) =>
                                                        setDigest({
                                                            ...digest,
                                                            content: { ...digest.content, [key]: e.target.checked },
                                                        })
                                                    }
                                                    className="h-4 w-4 rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                                                />
                                                <span className="text-sm text-zinc-700 capitalize">{key.replace('_', ' ')} summary</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                {/* Send test button (always before save) */}
                                <div className="flex items-center gap-3">
                                    <button
                                        type="button"
                                        onClick={handleSendTest}
                                        className="flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50 transition-colors"
                                    >
                                        {testSent ? (
                                            <>
                                                <Check className="h-4 w-4 text-emerald-600" />
                                                <span className="text-emerald-600">Sent to your inbox</span>
                                            </>
                                        ) : (
                                            <>
                                                <Send className="h-4 w-4" />
                                                Send test now
                                            </>
                                        )}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setDigestSaved(true)}
                                        className="rounded-md bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors"
                                    >
                                        Save digest settings
                                    </button>
                                    {digestSaved && (
                                        <span className="text-sm text-emerald-600">Saved.</span>
                                    )}
                                </div>
                            </>
                        )}
                    </div>
                </SectionCard>

                {/* Slack */}
                <SectionCard
                    title="Slack"
                    description='Connect your Slack workspace for on-demand "Send to Slack" from any data page.'
                >
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className={cn(
                                'flex h-8 w-8 items-center justify-center rounded-md',
                                slackConnected ? 'bg-[#4A154B]' : 'bg-zinc-100',
                            )}>
                                <Slack className={cn('h-4 w-4', slackConnected ? 'text-white' : 'text-zinc-400')} />
                            </div>
                            <div>
                                <p className="text-sm font-medium text-zinc-800">
                                    {slackConnected ? 'Slack connected' : 'Slack not connected'}
                                </p>
                                <p className="text-sm text-zinc-500">
                                    {slackConnected ? '#nexstage-alerts' : 'Connect to enable one-click Slack sharing.'}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            className="rounded-md border border-zinc-200 px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 transition-colors"
                        >
                            {slackConnected ? 'Disconnect' : 'Connect Slack'}
                        </button>
                    </div>
                    {!slackConnected && (
                        <p className="mt-3 text-sm text-zinc-400">
                            Scheduled Slack digests arrive in v2 —{' '}
                            <a href="#" className="text-teal-600 hover:underline">vote on the roadmap</a>.
                        </p>
                    )}
                </SectionCard>

                {/* Anomaly alerts */}
                <SectionCard
                    title="Anomaly alerts"
                    description="Thresholds that trigger alerts when metrics cross unusual bounds."
                >
                    <div className="space-y-4">
                        {[
                            {
                                key: 'real_store_delta_pct' as const,
                                label: 'Attribution gap',
                                description: 'Alert when attributed revenue diverges from store revenue by more than X%',
                                unit: '%',
                                default: 15,
                            },
                            {
                                key: 'platform_over_report_pct' as const,
                                label: 'Platform discrepancy',
                                description: 'Alert when any platform reports purchases more than X% above store orders',
                                unit: '%',
                                default: 20,
                            },
                            {
                                key: 'ad_spend_dod_pct' as const,
                                label: 'Ad spend day-over-day change',
                                description: 'Alert on ad spend swings larger than ±X% vs previous day',
                                unit: '%',
                                default: 40,
                            },
                            {
                                key: 'integration_down_hours' as const,
                                label: 'Integration down for',
                                description: 'Alert if an integration stops syncing for more than X hours',
                                unit: 'h',
                                default: 6,
                            },
                        ].map((rule) => (
                            <div key={rule.key} className="flex items-start gap-4">
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-zinc-800">{rule.label}</p>
                                    <p className="text-sm text-zinc-500">{rule.description}</p>
                                </div>
                                <div className="flex items-center gap-1.5 shrink-0">
                                    <Input
                                        type="number"
                                        min={1}
                                        value={anomaly[rule.key]}
                                        onChange={(e) =>
                                            setAnomaly({ ...anomaly, [rule.key]: Number(e.target.value) })
                                        }
                                        className="w-20 tabular-nums text-center"
                                    />
                                    <span className="text-sm text-zinc-500">{rule.unit}</span>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4">
                        <p className="text-sm font-medium text-zinc-700 mb-2">Delivery channels</p>
                        <div className="flex flex-wrap gap-3">
                            {['Email', 'In-app TriageInbox', 'Slack (when connected)'].map((ch) => (
                                <label key={ch} className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        defaultChecked={ch !== 'Slack (when connected)'}
                                        className="h-4 w-4 rounded border-zinc-300 text-teal-600 focus:ring-teal-500"
                                    />
                                    <span className="text-sm text-zinc-700">{ch}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                </SectionCard>

                {/* Notification matrix — per-severity per-channel */}
                <SectionCard
                    title="Alert matrix"
                    description="Per-severity delivery preferences. Critical alerts always fire immediately and cannot be disabled."
                >
                    <form onSubmit={submit}>
                        <div className="overflow-x-auto">
                            <table className="min-w-full">
                                <thead>
                                    <tr className="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                        <th className="py-2 text-left">Alert level</th>
                                        <th className="px-4 py-2 text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Mail className="h-3.5 w-3.5" />
                                                Email
                                            </div>
                                        </th>
                                        <th className="px-4 py-2 text-center">Email delivery</th>
                                        <th className="px-4 py-2 text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Bell className="h-3.5 w-3.5" />
                                                In-app
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-zinc-100">
                                    {SEVERITIES.map((severity) => {
                                        const emailPref  = prefFor(severity, 'email');
                                        const inAppPref  = prefFor(severity, 'in_app');
                                        const isCritical = severity === 'critical';

                                        return (
                                            <tr key={severity} className="hover:bg-zinc-50 transition-colors">
                                                <td className="py-3">
                                                    <div>
                                                        <span className={cn(
                                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                            SEVERITY_COLORS[severity],
                                                        )}>
                                                            {SEVERITY_LABELS[severity]}
                                                        </span>
                                                        <p className="mt-0.5 text-xs text-zinc-500">
                                                            {SEVERITY_DESCRIPTIONS[severity]}
                                                        </p>
                                                    </div>
                                                </td>

                                                <td className="px-4 py-3 text-center">
                                                    <Switch
                                                        checked={emailPref?.enabled ?? false}
                                                        onCheckedChange={() => !isCritical && toggleEnabled(severity, 'email')}
                                                        disabled={isCritical}
                                                    />
                                                </td>

                                                <td className="px-4 py-3 text-center">
                                                    {emailPref?.enabled ? (
                                                        <select
                                                            value={emailPref.delivery_mode}
                                                            onChange={(e) =>
                                                                setDeliveryMode(severity, 'email', e.target.value as DeliveryMode)
                                                            }
                                                            disabled={isCritical}
                                                            className="rounded-md border border-zinc-200 bg-white px-2 py-1 text-sm text-zinc-700 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
                                                        >
                                                            {(['immediate', 'daily_digest', 'weekly_digest'] as DeliveryMode[]).map((m) => (
                                                                <option key={m} value={m}>{DELIVERY_MODE_LABELS[m]}</option>
                                                            ))}
                                                        </select>
                                                    ) : (
                                                        <span className="text-sm text-zinc-400">—</span>
                                                    )}
                                                </td>

                                                <td className="px-4 py-3 text-center">
                                                    <Switch
                                                        checked={inAppPref?.enabled ?? false}
                                                        onCheckedChange={() => !isCritical && toggleEnabled(severity, 'in_app')}
                                                        disabled={isCritical}
                                                    />
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>

                        {/* Quiet hours */}
                        <div className="mt-5 border-t border-zinc-100 pt-5">
                            <p className="text-sm font-medium text-zinc-700">Quiet hours</p>
                            <p className="mt-0.5 text-sm text-zinc-500">
                                Critical alerts always fire. All others are held until quiet hours end.
                            </p>
                            <div className="mt-3 flex items-center gap-4">
                                <div className="flex items-center gap-2">
                                    <Label htmlFor="quiet_start" className="text-sm">From</Label>
                                    <Input
                                        id="quiet_start"
                                        type="time"
                                        value={data.quiet_hours_start}
                                        onChange={(e) => setData('quiet_hours_start', e.target.value)}
                                        className="w-32"
                                    />
                                </div>
                                <div className="flex items-center gap-2">
                                    <Label htmlFor="quiet_end" className="text-sm">To</Label>
                                    <Input
                                        id="quiet_end"
                                        type="time"
                                        value={data.quiet_hours_end}
                                        onChange={(e) => setData('quiet_hours_end', e.target.value)}
                                        className="w-32"
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="mt-5 flex items-center gap-4">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                            >
                                {processing ? 'Saving…' : 'Save preferences'}
                            </button>
                            {recentlySuccessful && (
                                <span className="text-sm text-emerald-600">Saved.</span>
                            )}
                        </div>
                    </form>
                </SectionCard>
            </div>
        </SettingsLayout>
    );
}
