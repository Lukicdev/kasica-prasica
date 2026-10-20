<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Transaction;

/**
 * Opening balance first, then every transaction from oldest to newest.
 * The running balance starts at the opening balance and applies each
 * transaction, including any dated before the opening balance.
 */
class AccountStatement
{
    public function __construct(
        private AccountBalance $balances,
        private MoneyFormatter $formatter,
    ) {}

    /**
     * @return list<array{
     *     id: int|null,
     *     kind: string,
     *     date: string,
     *     date_label: string,
     *     type: string|null,
     *     type_label: string,
     *     note: string|null,
     *     person: string|null,
     *     amount: array{
     *         amount: string,
     *         currency: string,
     *         formatted: string,
     *         negative: bool,
     *         original_formatted: string|null
     *     }|null,
     *     running_balance: array{amount: string, currency: string, formatted: string, negative: bool}
     * }>
     */
    public function lines(Account $account): array
    {
        $running = Decimal::of((string) $account->opening_balance)->toString(MoneyConverter::SCALE);

        $lines = [[
            'id' => null,
            'kind' => 'opening',
            'date' => $account->opening_balance_on->toDateString(),
            'date_label' => $account->opening_balance_on->format('j M Y'),
            'type' => null,
            'type_label' => 'Opening balance',
            'note' => null,
            'person' => null,
            'amount' => null,
            'running_balance' => $this->money($running, $account),
        ]];

        $transactions = $account->transactions()
            ->with('person:id,name')
            ->orderBy('booked_on')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $running = $this->balances->apply($running, $transaction->type, (string) $transaction->account_amount);

            $lines[] = [
                'id' => $transaction->id,
                'kind' => 'transaction',
                'date' => $transaction->booked_on->toDateString(),
                'date_label' => $transaction->booked_on->format('j M Y'),
                'type' => $transaction->type->value,
                'type_label' => $transaction->typeLabel(),
                'note' => $transaction->note,
                'person' => $transaction->person?->name,
                'amount' => $this->amount($transaction, $account),
                'running_balance' => $this->money($running, $account),
            ];
        }

        return $lines;
    }

    /**
     * @return array{
     *     amount: string,
     *     currency: string,
     *     formatted: string,
     *     negative: bool,
     *     original_formatted: string|null
     * }
     */
    private function amount(Transaction $transaction, Account $account): array
    {
        $accountAmount = Decimal::of((string) $transaction->account_amount)->toString(MoneyConverter::SCALE);
        $originalAmount = Decimal::of((string) $transaction->amount)->toString(MoneyConverter::SCALE);

        return [
            'amount' => $accountAmount,
            'currency' => $account->currency->value,
            'formatted' => $this->formatter->format($accountAmount, $account->currency),
            'negative' => Decimal::of($accountAmount)->isNegative(),
            'original_formatted' => $transaction->currency === $account->currency
                ? null
                : $this->formatter->format($originalAmount, $transaction->currency),
        ];
    }

    /**
     * @return array{amount: string, currency: string, formatted: string, negative: bool}
     */
    private function money(string $amount, Account $account): array
    {
        $normalized = Decimal::of($amount)->toString(MoneyConverter::SCALE);

        return [
            'amount' => $normalized,
            'currency' => $account->currency->value,
            'formatted' => $this->formatter->format($normalized, $account->currency),
            'negative' => Decimal::of($normalized)->isNegative(),
        ];
    }
}
