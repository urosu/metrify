<?php

namespace App\Integrations\Contracts;

use App\Integrations\Contracts\Platform\GoogleAdsConnector;

/**
 * Google Ads API v23.1 via SearchStream.
 *
 * @deprecated Use {@see GoogleAdsConnector} from the layered contract hierarchy.
 *             This interface is kept for backward compatibility and will be
 *             removed once all consumers migrate to the new contracts.
 *
 * Migration path: GoogleAdsConnector extends AdPlatformConnector extends Connectable + Syncable.
 * See app/Integrations/Contracts/Platform/GoogleAdsConnector.php
 */
interface GoogleAdsClientInterface extends GoogleAdsConnector
{
    //
}
