/**
 * PublicSnapshotLayout — minimal read-only chrome for /public/snapshot/{token}.
 *
 * No sidebar, no TopBar filter stack. Just logo + frozen date badge + content.
 * All filters shown as static FilterChipSentence at the top (read-only, no interaction).
 *
 * @see docs/UX.md §5.29 ShareSnapshotButton
 * @see docs/planning/frontend.md §2 Layout shells
 */
import { PropsWithChildren } from 'react';
import { Toaster } from '@/Components/ui/sonner';

interface PublicSnapshotLayoutProps {
    workspaceName?: string;
    /** Frozen date range label, e.g. "Mar 1 – Mar 31, 2026" */
    dateRangeLabel?: string;
    /** ISO expiry date for this snapshot link */
    expiresAt?: string;
    children: React.ReactNode;
}

export default function PublicSnapshotLayout({
    workspaceName,
    dateRangeLabel,
    expiresAt,
    children,
}: PublicSnapshotLayoutProps & PropsWithChildren) {
    const expired = expiresAt ? new Date(expiresAt) < new Date() : false;

    return (
        <div className="min-h-screen bg-zinc-50">
            {/* Minimal header */}
            <header className="border-b border-border bg-white px-6 py-3 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <span className="text-base font-bold text-zinc-900 tracking-tight">Nexstage</span>
                    {workspaceName && (
                        <>
                            <span className="text-zinc-300">/</span>
                            <span className="text-sm font-medium text-zinc-600">{workspaceName}</span>
                        </>
                    )}
                </div>

                <div className="flex items-center gap-3">
                    {dateRangeLabel && (
                        <span className="text-xs font-medium rounded-md border border-border px-2 py-1 text-muted-foreground bg-zinc-50">
                            {dateRangeLabel}
                        </span>
                    )}
                    <span className="text-xs text-muted-foreground">
                        Read-only snapshot
                    </span>
                </div>
            </header>

            {/* Expired notice */}
            {expired && (
                <div className="border-b border-amber-200 bg-amber-50 px-6 py-3 text-sm text-amber-800">
                    This snapshot link has expired. Ask the workspace owner to share a new one.
                </div>
            )}

            {/* Content */}
            <main className="mx-auto max-w-[1440px] px-6 py-6">
                {!expired && children}
            </main>

            {/* Powered-by footer */}
            <footer className="border-t border-border bg-white px-6 py-4 mt-16">
                <p className="text-xs text-muted-foreground text-center">
                    Powered by{' '}
                    <a
                        href="https://nexstage.com"
                        className="font-medium text-zinc-900 hover:underline"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Nexstage
                    </a>
                </p>
            </footer>

            <Toaster />
        </div>
    );
}
