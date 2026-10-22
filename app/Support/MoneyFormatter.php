<?php

namespace App\Support;

class MoneyFormatter
{
    public function format(string $amount, string $currency): string
    {
        return $this->group(Decimal::of($amount)->toString(MoneyConverter::SCALE)).' '.$currency;
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
