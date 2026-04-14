<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\ReportMonthlyRevenueToStripeJob;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportMonthlyRevenueToStripeJobTest extends TestCase
{
    use RefreshDatabase;

    // Mirrors production config/billing.php: only starter + growth are flat plans.
    // 'scale' is the metered tier — it is NOT in flat_plans.
    private array $flatPlans = [
        'starter' => ['revenue_limit' => 5000,  'price_id_monthly' => 'price_sm', 'price_id_annual' => 'price_sa'],
        'growth'  => ['revenue_limit' => 25000, 'price_id_monthly' => 'price_gm', 'price_id_annual' => 'price_ga'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.flat_plans' => $this->flatPlans,
            'billing.scale_plan' => [
                'price_id'             => 'price_scale',
                'gmv_rate'             => 0.01,
                'ad_spend_rate'        => 0.02,
                'minimum_monthly'      => 149,
                'revenue_threshold'    => 25000,
                'enterprise_threshold' => 250000,
            ],
            'cashier.secret' => 'sk_test_fake',
        ]);
    }

    private function makeWorkspaceWithSubscription(
        string $billingPlan,
        string $stripePriceId,
        string $stripeStatus = 'active',
    ): array {
        $workspace = Workspace::factory()->create([
            'billing_plan'       => $billingPlan,
            'reporting_currency' => 'EUR',
            'stripe_id'          => 'cus_test_' . uniqid(),
        ]);

        $sub = Subscription::create([
            'workspace_id'  => $workspace->id,
            'type'          => 'default',
            'stripe_id'     => 'sub_' . uniqid(),
            'stripe_status' => $stripeStatus,
            'stripe_price'  => $stripePriceId,
            'quantity'      => 1,
        ]);

        return [$workspace, $sub];
    }

    private function seedRevenue(Workspace $workspace, float $revenue): void
    {
        app(WorkspaceContext::class)->set($workspace->id);
        $store = Store::factory()->create(['workspace_id' => $workspace->id]);

        $prevMonth = now()->subMonth();

        DB::table('daily_snapshots')->insert([
            'workspace_id'  => $workspace->id,
            'store_id'      => $store->id,
            'date'          => $prevMonth->startOfMonth()->toDateString(),
            'orders_count'  => 10,
            'revenue'       => $revenue,
            'revenue_native' => $revenue,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Tests the tier resolution logic directly (pure function, no Stripe needed).
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('tierResolutionProvider')]
    public function test_tier_resolution_logic(float $revenue, string $expectedTier): void
    {
        // Access the private method via reflection
        $job    = new ReportMonthlyRevenueToStripeJob();
        $method = new \ReflectionMethod($job, 'resolveTierFromRevenue');

        $result = $method->invoke($job, $revenue, $this->flatPlans);

        $this->assertSame($expectedTier, $result);
    }

    public static function tierResolutionProvider(): array
    {
        return [
            'zero revenue → starter'          => [0.0,      'starter'],
            'exactly starter limit → starter' => [5000.0,   'starter'],
            'just over starter → growth'      => [5001.0,   'growth'],
            'exactly growth limit → growth'   => [25000.0,  'growth'],
            'just over growth → scale'        => [25001.0,  'scale'],
            'high revenue → scale'            => [100000.0, 'scale'],
        ];
    }

    public function test_annual_plan_only_upgrades_not_downgrades(): void
    {
        $job    = new ReportMonthlyRevenueToStripeJob();
        $method = new \ReflectionMethod($job, 'isTierDowngrade');

        // growth → starter is a downgrade
        $this->assertTrue($method->invoke($job, 'growth', 'starter', $this->flatPlans));

        // starter → growth is NOT a downgrade
        $this->assertFalse($method->invoke($job, 'starter', 'growth', $this->flatPlans));

        // scale → starter is a downgrade (scale → any flat plan is always a downgrade)
        $this->assertTrue($method->invoke($job, 'scale', 'starter', $this->flatPlans));

        // growth → scale is NOT a downgrade (flat → scale is an upgrade to metered)
        $this->assertFalse($method->invoke($job, 'growth', 'scale', $this->flatPlans));
    }

    public function test_skips_workspaces_without_active_subscription(): void
    {
        // Workspace with billing_plan but NO subscription rows.
        // Revenue of 3000 would normally trigger upgrade starter → growth,
        // but the early-return on null subscription must leave billing_plan unchanged.
        $workspace = Workspace::factory()->create([
            'billing_plan'       => 'starter',
            'reporting_currency' => 'EUR',
            'stripe_id'          => 'cus_no_sub',
        ]);
        $this->seedRevenue($workspace, 3000.00);

        $job    = new ReportMonthlyRevenueToStripeJob();
        $method = new \ReflectionMethod($job, 'recalculateFlatTier');

        $prevMonth   = now()->subMonth();
        $startOfPrev = $prevMonth->copy()->startOfMonth()->toDateString();
        $endOfPrev   = $prevMonth->copy()->endOfMonth()->toDateString();

        $method->invoke($job, $workspace, $this->flatPlans, $startOfPrev, $endOfPrev);

        // billing_plan must be unchanged — the null-subscription path returned early.
        $this->assertSame('starter', $workspace->fresh()->billing_plan);
    }

    public function test_converts_revenue_to_eur_before_reporting(): void
    {
        $job    = new ReportMonthlyRevenueToStripeJob();
        $method = new \ReflectionMethod($job, 'convertToEur');

        // Create an FX rate for GBP
        \App\Models\FxRate::factory()->create([
            'base_currency'   => 'EUR',
            'target_currency' => 'GBP',
            'rate'            => 0.86,
            'date'            => today(),
        ]);

        $fxService = app(\App\Services\Fx\FxRateService::class);
        $result    = $method->invoke($job, 86.0, 'GBP', Carbon::today(), $fxService, 1);

        // GBP 86 / rate(EUR→GBP 0.86) = EUR 100
        $this->assertEqualsWithDelta(100.0, $result, 0.01);
    }

    public function test_eur_revenue_returned_as_is(): void
    {
        $job    = new ReportMonthlyRevenueToStripeJob();
        $method = new \ReflectionMethod($job, 'convertToEur');

        $fxService = app(\App\Services\Fx\FxRateService::class);
        $result    = $method->invoke($job, 500.0, 'EUR', Carbon::today(), $fxService, 1);

        $this->assertSame(500.0, $result);
    }
}
