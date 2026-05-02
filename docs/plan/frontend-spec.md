# Nexstage Frontend Specification

Tailwind 4, PrimeVue (unstyled), ECharts 6 via vue-echarts 8, Inertia 3 + Vue 3.5.

---

## 1. Design Tokens

Tailwind 4 `@theme` block in `resources/css/app.css`. All values OKLCH. All text/background pairs must meet WCAG AA (4.5:1 minimum).

```css
@theme {
  --color-positive: oklch(0.723 0.219 149.579);    /* emerald-500 */
  --color-warning: oklch(0.769 0.188 70.08);        /* amber-500 */
  --color-negative: oklch(0.577 0.245 27.325);      /* rose-600 */
  --color-neutral: oklch(0.552 0.016 285.938);      /* zinc-500 */
  --color-info: oklch(0.685 0.169 237.323);         /* sky-500 */
  --color-surface: oklch(0.985 0.002 247.839);      /* zinc-50 */
  --color-surface-raised: oklch(1 0 0);              /* white */
  --color-border: oklch(0.871 0.006 286.286);        /* zinc-300 */
  --color-border-subtle: oklch(0.920 0.004 286.32);  /* zinc-200 */
  --color-text: oklch(0.274 0.006 286.033);          /* zinc-800, 14.5:1 */
  --color-text-secondary: oklch(0.442 0.017 285.786);/* zinc-600, 7.1:1 */
  --color-text-muted: oklch(0.552 0.016 285.938);   /* zinc-500, 4.6:1 */
  --color-delta-up: oklch(0.723 0.219 149.579);     /* emerald-500 */
  --color-delta-down: oklch(0.577 0.245 27.325);    /* rose-600 */
  --color-delta-neutral: oklch(0.552 0.016 285.938); /* zinc-500 */
  --color-heatmap-min: oklch(1 0 0);                 /* white */
  --color-heatmap-max: oklch(0.357 0.176 281.7);     /* indigo-700 */
  --spacing-page: 1.5rem;
  --spacing-section: 1rem;
  --spacing-card: 0.75rem;
  --font-mono: 'JetBrains Mono', ui-monospace, monospace;
}
```

**Status color usage:**

| Context | Thresholds | Colors |
|---|---|---|
| Inventory days-of-stock | >60d / 30-60d / 15-29d / <15d | zinc / positive / warning / negative |
| COGS badge | Actual / Estimated / Missing | positive / warning / negative |
| Creative triage | Winner / Iterate / Kill | positive / warning / negative |
| PageSpeed score | >=90 / 50-89 / <50 | positive / warning / negative |
| RFM segments | Champions-Loyal / At Risk / Lost | positive / warning / negative |

---

## 2. Shared TypeScript Types

File: `resources/js/types/index.d.ts`

```typescript
interface SharedData {
  auth: { user: { id: number; name: string; email: string } };
  workspace: WorkspaceShared;
  permissions: Permissions;
  dateRange: DateRangeShared;
  flash: { success?: string; error?: string };
}
interface WorkspaceShared {
  id: number; name: string; slug: string;
  reporting_currency: string; reporting_timezone: string;
  attribution_model: 'last_click' | 'first_click' | 'linear';
  attribution_window_days: 7 | 14 | 30;
  naming_delimiter: string | null; naming_dimensions: string[] | null;
  brand_keywords: string[] | null;
  target_roas: number | null; target_cac: number | null; target_revenue: number | null;
  onboarding_checklist: Record<string, boolean>;
  plan: 'trialing' | 'active' | 'past_due' | 'canceled';
}
interface Permissions {
  role: 'owner' | 'admin' | 'member';
  canAccessFinancials: boolean; canAccessPii: boolean; canAccessSettings: boolean;
  canManageMembers: boolean; canManageWorkspace: boolean;
  canManageSettings: boolean; canManageData: boolean;
}
interface DateRangeShared {
  start: string; end: string;  // YYYY-MM-DD
  comparison_start: string | null; comparison_end: string | null;
  preset: string;  // 'last_7d' | 'last_30d' | 'this_month' | 'custom' | ...
  granularity: 'day' | 'week' | 'month';
  comparison_enabled: boolean;
}
interface KpiValue { value: number | null; comparison: number | null; delta: number | null }
interface SparklinePoint { date: string; value: number }
interface PaginatedResult<T> {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
  links: { first: string; last: string; prev: string | null; next: string | null };
}
interface ColumnDef {
  field: string; header: string; sortable?: boolean;
  format?: 'currency' | 'number' | 'percent' | 'ratio' | 'date';
  visible?: boolean; width?: string;
  permission?: 'canAccessFinancials' | 'canAccessPii';
}
```

