<?php

namespace App\Exceptions;

use RuntimeException;

class MissingExchangeRate extends RuntimeException
{
    public function __construct(public readonly string $currency)
    {
        parent::__construct("No exchange rate is available for {$currency}.");
    }
}
