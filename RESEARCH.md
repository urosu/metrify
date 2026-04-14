# Nexstage Research Pass — April 2026

Single research pass to validate (or invalidate) assumptions in PLANNING.md before final rewrite. Findings are organised by impact: things that change the plan, things that confirm it, things that are now verified facts to bake into code/docs.

---

## 1. Competitive landscape — what changed

### The WooCommerce-supported competitors (the threat surface)

| Tool | WC support | Pricing entry | Wedge | Threat level |
|---|---|---|---|---|
| **ThoughtMetric** | Yes (since launch) | $99/mo (50k pageviews) | All-features-in-every-plan, MTA, server-side, post-purchase surveys | **HIGH** |
| **Cometly** | Yes | Custom (ad-spend) | Server-side first-party, CAPI conversion sync, AI recommendations | **HIGH** |
| **Northbeam** | Yes | $1,000/mo+ | ML-based MTA + MMM, 6-8mo onboarding | LOW (wrong segment) |
| **Conjura** | Yes (recent connector) | Demo-only / GMV-based | Profit-first, SKU contribution margin, Owly AI agent | **HIGH** |
| **Glew** | Yes | Opaque | Multi-store BI, B2B/wholesale focus | MED |
| **Metorik** | Yes (WC-native) | Volume-based | Cohorts, LTV, subscriptions, ex-Automattic | MED |
| **AgencyAnalytics** | Yes (80+ integrations) | ~$59/mo entry | White-label agency reporting | MED |
| **Polar Analytics** | **No** (roadmap) | $510/mo+ | Snowflake, semantic layer, MTA | Future threat |
| **Triple Whale** | **No** (Shopify-only) | $129/mo+ | Moby AI agents, Triple Pixel | N/A |
| **ROASMonster** | Yes (implied) | Demo-only | Target ROAS/CPO evaluation, "over/under the line" framing | **HIGH** |

**Key finding: ThoughtMetric is our most direct competitor, not Triple Whale.** They already ship the "WooCommerce-native, multi-touch attribution, simple pricing" combination at $99/mo. They include 5 attribution models, server-side tracking, post-purchase surveys, SKU-level reporting, and creative analytics. Their explicit pitch: "every feature in every plan, no upgrades." This is uncomfortable.

**What ThoughtMetric does NOT do** (verified from their own docs and competitor reviews):
- No GSC / organic search integration
- No site performance monitoring (Lighthouse / uptime)
- No anomaly detection or cross-channel correlation ("why did it change")
- No incrementality testing
- No API (per GetApp listing)
- No EU/GDPR positioning — US company, US pricing
- Pricing scales by **pageviews**, not GMV or ad spend (regressive for high-traffic / low-AOV stores)
- Limited to formula-based attribution; no causal validation

**Cometly** is the second WooCommerce-supported MTA tool. They have CAPI conversion sync (sending data back to Meta/Google), which is something we don't have. They're priced custom by ad spend, which is opaque. They don't have GSC or organic in their pitch either.

**Conjura** is the most concerning new entrant. They launched a WooCommerce connector recently (early 2026 per their own blog), they're Ireland-based (EU-friendly), they push "profit-first" (contribution margin, COGS, SKU profitability), and they have an "Owly AI" agent. They're demo-only pricing, which suggests mid-market+. Their content marketing is the strongest in the WooCommerce analytics space right now — every blog post on Conjura.com is hammering "revenue is vanity, profit is sanity."

**Polar Analytics** says WooCommerce is on the roadmap. When they ship it, they're a real threat — they have semantic layer, dedicated Snowflake, 45+ connectors, and Klaviyo enrichment. Time advantage is finite. However, their pricing starts at $510/mo for the BI module alone, which puts them well above Nexstage's positioning.

**Triple Whale** is pivoting to "autonomous execution" via Moby Agents. They explicitly want to move from reporting to taking action (auto-budget reallocation, creative generation, one-click publishing). This is the 2026 industry direction — not just dashboards, but agents that act. **They're still Shopify-only**, so no direct threat, but the framing matters: investors and customers will increasingly ask "what does it DO, not just show."

**ROASMonster** is the closest spiritual sibling to Nexstage. Their entire pitch is "evaluate results against your target ROAS/CPO so you know where you're over or under the line." This is identical to our MetricCard target system — and it means **we're not inventing the target-based framing, they already own it.** ROASMonster is demo-only (no public pricing), claims "15+ features", focuses on CPO-conscious brands with tight margins. Their public content does NOT mention GSC, site performance, or anomaly correlation as core features. **Implication**: our wedge cannot be "target-based evaluation" — ROASMonster ships that today. Our wedge is **four-pillar correlation** (store + ads + organic + site) plus **the "why did it change" narrative layer**. The target system is table stakes for this segment, not differentiation.

### What competitors complain about (from their own customers' G2/Capterra reviews)

- **Triple Whale**: "Attribution is consistently buggy", "support ignores tracking problems for months at $600/mo", "expensive for SMBs", "slow loading on large data"
- **Polar Analytics**: "Integration process is time-consuming", "ratios hard to understand", "data takes time to update", "custom connectors require support intervention"
- **ThoughtMetric**: "Wants Amazon integration", "wants ROAS / LTV / fastest-moving items / frequently-bought-together insights", "wants more platforms", "discrepancy on data with no clarity for 10 days"
- **Northbeam**: "6-8 month onboarding", "expensive", "opaque pricing"
- Universal across tools: data discrepancies vs platform numbers, slow support, opaque pricing

