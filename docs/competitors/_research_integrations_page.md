# Integrations Page Research

Researched: 2026-04-30. Scope: tracking-health gauge UX, connection card composition, OAuth re-auth flow, error directory presentation, sync activity feed, missing-data warnings UX.

## Sources consulted

- Elevar docs: Channel Accuracy Report, Server Event Logs, Error Code Directory (docs.getelevar.com)
- Vercel: integrations marketplace, Entity card pattern, Status Dot (vercel.com/docs/integrations, vercel.com/geist)
- Shopify Polaris: account connection pattern (shopify.dev/docs/api/app-home/patterns/compositions/account-connection)
- Klaviyo: integrations tab, integration health status (help.klaviyo.com/hc/en-us/articles/16741902158491)
- Segment: Warehouse Health dashboard, data-health-dashboard recipe (segment.com/docs, segment.com/recipes)
- Stripe Connect: OAuth connection flow, Standard/Express account UI (docs.stripe.com/connect)

---

## 1. Tracking-health gauge UX

**Pattern:** Elevar Channel Accuracy Report (canonical reference)

- Single composite % per channel (e.g. "Meta CAPI 99.3%"). Rolling window 7d / 30d with trend sparkline.
- Accuracy alerts: configurable threshold (notify if < 95%); email fires on breach.
- Accuracy = (orders successfully delivered to destination) / (Shopify canonical order count) × 100.
- UI: a dominant numeric score + letter grade + mini breakdown by dimension is the cleanest encoding.
- Score breakdown dimensions (weighted): order attribution coverage (30%), ad conversion data (25%), UTM coverage (20%), sync recency (15%), tag coverage (10%).
- Gauge arc (SVG circle) with color shift: green ≥ 90, amber 75–89, red < 75.
- Competitor gap: Elevar does per-channel %; Nexstage extends to composite across all six sources.

**Segment Warehouse Health:**
- Shows rows-synced trend over time in a simple line chart; color-coded status per warehouse connection.
- Per-table row counts + last-sync timestamp; error rows surfaced inline below the trend.
- Takeaway: an "events over time" mini-chart per connection is expected by power users.

**Klaviyo integrations tab:**
- Lists connected integrations with: logo, name, connected status pill (Connected / Disconnected / Error), last-event timestamp, event count (7d).
- "View logs" link per integration → chronological event log with filter by event type and status.
- Takeaway: event count + last-event timestamp are the two minimum signals a healthy card must show.

---

## 2. Connection card composition

**Vercel Entity pattern (canonical):**
- Icon (32–40px) + primary text (name) + secondary metadata (account/email) + StatusDot + trailing action menu (···).
- Status states: healthy (green), building (amber pulsing), error (red), inactive (grey).
- Cards are uniform height in a grid; no nested modals — row click → right-side drawer.
- Takeaway: consistent card height via min-h + flex-col + mt-auto on footer prevents ragged grid.

**Shopify Polaris account-connection pattern:**
- Account avatar (square, rounded, 40px) + name + connection detail ("Connected as email@domain.com") + disconnect button.
- Warning strip inside the card (not a modal) for expiring tokens.
- Primary CTA ("Connect") is teal/green prominent button; disconnect is de-emphasized text or destructive variant hidden behind ··· menu.
- Takeaway: Polaris prefers inline warning strip over modal for token-expiry; the card self-contains the reconnect affordance.

**Elevar per-check health dots:**
- Under the card header: a list of named checks (Pixel firing, CAPI sending, Match rate, Token valid) each with a green check or red ×.
- Failing checks show a plain-English note inline (not a tooltip): "Server-side match rate 62% — recommend ≥ 80%".
- Takeaway: 3–5 named checks per card is the sweet spot; more than 6 creates visual noise.

**GA4-specific:**
- GA4 must show "Connect GA4" as a primary CTA when not_connected; labelled "GA4" (not "Google Analytics", not "Site").
- Connected state shows property ID + measurement ID (G-XXXXXXX) as account_info.
- Health checks: property accessible, purchase event firing, session_start event, data stream active.

---

## 3. OAuth re-auth flow

**Stripe Connect Standard OAuth (canonical pattern):**
- Connection URL generated server-side → redirect → platform OAuth page → callback with code → token exchange.
- State parameter (CSRF protection) stored in session or cache; validated on callback.
- Pending pickers pattern (Nexstage already uses): cache key returned as query param after OAuth; frontend reads items from cache to let user select ad account / property.
- Re-auth (token refresh): same flow; existing connection record updated; banner clears on next SWR revalidate.
- Takeaway: OAuth popup (centered, ~600×700px) preferred over full-page redirect for perceived speed; falls back to redirect if popup blocked.

