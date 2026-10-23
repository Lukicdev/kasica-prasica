<?php

namespace App\Actions\Currencies;

use App\Enums\Currency;
use App\Models\Workspace;
use App\Models\WorkspaceCurrency;
use App\Money\CurrencyRates;
use App\Money\Decimal;
use App\Money\Rate;
use App\Services\FetchExchangeRates;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\ValidationException;

class AddWorkspaceCurrency
{
    public function __construct(
        private CurrencyRates $rates,
        private FetchExchangeRates $fetch,
    ) {}

    public function handle(Workspace $workspace, string $code, ?string $manualRate): WorkspaceCurrency
    {
        $code = strtoupper($code);

        if (! Currency::tryFrom($code) instanceof Currency) {
            throw ValidationException::withMessages([
                'code' => 'Choose a currency from the list.',
            ]);
        }

        if ($workspace->currencies()->where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => 'That currency is already enabled.',
            ]);
        }

        $manual = $this->manual($manualRate);

        if ($manual === null && ! $this->rates->automatic($workspace->base_currency, $code)) {
            try {
                $this->fetch->handle();
            } catch (ConnectionException|RequestException) {
                // A manual rate is still accepted when Frankfurter cannot be reached.
            }
        }

        if ($manual === null && ! $this->rates->automatic($workspace->base_currency, $code)) {
            throw ValidationException::withMessages([
                'manual_rate' => "Enter how many {$workspace->base_currency} one {$code} is worth.",
            ]);
        }

        return $workspace->currencies()->create([
            'code' => $code,
            'manual_rate' => $manual,
        ]);
    }

    private function manual(?string $rate): ?string
    {
        if ($rate === null || trim($rate) === '') {
            return null;
        }

        $decimal = Decimal::of(trim($rate));

        if ($decimal->isZero() || $decimal->isNegative()) {
            throw ValidationException::withMessages([
                'manual_rate' => 'Enter a rate greater than zero.',
            ]);
        }

        return Rate::trim($decimal->toString(8));
    }
}
