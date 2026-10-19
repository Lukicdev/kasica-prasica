<?php

namespace App\Support;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Account balances are calculated, never stored.
 *
 * Balance = opening balance + income − expenses, using each transaction's
 * amount in the account's own currency. A negative expense is a refund, so
 * it increases the balance.
 */
class AccountBalance
{
    /**
     * @param  Collection<int, Account>  $accounts
     * @return array<int, string>
     */
    public function forAccounts(Collection $accounts): array
    {
        /** @var array<int, Decimal> $totals */
        $totals = [];

        foreach ($accounts as $account) {
            $totals[$account->id] = Decimal::of((string) $account->opening_balance);
        }

        if ($totals === []) {
            return [];
        }

        $transactions = Transaction::query()
            ->whereIn('account_id', array_keys($totals))
            ->get(['account_id', 'type', 'account_amount']);

        foreach ($transactions as $transaction) {
            $totals[$transaction->account_id] = $this->adjust(
                $totals[$transaction->account_id],
                $transaction->type,
                (string) $transaction->account_amount,
            );
        }

        $balances = [];

        foreach ($totals as $id => $total) {
            $balances[$id] = $total->toString(MoneyConverter::SCALE);
        }

        return $balances;
    }

    public function of(Account $account): string
    {
        return $this->forAccounts(new Collection([$account]))[$account->id];
    }

    public function apply(string $balance, TransactionType $type, string $accountAmount): string
    {
        return $this->adjust(Decimal::of($balance), $type, $accountAmount)
            ->toString(MoneyConverter::SCALE);
    }

    private function adjust(Decimal $balance, TransactionType $type, string $accountAmount): Decimal
    {
        $effect = Decimal::of($accountAmount);

        return $type === TransactionType::Income
            ? $balance->add($effect)
            : $balance->subtract($effect);
    }
}
