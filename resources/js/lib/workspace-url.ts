/**
 * Build an app URL prefixed with the active workspace slug.
 *
 * All workspace-scoped routes live under /{workspace-slug}/... so every
 * internal link needs this prefix. Pass the slug from usePage().props.workspace.
 *
 * Falls back to the bare path when slug is undefined (e.g. during SSR or
 * before workspace is resolved) so links degrade gracefully.
 */
export function wurl(workspaceSlug: string | undefined, path: string): string {
    if (!workspaceSlug) return path;
    return `/${workspaceSlug}${path}`;
}
