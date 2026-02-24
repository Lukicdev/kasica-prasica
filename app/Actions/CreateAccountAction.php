<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Account;
use App\Models\User;

final class CreateAccountAction
{
    /**
     * Create a new account.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Account
    {
        return Account::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'currency' => $data['currency'],
            'initial_balance' => $data['initial_balance'],
            'is_shared' => $data['is_shared'] ?? false,
        ]);
    }
}
