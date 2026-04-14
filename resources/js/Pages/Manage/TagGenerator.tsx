import { useState, useMemo } from 'react';
import { Head } from '@inertiajs/react';
import { Copy, Check, ExternalLink } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { cn } from '@/lib/utils';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Campaign {
    id: number;
    name: string;
    platform: 'facebook' | 'google';
}

interface Props {
    campaigns: Campaign[];
}

// ─── Templates ────────────────────────────────────────────────────────────────

/**
 * Pre-built UTM templates for Facebook and Google.
 * {{...}} placeholders are used by ad platforms for dynamic value insertion.
 *
 * Facebook: {{campaign.name}}, {{ad.name}}, {{adset.name}}
 * Google:   {campaignname}, {keyword}
 *
 * See: PLANNING.md "Tag Generator"
 */
const TEMPLATES: Record<string, { label: string; platform: 'facebook' | 'google'; utm: Partial<UtmFields> }> = {
    facebook_standard: {
        label: 'Facebook — standard',
        platform: 'facebook',
        utm: {
            source: 'facebook',
            medium: 'cpc',
            campaign: '{{campaign.name}}',
            content: '{{ad.name}}',
            term: '',
        },
    },
    facebook_adset: {
        label: 'Facebook — with ad set',
        platform: 'facebook',
        utm: {
            source: 'facebook',
            medium: 'cpc',
            campaign: '{{campaign.name}}',
            content: '{{adset.name}}',
            term: '{{ad.name}}',
        },
    },
    google_standard: {
        label: 'Google Ads — standard',
        platform: 'google',
        utm: {
            source: 'google',
            medium: 'cpc',
            campaign: '{campaignname}',
            content: '',
            term: '{keyword}',
        },
    },
    google_adgroup: {
        label: 'Google Ads — with ad group',
        platform: 'google',
        utm: {
            source: 'google',
            medium: 'cpc',
            campaign: '{campaignname}',
            content: '{adgroupname}',
            term: '{keyword}',
        },
    },
};

// ─── Helpers ──────────────────────────────────────────────────────────────────

interface UtmFields {
    source: string;
    medium: string;
    campaign: string;
    content: string;
    term: string;
}

function buildTaggedUrl(baseUrl: string, utm: UtmFields): string {
    if (!baseUrl) return '';

    // Parse the base URL (or treat as relative if parsing fails)
    let url: URL | null = null;
    try {
        const normalized = baseUrl.startsWith('http') ? baseUrl : 'https://' + baseUrl;
        url = new URL(normalized);
    } catch {
        return baseUrl;
    }

    const params: [string, string][] = [
        ['utm_source',   utm.source],
        ['utm_medium',   utm.medium],
        ['utm_campaign', utm.campaign],
        ['utm_content',  utm.content],
        ['utm_term',     utm.term],
    ];

    for (const [key, value] of params) {
        if (value) {
            // Don't percent-encode {{ }} Facebook dynamic parameters
            url.searchParams.set(key, value);
        }
    }

    // Restore double-brace placeholders that URL encoding breaks
    return url.toString().replace(/%7B%7B/g, '{{').replace(/%7D%7D/g, '}}');
}

// ─── Copy button ──────────────────────────────────────────────────────────────

function CopyButton({ text, className }: { text: string; className?: string }) {
    const [copied, setCopied] = useState(false);

    function handleCopy(): void {
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }).catch(() => {
            // Fallback for environments without clipboard API
            const ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    }

    return (
        <button
            type="button"
            onClick={handleCopy}
            disabled={!text}
            className={cn(
                'flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-medium transition-colors',
                copied
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                    : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:bg-zinc-50',
                !text && 'cursor-not-allowed opacity-40',
                className,
            )}
        >
            {copied ? (
                <><Check className="h-3.5 w-3.5" /> Copied!</>
            ) : (
                <><Copy className="h-3.5 w-3.5" /> Copy</>
            )}
        </button>
    );
}

// ─── UTM Field ────────────────────────────────────────────────────────────────

function UtmField({
    label,
    value,
    onChange,
    placeholder,
    hint,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
    placeholder?: string;
    hint?: string;
}) {
    return (
        <div>
            <label className="mb-1 block text-xs font-medium text-zinc-600">{label}</label>
            <input
                type="text"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className="w-full rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-900 outline-none placeholder:text-zinc-300 focus:border-primary/50 focus:ring-1 focus:ring-primary/20"
            />
            {hint && <p className="mt-0.5 text-[10px] text-zinc-400">{hint}</p>}
        </div>
    );
}

