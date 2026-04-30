<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Attribution\AttributionParserService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin attribution debug — full parser pipeline trace for a single order.
 *
 * Purpose: Runs AttributionParserService::debug() on an arbitrary order and
 *          renders every source's match/skip decision. Used during beta to
 *          diagnose PYS vs WC-native misattribution.
 *
 * Reads:  orders (withoutGlobalScopes — any workspace), AttributionParserService pipeline.
 * Writes: nothing.
 * Callers: routes/web.php admin group (GET /admin/attribution-debug/{orderId}).
 *
 * @see docs/planning/backend.md#6
 */
class AdminAttributionDebugController extends Controller
{
    public function __invoke(int $orderId, AttributionParserService $parser): Response
    {
        $order = Order::withoutGlobalScopes()->with(['store', 'workspace'])->findOrFail($orderId);

        $pipeline = $parser->debug($order);

        // Serialise ParsedAttribution objects for the Inertia payload.
        $pipelineData = array_map(static function (array $step): array {
            $result = $step['result'];

            return [
                'source'  => $step['source'],
                'matched' => $step['matched'],
                'skipped' => $step['skipped'] ?? false,
                'result'  => $result === null ? null : [
                    'source_type'  => $result->source_type,
                    'first_touch'  => $result->first_touch,
                    'last_touch'   => $result->last_touch,
                    'click_ids'    => $result->click_ids,
                    'channel'      => $result->channel,
                    'channel_type' => $result->channel_type,
                    'raw_data'     => $result->raw_data,
                ],
            ];
        }, $pipeline);

        return Inertia::render('Admin/AttributionDebug', [
            'order' => [
                'id'               => $order->id,
                'external_id'      => $order->external_id,
                'occurred_at'      => $order->occurred_at?->toISOString(),
                'workspace_id'     => $order->workspace_id,
                'store_name'       => $order->store?->name,
                'utm_source'       => $order->utm_source,
                'utm_medium'       => $order->utm_medium,
                'utm_campaign'     => $order->utm_campaign,
                'source_type'      => $order->source_type,
                'attribution_source' => $order->attribution_source,
                'raw_meta_keys'    => is_array($order->raw_meta) ? array_keys($order->raw_meta) : [],
            ],
            'pipeline' => $pipelineData,
        ]);
    }
}
