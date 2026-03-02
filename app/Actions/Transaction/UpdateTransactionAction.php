<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

final class UpdateTransactionAction
{
    /**
     * Update a transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $oldAccountId = $transaction->account_id;
            $oldType = $transaction->type;
            $oldAmount = (float) $transaction->amount;

            // Revert the old transaction's effect on balance
            $this->revertAccountBalance($transaction);

            // Update the transaction
            $transaction->update($data);
            $transaction->refresh();

            // Apply the new transaction's effect on balance
            $this->updateAccountBalance($transaction);

            // If account changed, also revert from old account
            if (isset($data['account_id']) && $data['account_id'] !== $oldAccountId) {
                $oldAccount = Account::findOrFail($oldAccountId);
                $this->revertTransactionEffect($oldAccount, $oldType, $oldAmount);
            }

            return $transaction->fresh();
        });
    }

    /**
     * Revert the account balance based on the transaction.
     */
    private function revertAccountBalance(Transaction $transaction): void
    {
        $account = Account::findOrFail($transaction->account_id);
        $this->revertTransactionEffect($account, $transaction->type, (float) $transaction->amount);
    }

    /**
     * Revert a transaction's effect on an account balance.
     */
    private function revertTransactionEffect(Account $account, TransactionType $type, float $amount): void
    {
        match ($type) {
            TransactionType::Income => $account->decrement('balance', $amount),
            TransactionType::Expense => $account->increment('balance', $amount),
            TransactionType::Transfer => $account->increment('balance', $amount),
        };
    }

    /**
     * Update the account balance based on the transaction.
     */
    private function updateAccountBalance(Transaction $transaction): void
    {
        $account = Account::findOrFail($transaction->account_id);
        $amount = (float) $transaction->amount;

        match ($transaction->type) {
            TransactionType::Income => $account->increment('balance', $amount),
            TransactionType::Expense => $account->decrement('balance', $amount),
            TransactionType::Transfer => $account->decrement('balance', $amount),
        };
    }
}
