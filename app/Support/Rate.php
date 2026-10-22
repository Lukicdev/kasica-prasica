<?php

namespace App\Support;

/**
 * Exchange rates kept as exact decimals, without trailing zeros.
 */
final class Rate
{
    public static function trim(string $amount): string
    {
        $scaled = Decimal::of($amount)->round(8)->toString(8);

        if (! str_contains($scaled, '.')) {
            return $scaled;
        }

        $trimmed = rtrim(rtrim($scaled, '0'), '.');

        return $trimmed === '' || $trimmed === '-' ? '0' : $trimmed;
    }
}
