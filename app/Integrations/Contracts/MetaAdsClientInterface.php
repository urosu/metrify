<?php

namespace App\Integrations\Contracts;

use App\Integrations\Contracts\Platform\MetaAdsConnector;

/**
 * Meta (Facebook) Graph API v25.0.
 *
 * @deprecated Use {@see MetaAdsConnector} from the layered contract hierarchy.
 *             This interface is kept for backward compatibility and will be
 *             removed once all consumers migrate to the new contracts.
 *
 * Migration path: MetaAdsConnector extends AdPlatformConnector extends Connectable + Syncable.
 * See app/Integrations/Contracts/Platform/MetaAdsConnector.php
 */
interface MetaAdsClientInterface extends MetaAdsConnector
{
    //
}