---

## 2. Customer pain points — what WooCommerce store owners actually want

Synthesised from competitor review sites, WordPress.org plugin reviews, agency-focused content, and ecommerce blogs (April 2025 — March 2026).

### Pain points repeatedly mentioned

1. **The ad spend blindspot.** "WooCommerce sees $1,000 in sales but doesn't know you spent $800 on Meta ads to get them." Repeated almost verbatim across multiple sources. This is THE foundational pain. Nexstage's "MER and Real ROAS" framing addresses this directly.

2. **Native WooCommerce Analytics is too slow and too thin.** Single-currency only, multi-currency support listed as beta limitation. 1,000 orders takes 1+ hour for initial sync. No visitor/session/page data ("the most important missing data" per top WordPress.org review). No demographic/geographic breakdown beyond country. Plugin marketed as "early access (beta)" still in April 2026.

3. **Profit ≠ revenue.** Across Conjura, Polar, Triple Whale, and most G2 reviews, the consistent demand is **contribution margin / SKU-level profitability** — not just revenue tracking. "Revenue can increase while profit declines. Best-selling products can quietly lose money." This is being positioned as 2026's table stakes by nearly every competitor.

4. **Data fragmentation.** "Shopify says one number, GA4 another, Klaviyo a third, Meta a fourth." Universal pain. This is exactly Nexstage's trust thesis — but the framing is usually "give me one number" not "show me the disagreement." We are betting that buyers want the disagreement visible. **This bet is unverified.** Most competitor messaging is "single source of truth", "consolidated dashboard" — the exact opposite of "show disagreement."

5. **Agencies want white-label and multi-client roll-up.** AgencyAnalytics owns this segment with 80+ integrations and white-label reporting. Nexstage doesn't have white-label and hasn't seriously addressed agency multi-tenancy beyond per-workspace billing.

6. **WooCommerce-native attribution has known bugs.** Open GitHub issue #62508 (Dec 2025): WC 8.9 / 9.x frequently records `Source = Unknown` despite valid UTMs in the session, even with no redirects. WooCommerce support has acknowledged but not fixed. WC attribution uses session-only cookies (not persistent), so a user who visits via Facebook ad on Monday and converts via direct on Thursday gets attributed to "direct" — same flaw as last-click GA4. **Implication for Nexstage**: relying solely on WC native attribution will inherit these bugs. The fallback UTM extraction we already do is critical, not optional.

7. **Server-side / first-party tracking is becoming a baseline expectation.** ThoughtMetric, Cometly, Polar all advertise "server-side" tracking heavily. We don't currently do this — we extract from WC's order_attribution meta after the fact. This is OK for accuracy (server is ground truth) but we don't have the "we capture conversions even when pixel is blocked" pitch.

8. **AI agents are the 2026 narrative.** Triple Whale Moby, Polar AI, Conjura Owly, ThoughtMetric AI Connectors, Cometly AI Recommendations — every competitor has at least one AI feature this year. The framing is "ask in plain English, get an answer" or "agent acts on your behalf." Static dashboards are increasingly seen as old-fashioned. We have AI summary planned but it's a small section.

