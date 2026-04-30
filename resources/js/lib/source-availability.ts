/**
 * source-availability.ts
 *
 * Derives the list of MetricSource slugs a workspace can actually filter by,
 * based on the `metricSources` shared Inertia prop (populated by MetricSourceResolver).
 *
 * Real + Store are always available (they rely on order data, not external integrations).
 * The remaining four sources are gated on whether the integration is connected.
 *
 * @see app/Services/Metrics/MetricSourceResolver.php
 * @see resources/js/types/index.d.ts  MetricSources interface
 */
import { MetricSource } from '@/Components/shared/SourceBadge';
import { MetricSources } from '@/types';

/**
 * Returns the ordered list of sources the workspace can use as a lens.
 * Order mirrors the canonical ALL_SOURCES display order in SourceToggle.
 *
 * @param metricSources  The `metricSources` shared prop. Pass null/undefined on
 *                       pages where workspace context is absent — returns ['real','store'].
 */
export function availableSources(metricSources: MetricSources | null | undefined): MetricSource[] {
    const out: MetricSource[] = ['real', 'store']; // always available
    if (!metricSources) return out;
    if (metricSources.has_facebook) out.push('facebook');
    if (metricSources.has_google)   out.push('google');
    if (metricSources.has_gsc)      out.push('gsc');
    if (metricSources.has_ga4)      out.push('ga4');
    return out;
}
