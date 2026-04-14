<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Jobs\TriggerReactivationBackfillJob;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Listens to Cashier's WebhookReceived event and keeps workspaces.billing_plan
 * in sync with Stripe subscription state.
 *
 * Handled events:
 *   customer.subscription.created  → set billing_plan from price ID lookup
 *   customer.subscription.updated  → update billing_plan
 *   customer.subscription.deleted  → set billing_plan = null
 */
class SyncBillingPlanFromStripe
{
    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;
        $type    = $payload['type'] ?? '';

        if (! str_starts_with($type, 'customer.subscription.')) {
            return;
        }

        $subscription = $payload['data']['object'] ?? [];
        $stripeId     = $subscription['customer'] ?? null;

        if ($stripeId === null) {
            Log::warning('SyncBillingPlanFromStripe: missing customer ID in payload', ['type' => $type]);
            return;
        }

        $workspace = Workspace::withoutGlobalScopes()
            ->where('stripe_id', $stripeId)
            ->whereNull('deleted_at')
            ->select(['id', 'billing_plan', 'trial_ends_at'])
            ->first();

        if ($workspace === null) {
            // Not our workspace — Cashier may receive events for test/other customers.
            return;
        }

        match ($type) {
            'customer.subscription.created' => $this->handleCreated($workspace, $subscription),
            'customer.subscription.updated' => $this->handleUpdated($workspace, $subscription),
            'customer.subscription.deleted' => $this->handleDeleted($workspace),
            default                          => null,
        };
    }

    private function handleCreated(Workspace $workspace, array $subscription): void
    {
        $plan = $this->resolvePlanFromSubscription($subscription);

        $wasFrozen = $this->isFrozen($workspace);

        $workspace->billing_plan = $plan;
        $workspace->save();

        Log::info('SyncBillingPlanFromStripe: plan set on subscription created', [
            'workspace_id' => $workspace->id,
            'billing_plan' => $plan,
        ]);

        if ($wasFrozen && $plan !== null) {
            $this->triggerReactivationBackfill($workspace);
        }
    }

    private function handleUpdated(Workspace $workspace, array $subscription): void
    {
        $plan = $this->resolvePlanFromSubscription($subscription);

        $wasFrozen = $this->isFrozen($workspace);

        $workspace->billing_plan = $plan;
        $workspace->save();

        Log::info('SyncBillingPlanFromStripe: plan updated', [
            'workspace_id' => $workspace->id,
            'billing_plan' => $plan,
        ]);

        if ($wasFrozen && $plan !== null) {
            $this->triggerReactivationBackfill($workspace);
        }
    }

    private function handleDeleted(Workspace $workspace): void
    {
        $workspace->billing_plan = null;
        $workspace->save();

        Log::info('SyncBillingPlanFromStripe: billing_plan cleared on subscription deleted', [
            'workspace_id' => $workspace->id,
        ]);
    }

    /**
     * Resolve billing_plan string from a Stripe subscription object.
     *
     * Looks up the active price ID against config/billing.php flat_plans and
     * scale_plan. Returns null if no match (treated as no active plan).
     */
    private function resolvePlanFromSubscription(array $subscription): ?string
    {
        // Extract the price ID from the first subscription item.
        $items   = $subscription['items']['data'] ?? [];
        $priceId = $items[0]['price']['id'] ?? ($subscription['plan']['id'] ?? null);

        if ($priceId === null) {
            return null;
        }

        // Check flat plans.
        /** @var array<string, array{price_id_monthly: string|null, price_id_annual: string|null, revenue_limit: int}> $flatPlans */
        $flatPlans = config('billing.flat_plans', []);

        foreach ($flatPlans as $planName => $config) {
            if ($priceId === $config['price_id_monthly'] || $priceId === $config['price_id_annual']) {
                return $planName;
            }
        }

        // Check Scale plan (metered). DB plan key is 'scale'.
        $scalePriceId = config('billing.scale_plan.price_id');

        if ($scalePriceId !== null && $priceId === $scalePriceId) {
            return 'scale';
        }

        Log::warning('SyncBillingPlanFromStripe: unrecognised price ID', ['price_id' => $priceId]);

        return null;
    }

    /**
     * A workspace is "frozen" when its trial has expired and it has no billing plan.
     * Frozen workspaces have all sync jobs blocked at the scheduler and job handle() level.
     * See: PLANNING.md "14-day free trial — freeze"
     */
    private function isFrozen(Workspace $workspace): bool
    {
        return $workspace->billing_plan === null
            && $workspace->trial_ends_at !== null
            && $workspace->trial_ends_at->lt(now());
    }

    /**
     * Dispatch a catch-up import for the gap period between freeze and now.
     *
     * Why trial_ends_at as gap start: that is the exact date sync jobs stopped
     * running. Any data from that date onwards is missing and needs backfilling.
     * See: PLANNING.md "Reactivation after freeze"
     */
    private function triggerReactivationBackfill(Workspace $workspace): void
    {
        // trial_ends_at is the day sync stopped — that's where the gap begins.
        // We already checked trial_ends_at is not null in isFrozen().
        $gapStart = $workspace->trial_ends_at->toDateString(); // @phpstan-ignore-line

        TriggerReactivationBackfillJob::dispatch($workspace->id, $gapStart);

        Log::info('SyncBillingPlanFromStripe: reactivation backfill dispatched', [
            'workspace_id' => $workspace->id,
            'gap_start'    => $gapStart,
        ]);
    }
}
