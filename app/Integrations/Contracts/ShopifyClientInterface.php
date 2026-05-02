<?php

namespace App\Integrations\Contracts;

use App\Integrations\Contracts\Platform\ShopifyConnector;

/**
 * Shopify GraphQL Admin API (2026-04).
 *
 * @deprecated Use {@see ShopifyConnector} from the layered contract hierarchy.
 *             This interface is kept for backward compatibility and will be
 *             removed once all consumers migrate to the new contracts.
 *
 * Migration path: ShopifyConnector extends StoreConnector extends Connectable + Syncable.
 * See app/Integrations/Contracts/Platform/ShopifyConnector.php
 */
interface ShopifyClientInterface extends ShopifyConnector
{
    //
}
