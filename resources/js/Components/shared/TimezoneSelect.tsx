import { useMemo } from 'react';
import { cn } from '@/lib/utils';

interface Props {
    id?: string;
    value: string;
    onChange: (value: string) => void;
    disabled?: boolean;
    className?: string;
}

interface TzEntry {
    tz: string;
    offsetMinutes: number;
    label: string;
}

// Returns the current UTC offset in signed minutes for a given IANA timezone.
// Uses Intl.DateTimeFormat with timeZoneName: 'shortOffset' (e.g. "GMT+2", "GMT-5:30").
// Correctly reflects DST — the offset is for right now, not a fixed standard offset.
function getOffsetMinutes(tz: string): number {
    const parts = new Intl.DateTimeFormat('en', {
        timeZone: tz,
        timeZoneName: 'shortOffset',
    }).formatToParts(new Date());

    const offsetStr = parts.find((p) => p.type === 'timeZoneName')?.value ?? 'GMT+0';
    const match = offsetStr.match(/GMT([+-])(\d+)(?::(\d+))?/);
    if (!match) return 0;

    const sign = match[1] === '+' ? 1 : -1;
    const hours = parseInt(match[2], 10);
    const minutes = parseInt(match[3] ?? '0', 10);
    return sign * (hours * 60 + minutes);
}

// Formats signed minutes into "UTC+02:00" / "UTC-05:30" / "UTC+00:00".
function formatOffset(minutes: number): string {
    const sign = minutes >= 0 ? '+' : '-';
    const abs = Math.abs(minutes);
    const h = String(Math.floor(abs / 60)).padStart(2, '0');
    const m = String(abs % 60).padStart(2, '0');
    return `UTC${sign}${h}:${m}`;
}

// Groups IANA timezones by region prefix, computes offsets, and sorts each group west-to-east.
// Intl.supportedValuesOf is available in Chrome 99+, FF 86+, Safari 15.4+.
function getGroupedTimezones(): Record<string, TzEntry[]> {
    const all: string[] = Intl.supportedValuesOf('timeZone');
    const groups: Record<string, TzEntry[]> = {};

    for (const tz of all) {
        const slash = tz.indexOf('/');
        const region = slash === -1 ? 'Other' : tz.slice(0, slash);
        const offsetMinutes = getOffsetMinutes(tz);
        const entry: TzEntry = {
            tz,
            offsetMinutes,
            label: `${tz} (${formatOffset(offsetMinutes)})`,
        };
        (groups[region] ??= []).push(entry);
    }

    for (const entries of Object.values(groups)) {
        entries.sort((a, b) => a.offsetMinutes - b.offsetMinutes || a.tz.localeCompare(b.tz));
    }

    return groups;
}

export function TimezoneSelect({ id, value, onChange, disabled, className }: Props) {
    const groups = useMemo(() => getGroupedTimezones(), []);

    return (
        <select
            id={id}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            disabled={disabled}
            className={cn(
                'block w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900',
                'focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary',
                'disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
        >
            {Object.entries(groups).map(([region, entries]) => (
                <optgroup key={region} label={region}>
                    {entries.map(({ tz, label }) => (
                        <option key={tz} value={tz}>
                            {label}
                        </option>
                    ))}
                </optgroup>
            ))}
        </select>
    );
}