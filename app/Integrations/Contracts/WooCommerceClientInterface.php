<?php

namespace App\Integrations\Contracts;

use App\Integrations\Contracts\Platform\WooCommerceConnector;

/**
 * WooCommerce REST API v3.
 *
 * @deprecated Use {@see WooCommerceConnector} from the layered contract hierarchy.
 *             This interface is kept for backward compatibility and will be
 *             removed once all consumers migrate to the new contracts.
 *
 * Migration path: WooCommerceConnector extends StoreConnector extends Connectable + Syncable.
 * See app/Integrations/Contracts/Platform/WooCommerceConnector.php
 */
interface WooCommerceClientInterface extends WooCommerceConnector
{
    //
}
