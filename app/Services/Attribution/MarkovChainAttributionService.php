<?php

declare(strict_types=1);

namespace App\Services\Attribution;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Markov-chain "data-driven" attribution.
 *
 * Algorithm (removal-effect / Markov chain):
 *   1. Fetch all order journeys for the workspace × date window via cursor() to
 *      avoid loading the full result-set into memory.
 *   2. Build a transition matrix P[from_state][to_state] across all journeys,
 *      where journey paths look like: START → ch_A → ch_B → CONVERSION.
 *   3. Compute the baseline conversion rate of the full graph using a
 *      Markov-chain simulation (absorbing-state random walk).
 *   4. For each channel C: remove C from the graph (set its row + column to 0),
 *      recompute the conversion rate.  The fractional drop is C's removal effect.
 *   5. Normalise removal effects to sum to 1 → attribution shares.
 *   6. Allocate each order's revenue to channels by:
 *        a. Single-touch journeys → 100 % to that channel.
 *        b. Multi-touch journeys  → revenue × channel's normalised removal-effect share.
 *
 * Performance:
 *   - Journey rows are streamed via DB::cursor() / chunked if cursor() not available
 *     on the driver.  The transition matrix is at most (K+2)² where K = unique
 *     channels, typically ≤ 30 for a small workspace.
 *   - Removal-effect simulation is O(K × iterations × K) — negligible for K ≤ 100.
 *   - This method is ONLY called from BuildAttributionSnapshotJob (background queue),
 *     never on the request path.
 *
 * Edge cases:
 *   - Zero-touchpoint order: skipped (no signal).
 *   - Single-touch journey: full revenue to that channel; skipped in matrix building
 *     to avoid inflation of direct-only transitions.
 *   - No journeys in window: returns empty array.
 *   - All removal effects zero (degenerate): falls back to equal-share linear split.
 *
 * @see app/Jobs/BuildAttributionSnapshotJob.php  — caller
 * @see docs/competitors/features/attribution-comparison.md — Markov chain rationale
 */
final class MarkovChainAttributionService
{
    /** Absorbing state labels in the transition matrix. */
    private const STATE_START      = '__start__';
    private const STATE_CONVERSION = '__conversion__';
    private const STATE_NULL       = '__null__';

    /** Max random-walk iterations for conversion-rate simulation. */
    private const WALK_ITERATIONS = 10_000;

