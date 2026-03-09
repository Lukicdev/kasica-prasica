<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class ListBudgetsAction
{
    /**
     * Get budgets for the user.
     *
     * @return Collection<int, Budget>
     */
    public function handle(User $user): Collection
    {
        return Budget::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }
}