---

## 3. Vue Component Contracts

All shared components in `resources/js/Components/`.

```typescript
// 1. KpiCard — sparkline via vue-sparklines, NOT ECharts
interface KpiCardProps {
  label: string; value: number | null;
  format: 'currency' | 'number' | 'percent' | 'ratio';
  currency?: string; comparison?: number | null;
  sparkline?: SparklinePoint[]; invertDelta?: boolean;  // true for CPA/CAC
}  // Emits: click()

// 2. DataTable — PrimeVue wrapper (unstyled + Tailwind presets)
interface DataTableProps {
  columns: ColumnDef[]; rows: Record<string, unknown>[];
  loading?: boolean; paginator?: boolean; totalRecords?: number;
  expandable?: boolean;  // Campaign→AdSet→Ad, Product→Variant
}  // Emits: sort(field,order), page(page), row-click(row), row-expand(row), cell-edit({field,row,value})

// 3. TimeSeriesChart — ECharts line/area + comparison + annotations
interface TimeSeriesChartProps {
  series: { name: string; data: [string, number][]; type?: 'line'|'area'; stack?: string }[];
  comparison?: { name: string; data: [string, number][] };
  annotations?: { date: string; label: string }[];
  granularity: 'day' | 'week' | 'month';
  format: 'currency' | 'number' | 'percent'; currency?: string;
}  // No emits

// 4. Drawer — right-side overlay, URL synced via pushState
interface DrawerProps { open: boolean; title: string; width?: 'sm'|'md'|'lg' }  // sm=400 md=480 lg=560
// Emits: close()

// 5. DateRangePicker
interface DateRangePickerProps { modelValue: DateRangeShared }
// Emits: update:modelValue(DateRangeShared)

// 6. FilterChips
interface FilterChipsProps { filters: { key: string; label: string; value: string }[] }
// Emits: remove(key)

// 7. AlertStrip — max 3 visible
interface AlertStripProps {
  alerts: { id: number; severity: 'critical'|'warning'|'info'; message: string; actionUrl?: string }[];
}  // Emits: dismiss(id), snooze(id)

// 8. EmptyState
interface EmptyStateProps { message: string; actionLabel?: string; actionUrl?: string }
// Emits: action()

// 9. SkeletonLoader
interface SkeletonLoaderProps { type: 'kpi'|'chart'|'table'|'card'; rows?: number }

// 10. RfmGrid — plain HTML/CSS clickable grid
interface RfmGridProps { data: { r: number; f: number; count: number; segment: string }[]; gridSize?: 3|5 }
// Emits: cell-click({ r, f })

// 11. WaterfallChart
interface WaterfallStep { label: string; value: number; type: 'total'|'subtotal'|'decrease' }
interface WaterfallChartProps { steps: WaterfallStep[]; currency?: string }

// 12. CohortHeatmap
interface CohortHeatmapProps {
  data: { cohort_month: string; period_offset: number; value: number }[];
  metric: string;  // 'retention'|'revenue'|'orders' — controls color scale
}

// 13. HorizontalFunnel
interface FunnelChartProps { steps: { label: string; value: number; drop_off_pct: number }[] }

// 14. SalesHeatmap — 7x24 grid
interface SalesHeatmapProps { data: [number, number, number][]; metric?: 'orders'|'revenue' }  // [dow 1-7, hour 0-23, value]

// 15. QuadrantScatter
interface QuadrantScatterProps {
  data: { label: string; x: number; y: number; size: number }[];
  xLabel: string; yLabel: string;
  quadrants?: { topRight: { label: string; color: string }; topLeft: { label: string; color: string };
    bottomRight: { label: string; color: string }; bottomLeft: { label: string; color: string } };
}
```

---

## 4. ECharts Skeleton Configs

Pattern: `<v-chart :option="option" autoresize />`. Canvas renderer. Import only needed components via `use()`.

### 4.1 TimeSeriesChart

```javascript
const option = {
  tooltip: { trigger: 'axis' },
  xAxis: { type: 'time' },
  yAxis: { type: 'value', axisLabel: { formatter: formatFn } },
  series: [
    { name: 'Revenue', type: 'line', smooth: true, data: props.series[0].data,
      areaStyle: { opacity: 0.15 }, lineStyle: { width: 2 } },
    ...(props.comparison ? [{
      name: props.comparison.name, type: 'line', data: props.comparison.data,
      lineStyle: { type: 'dashed', width: 1.5, opacity: 0.6 }, symbol: 'none',
    }] : []),
  ],
  // Annotations as markLine on first series
  // markLine: { data: annotations.map(a => ({ xAxis: a.date, label: { formatter: a.label } })) }
};
// Stacked area: add `areaStyle: { opacity: 0.5 }` + `stack: 'total'` per series — not a separate component.
```