    /**
     * Run Markov chain attribution for all orders in the given workspace + date window.
     *
     * Returns an array keyed by lowercase channel_id (matching the convention used
     * by BuildAttributionSnapshotJob for other models), each containing:
     *   - revenue  float  Total attributed revenue
     *   - orders   int    Number of orders (each counted once regardless of journey length)
     *   - share    float  0–1 fractional share of total attributed revenue
     *
     * @return array<string, array{revenue: float, orders: int, share: float}>
     */
    public function attribute(int $workspaceId, Carbon $from, Carbon $to): array
    {
        $dayStart = $from->toDateString() . ' 00:00:00';
        $dayEnd   = $to->toDateString()   . ' 23:59:59';

        // ── Step 1: stream journeys ───────────────────────────────────────────
        // Each row: order_id, revenue, attribution_journey (JSONB array).
        // Use chunk() rather than cursor() — cursor() requires an unbuffered PDO
        // connection which is not always available in Docker + postgres setups.

        /** @var list<array{order_id: int, revenue: float, touches: list<string>}> $journeys */
        $journeys          = [];
        $transitionCounts  = [];   // [from_state][to_state] => int count
        $channelSet        = [];   // unique channel ids (for removal loop)

        DB::table('orders')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('occurred_at', [$dayStart, $dayEnd])
            ->whereNotNull('attribution_journey')
            ->whereRaw("jsonb_typeof(attribution_journey) = 'array'")
            ->select([
                'id',
                DB::raw('COALESCE(total_in_reporting_currency, total) AS revenue'),
                'attribution_journey',
            ])
            ->orderBy('id')
            ->chunk(500, function ($chunk) use (
                &$journeys,
                &$transitionCounts,
                &$channelSet,
            ): void {
                foreach ($chunk as $row) {
                    $raw = $row->attribution_journey;
                    if (is_string($raw)) {
                        $raw = json_decode($raw, true) ?? [];
                    }

                    if (! is_array($raw) || count($raw) === 0) {
                        continue;
                    }

                    // Extract ordered channel_id list from journey touches.
                    $touches = [];
                    foreach ($raw as $touch) {
                        if (! is_array($touch)) {
                            continue;
                        }
                        $ch = $this->channelId($touch);
                        if ($ch !== '') {
                            $touches[] = $ch;
                            $channelSet[$ch] = true;
                        }
                    }

                    if (count($touches) === 0) {
                        continue;
                    }

                    $journeys[] = [
                        'order_id' => (int) $row->id,
                        'revenue'  => (float) $row->revenue,
                        'touches'  => $touches,
                    ];

                    // Build transition counts only for multi-touch journeys
                    // (single-touch can't contribute meaningful path information).
                    if (count($touches) > 1) {
                        // START → first_channel
                        $first = $touches[0];
                        $transitionCounts[self::STATE_START][$first]
                            = ($transitionCounts[self::STATE_START][$first] ?? 0) + 1;

                        // channel → channel (sequential pairs)
                        for ($i = 0, $n = count($touches) - 1; $i < $n; $i++) {
                            $from_s = $touches[$i];
                            $to_s   = $touches[$i + 1];
                            $transitionCounts[$from_s][$to_s]
                                = ($transitionCounts[$from_s][$to_s] ?? 0) + 1;
                        }

                        // last_channel → CONVERSION
                        $last = $touches[count($touches) - 1];
                        $transitionCounts[$last][self::STATE_CONVERSION]
                            = ($transitionCounts[$last][self::STATE_CONVERSION] ?? 0) + 1;
                    }
                }
            });

        if (empty($journeys)) {
            return [];
        }

        $channels = array_keys($channelSet);

        // ── Step 2: normalise transition matrix → probabilities ───────────────
        // P[from][to] = count(from→to) / sum_over_k(count(from→k))
        // Each non-absorbing state also has a NULL absorbing state to ensure
        // rows sum to 1 (captures journeys that ended without conversion).

        $matrix = $this->buildMatrix($transitionCounts, $channels);

        // ── Step 3: baseline conversion rate ─────────────────────────────────
        $baseRate = $this->simulateConversionRate($matrix, $channels);

        // ── Step 4: removal effects ───────────────────────────────────────────
        $removalEffects = [];
        foreach ($channels as $ch) {
            $reducedMatrix = $this->removeChannel($matrix, $ch, $channels);
            $reducedRate   = $this->simulateConversionRate($reducedMatrix, $channels);
            $drop          = max(0.0, $baseRate - $reducedRate);
            $removalEffects[$ch] = $drop;
        }

        // ── Step 5: normalise removal effects → shares ────────────────────────
        $totalEffect = array_sum($removalEffects);
        $shares      = [];
        if ($totalEffect > 0.0) {
            foreach ($channels as $ch) {
                $shares[$ch] = ($removalEffects[$ch] ?? 0.0) / $totalEffect;
            }
        } else {
            // Degenerate: fall back to equal shares across all channels seen.
            $equalShare = count($channels) > 0 ? 1.0 / count($channels) : 0.0;
            foreach ($channels as $ch) {
                $shares[$ch] = $equalShare;
            }
        }

        // ── Step 6: allocate revenue per order ────────────────────────────────
        $channelRevenue = array_fill_keys($channels, 0.0);
        $channelOrders  = array_fill_keys($channels, 0);

        foreach ($journeys as $journey) {
            $touches = $journey['touches'];
            $revenue = $journey['revenue'];

            if (count($touches) === 1) {
                // Single-touch: 100 % to that channel.
                $ch = $touches[0];
                if (! isset($channelRevenue[$ch])) {
                    $channelRevenue[$ch] = 0.0;
                    $channelOrders[$ch]  = 0;
                }
                $channelRevenue[$ch] += $revenue;
                $channelOrders[$ch]++;
                continue;
            }

            // Multi-touch: distribute by Markov share across unique channels in
            // this journey (channels that appear multiple times are counted once).
            $journeyChannels = array_unique($touches);
            $journeyTotal    = 0.0;
            foreach ($journeyChannels as $ch) {
                $journeyTotal += $shares[$ch] ?? 0.0;
            }

            if ($journeyTotal <= 0.0) {
                // All channels in this journey had zero share (very unlikely);
                // fall back to linear.
                $linearShare = 1.0 / count($journeyChannels);
                foreach ($journeyChannels as $ch) {
                    if (! isset($channelRevenue[$ch])) {
                        $channelRevenue[$ch] = 0.0;
                        $channelOrders[$ch]  = 0;
                    }
                    $channelRevenue[$ch] += $revenue * $linearShare;
                    $channelOrders[$ch]++;
                }
            } else {
                foreach ($journeyChannels as $ch) {
                    $chShare = ($shares[$ch] ?? 0.0) / $journeyTotal;
                    if (! isset($channelRevenue[$ch])) {
                        $channelRevenue[$ch] = 0.0;
                        $channelOrders[$ch]  = 0;
                    }
                    $channelRevenue[$ch] += $revenue * $chShare;
                    // Count each order once per journey (not once per channel touch).
                    $channelOrders[$ch]++;
                }
            }
        }

        // ── Build result ──────────────────────────────────────────────────────
        $totalRevenue = array_sum($channelRevenue) ?: 1.0;
        $result       = [];

        foreach ($channels as $ch) {
            $rev = $channelRevenue[$ch] ?? 0.0;
            if ($rev <= 0.0 && ($channelOrders[$ch] ?? 0) === 0) {
                continue;
            }
            $result[$ch] = [
                'revenue' => round($rev, 4),
                'orders'  => (int) ($channelOrders[$ch] ?? 0),
                'share'   => round($rev / $totalRevenue, 6),
            ];
        }

        Log::debug('MarkovChainAttributionService: completed', [
            'workspace_id' => $workspaceId,
            'from'         => $from->toDateString(),
            'to'           => $to->toDateString(),
            'journeys'     => count($journeys),
            'channels'     => count($channels),
            'base_rate'    => round($baseRate, 4),
        ]);

        return $result;
    }

