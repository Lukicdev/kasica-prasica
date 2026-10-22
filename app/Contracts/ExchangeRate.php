<?php

namespace App\Contracts;

use App\Models\Workspace;
use Carbon\CarbonInterface;

interface ExchangeRate
{
    /**
     * How many units of the workspace base one unit of the currency is worth.
     *
     * A date selects the quote published on or before that day. Null uses the latest quote.
     * A manual rate on the workspace ignores the date.
     */
    public function basePerUnit(Workspace $workspace, string $currency, ?CarbonInterface $on = null): string;
}
