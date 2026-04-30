import { useState } from 'react';
import { Share2, Copy, Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/Components/ui/dialog';

export interface ShareSnapshotButtonProps {
  workspaceSlug: string;
  page: string;
  urlState?: string;
  className?: string;
}

export function ShareSnapshotButton({ workspaceSlug, page, urlState, className }: ShareSnapshotButtonProps) {
  const [open, setOpen] = useState(false);
  const [copied, setCopied] = useState(false);
  const [loading, setLoading] = useState(false);
  const [snapshotUrl, setSnapshotUrl] = useState<string | null>(null);

  async function handleCopyLink() {
    setLoading(true);
    try {
      const res = await fetch(`/${workspaceSlug}/share-snapshots`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ workspace_slug: workspaceSlug, page, url_state: urlState }),
      });
      const data = await res.json() as { url: string };
      const url = data.url;
      setSnapshotUrl(url);
      await navigator.clipboard.writeText(url);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      // Fallback: copy current URL
      await navigator.clipboard.writeText(window.location.href);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } finally {
      setLoading(false);
    }
  }

  return (
    <>
      <button
        onClick={() => setOpen(true)}
        className={cn(
          'inline-flex items-center gap-1.5 rounded-md border border-border bg-white px-2.5 py-1.5 text-xs font-medium text-foreground shadow-sm hover:bg-muted/50 transition-colors',
          className,
        )}
      >
        <Share2 className="h-3.5 w-3.5" />
        Share
      </button>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Share snapshot</DialogTitle>
            <DialogDescription>
              Creates a shareable frozen view of the current page state.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-3">
            {snapshotUrl && (
              <div className="rounded-md border border-border bg-muted px-3 py-2">
                <p className="text-sm text-muted-foreground break-all">{snapshotUrl}</p>
              </div>
            )}

            <button
              onClick={handleCopyLink}
              disabled={loading}
              className={cn(
                'w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-60',
              )}
            >
              {copied ? (
                <><Check className="h-4 w-4" /> Copied!</>
              ) : (
                <><Copy className="h-4 w-4" /> Copy link</>
              )}
            </button>
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
}
