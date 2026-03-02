<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateTransactionAction
{
    /**
     * Create a new transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'account_id' => $data['account_id'],
                'category_id' => $data['category_id'] ?? null,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'related_transaction_id' => $data['related_transaction_id'] ?? null,
            ]);

            $this->updateAccountBalance($transaction);

            return $transaction;
        });
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
            TransactionType::Transfer => $this->handleTransferBalance($transaction, $account),
        };
    }

    /**
     * Handle balance updates for transfer transactions.
     */
    private function handleTransferBalance(Transaction $transaction, Account $account): void
    {
        // For transfers, subtract from source account
        $account->decrement('balance', (float) $transaction->amount);

        // If there's a related transaction, add to destination account
        if ($transaction->related_transaction_id) {
            $relatedTransaction = Transaction::find($transaction->related_transaction_id);
            if ($relatedTransaction && $relatedTransaction->account_id !== $account->id) {
                $destinationAccount = Account::findOrFail($relatedTransaction->account_id);
                $destinationAccount->increment('balance', (float) $transaction->amount);
            }
        }
    }
}
