<?php

namespace App\Support;

use App\Enums\Currency;

class MoneyFormatter
{
    public function format(string $amount, Currency $currency): string
    {
        return $this->group(Decimal::of($amount)->toString(MoneyConverter::SCALE)).' '.$currency->value;
    }

    private function group(string $amount): string
    {
        $negative = str_starts_with($amount, '-');
        $absolute = ltrim($amount, '-');
        [$whole, $fraction] = explode('.', $absolute, 2);
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole) ?? $whole;

        return ($negative ? '-' : '').$grouped.'.'.$fraction;
    }
}
