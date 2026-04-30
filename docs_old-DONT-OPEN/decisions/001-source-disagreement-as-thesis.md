# ADR 001: Source Disagreement as Product Thesis

**Status:** Accepted  
**Date:** 2026-04-24  
**Context:** Competitive analysis revealed every ecommerce analytics tool claims one "true" revenue number (Shopify Native, Lifetimely, ROAS Monster, Hyros). Users trust none of them.

## Decision

Make **source disagreement visible first-class** instead of hiding it:
- Every metric carries six-source badges. **Badge order (left-to-right): Real · Store · Facebook · Google · GSC · GA4.** Real is the default active lens and renders leftmost; all other badges are outlined/dimmer when not active.
- "Not Tracked" is a first-class bucket that can go negative when platforms over-report.
- Real (Nexstage-computed reconciliation) is the default, but users can switch to any source 1-click.

**2026-04 architectural update:** GA4 was promoted from a narrow sessions-only connector to a full attribution source. It now pulls source/medium/campaign, conversions, totalRevenue, and per-order transactionId via the Analytics Data API into two new tables (`ga4_daily_attribution`, `ga4_order_attribution`). `GA4Source` is registered as priority 5 in `AttributionParserService`. The badge formerly labelled "Site" is now labelled "GA4" — the violet-500 color slot is reused.

**"Real" label status:** "Real" is a provisional label. A rename is under consideration: candidates are "Nexstage", "Reconciled", and "Net". The rename will affect every badge label, the `source-real` CSS token, and marketing copy. No rename until decision is formally accepted — keep "Real" in all docs and code until then.

## Rationale

1. **Users already know sources disagree** — they reconcile manually in spreadsheets. Making it visible is honest.
2. **Disagreement is the diagnostic** — Not the bug; the feature. "Why do Facebook and store disagree by 15%?" is the exact question Nexstage solves.
3. **Commoditized metrics are worthless** — Every tool computes ROAS. Nexstage computes ROAS + shows why sources disagree.
4. **Pricing leverage** — 0.4% revenue share aligns incentives; we profit only when merchants make better decisions, which requires trust.

## Consequences

- **UI commitment:** Every page must show six-source badges in Real-first order (non-negotiable).
- **Schema commitment:** `daily_snapshots` tracks 7 per-source revenue columns (not space-efficient, but query-transparent). New tables `ga4_daily_attribution`, `ga4_order_attribution`, `shopify_daily_sessions` feed the GA4 and Shopify session slots.
- **Marketing commitment:** This is THE wedge in messaging, not a footnote.
- **Deferred by this decision:** Benchmarking/peer data (would dilute trust thesis with comparison noise).
- **Site pixel deferred to v2.** The "Site" badge is dropped from v1. GA4 fills the gap for stores that have it set up. When Site ships in v2 it gets a new CSS token — do not reuse `source-ga4`.

## Alternatives Considered

1. **Single "Real" number** (ROAS Monster) — Users distrust opaque reconciliation; we rejected this.
2. **Toggle between sources** (Northbeam) — Northbeam requires model selection at top; we make source switching 1-click.
3. **Probabilistic weighted blend** (Fairing) — Too academic for SMB operators; direct source listing is clearer.

## Validation

- Fairing (attribution reconciliation for agencies) validates source disagreement as sellable thesis.
- Rockerbox (dedup-focused) validates that "which source claimed it?" is a real user question.
- ROAS Monster (anti-pattern we invert) validates that hiding disagreement feels like a gotcha.
