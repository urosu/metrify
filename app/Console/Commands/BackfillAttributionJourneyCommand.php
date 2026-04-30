<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Workspace;
use App\Services\Attribution\AttributionJourneyBuilder;
use App\Services\WorkspaceContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Synchronously backfill orders.attribution_journey for orders that are missing it.
 *
 * This is a one-shot CLI tool for first-pass population. Processes orders
 * in batches and writes attribution_journey in one SQL statement per batch.
 *
 * Usage:
 *   php artisan attribution:backfill-journey
 *   php artisan attribution:backfill-journey --workspace=42
 *   php artisan attribution:backfill-journey --workspace=42 --limit=10000
 *
 * Exit codes: 0 success, 1 on error.
 *
 * Writes: orders.attribution_journey
 * Called by: CLI
 *
 * @see app/Services/Attribution/AttributionJourneyBuilder.php
 * @see app/Jobs/BuildAttributionJourneyJob.php (queue variant)
 */
class BackfillAttributionJourneyCommand extends Command
{
    protected $signature = 'attribution:backfill-journey
        {--workspace= : Workspace ID; omit to process all workspaces}
        {--limit=10000 : Max orders to process (across all workspaces)}';

    protected $description = 'Backfill orders.attribution_journey for orders missing a journey.';

    /** Orders updated per DB batch. Mirrors BuildAttributionJourneyJob::BATCH_SIZE. */
    private const BATCH_SIZE = 500;

    public function handle(
        AttributionJourneyBuilder $builder,
        WorkspaceContext $context,
    ): int {
        $workspaceId = $this->option('workspace') !== null
            ? (int) $this->option('workspace')
            : null;

        $limit = (int) ($this->option('limit') ?? 10000);

        $workspaces = $this->resolveWorkspaces($workspaceId);

        if ($workspaces->isEmpty()) {
            $this->error('No matching workspaces found.');
            return self::FAILURE;
        }

        $totalProcessed = 0;
        $totalPopulated = 0;
        $totalNoSignal  = 0;

        foreach ($workspaces as $workspace) {
            $context->set($workspace->id);

            $missing = Order::withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->whereNull('attribution_journey')
                ->count();

            if ($missing === 0) {
                $this->line("[workspace:{$workspace->id}] no orders missing journey — skipped.");
                continue;
            }

            $wsLimit = min($missing, max(0, $limit - $totalProcessed));

            if ($wsLimit <= 0) {
                $this->line("[workspace:{$workspace->id}] global limit reached — skipped.");
                break;
            }

            $this->line("[workspace:{$workspace->id}] processing {$wsLimit} of {$missing} missing orders...");

            $populated = 0;
            $noSignal  = 0;
            $processed = 0;

            Order::withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->whereNull('attribution_journey')
                ->select([
                    'id', 'workspace_id', 'external_id',
                    'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
                    'source_type', 'raw_meta', 'platform_data', 'occurred_at',
                ])
                ->orderBy('id')
                ->limit($wsLimit)
                ->chunk(self::BATCH_SIZE, function ($orders) use ($builder, &$populated, &$noSignal, &$processed): void {
                    $now  = now()->toDateTimeString();
                    $rows = [];

                    foreach ($orders as $order) {
                        $journey = $builder->buildForOrder($order);

                        $rows[] = [
                            'id'                  => $order->id,
                            'attribution_journey' => json_encode(empty($journey) ? [] : $journey),
                            'updated_at'          => $now,
                        ];

                        if (empty($journey)) {
                            $noSignal++;
                        } else {
                            $populated++;
                        }
                        $processed++;
                    }

                    if (empty($rows)) {
                        return;
                    }

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

            $this->info(
                "[workspace:{$workspace->id}] done. " .
                "populated={$populated}, no_signal={$noSignal}"
            );

            $totalProcessed += $processed;
            $totalPopulated += $populated;
            $totalNoSignal  += $noSignal;

            if ($totalProcessed >= $limit) {
                $this->warn('Global limit reached — stopping.');
                break;
            }
        }

        $this->info("Backfill complete. total_populated={$totalPopulated}, total_no_signal={$totalNoSignal}");

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Workspace>
     */
    private function resolveWorkspaces(?int $workspaceId): \Illuminate\Support\Collection
    {
        if ($workspaceId !== null) {
            return Workspace::withoutGlobalScopes()
                ->where('id', $workspaceId)
                ->whereNull('deleted_at')
                ->get();
        }

        return Workspace::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();
    }
}
