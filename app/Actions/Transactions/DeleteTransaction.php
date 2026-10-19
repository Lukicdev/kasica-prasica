<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteTransaction
{
    public function handle(Transaction $transaction, User $actor): void
    {
        if ($actor->workspace_id !== $transaction->workspace_id) {
            throw new AuthorizationException('This transaction is outside your workspace.');
        }

        $transaction->delete();
    }
}
