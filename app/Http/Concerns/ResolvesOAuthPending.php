<?php

declare(strict_types=1);

namespace App\Http\Concerns;

/**
 * Shared helper for resolving pending OAuth cache entries after an OAuth callback.
 *
 * Used by IntegrationsController and OnboardingController, which both present a
 * platform account/property picker when an OAuth flow has just completed.
 */
trait ResolvesOAuthPending
{
    /**
     * Read a pending OAuth cache entry and return the key + payload field for the frontend.
     *
     * Returns null when:
     *   - $rawKey is not a non-empty string
     *   - the cache entry has expired or doesn't exist
     *   - the workspace_id in the entry doesn't match (prevents cross-tenant leakage)
     *
     * @return array{key: string, items: mixed}|null
     */
    private function resolvePending(mixed $rawKey, int $workspaceId, string $field): ?array
    {
        if (! is_string($rawKey) || $rawKey === '') {
            return null;
        }

        $cached = cache()->get($rawKey);

        if ($cached === null || (int) ($cached['workspace_id'] ?? 0) !== $workspaceId) {
            return null;
        }

        return ['key' => $rawKey, 'items' => $cached[$field]];
    }
}
