<?php

namespace App\Actions\Budgets;

use App\Enums\CategoryKind;
use App\Models\BudgetMonth;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\User;
use App\Support\CalendarMonth;
use App\Support\Decimal;
use App\Support\MoneyConverter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCategoryBudget
{
    /**
     * Set one category's budget for a month that has already been started.
     * The amount is dinars, rounded to two decimals.
     */
    public function handle(User $actor, CalendarMonth $month, Category $category, string $amount): CategoryBudget
    {
        $this->guard($actor, $category);

        $stored = Decimal::of($amount)->toString(MoneyConverter::SCALE);

        return DB::transaction(function () use ($actor, $month, $category, $stored): CategoryBudget {
            $budgetMonth = BudgetMonth::query()
                ->whereBelongsTo($actor->workspace)
                ->where('month', $month->value())
                ->lockForUpdate()
                ->first();

            if (! $budgetMonth instanceof BudgetMonth) {
                throw ValidationException::withMessages([
                    'month' => 'Start this month before setting a budget.',
                ]);
            }

            $line = CategoryBudget::query()
                ->whereBelongsTo($budgetMonth)
                ->where('category_id', $category->id)
                ->lockForUpdate()
                ->first();

            if (! $line instanceof CategoryBudget && $category->archived_at !== null) {
                throw ValidationException::withMessages([
                    'category_id' => 'This category is archived.',
                ]);
            }

            $budgetMonth->update([
                'updated_by' => $actor->id,
            ]);

            if ($line instanceof CategoryBudget) {
                $line->update([
                    'amount' => $stored,
                    'updated_by' => $actor->id,
                ]);

                return $line;
            }

            return CategoryBudget::query()->create([
                'budget_month_id' => $budgetMonth->id,
                'category_id' => $category->id,
                'amount' => $stored,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }

    private function guard(User $actor, Category $category): void
    {
        if ($actor->workspace_id !== $category->workspace_id) {
            throw new AuthorizationException('This category is outside your workspace.');
        }

        if ($category->kind !== CategoryKind::Expense) {
            throw ValidationException::withMessages([
                'category_id' => 'Choose an expense category.',
            ]);
        }
    }
}
