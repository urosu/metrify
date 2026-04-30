<?php

declare(strict_types=1);

namespace App\Services\PixelEvents;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Correlates pixel sessions with orders for `clicks_modeled` attribution.
 *
 * Correlation strategy (cheapest-first):
 *
 *  1. session_id match — if an order carries a `pixel_session_id` in its meta
 *     (orders.utm_content or order_metafields key='pixel_session_id'), match
 *     directly by session_id. This is the fast path once storefront integration
 *     is live and the snippet populates the session cookie.
 *
 *  2. IP + date proximity — fallback heuristic: match the order's created_at
 *     to the closest `begin_checkout` or `purchase` pixel event within the same
 *     workspace, same IP, within a ±2-hour window.
 *     False-positive rate is higher for shared IPs (offices, NAT) — production
 *     use should combine with user_agent matching for additional signal.
 *
 * Output: writes rows to `pixel_order_correlations`. The `clicks_modeled`
 * attribution column (to be added to daily_snapshots) reads aggregate counts
 * from this table rather than computing from raw events on every request.
 *
 * @see database/migrations/2026_04_29_000014_create_pixel_order_correlations_table.php
 * @see app/Models/PixelEvent.php
 */
class EventOrderCorrelator
{
    /**
     * Correlate pixel sessions to orders within the given time window.
     *
     * @param  int     $workspaceId
     * @param  Carbon  $from
     * @param  Carbon  $to
     * @return int     Number of new correlations written.
     */
    public function correlate(int $workspaceId, Carbon $from, Carbon $to): int
    {
        $written = 0;

        // ── Pass 1: session_id match ──────────────────────────────────────────
        // Requires the storefront snippet to store session_id in order meta.
        // Currently zero matches until the snippet populates pixel_session_id.
        $sessionMatches = $this->findSessionIdMatches($workspaceId, $from, $to);
        $written       += $this->persistCorrelations($sessionMatches, 'session_id');

        // ── Pass 2: IP + date proximity ───────────────────────────────────────
        $ipMatches = $this->findIpProximityMatches($workspaceId, $from, $to);
        $written  += $this->persistCorrelations($ipMatches, 'ip_proximity');

        Log::info('EventOrderCorrelator: completed', [
            'workspace_id' => $workspaceId,
            'from'         => $from->toDateTimeString(),
            'to'           => $to->toDateTimeString(),
            'written'      => $written,
        ]);

        return $written;
    }

    // ── Private: Pass 1 ───────────────────────────────────────────────────────

    /**
     * Match orders that have a pixel_session_id metafield to the corresponding
     * pixel session's last `begin_checkout` or `purchase` event.
     *
     * @return Collection<array{order_id: int, pixel_event_id: int, session_id: string}>
     */
    private function findSessionIdMatches(int $workspaceId, Carbon $from, Carbon $to): Collection
    {
        // Joins order_metafields (key='pixel_session_id') → pixel_events.session_id.
        // Uses DB::table() to bypass WorkspaceScope — this service runs in a job
        // that sets WorkspaceContext, but raw queries are safer here for clarity.
        return DB::table('orders AS o')
            ->join('order_metafields AS m', function ($j): void {
                $j->on('m.order_id', '=', 'o.id')
                  ->where('m.key', '=', 'pixel_session_id');
            })
            ->join('pixel_events AS pe', function ($j): void {
                $j->on('pe.session_id', '=', 'm.value')
                  ->on('pe.workspace_id', '=', 'o.workspace_id')
                  ->whereIn('pe.event_type', ['begin_checkout', 'purchase']);
            })
            ->where('o.workspace_id', $workspaceId)
            ->whereBetween('o.created_at', [$from, $to])
            ->select([
                'o.id AS order_id',
                'pe.id AS pixel_event_id',
                DB::raw("m.value AS session_id"),
            ])
            ->get()
            ->map(fn ($r) => [
                'order_id'       => $r->order_id,
                'pixel_event_id' => $r->pixel_event_id,
                'session_id'     => $r->session_id,
            ]);
    }

    // ── Private: Pass 2 ───────────────────────────────────────────────────────

    /**
     * Match orders to pixel events by temporal proximity within the same workspace.
     *
     * Finds unmatched orders and looks for a `purchase` or `begin_checkout` pixel
     * event that occurred within ±30 minutes of the order's occurred_at/created_at.
     * When multiple pixel events fall in the window the closest one wins.
     *
     * Note: `orders` does not carry an IP address column, so IP-based heuristics
     * are not available at the order level. A future improvement is to store the
     * customer's hashed email or click IDs in pixel_events.payload and match on
     * those instead. For now, temporal proximity gives a usable first-pass signal
     * for low-volume single-store workspaces.
     *
     * Excludes orders already matched in Pass 1 or in a prior correlator run.
     *
     * @return Collection<array{order_id: int, pixel_event_id: int, session_id: string|null}>
     */
    private function findIpProximityMatches(int $workspaceId, Carbon $from, Carbon $to): Collection
    {
        // Already-correlated order IDs (from any previous run, not just Pass 1).
        $alreadyMatched = DB::table('pixel_order_correlations')
            ->where('workspace_id', $workspaceId)
            ->pluck('order_id')
            ->all();

        // Use occurred_at if available, fall back to created_at.
        $orders = DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('created_at', [$from, $to])
            ->when(count($alreadyMatched) > 0, fn ($q) => $q->whereNotIn('id', $alreadyMatched))
            ->select(['id', DB::raw('COALESCE(occurred_at, created_at) AS order_ts')])
            ->get();

        $results = collect();

        foreach ($orders as $order) {
            // Find the closest checkout/purchase pixel event within ±30 minutes.
            $orderTs = Carbon::parse($order->order_ts);

            $match = DB::table('pixel_events')
                ->where('workspace_id', $workspaceId)
                ->whereIn('event_type', ['begin_checkout', 'purchase'])
                ->whereBetween('occurred_at', [
                    $orderTs->copy()->subMinutes(30),
                    $orderTs->copy()->addMinutes(30),
                ])
                ->orderByRaw('ABS(EXTRACT(EPOCH FROM (occurred_at - ?::timestamptz)))', [$order->order_ts])
                ->select(['id', 'session_id'])
                ->first();

            if ($match !== null) {
                $results->push([
                    'order_id'       => $order->id,
                    'pixel_event_id' => $match->id,
                    'session_id'     => $match->session_id,
                ]);
            }
        }

        return $results;
    }

    // ── Private: persist ──────────────────────────────────────────────────────

    /**
     * @param  Collection<array{order_id: int, pixel_event_id: int, session_id: string|null}>  $correlations
     * @param  string  $method  'session_id' | 'ip_proximity'
     * @return int  Rows inserted.
     */
    private function persistCorrelations(Collection $correlations, string $method): int
    {
        $written = 0;
        $now     = now()->toDateTimeString();

        foreach ($correlations as $c) {
            $inserted = DB::table('pixel_order_correlations')->updateOrInsert(
                [
                    'order_id' => $c['order_id'],
                ],
                [
                    'workspace_id'   => DB::table('orders')->where('id', $c['order_id'])->value('workspace_id'),
                    'pixel_event_id' => $c['pixel_event_id'],
                    'session_id'     => $c['session_id'],
                    'method'         => $method,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]
            );

            if ($inserted) {
                $written++;
            }
        }

        return $written;
    }
}
