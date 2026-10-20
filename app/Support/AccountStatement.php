<?php

namespace App\Support;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;

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

        $transactions = Transaction::query()
            ->where(function (Builder $query) use ($account): void {
                $query->where('account_id', $account->id)
                    ->orWhere('destination_account_id', $account->id);
            })
            ->with([
                'person:id,name',
                'account:id,name',
                'destinationAccount:id,name',
            ])
            ->orderBy('booked_on')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $running = $this->balances->applyOnAccount($running, $transaction, $account->id);

            $lines[] = [
                'id' => $transaction->id,
                'kind' => 'transaction',
                'date' => $transaction->booked_on->toDateString(),
                'date_label' => $transaction->booked_on->format('j M Y'),
                'type' => $transaction->type->value,
                'type_label' => $this->typeLabel($transaction, $account),
                'note' => $transaction->note,
                'person' => $transaction->person?->name,
                'amount' => $this->amount($transaction, $account),
                'running_balance' => $this->money($running, $account),
            ];
        }

        return $lines;
    }

    private function typeLabel(Transaction $transaction, Account $account): string
    {
        if ($transaction->type !== TransactionType::Transfer) {
            return $transaction->typeLabel();
        }

        if ($transaction->destination_account_id === $account->id) {
            return 'Transfer from '.$transaction->account->name;
        }

        return 'Transfer to '.$transaction->destinationAccount->name;
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
        $incoming = $transaction->type === TransactionType::Transfer
            && $transaction->destination_account_id === $account->id;
        $movement = $incoming
            ? (string) ($transaction->destination_amount ?? '0')
            : (string) $transaction->account_amount;
        $accountAmount = Decimal::of($movement)->toString(MoneyConverter::SCALE);
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
