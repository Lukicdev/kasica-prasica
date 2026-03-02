<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

final class DeleteTransactionAction
{
    /**
     * Delete a transaction.
     */
    public function handle(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Revert the transaction's effect on account balance before deleting
            $this->revertAccountBalance($transaction);

            $transaction->delete();
        });
    }

    /**
     * Revert the account balance based on the transaction.
     */
    private function revertAccountBalance(Transaction $transaction): void
    {
        $account = Account::findOrFail($transaction->account_id);
        $amount = (float) $transaction->amount;

        match ($transaction->type) {
            TransactionType::Income => $account->decrement('balance', $amount),
            TransactionType::Expense => $account->increment('balance', $amount),
            TransactionType::Transfer => $account->increment('balance', $amount),
        };
    }
}
