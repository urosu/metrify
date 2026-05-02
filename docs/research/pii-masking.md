# PII Masking Research — Customer Email / Phone Display

## Research queries
- Customer email masking ecommerce dashboard
- GDPR masked email pattern
- Triple Whale customer journey email masking
- Polar PII display pattern

---

## Customer email masking — ecommerce dashboards

GDPR Art. 5(1)(c) (data minimisation) requires that personal data is "adequate, relevant and limited to what is necessary". In analytics dashboards this means:
- Emails should not be displayed in full unless the user has a specific need to identify the individual
- Aggregate analytics views (cohorts, RFM grids, revenue charts) should never show raw emails
- Row-level tables (order lists, customer lists) may show emails but should mask them by default with a "reveal" option (click-to-copy, hover, or modal)

Common masking conventions in the industry:
1. `j***@example.com` — reveal first char of local part, mask rest, show domain
2. `jane@***.com` — show full local part, mask domain TLD
3. `jane.d***@example.com` — show local part up to natural boundary (dot), mask rest
4. Full redaction `[customer]` — replace entirely; used in SOC2-compliant screens

The most common convention across analytics SaaS is **option 1**: `j***@example.com`. It preserves enough information to identify the customer in a support context (first letter + domain) without exposing the full email.

## Triple Whale customer journey email masking

Triple Whale's Pixel / Customer Journeys screen shows order rows with customer emails masked as: `j***@gmail.com`. The rule is:
- First character of local part is visible
- All remaining local part characters replaced with `***` (three asterisks regardless of length)
- `@` separator visible
- Domain shown in full (not considered PII under most interpretations since it's not user-specific)

Triple Whale also provides a "Reveal email" button on the individual customer journey panel (their drawer/modal equivalent) for users who need the full email to look up in their CRM.

## Polar PII display pattern

Polar Analytics masks emails in their customer table using the same pattern (`j***@domain.com`) and adds a tooltip on hover: "Email masked for privacy. Click to copy full email." The click-to-copy action logs the reveal in their audit trail (SOC2 compliance).

For Nexstage: reveal-and-copy on click is the target pattern; audit logging is a backend task outside this pass.

## GDPR masked email pattern — standardisation

EU GDPR guidance from the EDPB (European Data Protection Board) on pseudonymisation suggests replacing characters that make an email uniquely identifiable. The local part (before `@`) is the sensitive portion; the domain is generally not. This aligns with the Triple Whale / Polar approach.

---

## Nexstage standard

**Convention chosen: `j***@example.com`**
- First character of local part
- `***` for the rest of the local part (three asterisks, length-independent)
- `@domain.tld` shown in full

**Implementation:**
```ts
/**
 * Mask a customer email for display in analytics tables.
 * Shows first character of local part + *** + full domain.
 * Convention: j***@example.com
 * @see docs/competitors/_research_pii_masking.md
 */
export function maskEmail(email: string): string {
    const at = email.indexOf('@');
    if (at <= 0) return '***';
    return email[0] + '***' + email.slice(at);
}
```

**Reveal pattern:** Full email shown in individual customer drawer (DrawerSidePanel) title where the user has explicitly opened that record. Masked in all list/table row contexts.

**Phone numbers:** `+1 ***-***-1234` — show country code + last 4 digits only.
```ts
export function maskPhone(phone: string): string {
    const digits = phone.replace(/\D/g, '');
    if (digits.length < 4) return '***';
    return '***-' + digits.slice(-4);
}
```

## Files touched
- `resources/js/lib/formatters.ts` — added `maskEmail` and `maskPhone` helpers
- `resources/js/Pages/Orders/Index.tsx` — `CustomerCell` uses `maskEmail` in table rows
- `resources/js/Pages/Customers/Index.tsx` — customer table rows use `maskEmail`; drawer title shows full email (user explicitly opened that record)
