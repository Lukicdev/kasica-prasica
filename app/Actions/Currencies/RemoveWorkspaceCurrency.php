<?php

namespace App\Actions\Currencies;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\WorkspaceCurrency;
use Illuminate\Validation\ValidationException;

class RemoveWorkspaceCurrency
{
    public function handle(WorkspaceCurrency $currency): void
    {
        $workspace = $currency->workspace;

        if ($currency->code === $workspace->base_currency) {
            throw ValidationException::withMessages([
                'code' => 'The base currency stays enabled.',
            ]);
        }

        $used = Account::query()->whereBelongsTo($workspace)->where('currency', $currency->code)->exists()
            || Transaction::query()->whereBelongsTo($workspace)->where('currency', $currency->code)->exists();

        if ($used) {
            throw ValidationException::withMessages([
                'code' => 'This currency is used by an account or a transaction.',
            ]);
        }

        $currency->delete();
    }
}
