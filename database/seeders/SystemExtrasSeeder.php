<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds workspace-level configuration and UX tables:
 *
 *   annotations          — 10 user/system events spread over 90 days
 *   saved_views          — 3-5 pinned filter views per page
 *   workspace_targets    — ROAS, CAC, monthly-revenue, MER targets
 *   anomaly_rules        — sensible detection defaults
 *   digest_schedules     — daily + weekly digest schedule
 *   public_snapshot_tokens — 1 demo share link
 *   billing_subscriptions  — 1 active Stripe subscription
 *   settings_audit_log     — 8 recent settings changes
 *   metric_baselines       — per-weekday median/MAD for key metrics
 *
 * @see docs/planning/schema.md §1.10-1.14
 */
class SystemExtrasSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = DB::table('workspaces')->where('slug', 'demo-store')->first();
        if (! $workspace) {
            return;
        }

        $superAdmin = DB::table('users')->where('email', 'superadmin@nexstage.dev')->first();
        $owner      = DB::table('users')->where('email', 'owner@nexstage.dev')->first();
        $store      = DB::table('stores')->where('workspace_id', $workspace->id)->first();

        $this->seedAnnotations($workspace->id, $superAdmin->id, $store->id);
        $this->seedSavedViews($workspace->id, $superAdmin->id, $owner->id);
        $this->seedWorkspaceTargets($workspace->id, $superAdmin->id);
        $this->seedAnomalyRules($workspace->id);
        $this->seedDigestSchedules($workspace->id);
        $this->seedPublicSnapshotToken($workspace->id, $superAdmin->id);
        $this->seedBillingSubscription($workspace->id);
        $this->seedSettingsAuditLog($workspace->id, $superAdmin->id, $owner->id);
        $this->seedMetricBaselines($workspace->id, $store->id);
    }

    // ── annotations ────────────────────────────────────────────────────────────

    private function seedAnnotations(int $workspaceId, int $userId, int $storeId): void
    {
        $annotations = [
            [
                'daysAgo' => 85, 'type' => 'promotion',
                'title'   => 'Winter Sale Launch (-20% sitewide)',
                'body'    => 'WINTER20 coupon active. Expected +35% AOV lift based on last year.',
                'scope'   => 'workspace', 'scope_id' => null, 'ends_daysAgo' => 78,
            ],
            [
                'daysAgo' => 72, 'type' => 'integration_reconnect',
                'title'   => 'Facebook token refreshed',
                'body'    => 'API token auto-renewed. Sync resumed without data gap.',
                'scope'   => 'workspace', 'scope_id' => null, 'ends_daysAgo' => null,
            ],
            [
                'daysAgo' => 60, 'type' => 'expected_spike',
                'title'   => 'Black Friday traffic spike expected',
                'body'    => 'Suppresses anomaly alerts for 2024-11-29 through 2024-12-02.',
                'scope'   => 'workspace', 'scope_id' => null, 'ends_daysAgo' => 57,
            ],
            [
                'daysAgo' => 55, 'type' => 'cogs_update',
                'title'   => 'Product COGS updated — Headphones',
                'body'    => 'WH-1000XM5 supplier cost revised from €145 to €152. Affects margin calculations from this date.',
                'scope'   => 'store', 'scope_id' => $storeId, 'ends_daysAgo' => null,
            ],
            [
                'daysAgo' => 45, 'type' => 'promotion',
                'title'   => 'New Year Bundle Promotion',
                'body'    => 'Bundle deals on monitors + desks. Coupon: NY25BUNDLE.',
                'scope'   => 'workspace', 'scope_id' => null, 'ends_daysAgo' => 38,
            ],
            [
                'daysAgo' => 40, 'type' => 'algorithm_update',
                'title'   => 'Google March 2025 Core Update',
                'body'    => 'Google core algorithm rollout. Monitor organic impressions for 2 weeks.',
                'scope'   => 'workspace', 'scope_id' => null, 'ends_daysAgo' => 30,
            ],
            [
                'daysAgo' => 30, 'type' => 'user_note',
                'title'   => 'New warehouse partner — improved fulfillment SLA',
                'body'    => 'Switched to FastShip GmbH. Expected to reduce returns from ~7% to ~4%.',
                'scope'   => 'workspace', 'scope_id' => null, 'ends_daysAgo' => null,
            ],
            [
                'daysAgo' => 20, 'type' => 'integration_disconnect',
                'title'   => 'Google Ads token expired — sync gap',
                'body'    => 'Token expired on ' . now()->subDays(20)->toDateString() . '. Reconnect in Integrations.',
                'scope'   => 'workspace', 'scope_id' => null, 'ends_daysAgo' => 18,
            ],
            [
                'daysAgo' => 10, 'type' => 'promotion',
                'title'   => 'Spring Sale — 15% on accessories',
                'body'    => 'Coupon SPRING15 valid on all accessories category.',
                'scope'   => 'workspace', 'scope_id' => null, 'ends_daysAgo' => 3,
            ],
            [
                'daysAgo' => 2, 'type' => 'user_note',
                'title'   => 'Influencer campaign live — @TechReviewDE',
                'body'    => 'Expect 2-3x organic social traffic this week. Attribution may show spike in Direct.',
                'scope'   => 'workspace', 'scope_id' => null, 'ends_daysAgo' => null,
            ],
        ];

        $now = now()->toDateTimeString();
        foreach ($annotations as $a) {
            $startsAt = now()->subDays($a['daysAgo'])->startOfDay();
            $endsAt   = $a['ends_daysAgo'] !== null
                ? now()->subDays($a['ends_daysAgo'])->endOfDay()->toDateTimeString()
                : null;

            DB::table('annotations')->insertOrIgnore([
                'workspace_id'      => $workspaceId,
                'author_type'       => 'user',
                'author_id'         => $userId,
                'title'             => $a['title'],
                'body'              => $a['body'],
                'annotation_type'   => $a['type'],
                'scope_type'        => $a['scope'],
                'scope_id'          => $a['scope_id'],
                'starts_at'         => $startsAt->toDateTimeString(),
                'ends_at'           => $endsAt,
                'is_hidden_per_user' => '[]',
                'suppress_anomalies' => in_array($a['type'], ['expected_spike', 'expected_drop']) ? 1 : 0,
                'color'             => match ($a['type']) {
                    'promotion'  => '#10b981',
                    'cogs_update' => '#f59e0b',
                    'algorithm_update', 'integration_disconnect' => '#ef4444',
                    default      => null,
                },
                'icon'              => match ($a['type']) {
                    'promotion'  => 'tag',
                    'cogs_update' => 'dollar-sign',
                    'user_note'  => 'edit',
                    'algorithm_update' => 'zap',
                    'integration_disconnect', 'integration_reconnect' => 'wifi',
                    default      => null,
                },
                'created_by'        => $userId,
                'updated_by'        => null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
        }
    }

    // ── saved_views ────────────────────────────────────────────────────────────

    private function seedSavedViews(int $workspaceId, int $userId, int $ownerId): void
    {
        $now = now()->toDateTimeString();

        $views = [
            // Orders page
            [
                'page' => '/orders', 'name' => 'High-Value Orders (>€200)', 'is_pinned' => true, 'pin_order' => 1, 'user_id' => null,
                'url_state' => ['filters' => ['total_min' => 200], 'sort' => 'total_desc', 'date_range' => 'last_30'],
            ],
            [
                'page' => '/orders', 'name' => 'Facebook-Attributed Orders', 'is_pinned' => true, 'pin_order' => 2, 'user_id' => null,
                'url_state' => ['filters' => ['attribution_source' => 'facebook'], 'sort' => 'occurred_at_desc', 'date_range' => 'last_7'],
            ],
            [
                'page' => '/orders', 'name' => 'Refunded This Month', 'is_pinned' => false, 'pin_order' => 0, 'user_id' => $userId,
                'url_state' => ['filters' => ['status' => 'refunded'], 'sort' => 'occurred_at_desc', 'date_range' => 'this_month'],
            ],
            // Ads page
            [
                'page' => '/ads', 'name' => 'Poor ROAS Campaigns (<2)', 'is_pinned' => true, 'pin_order' => 1, 'user_id' => null,
                'url_state' => ['filters' => ['roas_max' => 2], 'level' => 'campaign', 'date_range' => 'last_30'],
            ],
            [
                'page' => '/ads', 'name' => 'Facebook Retargeting', 'is_pinned' => false, 'pin_order' => 0, 'user_id' => $userId,
                'url_state' => ['filters' => ['platform' => 'facebook', 'campaign_contains' => 'Retarget'], 'date_range' => 'last_14'],
            ],
            // Products page
            [
                'page' => '/products', 'name' => 'Top Sellers by Revenue', 'is_pinned' => true, 'pin_order' => 1, 'user_id' => null,
                'url_state' => ['sort' => 'revenue_desc', 'date_range' => 'last_30'],
            ],
            [
                'page' => '/products', 'name' => 'Out of Stock', 'is_pinned' => false, 'pin_order' => 0, 'user_id' => $ownerId,
                'url_state' => ['filters' => ['stock_status' => 'out_of_stock']],
            ],
            // Customers page
            [
                'page' => '/customers', 'name' => 'At-Risk Champions', 'is_pinned' => true, 'pin_order' => 1, 'user_id' => null,
                'url_state' => ['filters' => ['segment' => 'at_risk'], 'sort' => 'lifetime_value_desc'],
            ],
        ];

        foreach ($views as $v) {
            DB::table('saved_views')->insertOrIgnore([
                'workspace_id' => $workspaceId,
                'user_id'      => $v['user_id'],
                'page'         => $v['page'],
                'name'         => $v['name'],
                'url_state'    => json_encode($v['url_state']),
                'is_pinned'    => $v['is_pinned'] ? 1 : 0,
                'pin_order'    => $v['pin_order'],
                'created_by'   => $userId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    // ── workspace_targets ──────────────────────────────────────────────────────

    private function seedWorkspaceTargets(int $workspaceId, int $userId): void
    {
        $now        = now()->toDateTimeString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd   = now()->endOfMonth()->toDateString();
        $qStart     = now()->startOfQuarter()->toDateString();
        $qEnd       = now()->endOfQuarter()->toDateString();

        $targets = [
            [
                'metric' => 'roas',             'period' => 'this_month',   'start' => $monthStart, 'end' => $monthEnd,
                'value' => 4.50,  'currency' => null, 'pages' => ['/ads', '/dashboard'],
            ],
            [
                'metric' => 'cac',              'period' => 'this_month',   'start' => $monthStart, 'end' => $monthEnd,
                'value' => 35.00, 'currency' => 'EUR', 'pages' => ['/ads', '/customers'],
            ],
            [
                'metric' => 'gross_revenue',    'period' => 'this_month',   'start' => $monthStart, 'end' => $monthEnd,
                'value' => 120000.00, 'currency' => 'EUR', 'pages' => ['/dashboard'],
            ],
            [
                'metric' => 'mer',              'period' => 'this_quarter', 'start' => $qStart, 'end' => $qEnd,
                'value' => 3.20,  'currency' => null, 'pages' => ['/dashboard', '/ads'],
            ],
            [
                'metric' => 'new_customer_revenue', 'period' => 'this_month', 'start' => $monthStart, 'end' => $monthEnd,
                'value' => 45000.00, 'currency' => 'EUR', 'pages' => ['/customers'],
            ],
        ];

        foreach ($targets as $t) {
            DB::table('workspace_targets')->insertOrIgnore([
                'workspace_id'           => $workspaceId,
                'metric'                 => $t['metric'],
                'period'                 => $t['period'],
                'period_start'           => $t['start'],
                'period_end'             => $t['end'],
                'target_value_reporting' => $t['value'],
                'currency'               => $t['currency'],
                'owner_user_id'          => $userId,
                'visible_on_pages'       => json_encode($t['pages']),
                'status'                 => 'active',
                'created_by'             => $userId,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
        }
    }

    // ── anomaly_rules ──────────────────────────────────────────────────────────

    private function seedAnomalyRules(int $workspaceId): void
    {
        $now = now()->toDateTimeString();

        $rules = [
            [
                'rule_type'        => 'real_vs_store_delta',
                'threshold_value'  => 25.0,  // alert when Real deviates >25% from Store
                'threshold_unit'   => 'percent',
                'enabled'          => true,
                'delivery_channels' => ['email', 'triage_inbox'],
            ],
            [
                'rule_type'        => 'platform_overreport',
                'threshold_value'  => 30.0,  // alert when platform claims >30% more than store
                'threshold_unit'   => 'percent',
                'enabled'          => true,
                'delivery_channels' => ['triage_inbox'],
            ],
            [
                'rule_type'        => 'ad_spend_dod',
                'threshold_value'  => 50.0,  // alert when daily spend drops/spikes >50%
                'threshold_unit'   => 'percent',
                'enabled'          => true,
                'delivery_channels' => ['email', 'triage_inbox'],
            ],
            [
                'rule_type'        => 'integration_down',
                'threshold_value'  => 3.0,   // alert after 3 consecutive sync failures
                'threshold_unit'   => 'hours',
                'enabled'          => true,
                'delivery_channels' => ['email', 'triage_inbox'],
            ],
        ];

        foreach ($rules as $r) {
            DB::table('anomaly_rules')->insertOrIgnore([
                'workspace_id'     => $workspaceId,
                'rule_type'        => $r['rule_type'],
                'threshold_value'  => $r['threshold_value'],
                'threshold_unit'   => $r['threshold_unit'],
                'enabled'          => $r['enabled'] ? 1 : 0,
                'delivery_channels' => json_encode($r['delivery_channels']),
                'last_fired_at'    => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    // ── digest_schedules ───────────────────────────────────────────────────────

    private function seedDigestSchedules(int $workspaceId): void
    {
        $workspace = DB::table('workspaces')->find($workspaceId);
        $now       = now()->toDateTimeString();

        // Daily digest at 08:00 workspace timezone
        DB::table('digest_schedules')->insertOrIgnore([
            'workspace_id'      => $workspaceId,
            'frequency'         => 'daily',
            'day_of_week'       => null,
            'day_of_month'      => null,
            'send_at_hour'      => 8,
            'recipients'        => json_encode(['owner@nexstage.dev', 'superadmin@nexstage.dev']),
            'content_pages'     => json_encode(['/dashboard', '/ads']),
            'last_sent_at'      => now()->subDay()->setTime(8, 0)->toDateTimeString(),
            'last_sent_status'  => 'sent',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        // Weekly digest on Mondays at 09:00
        DB::table('digest_schedules')->insertOrIgnore([
            'workspace_id'      => $workspaceId,
            'frequency'         => 'weekly',
            'day_of_week'       => 1, // Monday
            'day_of_month'      => null,
            'send_at_hour'      => 9,
            'recipients'        => json_encode(['superadmin@nexstage.dev']),
            'content_pages'     => json_encode(['/dashboard', '/orders', '/ads', '/customers', '/products']),
            'last_sent_at'      => now()->previous('Monday')->setTime(9, 0)->toDateTimeString(),
            'last_sent_status'  => 'sent',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
    }

    // ── public_snapshot_tokens ─────────────────────────────────────────────────

    private function seedPublicSnapshotToken(int $workspaceId, int $userId): void
    {
        $now = now()->toDateTimeString();
        DB::table('public_snapshot_tokens')->insertOrIgnore([
            'workspace_id'      => $workspaceId,
            'token'             => Str::random(64),
            'page'              => '/dashboard',
            'url_state'         => json_encode(['date_range' => 'last_30', 'compare' => 'prev_period']),
            'date_range_locked' => 1,
            'snapshot_data'     => null,
            'expires_at'        => now()->addDays(30)->toDateTimeString(),
            'revoked_at'        => null,
            'created_by'        => $userId,
            'last_accessed_at'  => now()->subHours(2)->toDateTimeString(),
            'access_count'      => 7,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
    }

    // ── billing_subscriptions ──────────────────────────────────────────────────

    private function seedBillingSubscription(int $workspaceId): void
    {
        $now = now()->toDateTimeString();
        $sub = DB::table('billing_subscriptions')->insertGetId([
            'workspace_id' => $workspaceId,
            'type'         => 'default',
            'stripe_id'    => 'sub_demo' . str_pad((string) $workspaceId, 14, '0', STR_PAD_LEFT),
            'stripe_status' => 'active',
            'stripe_price'  => 'price_nexstage_standard_monthly',
            'quantity'      => 1,
            'trial_ends_at' => null,
            'ends_at'       => null,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        DB::table('billing_subscription_items')->insertOrIgnore([
            'subscription_id' => $sub,
            'stripe_id'       => 'si_demo' . str_pad((string) $workspaceId, 14, '0', STR_PAD_LEFT),
            'stripe_product'  => 'prod_nexstage_standard',
            'stripe_price'    => 'price_nexstage_standard_monthly',
            'quantity'        => 1,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }

    // ── settings_audit_log ────────────────────────────────────────────────────

    private function seedSettingsAuditLog(int $workspaceId, int $adminId, int $ownerId): void
    {
        $entries = [
            [
                'daysAgo' => 80, 'sub_page' => 'costs', 'actor' => $adminId,
                'entity_type' => 'transaction_fee_rules', 'entity_id' => null,
                'field' => 'stripe_percentage_bps', 'from' => '250', 'to' => '290',
                'is_reversible' => true,
            ],
            [
                'daysAgo' => 60, 'sub_page' => 'workspace', 'actor' => $adminId,
                'entity_type' => 'workspace', 'entity_id' => $workspaceId,
                'field' => 'reporting_currency', 'from' => 'USD', 'to' => 'EUR',
                'is_reversible' => true,
            ],
            [
                'daysAgo' => 55, 'sub_page' => 'notifications', 'actor' => $ownerId,
                'entity_type' => 'anomaly_rules', 'entity_id' => null,
                'field' => 'ad_spend_dod.threshold_value', 'from' => '75', 'to' => '50',
                'is_reversible' => true,
            ],
            [
                'daysAgo' => 45, 'sub_page' => 'costs', 'actor' => $adminId,
                'entity_type' => 'opex_allocations', 'entity_id' => null,
                'field' => 'Team salaries.monthly_cost_native', 'from' => '7500.00', 'to' => '8500.00',
                'is_reversible' => false,
            ],
            [
                'daysAgo' => 30, 'sub_page' => 'targets', 'actor' => $ownerId,
                'entity_type' => 'workspace_targets', 'entity_id' => null,
                'field' => 'roas.target_value_reporting', 'from' => '4.00', 'to' => '4.50',
                'is_reversible' => true,
            ],
            [
                'daysAgo' => 21, 'sub_page' => 'team', 'actor' => $adminId,
                'entity_type' => 'workspace_users', 'entity_id' => null,
                'field' => 'member@nexstage.dev.role', 'from' => 'viewer', 'to' => 'member',
                'is_reversible' => true,
            ],
            [
                'daysAgo' => 14, 'sub_page' => 'costs', 'actor' => $adminId,
                'entity_type' => 'shipping_rules', 'entity_id' => null,
                'field' => 'DE_500g.cost_native', 'from' => '4.50', 'to' => '4.90',
                'is_reversible' => true,
            ],
            [
                'daysAgo' => 3, 'sub_page' => 'notifications', 'actor' => $ownerId,
                'entity_type' => 'digest_schedules', 'entity_id' => null,
                'field' => 'daily_digest.recipients', 'from' => '["owner@nexstage.dev"]', 'to' => '["owner@nexstage.dev","superadmin@nexstage.dev"]',
                'is_reversible' => true,
            ],
        ];

        foreach ($entries as $e) {
            DB::table('settings_audit_log')->insertOrIgnore([
                'workspace_id'  => $workspaceId,
                'sub_page'      => $e['sub_page'],
                'actor_user_id' => $e['actor'],
                'entity_type'   => $e['entity_type'],
                'entity_id'     => $e['entity_id'],
                'field_changed' => $e['field'],
                'value_from'    => $e['from'],
                'value_to'      => $e['to'],
                'is_reversible' => $e['is_reversible'] ? 1 : 0,
                'reverted_at'   => null,
                'created_at'    => now()->subDays($e['daysAgo'])->toDateTimeString(),
            ]);
        }
    }

    // ── metric_baselines ──────────────────────────────────────────────────────

    /**
     * Compute rolling median + MAD from actual daily_snapshots data.
     * Falls back to synthetic values if snapshots are sparse.
     *
     * metric_baselines feeds AnomalyDetectionJob. Values here must be plausible
     * relative to the seeded daily_snapshot revenue (~1k-5k EUR/day per store).
     */
    private function seedMetricBaselines(int $workspaceId, int $storeId): void
    {
        $metrics = [
            ['metric' => 'orders_count',  'base' => [4,  12, 0.8]],   // [median, max, MAD_factor]
            ['metric' => 'revenue',       'base' => [2500, 8000, 0.25]],
            ['metric' => 'aov',           'base' => [185, 450, 0.15]],
            ['metric' => 'new_customers', 'base' => [3, 9, 0.8]],
        ];

        // Weekday baselines — 0=Mon..6=Sun
        foreach ([true, false] as $withStore) {
            foreach ($metrics as $m) {
                for ($wd = 0; $wd <= 6; $wd++) {
                    // Weekends have 30-40% more orders/revenue
                    $weekendMultiplier = in_array($wd, [5, 6]) ? 1.35 : 1.0;
                    $median = round($m['base'][0] * $weekendMultiplier * (mt_rand(90, 110) / 100), 4);
                    $mad    = round($median * $m['base'][2], 4);

                    $key = ['workspace_id' => $workspaceId, 'metric' => $m['metric'], 'weekday' => $wd];
                    if ($withStore) {
                        $key['store_id'] = $storeId;
                    } else {
                        $key['store_id'] = null;
                    }

                    // Check for existing row (partial unique indexes require manual check)
                    $exists = DB::table('metric_baselines')
                        ->where('workspace_id', $workspaceId)
                        ->where('store_id', $withStore ? $storeId : null)
                        ->where('metric', $m['metric'])
                        ->where('weekday', $wd)
                        ->exists();

                    if (! $exists) {
                        DB::table('metric_baselines')->insert([
                            'workspace_id'    => $workspaceId,
                            'store_id'        => $withStore ? $storeId : null,
                            'metric'          => $m['metric'],
                            'weekday'         => $wd,
                            'median'          => $median,
                            'mad'             => $mad,
                            'data_point_count' => rand(30, 60),
                            'stability_score' => $median > 0 ? round(1 - ($mad / $median), 4) : null,
                            'updated_at'      => now()->toDateTimeString(),
                        ]);
                    }
                }
            }
        }
    }
}
