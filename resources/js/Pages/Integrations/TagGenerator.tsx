import { useState, useMemo } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { Copy, Check } from 'lucide-react';
import AppLayout from '@/Components/layouts/AppLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { SectionCard } from '@/Components/shared/SectionCard';
import { SubNavTabs } from '@/Components/shared/SubNavTabs';
import { cn } from '@/lib/utils';
import { wurl } from '@/lib/workspace-url';
import type { PageProps } from '@/types';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Campaign {
    id: number;
    name: string;
    platform: string;
}

interface Props {
    campaigns: Campaign[];
}

interface UtmFields {
    source: string;
    medium: string;
    campaign: string;
    content: string;
    term: string;
}

// ─── Templates ────────────────────────────────────────────────────────────────

/**
 * Pre-built UTM templates for Facebook and Google.
 * {{...}} placeholders are used by ad platforms for dynamic value insertion.
 *
 * Facebook: {{campaign.name}}, {{ad.name}}, {{adset.name}}
 * Google:   {campaignname}, {keyword}, {adgroupname}
 *
 * IMPORTANT: buildTaggedUrl restores these placeholders after URL encoding —
 * do not remove that step or Facebook ad destination URLs will break.
 */
const TEMPLATES: Record<string, { label: string; utm: Partial<UtmFields> }> = {
    facebook_standard: {
        label: 'Facebook — standard',
        utm: { source: 'facebook', medium: 'cpc', campaign: '{{campaign.name}}', content: '{{ad.name}}', term: '' },
    },
    facebook_adset: {
        label: 'Facebook — with ad set',
        utm: { source: 'facebook', medium: 'cpc', campaign: '{{campaign.name}}', content: '{{adset.name}}', term: '{{ad.name}}' },
    },
    google_standard: {
        label: 'Google Ads — standard',
        utm: { source: 'google', medium: 'cpc', campaign: '{campaignname}', content: '', term: '{keyword}' },
    },
    google_adgroup: {
        label: 'Google Ads — with ad group',
        utm: { source: 'google', medium: 'cpc', campaign: '{campaignname}', content: '{adgroupname}', term: '{keyword}' },
    },
    email_blast: {
        label: 'Email blast',
        utm: { source: 'newsletter', medium: 'email', campaign: '', content: 'hero_cta', term: '' },
    },
    klaviyo_flow: {
        label: 'Klaviyo flow',
        utm: { source: 'klaviyo', medium: 'email', campaign: '', content: 'flow_email', term: '' },
    },
    sms_blast: {
        label: 'SMS blast',
        utm: { source: 'klaviyo-sms', medium: 'sms', campaign: '', content: '', term: '' },
    },
};

// UTM source/medium suggestions — keep in sync with ChannelMappingsSeeder.php
// (see CLAUDE.md "UTM source / medium sync").
// Sources: literal utm_source_pattern values from seeder that merchants type manually.
const UTM_SOURCES = [
    'facebook', 'instagram', 'meta', 'fb', 'ig',
    'google', 'adwords', 'bing', 'microsoft', 'youtube',
    'tiktok', 'twitter', 'x', 'linkedin', 'pinterest',
    'klaviyo', 'klaviyo-sms', 'mailchimp', 'omnisend',
    'newsletter', 'postscript', 'attentive',
    'impact', 'cj', 'shareasale', 'awin',
    'direct', 'referral',
];

// Mediums: values from seeder utm_medium_pattern column (plus cpm — common but
// the seeder omits it because it's medium-only, no source pattern for it).
const UTM_MEDIUMS = [
    'cpc', 'ppc', 'paid', 'paidsocial', 'social',
    'email', 'sms', 'organic', 'referral', 'affiliate',
];

const UTM_CONTENT_FORMATS = [
    'image', 'video', 'carousel', 'story', 'reel', 'collection',
];

// ─── Helpers ──────────────────────────────────────────────────────────────────

function buildTaggedUrl(baseUrl: string, utm: UtmFields): string {
    if (!baseUrl) return '';
    try {
        const url = new URL(baseUrl.startsWith('http') ? baseUrl : `https://${baseUrl}`);
        const params: [string, string][] = [
            ['utm_source',   utm.source],
            ['utm_medium',   utm.medium],
            ['utm_campaign', utm.campaign],
            ['utm_content',  utm.content],
            ['utm_term',     utm.term],
        ];
        for (const [key, value] of params) {
            if (value) url.searchParams.set(key, value);
        }
        // Restore double-brace Facebook placeholders that URL encoding breaks.
        // Without this, {{campaign.name}} becomes %7B%7Bcampaign.name%7D%7D,
        // which ad platforms cannot parse as a dynamic value insertion token.
        return url.toString().replace(/%7B%7B/g, '{{').replace(/%7D%7D/g, '}}');
    } catch {
        return '';
    }
}