9. **What store owners want that nobody's nailed yet** (from review feedback gaps):
   - Country/state/city revenue breakdown (multiple reviews ask for this)
   - Frequently-bought-together / market basket
   - Customer segmentation tied to acquisition source
   - Stock-aware sales velocity (don't celebrate sales of products that are about to stock out)
   - Side-by-side platform-vs-actual comparison ("what Facebook says vs what actually happened")

**The last bullet is interesting.** A ThoughtMetric review on Capterra explicitly asks for "a section where it shows you a side-by-side of what the platform (TikTok or Facebook) is saying they brought in (in both dollars and ROAS) and then what ThoughtMetric is saying the actual is." This is exactly Nexstage's Platform ROAS vs Real ROAS framing. **This is the validation we needed for the trust thesis.** At least one customer of our closest competitor is explicitly asking for what we already plan to ship.

---

## 3. Fact verification

### WooCommerce native attribution (verified)

- Available since **WooCommerce 8.5** (January 2024), default-enabled.
- Renamed from "Origin" to "Attribution" column in **WC 8.9** (May 2024).
- Uses **last-click only**. Session cookies, not persistent — destroyed when browser closes.
- Stores `_wc_order_attribution_*` meta keys: `source_type`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`, `referrer`, `device_type`, `session_count`, `session_pages`, `user_agent`.
- Single-currency only — multi-currency listed as beta limitation in WooCommerce.com extension.
- HPOS-compatible. WP Consent API integration available.
- **Known bug** (GitHub #62508, Dec 2025): WC 8.9 / 9.x / 10.4 frequently records "Unknown" despite valid UTM session entry, even when checkout completes in the same session with no redirects. WooCommerce support has not fixed it. Possible cause: timing/session initialization, theme JS deferral, or CMP interaction.
- **WAF compatibility filter** since WC 9.0: `wc_order_attribution_use_base64_cookies` for stores hit by OWASP/Comodo WAF rulesets that flag the attribution cookies.
- **Implication**: PLANNING.md should explicitly document the known "Unknown" bug as a reason our raw UTM fallback (`utm_*` query param extraction at order creation time) is a hard requirement, not a nice-to-have.

### GA4 EU legal status (verified Jan-March 2026)

- **Conditionally legal** in most EU countries since the EU-US Data Privacy Framework (DPF) was adopted in July 2023 — but with strict cookie consent requirements.
- **DPF is fragile.** PCLOB (Privacy and Civil Liberties Oversight Board) lost its quorum in January 2025 after Trump fired Democratic members. NOYB (Schrems) has filed challenges. CJEU is expected to review DPF validity by 2026. Schrems publicly called the framework "built on sand."
- **EDPB published a 2025 report** urging the Commission to re-evaluate the adequacy decision within three years.
- **Cookie rejection rates**: 87% in Germany, 73% in France. Independent testing shows GA4 misses **20% to 56% of actual website traffic** depending on consent implementation. Cookie-free European tools capture 40-60% more visitors.
- Norwegian DPA confirmed (January 2025) that previous rulings apply to GA4 too.
- **Verdict**: The trust thesis stance UNCHANGED. The EU positioning argument is **stronger**, not weaker:
  - DPF instability + 87% German rejection = GA4 missing the majority of traffic in our target geographies.
  - Adding GA4 to Nexstage would actively undermine our EU positioning.
  - Conclusion is the same as before: **never integrate GA4**.

### Facebook Marketing API (verified)

- Current version: **v25.0** (released February 18, 2026).
- Previous v24.0 released October 8, 2025. v23.0 May 2025. v22.0 January 2025. Cadence: roughly every 3-4 months.
- **v22.0 was the minimum required from September 9, 2025.** Anything older fails entirely.
- **Major deprecations active right now**:
  - Advantage+ Shopping Campaigns (ASC) and Advantage+ App Campaigns (AAC): cannot be created/updated via API as of v25.0 (Feb 18, 2026). Extends to all versions May 19, 2026. By v26.0 (Sept 2026), remaining ASC/AAC campaigns will be paused entirely.
  - Page Reach, Video Impressions, Story Impressions, 3-second viewer metrics deprecated June 2026 — replaced by Media Views / Media Viewers.
  - 10s video metrics deprecated January 26, 2026 (already happened, no replacement announced).
  - Webhooks mTLS certificate change to Meta CA by March 31, 2026.
- **v19 and v20 sunset dates**: v19 removed May 21, 2026; v20 removed September 24, 2026.
- **Implication for Nexstage**: PLANNING.md previously listed "v25.0+" as the API version. This is correct as of April 2026 but will need to track quarterly upgrades. CLAUDE.md should mention "always check current Facebook Marketing API version before assuming v25 is current."

### Google Ads API (verified)

- Current version: **v23.1** (released February 25, 2026). v23.0 released January 28, 2026. v22 still supported (sunset late 2026). v20 and v21 receive only minor updates.
- **v19 was sunset February 11, 2026** — all v19 requests now fail. Anyone running on v19 has been broken for two months.
- **New release cadence as of January 2026**: Google moved from quarterly to **monthly minor versions** + roughly 4 major versions per year. This means more frequent upgrades but smaller breaking changes per upgrade.
- Sunset rule: max 4 major versions supported simultaneously, each major version supported for ~12 months.
- **Implication**: PLANNING.md should reference "v23+ via google-ads-php client library" without pinning a specific version, because Google now ships monthly. CLAUDE.md should mention checking current version before any Google Ads code changes.

### Anthropic API / Claude models (verified)

- Current model family is **Claude 4.6**. Three production tiers:
  - **Claude Opus 4.6** — `claude-opus-4-6` — $5/$25 per million tokens. Most capable. Default for complex reasoning.
  - **Claude Sonnet 4.6** — `claude-sonnet-4-6` — $3/$15 per million tokens. Best balance. Default choice for production.
  - **Claude Haiku 4.5** — `claude-haiku-4-5-20251001` — $1/$5 per million tokens. Fastest, cheapest current model.
- Both 4.6 models include **1M token context window at standard pricing** (no surcharge above 200K like older models).
- **Batch API**: 50% discount on all models, processed within 24 hours.
- **Prompt caching**: 90% discount on cache reads. Cache writes 1.25x base (5min TTL) or 2x base (1hr TTL).
- Stacking caching + batch can reduce costs by up to 95% on repeated workloads.
- **Deprecated as of April 2026**: Claude Haiku 3 retires April 19, 2026. Claude 3.5 Haiku and Claude 3.7 Sonnet removed from daily record February 19, 2026.
- **Implication for Nexstage**: AI summary feature should default to **Sonnet 4.6** (`claude-sonnet-4-6`). This is what CLAUDE.md should specify. For very large dashboard syntheses, batch API + caching is the right cost optimization.

### CLAUDE.md best practices (verified from Anthropic docs + community guidance)

Strong consensus from Anthropic's own Claude Code best practices doc, HumanLayer blog, FlorianBruniaux's ultimate guide, and community sources:

1. **Keep it short.** Multiple sources say <200 lines. HumanLayer: "your CLAUDE.md file should contain as few instructions as possible — ideally only ones which are universally applicable."
2. **Don't include code style guidelines** — that's a linter's job. LLMs are slow and expensive compared to deterministic tools.
3. **Don't stuff every command.** Frontier thinking models can attend to ~150-200 instructions reliably; smaller/non-thinking models attend to fewer. Every irrelevant instruction degrades performance on the relevant ones.
4. **Structure: WHAT, WHY, HOW.**
   - WHAT: tech stack, project structure, codebase map.
   - WHY: purpose of the project, what each part does.
   - HOW: how to verify changes (test commands, lint, typecheck).
5. **Document what Claude gets wrong**, not what it gets right. Build a "Gotchas" section that grows over time.
6. **Avoid "never X" framing.** Use "prefer Y over X" — "never" creates dead-ends where the agent gets stuck.
7. **Don't @-import files** (embeds entire file every run). Use prose references: "For complex usage, see path/to/docs.md".
8. **Important commands belong in CLAUDE.md** but keep them minimal: only the ones run constantly.
9. **Critical safety rules** (no secrets, no destructive operations) belong in CLAUDE.md or as Stop hooks.
10. **Iterate based on what Claude actually gets wrong**, not on theoretical concerns.

**Implication for Nexstage CLAUDE.md**: The current draft is ~248 lines, which is over the recommended limit. Trim aggressively. Move detailed schema/architecture docs out of CLAUDE.md and into PLANNING.md (referenced by prose pointer). Keep CLAUDE.md focused on: tech stack, where things live, how to run things, the trust thesis, and the most common mistakes Claude makes on this codebase.

---

## 4. Implications for PLANNING.md — proposed changes

### Things to ADD

**A. Contribution margin / COGS tracking (NEW — significant addition)**

This is the single most consistent gap. Conjura, Polar, Triple Whale all build their pitch around it. Multiple WC store owners on review sites explicitly want it. Without it, our positioning is "ROAS dashboard" rather than "profit dashboard", which puts us in the same bucket as ThoughtMetric.

Proposed minimal scope:
- New table `product_costs` with `workspace_id`, `product_id`, `cost_amount`, `cost_currency`, `effective_from` (history-tracked).
- Optional CSV upload for COGS per SKU (most stores already track this in a spreadsheet).
- New computed metrics in MetricCard: `Contribution Margin %`, `Contribution Profit`, `Profit per Order`. All marked with the gold "Real" badge since they're derived.
- Dashboard hero gets a "Real Profit" tile when COGS is configured.
- Phase: NEW Phase 1.6 (after current Phase 1.5 cleanup).
- Effort: Modest. Schema is small. Rendering reuses existing MetricCard. Hardest part is COGS ingestion UI and historical cost lookup logic.

This is the most important proposed change. Without COGS, we're competing on attribution + UX with ThoughtMetric and we lose on price. With COGS, we're competing on attribution + organic + profit + EU positioning.

**B. Side-by-side Platform vs Real comparison view (NEW — small addition)**

The customer review I cited above ("a section where it shows you a side-by-side of what Facebook says vs what actually happened") validates that customers explicitly want this. We already compute Platform ROAS and Real ROAS separately. We need an explicit dedicated view where they sit side-by-side with the delta highlighted.

- Proposed: add `/analytics/discrepancy` route, or a permanent "Platform vs Real" card on the dashboard below the Real row.
- Effort: trivial. Data already exists.
- Phase: 1.5 or 1.6.

**C. Frequently-bought-together / market basket (CONSIDER, not commit)**

Multiple reviews ask for this. We have order_items and product data — we could compute basic basket lift in a nightly job. Not a priority but flag for Phase 2+.

**D. Server-side / CAPI conversion sync (CONSIDER, not commit)**

Cometly and Polar both pitch this. Sending verified purchase data back to Meta and Google CAPI would improve their algorithms and is becoming a baseline expectation. We have all the data. This is mostly an engineering project. Not Phase 1.5, but worth flagging as a strong Phase 2 candidate.

### Things to KEEP that research VALIDATES

- **Trust thesis (Platform ROAS vs Real ROAS, "Not Tracked")**: Validated. Customers of competitors are explicitly asking for this. Keep all current framing.
- **WooCommerce-native + organic-as-first-class**: Validated. None of the WC-supported competitors (ThoughtMetric, Cometly, Conjura) integrate GSC or treat organic as a peer to paid. This is a real gap we own.
- **EU/GDPR positioning**: Strongly validated. DPF instability makes this story stronger over the next 12 months, not weaker.
- **Anomaly detection / "why did it change"**: No competitor has cross-channel correlation with ordered investigation chains. Triple Whale Moby answers questions when asked. We're proposing automated narrative alerts. This is differentiated.
- **Native uptime monitoring (Option A, self-hosted Python probes)**: Validated. No competitor monitors uptime as a peer to revenue.
- **Tag generator**: Validated. Cometly and ThoughtMetric have UTM templates but no generator integrated with the workspace.
- **Source-tagged MetricCard primitive**: Strong UX advantage, no competitor has this level of provenance visibility.

### Things to RECONSIDER

- **Pricing.** Current Starter €29 is significantly underpriced relative to ThoughtMetric ($99) and Triple Whale Growth ($129). Polar starts at $510 for the BI module. The risk of underpricing is signalling lack of value. The opportunity is winning on price for the SMB segment that finds ThoughtMetric expensive.
  - Option A: Keep €29 Starter, add a clearer "no-frills" tone. Lean into "simpler than ThoughtMetric, half the price."
  - Option B: Raise Starter to €49 to signal value parity with ThoughtMetric while staying half-price. Keep Growth at €79.
  - Option C: Change pricing model to GMV-based across all tiers (matching Polar/Triple Whale norm).
  - **My recommendation**: Option B for now (€49 / €79 / €179 with Scale at 1% GMV). Defer the final decision until after first 5-10 beta customers.
  - **Flagged separately as requested.**

- **Phase 1.5 task: UTM coverage active onboarding modal.** Worth keeping — but reframe the modal copy to acknowledge the WC #62508 bug rather than imply user error.

- **Phase 1.5 task: BreakdownView migration on /countries.** Keep, and consider adding state-level breakdown via IP geolocation since multiple reviews mention country/state/city as a gap nobody fills.

### Things to CUT or DEFER

- **Multi-store comparison** (was Phase 2): defer. None of the competitor research suggests this is a top-5 pain.
- **Recap-as-separate-page**: already cut, validated.
- **TV Mode**: already cut, validated.
- **GA4 integration**: already declined, strongly validated.
- **Team message board**: already cut, validated.

---

## 5. Implications for CLAUDE.md — proposed changes

The current CLAUDE.md is too long and includes content that should live in PLANNING.md.

**Trim from CLAUDE.md and move to PLANNING.md:**
- Detailed schema explanations
- Full job dispatch table
- Full architectural patterns explanation

**Keep in CLAUDE.md:**
- Tech stack one-liner
- Codebase map (where things live, max 15 lines)
- Critical commands (Docker, Artisan, ngrok URL, npm scripts)
- Trust thesis (3-4 lines — it shapes every decision Claude makes about UI and metric naming)
- Gotchas section (the things Claude gets wrong most often on this codebase)
- "Prefer X over Y" rules, no "never" rules
- Branding (one line)
- Reference to PLANNING.md and PROGRESS.md by prose pointer

**Add to CLAUDE.md:**
- Specify default Claude model: `claude-sonnet-4-6` for AI summary feature
- Note: "Always check current Facebook Marketing API and Google Ads API versions before changing those connectors — they ship every 1-3 months."
- Note: "Never use @-imports; reference docs by prose path."

Target length: <150 lines. Current is ~248.

---

## 6. Implications for PROGRESS.md — proposed changes

Minor:
- Add Phase 1.6 (Profit & Positioning) after Phase 1.5, containing the COGS work and Platform vs Real comparison view.
- Note WC attribution bug #62508 as a known external issue affecting our UTM coverage metric.
- Refresh fact-checked items: FB Marketing API v25.0, Google Ads API v23+, Claude Sonnet 4.6 model string.

---

## 7. Things I'm explicitly NOT changing

- **The trust thesis itself.** Validated by customer reviews of competitors.
- **The decision to never integrate GA4.** Strengthened by research.
- **Native uptime monitoring as Option A (self-hosted Python probes).** Confirmed by user.
- **WooCommerce-first focus.** Validated — Polar isn't there yet, Triple Whale never will be, ThoughtMetric is the only direct WC competitor with attribution.
- **Anomaly detection design (median + weekday split, silent mode, graduation criteria).** No competitor has this level of rigour. Keep as planned. (Note: MAD / modified z-score dropped per simplification in section 4 — weekday-split median is sufficient.)

---

## 8. Outstanding uncertainties

- **Will customers pay for "show me the disagreement" or do they want "give me one number"?** Every competitor's marketing says "single source of truth." We're betting they're wrong about what users want. We won't know until we have 5-10 paying customers.
- **Pricing**: only validated by beta sales.
- **AI agent expectation**: by mid-2026, will customers expect autonomous action (Triple Whale Moby model) and not just narrative summaries? Unknown. We're betting on "narrative + recommendations" as a middle ground.
- **Profit-first positioning from Conjura**: are they winning customers in the WC space or just publishing content? No public metrics. Treat as competitive threat, not yet validated demand signal.

---

## 9. Addendum — corrected after follow-up discussion (April 2026, second pass)

This section supersedes anything earlier in the document that conflicts with it.

### Attribution: per-platform, no fallback we control

We do not run scripts on user sites. We are API-only until Phase 4 plugins. Each platform exposes different attribution data and the connector reports its capabilities.

**WooCommerce attribution chain:**
1. **PixelYourSite order meta** (if `pys_enrich_data` key present on order). Verified format below. Provides BOTH first-touch and last-touch in a single payload, parsed from pipe-delimited UTM strings.
2. **WooCommerce native `_wc_order_attribution_*` order meta** — last-touch only, session cookies only, has known bug GitHub #62508 and frequently misattributes email/social/ad traffic to "organic" because it sees the most recent referrer rather than the actual marketing touchpoint.
3. Heuristic from `_wc_order_attribution_referrer` URL field
4. "Not Tracked" with reason code

**PYS data format (verified from real order April 2026):**

PYS stores all attribution in a single serialized array under the meta key `pys_enrich_data`. Fields inside:
- `pys_landing` — first-touch landing URL
- `pys_source` — first-touch source string (e.g. `"google.com"`, `"android-app:"`, `"facebook.com"`)
- `pys_utm` — first-touch UTMs as pipe-delimited string: `"utm_source:Klaviyo|utm_medium:email|utm_campaign:SPRING25|utm_content:undefined|utm_term:undefined"`
- `pys_utm_id` — first-touch click IDs (fbadid/gadid/padid/bingid), same pipe-delimited format
- `last_pys_landing` / `last_pys_source` / `last_pys_utm` / `last_pys_utm_id` — last-touch equivalents
- `pys_browser_time` — `"09-10|Monday|March"` (hour|weekday|month)

Separate sibling meta keys also present when PYS is installed:
- `pys_fb_cookie` — `{fbc, fbp}` Facebook click/browser IDs (enables CAPI conversion sync in Phase 4 with no plugin needed)
- `pys_ga_cookie` — `{clientId, sessions}` Google measurement
- `pys_enrich_data_analytics` — `{orders_count, avg_order_value, ltv}` per-customer aggregates

**Parser must treat the literal string `"undefined"` as null** (PYS uses it in place of empty values).

**Real-world validation case from the verification order:**

A customer placed an order from a Klaviyo email campaign on Android. WC native recorded the order as `source_type: organic, utm_source: google, utm_medium: organic` because the most recent session referrer was `google.com`. PYS recorded the actual attribution: `pys_utm` showed `utm_source: Klaviyo, utm_medium: email, utm_campaign: SPRING25mail101...`. Both first-touch and last-touch in PYS confirmed Klaviyo as the real source.

**If we had used WC native, this customer's revenue would have been credited to organic search instead of email marketing.** A store running Klaviyo or any email tool gets systematic misattribution from WC native. PYS-first is therefore not a marginal improvement, it's a correctness requirement for any store running email marketing.

**Shopify attribution chain (significantly stronger than WC):**
1. `Order.customerJourneySummary.lastVisit.utmParameters` — multi-touch native, includes source/medium/campaign/content/term, plus full referrer and landing page
2. `Order.customerJourneySummary.firstVisit.utmParameters` — first-touch attribution, also native
3. `Order.landingPage` — query string parsing
4. `Order.referringSite` — heuristic
5. "Not Tracked"

Shopify is the easier platform to support for attribution. Multi-touch journey is built in. `customerJourneySummary` is the canonical field. WC requires more work.

**Implication for the connector interface**: `connector.supportedAttributionFeatures()` returns a list including `last_touch`, `first_touch`, `multi_touch_journey`, `referrer_url`, `landing_page`. The service layer queries the highest-priority feature each connector supports.

### COGS: per-platform via order meta (preferred) or daily snapshot (fallback)

**Verified facts from research:**

WooCommerce snapshots cost into order item meta at order creation. Three sources, all stored on the order item, not the product:
1. WooCommerce core COGS feature — accessible via `WC_Order_Item::get_cogs_value()`. Cost is locked to the order at creation.
2. WPFactory free plugin (very widely used) — `_alg_wc_cog_item_cost`, `_alg_wc_cog_item_profit`, `_alg_wc_cog_order_total_cost`, `_alg_wc_cog_order_profit`
3. WooCommerce.com Cost of Goods extension — `_wc_cog_*` keys

Shopify does NOT snapshot cost into orders. Verified across multiple Shopify community threads from 2018-2025. Only `InventoryItem.unitCost` exposes current cost. Shopify's own internal "Profit by SKU" report uses historical cost but the API does not expose it. Multiple developers have requested this for years.

**COGS architecture:**
- `connector.supportsHistoricalCogs(): bool` — true for WooCommerce (with any of the three sources), false for Shopify.
- For WooCommerce: read cost from order item meta during order sync, store on `order_items.unit_cost`. Free historical accuracy.
- For Shopify: snapshot `InventoryItem.unitCost` nightly into `daily_snapshot_products.unit_cost`. For each order, look up the snapshot from the order date. Pre-snapshot orders use current cost with a "historical estimate" badge.
- For stores with no COGS source at all: prompt to install Cost of Goods for WooCommerce (free) or upload CSV. A new `product_costs` table holds the manual data, layered on top of the existing snapshot/order-item logic.

The schema needs `daily_snapshot_products.unit_cost` after all, but as a Shopify fallback, not the default path.

### Platform-agnostic discipline (cross-cutting requirement)

Every new feature from Phase 1.6 onward must go through the `StoreConnector` abstraction. Hard rule. The test for the abstraction is whether adding Shopify in Phase 2 means "implement a new connector class" or "rewrite half the codebase." If it's the latter, the abstraction failed.

Specific requirements:
- Normalised domain objects (`NormalisedOrder`, `NormalisedProduct`, `NormalisedRefund`, `NormalisedAttribution`) are the only data shapes that reach the service layer. Platform-specific raw data goes into a `platform_data JSONB` escape hatch on each table.
- Connectors report capabilities via a typed `supportedFeatures()` method. UI hides features the current platform does not support.
- All UI labels are platform-neutral. The sidebar says "Store" not "WooCommerce". Platform-specific labels come from a `platforms` config file.
- No direct WooCommerce REST API calls anywhere outside the WooCommerceConnector class. Same for Shopify when it lands.
- Webhook handlers normalise platform-specific payloads into uniform internal events before any service layer touches them.

### Dashboard design — copy what works in competitors, fix what doesn't

Synthesised from Conjura, Triple Whale, Polar, ROASMonster reviews and customer feedback in this round.

**Copy these patterns (consistent positive feedback):**
- Product images on every product row (Conjura reviewers explicitly cite this)
- Saved filtered views per user (every BreakdownView gets named-view persistence in `users.view_preferences`)
- Last-updated timestamp visible on every screen (small, corner — "Updated 14 minutes ago")
- Period comparison built into every metric card (current + previous + delta + sparkline) — ROASMonster does this religiously and it works
- Action language not metric language ("ROAS held at 1.8x — below your 2.5x target. See campaigns ↓" instead of "Real ROAS: 1.8x")
- Order-level drill-down with full enrichment
- Customer table with filtering (LTV, channel, segment) — Phase 2+ work

**Fix these pain points (consistent negative feedback across competitors):**
- "Too many metrics, overwhelming" — keep dashboard hero to 3-4 numbers max. Everything else is a drill-down.
- "Slow to load on large data" — use `daily_snapshots`, never aggregate raw orders in page requests. Already a discipline in PLANNING.md.
- "Hard to understand ratios" — every metric card has a "Why this number?" affordance showing the formula and source.
- "Data discrepancy with no explanation" — the discrepancy IS the explanation. Platform-vs-Real drill-down at `/analytics/discrepancy`.
- "Setup takes forever" — every onboarding step has a skip button. Trial works with zero integrations.
- "Confusing pricing tiers" — flat tier structure with auto-assignment based on measured volume (already in plan).

**Dashboard structure (final):**
1. **Hero row** — 3-4 big numbers: Revenue, Orders, Real ROAS or Real CPO, Real Profit (when COGS configured). One number each, with delta + sparkline. No badges by default.
2. **Recommendations card** — 3-5 actionable items at the top of the page, expandable "show all" toggle.
3. **Channel rows** — Store / Paid / Organic / Site Health, collapsed by default, gated on integration flags.
4. **Multi-series chart** — at the bottom, with event overlays (holidays, workspace events, daily notes — all scope-filtered).
5. **Last updated stamp** — top-right corner.

The "Why this number?" affordance is on every MetricCard. Click → modal showing source, formula, and any conflicting platform values. This is how the trust thesis survives without overwhelming the simple user.

### Scope filtering (cross-cutting, every analytics view)

Every analytics page must be filterable by `(store, integration, date_range)` with sensible defaults. This is a third axis on top of BreakdownView's existing `breakdownBy` and `cardData`.

Implementation:
- A sticky scope selector at the top of every analytics page. Default = all stores, all integrations.
- Selected scope persists in URL for sharing and in `view_preferences` for the user.
- Every metric query accepts `(workspace_id, store_ids?, integration_ids?, date_range)`.
- Charts: when scope = "compare Store A vs Store B", the chart shows two series. When scope = "all", one series with totals. Chart component handles series logic.

This is a hard requirement, not a nice-to-have. Every Phase 1.6+ feature must respect it.

### Daily notes and workspace events: optional per-store / per-integration scope

Currently `daily_notes` are workspace-scoped. Schema change:
```
daily_notes:
  + scope_type ENUM('workspace', 'store', 'integration') DEFAULT 'workspace'
  + scope_id BIGINT NULL  -- null when scope_type = 'workspace'
```
Same for `workspace_events`.

Rendering: a note appears as an event marker on the chart only when the current scope filter matches its scope. Cross-scope visibility (workspace-scoped notes always visible) is the default; opt-in per-store scoping for things like "Store A had a flash sale on day X."

### Anomaly detection — simplified version

Stripped down to the minimum that still works:

1. **Baselines** — per metric, median of the last 28 days split by weekday. Stored in `metric_baselines`.
2. **Detection** — flag when current value differs from weekday median by more than a configurable percentage (default 30% drop, 50% rise). Volume floor: skip metrics under €500/day revenue or 15 orders/day.
3. **Single-cause correlation** — for each flagged candidate, check 4-5 obvious causes in order: spend changed → attribution changed → site health degraded → stock outage → refund spike. Pick the first that matches as the narrative. If none match, "we don't know why — investigate."
4. **Silent mode graduation** — `is_silent = true` by default. Founder reviews and tags as TP/FP/unclear. Alert type graduates to user-visible after ≥20 reviewed and ≥70% TP rate.

What we dropped from the original complex version: MAD, modified z-score, 90-day baselines, multi-cause composite alerts. All of those are easy to add later if the simple version proves insufficient. They are not needed at launch.

This fits in 1 page of PLANNING.md.

### Pricing — final proposal at market-floor

Previous drafts (€19/€49/€79 and €39/€89/€149) were both underpriced. Real market floor for a product doing attribution + organic + site + profit is between ThoughtMetric ($99 / ~€92, attribution only) and Conjura (~€395 entry, profit-first). We do more than ThoughtMetric and less than Conjura. We should price between them.

**Final proposal — 3 tiers + Enterprise:**

| Tier | Target | Price | Volume cap |
|---|---|---|---|
| Trial | Everyone | €0 | 14 days, full Growth features |
| **Starter** | Small ecom, first ad campaigns | **€119/mo** | 1 store, ≤€20k GMV OR ≤€5k ad spend |
| **Growth** | Mid-market ecom, serious paid + organic | **€249/mo** | ≤€80k GMV OR ≤€20k ad spend |
| **Scale** | Brands above €80k GMV | **0.55% of GMV + 1% of ad spend, min €499/mo** | uncapped |
| **Enterprise** | Multi-store, agencies, custom needs | custom, ~€1500+/mo | custom |

Annual discount: 20% off Starter and Growth (Stripe metered limitation prevents annual on Scale).

Auto-tier assignment after trial based on measured volume from previous month. Customer can manually downgrade if their volume drops.

**Reasoning:**
- €119 Starter is a 30% premium over ThoughtMetric ($99). Below that we look like a side project; above €149 we lose the "easier than Triple Whale" pricing story.
- €249 Growth is roughly 2x Starter (clean upgrade math) and below Triple Whale's serious tier.
- Scale at €499 minimum with blended 0.55%/1% pricing — a €100k GMV / €25k ad spend store pays €800/mo. Same store on Triple Whale would be ~€1,400; on Conjura ~€800-1,000. We're cheaper than Conjura for similar features.

**Discount strategy** (never on the public price page):
- Founder discount: 50% off year 1 for first 20 customers, in exchange for direct feedback access.
- Case study discount: 30% off for 6 months in exchange for a public case study.
- Agency referral: 10% recurring on referred customers.

**Revenue math at typical mix:**
- 30 customers (15 Starter + 12 Growth + 3 Scale) ≈ €7.2k MRR / €86k ARR
- 100 customers (50 Starter + 35 Growth + 12 Scale + 3 Enterprise) ≈ €25k MRR / €300k ARR

**Tier names**: Starter / Growth / Scale / Enterprise. Boring, clear, what every successful SaaS converges on.

**Site tier dropped.** Content-site customers are a different ICP with different competitors (Plausible, Fathom, Simple Analytics) who own that segment on price and simplicity. Trying to serve "ecom AND content sites" dilutes positioning. Pick one ICP: WooCommerce or Shopify SMB doing €5k-€500k monthly GMV. Content sites are out of scope. If demand emerges later, add a Lite tier in 6-12 months. Three tiers + Enterprise is the cleanest possible structure.

Final pricing validated by 5-10 beta customer conversations. Subject to revision based on real conversion data — but the floor (€119) is committed.

### Recommendations layer (Phase 3 — moved from Phase 2)

Beyond anomaly detection (which explains the past), Phase 3 adds a Recommendations layer (which suggests the future). Each recommendation joins multiple data sources nightly and produces a narrative card with an impact estimate and a suggested action.

Examples (all use data we already collect):
- **Organic-to-paid substitution**: detect keywords where the workspace ranks top 5 organically AND pays for Google Ads on the same keyword — suggest reducing the bid (cannibalisation).
- **GSC product opportunity**: product pages with rising GSC impressions but no corresponding ads — suggest a bid.
- **Site health revenue impact**: compute conversion rate sensitivity to LCP from historical data, translate Lighthouse drops into euro-impact estimates.
- **Stock-aware campaign alerts**: flag campaigns spending on out-of-stock products.
- **Cohort × channel quality**: real CAC payback adjusted for LTV differences by channel.
- **Basket bundling** (combined with FBT in Phase 2): "product X and Y co-occur in 18% of orders, Y has 2.3x margin, bundling could lift profit per order by €4.20."

Stored in `recommendations` table, surfaced on the dashboard as a card. No artificial limit on count — show all current recommendations, sorted by impact, with collapse/expand and dismiss/snooze. No auto-execution — we suggest, users decide.

### Phase reorganisation (final)

- **Phase 1.5** — Foundation Cleanup (audit fixes, no new features)
- **Phase 1.6** — Profit & Positioning + FBT (COGS via WC order meta, Platform-vs-Real drill-down, scope filtering everywhere, scoped daily notes/events, monthly reports, dashboard design principles, **frequently-bought-together with margin lift** — tested on WooCommerce first to validate the algorithm before Shopify)
- **Phase 2** — Platform expansion (Shopify connector with COGS via daily snapshot fallback, multi-platform feature parity, FBT extends to cross-platform)
- **Phase 3** — Intelligence (simplified anomaly detection, recommendations layer, basic alerts, silent mode validation, intelligent narratives)
- **Phase 4** — Advanced / Plugins (CAPI conversion sync, native uptime monitoring with self-hosted probes, our own WooCommerce plugin for richer data, agency white-label, Playwright synthetic checkout, ML seasonality)

Phase 1.9 is deleted. Reports absorbed into 1.6. Uptime moves to Phase 4 (requires self-hosted infrastructure, not blocking).

### What's NOT in any phase

- GA4 integration — never. Validated by trust thesis and EU legal status research.
- Multi-store comparison within a workspace — no demand signal.
- Recap-as-separate-page — already cut.
- TV Mode — already cut.
- Per-page report builder — competing with AgencyAnalytics is not our segment.
- Custom SQL access — competing with Polar is not our segment.

### Outstanding items requiring user decisions

1. **Pricing**: €119 / €249 / €499 min Scale / Enterprise — confirm or push back. This is committed market-floor pricing, no longer a guess.
2. **Anomaly detection simplified version**: 28-day median + % threshold + single-cause — confirm sufficient.
3. **Default Claude model**: Sonnet 4.6 (`claude-sonnet-4-6`) confirmed for narratives.
4. **Phase order**: 1.5 → 1.6 → 2 (Shopify) → 3 (Intelligence) → 4 (Plugins/Uptime/CAPI/Advanced) — confirm.
5. **Dropping Site tier and content-site ICP entirely**: confirm we focus only on ecom WooCommerce and Shopify customers.

PYS meta key format is now verified from real order data — no longer outstanding.

---

## 10. Notes for future research passes

When re-running a research pass (Phase 3 or later), re-verify:

- Facebook Marketing API current version (cadence: ~3-4 months)
- Google Ads API current version (monthly minor versions as of Jan 2026)
- Anthropic Claude model strings and pricing
- Competitor feature changes — especially Conjura (closest WC profit-first competitor), ThoughtMetric (closest WC attribution competitor), Polar Analytics (if WC connector ships)
- EU DPF status (CJEU review expected 2026)
- WooCommerce native attribution bug GitHub #62508 (still open as of April 2026)
- PixelYourSite meta format (verify nothing has changed in the plugin between now and then)
- WooCommerce COGS plugin ecosystem (WPFactory, WooCommerce.com Cost of Goods) — verify meta key formats haven't changed
