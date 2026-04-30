# Drilldown Pattern Research — Sidebar vs Inline Expansion

## Research queries
- Linear issue side panel
- Stripe payment row drawer
- Notion side peek pattern
- Inline expansion vs side drawer accessibility

---

## Linear issue side panel

Linear opens issue detail in a right-side panel that overlays the list without navigating away. The list column stays visible and scrollable. Key behaviors:
- Panel slides in from the right at ~40% viewport width
- List stays interactive (click another row = new panel)
- Close via Esc, backdrop click, or explicit X button
- Keyboard focus traps inside the panel for accessibility (WCAG 2.1 §2.4.3)
- URL updates (query param or hash) so deep links work
- No page reload — list context is never lost

Verdict: **always use drawer for row-click drilldown in list/table views** unless the inline content is < 2 lines of metadata.

## Stripe payment row drawer

Stripe opens payment detail in a full-height right drawer on row click. Characteristics:
- 480–520px wide panel, fixed right
- Header: payment ID + status badge
- Body: timeline of events, metadata key-value pairs
- Actions (refund, resend) in the panel header toolbar
- Backdrop is semi-transparent but the list remains visible behind it
- Stacked drawers not supported — second click replaces current panel

Key difference from Linear: Stripe does NOT update the URL for the drawer state (the list URL stays); Linear does. For Nexstage, Inertia's `router.get` with `preserveState` can do either. Both are acceptable; preserveState is preferred so the user's filter/sort state survives.

## Notion side peek pattern

Notion's side peek opens a database row as a full document in the right half of the viewport. Behaviors:
- Peek is essentially a page-in-page: full editor width on the right
- The database table remains scrollable and highlighted on the left
- Toggling from peek to full-page is a single button click
- Close returns focus to the highlighted row in the table

For data tables (not document editors), a narrower drawer (40–45% width) is sufficient — no need for a two-column split layout.

## Inline expansion vs side drawer accessibility

WCAG 2.1 SC 4.1.3 (Status Messages), SC 2.4.3 (Focus Order), SC 1.3.2 (Meaningful Sequence):
- Inline row expansion inserts DOM nodes **between** table rows. This breaks table semantics (`<table>` children must be `<tr>` directly inside `<tbody>`). It also reorders focus unexpectedly.
- A right-side drawer is a separate `<dialog>` or `role="dialog"` region. It receives focus on open, returns focus on close, and does not break table semantics.
- Screen readers handle `role="dialog"` with `aria-labelledby` predictably. Expanded rows in tables are non-standard.

**Rule: for any data table row click, prefer DrawerSidePanel over inline expansion.**

Exception: error/exception detail rows in *admin-only* debug tables (Queue, Logs) where the content is raw error text and users may need to copy it. The inline format is acceptable there since it is a developer tool, not a user-facing analytics surface.

---

## Application to Nexstage pages

### Converted to drawer (correct pattern)
- `Customers/Index.tsx` — already uses DrawerSidePanel for customer row click ✓
- `Seo/Index.tsx` — already uses DrawerSidePanel for query/page row click ✓
- `Integrations/Index.tsx` — already uses DrawerSidePanel for connection row click ✓
- `Orders/Index.tsx` — already uses DrawerSidePanel for order row click ✓

### Kept as inline (admin debug tools — acceptable)
- `Admin/Logs.tsx` — error text inline expansion for sync log rows (admin-only, copy-paste use case)
- `Admin/Queue.tsx` — exception text inline expansion for failed queue rows (admin-only)

### Kept as accordion (content grouping — acceptable)
- `Manage/NamingConvention.tsx` — campaign category accordion (collapses groups of campaigns; not a row drilldown)
- `Integrations/TagGenerator.tsx` — `<details>` element for collapsible helper content

No conversions required — all user-facing drilldowns are already using DrawerSidePanel. Admin debug tables retain inline expansion for practical copy-paste reasons.
