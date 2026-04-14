import { useState } from 'react';
import { Loader2, AlertTriangle, Info } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

interface Props {
    open: boolean;
    onClose: () => void;
    /** Called with the selected ISO date, or null when "All available data" is chosen */
    onConfirm: (fromDate: string | null) => void;
    /** The integration's original import start date — default for the specific-date picker */
    defaultDate: string;
    name: string;
    processing: boolean;
    /** Optional platform-specific notice shown in "All available data" mode */
    notice?: string;
}

/**
 * Dialog for re-importing integration data.
 *
 * Two modes:
 *  - Specific date: user picks a start date; posts that date to the backend.
 *  - All available data: no date required; posts null and the job resolves
 *    the earliest possible date internally per platform.
 */
export function ReimportDialog({ open, onClose, onConfirm, defaultDate, name, processing, notice }: Props) {
    const [mode, setMode]       = useState<'specific' | 'all'>('specific');
    const [fromDate, setFromDate] = useState(defaultDate);

    const handleOpenChange = (isOpen: boolean) => {
        if (!isOpen) {
            onClose();
        } else {
            setMode('specific');
            setFromDate(defaultDate);
        }
    };

    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const maxDate = yesterday.toISOString().split('T')[0];

    const canSubmit = !processing && (mode === 'all' || !!fromDate);

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-sm" showCloseButton={!processing}>
                <DialogHeader>
                    <DialogTitle>Re-import data</DialogTitle>
                    <DialogDescription>
                        Re-import data for <strong className="text-foreground">{name}</strong>.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-3">
                    {/* Mode toggle */}
                    <div className="flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5 gap-0.5">
                        <button
                            type="button"
                            onClick={() => setMode('specific')}
                            disabled={processing}
                            className={`flex-1 rounded-md px-3 py-1.5 text-xs font-medium transition-colors ${
                                mode === 'specific'
                                    ? 'bg-white text-zinc-900 shadow-sm'
                                    : 'text-zinc-500 hover:text-zinc-700'
                            }`}
                        >
                            Specific date
                        </button>
                        <button
                            type="button"
                            onClick={() => setMode('all')}
                            disabled={processing}
                            className={`flex-1 rounded-md px-3 py-1.5 text-xs font-medium transition-colors ${
                                mode === 'all'
                                    ? 'bg-white text-zinc-900 shadow-sm'
                                    : 'text-zinc-500 hover:text-zinc-700'
                            }`}
                        >
                            All available data
                        </button>
                    </div>

                    {mode === 'specific' && (
                        <div className="space-y-1.5">
                            <Label htmlFor="reimport-from">Import from</Label>
                            <Input
                                id="reimport-from"
                                type="date"
                                value={fromDate}
                                max={maxDate}
                                onChange={(e) => setFromDate(e.target.value)}
                                disabled={processing}
                            />
                            <p className="text-xs text-muted-foreground">
                                All data from this date to today will be re-imported.
                            </p>
                        </div>
                    )}

                    {mode === 'all' && notice && (
                        <div className="flex items-start gap-2 rounded-lg bg-blue-50 px-3 py-2.5 text-xs text-blue-800 ring-1 ring-blue-200">
                            <Info className="mt-px h-3.5 w-3.5 shrink-0 text-blue-500" />
                            <span>{notice}</span>
                        </div>
                    )}

                    <div className="flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2.5 text-xs text-amber-800 ring-1 ring-amber-200">
                        <AlertTriangle className="mt-px h-3.5 w-3.5 shrink-0 text-amber-500" />
                        <span>
                            {mode === 'all'
                                ? 'All existing data for this integration will be overwritten.'
                                : 'Existing data in the selected range will be overwritten. Historical data before this date is not affected.'}
                        </span>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={processing}>
                        Cancel
                    </Button>
                    <Button
                        onClick={() => onConfirm(mode === 'all' ? null : fromDate)}
                        disabled={!canSubmit}
                    >
                        {processing && <Loader2 className="animate-spin" />}
                        {processing ? 'Queuing…' : 'Re-import'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
