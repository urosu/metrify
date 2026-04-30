import { cn } from '@/lib/utils';

interface LoadingStateProps {
  rows?: number;
  variant?: 'card' | 'table' | 'text';
  className?: string;
}

export function LoadingState({ rows = 5, variant = 'card', className }: LoadingStateProps) {
  if (variant === 'card') {
    return (
      <div className={cn('h-32 w-full animate-pulse rounded-xl bg-muted', className)} />
    );
  }

  if (variant === 'table') {
    return (
      <div className={cn('flex flex-col gap-3', className)}>
        {Array.from({ length: rows }).map((_, i) => (
          <div key={i} className="h-4 w-full animate-pulse rounded bg-muted" />
        ))}
      </div>
    );
  }

  return (
    <div className={cn('flex flex-col gap-2', className)}>
      <div className="h-4 w-full animate-pulse rounded bg-muted" />
      <div className="h-4 w-3/4 animate-pulse rounded bg-muted" />
    </div>
  );
}
