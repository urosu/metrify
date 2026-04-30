import { Loader2 } from 'lucide-react';

interface DemoBannerProps {
    message?: string;
}

export function DemoBanner({ message = 'Showing sample data while your import runs.' }: DemoBannerProps) {
    return (
        <div className="flex items-center gap-2 border-b border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-700">
            <Loader2 className="h-3 w-3 animate-spin" />
            <span>{message}</span>
        </div>
    );
}