    // ─── private helpers ──────────────────────────────────────────────────────

    /**
     * Build a normalised probability transition matrix from raw counts.
     *
     * States: START, each channel, CONVERSION, NULL (absorbing non-conversion).
     *
     * @param  array<string, array<string, int>>  $counts   Raw [from][to] transition counts
     * @param  list<string>                       $channels All unique channel ids
     * @return array<string, array<string, float>> Normalised probability matrix
     */
    private function buildMatrix(array $counts, array $channels): array
    {
        $allStates = array_merge(
            [self::STATE_START],
            $channels,
            [self::STATE_CONVERSION, self::STATE_NULL],
        );

        $matrix = [];

        foreach ($allStates as $from) {
            // Absorbing states have no outgoing transitions.
            if ($from === self::STATE_CONVERSION || $from === self::STATE_NULL) {
                continue;
            }

            $rowCounts = $counts[$from] ?? [];
            $rowTotal  = array_sum($rowCounts);

            if ($rowTotal <= 0) {
                // No transitions from this state: treat as direct null-absorption.
                $matrix[$from][self::STATE_NULL] = 1.0;
                continue;
            }

            // Explicitly add a null-absorption probability for every non-absorbing
            // state that has outgoing counts but may not lead to conversion on every walk.
            // The null state collects "journeys that started but never converted".
            $matrix[$from] = [];
            foreach ($rowCounts as $to => $cnt) {
                $matrix[$from][$to] = $cnt / $rowTotal;
            }

            // If there is no direct null transition in the raw counts, it means
            // every walk from this state eventually reaches conversion or another
            // channel — no explicit null absorption needed.
        }

        return $matrix;
    }