### 4.2 WaterfallChart

```javascript
function buildWaterfall(steps) {
  const base = [], increase = [], decrease = [];
  let running = 0;
  for (const s of steps) {
    if (s.type === 'total' || s.type === 'subtotal') {
      base.push(0); increase.push(s.value); decrease.push(0);
      running = s.value;
    } else {
      base.push(running + s.value); increase.push(0); decrease.push(Math.abs(s.value));
      running += s.value;
    }
  }
  return { base, increase, decrease, labels: steps.map(s => s.label) };
}
const { base, increase, decrease, labels } = buildWaterfall(props.steps);
const option = {
  tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
  xAxis: { type: 'category', data: labels },
  yAxis: { type: 'value' },
  series: [
    { name: 'Base', type: 'bar', stack: 'w', data: base,
      itemStyle: { color: 'transparent' }, emphasis: { itemStyle: { color: 'transparent' } } },
    { name: 'Increase', type: 'bar', stack: 'w', data: increase,
      itemStyle: { color: 'oklch(0.723 0.219 149.579)' } },   // emerald
    { name: 'Decrease', type: 'bar', stack: 'w', data: decrease,
      itemStyle: { color: 'oklch(0.577 0.245 27.325)' } },    // rose
  ],
};
// Subtotal bars: override itemStyle per data point with zinc-400.
```

### 4.3 CohortHeatmap

```javascript
const option = {
  tooltip: { formatter: (p) => `${p.data[2]}%` },
  xAxis: { type: 'category', data: periodOffsets, name: 'Month' },
  yAxis: { type: 'category', data: cohortMonths },
  visualMap: { min: 0, max: 100, calculable: true,
    inRange: { color: props.metric === 'retention'
      ? ['#ffffff', 'oklch(0.723 0.219 149.579)']   // white → emerald
      : ['#ffffff', 'oklch(0.357 0.176 281.7)'] },  // white → indigo
  },
  series: [{ type: 'heatmap', data: matrixData, label: { show: true } }],
};
```

### 4.4 QuadrantScatter

```javascript
const option = {
  tooltip: { formatter: (p) => p.data[3] },
  xAxis: { name: props.xLabel, inverse: true },  // SEO: position 1=right
  yAxis: { name: props.yLabel },
  series: [{
    type: 'scatter', data: props.data.map(d => [d.x, d.y, d.size, d.label]),
    symbolSize: (val) => Math.sqrt(val[2]) * 2,
    markArea: { silent: true, data: [
      [{ coord: [xMid, yMid], itemStyle: { color: 'oklch(0.723 0.219 149.579/0.08)' } },
       { coord: [xMin, yMax] }],   // top-right: emerald
      [{ coord: [xMax, yMid], itemStyle: { color: 'oklch(0.769 0.188 70.08/0.08)' } },
       { coord: [xMid, yMax] }],   // top-left: amber
      [{ coord: [xMid, 0], itemStyle: { color: 'oklch(0.871 0.006 286.286/0.08)' } },
       { coord: [xMin, yMid] }],   // bottom-right: zinc
      [{ coord: [xMax, 0], itemStyle: { color: 'oklch(0.871 0.006 286.286/0.05)' } },
       { coord: [xMid, yMid] }],   // bottom-left: zinc lighter
    ] },
  }],
};
```

### 4.5 HorizontalFunnel

```javascript
const option = {
  tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
  series: [{
    type: 'funnel', orient: 'horizontal', left: '5%', width: '90%', sort: 'none',
    data: props.steps.map(s => ({ name: s.label, value: s.value })),
    label: { show: true, position: 'inside', formatter: '{b}\n{c}' },
    itemStyle: { borderWidth: 1, borderColor: '#fff' },
  }],
};
```

### 4.6 SalesHeatmap

```javascript
const hours = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const option = {
  tooltip: { formatter: (p) => `${days[p.data[0]-1]} ${hours[p.data[1]]}:00 — ${p.data[2]}` },
  xAxis: { type: 'category', data: hours, splitArea: { show: true } },
  yAxis: { type: 'category', data: days },
  visualMap: { min: 0, max: maxValue, calculable: true,
    inRange: { color: ['#ffffff', 'oklch(0.357 0.176 281.7)'] } },  // white → indigo-700
  series: [{
    type: 'heatmap', data: props.data, label: { show: false },
    itemStyle: { borderColor: '#fff', borderWidth: 1 },
  }],
};
```