// ─── Main component ───────────────────────────────────────────────────────────

export default function TagGenerator({ campaigns }: Props) {
    const [baseUrl, setBaseUrl]     = useState('');
    const [activeTemplate, setActiveTemplate] = useState<string>('facebook_standard');
    const [utm, setUtm] = useState<UtmFields>({
        source:   'facebook',
        medium:   'cpc',
        campaign: '{{campaign.name}}',
        content:  '{{ad.name}}',
        term:     '',
    });

    function applyTemplate(key: string): void {
        const tmpl = TEMPLATES[key];
        if (!tmpl) return;
        setActiveTemplate(key);
        setUtm((prev) => ({ ...prev, ...tmpl.utm }));
    }

    function setField<K extends keyof UtmFields>(key: K, value: UtmFields[K]): void {
        setUtm((prev) => ({ ...prev, [key]: value }));
    }

    const taggedUrl = useMemo(() => buildTaggedUrl(baseUrl, utm), [baseUrl, utm]);

    const facebookCampaigns = campaigns.filter((c) => c.platform === 'facebook');
    const googleCampaigns   = campaigns.filter((c) => c.platform === 'google');

    return (
        <AppLayout>
            <Head title="Tag Generator" />
            <PageHeader
                title="Tag Generator"
                subtitle="Build UTM-tagged URLs for your ad campaigns"
            />

            {/* Why this matters */}
            <div className="mb-6 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                Nexstage matches ad spend to store orders using <strong>utm_source</strong> and <strong>utm_campaign</strong>.
                Without UTM parameters on your ad destination URLs, attribution numbers will be empty.
                Use the templates below to generate properly tagged URLs.
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* Left: form */}
                <div className="space-y-5">
                    {/* Template picker */}
                    <div className="rounded-xl border border-zinc-200 bg-white p-5">
                        <h2 className="mb-3 text-sm font-semibold text-zinc-900">Start from a template</h2>
                        <div className="grid grid-cols-2 gap-2">
                            {Object.entries(TEMPLATES).map(([key, tmpl]) => (
                                <button
                                    key={key}
                                    type="button"
                                    onClick={() => applyTemplate(key)}
                                    className={cn(
                                        'rounded-md border px-3 py-2 text-left text-xs transition-colors',
                                        activeTemplate === key
                                            ? 'border-primary bg-primary/5 text-primary font-medium'
                                            : 'border-zinc-200 text-zinc-600 hover:border-zinc-300 hover:bg-zinc-50',
                                    )}
                                >
                                    {tmpl.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Base URL */}
                    <div className="rounded-xl border border-zinc-200 bg-white p-5">
                        <h2 className="mb-3 text-sm font-semibold text-zinc-900">Destination URL</h2>
                        <UtmField
                            label="Base URL"
                            value={baseUrl}
                            onChange={setBaseUrl}
                            placeholder="https://your-store.com/product-page"
                            hint="The page your ad links to — usually your product or landing page."
                        />
                    </div>

                    {/* UTM Parameters */}
                    <div className="rounded-xl border border-zinc-200 bg-white p-5">
                        <h2 className="mb-3 text-sm font-semibold text-zinc-900">UTM Parameters</h2>
                        <div className="space-y-3">
                            <UtmField
                                label="utm_source *"
                                value={utm.source}
                                onChange={(v) => setField('source', v)}
                                placeholder="facebook"
                                hint="Traffic source. Must match: facebook, fb, google, cpc, etc."
                            />
                            <UtmField
                                label="utm_medium *"
                                value={utm.medium}
                                onChange={(v) => setField('medium', v)}
                                placeholder="cpc"
                                hint="Marketing medium. Use 'cpc' for paid ads."
                            />
                            <UtmField
                                label="utm_campaign"
                                value={utm.campaign}
                                onChange={(v) => setField('campaign', v)}
                                placeholder="{{campaign.name}} or {campaignname}"
                                hint="Facebook: {{campaign.name}} — Google: {campaignname}"
                            />
                            <UtmField
                                label="utm_content"
                                value={utm.content}
                                onChange={(v) => setField('content', v)}
                                placeholder="{{ad.name}}"
                                hint="Ad or ad set identifier. Optional but recommended."
                            />
                            <UtmField
                                label="utm_term"
                                value={utm.term}
                                onChange={(v) => setField('term', v)}
                                placeholder="{keyword}"
                                hint="Google: {keyword} for search keyword. Usually empty for Facebook."
                            />
                        </div>
                    </div>
                </div>

                {/* Right: preview + campaign reference */}
                <div className="space-y-5">
                    {/* Live preview */}
                    <div className="rounded-xl border border-zinc-200 bg-white p-5">
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="text-sm font-semibold text-zinc-900">Tagged URL preview</h2>
                            <CopyButton text={taggedUrl} />
                        </div>
                        <div className="min-h-[80px] rounded-md border border-zinc-100 bg-zinc-50 p-3">
                            {taggedUrl ? (
                                <p className="break-all text-xs font-mono text-zinc-700 leading-relaxed">
                                    {taggedUrl}
                                </p>
                            ) : (
                                <p className="text-xs text-zinc-400">
                                    Enter a destination URL above to see the preview.
                                </p>
                            )}
                        </div>
                        {taggedUrl && (
                            <p className="mt-2 text-[10px] text-zinc-400">
                                Paste this URL into your ad's destination URL field.
                                Platforms will replace dynamic placeholders (e.g. {'{{'} campaign.name {'}}'}
                                ) at serving time.
                            </p>
                        )}
                    </div>

                    {/* Quick source reference */}
                    <div className="rounded-xl border border-zinc-200 bg-white p-5">
                        <h2 className="mb-3 text-sm font-semibold text-zinc-900">Source matching reference</h2>
                        <p className="mb-3 text-xs text-zinc-500">
                            Nexstage recognizes these utm_source values for paid attribution:
                        </p>
                        <div className="space-y-2">
                            <div className="rounded-md bg-zinc-50 px-3 py-2">
                                <div className="text-[10px] font-semibold uppercase tracking-wide text-zinc-400 mb-1">Facebook / Meta</div>
                                <div className="flex flex-wrap gap-1.5">
                                    {['facebook', 'fb', 'ig', 'instagram'].map((s) => (
                                        <code key={s} className="rounded bg-white border border-zinc-200 px-1.5 py-0.5 text-[11px] text-zinc-700">{s}</code>
                                    ))}
                                </div>
                            </div>
                            <div className="rounded-md bg-zinc-50 px-3 py-2">
                                <div className="text-[10px] font-semibold uppercase tracking-wide text-zinc-400 mb-1">Google Ads</div>
                                <div className="flex flex-wrap gap-1.5">
                                    {['google', 'cpc', 'google-ads', 'ppc'].map((s) => (
                                        <code key={s} className="rounded bg-white border border-zinc-200 px-1.5 py-0.5 text-[11px] text-zinc-700">{s}</code>
                                    ))}
                                </div>
                            </div>
                        </div>
                        <p className="mt-3 text-[10px] text-zinc-400">
                            Any other value will appear as "Other Tagged" revenue — visible but not counted as paid attribution.
                        </p>
                    </div>

                    {/* Connected campaigns — quick reference */}
                    {(facebookCampaigns.length > 0 || googleCampaigns.length > 0) && (
                        <div className="rounded-xl border border-zinc-200 bg-white p-5">
                            <h2 className="mb-3 text-sm font-semibold text-zinc-900">Your campaign names</h2>
                            <p className="mb-3 text-xs text-zinc-500">
                                Copy these exact names into utm_campaign for Real ROAS matching.
                            </p>
                            {facebookCampaigns.length > 0 && (
                                <div className="mb-3">
                                    <div className="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-400">Facebook</div>
                                    <div className="space-y-1">
                                        {facebookCampaigns.map((c) => (
                                            <div key={c.id} className="flex items-center justify-between gap-2 rounded-md bg-zinc-50 px-2.5 py-1.5">
                                                <span className="flex-1 truncate text-xs text-zinc-700">{c.name}</span>
                                                <CopyButton text={c.name} />
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                            {googleCampaigns.length > 0 && (
                                <div>
                                    <div className="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-400">Google</div>
                                    <div className="space-y-1">
                                        {googleCampaigns.map((c) => (
                                            <div key={c.id} className="flex items-center justify-between gap-2 rounded-md bg-zinc-50 px-2.5 py-1.5">
                                                <span className="flex-1 truncate text-xs text-zinc-700">{c.name}</span>
                                                <CopyButton text={c.name} />
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Docs link */}
                    <div className="rounded-xl border border-zinc-100 bg-zinc-50 p-4">
                        <p className="text-xs text-zinc-500">
                            Need to verify your existing tags?{' '}
                            <a
                                href="https://ga-dev-tools.google/ga4/campaign-url-builder/"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-0.5 font-medium text-primary hover:underline"
                            >
                                Google Campaign URL Builder <ExternalLink className="h-3 w-3" />
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
