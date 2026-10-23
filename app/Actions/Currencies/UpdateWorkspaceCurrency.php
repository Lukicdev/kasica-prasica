<?php

namespace App\Actions\Currencies;

use App\Models\WorkspaceCurrency;
use App\Money\CurrencyRates;
use App\Money\Decimal;
use App\Money\Rate;
use Illuminate\Validation\ValidationException;

class UpdateWorkspaceCurrency
{
    public function __construct(private CurrencyRates $rates) {}

    public function handle(WorkspaceCurrency $currency, ?string $manualRate): void
    {
        $workspace = $currency->workspace;

        if ($currency->code === $workspace->base_currency) {
            throw ValidationException::withMessages([
                'manual_rate' => 'The base currency is always worth 1.',
            ]);
        }

        $manual = $this->manual($manualRate);

        if ($manual === null && ! $this->rates->automatic($workspace->base_currency, $currency->code)) {
            throw ValidationException::withMessages([
                'manual_rate' => "Enter how many {$workspace->base_currency} one {$currency->code} is worth.",
            ]);
        }

        $currency->update(['manual_rate' => $manual]);
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
