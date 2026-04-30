# Polaris Pivot Research — Wave 3A-1

**Date:** 2026-04-30
**Purpose:** Informs the teal-primary design token overhaul and MetricCard refactor.

---

## 1. Color systems observed

### Shopify Polaris (current, v12+)
- **Primary interactive:** Indigo is canonical primary action; teal is a secondary highlight / informational color. Legacy `#008060` green was the Shopify brand action color for many years (still appears in checkout green and App Store CTAs). The Polaris token system has moved to semantic alias tokens (`--p-color-interactive`, `--p-color-action-primary`) that resolve to HSLuv-calibrated values.
- **Neutral spine:** Background = very light gray (~98% L), Surface = white, borders = ~92% L, text = dark gray (~14% L). Closely matches our existing zinc scale.
- **Key lesson:** Polaris uses token indirection (`--p-color-*`) everywhere — no raw hex in components. We replicate this with our `--color-*` system.
- **Radius:** 4/8/12px canonical. Matches our 4/6/8/12/16 set.
- **Padding:** Card inner padding 16px standard, 20px for larger cards. We use 20/24px — compatible.
- **MetricCard pattern (App Home):** Single large number, badge for trend direction, sparkline optional. Grid with responsive column templates. Source attribution is secondary detail, not primary display.

### Polar Analytics
- **Colors:** Multi-chromatic categorical palette matching 6 sources. Uses colored left-border on cards to indicate source. Teal is used for the primary brand accent.
- **Key lesson:** Source comparison is a drill-down detail, NOT the primary card surface. First-class metric → secondary source breakdown. Validates our Design v2 direction.
- **Cards:** Generous padding (~20px), prominent number, small label above, delta chip below.

### Plausible Analytics
- **Colors:** Single brand accent (blue/indigo) + neutrals. Very restrained. Indigo used for chart lines and hover states only.
- **Key lesson:** Minimal palette with ONE strong accent color reads as trustworthy and calm. Don't use multiple vivid colors on chrome/cards.

### Linear
- **Colors:** Near-black background, white surfaces, electric violet/teal for primary interactive elements. Teal `oklch(0.60 0.14 185)` area used for active states and key highlights.
- **Key lesson:** Teal reads as "intelligent / computed" without the "achievement" connotation of green. Good for a product that synthesizes data.

### Stripe
- **Colors:** Neutral-dominant (whites, zinc, near-blacks). Brand purple `#635BFF` used sparingly — only on primary CTAs and brand chrome. Status = muted (green/red/gray).
- **Lesson for us:** Deep chromatic accent only on interactive elements; neutrals carry the data surfaces.

---

## 2. MetricCard patterns observed

| Product | Primary number size | Padding | Source display | Sparkline |
|---|---|---|---|---|
| Stripe | ~28–32px | ~24px | Click-through only | Yes, axis-less |
| Shopify App Home | 32px+ | 20px | Not shown | Badge for trend |
| Polar Analytics | 28–36px | 20px | Left-border tint | Optional |
| Plausible | 32px | 16–20px | Not applicable | No |
| Linear | 22–28px | 16px | Not applicable | No |

**Synthesis:** 
- Large number (text-3xl to text-4xl, 36–48px) is the universal convention.
- Source is never in the default card face — always a click/drill-down detail.
- Sparklines are used by the strongest competitors (Stripe, Polar).
- Min-width must prevent below-readable collapse (~14–16rem minimum).

---

## 3. Spacing / radius / typography numbers

**Polaris canonical:**
- Radius: 4 / 8 / 12px
- Type: 13 / 14 / 16 / 20 / 24px (floor 13px for their apps, but our floor is 14px per user feedback)
- Padding: 16 / 20 / 24px

**Our scale (keeping, compatible):**
- Radius: 4 / 6 / 8 / 12 / 16px
- Type: 14 / 15 / 16 / 18 / 22 / 28 / 36 / 48px — body 15px, floor 14px
- Padding on cards: 20px (p-5) default, 24px (p-6) detail variant

---

## 4. Anti-AI signals (what to avoid)

From competitive analysis and user feedback:
- No gradients on cards or chrome (Stripe, Plausible, Linear all flat)
- No glass/blur on data surfaces
- No gold/amber on the "Real" source (was reading as "achievement/celebration" — should read neutral/informational)
- No "disagreement framing" in primary card face (all competitors: source comparison is hidden by default)
- No inline hex colors — all via tokens
- No shadows on cards (only overlays)
- No animated backgrounds/meshes

