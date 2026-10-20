<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $this->sharesWorkspace($user, $transaction);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $this->sharesWorkspace($user, $transaction);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->sharesWorkspace($user, $transaction);
    }

    private function sharesWorkspace(User $user, Transaction $transaction): bool
    {
        return $user->workspace_id === $transaction->workspace_id;
    }
}
