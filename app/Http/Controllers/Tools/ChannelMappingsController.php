<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Jobs\RecomputeAttributionJob;
use App\Jobs\ReclassifyOrdersForMappingJob;
use App\Models\ChannelMapping;
use App\Models\Workspace;
use App\Services\Attribution\ChannelClassifierService;
use App\Services\JobLockChecker;
use App\Services\WorkspaceContext;
use Database\Seeders\ChannelMappingsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Workspace channel mappings CRUD — renders at /tools/channel-mappings.
 *
 * Shows workspace-scoped overrides + global seeded defaults. Unrecognised
 * (source, medium) pairs from the last 90 days are surfaced so users can
 * map them in one click. The test widget runs the classifier client-side.
 *
 * Creating/updating/deleting a workspace-scoped override dispatches
 * {@see ReclassifyOrdersForMappingJob} to re-stamp historical orders.
 *
 * Pattern: Triple Whale source config table + Northbeam if-then rule view.
 *
 * Reads: channel_mappings, orders (attribution_last_touch).
 * Writes: channel_mappings (workspace-scoped rows).
 * Called by: /tools/channel-mappings routes.
 *
 * @see docs/planning/backend.md §6
 * @see docs/competitors/_research_tools_utilities.md §2
 */
class ChannelMappingsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $workspaceId = app(WorkspaceContext::class)->id();

        $workspaceRows = ChannelMapping::where('workspace_id', $workspaceId)
            ->orderBy('channel_type')
            ->orderBy('utm_source_pattern')
            ->orderBy('utm_medium_pattern')
            ->get()
            ->map(fn (ChannelMapping $m) => $this->toRow($m, false))
            ->all();

        $globalRows = ChannelMapping::whereNull('workspace_id')
            ->orderBy('channel_type')
            ->orderBy('utm_source_pattern')
            ->orderBy('utm_medium_pattern')
            ->get()
            ->map(fn (ChannelMapping $m) => $this->toRow($m, true))
            ->all();

        // Top unclassified (source, medium) pairs — last 90 days, this workspace.
        $unrecognized = DB::select(
            <<<'SQL'
                SELECT
                    LOWER(attribution_last_touch->>'source')  AS source,
                    LOWER(attribution_last_touch->>'medium')  AS medium,
                    COUNT(*)                                   AS order_count,
                    COALESCE(SUM(total_in_reporting_currency), 0) AS revenue
                FROM orders
                WHERE workspace_id = ?
                  AND status IN ('completed', 'processing')
                  AND attribution_source IN ('pys', 'wc_native')
                  AND attribution_last_touch IS NOT NULL
                  AND attribution_last_touch->>'source' IS NOT NULL
                  AND (attribution_last_touch->>'channel' IS NULL OR attribution_last_touch->>'channel' = '')
                  AND occurred_at >= NOW() - INTERVAL '90 days'
                GROUP BY 1, 2
                ORDER BY order_count DESC
                LIMIT 20
            SQL,
            [$workspaceId],
        );

        return Inertia::render('Tools/ChannelMappings', [
            'workspace_mappings' => $workspaceRows,
            'global_mappings'    => $globalRows,
            'unrecognized'       => array_map(fn ($r) => [
                'source'      => $r->source,
                'medium'      => $r->medium,
                'order_count' => (int) $r->order_count,
                'revenue'     => round((float) $r->revenue, 2),
            ], $unrecognized),
            'is_recomputing'     => app(JobLockChecker::class)->isLocked(RecomputeAttributionJob::class, $workspaceId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $validated   = $this->validateMapping($request);

        [$source, $medium] = $this->normalize($validated);

        try {
            ChannelMapping::create([
                'workspace_id'       => $workspaceId,
                'utm_source_pattern' => $source,
                'utm_medium_pattern' => $medium,
                'channel_name'       => $validated['channel_name'],
                'channel_type'       => $validated['channel_type'],
                'is_global'          => false,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), '23505')) {
                return back()->withErrors([
                    'utm_source_pattern' => 'A mapping for this source/medium combination already exists in this workspace.',
                ]);
            }
            throw $e;
        }

        $this->invalidateMappingCache($workspaceId);

        ReclassifyOrdersForMappingJob::dispatch(
            $workspaceId,
            $source,
            $medium,
            $validated['channel_name'],
            $validated['channel_type'],
        );

        return back()->with('success', 'Mapping created. Historical orders will be reclassified in the background.');
    }

    public function update(Request $request, ChannelMapping $channelMapping): RedirectResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        abort_unless($channelMapping->workspace_id === $workspaceId, 404);

        $validated = $this->validateMapping($request);
        [$source, $medium] = $this->normalize($validated);

        try {
            $channelMapping->update([
                'utm_source_pattern' => $source,
                'utm_medium_pattern' => $medium,
                'channel_name'       => $validated['channel_name'],
                'channel_type'       => $validated['channel_type'],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), '23505')) {
                return back()->withErrors([
                    'utm_source_pattern' => 'A mapping for this source/medium combination already exists in this workspace.',
                ]);
            }
            throw $e;
        }

        $this->invalidateMappingCache($workspaceId);

        ReclassifyOrdersForMappingJob::dispatch(
            $workspaceId,
            $source,
            $medium,
            $validated['channel_name'],
            $validated['channel_type'],
        );

        return back()->with('success', 'Mapping updated. Historical orders will be reclassified in the background.');
    }

    public function destroy(ChannelMapping $channelMapping): RedirectResponse
    {
        abort_unless($channelMapping->workspace_id === app(WorkspaceContext::class)->id(), 404);

        $name        = $channelMapping->channel_name;
        $workspaceId = $channelMapping->workspace_id;
        $channelMapping->delete();

        $this->invalidateMappingCache($workspaceId);

        return back()->with('success', "Mapping deleted: {$name}");
    }

    /**
     * Re-seed global defaults (owner-only). Truncates + reinserts the ~40 global
     * rows; workspace overrides are preserved. Busts the classifier cache for every workspace.
     */
    public function importDefaults(Request $request): RedirectResponse
    {
        $user        = $request->user();
        $workspaceId = app(WorkspaceContext::class)->id();

        $role = DB::table('workspace_users')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $user->id)
            ->value('role');

        abort_unless($role === 'owner', 403, 'Only workspace owners can re-seed defaults.');

        (new ChannelMappingsSeeder())->run();

        $workspaceKeys = Workspace::query()->pluck('id')
            ->map(fn (int $id) => ChannelClassifierService::cacheKey($id))
            ->all();

        Cache::deleteMultiple(array_merge($workspaceKeys, [ChannelClassifierService::GLOBAL_CACHE_KEY]));

        return back()->with('success', 'Global channel mapping defaults re-seeded.');
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function toRow(ChannelMapping $m, bool $isGlobal): array
    {
        return [
            'id'                 => $m->id,
            'utm_source_pattern' => $m->utm_source_pattern,
            'utm_medium_pattern' => $m->utm_medium_pattern,
            'channel_name'       => $m->channel_name,
            'channel_type'       => $m->channel_type,
            'is_global'          => $isGlobal,
        ];
    }

    /** @return array<string, mixed> */
    private function validateMapping(Request $request): array
    {
        return $request->validate([
            'utm_source_pattern' => ['required', 'string', 'max:255'],
            'utm_medium_pattern' => ['nullable', 'string', 'max:255'],
            'channel_name'       => ['required', 'string', 'max:120'],
            'channel_type'       => ['required', 'string', 'in:email,paid_social,paid_search,organic_search,organic_social,direct,referral,affiliate,sms,other'],
        ]);
    }

    /** @param array<string, mixed> $validated  @return array{0:string,1:?string} */
    private function normalize(array $validated): array
    {
        $source = strtolower(trim($validated['utm_source_pattern']));
        $medium = isset($validated['utm_medium_pattern']) && $validated['utm_medium_pattern'] !== ''
            ? strtolower(trim($validated['utm_medium_pattern']))
            : null;

        return [$source, $medium];
    }

    private function invalidateMappingCache(int $workspaceId): void
    {
        Cache::deleteMultiple([
            ChannelClassifierService::cacheKey($workspaceId),
            ChannelClassifierService::GLOBAL_CACHE_KEY,
        ]);
    }
}
