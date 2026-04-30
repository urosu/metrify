/**
 * GA4 property-selection page.
 *
 * Rendered after the OAuth callback exchanges the code for tokens and
 * lists the user's GA4 properties via the Analytics Admin API.
 *
 * The user picks one property from the list; the form POSTs to
 * POST /oauth/ga4/select-property which persists tokens and the chosen
 * property_id into `ga4_properties` + `integration_credentials`.
 *
 * @see docs/competitors/_research_ga4_oauth_integration.md §Property Selection UI
 * @see app/Http/Controllers/Ga4OAuthController.php
 */

import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Activity, CheckCircle2 } from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Ga4PropertyItem {
    property_id: string;    // e.g. "properties/456"
    property_name: string;
    account_name: string;
    measurement_id: string | null;
}

interface Props {
    pending_key: string;
    properties: Ga4PropertyItem[];
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function Ga4PropertySelect({ pending_key, properties }: Props) {
    const [selected, setSelected] = useState<string | null>(
        properties.length === 1 ? properties[0].property_id : null,
    );
    const [processing, setProcessing] = useState(false);
    const [fieldError, setFieldError] = useState<string | null>(null);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (!selected) {
            setFieldError('Please select a property.');
            return;
        }

        setFieldError(null);
        setProcessing(true);

        router.post(
            '/oauth/ga4/select-property',
            { pending_key, property_id: selected },
            {
                onError: () => setProcessing(false),
                onFinish: () => setProcessing(false),
            },
        );
    }

    // Group properties by account name for readability.
    const byAccount = properties.reduce<Record<string, Ga4PropertyItem[]>>(
        (acc, prop) => {
            const key = prop.account_name;
            if (!acc[key]) acc[key] = [];
            acc[key].push(prop);
            return acc;
        },
        {},
    );

