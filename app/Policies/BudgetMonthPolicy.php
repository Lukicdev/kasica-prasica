<?php

namespace App\Policies;

use App\Models\BudgetMonth;
use App\Models\User;

class BudgetMonthPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BudgetMonth $budgetMonth): bool
    {
        return $this->sharesWorkspace($user, $budgetMonth);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, BudgetMonth $budgetMonth): bool
    {
        return $this->sharesWorkspace($user, $budgetMonth);
    }

    private function sharesWorkspace(User $user, BudgetMonth $budgetMonth): bool
    {
        return $user->workspace_id === $budgetMonth->workspace_id;
    }
}
