export type Granularity = 'hourly' | 'daily' | 'weekly';

export function formatCurrency(amount: number, currency: string, compact = false): string {
    if (compact && Math.abs(amount) >= 1000) {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency,
            notation: 'compact',
            maximumFractionDigits: 1,
        }).format(amount);
    }
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

export function formatNumber(value: number, compact = false): string {
    if (compact && Math.abs(value) >= 1000) {
        return new Intl.NumberFormat('en', {
            notation: 'compact',
            maximumFractionDigits: 1,
        }).format(value);
    }
    return new Intl.NumberFormat('en').format(value);
}

export function formatPercent(value: number): string {
    return new Intl.NumberFormat('en', {
        style: 'percent',
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    }).format(value / 100);
}

/**
 * Percent change between two values. Returns null when prior is null/0 (undefined growth).
 * Result is the delta as a percentage (e.g. 12.5 means +12.5%, -8.0 means -8%).
 */
export function pctChange(current: number | null | undefined, prior: number | null | undefined): number | null {
    if (current == null || prior == null || prior === 0) return null;
    return ((current - prior) / Math.abs(prior)) * 100;
}

/**
 * Relative-time string for activity feeds — "just now", "5m ago", "3h ago", "2d ago",
 * else the absolute date. Operates on UTC-ish ISO strings or Date instances.
 */
export function formatRelativeTime(input: string | Date | null | undefined): string {
    if (!input) return '—';
    const d = input instanceof Date ? input : new Date(input);
    if (isNaN(d.getTime())) return '—';
    const seconds = Math.floor((Date.now() - d.getTime()) / 1000);
    if (seconds < 30)        return 'just now';
    if (seconds < 60)        return `${seconds}s ago`;
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60)        return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24)          return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 7)            return `${days}d ago`;
    return formatDateOnly(d);
}

/** Format a datetime string or Date as "DD.MM.YYYY HH:mm" in 24h. */
export function formatDatetime(date: string | Date | null | undefined, timezone?: string): string {
    if (!date) return '—';
    const d = typeof date === 'string' ? new Date(date) : date;
    const opts: Intl.DateTimeFormatOptions = {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hour12: false,
        ...(timezone ? { timeZone: timezone } : {}),
    };
    const parts = new Intl.DateTimeFormat('de-DE', opts).formatToParts(d);
    const get = (type: string) => parts.find(p => p.type === type)?.value ?? '';
    return `${get('day')}.${get('month')}.${get('year')} ${get('hour')}:${get('minute')}`;
}

/** Format a date-only string or Date as "DD.MM.YYYY". */
export function formatDateOnly(date: string | Date | null | undefined, timezone?: string): string {
    if (!date) return '—';
    const d = typeof date === 'string' ? new Date(date) : date;
    const opts: Intl.DateTimeFormatOptions = {
        day: '2-digit', month: '2-digit', year: 'numeric',
        ...(timezone ? { timeZone: timezone } : {}),
    };
    const parts = new Intl.DateTimeFormat('de-DE', opts).formatToParts(d);
    const get = (type: string) => parts.find(p => p.type === type)?.value ?? '';
    return `${get('day')}.${get('month')}.${get('year')}`;
}

/**
 * Mask a customer email for display in analytics tables.
 * Shows first character of local part + "***" + full domain.
 * Convention: j***@example.com
 *
 * Full email is appropriate only in individual customer/order DrawerSidePanels
 * where the user has explicitly opened that record.
 *
 * @see docs/competitors/_research_pii_masking.md
 */
export function maskEmail(email: string): string {
    if (!email) return '—';
    const at = email.indexOf('@');
    if (at <= 0) return '***';
    return email[0] + '***' + email.slice(at);
}

/**
 * Mask a phone number for display — shows last 4 digits only.
 * Convention: ***-1234
 *
 * @see docs/competitors/_research_pii_masking.md
 */
export function maskPhone(phone: string): string {
    if (!phone) return '—';
    const digits = phone.replace(/\D/g, '');
    if (digits.length < 4) return '***';
    return '***-' + digits.slice(-4);
}

export function formatDate(
    date: string | Date,
    granularity: Granularity,
    timezone?: string,
): string {
    const d = typeof date === 'string' ? new Date(date) : date;
    const options: Intl.DateTimeFormatOptions = {};
    if (timezone) options.timeZone = timezone;

    if (granularity === 'hourly') {
        return new Intl.DateTimeFormat('en', { ...options, hour: 'numeric', hour12: false }).format(d);
    }

    // daily and weekly — D.M.YY
    const day   = new Intl.DateTimeFormat('en', { ...options, day: 'numeric' }).format(d);
    const month = new Intl.DateTimeFormat('en', { ...options, month: 'numeric' }).format(d);
    const year  = String(new Intl.DateTimeFormat('en', { ...options, year: 'numeric' }).format(d)).slice(-2);
    return `${day}.${month}.${year}`;
}
