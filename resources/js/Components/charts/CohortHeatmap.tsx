/**
 * CohortHeatmap — retention grid (custom SVG, not Recharts).
 *
 * Rows = cohort month, columns = period offset (months since acquisition).
 * Cell color = retention rate, using a zinc-200 → emerald-500 scale.
 *
 * Accessible <table> fallback rendered in a visually-hidden <caption>.
 * Desktop-only per UX §8 (<1280px shows "View on desktop" banner).
 *
 * @see docs/UX.md §5.6 Chart primitives — CohortHeatmap
 * @see docs/PLANNING.md §4 Q3 (custom SVG, Recharts can't do heatmaps)
 */
import { cn } from '@/lib/utils';

export interface CohortRow {
    /** Label for this cohort row, e.g. "Jan 2026" */
    cohortLabel: string;
    /** Retention rates 0–1 for each period. Index 0 = acquisition period (always 1.0). */
    retentionRates: number[];
    /** Total customers in this cohort */
    cohortSize: number;
}

interface CohortHeatmapProps {
    rows: CohortRow[];
    /** Number of periods to display. Default inferred from data. */
    periodCount?: number;
    periodLabel?: string;
    className?: string;
}

/** Map retention rate 0–1 to a background color (zinc → emerald scale). */
function retentionColor(rate: number): string {
    if (rate >= 0.8) return 'oklch(0.596 0.156 149.2)';  /* emerald-600 */
    if (rate >= 0.6) return 'oklch(0.696 0.172 149.2)';  /* emerald-400 */
    if (rate >= 0.4) return 'oklch(0.796 0.144 149.2)';  /* emerald-300 */
    if (rate >= 0.2) return 'oklch(0.895 0.082 149.2)';  /* emerald-100 */
    if (rate >  0)   return 'oklch(0.955 0.04 149.2)';   /* emerald-50  */
    return 'oklch(0.967 0 0)';                            /* zinc-100    */
}

function retentionTextColor(rate: number): string {
    return rate >= 0.5 ? 'oklch(1 0 0)' : 'oklch(0.141 0 0)';
}

const CELL_W = 52;
const CELL_H = 28;
const ROW_LABEL_W = 72;
const COL_LABEL_H = 24;

export function CohortHeatmap({
    rows,
    periodCount,
    periodLabel = 'Month',
    className,
}: CohortHeatmapProps) {
    const periods = periodCount ?? Math.max(...rows.map((r) => r.retentionRates.length));
    const width = ROW_LABEL_W + periods * CELL_W;
    const height = COL_LABEL_H + rows.length * CELL_H;

    return (
        <div className={cn('', className)}>
            {/* Desktop-only notice */}
            <div className="hidden max-[1279px]:block rounded-lg border border-border bg-zinc-50 p-6 text-center text-sm text-zinc-500">
                CohortHeatmap is optimised for desktop (1280px+). Please view on a wider screen.
            </div>

            <div className="max-[1279px]:hidden overflow-x-auto">
                <svg
                    width={width}
                    height={height}
                    role="img"
                    aria-label="Cohort retention heatmap"
                    style={{ fontFamily: 'var(--font-sans)', fontSize: 11 }}
                >
                    {/* Column headers */}
                    {Array.from({ length: periods }).map((_, colIdx) => (
                        <text
                            key={colIdx}
                            x={ROW_LABEL_W + colIdx * CELL_W + CELL_W / 2}
                            y={COL_LABEL_H - 6}
                            textAnchor="middle"
                            fill="oklch(0.552 0 0)"
                            fontSize={10}
                            fontWeight={500}
                        >
                            {periodLabel} {colIdx}
                        </text>
                    ))}

                    {/* Rows */}
                    {rows.map((row, rowIdx) => (
                        <g key={row.cohortLabel}>
                            {/* Row label */}
                            <text
                                x={ROW_LABEL_W - 8}
                                y={COL_LABEL_H + rowIdx * CELL_H + CELL_H / 2 + 4}
                                textAnchor="end"
                                fill="oklch(0.552 0 0)"
                                fontSize={10}
                            >
                                {row.cohortLabel}
                            </text>

                            {/* Cells */}
                            {Array.from({ length: periods }).map((_, colIdx) => {
                                const rate = row.retentionRates[colIdx] ?? 0;
                                const isEmpty = colIdx >= row.retentionRates.length;
                                const cx = ROW_LABEL_W + colIdx * CELL_W;
                                const cy = COL_LABEL_H + rowIdx * CELL_H;

                                return (
                                    <g key={colIdx}>
                                        <rect
                                            x={cx + 1}
                                            y={cy + 1}
                                            width={CELL_W - 2}
                                            height={CELL_H - 2}
                                            rx={2}
                                            fill={isEmpty ? 'oklch(0.985 0 0)' : retentionColor(rate)}
                                        />
                                        {!isEmpty && (
                                            <text
                                                x={cx + CELL_W / 2}
                                                y={cy + CELL_H / 2 + 4}
                                                textAnchor="middle"
                                                fill={retentionTextColor(rate)}
                                                fontSize={10}
                                                fontWeight={colIdx === 0 ? 700 : 400}
                                                style={{ fontVariantNumeric: 'tabular-nums' }}
                                            >
                                                {Math.round(rate * 100)}%
                                            </text>
                                        )}
                                    </g>
                                );
                            })}
                        </g>
                    ))}
                </svg>

                {/* Accessible table fallback */}
                <table className="sr-only" aria-label="Cohort retention data">
                    <thead>
                        <tr>
                            <th scope="col">Cohort</th>
                            {Array.from({ length: periods }).map((_, i) => (
                                <th key={i} scope="col">{periodLabel} {i}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.cohortLabel}>
                                <th scope="row">{row.cohortLabel}</th>
                                {Array.from({ length: periods }).map((_, i) => (
                                    <td key={i}>
                                        {row.retentionRates[i] !== undefined
                                            ? `${Math.round(row.retentionRates[i] * 100)}%`
                                            : '—'}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
