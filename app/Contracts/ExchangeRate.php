<?php

namespace App\Contracts;

use App\Enums\Currency;
use Carbon\CarbonInterface;

interface ExchangeRate
{
    /**
     * How many dinars one unit of the given currency is worth.
     *
     * The date is part of the contract so a later dated-rate source can
     * replace the fixed rate without changing callers. The fixed rate ignores it.
     */
    public function rsdPerUnit(Currency $currency, ?CarbonInterface $on = null): string;
}
