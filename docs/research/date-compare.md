# Date Range Comparison — Research Notes

**Research date:** 2026-04-30  
**Context:** Feature build for custom date range comparison (customer feature #1).

---

## 1. Comparison toggle UX (chip / checkbox / segmented control)

### Stripe Analytics
- Comparison is a **toggle chip** ("Compare to: Previous period") that appears inline below the date picker trigger, not inside the popover.
- Once toggled on, the secondary range pill appears next to the primary one in the header ("Apr 1–30 vs Mar 1–30").
- No separate popover step required — chip click is immediate, no Apply needed for the comparison toggle itself.
- **Naming:** "Previous period" (auto) / "Same period last year". No "prior period" — always "previous."

### Plausible
- Comparison mode is a **segmented control** inside the date-picker popover: "Disable / Previous period / Year over year / Custom range."
- Selecting any non-Disable option immediately previews the comparison label in the trigger.
- "Match day of week" checkbox appears below the segmented control — aligns Mon-Mon not Jan-1 to Jan-7.
- **Chart overlay:** comparison series shown as a dashed, 40%-opacity line of the same hue as primary. No separate color — just dash + opacity.
- **Tooltip:** single tooltip showing both values side by side with delta labeled "Δ +12.4%."

### Triple Whale
- Comparison is a **checkbox** ("Compare to previous period") in the date popover, below the presets list.
- Once checked, a second date row appears for custom comparison range.
- URL state: `?compare=previous_period` or `?compare=2024-01-01:2024-01-31`.
- **MetricCard treatment:** primary value stays large; secondary "prior" value shown in smaller muted text below, then delta chip.

### Polar Analytics
- Comparison toggle is a **pill inside the date display bar** ("vs Previous period ×"), dismissable with ×.
- Selecting comparison auto-advances to "Previous period"; dropdown reveals "Previous period / Same period last year / Custom."
- **Chart overlay:** comparison line is the primary series color at 35% opacity, dashed. Legend shows "Current · Previous" with matching opacity treatment.

### Northbeam
- Comparison appears as a **"vs" chip** appended to the primary date range in the toolbar: "Last 7 days vs Prior 7 days."
- Clicking the "vs" chip opens a small popover with three options: Prior period / Same period LY / Custom.
- **MetricCard treatment:** absolute value shown large; delta chip shows sign + %; hover on delta reveals absolute difference in a tooltip.
- **Naming convention:** "Prior period" (Northbeam) vs "Previous period" (Stripe, Polar). Northbeam is the ecommerce-analytics standard; we adopt "Prior period."

### Google Analytics 4
- Comparison is a separate **"Add comparison" button** below the date range input — two-step flow, more deliberate.
- Once set, both ranges appear as two separate date rows with "×" to dismiss each.
- **MetricCard:** two stacked number rows; delta chip only for the difference metric.
- **Chart overlay:** dashed line with lighter fill below for the comparison area. Comparison color = primary color at 50% opacity.
- GA4 labels them "Date range 1 / Date range 2" — deliberately neutral, no "prior" framing.

### Shopify Analytics
- Date comparison is a **"Compare" checkbox** in the date picker sidebar, below the preset list.
- Checking it expands a comparison row with its own preset list ("Previous period / Previous year / Custom").
- The primary date range preview at the bottom shows both ranges side by side.
- **Chart:** Two-line overlay; comparison line = same hue, dotted, lighter stroke.
- **Naming:** "Previous period" / "Previous year." Shopify never says "prior."

---

## 2. Prior-period vs same-period-last-year naming conventions

| Product | Prior period label | YoY label |
|---|---|---|
| Stripe | "Previous period" | "Same period last year" |
| Plausible | "Previous period" | "Year over year" |
| Triple Whale | "Previous period" | "Previous year" |
| Polar | "Previous period" | "Same period last year" |
| Northbeam | "Prior period" | "Same period last year" |
| GA4 | "Date range 1/2" (neutral) | — |
| Shopify | "Previous period" | "Previous year" |

**Decision for Nexstage:** Use **"Previous period"** (broader adoption; Shopify baseline users expect it). Keep "Same period last year" for YoY (Stripe/Polar convention, most descriptive). Truncate to "vs prior period" / "vs prior year" in delta chips where space is short.

---

## 3. Chart overlay: overlay vs side-by-side

- **Overlay (line-on-line)** — used by Plausible, Polar, Shopify, GA4, Northbeam. Most space-efficient. Recommended for dense dashboards.
- **Side-by-side bars** — used by Stripe in bar-chart contexts. Useful when exact bar comparison matters; overkill for trend lines.
- **Decision:** Overlay for Nexstage line charts. Comparison series = same color as primary at 40% opacity, `strokeDasharray="4 2"`. This matches Plausible's pattern and differentiates the series accessibly (opacity + dash).

---

## 4. % delta formatting

| Pattern | Example |
|---|---|
| Sign + percent chip | +12.4% ↑ (green) / -4.2% ↓ (red) |
| Absolute + percent | "$16,320 (+15.1%)" — Northbeam MetricCard |
| Tooltip expansion | Hover chip → absolute diff appears |

**Decision for Nexstage MetricCard:**
- Delta chip shows sign + `|Δ|%` with trend icon (matches existing pattern).
- Add `comparisonLabel` prop so the "vs prior period" text below chip can be updated to "vs prior year" or a custom range label.
- When comparison active: chip tooltip shows absolute difference.

---

## 5. Table column treatment

- **Polar / Northbeam:** "vs Prior" as an optional additional column (toggle), showing ±% per row. Not forced on by default.
- **Triple Whale:** Inline ±% in the same cell as the value, muted color. Compact.
- **Lifetimely:** Dedicated "Change" column with colored background per row proportional to delta magnitude.
- **Decision for Nexstage DataTable:** Add `showComparison` prop. When true, each data row gets a `delta_pct` column rendered as a `DeltaChip`. Backend must include `delta_pct` per row when comparison range is active.

---

## 6. Patterns borrowed for this implementation

1. **Comparison chips in date picker popover** — Shopify / Plausible pattern: chip row inside the same popover, no second control.
2. **Muted dashed overlay line** — Plausible / Polar pattern: comparison line = primary color at 40% opacity, dash pattern `4 2`.
3. **"vs prior period" / "vs prior year" delta chip label** — Northbeam naming (ecommerce analytics standard).
4. **URL state with `cmp_from` / `cmp_to` / `cmp_mode`** — Plausible URL-stateful-everything pattern.
5. **Comparison mode badge on trigger** — Polar's dismissable "vs Previous period ×" pill in the date trigger.
6. **Absolute + pct in tooltip** — Northbeam MetricCard hover-tooltip pattern.

---

## 7. Implementation notes for Nexstage

- **`DateRangePicker` already has** the comparison UI (chip row + custom calendar). It uses `compare_from`/`compare_to` in URL params via `useDateRange`. This is already partially implemented in the existing component — the new `DateCompareToolbar` wraps it and adds the active-comparison badge + mode tracking (`cmp_mode` URL param).
- **`MetricCard`** needs two new optional props: `comparisonValue` (raw number) and `comparisonLabel` (string, e.g. "vs prior period"). Chip label currently hardcoded to "vs prior period" — parameterize it.
- **`LineChart`** already accepts `comparisonData?: ChartDataPoint[]` — this prop is fully wired. The `compareLabel` prop drives the legend label.
- **`KpiCardGrid`** needs `comparison_value` and `comparison_label` per card from the controller.
- **`RevenueTrendChart`** needs a `comparisonData` series prop for the second muted line.
