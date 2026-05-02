<?php

namespace App\Integrations\Contracts\Platform;

use App\Integrations\Contracts\Category\EmailPlatformConnector;
use App\Models\EmailAccount;

/**
 * Klaviyo-specific extensions to EmailPlatformConnector.
 *
 * Adds predictive analytics sync, which is unique to Klaviyo's
 * profile API (predicted CLV, next order date, churn risk).
 */
interface KlaviyoConnector extends EmailPlatformConnector
{
    /**
     * Sync predictive analytics data from Klaviyo profiles.
     *
     * Updates Customer records with predicted_clv, predicted_next_order_at,
     * and churn_risk from Klaviyo's predictive_analytics profile field.
     *
     * Requires 500+ customers and 180+ days of history on the Klaviyo side.
     * Updates weekly -- call on the same schedule.
     */
    public function syncPredictiveProfiles(EmailAccount $account): void;
}
