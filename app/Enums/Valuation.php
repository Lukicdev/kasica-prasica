<?php

namespace App\Enums;

/**
 * How an asset's value is decided. Manual is entered by hand. A later case,
 * such as a market price for stocks or crypto, can supply the value instead.
 */
enum Valuation: string
{
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'By hand',
        };
    }
}
