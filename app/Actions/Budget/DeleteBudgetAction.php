<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Models\Budget;

final class DeleteBudgetAction
{
    /**
     * Delete a budget.
     */
    public function handle(Budget $budget): void
    {
        $budget->delete();
    }
}
