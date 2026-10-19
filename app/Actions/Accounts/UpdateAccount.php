<?php

namespace App\Actions\Accounts;

use App\Enums\AccountType;
use App\Enums\Currency;
use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class UpdateAccount
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
    public function handle(Account $account, User $actor, array $attributes): Account
    {
        if ($actor->workspace_id !== $account->workspace_id) {
            throw new AuthorizationException('This account is outside your workspace.');
        }

        if ($account->transactions()->exists() && $attributes['currency'] !== $account->currency) {
            throw ValidationException::withMessages([
                'currency' => 'Currency cannot change once the account has transactions.',
            ]);
        }

        $account->update([
            'name' => $attributes['name'],
            'type' => $attributes['type'],
            'currency' => $attributes['currency'],
            'opening_balance' => $attributes['opening_balance'],
            'opening_balance_on' => $attributes['opening_balance_on'],
            'note' => $attributes['note'],
            'updated_by' => $actor->id,
        ]);

        return $account;
    }
}
