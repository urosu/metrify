/**
 * Returns the next pending onboarding step for a workspace as { href, label }.
 * Pages use this to populate EmptyState CTAs so they always link to the
 * actionable next step rather than a static settings page.
 *
 * Priority order (matches onboarding wizard steps):
 *  1. No store → connect store
 *  2. No ad accounts → connect ads
 *  3. No GSC → connect Search Console
 *  4. Fallthrough → integrations overview
 */

import type { Workspace } from '@/types';
import { wurl } from '@/lib/workspace-url';

export interface OnboardingStep {
    href: string;
    label: string;
}

export function getNextOnboardingStep(workspace: Workspace | undefined): OnboardingStep {
    const slug = workspace?.slug;
    const base = (path: string) => wurl(slug, path);

    if (!workspace?.has_store) {
        return { href: base('/settings/integrations'), label: 'Connect your store' };
    }
    if (!workspace?.has_ads) {
        return { href: base('/settings/integrations'), label: 'Connect ad accounts' };
    }
    if (!workspace?.has_gsc) {
        return { href: base('/settings/integrations'), label: 'Connect Search Console' };
    }
    return { href: base('/settings/integrations'), label: 'Configure integrations' };
}
