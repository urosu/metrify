import { MoreHorizontal } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export interface ActionItem {
    label: string;
    icon: React.ReactNode;
    onClick: () => void;
    variant?: 'default' | 'destructive';
    disabled?: boolean;
    /** Renders a separator above this item */
    separator?: boolean;
}

interface Props {
    items: ActionItem[];
}

/**
 * The ··· overflow menu shown on each integration row.
 * Accepts a flat list of ActionItems; separator=true draws a divider above the item.
 */
export function IntegrationActionsMenu({ items }: Props) {
    const visible = items.filter((item) => item !== undefined);

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                aria-label="More actions"
            >
                <MoreHorizontal className="h-4 w-4" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-44">
                {visible.map((item, i) => (
                    <span key={i}>
                        {item.separator && <DropdownMenuSeparator />}
                        <DropdownMenuItem
                            onClick={item.onClick}
                            disabled={item.disabled}
                            variant={item.variant === 'destructive' ? 'destructive' : 'default'}
                        >
                            {item.icon}
                            {item.label}
                        </DropdownMenuItem>
                    </span>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
