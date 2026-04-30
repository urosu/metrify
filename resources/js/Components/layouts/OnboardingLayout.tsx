import { Link, router } from '@inertiajs/react';

interface Props {
    children: React.ReactNode;
    /**
     * The condensed progress step (1–5) rendered in the step indicator.
     * Maps from the 7-step internal machine:
     *   0 Welcome          → 1
     *   1 Connect store    → 2
     *   2 Country          → 3
     *   4 Costs            → 4
     *   5 Import window    → 4
     *   6 Importing        → 5
     */
    currentStep: 1 | 2 | 3 | 4 | 5;
}

// Related: resources/js/Pages/Onboarding/Index.tsx (only consumer)
export default function OnboardingLayout({ children, currentStep }: Props) {
    const steps = ['Welcome', 'Connect', 'Store setup', 'Import window', 'Syncing'];

    return (
        <div
            className="flex min-h-screen flex-col items-center px-4 py-10"
            style={{ background: 'var(--color-background)' }}
        >
            {/* Logo + logout */}
            <div className="mb-8 flex w-full max-w-xl items-center justify-between">
                <Link href="/" className="text-lg font-semibold tracking-tight" style={{ color: 'var(--color-text)' }}>
                    Nexstage
                </Link>
                <button
                    type="button"
                    onClick={() => router.post(route('logout'))}
                    className="text-sm hover:underline"
                    style={{ color: 'var(--color-text-tertiary)' }}
                >
                    Log out
                </button>
            </div>

            {/* Step indicator */}
            <div className="mb-8 flex items-center gap-2">
                {steps.map((label, idx) => {
                    const num = idx + 1;
                    const done = num < currentStep;
                    const active = num === currentStep;

                    return (
                        <div key={label} className="flex items-center gap-2">
                            <div className="flex items-center gap-1.5">
                                {/* Dot / check */}
                                <div
                                    className="flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold transition-colors"
                                    style={
                                        done
                                            ? { background: 'var(--color-primary)', color: 'var(--color-primary-fg)' }
                                            : active
                                              ? {
                                                    background: 'var(--color-primary)',
                                                    color: 'var(--color-primary-fg)',
                                                    outline: '2px solid var(--color-primary)',
                                                    outlineOffset: '2px',
                                                }
                                              : { background: 'var(--color-muted)', color: 'var(--color-text-secondary)' }
                                    }
                                >
                                    {done ? (
                                        <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    ) : (
                                        num
                                    )}
                                </div>
                                {/* Label — hidden on mobile to keep indicator compact */}
                                <span
                                    className="hidden text-sm font-medium sm:inline"
                                    style={{
                                        color: active ? 'var(--color-text)' : done ? 'var(--color-text-secondary)' : 'var(--color-text-muted)',
                                    }}
                                >
                                    {label}
                                </span>
                            </div>
                            {/* Connector line */}
                            {idx < steps.length - 1 && (
                                <div
                                    className="h-px w-6 sm:w-10"
                                    style={{
                                        background: done ? 'var(--color-primary)' : 'var(--color-border)',
                                    }}
                                />
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Card */}
            <div
                className="w-full max-w-xl rounded-xl border bg-white p-8"
                style={{
                    borderColor: 'var(--color-border)',
                    boxShadow: '0 1px 4px rgba(0,0,0,0.06)',
                }}
            >
                {children}
            </div>
        </div>
    );
}
