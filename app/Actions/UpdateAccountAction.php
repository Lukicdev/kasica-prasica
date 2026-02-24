<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Account;

final class UpdateAccountAction
{
    /**
     * Update an account.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Account $account, array $data): Account
    {
        $account->update($data);

        return $account->fresh();
    }
}
