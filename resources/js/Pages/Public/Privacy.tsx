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

export default function Privacy() {
    return (
        <>
            <Head title="Privacy Policy — Nexstage" />
            <div className="min-h-screen bg-background">
                <div className="mx-auto max-w-3xl px-4 py-16">
                    <h1 className="mb-2 text-3xl font-bold tracking-tight text-foreground">
                        Privacy Policy
                    </h1>
                    <p className="mb-2 text-sm text-muted-foreground">Last updated: 2026-04-29</p>
                    <p className="mb-10 rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                        <strong>Draft notice:</strong> This is a template awaiting formal legal review.
                        It is provided for informational purposes only and does not constitute legal advice.
                        Do not rely on this document as a final or binding agreement until it has been
                        reviewed by a qualified attorney.
                    </p>

                    <Section title="1. Data We Collect">
                        <p>
                            We collect the following categories of data when you use Nexstage:
                        </p>
                        <p>
                            <strong>Account information:</strong> Your name, email address, password
                            (hashed), workspace name, billing details (handled by our payment processor,
                            Stripe), and any profile information you choose to provide.
                        </p>
                        <p>
                            <strong>Store data via OAuth:</strong> When you connect a Shopify or
                            WooCommerce store, we access order data, product data, customer metadata
                            (aggregated — we do not store individual customer PII beyond what is
                            necessary to compute analytics), refunds, and revenue figures via the
                            platform's official OAuth APIs. When you connect advertising platforms
                            (Facebook Ads, Google Ads), we access campaign spend, impressions, clicks,
                            and platform-attributed conversion data. When you connect Google Search
                            Console or GA4, we access search query data, sessions, and engagement
                            metrics.
                        </p>
                        <p>
                            <strong>Usage analytics:</strong> We collect anonymised usage data such as
                            pages visited within the app, features used, error logs, and session duration
                            to improve the Service. We do not use third-party behavioural tracking on
                            authenticated users.
                        </p>
                    </Section>

                    <Section title="2. How We Use Your Data">
                        <p>We use the data we collect to:</p>
                        <ul className="list-disc pl-5 space-y-1">
                            <li>Provide, operate, and improve the Service.</li>
                            <li>Generate analytics reports, attribution models, and performance
                                dashboards on your behalf.</li>
                            <li>Send transactional emails (email verification, billing receipts,
                                anomaly alerts) and digest summaries you have opted in to receive.</li>
                            <li>Respond to your support requests.</li>
                            <li>Comply with legal obligations.</li>
                            <li>Detect and prevent fraud, abuse, and security incidents.</li>
                        </ul>
                        <p>
                            We do not use your store data to train machine learning models sold to
                            third parties, and we do not use it for advertising targeting.
                        </p>
                    </Section>

                    <Section title="3. Sharing of Data">
                        <p>
                            <strong>We do not sell your personal data.</strong> We share data only in
                            the following limited circumstances:
                        </p>
                        <ul className="list-disc pl-5 space-y-1">
                            <li>
                                <strong>Service providers:</strong> We share data with trusted
                                sub-processors (e.g., Stripe for billing, cloud infrastructure providers
                                for hosting) who process it only on our behalf and under data processing
                                agreements.
                            </li>
                            <li>
                                <strong>Workspace members:</strong> Data within a workspace is visible to
                                all members you have invited to that workspace.
                            </li>
                            <li>
                                <strong>Legal requirements:</strong> We may disclose data if required by
                                law, court order, or government authority, or to protect the rights,
                                property, or safety of Nexstage, our users, or the public.
                            </li>
                            <li>
                                <strong>Business transfers:</strong> If Nexstage is acquired or merges
                                with another entity, your data may be transferred as part of that
                                transaction, subject to the same privacy protections.
                            </li>
                        </ul>
                    </Section>

                    <Section title="4. Storage and Security">
                        <p>
                            Your data is stored on servers located in the European Union. We implement
                            industry-standard security measures including encryption in transit (TLS),
                            encryption at rest, access controls, and regular security reviews.
                        </p>
                        <p>
                            OAuth tokens and webhook secrets are encrypted at rest using AES-256
                            encryption before being stored in our database. We retain your data for
                            as long as your account is active. Following account deletion or 30 days
                            after subscription cancellation, we delete or anonymise your data, except
                            where retention is required by law.
                        </p>
                        <p>
                            No method of transmission over the internet is 100% secure. While we
                            strive to protect your data, we cannot guarantee absolute security.
                        </p>
                    </Section>

                    <Section title="5. Your Rights (GDPR / CCPA)">
                        <p>
                            Depending on your location, you may have the following rights regarding
                            your personal data:
                        </p>
                        <ul className="list-disc pl-5 space-y-1">
                            <li><strong>Access:</strong> Request a copy of the personal data we hold
                                about you.</li>
                            <li><strong>Rectification:</strong> Request correction of inaccurate or
                                incomplete data.</li>
                            <li><strong>Erasure:</strong> Request deletion of your personal data
                                ("right to be forgotten"), subject to legal retention requirements.</li>
                            <li><strong>Portability:</strong> Request your data in a structured,
                                machine-readable format.</li>
                            <li><strong>Restriction / Objection:</strong> Request that we restrict
                                or stop processing your data in certain circumstances.</li>
                            <li><strong>Opt-out of sale (CCPA):</strong> We do not sell personal
                                data, so no opt-out is needed — but you may request confirmation at
                                any time.</li>
                        </ul>
                        <p>
                            To exercise any of these rights, contact us at hello@nexstage.io. We will
                            respond within 30 days. We may need to verify your identity before
                            fulfilling a request.
                        </p>
                    </Section>

                    <Section title="6. Cookies">
                        <p>
                            We use a small number of strictly necessary cookies to operate the Service,
                            including session cookies for authentication and CSRF protection. We do not
                            use third-party advertising cookies or behavioural tracking cookies on
                            authenticated pages.
                        </p>
                        <p>
                            On our public marketing pages (e.g., the pricing page), we may use
                            anonymised analytics to understand traffic patterns. You can disable cookies
                            in your browser settings, though doing so may affect the functionality of
                            the Service.
                        </p>
                    </Section>

                    <Section title="7. Contact">
                        <p>
                            If you have questions, concerns, or requests related to this Privacy Policy,
                            please contact us:
                        </p>
                        <p>
                            <strong>Nexstage — Data Controller</strong><br />
                            Email:{' '}
                            <a
                                href="mailto:hello@nexstage.io"
                                className="text-foreground underline hover:no-underline"
                            >
                                hello@nexstage.io
                            </a>
                        </p>
                        <p>
                            If you are located in the EU and are not satisfied with our response, you
                            have the right to lodge a complaint with your local data protection
                            authority.
                        </p>
                    </Section>
                </div>
            </div>
        </>
    );
}
