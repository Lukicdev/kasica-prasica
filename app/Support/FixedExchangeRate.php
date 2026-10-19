<?php

namespace App\Support;

use App\Contracts\ExchangeRate;
use App\Enums\Currency;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * The only place that reads the workspace exchange rate.
 *
 * Replace this binding when dated or automatic rates arrive. Callers ask
 * {@see ExchangeRate} and do not read the rate themselves.
 */
class FixedExchangeRate implements ExchangeRate
{
    public function rsdPerUnit(Currency $currency, ?CarbonInterface $on = null): string
    {
        $rate = match ($currency) {
            Currency::Rsd => '1',
            Currency::Eur => config('money.eur_to_rsd'),
        };

        if (! is_string($rate)) {
            throw new InvalidArgumentException('The exchange rate must be a positive decimal string.');
        }

        $decimal = Decimal::of($rate);

        if ($decimal->isZero() || $decimal->isNegative()) {
            throw new InvalidArgumentException('The exchange rate must be a positive decimal string.');
        }

        return $rate;
    }
}
