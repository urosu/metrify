import { useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Bell, Mail, MonitorSmartphone, Info } from 'lucide-react';
import type { PageProps } from '@/types';

// ── Types ─────────────────────────────────────────────────────────────────────

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

// ── Constants ─────────────────────────────────────────────────────────────────

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
    critical: 'bg-red-100 text-red-700',
    high:     'bg-orange-100 text-orange-700',
    medium:   'bg-amber-100 text-amber-700',
    low:      'bg-zinc-100 text-zinc-600',
};

const DELIVERY_MODE_LABELS: Record<DeliveryMode, string> = {
    immediate:    'Immediately',
    daily_digest: 'Daily digest',
    weekly_digest: 'Weekly digest',
};

// ── Helpers ───────────────────────────────────────────────────────────────────

function findPref(preferences: Preference[], severity: Severity, channel: Channel): Preference | undefined {
    return preferences.find((p) => p.severity === severity && p.channel === channel);
}

// ── Toggle ────────────────────────────────────────────────────────────────────

function Toggle({ checked, onChange, disabled }: { checked: boolean; onChange: (v: boolean) => void; disabled?: boolean }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className={[
                'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200',
                'focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1',
                checked ? 'bg-primary' : 'bg-zinc-300',
                disabled ? 'cursor-not-allowed opacity-50' : '',
            ].join(' ')}
        >
            <span
                className={[
                    'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200',
                    checked ? 'translate-x-4' : 'translate-x-0',
                ].join(' ')}
            />
        </button>
    );
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function NotificationsSettings({ preferences, quiet_hours_start, quiet_hours_end }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        preferences:       preferences,
        quiet_hours_start: quiet_hours_start ?? '22:00',
        quiet_hours_end:   quiet_hours_end   ?? '08:00',
    });

    function setPref(severity: Severity, channel: Channel, patch: Partial<Preference>) {
        setData('preferences', data.preferences.map((p) =>
            p.severity === severity && p.channel === channel ? { ...p, ...patch } : p,
        ));
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/settings/notifications');
    }

    return (
        <AppLayout>
            <Head title="Notifications" />

            <PageHeader
                title="Notifications"
                subtitle="Choose how and when you receive alerts for this workspace. Settings apply to you only — each team member manages their own preferences."
            />

            <form onSubmit={handleSubmit} className="mt-6 max-w-2xl space-y-6">

                {/* Delivery matrix */}
                <div className="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                    <div className="border-b border-zinc-200 px-6 py-4">
                        <h3 className="text-base font-semibold text-zinc-900">Alert delivery</h3>
                        <p className="mt-0.5 text-xs text-zinc-500">
                            Configure which alert severities reach you and how.
                        </p>
                    </div>

                    {/* Column headers */}
                    <div className="grid grid-cols-[1fr_auto_auto] gap-x-6 border-b border-zinc-100 bg-zinc-50 px-6 py-2.5 text-xs font-medium text-zinc-500">
                        <span>Severity</span>
                        <span className="flex items-center gap-1.5 w-32 justify-center">
                            <MonitorSmartphone className="h-3.5 w-3.5" />
                            In-app
                        </span>
                        <span className="flex items-center gap-1.5 w-40 justify-center">
                            <Mail className="h-3.5 w-3.5" />
                            Email
                        </span>
                    </div>

                    {SEVERITIES.map((severity, idx) => {
                        const inApp = findPref(data.preferences, severity, 'in_app');
                        const email = findPref(data.preferences, severity, 'email');
                        const isLast = idx === SEVERITIES.length - 1;

                        return (
                            <div
                                key={severity}
                                className={[
                                    'grid grid-cols-[1fr_auto_auto] items-center gap-x-6 px-6 py-4',
                                    !isLast ? 'border-b border-zinc-100' : '',
                                ].join(' ')}
                            >
                                {/* Severity label */}
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${SEVERITY_COLORS[severity]}`}>
                                            {SEVERITY_LABELS[severity]}
                                        </span>
                                    </div>
                                    <p className="mt-0.5 text-xs text-zinc-400 leading-snug">
                                        {SEVERITY_DESCRIPTIONS[severity]}
                                    </p>
                                </div>

                                {/* In-app toggle — always immediate, no delivery_mode needed */}
                                <div className="w-32 flex justify-center">
                                    {inApp && (
                                        <Toggle
                                            checked={inApp.enabled}
                                            onChange={(v) => setPref(severity, 'in_app', { enabled: v })}
                                        />
                                    )}
                                </div>

                                {/* Email toggle + delivery mode */}
                                <div className="w-40 flex flex-col items-center gap-1.5">
                                    {email && (
                                        <>
                                            <Toggle
                                                checked={email.enabled}
                                                onChange={(v) => setPref(severity, 'email', { enabled: v })}
                                            />
                                            {email.enabled && (
                                                <select
                                                    value={email.delivery_mode}
                                                    onChange={(e) => setPref(severity, 'email', { delivery_mode: e.target.value as DeliveryMode })}
                                                    className="w-full rounded border border-zinc-200 bg-white px-2 py-1 text-xs text-zinc-700 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                                >
                                                    <option value="immediate">Immediately</option>
                                                    <option value="daily_digest">Daily digest</option>
                                                    <option value="weekly_digest">Weekly digest</option>
                                                </select>
                                            )}
                                        </>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Quiet hours */}
                <div className="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                    <div className="border-b border-zinc-200 px-6 py-4">
                        <h3 className="text-base font-semibold text-zinc-900">Quiet hours</h3>
                        <p className="mt-0.5 text-xs text-zinc-500">
                            Notifications outside these hours are queued and delivered when the window ends.
                        </p>
                    </div>
                    <div className="px-6 py-5 space-y-4">
                        <div className="flex items-center gap-4">
                            <div>
                                <label className="block text-xs font-medium text-zinc-700 mb-1">Start time</label>
                                <input
                                    type="time"
                                    value={data.quiet_hours_start}
                                    onChange={(e) => setData('quiet_hours_start', e.target.value)}
                                    className="rounded-md border border-zinc-300 px-3 py-1.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                                {errors.quiet_hours_start && (
                                    <p className="mt-1 text-xs text-red-600">{errors.quiet_hours_start}</p>
                                )}
                            </div>
                            <span className="text-sm text-zinc-400 mt-4">to</span>
                            <div>
                                <label className="block text-xs font-medium text-zinc-700 mb-1">End time</label>
                                <input
                                    type="time"
                                    value={data.quiet_hours_end}
                                    onChange={(e) => setData('quiet_hours_end', e.target.value)}
                                    className="rounded-md border border-zinc-300 px-3 py-1.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                                {errors.quiet_hours_end && (
                                    <p className="mt-1 text-xs text-red-600">{errors.quiet_hours_end}</p>
                                )}
                            </div>
                        </div>

                        <div className="flex items-start gap-2 rounded-md bg-amber-50 border border-amber-100 px-4 py-3 text-xs text-amber-700">
                            <Info className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                            <span>
                                <strong>Critical alerts always bypass quiet hours</strong> — site down and payment gateway failure
                                notifications fire immediately regardless of this setting. Times are in your workspace timezone.
                            </span>
                        </div>
                    </div>
                </div>

                {/* Save */}
                <div className="flex items-center gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors"
                    >
                        {processing ? 'Saving…' : 'Save preferences'}
                    </button>
                </div>
            </form>
        </AppLayout>
    );
}