    return (
        <>
            <Head title="Connect Google Analytics 4" />

            <div
                style={{
                    minHeight: '100vh',
                    backgroundColor: 'var(--color-bg, #f9fafb)',
                    display: 'flex',
                    alignItems: 'flex-start',
                    justifyContent: 'center',
                    paddingTop: '4rem',
                    paddingBottom: '4rem',
                    paddingLeft: '1rem',
                    paddingRight: '1rem',
                }}
            >
                <div style={{ width: '100%', maxWidth: '520px' }}>
                    {/* Header */}
                    <div style={{ marginBottom: '1.5rem', textAlign: 'center' }}>
                        <div
                            style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                width: '3rem',
                                height: '3rem',
                                borderRadius: '0.75rem',
                                backgroundColor: 'var(--color-violet-100, #ede9fe)',
                                marginBottom: '1rem',
                            }}
                        >
                            <Activity
                                style={{
                                    width: '1.25rem',
                                    height: '1.25rem',
                                    color: 'var(--color-violet-600, #7c3aed)',
                                }}
                            />
                        </div>
                        <h1
                            style={{
                                fontSize: '1.125rem',
                                fontWeight: 600,
                                color: 'var(--color-zinc-900, #18181b)',
                                margin: 0,
                            }}
                        >
                            Connect Google Analytics 4
                        </h1>
                        <p
                            style={{
                                marginTop: '0.375rem',
                                fontSize: '0.875rem',
                                color: 'var(--color-zinc-500, #71717a)',
                            }}
                        >
                            Choose which GA4 property to connect to Nexstage.
                        </p>
                    </div>

                    {/* Card */}
                    <div
                        style={{
                            backgroundColor: 'var(--color-white, #ffffff)',
                            border: '1px solid var(--color-zinc-200, #e4e4e7)',
                            borderRadius: '0.75rem',
                            overflow: 'hidden',
                        }}
                    >
                        {properties.length === 0 ? (
                            <div
                                style={{
                                    padding: '2rem',
                                    textAlign: 'center',
                                    color: 'var(--color-zinc-500, #71717a)',
                                    fontSize: '0.875rem',
                                }}
                            >
                                No GA4 properties found for this Google account.
                                <br />
                                <a
                                    href="https://analytics.google.com/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    style={{
                                        marginTop: '0.5rem',
                                        display: 'inline-block',
                                        color: 'var(--color-teal-600, #0d9488)',
                                        textDecoration: 'underline',
                                        textUnderlineOffset: '2px',
                                    }}
                                >
                                    Create a GA4 property
                                </a>
                            </div>
                        ) : (
                            <form onSubmit={handleSubmit}>
                                {/* Property list grouped by account */}
                                <div
                                    style={{
                                        padding: '0.75rem',
                                        maxHeight: '22rem',
                                        overflowY: 'auto',
                                    }}
                                >
                                    {Object.entries(byAccount).map(([accountName, props]) => (
                                        <div key={accountName} style={{ marginBottom: '0.5rem' }}>
                                            {/* Account label */}
                                            <p
                                                style={{
                                                    fontSize: '0.6875rem',
                                                    fontWeight: 600,
                                                    color: 'var(--color-zinc-400, #a1a1aa)',
                                                    textTransform: 'uppercase',
                                                    letterSpacing: '0.05em',
                                                    paddingLeft: '0.625rem',
                                                    paddingTop: '0.5rem',
                                                    paddingBottom: '0.375rem',
                                                }}
                                            >
                                                {accountName}
                                            </p>

                                            {/* Property rows */}
                                            {props.map((prop) => {
                                                const isSelected = selected === prop.property_id;
                                                return (
                                                    <button
                                                        key={prop.property_id}
                                                        type="button"
                                                        onClick={() => setSelected(prop.property_id)}
                                                        style={{
                                                            display: 'flex',
                                                            alignItems: 'center',
                                                            gap: '0.75rem',
                                                            width: '100%',
                                                            padding: '0.625rem 0.75rem',
                                                            borderRadius: '0.5rem',
                                                            border: isSelected
                                                                ? '1px solid var(--color-violet-300, #c4b5fd)'
                                                                : '1px solid transparent',
                                                            backgroundColor: isSelected
                                                                ? 'var(--color-violet-50, #f5f3ff)'
                                                                : 'transparent',
                                                            cursor: 'pointer',
                                                            textAlign: 'left',
                                                            transition: 'background-color 120ms, border-color 120ms',
                                                            marginBottom: '0.25rem',
                                                        }}
                                                        aria-pressed={isSelected}
                                                    >
                                                        {/* Radio indicator */}
                                                        <span
                                                            style={{
                                                                flexShrink: 0,
                                                                display: 'flex',
                                                                alignItems: 'center',
                                                                justifyContent: 'center',
                                                                width: '1rem',
                                                                height: '1rem',
                                                                borderRadius: '50%',
                                                                border: isSelected
                                                                    ? '2px solid var(--color-violet-600, #7c3aed)'
                                                                    : '2px solid var(--color-zinc-300, #d4d4d8)',
                                                                backgroundColor: isSelected
                                                                    ? 'var(--color-violet-600, #7c3aed)'
                                                                    : 'transparent',
                                                            }}
                                                        >
                                                            {isSelected && (
                                                                <span
                                                                    style={{
                                                                        width: '0.375rem',
                                                                        height: '0.375rem',
                                                                        borderRadius: '50%',
                                                                        backgroundColor: '#ffffff',
                                                                    }}
                                                                />
                                                            )}
                                                        </span>

                                                        {/* Property info */}
                                                        <span style={{ minWidth: 0, flex: 1 }}>
                                                            <span
                                                                style={{
                                                                    display: 'block',
                                                                    fontSize: '0.875rem',
                                                                    fontWeight: isSelected ? 600 : 400,
                                                                    color: isSelected
                                                                        ? 'var(--color-violet-900, #4c1d95)'
                                                                        : 'var(--color-zinc-800, #27272a)',
                                                                    overflow: 'hidden',
                                                                    textOverflow: 'ellipsis',
                                                                    whiteSpace: 'nowrap',
                                                                }}
                                                            >
                                                                {prop.property_name}
                                                            </span>
                                                            <span
                                                                style={{
                                                                    display: 'block',
                                                                    fontSize: '0.75rem',
                                                                    color: 'var(--color-zinc-400, #a1a1aa)',
                                                                    overflow: 'hidden',
                                                                    textOverflow: 'ellipsis',
                                                                    whiteSpace: 'nowrap',
                                                                }}
                                                            >
                                                                {prop.property_id}
                                                                {prop.measurement_id
                                                                    ? ` · ${prop.measurement_id}`
                                                                    : ''}
                                                            </span>
                                                        </span>

                                                        {isSelected && (
                                                            <CheckCircle2
                                                                style={{
                                                                    flexShrink: 0,
                                                                    width: '1rem',
                                                                    height: '1rem',
                                                                    color: 'var(--color-violet-600, #7c3aed)',
                                                                }}
                                                            />
                                                        )}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    ))}
                                </div>

                                {/* Validation error */}
                                {fieldError && (
                                    <p
                                        style={{
                                            margin: '0 0.75rem 0.5rem',
                                            fontSize: '0.8125rem',
                                            color: 'var(--color-rose-600, #e11d48)',
                                        }}
                                    >
                                        {fieldError}
                                    </p>
                                )}

                                {/* Footer */}
                                <div
                                    style={{
                                        borderTop: '1px solid var(--color-zinc-100, #f4f4f5)',
                                        padding: '0.875rem 1rem',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '0.75rem',
                                        justifyContent: 'flex-end',
                                    }}
                                >
                                    <a
                                        href="/settings/integrations"
                                        style={{
                                            fontSize: '0.875rem',
                                            color: 'var(--color-zinc-500, #71717a)',
                                            textDecoration: 'none',
                                        }}
                                    >
                                        Cancel
                                    </a>
                                    <button
                                        type="submit"
                                        disabled={!selected || processing}
                                        style={{
                                            display: 'inline-flex',
                                            alignItems: 'center',
                                            gap: '0.375rem',
                                            borderRadius: '0.375rem',
                                            backgroundColor:
                                                !selected || processing
                                                    ? 'var(--color-zinc-300, #d4d4d8)'
                                                    : 'var(--color-teal-600, #0d9488)',
                                            color: !selected || processing
                                                ? 'var(--color-zinc-500, #71717a)'
                                                : '#ffffff',
                                            border: 'none',
                                            padding: '0.5rem 1rem',
                                            fontSize: '0.875rem',
                                            fontWeight: 500,
                                            cursor: !selected || processing ? 'not-allowed' : 'pointer',
                                            transition: 'background-color 120ms',
                                        }}
                                    >
                                        {processing ? 'Connecting…' : 'Connect property'}
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>

                    {/* Scope note */}
                    <p
                        style={{
                            marginTop: '1rem',
                            fontSize: '0.75rem',
                            color: 'var(--color-zinc-400, #a1a1aa)',
                            textAlign: 'center',
                        }}
                    >
                        Nexstage requests read-only access (
                        <code
                            style={{
                                fontFamily: 'monospace',
                                fontSize: '0.6875rem',
                                backgroundColor: 'var(--color-zinc-100, #f4f4f5)',
                                borderRadius: '0.25rem',
                                padding: '0.0625rem 0.25rem',
                            }}
                        >
                            analytics.readonly
                        </code>
                        ). We never write to your GA4 data.
                    </p>
                </div>
            </div>
        </>
    );
}
