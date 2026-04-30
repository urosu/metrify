<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Services\Attribution\AttributionJourneyBuilder;
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
 * Populates orders.attribution_journey for all orders in a workspace that are
 * missing the column (attribution_journey IS NULL).
 *
 * Runs in batches of 500. Each order is processed through AttributionJourneyBuilder,
 * which walks 5 confidence tiers: Shopify moments → GA4 → PYS → WC native → referrer.
 *
 * This job is intentionally additive — it does not touch attribution_first_touch,
 * attribution_last_touch, or attribution_source (owned by AttributionParserService /
 * BackfillAttributionDataJob). attribution_journey is additional context for the
 * linear / position_based / time_decay models in AttributionDataService.
 *
 * Queue:      attribution
 * Timeout:    3600 s (1 hour)
 * Tries:      3
 * Unique:     yes — one run per workspace at a time
 *
 * Multi-tenant note: jobs don't inherit request scope. We call
 * app(WorkspaceContext::class)->set($workspaceId) at the top of handle() and
 * use withoutGlobalScopes() + explicit where('workspace_id') throughout.
 *
 * Scheduled: nightly at 03:00 UTC in routes/console.php (after snapshot builds).
 * Also dispatched by RecomputeAttributionJob when journey logic changes.
 *
 * Dispatched by: routes/console.php (nightly), RecomputeAttributionJob,
 *               BackfillAttributionJourneyCommand (synchronous CLI variant)
 *
 * @see app/Services/Attribution/AttributionJourneyBuilder.php
 * @see docs/planning/backend.md §7 (attribution pipeline)
 */
class BuildAttributionJourneyJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 3600;
    public int $tries     = 3;
    public int $uniqueFor = 3660;

    /**
     * 500 orders per DB batch. Each order may join ga4_order_attribution (one
     * DB round-trip per order), so keep this below 1000 to avoid long lock windows.
     */
    private const BATCH_SIZE = 500;

    public function __construct(
        public readonly int $workspaceId,
    ) {
        $this->onQueue('attribution');
    }

    public function uniqueId(): string
    {
        return (string) $this->workspaceId;
    }

    public function handle(AttributionJourneyBuilder $builder): void
    {
        app(WorkspaceContext::class)->set($this->workspaceId);

        $total = Order::withoutGlobalScopes()
            ->where('workspace_id', $this->workspaceId)
            ->whereNull('attribution_journey')
            ->count();

        Log::info('BuildAttributionJourneyJob: started', [
            'workspace_id'   => $this->workspaceId,
            'orders_missing' => $total,
        ]);

        $processed = 0;
        $skipped   = 0;

        Order::withoutGlobalScopes()
            ->where('workspace_id', $this->workspaceId)
            ->whereNull('attribution_journey')
            ->select([
                'id', 'workspace_id', 'external_id',
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
                'source_type', 'raw_meta', 'platform_data', 'occurred_at',
            ])
            ->orderBy('id')
            ->chunk(self::BATCH_SIZE, function ($orders) use ($builder, &$processed, &$skipped): void {
                $now  = now()->toDateTimeString();
                $rows = [];

                foreach ($orders as $order) {
                    $journey = $builder->buildForOrder($order);

                    if (empty($journey)) {
                        // Write an explicit empty array so we don't reprocess on next run.
                        $rows[] = [
                            'id'                  => $order->id,
                            'attribution_journey' => '[]',
                            'updated_at'          => $now,
                        ];
                        $skipped++;
                    } else {
                        $rows[] = [
                            'id'                  => $order->id,
                            'attribution_journey' => json_encode($journey),
                            'updated_at'          => $now,
                        ];
                        $processed++;
                    }
                }

                if (empty($rows)) {
                    return;
                }

                // Single UPDATE … FROM VALUES for the whole batch — one round-trip.
                $placeholders = implode(', ', array_fill(0, count($rows), '(?, ?, ?)'));
                $bindings     = [];
                foreach ($rows as $row) {
                    $bindings[] = $row['id'];
                    $bindings[] = $row['attribution_journey'];
                    $bindings[] = $row['updated_at'];
                }

                DB::statement("
                    UPDATE orders AS o
                    SET attribution_journey = v.attribution_journey::jsonb,
                        updated_at          = v.updated_at::timestamp
                    FROM (VALUES {$placeholders}) AS v(id, attribution_journey, updated_at)
                    WHERE o.id = v.id::bigint
                ", $bindings);
            });

        Log::info('BuildAttributionJourneyJob: completed', [
            'workspace_id' => $this->workspaceId,
            'populated'    => $processed,
            'no_signal'    => $skipped,
        ]);
    }
}
