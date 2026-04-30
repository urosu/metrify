# Tools / Utilities — Competitive Research

> Researched 2026-04-30 for Wave 3A-2 `/tools` surface area.

## 1. UTM Tag Generator patterns

### Google Campaign URL Builder (reference implementation)
- Five-field form: Website URL (required), source, medium, campaign, term, content.
- Generated URL appears live beneath the form as the user types.
- Single prominent "Copy URL" button; no history, no templates.
- **Nexstage delta:** add pre-built templates (Facebook / Google / email), seeder-synced source/medium datalists, clickable campaign reference from the DB.

### HubSpot Tracking URL Builder
- Adds a "Campaign" dropdown that pulls from HubSpot campaigns (server-side list).
- Shows a live preview URL with copy button.
- Has "Save" to attach to campaign record — Nexstage equivalent: history list in localStorage.
- Navigation: under Marketing → Planning → Tracking URL Builder (one level deep).

### Klaviyo UTM builder (internal flow)
- Not a standalone page — UTM fields are embedded inside the email/flow builder inline.
- Klaviyo auto-populates `utm_source=klaviyo` and `utm_medium=email`; user customises campaign only.
- Nexstage takeaway: pre-populate source/medium from templates to match what Klaviyo ships.

### Key patterns applied
- Template picker + free-text fields (hybrid, reduces typing errors).
- Datalist suggestions seeded from known-good values (our seeder sync).
- Live URL preview with monospace font (easy to scan for typos).
- Copy to clipboard with transient "Copied!" feedback.

---

## 2. Channel Mapping / Classification UI patterns

### Triple Whale — Source attribution config
- Table of source → channel mappings with inline dropdowns for channel type.
- "Add source" row at bottom with text input + select.
- No bulk import; workspace-only, no global defaults concept.
- Nexstage delta: two-tier (global defaults + workspace overrides), unrecognised pairs surfaced automatically, test widget for live simulation.

### Northbeam — UTM convention enforcement
- Strict taxonomy enforcement: source must be in an allowlist; non-matching sources flagged red.
- Classification rules shown as if-then table: "if source contains X → channel Y".
- Northbeam calls this "UTM taxonomy" not "channel mappings" — same concept.
- Nexstage applies the same idea via `ChannelClassifierService` with regex + literal rows.

### Klaviyo — Source classification
- Klaviyo identifies sources by medium first (email → Email, sms → SMS), then source.
- No user-editable rule table; rules are hardcoded in the product.
- Nexstage advantage: user-editable workspace overrides on top of global seeded defaults.

### Linear / Stripe / Vercel — "Tools" sidebar group patterns
- Linear: flat "Settings" section, no collapsible Tools group. Tools surface inline in command palette.
- Stripe: "Developers" group in sidebar — collapsible, 4–6 items deep. Exactly the Nexstage Tools group model.
- Vercel: "Tools" not in sidebar; developer utilities under "Storage" / "Deployments" tabs.
- **Best pattern: Stripe's collapsible sidebar group** — clear label, chevron indicator, each item a direct link.

---

## 3. Naming Convention UI patterns

### Northbeam — Naming convention enforcement
- Defines a strict `{platform}_{country}_{objective}_{audience}_{creative}` template.
- Audit table: all active campaigns, green/amber/red badge per field parsed.
- Coverage % badge prominently in page header.
- "Rename suggestions" shown inline — Northbeam generates a corrected name and links to the ad platform.
- Nexstage uses pipe-delimited `Country | Campaign | Target | Shape` template with `CampaignNameParserService`.

### Triple Whale — Campaign naming
- Less opinionated; shows raw campaign names with a "name health" score.
- No template enforcement; relies on ad platform naming discipline.

### Elevar — UTM + naming enforcer
- Combines UTM validation and naming convention in one "Data Health" view.
- Shows a score (0–100) for each campaign: UTM completeness + naming completeness.
- Nexstage equivalent: coverage % + bucket breakdown (clean / partial / minimal).

---

## 4. Tools group navigation patterns (sidebar)

| Product    | Pattern                          | Depth     |
|------------|----------------------------------|-----------|
| Stripe     | Collapsible "Developers" group   | 1 chevron |
| Northbeam  | "Settings" flat list, no group   | flat      |
| Triple Whale | Side tabs per section           | 2-level   |
| Linear     | Command palette for tools        | 0 sidebar |
| Nexstage   | Collapsible "Tools" group        | 1 chevron |

Nexstage's collapsible sidebar group (4 items: Tag Generator, Channel Mappings, Naming Convention, Holidays & Events) follows the Stripe pattern — the most compact way to surface utility pages without polluting the main nav.

**No `/tools` index card grid needed** — each tool is deep enough to stand alone. Direct sidebar links preferred (confirmed by Stripe pattern).

---

## 5. Sync note (CLAUDE.md critical)

`TagGenerator.tsx` `UTM_SOURCES` and `UTM_MEDIUMS` arrays match the `utm_source_pattern` /
`utm_medium_pattern` literal values in `ChannelMappingsSeeder.php`. The `/tools/tag-generator`
page inherits these arrays directly — no drift introduced.
