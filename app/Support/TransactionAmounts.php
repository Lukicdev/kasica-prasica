<?php

namespace App\Support;

use App\Contracts\ExchangeRate;
use App\Enums\Currency;
use App\Models\Account;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Splits a transaction into the original amount, the movement in the account's
 * currency, and the dinar value at the rate for that date.
 */
class TransactionAmounts
{
    public function __construct(
        private ExchangeRate $exchangeRate,
        private MoneyConverter $converter,
    ) {}

    /**
     * @return array{amount: string, account_amount: string, base_amount: string, exchange_rate: string}
     */
    public function resolve(
        Account $account,
        string $amount,
        Currency $currency,
        CarbonInterface $on,
    ): array {
        $normalized = Decimal::of($amount)->toString(MoneyConverter::SCALE);

        if (Decimal::of($normalized)->isZero()) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount other than zero.',
            ]);
        }

        $base = $this->converter->baseAmount($normalized, $currency, $on);

        return [
            'amount' => $normalized,
            'account_amount' => $currency === $account->currency
                ? $normalized
                : $this->converter->displayAmount($base, $account->currency, $on),
            'base_amount' => $base,
            'exchange_rate' => $this->exchangeRate->rsdPerUnit($currency, $on),
        ];
    }
}
