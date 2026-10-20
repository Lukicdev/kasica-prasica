<?php

namespace App\Actions\Budgets;

use App\Models\BudgetMonth;
use App\Models\CategoryBudget;
use App\Models\User;
use App\Support\CalendarMonth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartBudgetMonth
{
    /**
     * Open a month. Copy takes the previous calendar month's amounts.
     * An empty start records the month and leaves every category at zero.
     */
    public function handle(User $actor, CalendarMonth $month, bool $copy): BudgetMonth
    {
        return DB::transaction(function () use ($actor, $month, $copy): BudgetMonth {
            $existing = BudgetMonth::query()
                ->whereBelongsTo($actor->workspace)
                ->where('month', $month->value())
                ->lockForUpdate()
                ->first();

            if ($existing instanceof BudgetMonth) {
                throw ValidationException::withMessages([
                    'month' => 'This month already has a budget.',
                ]);
            }

            $budgetMonth = BudgetMonth::query()->create([
                'workspace_id' => $actor->workspace_id,
                'month' => $month->value(),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            if ($copy) {
                foreach ($this->previousLines($actor, $month) as $line) {
                    CategoryBudget::query()->create([
                        'budget_month_id' => $budgetMonth->id,
                        'category_id' => $line->category_id,
                        'amount' => $line->amount,
                        'created_by' => $actor->id,
                        'updated_by' => $actor->id,
                    ]);
                }
            }

            return $budgetMonth;
        });
    }

    /**
     * @return Collection<int, CategoryBudget>
     */
    private function previousLines(User $actor, CalendarMonth $month): Collection
    {
        $previous = BudgetMonth::query()
            ->whereBelongsTo($actor->workspace)
            ->where('month', $month->previous()->value())
            ->with('lines')
            ->lockForUpdate()
            ->first();

        if (! $previous instanceof BudgetMonth) {
            throw ValidationException::withMessages([
                'copy' => 'The previous month has no budget to copy.',
            ]);
        }

        return $previous->lines;
    }
}
