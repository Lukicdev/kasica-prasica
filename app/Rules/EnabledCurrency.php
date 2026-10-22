<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EnabledCurrency implements ValidationRule
{
    public function __construct(private ?User $user) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->user instanceof User || ! is_string($value)) {
            $fail('Choose an enabled currency.');

            return;
        }

        $enabled = $this->user->workspace->currencies()
            ->where('code', strtoupper($value))
            ->exists();

        if (! $enabled) {
            $fail('Choose an enabled currency.');
        }
    }
}
