# UTM Link Builder & Ad Naming Convention Builder Research

Research conducted 2026-04-30.

---

## 1. UTM Link Builders

### Existing tools

| Tool | Type | Key feature | Price |
|------|------|-------------|-------|
| Google Campaign URL Builder | Free web form | Bare-bones, no presets, no validation | Free |
| UTM.io | SaaS | Convention templates with locked dropdown values, auto-suggest from history, Chrome extension | Paid |
| Terminus / Rebrandly | SaaS | UTM builder + link shortening + click analytics | Paid |
| Triple Whale | In-product | UTM builder inside attribution setup wizard, auto-generates templates per ad platform with dynamic macros | Bundled |

### Ecommerce UTM best practices (2025-2026)

```
utm_source:   platform name lowercase (facebook, google, tiktok, email, sms)
utm_medium:   traffic type (paid-social, paid-search, cpc, email, influencer)
utm_campaign: structured string (tof_broad_video_spring24)
utm_content:  ad-level identifier (ugc-testimonial-v2)
utm_term:     keyword / audience (broad_25-55)
```

### Platform dynamic macros (auto-populate from ad platform)

- **Facebook:** `{{campaign.name}}`, `{{adset.name}}`, `{{ad.name}}`, `{{campaign.id}}`
- **Google:** `{campaignid}`, `{adgroupid}`, `{creative}`, `{keyword}`
- **TikTok:** `__CAMPAIGN_NAME__`, `__AID_NAME__`, `__CID_NAME__`

### Nexstage UTM Builder UI

- Simple form with dropdowns per field
- Pre-filled presets per platform (Facebook, Google, TikTok) with dynamic macro templates
- "Copy URL" button + bulk generator for multiple variants
- Saved templates per channel
- Validation: warn if utm_source doesn't match connected ad accounts

---

## 2. Ad Naming Convention Builder (CRITICAL for attribution)

### Why it matters

If campaign/adset/ad names follow a parseable structure (delimited by `|`, `_`, or `-`), the analytics tool can extract dimensions (funnel stage, audience, creative type, product, country) without manual tagging. This is how we connect ads to metrics like country-level ROAS, product-level attribution, etc.

### Standard naming pattern (pipe-delimited, 2025-2026 norm)

```
Campaign:  {Country} | {Funnel} | {Objective} | {Audience}
Ad Set:    {Targeting} | {Placement} | {Bid Strategy}
Ad:        {Creative Type} | {Hook Variant} | {Product} | {Date}
```

Example: `SI | TOF | Conversions | Broad` / `Interest_Fitness_25-45` / `UGC | Hook-A | Serum_30ml | 2026-04`

### How competitors handle parsing

| Competitor | Approach |
|-----------|----------|
| **Motion** | Gold standard. Dedicated Naming Convention Builder UI: pick delimiter, drag/drop dimension slots, preview against live ads, flags non-compliant ads. Parsed dimensions become filters/groupings. |
| **Triple Whale** | Naming convention parser in settings. User defines delimiter + position mapping (position 1 = funnel stage, etc.). |
| **Northbeam** | Delimiter-based parser + regex support. Parsed dimensions surface in creative analytics and ROAS views. |
| **Polar Analytics** | Naming convention setup page with position-to-dimension mapping; auto-tags historical ads. |

### Nexstage Naming Convention Builder UI (recommended)

1. **Choose delimiter** — `|`, `_`, `-`, or custom
2. **Define slots** — numbered list or drag-and-drop: "Position 1 = Country", "Position 2 = Funnel Stage (TOF/MOF/BOF)", "Position 3 = Audience Type", etc.
3. **Live preview** — pull real campaign/adset/ad names from connected ad accounts and show parsed output in a table
4. **Validation** — highlight ads that don't match the convention (red/yellow), show compliance percentage ("87% of spend is on properly-named ads")
5. **Suggested values** — dropdown of detected unique values per dimension
6. **Template export** — generate naming templates users can paste into their ad platform

### Key insight

The builder itself is simple (delimiter + position mapping). The real value is the **live preview against actual ad names** + **compliance dashboard** showing what percentage of spend is on properly-named ads. The parsed dimensions then power creative reporting, funnel analysis, country-level ROAS, and product attribution without any manual tagging.

---

## Sources

- Motion naming convention builder (motionapp.com/solutions/creative-reporting-tool)
- Triple Whale naming conventions (kb.triplewhale.com)
- Northbeam breakdowns manager (docs.northbeam.io)
- UTM.io convention templates (utm.io)
- Google Campaign URL Builder (ga-dev-tools.google/ga4/campaign-url-builder)
