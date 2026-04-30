<?php

declare(strict_types=1);

/**
 * Feature flags — env-backed, no package, read at call-time.
 *
 * Environment-level toggles only. Per-workspace toggles live in
 * `workspace_settings` JSONB, not here.
 *
 * @see docs/planning/backend.md §10 (feature flag spec)
 */
return [
    // ── Phased unlock (days since workspace created) ─────────────────────────
    // Drives gradual feature reveal: cohort on day 7, LTV basic day 30, predictive day 90.
    'cohort_analysis_unlock_days'  => (int) env('FEATURE_COHORT_UNLOCK_DAYS', 7),
    'ltv_basic_unlock_days'        => (int) env('FEATURE_LTV_BASIC_UNLOCK_DAYS', 30),
    'ltv_predictive_unlock_days'   => (int) env('FEATURE_LTV_PREDICTIVE_UNLOCK_DAYS', 90),

    // ── v2 toggles (schema-ready, UI-disabled in v1) ─────────────────────────
    'multi_touch_enabled'              => env('FEATURE_MULTI_TOUCH', false), // v1.5
    'benchmarking_enabled'             => env('FEATURE_BENCHMARKING', false),
    'white_label_enabled'              => env('FEATURE_WHITE_LABEL', false),
    'subscription_commerce_enabled'    => env('FEATURE_SUBSCRIPTION_COMMERCE', false),
    'ai_assistant_enabled'             => env('FEATURE_AI_ASSISTANT', false),
];
