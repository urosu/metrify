/**
 * Target — workspace goal primitives.
 *
 * Exports three composable sub-components:
 *
 *   TargetProgress  — thin progress bar beneath a MetricCard headline.
 *                     Color thresholds: green ≥ on-pace, amber = at-risk, rose = missed.
 *   TargetLine      — horizontal dashed overlay line on a LineChart at the target value.
 *                     Consumed by LineChart via a Recharts ReferenceLine label prop.
 *   Target          — composite card widget (progress bar + label + status chip + deadline).
 *
 * @see docs/UX.md §5.23 Target
 * @see docs/planning/frontend.md §3 primitive #38
 */
import { cn } from '@/lib/utils';

// ─── TargetProgress ──────────────────────────────────────────────────────────

export interface TargetProgressProps {
  /** Actual current value (e.g. 42000) */
  current: number;
  /** Goal value (e.g. 60000) */
  target: number;
  /** Optional label rendered above the bar */
  label?: string;
  className?: string;
}

/**
 * Progress bar showing actual vs. target with semantic color thresholds.
 *
 * Color thresholds per UX §5.23:
 *   ≥ 80 % → emerald (on pace)
 *   50–79 % → amber (behind trend)
 *   < 50 % → rose (terminal miss)
 */
export function TargetProgress({ current, target, label, className }: TargetProgressProps) {
  const pct = target > 0 ? Math.min(100, (current / target) * 100) : 0;

  const barColor =
    pct >= 80 ? 'bg-emerald-500' : pct >= 50 ? 'bg-amber-500' : 'bg-rose-500';

  return (
    <div className={cn('space-y-1', className)}>
      {label && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>{label}</span>
          <span className="tabular-nums">{Math.round(pct)}%</span>
        </div>
      )}
      <div className="h-1.5 w-full rounded-full bg-muted" role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}>
        <div
          className={cn('h-full rounded-full transition-all', barColor)}
          style={{ width: `${pct}%` }}
        />
      </div>
    </div>
  );
}

// ─── TargetLine ──────────────────────────────────────────────────────────────

export interface TargetLineProps {
  /** Y-axis value at which the horizontal goal line is drawn */
  value: number;
  /** Label rendered at the right edge of the line */
  label?: string;
  /** Stroke color; defaults to zinc-400 (#a1a1aa) */
  color?: string;
}

/**
 * Horizontal dashed goal line for use inside Recharts LineChart as an overlay.
 *
 * Usage inside LineChart:
 *   <ReferenceLine y={target} stroke={color} strokeDasharray="4 4"
 *     label={<TargetLine value={target} label="Goal" />} />
 *
 * The component itself renders only the right-edge label annotation.
 * The actual line is drawn by Recharts ReferenceLine; this component is
 * the label prop rendered at the reference line position.
 *
 * @see docs/UX.md §5.23 Target — TargetLine
 */
export function TargetLine({ label, color = '#a1a1aa' }: TargetLineProps) {
  if (!label) return null;
  return (
    <text
      x={0}
      y={0}
      textAnchor="start"
      fill={color}
      fontSize={11}
      dy={-4}
    >
      {label}
    </text>
  );
}

// ─── Target (composite) ──────────────────────────────────────────────────────

export interface TargetProps {
  label: string;
  current: number;
  target: number;
  unit?: string;
  deadline?: string;
  status?: 'on_track' | 'at_risk' | 'missed';
  className?: string;
}

const STATUS_CONFIG = {
  on_track: { label: 'On track', classes: 'bg-emerald-100 text-emerald-700' },
  at_risk:  { label: 'At risk',  classes: 'bg-amber-100 text-amber-700' },
  missed:   { label: 'Missed',   classes: 'bg-rose-100 text-rose-700' },
} as const;

function formatDeadline(isoDate: string): string {
  return new Date(isoDate).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

export function Target({ label, current, target, unit = '', deadline, status, className }: TargetProps) {
  const statusCfg = status ? STATUS_CONFIG[status] : null;

  return (
    <div className={cn('space-y-1.5', className)}>
      <div className="flex items-center justify-between gap-2">
        <span className="text-sm font-medium text-foreground">{label}</span>
        {statusCfg && (
          <span className={cn('rounded-full px-2 py-0.5 text-xs font-medium', statusCfg.classes)}>
            {statusCfg.label}
          </span>
        )}
      </div>

      <TargetProgress current={current} target={target} />

      <div className="flex items-center justify-between text-sm text-muted-foreground">
        <span className="tabular-nums">
          {current.toLocaleString()}{unit && ` ${unit}`} / {target.toLocaleString()}{unit && ` ${unit}`}
        </span>
        {deadline && <span>By {formatDeadline(deadline)}</span>}
      </div>
    </div>
  );
}
