<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\Budget;
use App\Models\User;

final class CreateBudgetAction
{
    /**
     * Create a new budget.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Budget
    {
        return Budget::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'amount' => $data['amount'],
            'period' => $data['period'],
        ]);
    }
}
