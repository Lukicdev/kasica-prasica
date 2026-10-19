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
        ?string $accountAmount,
        CarbonInterface $on,
    ): array {
        $normalized = Decimal::of($amount)->toString(MoneyConverter::SCALE);

        if (Decimal::of($normalized)->isZero()) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount other than zero.',
            ]);
        }

        if ($currency === $account->currency) {
            $settled = $normalized;
        } else {
            $settled = $this->confirmedAccountAmount($normalized, $accountAmount);
        }

        return [
            'amount' => $normalized,
            'account_amount' => $settled,
            'base_amount' => $this->converter->baseAmount($normalized, $currency, $on),
            'exchange_rate' => $this->exchangeRate->rsdPerUnit($currency, $on),
        ];
    }

    private function confirmedAccountAmount(string $amount, ?string $accountAmount): string
    {
        if ($accountAmount === null || trim($accountAmount) === '') {
            throw ValidationException::withMessages([
                'account_amount' => 'Confirm the amount in the account currency.',
            ]);
        }

        $settled = Decimal::of($accountAmount)->toString(MoneyConverter::SCALE);

        if (Decimal::of($settled)->isZero()) {
            throw ValidationException::withMessages([
                'account_amount' => 'Confirm the amount in the account currency.',
            ]);
        }

        if (Decimal::of($settled)->isNegative() !== Decimal::of($amount)->isNegative()) {
            throw ValidationException::withMessages([
                'account_amount' => 'The account amount must have the same sign as the transaction amount.',
            ]);
        }

        return $settled;
    }
}
