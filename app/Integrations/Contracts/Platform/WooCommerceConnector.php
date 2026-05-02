<?php

namespace App\Integrations\Contracts\Platform;

use App\Integrations\Contracts\Category\StoreConnector;

/**
 * WooCommerce-specific extensions to StoreConnector.
 *
 * WooCommerce uses the standard StoreConnector methods for everything.
 * This interface exists only for type discrimination in the container
 * and to document WooCommerce-specific behavior.
 *
 * No additional methods needed -- WooCommerce's REST API v3 maps
 * cleanly to the StoreConnector contract. Platform-specific concerns
 * (page-based pagination, 2-3 concurrent request limit, guest dedup
 * by billing.email, Action Scheduler webhooks) are implementation details.
 */
interface WooCommerceConnector extends StoreConnector
{
    //
}
