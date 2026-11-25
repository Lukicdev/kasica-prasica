<?php

namespace App\Money;

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

    /**
     * Quotes per currency, oldest first, for dated lookups.
     *
     * @var array<string, array{dates: list<string>, rates: list<string>}>
     */
    private array $series = [];

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

        if ($on !== null) {
            return $this->perEuroOn($currency, $on->toDateString());
        }

        $quote = ExchangeRateQuote::query()
            ->where('currency', $currency)
            ->orderByDesc('quoted_on')
            ->orderByDesc('id')
            ->first();

        if (! $quote instanceof ExchangeRateQuote) {
            throw new MissingExchangeRate($currency);
        }

        return Rate::trim((string) $quote->per_euro);
    }

    /**
     * The latest quote on or before the date. Lists convert rows from many
     * different days, so the currency's quotes are loaded once and searched
     * in memory instead of querying once per day.
     */
    private function perEuroOn(string $currency, string $date): string
    {
        $series = $this->series[$currency] ??= $this->loadSeries($currency);
        $low = 0;
        $high = count($series['dates']) - 1;
        $found = -1;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);

            if ($series['dates'][$middle] <= $date) {
                $found = $middle;
                $low = $middle + 1;
            } else {
                $high = $middle - 1;
            }
        }

        if ($found < 0) {
            throw new MissingExchangeRate($currency);
        }

        return Rate::trim($series['rates'][$found]);
    }

    /**
     * @return array{dates: list<string>, rates: list<string>}
     */
    private function loadSeries(string $currency): array
    {
        $dates = [];
        $rates = [];

        // Plain rows: hydrating a model per quote costs more than the queries saved.
        $quotes = ExchangeRateQuote::query()
            ->where('currency', $currency)
            ->orderBy('quoted_on')
            ->orderBy('id')
            ->toBase()
            ->get(['quoted_on', 'per_euro']);

        foreach ($quotes as $quote) {
            $date = substr((string) $quote->quoted_on, 0, 10);
            $rate = (string) $quote->per_euro;

            // Several rows on one day: the later id wins, as in the single lookup.
            if ($dates !== [] && $dates[array_key_last($dates)] === $date) {
                $rates[array_key_last($rates)] = $rate;

                continue;
            }

            $dates[] = $date;
            $rates[] = $rate;
        }

        return ['dates' => $dates, 'rates' => $rates];
    }
}
