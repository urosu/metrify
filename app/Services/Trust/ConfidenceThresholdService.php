<?php

declare(strict_types=1);

namespace App\Services\Trust;

use App\Models\Workspace;
use App\ValueObjects\SignalType;
use App\ValueObjects\WorkspaceSettings;
use Illuminate\Support\Facades\Cache;

/**
 * Evaluates a sample size against the workspace's configurable confidence thresholds.
 *
 * Returns a SignalType VO that drives the ConfidenceChip and SignalTypeBadge UI
 * primitives. Thresholds are stored in workspaces.workspace_settings:
 *   - orders:      workspace_settings.confidence_threshold_orders     (default 100)
 *   - sessions:    workspace_settings.confidence_threshold_sessions   (default 1000)
 *   - impressions: workspace_settings.confidence_threshold_impressions (default 10000)
 *
 * Signal classification rules:
 *   sampleSize >= threshold → 'measured'
 *   sampleSize < threshold  → 'insufficient'
 *
 * Workspace settings are cached per workspace for 10 minutes to avoid per-request
 * DB hits on high-traffic pages.
 *
 * Public method: evaluate(metric, sampleSize, workspaceId) → SignalType
 *
 * Reads:     workspaces.workspace_settings jsonb
 * Called by: DashboardController, AdsController, AttributionController, SeoController
 *
 * @see docs/planning/backend.md §1.3 (service spec)
 * @see docs/UX.md §5.27 (ConfidenceChip) §5.28 (SignalTypeBadge)
 */
class ConfidenceThresholdService
{
    /** @var string[] */
    public const METRIC_TYPES = ['orders', 'sessions', 'impressions'];

    private const CACHE_TTL = 600; // 10 minutes

    /**
     * Evaluate sample size against the workspace's threshold for the given metric type.
     *
     * @param string $metric     One of: 'orders', 'sessions', 'impressions'
     * @param int    $sampleSize The count of events being evaluated
     * @param int    $workspaceId
     * @return SignalType
     *
     * @throws \InvalidArgumentException if $metric is not a known type
     */
    public function evaluate(string $metric, int $sampleSize, int $workspaceId): SignalType
    {
        $threshold = $this->getThreshold($metric, $workspaceId);

        if ($sampleSize >= $threshold) {
            return SignalType::measured($sampleSize, $threshold);
        }

        return SignalType::insufficient($sampleSize, $threshold);
    }

    /**
     * Return the configured threshold for the given metric type.
     *
     * Falls back to default values if workspace settings are missing.
     */
    public function getThreshold(string $metric, int $workspaceId): int
    {
        if (!in_array($metric, self::METRIC_TYPES, true)) {
            throw new \InvalidArgumentException(
                "Unknown metric type '{$metric}'. Allowed: " . implode(', ', self::METRIC_TYPES)
            );
        }

        $settings = $this->loadSettings($workspaceId);

        return match ($metric) {
            'orders'      => $settings->confidenceThresholdOrders,
            'sessions'    => $settings->confidenceThresholdSessions,
            'impressions' => $settings->confidenceThresholdImpressions,
        };
    }

    private function loadSettings(int $workspaceId): WorkspaceSettings
    {
        $cacheKey = "workspace_settings.confidence.{$workspaceId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, static function () use ($workspaceId): WorkspaceSettings {
            $ws = Workspace::withoutGlobalScopes()
                ->select(['id', 'workspace_settings'])
                ->find($workspaceId);

            return $ws?->workspace_settings ?? new WorkspaceSettings();
        });
    }
}
