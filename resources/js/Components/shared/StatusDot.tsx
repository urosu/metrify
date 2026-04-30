import { cn } from '@/lib/utils';

export type StatusType = 'success' | 'warning' | 'error' | 'pending' | 'inactive';

interface StatusDotProps {
  status: StatusType;
  label?: string;
  size?: 'sm' | 'md';
  className?: string;
}

const STATUS_CLASSES: Record<StatusType, string> = {
  success:  'bg-emerald-500',
  warning:  'bg-amber-500',
  error:    'bg-rose-500',
  pending:  'bg-sky-500 animate-pulse',
  inactive: 'bg-muted-foreground/30',
};

export function StatusDot({ status, label, size = 'sm', className }: StatusDotProps) {
  const sizeClass = size === 'sm' ? 'h-2 w-2' : 'h-3 w-3';

  return (
    <span className={cn('inline-flex items-center gap-1.5', className)}>
      <span className={cn('rounded-full shrink-0', sizeClass, STATUS_CLASSES[status])} />
      {label && <span className="text-sm text-muted-foreground">{label}</span>}
    </span>
  );
}
