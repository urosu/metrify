# Research: Holidays / Marketing Calendar UX — 2026

Research date: 2026-04-30. Covers marketing calendar SaaS patterns, country picker UX, alert system consolidation, and holiday data sources.

---

## 1. List vs Calendar as default view

**Finding: List is universally the default in calendar-adjacent planning tools when the content is task-like or time-pressure-driven.**

- **Klaviyo "Campaigns" tab** — defaults to chronological list, calendar is a secondary "Date View" toggled from a button. Reason: list is scannable for "what's next / what's overdue."
- **Shopify admin + Shopify Marketing Events app** — list first with date sorting, calendar view is opt-in via a toggle. List shows columns: event, date, status, channel.
- **Motion (AI calendar)** — list is "Planner" (default), calendar is "Calendar." Users land on Planner because it shows prioritized upcoming tasks.
- **Coschedule / Planable / Buffer campaigns** — list (agenda) view is the landing view; calendar is secondary. "Marketers do not start their day asking 'what shape is my month?' They ask 'what's coming up next?'"
- **Google Calendar itself** defaults to month for personal use, but **all B2B tools built on top of it** (CoSchedule, Hootsuite calendar, Semrush calendar) default to list/agenda.
- **Linear** — issue list is default; calendar is a secondary view in the same toolbar pattern.

**Conclusion: List view first is the right call. Calendar is useful for "overview" but not the starting point for planning.**

---

## 2. Country picker — single-select vs multi-select

**Finding: For "which market am I selling into?" contexts, a searchable single-select (with an "All" option) dominates. Multi-select is used when the user needs cross-market comparison.**

### Evidence

- **Shopify Markets** — single country dropdown for the active market. Switching market refreshes the view. No multi-select.
- **Google Analytics 4 "Geo" report** — single country filter on the main view; multi-country compare is a secondary "compare" mode that shows only 2 countries side by side. Not the default.
- **Facebook Ads Manager "Breakdown by country"** — single-dimension breakdown; cannot group two countries in one view.
- **Klaviyo segments / flows** — single country condition per rule block; combine via AND/OR operators, not a multi-select chip.
- **Shopify analytics "Country" filter** — single-select searchable dropdown.
- **Globalization / accessibility UX research (W3C i18n, Nielsen Norman):**
  - Country lists with 200+ items need search-first interaction. Scrolling through 200 items is unusable.
  - Multi-select over large lists compounds cognitive load (user must keep track of what's selected).
  - Single-select with an "All countries" sentinel is the accessible, low-friction pattern.
  - If multi-select is needed, limit visible selection to ~5 items with overflow chips, and require search to add items (not scroll).

### Verdict: **Searchable single-select with "All countries" default.**

Multi-select is not justified here because: (a) merchants selling into multiple markets simultaneously are rare at the SMB tier this product targets, (b) multi-select on 200+ items is cognitively expensive, (c) the "All countries" option already gives the global overview. If a merchant operates in DE + AT + CH (DACH region), they likely change the filter between views rather than needing both simultaneously.

**Implementation:** shadcn Combobox pattern — trigger button shows current country name or "All countries", opens popover with search input + scrollable list. No checkboxes; click to select and close. Keyboard navigable (arrow keys + Enter). Displays flag emoji as prefix for visual scannability.

---

## 3. Alert system consolidation

**Finding: Best-in-class SaaS products (Linear, Stripe, Vercel, Klaviyo) use a single notification hub; per-page "subscribe to alerts" affordances are a UX anti-pattern called "notification sprawl."**

### Patterns observed

- **Linear** — single Notifications settings page (Profile → Notifications). Every "alert" in the product links here. No inline subscription toggles on individual issue pages — instead you "watch" an issue (a lightweight local action) and notification *delivery* is governed globally.
- **Stripe** — `/settings/notifications` is the canonical place. Radar rules, payment alerts, and reports all configure delivery there. Individual alert creation happens on Radar rule pages but delivery settings link back to the hub.
- **Vercel** — `/account/notifications` is the hub. Deployment alert emails and Slack hooks are all configured there. Per-project alert toggles exist but they deep-link to the hub for delivery config.
- **Klaviyo** — flow-level and campaign-level send-to lists live on the object, but *notification preferences for the Klaviyo operator* (e.g. "email me when a campaign completes") are at Account → Notifications.
- **Amplitude** — alert creation is on the chart, but delivery (email, Slack, webhook) is at Org Settings → Notifications. The chart "alert" just creates the rule; the hub controls where it goes.

### Anti-pattern: per-page alert configuration

Having both an inline "Configure alerts" panel on the Holidays page AND an Alerts configuration section below the calendar creates two sources of truth for the same setting. Users who find one won't find the other; settings diverge.

### Recommended consolidation

1. **`/settings/notifications`** is the canonical hub (already exists in Nexstage).
2. Add a **"Holiday & Sale Event reminders"** section to the hub — controls default lead time (7/14/30 days), delivery channel (email/Slack).
3. On the Holidays page: keep the per-event **"Watch" toggle** (lightweight local action: "I want reminders for this event") but remove the inline delivery config panel and the "Configure alerts" drawer. Replace both with a small callout: "Reminder delivery settings → [Notification settings]."
4. The per-event Watch toggle is kept because it's a *subscription* (which events), not a *preference* (how/when to deliver). This mirrors Linear's "watch issue" / Stripe's "notify on rule match" distinction.

---

## 4. Holiday data sources

For future backend implementation (not required in this task):

- **Google Calendar API** — `calendar.v3.events.list` with `calendarId` set to a country-specific public holiday calendar (e.g. `en.usa#holiday@group.v.calendar.google.com`). 200+ countries. Free tier generous. Best for statutory holidays.
- **Calendarific API** — commercial, 230+ countries, 100k+ holidays. Returns holiday type (national, local, religious, observance). Good JSON shape. Paid plans start at $0/mo (limited) to $24/mo.
- **Abstract Holidays API** — 200+ countries, RESTful, simple auth. Cheaper than Calendarific.
- **Wikipedia / Wikidata** — structured holiday data via SPARQL. Good for cultural/shopping events that commercial APIs miss (Soldes, Singles Day, Diwali as a shopping event).
- **Custom seeding** — shopping events (Black Friday, Cyber Monday, Amazon Prime Day, Singles Day, Click Frenzy, Dia das Maes) are not in any API; must be seeded manually with prep-day guidance and lift data.

**Recommended hybrid:** Calendarific for statutory/cultural holidays (via `UpdateHolidaysJob` weekly sync) + manual seeder for shopping events.

---

## 5. Calendar view UX best practices

- **Shopify admin** — month grid, events shown as colored chips, click opens a side panel. Max 3 chips per day cell; overflow shown as "+N more."
- **Notion calendar** — same pattern. Day cell click opens a popover with event list, then clicking an event opens the detail.
- **Coschedule** — color-coded by content type (social, email, blog). Tag chips truncated to single line.
- **Pattern for ecommerce calendar:** Day cells with colored importance dots (high = solid zinc-900, medium = zinc-500, low = zinc-200). Click on day opens a micro-popover listing events, then clicking an event opens the full DrawerSidePanel.
