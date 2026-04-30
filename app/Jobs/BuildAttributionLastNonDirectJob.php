<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\WorkspaceContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Populates orders.attribution_last_non_direct for all eligible orders in a workspace.
 *
 * Logic: walk back from attribution_last_touch, skip any touch where
 * channel === 'direct' OR medium === '(none)'. The most recent remaining
 * touch is written to attribution_last_non_direct. If every touch is
 * direct / (none), the column is set to NULL.
 *
 * Because attribution_last_touch is a single-touch JSONB field (not a
 * journey array), "walk back from last_touch" means: if the last_touch
 * itself is non-direct, copy it; otherwise fall back to first_touch (if
 * non-direct); otherwise NULL. The full multi-touch journey is owned by
 * BuildAttributionJourneyJob (WS-A2) and is not available here.
 *
 * Processes orders in chunks of 500 (JSONB reads are cheap — larger chunk
 * than BackfillAttributionDataJob which reads raw_meta).
 *
 * Queue:     low
 * Timeout:   1800 s
 * Tries:     3
 * Unique:    yes — one run per workspace at a time
 *
 * Dispatched by: schedule (nightly 03:15 UTC), RecomputeAttributionJob
 *
 * @see docs/planning/backend.md §3.3 (job spec)
 * @see docs/planning/schema.md §1.4 orders (attribution_last_non_direct)
 */
class BuildAttributionLastNonDirectJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 1800;
    public int $tries     = 3;
    public int $uniqueFor = 1860;

    /**
     * Orders processed per DB chunk. Larger than BackfillAttributionDataJob because
     * this read is JSONB-only (no raw_meta blob).
     */
    private const CHUNK_SIZE = 500;

    public function __construct(public readonly int $workspaceId)
    {
        $this->onQueue('low');
    }

    public function uniqueId(): string
    {
        return (string) $this->workspaceId;
    }

    public function handle(): void
    {
        app(WorkspaceContext::class)->set($this->workspaceId);

        Log::info('BuildAttributionLastNonDirectJob: starting', ['workspace_id' => $this->workspaceId]);

        $processed = 0;
        $updated   = 0;

        DB::table('orders')
            ->where('workspace_id', $this->workspaceId)
            ->whereNotNull('attribution_last_touch')
            // Only process orders that haven't been populated yet; re-run clears and rewrites all.
            // We process all orders on every run so config changes are reflected immediately.
            ->select(['id', 'attribution_last_touch', 'attribution_first_touch'])
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($orders) use (&$processed, &$updated): void {
                $now = now()->toDateTimeString();
                $rows = [];

                foreach ($orders as $order) {
                    $lastNonDirect = $this->resolveLastNonDirect(
                        $this->decodeTouchJson($order->attribution_last_touch),
                        $this->decodeTouchJson($order->attribution_first_touch),
                    );

                    $rows[] = [
                        'id'                         => $order->id,
                        'attribution_last_non_direct' => $lastNonDirect !== null
                            ? json_encode($lastNonDirect)
                            : null,
                        'updated_at' => $now,
                    ];

                    $processed++;
                    if ($lastNonDirect !== null) {
                        $updated++;
                    }
                }

                // Bulk UPDATE via VALUES CTE.
                $placeholders = implode(', ', array_fill(0, count($rows), '(?, ?::jsonb, ?::timestamp)'));
                $bindings = [];
                foreach ($rows as $row) {
                    $bindings[] = $row['id'];
                    $bindings[] = $row['attribution_last_non_direct'];
                    $bindings[] = $row['updated_at'];
                }

                DB::statement("
                    UPDATE orders AS o
                    SET attribution_last_non_direct = v.attribution_last_non_direct,
                        updated_at                 = v.updated_at
                    FROM (VALUES {$placeholders}) AS v(id, attribution_last_non_direct, updated_at)
                    WHERE o.id = v.id::bigint
                      AND o.workspace_id = {$this->workspaceId}
                ", $bindings);
            });

        Log::info('BuildAttributionLastNonDirectJob: completed', [
            'workspace_id' => $this->workspaceId,
            'processed'    => $processed,
            'non_direct'   => $updated,
        ]);
    }

    /**
     * Resolve the most recent non-direct touch.
     *
     * Strategy:
     *  1. If last_touch exists and is non-direct → return it.
     *  2. Else if first_touch exists and is non-direct → return it.
     *  3. Else → null (all touches are direct / unattributed).
     *
     * A touch is "direct" when channel === 'direct' OR medium === '(none)'.
     *
     * @param array<string,mixed>|null $lastTouch
     * @param array<string,mixed>|null $firstTouch
     * @return array<string,mixed>|null
     */
    private function resolveLastNonDirect(?array $lastTouch, ?array $firstTouch): ?array
    {
        if ($lastTouch !== null && ! $this->isDirect($lastTouch)) {
            return $lastTouch;
        }

        if ($firstTouch !== null && ! $this->isDirect($firstTouch)) {
            return $firstTouch;
        }

        return null;
    }

    /**
     * Returns true when a touch should be skipped as "direct / no attribution".
     *
     * Criteria:
     *  - channel === 'direct'   (ChannelClassifier resolved this as Direct)
     *  - medium === '(none)'    (GA4/WC convention for direct sessions)
     *  - source === 'direct'    (ReferrerHeuristicSource direct mapping)
     */
    private function isDirect(array $touch): bool
    {
        $channel = strtolower((string) ($touch['channel'] ?? ''));
        $medium  = strtolower((string) ($touch['medium'] ?? ''));
        $source  = strtolower((string) ($touch['source'] ?? ''));

        return $channel === 'direct'
            || $medium  === '(none)'
            || $source  === 'direct';
    }

    /**
     * Decode a JSONB column value (string or already-decoded array) to an array.
     *
     * @return array<string,mixed>|null
     */
    private function decodeTouchJson(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
