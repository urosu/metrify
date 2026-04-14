<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Flat Plans
    |--------------------------------------------------------------------------
    |
    | Two flat tiers: Starter (≤€5k/mo) and Growth (≤€25k/mo).
    | Thresholds apply to GMV (has_store=true) or ad spend (has_store=false).
    | When revenue exceeds Growth limit, the workspace moves to the Scale tier
    | (metered billing — see scale_plan below).
    |
    */

    'flat_plans' => [
        'starter' => [
            'price_id_monthly' => env('STRIPE_PRICE_STARTER_M'),
            'price_id_annual'  => env('STRIPE_PRICE_STARTER_A'),
            'revenue_limit'    => 5000,
        ],
        'growth' => [
            'price_id_monthly' => env('STRIPE_PRICE_GROWTH_M'),
            'price_id_annual'  => env('STRIPE_PRICE_GROWTH_A'),
            'revenue_limit'    => 25000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scale Plan (metered / percentage-based)
    |--------------------------------------------------------------------------
    |
    | Stripe metered billing for high-volume workspaces (>€25k/mo).
    | Revenue is always reported in EUR via fx_rates.
    | Floor of €149/month enforced before reporting usage.
    |
    | Rates:
    |   gmv_rate       — 1% of GMV for ecom workspaces (has_store=true)
    |   ad_spend_rate  — 2% of ad spend for non-ecom workspaces (has_store=false)
    |
    | DB plan key: 'scale' (workspaces.billing_plan CHECK constraint).
    | Annual billing is not available on the Scale tier (Stripe metered).
    |
    */

    'scale_plan' => [
        'price_id'             => env('STRIPE_PRICE_SCALE'),
        'gmv_rate'             => 0.01,
        'ad_spend_rate'        => 0.02,
        'minimum_monthly'      => 149,
        'revenue_threshold'    => 25000,
        'enterprise_threshold' => 250000,
    ],

];
