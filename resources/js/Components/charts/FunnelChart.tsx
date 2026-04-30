import React from 'react';

// Horizontal funnel chart for conversion stage visualization.
// Recharts' built-in <FunnelChart> only renders vertical funnels; we use a
// custom bar-based layout instead for the horizontal scanability preferred
// by Plausible-style dashboards (competitor research: _patterns_catalog.md §funnel).
// @see docs/UX.md#FunnelChart

export interface FunnelStage {
    label: string;
    /** null means data unavailable for this stage — renders as "N/A" */
    value: number | null;
    /** Optional source-specific color key — defaults to chart-1 */
    colorVar?: string;
}

interface FunnelChartProps {
    stages: FunnelStage[];
    /** Format the displayed value (default: number with locale formatting) */
    formatValue?: (value: number | null) => string;
    className?: string;
}

function defaultFormat(value: number | null): string {
    if (value == null) return 'N/A';
    return value.toLocaleString();
}

export function FunnelChart({ stages, formatValue = defaultFormat, className }: FunnelChartProps) {
    const maxValue = stages.find(s => s.value != null)?.value ?? 1;

    return (
        <div
            role="img"
            aria-label="Conversion funnel"
            className={className}
        >
            {/* sr-only table summary */}
            <span className="sr-only">
                <table>
                    <caption>Conversion funnel stages</caption>
                    <thead>
                        <tr>
                            <th>Stage</th>
                            <th>Value</th>
                            <th>Drop-off from previous</th>
                        </tr>
                    </thead>
                    <tbody>
                        {stages.map((stage, i) => {
                            const prev = stages[i - 1];
                            const dropOff = prev && prev.value != null && stage.value != null && prev.value > 0
                                ? `${(((prev.value - stage.value) / prev.value) * 100).toFixed(1)}%`
                                : '—';
                            return (
                                <tr key={stage.label}>
                                    <td>{stage.label}</td>
                                    <td>{formatValue(stage.value)}</td>
                                    <td>{dropOff}</td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </span>

            {/* Visual funnel */}
            <div className="flex flex-col gap-1.5">
                {stages.map((stage, i) => {
                    const widthPct = maxValue > 0 && stage.value != null ? (stage.value / maxValue) * 100 : 0;
                    const prev = stages[i - 1];
                    const dropOffPct = prev && prev.value != null && stage.value != null && prev.value > 0
                        ? ((prev.value - stage.value) / prev.value) * 100
                        : null;
                    const color = stage.colorVar ?? 'var(--chart-1)';

                    return (
                        <div key={stage.label}>
                            {/* Drop-off annotation — shown between stages */}
                            {dropOffPct !== null && (
                                <div className="mb-1 flex items-center gap-1.5 pl-1">
                                    <span className="text-sm text-muted-foreground" style={{ fontVariantNumeric: 'tabular-nums' }}>
                                        ↓ {dropOffPct.toFixed(1)}% drop-off
                                    </span>
                                </div>
                            )}

                            {/* Bar row */}
                            <div className="flex items-center gap-3">
                                {/* Stage label */}
                                <span className="w-28 shrink-0 truncate text-sm text-muted-foreground">
                                    {stage.label}
                                </span>

                                {/* Horizontal bar */}
                                <div className="relative flex h-7 flex-1 items-center overflow-hidden rounded-sm bg-muted/40">
                                    <div
                                        className="h-full rounded-sm transition-all duration-300"
                                        style={{
                                            width: `${widthPct}%`,
                                            backgroundColor: color,
                                            opacity: 0.85,
                                        }}
                                    />
                                </div>

                                {/* Value */}
                                <span
                                    className="w-20 shrink-0 text-right text-sm font-medium tabular-nums"
                                    style={{ fontVariantNumeric: 'tabular-nums' }}
                                >
                                    {formatValue(stage.value)}
                                </span>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
