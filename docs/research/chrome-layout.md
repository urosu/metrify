# Chrome Layout Research — Sidebar Grouping & TopBar Patterns

> Compiled for Wave 3A-2. Informs Sidebar restructure and SyncHealthIndicator placement.

---

## 1. Sidebar grouping conventions

### Linear
- **Structure:** Two-pane shell. Persistent left sidebar, collapsible to icon rail (`Cmd+/`).
- **Work items at top:** Issues, Projects, Views, Cycles — all primary work surfaces.
- **Utility at bottom:** Settings, Team, Notifications. Settings lives at the very bottom, below a visible divider.
- **Active state:** Subtle background tint + indigo left-edge bar. Inactive items are greyed out.
- **Collapse:** Icons-only rail, tooltip on hover.
- **Sync/status:** No dedicated sync indicator in sidebar. Build status is shown via favicon animation + tab title prefix (breadcrumb-style). Health is surfaced at entity level (per-issue status dots), not globally.

### Stripe
- **Structure:** Left sidebar (~240px) with two clear logical groups. Primary nav up top (Home, Payments, Customers, Products). "Shortcuts" section below (pinned + recently visited).
- **Settings/Integrations:** Not in the sidebar directly — accessed via the top-right account avatar dropdown (Profile, Team, Billing, API keys). The sidebar is reserved for work surfaces only.
- **Sync / health:** Not shown in sidebar. A dismissable inline alert strip sits at the top of the Home page for actionable items (e.g., unresolved disputes). No persistent sync dot.
- **TopBar:** Left = logo + workspace switcher. Right cluster = global search (Cmd+K) · notifications · help · user avatar. Date range lives inside each page's content area, not the chrome. Test-mode toggle is a prominent TopBar pill.

### Northbeam
- **Structure:** Left sidebar, ~13 primary items, collapses to icon rail. Work pages at top (Overview, Sales, Attribution, Orders, Creatives, Metrics Explorer). Enterprise-only pages lower (MMM, Apex, Benchmarks).
- **Settings/Integrations:** At the very bottom of the sidebar, below all work pages and a visual separator. Gear icon; no label in collapsed state.
- **Sync health:** "Synced Nh ago" timestamp visible from the **workspace switcher's expanded state** (top-left). Not a persistent dot — it's contextual to the current store. Also shown per-integration in the Integrations page card.
- **TopBar:** Left = logo + workspace switcher. Center = global attribution/accounting/window controls (very prominent, every page). Right = search + notifications + help + user avatar.

### Polar
- **Structure:** Left sidebar with folder-tree navigation (Dashboards folder-hierarchy as the main pattern). Work surfaces at top; utility (Data Sources, Account Settings) at bottom.
- **Settings/Integrations:** Below a divider at the bottom. Gear icon. "Data Sources" (connectors) also at bottom.
- **Sync health:** Not a persistent sidebar item. "Ask Polar" AI is a bottom-right floating button. Sync status appears inline in connector cards.

### Triple Whale
- **Structure:** Left sidebar, ~11 items, collapses to icon rail.
- **Work pages at top:** Summary, Pixel, Creative Cockpit, Dashboards, Lighthouse, LTV & Cohorts, Benchmarks, Moby.
- **Utility at bottom:** Integrations, Settings — below a separator, gear/plug icon.
- **Sync health:** Per-section refresh timestamps shown inline in Summary page section headers ("15m ago"). Northbeam-style global sync timestamp NOT in the sidebar — in the section header of the relevant data block.
- **TopBar:** Left = workspace switcher. Center-right = global filter chip-row (country, channel, product). Far right = search + notifications + help + Moby + user avatar.

---

## 2. Sync health placement — what works

| App | Placement | Verdict |
|---|---|---|
| Linear | Favicon + tab title (background jobs) | Only makes sense for transient states |
| Stripe | Dismissable inline alert strip on Home page | Good for actionable issues; not for passive health |
| Northbeam | Workspace switcher expanded state + per-integration cards | Decent but hidden in switcher |
| Triple Whale | Inline section-header timestamps per data block | Contextual but scattered |
| Polar | Per-connector card on Integrations page | Destination, not ambient |
| Vercel | Status Dot on every deployment/domain row | Entity-level, not global |

**Conclusion:** The most effective pattern for ambient sync health in an analytics dashboard is a compact **TopBar pill** — visible at all times without occupying sidebar real estate. This is the pattern users expect from tools like Vercel (deployment status in top area), and it keeps the sidebar clean for navigation only. A dot + label + last-sync timestamp fits naturally in the right cluster of the TopBar alongside notifications and user menu.

---

## 3. TopBar zones — cross-competitor survey

| Zone | Common contents |
|---|---|
| Far left | Logo + workspace/store switcher |
| Center | Date range picker (or attribution model controls per Northbeam) |
| Center-right | Page-specific filter chips (Triple Whale), or deliberately empty (Stripe, Linear) |
| Far right | Sync health (optional) · Search/Cmd+K · Notifications · Help · User avatar |

**Key finding:** Stripe keeps the TopBar minimal (workspace + search + notifications + user). Linear is even more minimal (just workspace + avatar). Northbeam is the outlier — it puts attribution model, window, and accounting mode in the TopBar, which is a strong pattern for their power-user audience but creates noise for simpler use cases. Nexstage should follow the Stripe/Linear model: workspace + date + system status + commands + user. Page-specific filters belong in page content.

---

## 4. Settings/Integrations position — verdict

All five competitors surveyed put Settings and Integrations **at the bottom of the sidebar**, below a visible divider. This is the universal convention. There are no counter-examples in the category. Nexstage should match this pattern.

---

## 5. Tools group

Linear has no "tools" concept in sidebar — tools are command-palette accessible. Stripe has a "Shortcuts" section (auto-populated from recent + pinned). Northbeam buries tools inside the Settings flow. The most practical pattern for Nexstage is a **collapsible "Tools" group** at the bottom (above Settings/Integrations), default collapsed, with ChevronRight expand icon — matches how Linear/Notion surface utility features without cluttering primary nav.
