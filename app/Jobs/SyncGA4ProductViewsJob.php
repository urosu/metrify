<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\GA4PropertyNotFoundException;
use App\Exceptions\GA4QuotaExceededException;
use App\Exceptions\GoogleTokenExpiredException;
use App\Models\Ga4Property;
use App\Models\IntegrationCredential;
use App\Services\Integrations\Google\GA4Client;
use App\Services\WorkspaceContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Nightly pull of GA4 enhanced ecommerce item-level event counts into ga4_product_page_views.
 *
 * Fetches itemViews, itemsAddedToCart, itemsPurchased per (date, itemName, itemId)
 * from the GA4 Data API. Requires the store to have GA4 enhanced ecommerce tracking
 * with view_item / add_to_cart / purchase events.
 *
 * Syncs a rolling 3-day window by default (provisional data may be revised by GA4).
 * When $backfillDays > 0, syncs that many extra days for initial backfill.
 *
 * Row identity is tracked via `row_signature` — a SHA-256 hash of (date + item_name + item_id).
 *
 * Queue:    sync-google-analytics
 * Schedule: nightly 05:15 UTC per active GA4 property (staggered 15 min after order attribution)
 * Timeout:  600 s
 * Tries:    3
 * Unique:   yes — one run per property
 *
 * Dispatched by: schedule (routes/console.php → per active ga4_property)
 *
 * @see docs/planning/backend.md §4 (connector spec)
 * @see app/Services/Integrations/Google/GA4Client.php::fetchProductPageViews()
 */
class SyncGA4ProductViewsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout   = 600;
    public int $tries     = 3;
    public int $uniqueFor = 3600;

    /** Rolling window — re-syncs last N days on every run (data_state=provisional). */
    private const ROLLING_DAYS = 3;

    public function __construct(
        public readonly int $ga4PropertyId,
        public readonly int $workspaceId,
        /** Extra days to back-fill beyond the rolling window. 0 = normal nightly run. */
        public readonly int $backfillDays = 0,
    ) {
        $this->onQueue('sync-google-analytics');
    }

    public function uniqueId(): string
    {
        return (string) $this->ga4PropertyId;
    }

    public function handle(WorkspaceContext $context): void
    {
        $context->set($this->workspaceId);

        $property = Ga4Property::withoutGlobalScopes()->find($this->ga4PropertyId);

        if ($property === null || $property->status === 'disconnected') {
            return;
        }

        $credential = IntegrationCredential::withoutGlobalScopes()
            ->where('integrationable_type', Ga4Property::class)
            ->where('integrationable_id', $this->ga4PropertyId)
            ->first();
        if ($credential?->is_seeded) {
            Log::info('SyncGA4ProductViewsJob: skipping seeded credential', ['ga4_property_id' => $this->ga4PropertyId]);
            return;
        }

        $to        = Carbon::yesterday();
        $totalDays = self::ROLLING_DAYS + $this->backfillDays;
        $from      = $to->copy()->subDays($totalDays - 1);

        try {
            $client = GA4Client::forProperty($property);
            $rows   = $client->fetchProductPageViews($from->toDateString(), $to->toDateString());
        } catch (GA4QuotaExceededException $e) {
            Log::warning('SyncGA4ProductViewsJob: quota exceeded', [
                'property_id' => $this->ga4PropertyId,
                'error'       => $e->getMessage(),
            ]);
            $property->update([
                'status'                    => 'error',
                'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1'),
            ]);
            $this->fail($e);
            return;
        } catch (GA4PropertyNotFoundException $e) {
            Log::warning('SyncGA4ProductViewsJob: property not found', [
                'property_id' => $this->ga4PropertyId,
                'error'       => $e->getMessage(),
            ]);
            $property->update([
                'status'                    => 'error',
                'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1'),
            ]);
            return;
        } catch (GoogleTokenExpiredException $e) {
            Log::warning('SyncGA4ProductViewsJob: token expired or revoked', [
                'property_id' => $this->ga4PropertyId,
            ]);
            $property->update([
                'status'                    => 'error',
                'consecutive_sync_failures' => DB::raw('consecutive_sync_failures + 1'),
            ]);
            $this->fail($e);
            return;
        }

        if (empty($rows)) {
            Log::info('SyncGA4ProductViewsJob: no rows returned', [
                'property_id' => $this->ga4PropertyId,
                'from'        => $from->toDateString(),
                'to'          => $to->toDateString(),
            ]);
            return;
        }

        $now        = now()->toDateTimeString();
        $upsertRows = [];

        foreach ($rows as $r) {
            $signature = $this->rowSignature($r->date, $r->item_name, $r->item_id);

            $upsertRows[] = [
                'workspace_id'        => $this->workspaceId,
                'ga4_property_id'     => $this->ga4PropertyId,
                'date'                => $r->date,
                'item_name'           => $r->item_name,
                'item_id'             => $r->item_id,
                'item_views'          => $r->item_views,
                'items_added_to_cart' => $r->items_added_to_cart,
                'items_purchased'     => $r->items_purchased,
                'row_signature'       => $signature,
                'data_state'          => $r->data_state,
                'synced_at'           => $now,
                'created_at'          => $now,
            ];
        }

        foreach (array_chunk($upsertRows, 500) as $batch) {
            DB::table('ga4_product_page_views')->upsert(
                $batch,
                ['ga4_property_id', 'date', 'row_signature'],
                ['item_views', 'items_added_to_cart', 'items_purchased', 'data_state', 'synced_at'],
            );
        }

        $property->update([
            'status'                    => 'active',
            'consecutive_sync_failures' => 0,
            'last_synced_at'            => now(),
        ]);

        Log::info('SyncGA4ProductViewsJob: completed', [
            'property_id' => $this->ga4PropertyId,
            'rows'        => count($upsertRows),
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
        ]);
    }

    /**
     * Build a deterministic 64-char SHA-256 signature for (date + item_name + item_id).
     * Null values use the '∅' sentinel so (null, 'X') ≠ ('', 'X').
     */
    private function rowSignature(string $date, ?string $itemName, ?string $itemId): string
    {
        $key = implode("\x00", [
            $date,
            $itemName ?? '∅',
            $itemId   ?? '∅',
        ]);

        return hash('sha256', $key);
    }
}