// ─── Copy Button ──────────────────────────────────────────────────────────────

function CopyButton({ text, label = 'Copy', className }: { text: string; label?: string; className?: string }) {
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
                'inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-medium transition-colors',
                copied
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                    : 'border-border bg-card text-muted-foreground hover:border-input hover:bg-muted/50',
                !text && 'cursor-not-allowed opacity-40',
                className,
            )}
        >
            {copied ? (
                <><Check className="h-3.5 w-3.5" /> Copied!</>
            ) : (
                <><Copy className="h-3.5 w-3.5" /> {label}</>
            )}
        </button>
    );
}

// ─── Field ────────────────────────────────────────────────────────────────────

function Field({
    label, value, onChange, suggestions, placeholder, required,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
    suggestions?: string[];
    placeholder?: string;
    required?: boolean;
}) {
    return (
        <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-foreground">
                {label}{required && <span className="ml-0.5 text-red-500">*</span>}
            </label>
            <input
                type="text"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                list={suggestions ? `${label}-list` : undefined}
                className="rounded-md border border-input px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground/70 focus:border-primary/70 focus:outline-none focus:ring-1 focus:ring-primary/50"
            />
            {suggestions && (
                <datalist id={`${label}-list`}>
                    {suggestions.map((s) => <option key={s} value={s} />)}
                </datalist>
            )}
        </div>
    );
}

// ─── UTM Examples ─────────────────────────────────────────────────────────────

interface UtmExample {
    label: string;
    scenario: string;
    fields: Partial<UtmFields>;
    paramString: string;
}

const UTM_EXAMPLES: UtmExample[] = [
    {
        label: 'Facebook ad campaign',
        scenario: 'Paid social campaign using Facebook dynamic value insertion.',
        fields: { source: 'facebook', medium: 'paid_social', campaign: 'summer_sale_2026', content: 'carousel_v2' },
        paramString: 'utm_source=facebook&utm_medium=paid_social&utm_campaign=summer_sale_2026&utm_content=carousel_v2',
    },
    {
        label: 'Google Search ad',
        scenario: 'Search campaign — Google replaces {keyword} at serving time.',
        fields: { source: 'google', medium: 'cpc', campaign: 'brand_search', term: '{keyword}' },
        paramString: 'utm_source=google&utm_medium=cpc&utm_campaign=brand_search&utm_term={keyword}',
    },
    {
        label: 'Email newsletter',
        scenario: 'Newsletter blast — content identifies which CTA was clicked.',
        fields: { source: 'newsletter', medium: 'email', campaign: 'may_promo', content: 'hero_cta' },
        paramString: 'utm_source=newsletter&utm_medium=email&utm_campaign=may_promo&utm_content=hero_cta',
    },
    {
        label: 'Instagram bio link',
        scenario: 'Organic bio link — no term or content needed.',
        fields: { source: 'instagram', medium: 'social', campaign: 'bio_link', content: '', term: '' },
        paramString: 'utm_source=instagram&utm_medium=social&utm_campaign=bio_link',
    },
    {
        label: 'Affiliate partner',
        scenario: 'Referral from a partner — use a slug, no spaces.',
        fields: { source: 'affiliate-partnerco', medium: 'referral', campaign: 'q2_partnership' },
        paramString: 'utm_source=affiliate-partnerco&utm_medium=referral&utm_campaign=q2_partnership',
    },
    {
        label: 'TikTok ad',
        scenario: 'Paid TikTok video — paid_social aligns with channel mapping.',
        fields: { source: 'tiktok', medium: 'paid_social', campaign: 'launch_video' },
        paramString: 'utm_source=tiktok&utm_medium=paid_social&utm_campaign=launch_video',
    },
];

// ─── UTM Examples Section ─────────────────────────────────────────────────────

