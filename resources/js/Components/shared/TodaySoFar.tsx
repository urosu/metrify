import { BarChart as RechartsBarChart, Bar, ResponsiveContainer } from 'recharts';
import { cn } from '@/lib/utils';

export interface TodaySoFarProps {
  revenue: number;
  revenueFormatted: string;
  orders: number;
  projectedRevenue?: number;
  projectedRevenueFormatted?: string;
  hourlyData?: { hour: number; revenue: number }[];
  currency?: string;
  className?: string;
}

export function TodaySoFar({
  revenueFormatted,
  orders,
  projectedRevenueFormatted,
  hourlyData,
  className,
}: TodaySoFarProps) {
  return (
    <div className={cn('rounded-lg border border-border bg-card p-4', className)}>
      <p className="text-sm font-medium text-muted-foreground mb-2">Today so far</p>

      <p className="text-2xl font-semibold text-foreground leading-tight">{revenueFormatted}</p>
      <p className="mt-0.5 text-sm text-muted-foreground">
        {orders.toLocaleString()} order{orders !== 1 ? 's' : ''}
      </p>

      {hourlyData && hourlyData.length > 0 && (
        <div className="mt-3 h-[60px]">
          <ResponsiveContainer width="100%" height="100%">
            <RechartsBarChart data={hourlyData} barSize={4} margin={{ top: 0, right: 0, bottom: 0, left: 0 }}>
              <Bar dataKey="revenue" fill="var(--chart-1)" radius={[2, 2, 0, 0]} />
            </RechartsBarChart>
          </ResponsiveContainer>
        </div>
      )}

      {projectedRevenueFormatted && (
        <p className="mt-2 text-sm text-muted-foreground">
          Projected: {projectedRevenueFormatted}
        </p>
      )}
    </div>
  );
}
