import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/Components/ui/sheet';

interface DrawerSidePanelProps {
    open: boolean;
    onClose: () => void;
    title?: string;
    subtitle?: React.ReactNode;
    /** Right-aligned slot in the header (e.g. action buttons). */
    headerActions?: React.ReactNode;
    children: React.ReactNode;
    /** Footer pinned to the bottom (e.g. primary CTA bar). */
    footer?: React.ReactNode;
    width?: number;
}

/**
 * Right-side slide-over panel.
 *
 * Layout contract:
 * - Header fixed at the top (title + optional subtitle + actions).
 * - Body scrolls on overflow, has consistent gutter padding (px-5).
 * - Optional footer pinned to the bottom.
 * - `width` is the desktop width in px; capped to viewport on mobile.
 *   We explicitly override the underlying shadcn `sm:max-w-sm` so the prop wins.
 */
export function DrawerSidePanel({
    open,
    onClose,
    title,
    subtitle,
    headerActions,
    children,
    footer,
    width = 520,
}: DrawerSidePanelProps) {
    return (
        <Sheet open={open} onOpenChange={(o) => !o && onClose()}>
            <SheetContent
                side="right"
                style={{ width: `min(${width}px, 100vw)`, maxWidth: '100vw' }}
                /* Override shadcn's hardcoded sm:max-w-sm via !important utilities. */
                className="!max-w-[100vw] !p-0 flex flex-col gap-0"
            >
                {title && (
                    <SheetHeader className="!p-0 border-b border-border">
                        <div className="flex items-start justify-between gap-3 px-5 py-4 pr-12">
                            <div className="min-w-0 flex-1">
                                <SheetTitle className="truncate text-base font-semibold text-foreground">
                                    {title}
                                </SheetTitle>
                                {subtitle && (
                                    <div className="mt-0.5 text-sm text-muted-foreground">
                                        {subtitle}
                                    </div>
                                )}
                            </div>
                            {headerActions && (
                                <div className="flex shrink-0 items-center gap-2">{headerActions}</div>
                            )}
                        </div>
                    </SheetHeader>
                )}
                <div className="flex-1 overflow-y-auto overflow-x-hidden px-5 py-5">
                    {children}
                </div>
                {footer && (
                    <div className="border-t border-border bg-muted/30 px-5 py-3">
                        {footer}
                    </div>
                )}
            </SheetContent>
        </Sheet>
    );
}
