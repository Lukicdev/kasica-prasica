<?php

namespace App\Actions\Accounts;

use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class RestoreAccount
{
    public function handle(Account $account, User $actor): Account
    {
        if ($actor->workspace_id !== $account->workspace_id) {
            throw new AuthorizationException('This account is outside your workspace.');
        }

        if ($account->archived_at === null) {
            return $account;
        }

        $account->update([
            'archived_at' => null,
            'updated_by' => $actor->id,
        ]);

        return $account;
    }
}
