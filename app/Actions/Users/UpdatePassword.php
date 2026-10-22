<?php

namespace App\Actions\Users;

use App\Models\User;

class UpdatePassword
{
    public function handle(User $user, string $password): void
    {
        $user->update([
            'password' => $password,
        ]);
    }
}
