# Research: Daily Journal / Marketing Annotation Patterns

Scope: "marketing daily journal dashboard", annotation layers, daily ecommerce KPI sheet replacement SaaS.
Date: 2026-04-30

---

## 1. Where competitors place daily notes / annotations

### Plausible Analytics
- Annotations live **inside the main dashboard**, floating above the time-series chart as small flag markers.
- No separate route. The annotation icon (flag) renders at the top of the chart column for that date.
- Right-clicking a data point opens a context menu: "Add annotation" → inline text field.
- Annotation list is accessible via a secondary "Annotations" tab beside the chart legend — not a separate route.
- Category is implicit (no explicit Sale / Promo / Other tagging); text-only, one note per date.
- **Key pattern**: annotations are a chart overlay first; the management UI is subordinate.

### Datadog Event Annotations
- "Event overlays" are rendered on every time-series chart across the product — vertical markers that can be toggled on/off globally.
- Events come from three sources: deployments (auto), monitors firing (auto), custom user events (manual via API or UI).
- The annotation source (deployment / alert / custom) is encoded in the marker color and icon shape.
- Clicking a vertical line opens a side panel — title, body, timestamp, source. No inline editing within the chart.
- Events have a separate top-level route `/events` but annotations are always visible in the chart context first.
- **Key pattern**: global event log + per-chart overlay; the chart is the primary UX anchor.

### Mixpanel Annotations
- Called "Annotations" in their reporting product (Insights, Flows, Funnels).
- Any report chart can have annotation lines added by team members. Visible to all workspace members.
- Added via: hover a date → "+" icon appears at the top of the chart → inline text editor in a tooltip.
- List view in `Settings → Annotations` shows all annotations with author + timestamp. This list is a secondary surface.
- No categories. Plain text only. Author avatar shows on flag.
- **Key pattern**: hover + "+" is the lowest-friction add gesture; global list in settings is just management.

### Amplitude Annotations
- Chart annotations appear on Amplitude's Chart view — vertical dashed lines with flag labels.
- The flag label is clickable → popover with full text + author + timestamp.
- Adding annotations: click anywhere on chart area → "Add note" button at bottom → opens a panel.
- Category support: Amplitude separates "Release" annotations (from their SDK) from "custom" user notes.
- Dedicated "Annotations" management page under "Organization" settings — not top-level nav.
- **Key pattern**: in-chart is primary; settings page is for management/bulk-delete; release events are auto-populated.

### Northbeam Annotations
- Northbeam does not ship a standalone "daily journal" but exposes an **"Events" feed** on the Overview page.
- Events appear as vertical dashed lines on every time-series chart on the page.
- Clicking a line shows a card: title, date, category (Campaign Launch / Site Change / External). Category colors match Northbeam's source palette.
- Adding: "+ Event" button above the chart → modal with date picker, title, body, category.
- No separate route — events are a layer on the existing Overview.
- **Key pattern**: category-tagged events as chart overlay; the modal is a lightweight CRUD form.

---

## 2. Daily grid vs calendar grid

| Pattern | Products using it | Best for |
|---|---|---|
| **Table grid** (rows = days, cols = metrics) | Google Sheets (base), Metorik, Putler date range export | Seeing all metrics side by side; easy scanning of trends across the same column |
| **Calendar grid** (7-col month view, tiles per day) | Holidays/Index (Nexstage), Northbeam event calendar, Klipfolio | When you primarily care about date relationship, week patterns; harder to compare raw numbers |
| **Hybrid** (table + sparkline per row) | Elevar daily log, some Shopify Plus exports | Good for mixed scan + trend |

**Verdict for this feature**: the customer's Google Sheets model is a table (rows = days). A table grid matches their mental model exactly and makes numeric comparison across columns trivial. The calendar grid is better for event planning (see Holidays page already built). Use table grid.

---

## 3. CRUD UX for notes

| Pattern | Friction | Mobile-friendly | Multi-note per day |
|---|---|---|---|
| Right-click on chart line → "Add note" | Low for power users, invisible for new users | No | Needs secondary UI |
| Click "+" on empty cell in table row | Very low — always visible | Yes | Natural (multiple chips) |
| Separate panel / modal | Higher — two-step | Yes | Natural |
| Inline cell edit (text input in table) | Low for existing note edit | Depends | Hard to manage multiples |

**Verdict**: Use "click + in the Activities cell" as primary gesture (visible, zero discoverability issue). The inline editor appears in-place. Multiple notes per day are shown as chips in the table cell. Hover any chip → tooltip with full text. This mirrors Northbeam's category-tagged card model but inside the table row rather than a modal.

---

## 4. Chart integration

- Plausible, Datadog, Amplitude, Mixpanel, Northbeam: annotation lines all use **dashed vertical 1px lines** with a flag label at the top.
- Color convention: Northbeam uses category colors. Plausible uses a neutral zinc line. Datadog uses source-color lines (red for alerts, green for deploys, grey for custom).
- Nexstage already has `ChartAnnotationLayer` + `LineChart.notes` prop — both use `strokeDasharray="4 4"` and neutral grey lines. Extend both for category colors.
- **Category → color mapping** (using existing CSS vars):
  - Sale: `var(--color-warning)` (amber)
  - Promo: `var(--brand-primary-subtle)` (indigo-tinted)
  - Site change: `var(--color-info)` (blue)
  - External event: `var(--color-success)` (green, or if no success var: a soft sage)
  - Other: `var(--color-text-tertiary)` (zinc)

---

## 5. Placement decision

**Decision: new top-level route `/journal`.**

Justification:
1. **Size mismatch**: the daily grid (30 rows × 11 columns) plus the annotation chart is a full page of content. Adding it as a tab on `/dashboard` would push well below the fold and fragment Dashboard's "one glance" purpose.
2. **Different time grain**: Dashboard is "today / this week / last 14d" (hourly + daily). Journal is always "full calendar month, every day". Different date-range mental model → different page.
3. **Datadog / Amplitude precedent**: both keep a full event log at a separate URL. The chart overlay is still visible from any chart, but the management + bulk view is a dedicated destination.
4. **Plausible counter-example**: Plausible keeps annotations inline because Plausible is a single-page tool. Nexstage has 10+ routes; the Journal's table is too dense for a tab.
5. **Northbeam counter-example**: Northbeam's event overlay works on Overview because events are secondary to the chart. For Nexstage the **table grid is the primary UI** (replacing the Google Sheet), making a top-level route correct.
6. **Navigation**: Journal sits naturally beside `/holidays` as a planning/activity section. Both are "what happened / what's coming" pages rather than analytics pages.

Route: `/{workspace:slug}/journal`
Controller: `JournalController`
Inertia page: `Journal/Index`

---

## 6. Key patterns adopted

- Northbeam: category-colored vertical lines + category chip on each note.
- Plausible: "+" gesture on a cell / chart point as primary add UX.
- Datadog: click annotation line → popover with full text (implemented via `ChartAnnotationLayer` + `AnnotationTooltip`).
- Amplitude: "Release vs Custom" distinction → mapped to "System vs User" distinction already in `ChartAnnotation.isSystem`.
