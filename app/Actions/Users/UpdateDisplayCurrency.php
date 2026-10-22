<?php

namespace App\Actions\Users;

use App\Models\User;

class UpdateDisplayCurrency
{
    public function handle(User $user, string $currency): void
    {
        $user->update([
            'display_currency' => $currency,
        ]);
    }
}
