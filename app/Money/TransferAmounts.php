<?php

namespace App\Money;

use App\Models\Account;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * A transfer's source movement, destination movement, and base-currency value.
 * Both account movements use the rate stored for that date.
 */
class TransferAmounts
{
    public function __construct(
        private TransactionAmounts $amounts,
        private MoneyConverter $converter,
    ) {}

    /**
     * @return array{
     *     amount: string,
     *     account_amount: string,
     *     destination_amount: string,
     *     base_amount: string,
     *     exchange_rate: string
     * }
     */
    public function resolve(
        Account $from,
        Account $to,
        string $amount,
        string $currency,
        CarbonInterface $on,
    ): array {
        $source = $this->amounts->resolve($from, $amount, $currency, $on);

        if (Decimal::of($source['amount'])->isNegative()) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount greater than zero.',
            ]);
        }

        $destinationAmount = $currency === $to->currency
            ? $source['amount']
            : ($from->currency === $to->currency
                ? $source['account_amount']
                : $this->converter->displayAmount($source['base_amount'], $to->currency, $from->workspace, $on));

        if (Decimal::of($destinationAmount)->isZero()) {
            throw ValidationException::withMessages([
                'amount' => 'This amount is too small to move into the other currency.',
            ]);
        }

        return [
            'amount' => $source['amount'],
            'account_amount' => $source['account_amount'],
            'destination_amount' => $destinationAmount,
            'base_amount' => $source['base_amount'],
            'exchange_rate' => $source['exchange_rate'],
        ];
    }
}
