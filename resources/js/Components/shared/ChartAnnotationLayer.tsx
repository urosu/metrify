/**
 * ChartAnnotationLayer — cross-cutting overlay for time-series charts.
 *
 * Renders workspace-scoped annotations as dashed vertical markers with flag labels.
 * Right-click any chart point → "Add annotation here" (via ContextMenu).
 *
 * Usage within a Recharts chart:
 *   <ChartAnnotationLayer annotations={annotations} />
 *
 * The component renders Recharts ReferenceLine elements, one per annotation.
 * Must be placed as a direct child of a Recharts <ComposedChart> or <LineChart>.
 *
 * For the flag label above the line, use the <AnnotationFlag> component as the
 * Recharts `label` prop.
 *
 * @see docs/UX.md §5.6.1 ChartAnnotationLayer
 */
import { ReferenceLine } from 'recharts';
import { cn } from '@/lib/utils';

export interface ChartAnnotation {
    id: string;
    date: string;            /* ISO date string */
    title: string;
    body?: string;
    author?: string;
    createdAt?: string;
    /** System annotations (e.g. integration disconnects) cannot be deleted. */
    isSystem?: boolean;
    /** User can hide per-user without deleting. */
    hiddenByUser?: boolean;
}

interface AnnotationFlagProps {
    viewBox?: { x?: number; y?: number; width?: number; height?: number };
    annotation: ChartAnnotation;
}

/** SVG flag label rendered at the top of the reference line. */
function AnnotationFlag({ viewBox, annotation }: AnnotationFlagProps) {
    if (!viewBox) return null;
    const { x = 0, y = 0 } = viewBox;
    const isSystem = annotation.isSystem;

    return (
        <g>
            <rect
                x={x + 2}
                y={y}
                width={Math.min(annotation.title.length * 6 + 8, 120)}
                height={16}
                rx={3}
                fill={isSystem ? '#fef3c7' : '#f4f4f5'}
                stroke={isSystem ? '#f59e0b' : '#d4d4d8'}
                strokeWidth={0.5}
            />
            <text
                x={x + 6}
                y={y + 11}
                fontSize={9}
                fill={isSystem ? '#92400e' : '#52525b'}
                fontFamily="var(--font-sans)"
                fontWeight={500}
            >
                {annotation.title.length > 16
                    ? annotation.title.slice(0, 14) + '…'
                    : annotation.title}
            </text>
        </g>
    );
}

interface ChartAnnotationLayerProps {
    annotations: ChartAnnotation[];
    /** Recharts uses 'xAxisId' to align reference lines. Default 0. */
    xAxisId?: number | string;
}

/**
 * Renders one Recharts ReferenceLine per annotation.
 * Filtered to exclude hidden-by-user annotations.
 *
 * Must be rendered inside a Recharts composed chart component as a direct child.
 */
export function ChartAnnotationLayer({
    annotations,
    xAxisId = 0,
}: ChartAnnotationLayerProps) {
    const visible = annotations.filter((a) => !a.hiddenByUser);

    return (
        <>
            {visible.map((annotation) => (
                <ReferenceLine
                    key={annotation.id}
                    x={annotation.date}
                    xAxisId={xAxisId}
                    stroke={annotation.isSystem ? '#f59e0b' : '#a1a1aa'}
                    strokeDasharray="4 4"
                    strokeWidth={1.5}
                    label={<AnnotationFlag annotation={annotation} />}
                />
            ))}
        </>
    );
}

/** Standalone annotation tooltip used when hovering a flag in a chart. */
export function AnnotationTooltip({
    annotation,
    className,
}: {
    annotation: ChartAnnotation;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'rounded-lg border border-border bg-white p-3 text-xs w-52',
                className,
            )}
            style={{ boxShadow: 'var(--shadow-overlay)' }}
        >
            <p className="font-semibold text-zinc-900 mb-1">{annotation.title}</p>
            {annotation.body && (
                <p className="text-zinc-500 mb-1">{annotation.body}</p>
            )}
            <div className="flex items-center justify-between text-zinc-400">
                {annotation.author && <span>by {annotation.author}</span>}
                {annotation.createdAt && (
                    <span>{new Date(annotation.createdAt).toLocaleDateString()}</span>
                )}
            </div>
            {annotation.isSystem && (
                <span className="mt-1 inline-flex items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] text-amber-700">
                    System event
                </span>
            )}
        </div>
    );
}
