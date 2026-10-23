<?php

namespace App\Money;

use App\Enums\Currency;
use App\Models\ExchangeRateQuote;

class CurrencyRates
{
    /**
     * A currency can be converted without a manual rate when both sides have a euro quote.
     * Euro itself is always quotable.
     */
    public function automatic(string $base, string $currency): bool
    {
        if ($currency === $base) {
            return true;
        }

        return $this->quoted($base) && $this->quoted($currency);
    }

    public function quoted(string $currency): bool
    {
        if ($currency === Currency::Eur->value) {
            return true;
        }

        return ExchangeRateQuote::query()->where('currency', $currency)->exists();
    }
}
