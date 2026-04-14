import { CheckCircle, XCircle, Clock, RefreshCw, AlertTriangle } from 'lucide-react';
import { cn } from '@/lib/utils';

// ─── Semantic color pairs ──────────────────────────────────────────────────────
// Named constants so color choices are one-source-of-truth across all preset maps.

const C = {
    success: { bg: 'bg-green-50',   text: 'text-green-700'  },
    warning: { bg: 'bg-amber-50',   text: 'text-amber-700'  },
    error:   { bg: 'bg-red-50',     text: 'text-red-700'    },
    info:    { bg: 'bg-blue-50',    text: 'text-blue-700'   },
    neutral: { bg: 'bg-zinc-100',   text: 'text-zinc-500'   },
    muted:   { bg: 'bg-zinc-100',   text: 'text-zinc-400'   },
    primary: { bg: 'bg-primary/15', text: 'text-primary'    },
    violet:  { bg: 'bg-violet-100', text: 'text-violet-700' },
    emerald: { bg: 'bg-emerald-50', text: 'text-emerald-700'},
} as const;

// ─── Preset color maps ─────────────────────────────────────────────────────────

const SYNC_MAP: Record<string, { bg: string; text: string; Icon?: React.ComponentType<{ className?: string }>; animate?: boolean }> = {
    completed:     { ...C.success, Icon: CheckCircle },
    processed:     { ...C.success, Icon: CheckCircle },
    ok:            { ...C.success, Icon: CheckCircle },
    active:        { ...C.success },
    warning:       { ...C.warning, Icon: AlertTriangle },
    failed:        { ...C.error,   Icon: XCircle },
    error:         { ...C.error,   Icon: XCircle },
    token_expired: { ...C.error,   Icon: XCircle },
    queued:        { ...C.neutral, Icon: Clock },
    running:       { ...C.info,    Icon: RefreshCw, animate: true },
    syncing:       { ...C.info,    Icon: RefreshCw, animate: true },
    connecting:    { ...C.info,    Icon: RefreshCw, animate: true },
    pending:       { ...C.neutral, Icon: Clock },
    paused:        { ...C.neutral },
    inactive:      { ...C.neutral },
    disconnected:  { ...C.muted },
};

const STOCK_MAP: Record<string, { bg: string; text: string }> = {
    in_stock:     { ...C.success },
    low_stock:    { ...C.warning },
    on_backorder: { ...C.warning },
    out_of_stock: { ...C.error   },
};

const PLAN_MAP: Record<string, { bg: string; text: string }> = {
    starter:    { ...C.neutral  },
    growth:     { ...C.primary  },
    scale:      { ...C.violet   },
    enterprise: { ...C.emerald  },
    percentage: { ...C.warning  },
};

const ROLE_MAP: Record<string, { bg: string; text: string }> = {
    owner:  { ...C.primary  },
    admin:  { ...C.neutral  },
    member: { bg: 'bg-zinc-50', text: 'text-zinc-500' },
};

type Preset = 'sync' | 'stock' | 'plan' | 'role';

const PRESET_MAP: Record<Preset, Record<string, { bg: string; text: string; Icon?: React.ComponentType<{ className?: string }>; animate?: boolean }>> = {
    sync:  SYNC_MAP,
    stock: STOCK_MAP,
    plan:  PLAN_MAP,
    role:  ROLE_MAP,
};

// ─── Component ────────────────────────────────────────────────────────────────

interface StatusBadgeProps {
    status: string;
    /** Use a built-in preset color map. Defaults to 'sync' (covers store/ad/integration statuses). */
    preset?: Preset;
    /** Override the displayed label (defaults to status with underscores → spaces, capitalized) */
    label?: string;
    size?: 'sm' | 'md';
}

function toLabel(status: string): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

// Shared status badge for store status, sync log status, ad status, stock status, plan, and role.
// Each Page used to define its own inline StatusBadge — this consolidates them all.
// Related: Pages/Stores/Index.tsx, Pages/Campaigns/Index.tsx, Pages/Admin/Logs.tsx,
//          Pages/Settings/Integrations.tsx — all import this.
export function StatusBadge({ status, preset = 'sync', label, size = 'sm' }: StatusBadgeProps) {
    const map = PRESET_MAP[preset];
    const key = status.toLowerCase();
    const config = map[key] ?? { bg: 'bg-zinc-100', text: 'text-zinc-500' };
    const Icon = 'Icon' in config ? config.Icon : undefined;
    const displayLabel = label ?? toLabel(status);

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full font-medium capitalize',
                size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-sm',
                config.bg,
                config.text,
            )}
        >
            {Icon && (
                <Icon className={cn(size === 'sm' ? 'h-3 w-3' : 'h-3.5 w-3.5', config.animate && 'animate-spin')} />
            )}
            {displayLabel}
        </span>
    );
}
