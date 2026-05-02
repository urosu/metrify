# User Flow / Funnel Visualization Research

Research conducted 2026-04-30.

---

## How competitors handle funnels

| Tool | Visualization | Segmentation | Landing page → product? |
|------|--------------|-------------|------------------------|
| GA4 | Vertical bars with step-by-step drop-off count and % | Segment comparison (e.g., Facebook vs Google side-by-side) | No direct link |
| Mixpanel | Vertical bar chart, conversion % above bars, absolute drop-off between | Group-by for segmentation (source, device, campaign) | No |
| Amplitude | Horizontal bars + Sankey-style "Journeys" for free-path | Rich segmentation | No |
| Shopify Native | Basic horizontal funnel: sessions → ATC → checkout → purchase | None — no segmentation, no landing page drill | No |
| Heap | Stepped bar with "group by" overlay (UTM source, device) | Click step → see actual pages/events | Partial |
| Triple Whale | "Product Journeys" — which products drive acquisition through to conversion | Paid traffic source | Closest to what we want |

### Key gap

**No tool cleanly shows: "Ad campaign X → landing page Y → product Z viewed → purchased/abandoned" as a single flow.** This is a genuine whitespace.

---

## Recommended Nexstage implementation

### Funnel page (or section within /products or /ads)

**Primary visualization: Horizontal bar funnel** (Plausible-style)

5 steps:
1. **Landing page visit** (from ad / organic / email)
2. **Product page view** (which product did they look at?)
3. **Add to cart**
4. **Checkout initiated**
5. **Purchase completed**

Each step: proportionally-wide horizontal bar with:
- Absolute count
- Drop-off count and % between steps
- Conversion rate from previous step

**Why horizontal bars beat Sankey:** Horizontal bars require zero graph literacy and immediately show the biggest leak. Sankey is better for free-path/branching analysis but requires clickstream pixel data (not available without first-party tracking at MVP).

### Source filter chips above funnel

Segment by channel: All / Facebook / Google / TikTok / Organic / Email / Direct

Shows: "Facebook traffic converts at 2.1% but drops 68% at checkout, while Google converts at 3.4% with only 42% checkout drop-off"

### Click-to-drill on each step

Clicking any step opens a right-side drawer showing the breakdown:
- **Landing page step:** Top 10 landing pages by volume
- **Product view step:** Top products viewed with their cart rate
- **Add to cart step:** Products in cart with purchase rate
- **Checkout step:** Abandonment reasons (if available)
- **Purchase step:** AOV, new vs returning

### Winner product identification

Flag products with:
- High view rate but low cart rate → "possible price resistance" or "poor product page"
- High cart rate but low purchase rate → "checkout friction" or "shipping cost shock"
- High purchase rate from specific ad source → "winner product for [channel]"

---

## Sources

- GA4 Funnel Exploration (support.google.com/analytics)
- Mixpanel Funnels (mixpanel.com/funnels)
- Amplitude Journeys (amplitude.com)
- Plausible top pages (plausible.io/docs)
- Shopify Analytics (help.shopify.com)
- Triple Whale Product Journeys (triplewhale.com/analytics)
