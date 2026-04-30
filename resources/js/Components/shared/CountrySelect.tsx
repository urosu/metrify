import { cn } from '@/lib/utils';

// Common countries ordered by ecommerce relevance (EU/EEA first, then English-speaking,
// then major APAC/LATAM markets). Value is ISO 3166-1 alpha-2.
export const COUNTRY_OPTIONS: { code: string; name: string }[] = [
    // European Union / EEA
    { code: 'AT', name: 'Austria' },
    { code: 'BE', name: 'Belgium' },
    { code: 'BG', name: 'Bulgaria' },
    { code: 'HR', name: 'Croatia' },
    { code: 'CY', name: 'Cyprus' },
    { code: 'CZ', name: 'Czech Republic' },
    { code: 'DK', name: 'Denmark' },
    { code: 'EE', name: 'Estonia' },
    { code: 'FI', name: 'Finland' },
    { code: 'FR', name: 'France' },
    { code: 'DE', name: 'Germany' },
    { code: 'GR', name: 'Greece' },
    { code: 'HU', name: 'Hungary' },
    { code: 'IE', name: 'Ireland' },
    { code: 'IT', name: 'Italy' },
    { code: 'LV', name: 'Latvia' },
    { code: 'LT', name: 'Lithuania' },
    { code: 'LU', name: 'Luxembourg' },
    { code: 'MT', name: 'Malta' },
    { code: 'NL', name: 'Netherlands' },
    { code: 'PL', name: 'Poland' },
    { code: 'PT', name: 'Portugal' },
    { code: 'RO', name: 'Romania' },
    { code: 'SK', name: 'Slovakia' },
    { code: 'SI', name: 'Slovenia' },
    { code: 'ES', name: 'Spain' },
    { code: 'SE', name: 'Sweden' },
    // Non-EU European
    { code: 'AD', name: 'Andorra' },
    { code: 'CH', name: 'Switzerland' },
    { code: 'GB', name: 'United Kingdom' },
    { code: 'NO', name: 'Norway' },
    { code: 'TR', name: 'Turkey' },
    { code: 'UA', name: 'Ukraine' },
    { code: 'RU', name: 'Russia' },
    // English-speaking / Americas
    { code: 'US', name: 'United States' },
    { code: 'CA', name: 'Canada' },
    { code: 'AU', name: 'Australia' },
    { code: 'NZ', name: 'New Zealand' },
    { code: 'ZA', name: 'South Africa' },
    { code: 'BR', name: 'Brazil' },
    { code: 'MX', name: 'Mexico' },
    { code: 'AE', name: 'UAE' },
    // Asia-Pacific
    { code: 'SG', name: 'Singapore' },
    { code: 'JP', name: 'Japan' },
    { code: 'KR', name: 'South Korea' },
    { code: 'CN', name: 'China' },
    { code: 'IN', name: 'India' },
];

interface Props {
    value: string;
    onChange: (value: string) => void;
    name?: string;
    id?: string;
    className?: string;
    disabled?: boolean;
    /**
     * When true, adds an "All countries (default)" option with value "*" at the top
     * of the list (after the empty placeholder). Used for tax rules where "*" is the
     * catch-all wildcard that applies to every order regardless of shipping country.
     * Do NOT enable for workspace/store primary_country_code fields.
     */
    includeWildcard?: boolean;
}

/**
 * Reusable country selector backed by a curated list of ~50 common countries.
 *
 * Renders a styled <select> matching the project's existing form inputs.
 * The wildcard "All countries" option is opt-in via `includeWildcard` — only
 * used in tax rules where country="*" means "apply to all orders".
 *
 * @see docs/planning/schema.md §1.6 (tax_rules wildcard)
 * @see docs/UX.md §5 (CountrySelect primitive)
 */
export function CountrySelect({ value, onChange, name, id, className, disabled, includeWildcard }: Props) {
    return (
        <select
            name={name}
            id={id}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            disabled={disabled}
            className={cn(
                'block w-full rounded-md border border-border bg-white px-3 py-2 text-sm text-foreground',
                'focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary',
                'disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
        >
            <option value="">Select country…</option>
            {includeWildcard && (
                <option value="*">All countries (default)</option>
            )}
            {COUNTRY_OPTIONS.map(({ code, name: label }) => (
                <option key={code} value={code}>
                    {label} ({code})
                </option>
            ))}
        </select>
    );
}
