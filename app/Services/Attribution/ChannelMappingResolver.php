<?php

declare(strict_types=1);

namespace App\Services\Attribution;

use App\Models\ChannelMapping;
use Illuminate\Support\Facades\Cache;

/**
 * Priority-ordered resolver from (utm_source, utm_medium) → channel_name + channel_type.
 *
 * Implements a 4-tier lookup — first match wins:
 *   Tier 1: workspace literal  — exact source + exact medium (lower priority value first)
 *   Tier 2: workspace wildcard — exact source, any medium     (lower priority value first)
 *   Tier 3: global literal     — exact/regex source + exact medium (priority-ordered)
 *   Tier 4: global wildcard    — exact/regex source, any medium    (priority-ordered)
 *
 * Within each tier rows are ordered by `priority` ASC (lower = higher precedence, default 100).
 * This lets the seeder express tie-breaking (e.g. "google / cpc" before "google / *").
 *
 * Reads channel_mappings (including the priority column added in L2 migrations).
 * Global rows (~40 seed rows) are cached under GLOBAL_CACHE_KEY, shared across
 * all workspaces. Workspace override rows cached per workspace under cacheKey(workspaceId).
 * Both caches have a 60-minute TTL.
 *
 * Public method: resolve(source, medium, workspaceId) → {channel_name, channel_type}|null
 *
 * Reads:     channel_mappings, Redis
 * Writes:    Redis
 * Called by: ChannelClassifierService (thin wrapper for back-compat)
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/UX.md §5 (ChannelClassifier consumer list)
 */
class ChannelMappingResolver
{
    private const CACHE_TTL = 3600;

    public const GLOBAL_CACHE_KEY = 'channel_mappings.global';

    public static function cacheKey(int $workspaceId): string
    {
        return "channel_mappings.workspace.{$workspaceId}";
    }

    /**
     * Resolve a utm_source + utm_medium pair to channel_name and channel_type.
     *
     * Returns null if no matching row exists anywhere (caller should treat as "other").
     *
     * @param string  $source      Normalised (lowercased, trimmed) utm_source
     * @param ?string $medium      Normalised utm_medium, or null when not present
     * @param int     $workspaceId
     * @return array{channel_name: string, channel_type: string}|null
     */
    public function resolve(string $source, ?string $medium, int $workspaceId): ?array
    {
        [$wsRows, $globalRows] = $this->loadAll($workspaceId);

        // Pre-partition once into the four tiers.
        $wsLiteral    = [];
        $wsWildcard   = [];
        $gbLiteral    = [];
        $gbWildcard   = [];

        foreach ($wsRows as $row) {
            if ($row['utm_medium_pattern'] !== null) {
                $wsLiteral[] = $row;
            } else {
                $wsWildcard[] = $row;
            }
        }

        foreach ($globalRows as $row) {
            if ($row['utm_medium_pattern'] !== null) {
                $gbLiteral[] = $row;
            } else {
                $gbWildcard[] = $row;
            }
        }

        // Rows are already sorted by priority ASC from the cache loader.

        // Tier 1: workspace + exact medium
        if ($medium !== null) {
            foreach ($wsLiteral as $row) {
                if ($row['utm_source_pattern'] === $source && $row['utm_medium_pattern'] === $medium) {
                    return ['channel_name' => $row['channel_name'], 'channel_type' => $row['channel_type']];
                }
            }
        }

        // Tier 2: workspace + wildcard medium
        foreach ($wsWildcard as $row) {
            if ($row['utm_source_pattern'] === $source) {
                return ['channel_name' => $row['channel_name'], 'channel_type' => $row['channel_type']];
            }
        }

        // Tier 3: global + exact medium (source may be a regex pattern)
        if ($medium !== null) {
            foreach ($gbLiteral as $row) {
                if ($row['utm_medium_pattern'] === $medium && $this->sourceMatches($row, $source)) {
                    return ['channel_name' => $row['channel_name'], 'channel_type' => $row['channel_type']];
                }
            }
        }

        // Tier 4: global + wildcard medium (source may be a regex pattern)
        foreach ($gbWildcard as $row) {
            if ($this->sourceMatches($row, $source)) {
                return ['channel_name' => $row['channel_name'], 'channel_type' => $row['channel_type']];
            }
        }

        return null;
    }

    /**
     * Load global and workspace rows from Redis, warming the cache on miss.
     *
     * Returns [workspaceRows, globalRows], each sorted by priority ASC.
     *
     * @return array{0: list<array>, 1: list<array>}
     */
    private function loadAll(int $workspaceId): array
    {
        return [
            $this->loadWorkspaceMappings($workspaceId),
            $this->loadGlobalMappings(),
        ];
    }

    /** @return list<array> Sorted by priority ASC. */
    private function loadGlobalMappings(): array
    {
        $cached = Cache::get(self::GLOBAL_CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        $rows = ChannelMapping::whereNull('workspace_id')
            ->orderBy('priority')
            ->get(['workspace_id', 'utm_source_pattern', 'utm_medium_pattern',
                   'channel_name', 'channel_type', 'is_regex', 'priority'])
            ->map(fn (ChannelMapping $m) => [
                'workspace_id'       => null,
                'utm_source_pattern' => $m->utm_source_pattern,
                'utm_medium_pattern' => $m->utm_medium_pattern,
                'channel_name'       => $m->channel_name,
                'channel_type'       => $m->channel_type,
                'is_regex'           => (bool) $m->is_regex,
                'priority'           => (int) ($m->priority ?? 100),
            ])
            ->values()
            ->all();

        Cache::put(self::GLOBAL_CACHE_KEY, $rows, self::CACHE_TTL);

        return $rows;
    }

    /** @return list<array> Sorted by priority ASC. */
    private function loadWorkspaceMappings(int $workspaceId): array
    {
        $cacheKey = self::cacheKey($workspaceId);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $rows = ChannelMapping::where('workspace_id', $workspaceId)
            ->orderBy('priority')
            ->get(['workspace_id', 'utm_source_pattern', 'utm_medium_pattern',
                   'channel_name', 'channel_type', 'is_regex', 'priority'])
            ->map(fn (ChannelMapping $m) => [
                'workspace_id'       => $m->workspace_id,
                'utm_source_pattern' => $m->utm_source_pattern,
                'utm_medium_pattern' => $m->utm_medium_pattern,
                'channel_name'       => $m->channel_name,
                'channel_type'       => $m->channel_type,
                'is_regex'           => (bool) $m->is_regex,
                'priority'           => (int) ($m->priority ?? 100),
            ])
            ->values()
            ->all();

        Cache::put($cacheKey, $rows, self::CACHE_TTL);

        return $rows;
    }

    /**
     * Test whether a row's source pattern matches $source.
     *
     * For is_regex=true rows the stored pattern is a PCRE pattern without delimiters.
     * Anchored as /^{pattern}$/i to prevent partial matches.
     * The @ suppresses warnings for malformed patterns (defence-in-depth; seeder validates).
     */
    private function sourceMatches(array $row, string $source): bool
    {
        if ($row['is_regex']) {
            return (bool) @preg_match('/^' . $row['utm_source_pattern'] . '$/i', $source);
        }

        return $row['utm_source_pattern'] === $source;
    }
}
