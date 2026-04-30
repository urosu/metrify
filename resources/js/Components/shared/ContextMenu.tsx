import React from 'react';
import { cn } from '@/lib/utils';
import {
  ContextMenu as RContextMenu,
  ContextMenuContent,
  ContextMenuItem as RContextMenuItem,
  ContextMenuTrigger,
} from '@/Components/ui/context-menu';

export interface ContextMenuItem {
  label: string;
  icon?: React.ComponentType<{ className?: string }>;
  onClick: () => void;
  destructive?: boolean;
  disabled?: boolean;
}

export interface ContextMenuProps {
  items: ContextMenuItem[];
  children: React.ReactNode;
}

export function ContextMenu({ items, children }: ContextMenuProps) {
  return (
    <RContextMenu>
      <ContextMenuTrigger>{children}</ContextMenuTrigger>
      <ContextMenuContent className="w-48">
        {items.map((item, i) => (
          <RContextMenuItem
            key={i}
            onClick={item.onClick}
            disabled={item.disabled}
            className={cn(item.destructive && 'text-rose-600')}
          >
            {item.icon && <item.icon className="mr-2 h-4 w-4" />}
            {item.label}
          </RContextMenuItem>
        ))}
      </ContextMenuContent>
    </RContextMenu>
  );
}
