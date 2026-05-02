<?php

namespace App\Integrations\Contracts;

use App\Integrations\Contracts\Category\AnalyticsConnector;

/**
 * Google Search Console API.
 *
 * @deprecated Use {@see AnalyticsConnector} directly. GSC has no platform-specific
 *             extensions beyond the standard AnalyticsConnector contract.
 *             This interface is kept for backward compatibility.
 *
 * Migration path: implements AnalyticsConnector (Connectable + Syncable).
 * See app/Integrations/Contracts/Category/AnalyticsConnector.php
 */
interface GSCClientInterface extends AnalyticsConnector
{
    //
}
