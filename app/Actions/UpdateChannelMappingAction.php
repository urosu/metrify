<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workspace;
use App\Services\Attribution\ChannelClassifierService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Create, update, or delete a channel mapping and invalidate caches + trigger reclassification.
 *
 * After any write, invalidates the ChannelMappingResolver Redis cache for the
 * workspace and dispatches ReclassifyOrdersForMappingJob so historical orders
 * are reclassified with the new mapping.
 *
 * Input:  Workspace, operation ('create'|'update'|'delete'), mapping data
 * Writes: channel_mappings; dispatches ReclassifyOrdersForMappingJob
 *
 * @see docs/planning/backend.md §2.2 (action spec)
 * @see docs/planning/backend.md §0 (rule 5: channel mapping changes trigger recalc)
 */
class UpdateChannelMappingAction
{
    /**
     * @param  array{id?: int, utm_source: string, utm_medium?: string|null, channel_name: string, channel_type: string, is_regex?: bool, priority?: int}  $data
     */
    public function create(Workspace $workspace, array $data): int
    {
        $id = (int) DB::table('channel_mappings')->insertGetId([
            'workspace_id' => $workspace->id,
            'utm_source'   => $data['utm_source'],
            'utm_medium'   => $data['utm_medium'] ?? null,
            'channel_name' => $data['channel_name'],
            'channel_type' => $data['channel_type'],
            'is_regex'     => $data['is_regex'] ?? false,
            'priority'     => $data['priority'] ?? 100,
            'created_by'   => $data['created_by'] ?? null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->afterWrite($workspace->id);

        return $id;
    }

    /**
     * @param  array{utm_source?: string, utm_medium?: string|null, channel_name?: string, channel_type?: string, is_regex?: bool, priority?: int}  $data
     */
    public function update(Workspace $workspace, int $mappingId, array $data): void
    {
        DB::table('channel_mappings')
            ->where('id', $mappingId)
            ->where('workspace_id', $workspace->id)
            ->update(array_merge($data, ['updated_at' => now()]));

        $this->afterWrite($workspace->id);
    }

    public function delete(Workspace $workspace, int $mappingId): void
    {
        DB::table('channel_mappings')
            ->where('id', $mappingId)
            ->where('workspace_id', $workspace->id)
            ->delete();

        $this->afterWrite($workspace->id);
    }

    private function afterWrite(int $workspaceId): void
    {
        Cache::forget(ChannelClassifierService::cacheKey($workspaceId));

        dispatch(new \App\Jobs\ReclassifyOrdersForMappingJob($workspaceId));
    }
}
