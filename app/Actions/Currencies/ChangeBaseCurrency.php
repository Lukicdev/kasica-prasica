<?php

namespace App\Actions\Currencies;

use App\Enums\Currency;
use App\Models\Workspace;
use App\Support\CurrencyRates;
use Illuminate\Validation\ValidationException;

class ChangeBaseCurrency
{
    public function __construct(private CurrencyRates $rates) {}

    public function handle(Workspace $workspace, string $code): void
    {
        $code = strtoupper($code);

        if ($code === $workspace->base_currency) {
            return;
        }

        if (! Currency::tryFrom($code) instanceof Currency) {
            throw ValidationException::withMessages([
                'currency' => 'Choose a currency from the list.',
            ]);
        }

        if ($workspace->hasLedger()) {
            throw ValidationException::withMessages([
                'currency' => 'The base currency stays fixed once money has been entered.',
            ]);
        }

        $enabled = $workspace->currencies()->pluck('code')->all();

        if (! in_array($code, $enabled, true)) {
            $enabled[] = $code;
        }

        foreach ($enabled as $currency) {
            if ($currency === $code) {
                continue;
            }

            if (! $this->rates->automatic($code, $currency)) {
                throw ValidationException::withMessages([
                    'currency' => "Remove {$currency} before changing the base currency. Frankfurter has no rate for that pair.",
                ]);
            }
        }

        $workspace->currencies()->update(['manual_rate' => null]);
        $workspace->update(['base_currency' => $code]);
        $workspace->ensureBaseCurrency();
    }
}
