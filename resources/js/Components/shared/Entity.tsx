import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { StatusDot, StatusType } from './StatusDot';

interface EntityProps {
  name: string;
  icon?: React.ComponentType<{ className?: string }>;
  imageUrl?: string;
  status?: StatusType;
  href?: string;
  maxWidth?: number;
  size?: 'sm' | 'md';
  className?: string;
}

export function Entity({
  name,
  icon: Icon,
  imageUrl,
  status,
  href,
  maxWidth = 200,
  size = 'md',
  className,
}: EntityProps) {
  const textClass = size === 'sm' ? 'text-xs' : 'text-sm';

  const inner = (
    <span className={cn('inline-flex items-center gap-2', className)}>
      {imageUrl ? (
        <img
          src={imageUrl}
          alt=""
          className="h-5 w-5 rounded-sm object-cover shrink-0"
        />
      ) : Icon ? (
        <Icon className="h-5 w-5 shrink-0 text-muted-foreground" />
      ) : null}

      <span
        className={cn('truncate font-medium text-foreground', textClass)}
        style={{ maxWidth }}
      >
        {name}
      </span>

      {status && <StatusDot status={status} />}
    </span>
  );

  if (href) {
    return (
      <Link href={href} className="inline-flex">
        {inner}
      </Link>
    );
  }

  return inner;
}
