<?php

namespace App\Actions\Accounts;

use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class RestoreAccount
{
    public function handle(Account $account, User $actor): Account
    {
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
