<?php

namespace App\Money;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Account balances are calculated, never stored.
 *
 * Balance = opening balance + income − expenses + adjustments, using each
 * movement in the account's own currency. A transfer subtracts from the
 * source account and adds to the destination. A negative expense is a refund,
 * so it increases the balance. Pending repeats are not transactions, so they
 * are left out.
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

        $ids = array_keys($totals);

        $transactions = Transaction::query()
            ->where(function (Builder $query) use ($ids): void {
                $query->whereIn('account_id', $ids)
                    ->orWhereIn('destination_account_id', $ids);
            })
            ->get([
                'account_id',
                'destination_account_id',
                'type',
                'account_amount',
                'destination_amount',
            ]);

        foreach ($transactions as $transaction) {
            foreach ($this->affectedAccountIds($transaction) as $accountId) {
                if (! isset($totals[$accountId])) {
                    continue;
                }

                $totals[$accountId] = $totals[$accountId]->add(
                    $this->signedEffect($transaction, $accountId),
                );
            }
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
        $effect = Decimal::of($accountAmount);

        $next = match ($type) {
            TransactionType::Income, TransactionType::Adjustment => Decimal::of($balance)->add($effect),
            TransactionType::Expense => Decimal::of($balance)->subtract($effect),
            TransactionType::Transfer => Decimal::of($balance)->subtract($effect),
        };

        return $next->toString(MoneyConverter::SCALE);
    }

    public function applyOnAccount(string $balance, Transaction $transaction, int $accountId): string
    {
        return Decimal::of($balance)
            ->add($this->signedEffect($transaction, $accountId))
            ->toString(MoneyConverter::SCALE);
    }

    private function signedEffect(Transaction $transaction, int $accountId): Decimal
    {
        if ($transaction->type === TransactionType::Transfer) {
            if ($transaction->account_id === $accountId) {
                return Decimal::of('0')->subtract(Decimal::of((string) $transaction->account_amount));
            }

            if ($transaction->destination_account_id === $accountId) {
                return Decimal::of((string) ($transaction->destination_amount ?? '0'));
            }

            return Decimal::of('0');
        }

        if ($transaction->account_id !== $accountId) {
            return Decimal::of('0');
        }

        $effect = Decimal::of((string) $transaction->account_amount);

        return $transaction->type === TransactionType::Income || $transaction->type === TransactionType::Adjustment
            ? $effect
            : Decimal::of('0')->subtract($effect);
    }

    /**
     * @return list<int>
     */
    private function affectedAccountIds(Transaction $transaction): array
    {
        $ids = [$transaction->account_id];

        if (is_int($transaction->destination_account_id)) {
            $ids[] = $transaction->destination_account_id;
        }

        return array_values(array_unique($ids));
    }
}
