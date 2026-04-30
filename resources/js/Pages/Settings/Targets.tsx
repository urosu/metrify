import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Plus, X, Check } from 'lucide-react';
import { SettingsLayout } from '@/Components/layouts/SettingsLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { formatCurrency } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

type TargetPeriod = 'monthly' | 'quarterly' | 'annual';
type TargetUnit = 'currency' | 'pct' | 'ratio';

interface Target {
    id: number;
    metric: string;
    period: TargetPeriod;
    target_value: number;
    current_value: number | null;
    unit: TargetUnit;
}

interface Props extends PageProps {
    targets: Target[];
    currency: string;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatTargetValue(value: number | null, unit: TargetUnit, currency: string): string {
    if (value === null) return '—';
    if (unit === 'currency') return formatCurrency(value, currency, true);
    if (unit === 'pct') return `${value.toFixed(1)}%`;
    if (unit === 'ratio') return `${value.toFixed(2)}x`;
    return String(value);
}

function progressColor(pct: number): string {
    if (pct >= 100) return 'bg-green-500';
    if (pct >= 75) return 'bg-amber-400';
    if (pct >= 50) return 'bg-orange-400';
    return 'bg-red-400';
}

const PERIOD_LABELS: Record<TargetPeriod, string> = {
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    annual: 'Annual',
};


// ─── Inline editable target value ─────────────────────────────────────────────

function EditableTargetValue({
    targetId,
    value,
    unit,
    currency,
    workspaceSlug,
}: {
    targetId: number;
    value: number;
    unit: TargetUnit;
    currency: string;
    workspaceSlug: string | undefined;
}) {
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState(String(value));
    const [saving, setSaving] = useState(false);

    function save() {
        const num = parseFloat(draft);
        if (isNaN(num)) { setEditing(false); return; }
        setSaving(true);
        router.patch(
            wurl(workspaceSlug, `/settings/targets/${targetId}`),
            { target_value: num },
            {
                preserveScroll: true,
                onFinish: () => { setSaving(false); setEditing(false); },
            },
        );
    }

    if (editing) {
        return (
            <div className="flex items-center gap-1">
                <input
                    autoFocus
                    type="number"
                    step="0.01"
                    value={draft}
                    onChange={(e) => setDraft(e.target.value)}
                    onKeyDown={(e) => { if (e.key === 'Enter') save(); if (e.key === 'Escape') setEditing(false); }}
                    className="w-24 rounded border border-primary px-1.5 py-0.5 text-xs focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                />
                <button type="button" onClick={save} disabled={saving} className="text-primary hover:text-primary/80">
                    {saving ? '…' : <Check className="h-3 w-3" />}
                </button>
                <button type="button" onClick={() => setEditing(false)} className="text-muted-foreground hover:text-muted-foreground">
                    <X className="h-3 w-3" />
                </button>
            </div>
        );
    }

    return (
        <button
            type="button"
            onClick={() => { setDraft(String(value)); setEditing(true); }}
            className="text-sm font-medium text-foreground hover:underline decoration-dashed underline-offset-2 transition-colors"
        >
            {formatTargetValue(value, unit, currency)}
        </button>
    );
}

// ─── Add target modal ─────────────────────────────────────────────────────────

const METRIC_SUGGESTIONS = [
    'Revenue',
    'Profit',
    'ROAS',
    'CAC',
    'Repeat Rate',
    'AOV',
    'MER',
    'Gross Margin',
    'LTV:CAC',
];

const METRIC_DEFAULT_UNIT: Record<string, TargetUnit> = {
    Revenue:        'currency',
    Profit:         'currency',
    CAC:            'currency',
    AOV:            'currency',
    ROAS:           'ratio',
    MER:            'ratio',
    'LTV:CAC':      'ratio',
    'Repeat Rate':  'pct',
    'Gross Margin': 'pct',
};

function AddTargetModal({
    currency,
    onClose,
    onSave,
}: {
    currency: string;
    onClose: () => void;
    onSave: (data: { metric: string; period: TargetPeriod; target_value: number; unit: TargetUnit }) => void;
}) {
    const [metric, setMetric]       = useState('Revenue');
    const [period, setPeriod]       = useState<TargetPeriod>('monthly');
    const [targetValue, setTargetValue] = useState('');
    const [unit, setUnit]           = useState<TargetUnit>('currency');

    function handleSave() {
        const num = parseFloat(targetValue);
        if (!metric || isNaN(num)) return;
        onSave({ metric, period, target_value: num, unit });
        onClose();
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div className="w-full max-w-sm rounded-xl border border-border bg-card p-6 shadow-xl">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-base font-semibold text-foreground">Add Target</h3>
                    <button type="button" onClick={onClose} className="text-muted-foreground hover:text-muted-foreground">
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <div className="space-y-4">
                    {/* Metric */}
                    <div>
                        <label className="block text-sm font-medium text-foreground mb-1">Metric</label>
                        <select
                            value={metric}
                            onChange={(e) => {
                                setMetric(e.target.value);
                                setUnit(METRIC_DEFAULT_UNIT[e.target.value] ?? unit);
                            }}
                            className="w-full rounded-md border border-input px-3 py-1.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            {METRIC_SUGGESTIONS.map((m) => <option key={m} value={m}>{m}</option>)}
                        </select>
                    </div>

                    {/* Period */}
                    <div>
                        <label className="block text-sm font-medium text-foreground mb-1">Period</label>
                        <select
                            value={period}
                            onChange={(e) => setPeriod(e.target.value as TargetPeriod)}
                            className="w-full rounded-md border border-input px-3 py-1.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annual">Annual</option>
                        </select>
                    </div>

                    {/* Unit */}
                    <div>
                        <label className="block text-sm font-medium text-foreground mb-1">Unit</label>
                        <select
                            value={unit}
                            onChange={(e) => setUnit(e.target.value as TargetUnit)}
                            className="w-full rounded-md border border-input px-3 py-1.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option value="currency">Currency ({currency})</option>
                            <option value="pct">Percentage (%)</option>
                            <option value="ratio">Ratio (×)</option>
                        </select>
                    </div>

                    {/* Target value */}
                    <div>
                        <label className="block text-sm font-medium text-foreground mb-1">Target Value</label>
                        <input
                            type="number"
                            step="0.01"
                            value={targetValue}
                            onChange={(e) => setTargetValue(e.target.value)}
                            className="w-full rounded-md border border-input px-3 py-1.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                            placeholder={unit === 'currency' ? '10000' : unit === 'pct' ? '25' : '3.5'}
                        />
                    </div>
                </div>

                <div className="mt-5 flex justify-end gap-3">
                    <button type="button" onClick={onClose} className="text-sm text-muted-foreground hover:text-foreground">Cancel</button>
                    <button
                        type="button"
                        onClick={handleSave}
                        disabled={!metric || !targetValue}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors"
                    >
                        Add Target
                    </button>
                </div>
            </div>
        </div>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function TargetsSettings({ targets, currency }: Props) {
    const { workspace } = usePage<PageProps>().props;
    const w = (path: string) => wurl(workspace?.slug, path);
    const [showAddModal, setShowAddModal] = useState(false);

    function deleteTarget(id: number) {
        if (!confirm('Delete this target?')) return;
        router.delete(w(`/settings/targets/${id}`), { preserveScroll: true });
    }

    function addTarget(data: { metric: string; period: TargetPeriod; target_value: number; unit: TargetUnit }) {
        router.post(w('/settings/targets'), data, { preserveScroll: true });
    }

    return (
        <SettingsLayout>
            <Head title="Targets" />

            <PageHeader
                title="Targets"
                subtitle="Set performance targets for key metrics. Progress is shown on MetricCards across the app."
            />

            <div className="mt-6 max-w-3xl">
                <div className="overflow-hidden rounded-xl border border-border bg-card">
                    <div className="flex items-center justify-between border-b border-border px-6 py-4">
                        <div>
                            <h3 className="text-base font-semibold text-foreground">Performance Targets</h3>
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                {targets.length} target{targets.length !== 1 ? 's' : ''} configured
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setShowAddModal(true)}
                            className="flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                        >
                            <Plus className="h-3.5 w-3.5" /> Add Target
                        </button>
                    </div>

                    {targets.length === 0 ? (
                        <div className="px-6 py-12 text-center">
                            <p className="text-sm text-muted-foreground">No targets set yet.</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Add targets for Revenue, ROAS, CAC, Repeat Rate, or any metric you track.
                                They appear as progress indicators on MetricCards.
                            </p>
                            <button
                                type="button"
                                onClick={() => setShowAddModal(true)}
                                className="mt-4 text-sm text-primary hover:text-primary/70 transition-colors"
                            >
                                Add your first target →
                            </button>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-border text-sm">
                                <thead className="bg-muted/50 border-b border-border">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Metric</th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Period</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold text-muted-foreground uppercase tracking-wide">Target</th>
                                        <th className="px-4 py-3 text-right text-sm font-semibold text-muted-foreground uppercase tracking-wide">Current</th>
                                        <th className="px-4 py-3 text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">Progress</th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border bg-card">
                                    {targets.map((t) => {
                                        const progress = t.current_value != null && t.target_value > 0
                                            ? Math.min(Math.round((t.current_value / t.target_value) * 100), 150)
                                            : null;

                                        return (
                                            <tr key={t.id} className="hover:bg-muted/50">
                                                {/* Metric */}
                                                <td className="px-6 py-3 font-medium text-foreground">{t.metric}</td>

                                                {/* Period */}
                                                <td className="px-4 py-3">
                                                    <span className={cn(
                                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                        t.period === 'monthly'   ? 'bg-blue-50 text-blue-700'  :
                                                        t.period === 'quarterly' ? 'bg-violet-50 text-violet-700' :
                                                        'bg-muted text-muted-foreground',
                                                    )}>
                                                        {PERIOD_LABELS[t.period]}
                                                    </span>
                                                </td>

                                                {/* Target (inline editable) */}
                                                <td className="px-4 py-3 text-right">
                                                    <EditableTargetValue
                                                        targetId={t.id}
                                                        value={t.target_value}
                                                        unit={t.unit}
                                                        currency={currency}
                                                        workspaceSlug={workspace?.slug}
                                                    />
                                                </td>

                                                {/* Current (read-only) */}
                                                <td className="px-4 py-3 text-right tabular-nums text-muted-foreground">
                                                    {formatTargetValue(t.current_value, t.unit, currency)}
                                                </td>

                                                {/* Progress bar */}
                                                <td className="px-4 py-3 min-w-[120px]">
                                                    {progress !== null ? (
                                                        <div className="flex items-center gap-2">
                                                            <div className="flex-1 overflow-hidden rounded-full bg-muted" style={{ height: 6 }}>
                                                                <div
                                                                    className={cn('h-full rounded-full transition-all', progressColor(progress))}
                                                                    style={{ width: `${Math.min(progress, 100)}%` }}
                                                                />
                                                            </div>
                                                            <span className={cn(
                                                                'text-xs font-medium tabular-nums',
                                                                progress >= 100 ? 'text-green-700' :
                                                                progress >= 75  ? 'text-amber-600' :
                                                                'text-muted-foreground',
                                                            )}>
                                                                {progress}%
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-sm text-muted-foreground">No data</span>
                                                    )}
                                                </td>

                                                {/* Delete */}
                                                <td className="px-4 py-3">
                                                    <button
                                                        type="button"
                                                        onClick={() => deleteTarget(t.id)}
                                                        className="text-sm text-red-500 hover:text-red-700 transition-colors"
                                                    >
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                <p className="mt-3 text-sm text-muted-foreground">
                    Target values are editable inline. Current values are computed from live snapshot data and update daily.
                </p>
            </div>

            {showAddModal && (
                <AddTargetModal
                    currency={currency}
                    onClose={() => setShowAddModal(false)}
                    onSave={addTarget}
                />
            )}
        </SettingsLayout>
    );
}
