<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Account;

final class DeleteAccountAction
{
    /**
     * Delete an account.
     */
    public function handle(Account $account): void
    {
        $account->delete();
    }
}
