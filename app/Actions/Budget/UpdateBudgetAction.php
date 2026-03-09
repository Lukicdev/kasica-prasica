<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\Budget;

final class UpdateBudgetAction
{
    /**
     * Update a budget.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Budget $budget, array $data): Budget
    {
        $budget->update($data);

        return $budget->fresh();
    }
}
