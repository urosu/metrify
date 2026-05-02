<?php

namespace App\Integrations\Contracts;

use App\Integrations\Contracts\Category\AnalyticsConnector;

/**
 * GA4 Data API v1beta.
 *
 * @deprecated Use {@see AnalyticsConnector} directly. GA4 has no platform-specific
 *             extensions beyond the standard AnalyticsConnector contract.
 *             This interface is kept for backward compatibility.
 *
 * Migration path: implements AnalyticsConnector (Connectable + Syncable).
 * See app/Integrations/Contracts/Category/AnalyticsConnector.php
 */
interface GA4ClientInterface extends AnalyticsConnector
{
    //
}
