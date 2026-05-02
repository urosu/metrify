<?php

namespace App\Integrations\Contracts;

use App\Integrations\Contracts\Platform\KlaviyoConnector;

/**
 * Klaviyo REST API (revision 2026-04-15, OAuth 2.0).
 *
 * @deprecated Use {@see KlaviyoConnector} from the layered contract hierarchy.
 *             This interface is kept for backward compatibility and will be
 *             removed once all consumers migrate to the new contracts.
 *
 * Migration path: KlaviyoConnector extends EmailPlatformConnector extends Connectable + Syncable.
 * See app/Integrations/Contracts/Platform/KlaviyoConnector.php
 */
interface KlaviyoClientInterface extends KlaviyoConnector
{
    //
}
