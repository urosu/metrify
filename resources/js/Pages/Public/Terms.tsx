import { Head } from '@inertiajs/react';

// DRAFT — Last updated: 2026-04-29.
// This is a generic SaaS template awaiting legal review. Not legal advice.

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <section className="mb-10">
            <h2 className="mb-3 text-2xl font-semibold text-foreground">{title}</h2>
            <div className="space-y-3 text-base leading-relaxed text-muted-foreground">
                {children}
            </div>
        </section>
    );
}

export default function Terms() {
    return (
        <>
            <Head title="Terms of Service — Nexstage" />
            <div className="min-h-screen bg-background">
                <div className="mx-auto max-w-3xl px-4 py-16">
                    <h1 className="mb-2 text-3xl font-bold tracking-tight text-foreground">
                        Terms of Service
                    </h1>
                    <p className="mb-2 text-sm text-muted-foreground">Last updated: 2026-04-29</p>
                    <p className="mb-10 rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                        <strong>Draft notice:</strong> This is a template awaiting formal legal review.
                        It is provided for informational purposes only and does not constitute legal advice.
                        Do not rely on this document as a final or binding agreement until it has been
                        reviewed by a qualified attorney.
                    </p>

                    <Section title="1. Acceptance of Terms">
                        <p>
                            By accessing or using Nexstage ("the Service"), you agree to be bound by these
                            Terms of Service ("Terms"). If you are using the Service on behalf of an
                            organisation, you represent that you have authority to bind that organisation
                            to these Terms.
                        </p>
                        <p>
                            If you do not agree to these Terms, do not access or use the Service.
                            Continued use of the Service after any changes to these Terms constitutes
                            your acceptance of the revised Terms. We will notify registered users of
                            material changes by email at least 14 days in advance.
                        </p>
                    </Section>

                    <Section title="2. Description of Service">
                        <p>
                            Nexstage is a multi-channel ecommerce analytics platform designed for
                            WooCommerce and Shopify merchants. The Service aggregates data from your
                            connected stores, advertising platforms (Facebook Ads, Google Ads), and
                            web analytics tools (Google Search Console, GA4) to provide unified
                            performance reporting, attribution analysis, and business intelligence.
                        </p>
                        <p>
                            We reserve the right to modify, suspend, or discontinue any part of the
                            Service at any time with reasonable notice. We are not liable to you or
                            any third party for any modification, suspension, or discontinuation.
                        </p>
                    </Section>

                    <Section title="3. Account Registration">
                        <p>
                            To use the Service you must create an account and provide accurate, complete
                            information. You are responsible for maintaining the confidentiality of your
                            account credentials and for all activity that occurs under your account.
                        </p>
                        <p>
                            You must notify us immediately at hello@nexstage.io if you suspect
                            unauthorised access to your account. We are not liable for any loss resulting
                            from your failure to keep your credentials secure. You may not share your
                            account with others or create accounts for the purpose of circumventing
                            usage limits.
                        </p>
                    </Section>

                    <Section title="4. Acceptable Use">
                        <p>You agree not to use the Service to:</p>
                        <ul className="list-disc pl-5 space-y-1">
                            <li>Violate any applicable law or regulation.</li>
                            <li>Infringe the intellectual property rights of others.</li>
                            <li>Transmit malware, spam, or any other harmful content.</li>
                            <li>Attempt to gain unauthorised access to any part of the Service or its
                                underlying infrastructure.</li>
                            <li>Scrape, reverse-engineer, or systematically extract data from the
                                Service beyond what is permitted by these Terms.</li>
                            <li>Use the Service in a way that could damage, overload, or impair our
                                infrastructure or interfere with other users' access.</li>
                        </ul>
                        <p>
                            We reserve the right to suspend or terminate your access if we reasonably
                            believe you have violated this section.
                        </p>
                    </Section>

                    <Section title="5. Subscription and Billing">
                        <p>
                            Access to the Service is provided on a subscription basis. Pricing consists
                            of a monthly base fee plus a variable component based on attributed revenue,
                            as described on our pricing page. All fees are billed in advance on a monthly
                            cycle.
                        </p>
                        <p>
                            A 14-day free trial is available to new accounts; no credit card is required
                            to start a trial. Following the trial period, continued use of paid features
                            requires a valid payment method. Fees are non-refundable except where required
                            by applicable law or explicitly stated otherwise.
                        </p>
                        <p>
                            We may change our pricing with at least 30 days' written notice. If you do
                            not agree to the new pricing, you may cancel your subscription before the
                            change takes effect.
                        </p>
                    </Section>

                    <Section title="6. Termination">
                        <p>
                            You may cancel your subscription at any time via Settings → Billing. Upon
                            cancellation, your account will remain active until the end of the current
                            billing period, after which paid features will be disabled. Your data will
                            remain accessible in a read-only state for 30 days following cancellation,
                            after which it may be permanently deleted.
                        </p>
                        <p>
                            We may terminate or suspend your account immediately, without notice, if you
                            materially breach these Terms, engage in fraudulent activity, or if required
                            to do so by law. Termination does not relieve you of any payment obligations
                            accrued prior to termination.
                        </p>
                    </Section>

                    <Section title="7. Disclaimers">
                        <p>
                            The Service is provided "as is" and "as available" without warranties of any
                            kind, either express or implied, including but not limited to warranties of
                            merchantability, fitness for a particular purpose, or non-infringement.
                        </p>
                        <p>
                            We do not warrant that the Service will be uninterrupted, error-free, or
                            that any data or analytics outputs will be complete or accurate. Attribution
                            data is inherently probabilistic and should be used as decision support, not
                            as a sole basis for financial decisions. We are not responsible for decisions
                            you make based on data provided by the Service.
                        </p>
                    </Section>

                    <Section title="8. Limitation of Liability">
                        <p>
                            To the fullest extent permitted by applicable law, Nexstage and its
                            affiliates, officers, directors, employees, and agents shall not be liable
                            for any indirect, incidental, special, consequential, or punitive damages,
                            including but not limited to loss of profits, data, or goodwill, arising
                            from your use of or inability to use the Service.
                        </p>
                        <p>
                            Our total aggregate liability to you for any claim arising from or related
                            to these Terms or the Service shall not exceed the total fees paid by you
                            to Nexstage in the 12 months immediately preceding the claim.
                        </p>
                    </Section>

                    <Section title="9. Governing Law">
                        <p>
                            These Terms shall be governed by and construed in accordance with the laws
                            of the jurisdiction in which Nexstage is incorporated, without regard to
                            its conflict of law provisions. Any dispute arising under these Terms shall
                            be subject to the exclusive jurisdiction of the courts of that jurisdiction.
                        </p>
                        <p>
                            If any provision of these Terms is found to be unenforceable, the remaining
                            provisions will continue in full force and effect.
                        </p>
                    </Section>

                    <Section title="10. Contact">
                        <p>
                            If you have any questions about these Terms, please contact us at:
                        </p>
                        <p>
                            <strong>Nexstage</strong><br />
                            Email:{' '}
                            <a
                                href="mailto:hello@nexstage.io"
                                className="text-foreground underline hover:no-underline"
                            >
                                hello@nexstage.io
                            </a>
                        </p>
                    </Section>
                </div>
            </div>
        </>
    );
}
