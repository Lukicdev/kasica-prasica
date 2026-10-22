<?php

namespace App\Support;

use App\Contracts\ExchangeRate;
use App\Exceptions\MissingExchangeRate;
use App\Models\ExchangeRateQuote;
use App\Models\Workspace;
use Carbon\CarbonInterface;

class StoredExchangeRate implements ExchangeRate
{
    /**
     * @var array<string, string>
     */
    private array $memory = [];

    public function basePerUnit(Workspace $workspace, string $currency, ?CarbonInterface $on = null): string
    {
        $currency = strtoupper($currency);
        $key = $workspace->id.'|'.$currency.'|'.($on?->toDateString() ?? 'latest');

        if (array_key_exists($key, $this->memory)) {
            return $this->memory[$key];
        }

        return $this->memory[$key] = $this->resolve($workspace, $currency, $on);
    }

    private function resolve(Workspace $workspace, string $currency, ?CarbonInterface $on): string
    {
        $base = $workspace->base_currency;

        if ($currency === $base) {
            return '1';
        }

        $workspace->loadMissing('currencies');
        $manual = $workspace->currencies->firstWhere('code', $currency)?->manual_rate;

        if (is_string($manual) && $manual !== '') {
            return Rate::trim($manual);
        }

        return Rate::trim(
            Decimal::of($this->perEuro($base, $on))
                ->divide(Decimal::of($this->perEuro($currency, $on)), 8)
                ->toString(8),
        );
    }

    private function perEuro(string $currency, ?CarbonInterface $on): string
    {
        if ($currency === 'EUR') {
            return '1';
        }

        $query = ExchangeRateQuote::query()->where('currency', $currency);

        if ($on !== null) {
            $query->whereDate('quoted_on', '<=', $on);
        }

        $quote = $query->orderByDesc('quoted_on')->orderByDesc('id')->first();

        if (! $quote instanceof ExchangeRateQuote) {
            throw new MissingExchangeRate($currency);
        }

        return Rate::trim((string) $quote->per_euro);
    }
}
