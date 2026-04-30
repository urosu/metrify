/**
 * TabTitleStatus — manages browser tab title and favicon for long-running operations.
 *
 * For historical syncs, report generation, scheduled export rendering:
 *   - Tab title gets a prefix: "▶ Importing (42%) · Nexstage" → "✓ Import complete · Nexstage"
 *   - Reverts to normal on completion.
 *   - Completion triggers a Toast even if user is on a different tab.
 *
 * Usage:
 *   <TabTitleStatus operation={operation} appName="Nexstage" />
 *
 * The component renders nothing visible — it only mutates the document title.
 *
 * @see docs/UX.md §5.8.1 Tab-title status channel
 */
import { useEffect, useRef } from 'react';

interface Operation {
    /** Running = show progress prefix. Completed = show checkmark briefly. */
    status: 'running' | 'completed' | 'failed' | 'idle';
    /** 0–100 progress percentage. Only relevant when status = 'running'. */
    progress?: number;
    /** Short label: "Importing", "Generating report", etc. */
    label?: string;
}

interface TabTitleStatusProps {
    operation: Operation;
    /** Base app name shown as suffix, e.g. "Nexstage". */
    appName?: string;
    /** Duration in ms to show the completion prefix before reverting. Default 4000. */
    completionDwellMs?: number;
}

export function TabTitleStatus({
    operation,
    appName = 'Nexstage',
    completionDwellMs = 4000,
}: TabTitleStatusProps) {
    const originalTitle = useRef(document.title);
    const completionTimer = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

    useEffect(() => {
        if (operation.status === 'idle') {
            document.title = originalTitle.current;
            return;
        }

        if (operation.status === 'running') {
            const pct = operation.progress !== undefined
                ? ` (${Math.round(operation.progress)}%)`
                : '';
            const label = operation.label ?? 'Processing';
            document.title = `▶ ${label}${pct} · ${appName}`;
        }

        if (operation.status === 'completed') {
            const label = operation.label ?? 'Complete';
            document.title = `✓ ${label} complete · ${appName}`;
            completionTimer.current = setTimeout(() => {
                document.title = originalTitle.current;
            }, completionDwellMs);
        }

        if (operation.status === 'failed') {
            const label = operation.label ?? 'Operation';
            document.title = `✗ ${label} failed · ${appName}`;
            completionTimer.current = setTimeout(() => {
                document.title = originalTitle.current;
            }, completionDwellMs);
        }

        return () => {
            if (completionTimer.current) clearTimeout(completionTimer.current);
        };
    }, [operation.status, operation.progress, operation.label, appName, completionDwellMs]);

    // Revert on unmount
    useEffect(() => {
        return () => {
            document.title = originalTitle.current;
            if (completionTimer.current) clearTimeout(completionTimer.current);
        };
    }, []);

    return null;
}
