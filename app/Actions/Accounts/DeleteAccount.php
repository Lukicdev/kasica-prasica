<?php

namespace App\Actions\Accounts;

use App\Models\Account;
use App\Models\User;

class DeleteAccount
{
    public function handle(Account $account, User $actor): void
    {
        $account->delete();
    }
}