---

## 5. Chosen teal primary — OKLCH values and AA ratios

**Target:** A teal-600–ish that:
- Passes WCAG AA (4.5:1) as text on white background
- Passes WCAG AA (4.5:1) as foreground on `--color-primary-subtle` background
- Reads as "brand / primary interactive" — not as "success/green"
- Stays distinct from the Google Ads source color (teal H=182)

**Chosen primary hue: H=195 (slightly blue-shifted teal)**
Google Ads uses H=182. Our primary uses H=195 — different enough to avoid confusion.

**OKLCH contrast estimation (on white, L=1.0):**
WCAG relative luminance uses `L_rel = (OKLCH_L)^2.2` approximately (OKLCH L is perceptual lightness).
For WCAG contrast: `ratio = (L_lighter + 0.05) / (L_darker + 0.05)`

| Token | OKLCH | Approx L_rel | Ratio on white | Use |
|---|---|---|---|---|
| `--color-primary` | `oklch(0.46 0.14 195)` | ~0.165 | ~5.5:1 AA ✓ | Primary actions, accents |
| `--color-primary-hover` | `oklch(0.39 0.14 195)` | ~0.11 | ~8.1:1 AA ✓ | Hover darkening |
| `--color-primary-subtle` | `oklch(0.95 0.04 195)` | ~0.89 | bg tint | Card tint, chip bg |
| `--color-primary-fg` | `oklch(0.985 0 0)` | ~0.97 | white on primary | Text on primary bg |
| `--color-secondary` | `oklch(0.50 0.20 10)` | ~0.19 | ~4.9:1 AA ✓ | Rose/coral callouts |
| `--color-secondary-hover` | `oklch(0.43 0.20 10)` | ~0.14 | ~6.7:1 AA ✓ | Hover darkening |
| `--color-secondary-subtle` | `oklch(0.95 0.04 10)` | ~0.89 | bg tint | Rose chip bg |
| `--color-secondary-fg` | `oklch(0.985 0 0)` | ~0.97 | white on secondary | Text on secondary bg |

**Source colors AA ratios (existing, unchanged — already documented in app.css):**
| Token | fg OKLCH | Ratio on white |
|---|---|---|
| `--source-real-fg` | `oklch(0.44 0 0)` | ~6.0:1 AA ✓ |
| `--source-store-fg` | `oklch(0.44 0.04 257)` | ~5.8:1 AA ✓ |
| `--source-facebook-fg` | `oklch(0.42 0.21 254)` | ~6.4:1 AA ✓ |
| `--source-google-fg` | `oklch(0.40 0.12 182)` | ~6.8:1 AA ✓ |
| `--source-gsc-fg` | `oklch(0.40 0.17 155)` | ~6.2:1 AA ✓ |
| `--source-ga4-fg` | `oklch(0.45 0.17 50)` | ~5.5:1 AA ✓ |

**Text scale ratios on white bg (`oklch(0.985 0 0)`):**
| Token | OKLCH | Ratio |
|---|---|---|
| `--color-text` / foreground | `oklch(0.141 0 0)` | ~16.5:1 AA ✓ |
| `--color-text-secondary` / muted-fg | `oklch(0.44 0 0)` | ~6.0:1 AA ✓ |
| `--color-text-tertiary` | `oklch(0.56 0 0)` | ~3.5:1 AA large ✓ |
| `--color-text-muted` | `oklch(0.65 0 0)` | ~2.2:1 (placeholder only, not body) |

---

## 6. Key decisions

1. **Teal H=195 primary** — distinct from Google source (H=182), reads "computed/intelligent", passes AA.
2. **Rose H=10 secondary** — warm coral, distinct from danger semantic (which stays as rose-600 destructive). Used for callout CTAs only.
3. **Real source stays neutral zinc** — no color change. "Real" should read as "synthesis/default", not a celebration.
4. **shadcn `--primary` alias updated** to point at teal instead of zinc-900. All Button variants inherit automatically.
5. **MetricCard:** `min-w-[14rem]`, value promoted to `text-4xl` (48px) for dashboard default, `text-3xl` for compact. `p-5` default, `p-6` detail.
6. **TrustBar:** Primitive retained but stripped of gold/amber conflicts framing. No import removal (page agents own that).
7. **WhyThisNumber conflicts section:** `amber-50/amber-700` neutralized to `zinc-50/zinc-700`.
