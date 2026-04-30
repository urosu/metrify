<?php

declare(strict_types=1);

namespace App\Services\Attribution\Sources;

use App\Contracts\AttributionSource;
use App\Models\Order;
use App\ValueObjects\ParsedAttribution;
use Illuminate\Support\Facades\DB;

/**
 * Attribution source that reads ga4_order_attribution by transaction_id.
 *
 * GA4 is a high-priority fallback: it fires after PYS, Shopify journey/landing
 * sources, and WooCommerceNativeSource, but before ReferrerHeuristicSource (the
 * weakest tier). Its key strength is providing *distinct* first-touch vs last-touch
 * for WC native single-touch orders, where WC only records one session.
 *
 * This source is query-based: it performs one DB lookup per order.
 * It is only effective after SyncGA4OrderAttributionJob has populated
 * ga4_order_attribution for the order's transaction_id.
 *
 * Match key: orders.external_id = ga4_order_attribution.transaction_id
 *   (GA4 receives the platform order ID as the transactionId ecommerce event parameter)
 *
 * Priority position: 4 — after WooCommerceNativeSource, before ReferrerHeuristicSource.
 *
 * Reads: ga4_order_attribution (by transaction_id + workspace_id).
 * Writes: nothing.
 * Called by: AttributionParserService.
 *
 * @see PLANNING.md section 6 (attribution source priority)
 * @see app/Jobs/SyncGA4OrderAttributionJob.php (populates the lookup table)
 */
class GA4Source implements AttributionSource
{
    public function tryParse(Order $order): ?ParsedAttribution
    {
        $transactionId = $order->external_id ?? null;

        if ($transactionId === null || $transactionId === '') {
            return null;
        }

        // Bypass global workspace scope — this source is called from jobs that
        // may have set a different scope context. The workspace_id check is explicit.
        $row = DB::table('ga4_order_attribution')
            ->where('workspace_id', $order->workspace_id)
            ->where('transaction_id', (string) $transactionId)
            ->first();

        if ($row === null) {
            return null;
        }

        // Build first-touch from firstUser* dimensions (true first-touch at user level).
        $firstSource = $row->first_user_source ?? null;
        if ($firstSource !== null && $firstSource !== '') {
            $firstTouch = ['source' => $firstSource];

            if (! empty($row->first_user_medium)) {
                $firstTouch['medium'] = $row->first_user_medium;
            }

            if (! empty($row->first_user_campaign)) {
                $firstTouch['campaign'] = $row->first_user_campaign;
            }

            if (! empty($row->landing_page)) {
                $firstTouch['landing_page'] = $row->landing_page;
            }
        } else {
            $firstTouch = null;
        }

        // Build last-touch from session* dimensions (session that resulted in purchase).
        $lastSource = $row->session_source ?? null;
        if ($lastSource !== null && $lastSource !== '') {
            $lastTouch = ['source' => $lastSource];

            if (! empty($row->session_medium)) {
                $lastTouch['medium'] = $row->session_medium;
            }

            if (! empty($row->session_campaign)) {
                $lastTouch['campaign'] = $row->session_campaign;
            }

            if (! empty($row->landing_page)) {
                $lastTouch['landing_page'] = $row->landing_page;
            }
        } else {
            $lastTouch = null;
        }

        if ($firstTouch === null && $lastTouch === null) {
            return null;
        }

        // Mirror whichever touch is available for single-touch case.
        $firstTouch ??= $lastTouch;
        $lastTouch  ??= $firstTouch;

        return new ParsedAttribution(
            source_type:  'ga4',
            first_touch:  $firstTouch,
            last_touch:   $lastTouch,
            click_ids:    null,
            channel:      null,
            channel_type: null,
            raw_data:     null,
        );
    }
}