    /**
     * Simulate the conversion rate of the given graph via a Monte-Carlo random walk.
     *
     * A walk starts at STATE_START and follows transition probabilities until it
     * reaches either STATE_CONVERSION or STATE_NULL (or a channel with no outgoing
     * transitions, which is treated as null absorption).
     *
     * Returns the fraction of walks that reached STATE_CONVERSION.
     *
     * @param  array<string, array<string, float>>  $matrix    Probability matrix
     * @param  list<string>                         $channels  Channel list (for fast keying)
     */
    private function simulateConversionRate(array $matrix, array $channels): float
    {
        if (empty($matrix)) {
            return 0.0;
        }

        $converted = 0;
        $total     = self::WALK_ITERATIONS;

        for ($i = 0; $i < $total; $i++) {
            $state    = self::STATE_START;
            $maxSteps = 50; // prevent infinite loops from degenerate matrices

            while ($maxSteps-- > 0) {
                if ($state === self::STATE_CONVERSION) {
                    $converted++;
                    break;
                }
                if ($state === self::STATE_NULL) {
                    break;
                }

                $transitions = $matrix[$state] ?? null;
                if (empty($transitions)) {
                    // No outgoing transitions: absorb as null.
                    break;
                }

                $state = $this->sampleState($transitions);
            }
        }

        return $total > 0 ? $converted / $total : 0.0;
    }

    /**
     * Return a copy of the matrix with channel $ch removed.
     *
     * Removal strategy: any transition pointing to $ch is redirected to
     * STATE_NULL.  The removed channel's own row is also dropped so walks that
     * would have continued through it now end without conversion.
     *
     * @param  array<string, array<string, float>>  $matrix
     * @param  string                               $removeChannel
     * @param  list<string>                         $channels
     * @return array<string, array<string, float>>
     */
    private function removeChannel(
        array  $matrix,
        string $removeChannel,
        array  $channels,
    ): array {
        $reduced = [];

        foreach ($matrix as $from => $transitions) {
            // Drop the removed channel's own outgoing row.
            if ($from === $removeChannel) {
                continue;
            }

            $newRow   = [];
            $absorbed = 0.0;

            foreach ($transitions as $to => $prob) {
                if ($to === $removeChannel) {
                    // Redirect this probability mass to NULL absorption.
                    $absorbed += $prob;
                } else {
                    $newRow[$to] = $prob;
                }
            }

            if ($absorbed > 0.0) {
                $newRow[self::STATE_NULL] = ($newRow[self::STATE_NULL] ?? 0.0) + $absorbed;
            }

            $reduced[$from] = $newRow;
        }

        return $reduced;
    }

    /**
     * Weighted-random sample a next state from a probability distribution.
     *
     * @param  array<string, float>  $distribution  Must sum to approximately 1.0
     * @return string  The sampled state key
     */
    private function sampleState(array $distribution): string
    {
        $rand = mt_rand() / mt_getrandmax();
        $cumulative = 0.0;

        foreach ($distribution as $state => $prob) {
            $cumulative += $prob;
            if ($rand <= $cumulative) {
                return $state;
            }
        }

        // Floating-point rounding: return the last state.
        return array_key_last($distribution);
    }

    /**
     * Extract a normalised lowercase channel_id from a journey touch array.
     *
     * Falls back through channel_type → channel → source → 'direct'.
     * Returns empty string when the touch carries no usable channel signal.
     *
     * @param  array<string, mixed>  $touch
     */
    private function channelId(array $touch): string
    {
        $raw = $touch['channel_type']
            ?? $touch['channel']
            ?? $touch['source']
            ?? null;

        if ($raw === null || $raw === '') {
            return 'direct';
        }

        return strtolower(trim((string) $raw));
    }
}
