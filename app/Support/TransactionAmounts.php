<?php

namespace App\Support;

use App\Contracts\ExchangeRate;
use App\Exceptions\MissingExchangeRate;
use App\Models\Account;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Splits a transaction into the original amount, the movement in the account's
 * currency, and the base-currency value at the rate for that date.
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
        string $currency,
        CarbonInterface $on,
    ): array {
        $normalized = Decimal::of($amount)->toString(MoneyConverter::SCALE);

        if (Decimal::of($normalized)->isZero()) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount other than zero.',
            ]);
        }

        $workspace = $account->workspace;

        try {
            $base = $this->converter->baseAmount($normalized, $currency, $workspace, $on);
            $rate = $this->exchangeRate->basePerUnit($workspace, $currency, $on);
        } catch (MissingExchangeRate) {
            throw ValidationException::withMessages([
                'currency' => "Enter a rate for {$currency} before using it.",
            ]);
        }

        return [
            'amount' => $normalized,
            'account_amount' => $currency === $account->currency
                ? $normalized
                : $this->converter->displayAmount($base, $account->currency, $workspace, $on),
            'base_amount' => $base,
            'exchange_rate' => $rate,
        ];
    }
}
