<?php

namespace App\Actions\Accounts;

use App\Enums\AccountType;
use App\Enums\Currency;
use App\Models\Account;
use App\Models\User;

class CreateAccount
{
    /**
     * @param  array{
     *     name: string,
     *     type: AccountType,
     *     currency: Currency,
     *     opening_balance: string,
     *     opening_balance_on: string,
     *     note: string|null
     * }  $attributes
     */
    public function handle(User $actor, array $attributes): Account
    {
        return Account::query()->create([
            'workspace_id' => $actor->workspace_id,
            'name' => $attributes['name'],
            'type' => $attributes['type'],
            'currency' => $attributes['currency'],
            'opening_balance' => $attributes['opening_balance'],
            'opening_balance_on' => $attributes['opening_balance_on'],
            'note' => $attributes['note'],
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }
}