function UtmExamplesSection({ onApply }: { onApply: (fields: Partial<UtmFields>) => void }) {
    const [open, setOpen] = useState(false);
    const [copiedIdx, setCopiedIdx] = useState<number | null>(null);

    function handleCopy(params: string, idx: number) {
        navigator.clipboard.writeText(params).then(() => {
            setCopiedIdx(idx);
            setTimeout(() => setCopiedIdx(null), 2000);
        }).catch(() => {});
    }

    return (
        <details open={open} onToggle={(e) => setOpen((e.target as HTMLDetailsElement).open)}>
            <summary className="flex cursor-pointer items-center gap-2 rounded-lg border border-border bg-card px-4 py-3 text-sm font-medium text-foreground hover:bg-muted/50 transition-colors list-none">
                <span className="flex-1">Common UTM examples</span>
                <span className="text-xs text-muted-foreground/70">{open ? 'Hide' : 'Show 6 examples'}</span>
            </summary>
            <div className="mt-2 space-y-2">
                {UTM_EXAMPLES.map((ex, idx) => (
                    <div key={idx} className="rounded-lg border border-border bg-muted/50 px-4 py-3">
                        <div className="mb-1 flex items-center gap-2">
                            <span className="text-xs font-semibold text-foreground">{ex.label}</span>
                            <span className="text-sm text-muted-foreground/70">— {ex.scenario}</span>
                        </div>
                        <div className="flex items-start gap-2">
                            <code className="flex-1 break-all font-mono text-sm leading-relaxed text-primary">
                                {ex.paramString}
                            </code>
                            <div className="flex shrink-0 gap-1.5">
                                <button
                                    type="button"
                                    onClick={() => handleCopy(ex.paramString, idx)}
                                    className={cn(
                                        'inline-flex items-center gap-1 rounded border px-2 py-1.5 text-xs font-medium transition-colors',
                                        copiedIdx === idx
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : 'border-border bg-card text-muted-foreground hover:bg-muted',
                                    )}
                                >
                                    {copiedIdx === idx ? <Check className="h-3 w-3" /> : <Copy className="h-3 w-3" />}
                                    {copiedIdx === idx ? 'Copied' : 'Copy'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => onApply(ex.fields)}
                                    className="inline-flex items-center gap-1 rounded border border-border bg-card px-2 py-1.5 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors"
                                >
                                    Fill form
                                </button>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </details>
    );
}

// ─── UTM 101 FAQ ──────────────────────────────────────────────────────────────

const UTM_FAQ = [
    {
        q: 'What are UTM parameters and why do they matter?',
        a: 'UTM parameters are tags appended to URLs that tell Nexstage (and Google Analytics) where a visitor came from. Without them, store orders have no source and appear as "Unattributed" — making channel attribution impossible.',
    },
    {
        q: "What's the difference between source and medium?",
        a: 'Source is the origin (facebook, google, newsletter). Medium is the channel type (cpc, paid_social, email). Think "who sent them" vs "how they got here". Channel mappings use source+medium together to group traffic.',
    },
    {
        q: 'Should I use spaces or underscores?',
        a: 'Always use underscores or hyphens — no spaces. Spaces get URL-encoded (%20 or +) and can break matching. E.g. "summer_sale" not "summer sale".',
    },
    {
        q: 'How do I check if my UTMs are working?',
        a: 'Place a test order using a UTM-tagged link, then open the Orders page and check the Source column. If it shows the expected source, attribution is working. If it shows "Unattributed", the UTM did not survive the checkout.',
    },
];

function UtmFaqSection() {
    return (
        <details>
            <summary className="flex cursor-pointer items-center gap-2 rounded-lg border border-border bg-card px-4 py-3 text-sm font-medium text-foreground hover:bg-muted/50 transition-colors list-none">
                <span className="flex-1">UTM 101 — quick answers</span>
                <span className="text-xs text-muted-foreground/70">Show FAQ</span>
            </summary>
            <div className="mt-2 divide-y divide-border rounded-lg border border-border bg-card">
                {UTM_FAQ.map((item, idx) => (
                    <div key={idx} className="px-4 py-3">
                        <p className="text-xs font-semibold text-foreground">{item.q}</p>
                        <p className="mt-1 text-sm leading-relaxed text-muted-foreground">{item.a}</p>
                    </div>
                ))}
            </div>
        </details>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function TagGenerator({ campaigns }: Props) {
    const { workspace: ws } = usePage<PageProps>().props;
    const wsSlug = ws?.slug;
    const w = (path: string) => wurl(wsSlug, path);

    const [activeTemplate, setActiveTemplate] = useState<string>('facebook_standard');
    const [baseUrl,  setBaseUrl]  = useState('');
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

    function applyExampleFields(fields: Partial<UtmFields>): void {
        setUtm((prev) => ({ ...prev, ...fields }));
        setActiveTemplate('');
    }

    const taggedUrl = useMemo(() => buildTaggedUrl(baseUrl, utm), [baseUrl, utm]);

    const campaignNames = campaigns.map((c) => c.name);
    const facebookCampaigns = campaigns.filter((c) => c.platform === 'facebook');
    const googleCampaigns   = campaigns.filter((c) => c.platform === 'google');

    const tabs = [
        { label: 'Tag Generator',     value: 'tag-generator',    href: w('/integrations/tag-generator'),    active: true  },
        { label: 'Naming Convention', value: 'naming-convention', href: w('/integrations/naming-convention'), active: false },
        { label: 'Channel Mappings',  value: 'channel-mappings',  href: w('/integrations/channel-mappings'),  active: false },
    ];

    return (
        <AppLayout>
            <Head title="UTM Tag Generator" />

            <div className="space-y-6">
                <PageHeader
                    title="UTM Tag Generator"
                    subtitle="Build tracking URLs for your campaigns. Values must match channel mappings so attribution resolves correctly."
                />

                <SubNavTabs tabs={tabs} className="mb-6" />

                {/* Attribution explainer */}
                <div className="mb-6 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Nexstage matches ad spend to store orders using <strong>utm_source</strong> and <strong>utm_campaign</strong>.
                    Without UTM parameters on your ad destination URLs, attribution numbers will be empty.
                    Use the templates below to generate properly tagged URLs.
                </div>

                {/* UTM examples + FAQ */}
                <div className="mb-6 space-y-2">
                    <UtmExamplesSection onApply={applyExampleFields} />
                    <UtmFaqSection />
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Left: Builder */}
                    <div className="space-y-5">
                        {/* Template picker */}
                        <SectionCard title="Start from a template">
                            <div className="grid grid-cols-2 gap-2">
                                {Object.entries(TEMPLATES).map(([key, tmpl]) => (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => applyTemplate(key)}
                                        className={cn(
                                            'rounded-md border px-3 py-2 text-left text-xs transition-colors',
                                            activeTemplate === key
                                                ? 'border-primary/70 bg-primary/5 text-primary font-medium'
                                                : 'border-border text-muted-foreground hover:border-input hover:bg-muted/50',
                                        )}
                                    >
                                        {tmpl.label}
                                    </button>
                                ))}
                            </div>
                        </SectionCard>

                        {/* UTM fields */}
                        <SectionCard title="Build your URL">
                            <div className="space-y-4">
                                <Field
                                    label="Destination URL"
                                    value={baseUrl}
                                    onChange={setBaseUrl}
                                    placeholder="https://yourstore.com/products/example"
                                    required
                                />
                                <Field
                                    label="utm_source"
                                    value={utm.source}
                                    onChange={(v) => setField('source', v)}
                                    suggestions={UTM_SOURCES}
                                    placeholder="e.g. facebook"
                                    required
                                />
                                <Field
                                    label="utm_medium"
                                    value={utm.medium}
                                    onChange={(v) => setField('medium', v)}
                                    suggestions={UTM_MEDIUMS}
                                    placeholder="e.g. cpc"
                                    required
                                />
                                <Field
                                    label="utm_campaign"
                                    value={utm.campaign}
                                    onChange={(v) => setField('campaign', v)}
                                    suggestions={campaignNames}
                                    placeholder="{{campaign.name}} or {campaignname}"
                                />
                                <Field
                                    label="utm_content"
                                    value={utm.content}
                                    onChange={(v) => setField('content', v)}
                                    suggestions={UTM_CONTENT_FORMATS}
                                    placeholder="e.g. {{ad.name}} or video_15s"
                                />
                                <Field
                                    label="utm_term"
                                    value={utm.term}
                                    onChange={(v) => setField('term', v)}
                                    placeholder="e.g. {keyword} or running+shoes"
                                />
                            </div>
                        </SectionCard>
                    </div>

                    {/* Right: Preview + reference */}
                    <div className="space-y-5">
                        {/* Live preview */}
                        <SectionCard title="Generated URL">
                            <div className="flex flex-col gap-4">
                                <div className="min-h-[80px] rounded-md border border-border bg-muted/50 p-3">
                                    {taggedUrl ? (
                                        <p className="break-all font-mono text-sm leading-relaxed text-foreground">
                                            {taggedUrl}
                                        </p>
                                    ) : (
                                        <p className="text-sm text-muted-foreground/70">
                                            Enter a destination URL above to see the preview.
                                        </p>
                                    )}
                                </div>
                                <div className="flex">
                                    <CopyButton text={taggedUrl} label="Copy URL" />
                                </div>
                                {taggedUrl && (
                                    <p className="text-sm text-muted-foreground/70">
                                        Paste this URL into your ad's destination URL field.
                                        Platforms replace dynamic placeholders (e.g.{' '}
                                        <code>{'{{campaign.name}}'}</code>) at serving time.
                                    </p>
                                )}
                            </div>
                        </SectionCard>

                        {/* Parameter reference */}
                        <SectionCard title="Parameter reference">
                            <div className="space-y-2">
                                {[
                                    { param: 'utm_source',   desc: 'Traffic origin — must match a channel_mappings source pattern.' },
                                    { param: 'utm_medium',   desc: 'Marketing channel type (cpc, email, sms …).' },
                                    { param: 'utm_campaign', desc: 'Campaign identifier — use ad platform dynamic name or a slug.' },
                                    { param: 'utm_content',  desc: 'Creative variant or ad format for A/B testing.' },
                                    { param: 'utm_term',     desc: 'Keyword or audience segment (paid search).' },
                                ].map(({ param, desc }) => (
                                    <div key={param} className="flex gap-2 text-xs">
                                        <span className="shrink-0 font-mono text-primary">{param}</span>
                                        <span className="text-muted-foreground">{desc}</span>
                                    </div>
                                ))}
                            </div>
                        </SectionCard>
                    </div>
                </div>

                {/* Campaign reference */}
                {campaigns.length > 0 && (
                    <div className="mt-6">
                        <SectionCard title={`Campaign reference (${campaigns.length} active/paused)`}>
                            <div className="max-h-64 overflow-y-auto space-y-4">
                                {facebookCampaigns.length > 0 && (
                                    <div>
                                        <div className="mb-1.5 text-sm font-semibold uppercase tracking-wide text-muted-foreground/70">Facebook</div>
                                        <div className="space-y-1">
                                            {facebookCampaigns.map((c) => (
                                                <div key={c.id} className="flex items-center justify-between gap-2 rounded-md bg-muted/50 px-2.5 py-1.5">
                                                    <span
                                                        className="flex-1 truncate text-sm text-foreground cursor-pointer hover:text-primary"
                                                        onClick={() => setField('campaign', c.name)}
                                                        title="Click to use as utm_campaign"
                                                    >
                                                        {c.name}
                                                    </span>
                                                    <CopyButton text={c.name} label="Copy name" />
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                                {googleCampaigns.length > 0 && (
                                    <div>
                                        <div className="mb-1.5 text-sm font-semibold uppercase tracking-wide text-muted-foreground/70">Google</div>
                                        <div className="space-y-1">
                                            {googleCampaigns.map((c) => (
                                                <div key={c.id} className="flex items-center justify-between gap-2 rounded-md bg-muted/50 px-2.5 py-1.5">
                                                    <span
                                                        className="flex-1 truncate text-sm text-foreground cursor-pointer hover:text-primary"
                                                        onClick={() => setField('campaign', c.name)}
                                                        title="Click to use as utm_campaign"
                                                    >
                                                        {c.name}
                                                    </span>
                                                    <CopyButton text={c.name} label="Copy name" />
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                                {facebookCampaigns.length === 0 && googleCampaigns.length === 0 && (
                                    // Fallback: platforms where we don't know the exact platform value
                                    <table className="w-full text-sm">
                                        <thead className="bg-muted/50 border-b border-border">
                                            <tr className="text-left text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                                <th className="px-3 py-2">Campaign name</th>
                                                <th className="px-3 py-2">Platform</th>
                                                <th className="px-3 py-2" />
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border">
                                            {campaigns.map((c) => (
                                                <tr key={c.id} className="hover:bg-muted/50">
                                                    <td
                                                        className="py-1.5 pr-4 text-foreground cursor-pointer hover:text-primary"
                                                        onClick={() => setField('campaign', c.name)}
                                                        title="Click to use as utm_campaign"
                                                    >
                                                        {c.name}
                                                    </td>
                                                    <td className="py-1.5 pr-4 text-muted-foreground/70 capitalize">{c.platform}</td>
                                                    <td className="py-1.5">
                                                        <CopyButton text={c.name} label="Copy name" />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}
                            </div>
                        </SectionCard>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
