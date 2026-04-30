import { ToggleGroup } from '@/Components/shared/ToggleGroup';
import type { Granularity } from '@/lib/formatters';

/**
 * Day / Week toggle for LineChart granularity selection.
 *
 * Matches the existing ToggleGroup pill style. Granularity options are limited
 * to 'daily' and 'weekly' — 'hourly' is reserved for intra-day views (TodaySoFar).
 *
 * Props: value and onChange follow the same pattern as other ToggleGroups on the page.
 *
 * @see docs/UX.md §5.6 LineChart
 */
export function GranularitySwitcher({
    value,
    onChange,
}: {
    value: Granularity;
    onChange: (v: Granularity) => void;
}) {
    return (
        <ToggleGroup<Granularity>
            options={[
                { label: 'Day',  value: 'daily'  },
                { label: 'Week', value: 'weekly' },
            ]}
            value={value}
            onChange={onChange}
        />
    );
}
