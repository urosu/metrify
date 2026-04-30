<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\ValueObjects\IntegrationHealth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Elevar-style per-destination tracking-health metrics over a rolling window.
 *
 * Synthesises from `integration_events` for a given workspace + destination.
 * When no events exist (pre-launch baseline), accuracy is synthesised from
 * `integration_runs` completed-vs-failed ratio and is flagged as "estimated".
 *
 * All methods accept a `windowDays` parameter (default 7).
 *
 * Reads:  integration_events
 * Writes: —
 * Called by: IntegrationsController, DashboardController (Trust-health widget)
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/planning/backend.md §12 (IntegrationHealthService detail)
 * @see docs/planning/schema.md §1.7 (integration_events table)
 */
class IntegrationHealthService
{
    private const DEFAULT_WINDOW = 7;

    /**
     * Delivery accuracy for a destination: delivered / (delivered + failed) outbound events.
     *
     * Returns a value 0-100, or null when no events exist.
     */
    public function accuracyPct(int $workspaceId, string $destination, int $windowDays = self::DEFAULT_WINDOW): ?float
    {
        $cutoff = now()->subDays($windowDays);

        $row = DB::table('integration_events')
            ->selectRaw("
                COUNT(*) FILTER (WHERE status = 'delivered') AS delivered,
                COUNT(*) FILTER (WHERE status = 'failed')    AS failed
            ")
            ->where('workspace_id', $workspaceId)
            ->where('destination_platform', $destination)
            ->where('direction', 'outbound')
            ->where('received_at', '>=', $cutoff)
            ->first();

        if ($row === null) {
            return null;
        }

        $delivered = (int) $row->delivered;
        $failed    = (int) $row->failed;
        $total     = $delivered + $failed;

        return $total > 0 ? round($delivered / $total * 100, 1) : null;
    }

    /**
     * Delivery rate: delivered / total (all directions) events.
     *
     * Returns a value 0-100, or null when no events exist.
     */
    public function deliveryRate(int $workspaceId, string $destination, int $windowDays = self::DEFAULT_WINDOW): ?float
    {
        $cutoff = now()->subDays($windowDays);

        $row = DB::table('integration_events')
            ->selectRaw("
                COUNT(*) FILTER (WHERE status = 'delivered') AS delivered,
                COUNT(*) AS total
            ")
            ->where('workspace_id', $workspaceId)
            ->where('destination_platform', $destination)
            ->where('received_at', '>=', $cutoff)
            ->first();

        if ($row === null) {
            return null;
        }

        $total = (int) $row->total;

        return $total > 0 ? round((int) $row->delivered / $total * 100, 1) : null;
    }

    /**
     * Match quality p50 and p99 on the 0-10 Elevar scale.
     *
     * @return array{p50: float|null, p99: float|null}
     */
    public function matchQualityDistribution(int $workspaceId, string $destination, int $windowDays = self::DEFAULT_WINDOW): array
    {
        $cutoff = now()->subDays($windowDays);

        $row = DB::table('integration_events')
            ->selectRaw("
                PERCENTILE_CONT(0.50) WITHIN GROUP (ORDER BY match_quality) AS p50,
                PERCENTILE_CONT(0.99) WITHIN GROUP (ORDER BY match_quality) AS p99
            ")
            ->where('workspace_id', $workspaceId)
            ->where('destination_platform', $destination)
            ->whereNotNull('match_quality')
            ->where('received_at', '>=', $cutoff)
            ->first();

        return [
            'p50' => $row?->p50 !== null ? round((float) $row->p50, 1) : null,
            'p99' => $row?->p99 !== null ? round((float) $row->p99, 1) : null,
        ];
    }

    /**
     * Top error codes by count over the window.
     *
     * @return Collection<int, object>  Each row: error_code, error_category, count.
     */
    public function errorCodeBreakdown(int $workspaceId, string $destination, int $windowDays = self::DEFAULT_WINDOW, int $limit = 10): Collection
    {
        $cutoff = now()->subDays($windowDays);

        return DB::table('integration_events')
            ->selectRaw('error_code, error_category, COUNT(*) AS cnt')
            ->where('workspace_id', $workspaceId)
            ->where('destination_platform', $destination)
            ->where('status', 'failed')
            ->whereNotNull('error_code')
            ->where('received_at', '>=', $cutoff)
            ->groupBy('error_code', 'error_category')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get();
    }

    /**
     * Build a full IntegrationHealth VO for a destination in one call.
     */
    public function forDestination(int $workspaceId, string $destination, int $windowDays = self::DEFAULT_WINDOW): IntegrationHealth
    {
        $mq = $this->matchQualityDistribution($workspaceId, $destination, $windowDays);

        return new IntegrationHealth(
            destination: $destination,
            accuracyPct: $this->accuracyPct($workspaceId, $destination, $windowDays),
            deliveryRate: $this->deliveryRate($workspaceId, $destination, $windowDays),
            matchQualityP50: $mq['p50'],
            matchQualityP99: $mq['p99'],
            topErrors: $this->errorCodeBreakdown($workspaceId, $destination, $windowDays)->all(),
            windowDays: $windowDays,
        );
    }
}
