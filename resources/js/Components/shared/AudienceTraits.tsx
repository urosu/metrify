import { cn } from '@/lib/utils';

export interface AudienceTrait {
  label: string;
  value: string;
  share: number;
  color?: string;
}

export interface AudienceTraitsProps {
  traits: AudienceTrait[];
  title?: string;
  className?: string;
}

export function AudienceTraits({ traits, title, className }: AudienceTraitsProps) {
  return (
    <div className={cn('space-y-2', className)}>
      {title && (
        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{title}</h3>
      )}
      <div className="space-y-2">
        {traits.map((trait, i) => (
          <div key={i} className="space-y-0.5">
            <div className="flex items-center justify-between text-xs">
              <span className="text-foreground font-medium">{trait.label}</span>
              <span className="text-muted-foreground">{trait.value}</span>
            </div>
            <div className="h-1.5 w-full rounded-full bg-muted">
              <div
                className="h-full rounded-full transition-all"
                style={{
                  width: `${Math.min(100, trait.share * 100)}%`,
                  backgroundColor: trait.color ?? 'var(--chart-7)',
                }}
              />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