**Elevar reconnect flow:**
- Token-expired banner appears inline in the integration card AND in a global alert strip.
- Clicking "Reconnect" anywhere triggers the OAuth redirect.
- Post-reconnect, the card status dot flips green within one polling cycle (no manual refresh needed).
- Takeaway: the reconnect affordance must be present both in the global banner AND in the card footer to reduce time-to-fix.

---

## 4. Error directory presentation

**Elevar Error Code Directory (canonical):**
- Columns: Code (platform-native, monospace) | Destination (SourceBadge) | Event | Count (7d) | Last seen | Explanation (plain English) | Fix it (action button).
- Error codes organised by platform: Meta #100-series, Google 4xx, Klaviyo-native codes.
- Plain-English explanations are the moat: "Missing user_data.em — customer email hash did not reach CAPI. Verify checkout extensibility installs the pixel on thank-you page."
- Row click → drawer showing last 5 raw JSON payloads sent to that platform for that error (monospace, copy button).
- Filter strip above table: Destination = any | Window = 7d / 30d | Status = any.

**Segment data-health-dashboard recipe:**
- Surfaces schema violations as error rows: event name | property | expected type | actual type | count | first seen | last seen.
- "Fix it" guidance links to documentation specific to the violation type.
- Takeaway: pairing every error code with a remediation link (or inline text) reduces support burden significantly.

---

## 5. Sync activity feed

**Vercel deployment list (analog):**
- Chronological list, newest-first. Columns: time (relative + absolute on hover) | source | action | records | errors | duration | status dot.
- Row click → right-side drawer with: request/response headers, payload JSON, full log tail, copy button.
- Filter by source and status above the list. Pagination or "load more" at 50 rows.
- "Last 50 events" is the right default window — enough to diagnose a pattern without overwhelming.

**Klaviyo event logs:**
- Streaming log with pause toggle. Rows include: timestamp | integration name | event type | status pill (delivered / failed) | entity ID.
- Pause toggle freezes the stream; "jump to latest" pill appears when paused and new events arrive.
- Takeaway: for the sync feed, a static last-50 table is v1; live streaming is v2.

---

## 6. Missing-data warnings UX

**Elevar approach:**
- Accuracy alerts fire as email + in-product banner when a channel drops below a configurable threshold.
- In-product: global alert strip at top of page + inline warning strip inside the affected integration card.
- Alert copy is specific: "Meta CAPI match rate dropped to 62% (threshold: 80%). Last good: Apr 27. Affected orders: ~14/day."
- Dismissable at info severity; warning and critical stay until resolved.

**Shopify Polaris:**
- AlertBanner (warning/critical) is non-dismissable by default for broken connections.
- Info banners (e.g. GSC 48h lag) are dismissable per-session.
- Stacking order: critical → warning → info.

**Klaviyo:**
- Missing-data state for an integration renders the card in a "Needs attention" visual variant (amber border, amber status dot, inline explanation).
- "No events received in 24h" is a standard threshold; "No events in 7 days" triggers card-level error state.
- Takeaway: the card itself (via status dot + inline check list) is the primary missing-data signal; the global banner is secondary.

---

## Key design decisions for Nexstage /integrations rebuild

1. **Tracking Health gauge dominant above the fold** — composite 0–100 score, grade letter, 30d sparkline, 5-dimension weighted breakdown. Elevar Channel Accuracy Report is the reference.
2. **6 connection cards in 3-col grid** — WooCommerce, Shopify, Facebook Ads, Google Ads, GSC, GA4. Vercel Entity pattern: logo + name + StatusDot + per-check health list + sparkline + action menu (···). GA4 labelled "GA4", not "Site".
3. **Sync activity feed** — last 50 events, DataTable. Row click → DrawerSidePanel with JSON payload (Vercel deployment detail analog). In-page filters: by integration, status, time-range.
4. **Error code directory** — Elevar pattern: code | destination | event | count | last seen | explanation. Filter strip above. Row click → drawer with last 5 raw payloads.
5. **Missing-data warnings** — AlertBanner stack above tabs (critical/warning/info order). Cards also show inline warning strip for expiring tokens.
6. **Connection deep-dive DrawerSidePanel** — OAuth scopes, account info, last sync, error log, manual sync, reconnect.
7. **In-page filters** (NOT TopBar) — by integration, status, time-range, per the memory entry on filter placement.
8. **GA4 "Connect GA4" CTA** — primary button, teal, "Connect GA4" label. Connect flow deferred to sibling OAuth agent.
9. **CSS vars only** — no hardcoded hex. Source colors via CSS vars; gauge/bar/sparkline colors via semantic tokens.
10. **Token rules** — font floor 14px, WCAG AA, Polaris padding/radii, no shadows on cards, no emoji/gradients/glass.
