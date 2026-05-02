<?php

namespace App\Integrations\Contracts\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Contract for currency exchange rate providers.
 *
 * Implementations: EcbRateProvider (free, 32 currencies, daily at 16:00 CET)
 *                  OpenExchangeRateProvider ($12/mo, 170+ currencies, hourly)
 *
 * Used by SyncFxRatesJob (daily 17:00 UTC) to populate the fx_rates table,
 * which CurrencyConverterService then queries at transaction-day granularity.
 */
interface CurrencyRateProvider
{
    /**
     * Fetch exchange rates for the given date.
     *
     * Returns rates relative to the provider's base currency
     * (ECB: EUR, Open Exchange Rates: USD).
     *
     * @return Collection<string, float>  Keyed by ISO 4217 currency code (e.g., ['USD' => 1.0842, 'GBP' => 0.8594]).
     *                                    Values are the rate: 1 base_currency = rate target_currency.
     *
     * @throws \App\Exceptions\FxRateProviderException  When the API is unavailable or returns invalid data.
     */
    public function fetchRates(CarbonImmutable $date): Collection;

    /**
     * Get the base currency for this provider's rates.
     *
     * ECB returns rates relative to EUR, Open Exchange Rates relative to USD.
     * The SyncFxRatesJob cross-multiplies to build all needed pairs.
     *
     * @return string  ISO 4217 currency code (e.g., 'EUR', 'USD').
     */
    public function getBaseCurrency(): string;

    /**
     * Get the list of supported currency codes.
     *
     * Used to validate workspace currency settings and warn when
     * a store's currency is not covered by the active provider.
     *
     * @return list<string>  ISO 4217 currency codes.
     */
    public function getSupportedCurrencies(): array;
}
