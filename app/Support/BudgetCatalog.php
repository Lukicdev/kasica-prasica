<?php

namespace App\Support;

use App\Enums\CategoryKind;
use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\BudgetMonth;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The budget screen for one calendar month.
 */
class BudgetCatalog
{
    public function __construct(
        private MoneyConverter $converter,
        private MoneyFormatter $formatter,
    ) {}

    /**
     * @return array{
     *     month: array{value: string, label: string, previous: string, next: string},
     *     started: bool,
     *     editable: bool,
     *     copy_from: array{value: string, label: string}|null,
     *     categories: list<array{
     *         id: int,
     *         name: string,
     *         icon: string|null,
     *         color: string|null,
     *         description: string|null,
     *         archived: bool,
     *         amount: string,
     *         display: array{
     *             amount: string,
     *             currency: string,
     *             formatted: string,
     *             original_amount: string|null,
     *             original_currency: string|null,
     *             original_formatted: string|null
     *         },
     *         updated_by: string|null
     *     }>,
     *     income: array{amount: string, currency: string, formatted: string, negative: bool, base_amount: string},
     *     budget: array{amount: string, currency: string, formatted: string, negative: bool, base_amount: string},
     *     unallocated: array{amount: string, currency: string, formatted: string, negative: bool, base_amount: string}
     * }
     */
    public function page(User $user, CalendarMonth $month): array
    {
        $workspace = $user->workspace;
        $display = $user->display_currency;

        $categories = Category::query()
            ->whereBelongsTo($workspace)
            ->where('kind', CategoryKind::Expense)
            ->ordered()
            ->get();

        $budgetMonth = BudgetMonth::query()
            ->whereBelongsTo($workspace)
            ->where('month', $month->value())
            ->with(['lines.editor:id,name'])
            ->first();

        $lines = $budgetMonth instanceof BudgetMonth
            ? $budgetMonth->lines->keyBy('category_id')
            : new Collection;

        $incomeBase = $this->incomeBase($user, $month);
        $budgetBase = $this->budgetBase($categories, $lines);
        $unallocatedBase = Decimal::of($incomeBase)->subtract(Decimal::of($budgetBase))->toString(MoneyConverter::SCALE);

        return [
            'month' => [
                'value' => $month->value(),
                'label' => $month->label(),
                'previous' => $month->previous()->value(),
                'next' => $month->next()->value(),
            ],
            'started' => $budgetMonth instanceof BudgetMonth,
            'editable' => $display->isBase(),
            'copy_from' => $this->copyFrom($user, $month),
            'categories' => array_values($this->visible($categories, $lines)->map(
                fn (Category $category): array => $this->row($category, $lines->get($category->id), $display),
            )->all()),
            'income' => $this->figure($incomeBase, $display),
            'budget' => $this->figure($budgetBase, $display),
            'unallocated' => $this->figure($unallocatedBase, $display),
        ];
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, CategoryBudget>  $lines
     * @return Collection<int, Category>
     */
    private function visible(Collection $categories, Collection $lines): Collection
    {
        return $categories
            ->filter(function (Category $category) use ($lines): bool {
                if ($category->archived_at === null) {
                    return true;
                }

                return $lines->has($category->id);
            })
            ->sortBy(fn (Category $category): array => [
                $category->archived_at === null ? 0 : 1,
                $category->position,
                $category->id,
            ])
            ->values();
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, CategoryBudget>  $lines
     */
    private function budgetBase(Collection $categories, Collection $lines): string
    {
        $total = Decimal::of('0');

        foreach ($categories as $category) {
            $line = $lines->get($category->id);

            if (! $line instanceof CategoryBudget) {
                continue;
            }

            $total = $total->add(Decimal::of((string) $line->amount));
        }

        return $total->toString(MoneyConverter::SCALE);
    }

    private function incomeBase(User $user, CalendarMonth $month): string
    {
        $amounts = Transaction::query()
            ->whereBelongsTo($user->workspace)
            ->where('type', TransactionType::Income)
            ->where('booked_on', '>=', $month->start()->toDateString())
            ->where('booked_on', '<', $month->endExclusive()->toDateString())
            ->pluck('base_amount');

        $total = Decimal::of('0');

        foreach ($amounts as $amount) {
            $total = $total->add(Decimal::of((string) $amount));
        }

        return $total->toString(MoneyConverter::SCALE);
    }

    /**
     * @return array{value: string, label: string}|null
     */
    private function copyFrom(User $user, CalendarMonth $month): ?array
    {
        $previous = $month->previous();
        $exists = BudgetMonth::query()
            ->whereBelongsTo($user->workspace)
            ->where('month', $previous->value())
            ->exists();

        if (! $exists) {
            return null;
        }

        return [
            'value' => $previous->value(),
            'label' => $previous->label(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     icon: string|null,
     *     color: string|null,
     *     description: string|null,
     *     archived: bool,
     *     amount: string,
     *     display: array{
     *         amount: string,
     *         currency: string,
     *         formatted: string,
     *         original_amount: string|null,
     *         original_currency: string|null,
     *         original_formatted: string|null
     *     },
     *     updated_by: string|null
     * }
     */
    private function row(Category $category, mixed $line, Currency $display): array
    {
        $amount = $line instanceof CategoryBudget
            ? Decimal::of((string) $line->amount)->toString(MoneyConverter::SCALE)
            : '0.00';

        return [
            'id' => $category->id,
            'name' => $category->name,
            'icon' => $category->icon,
            'color' => $category->color,
            'description' => $category->description,
            'archived' => $category->archived_at !== null,
            'amount' => $amount,
            'display' => $this->converter->present($amount, Currency::base(), $amount, $display)->toArray(),
            'updated_by' => $this->updatedBy($line),
        ];
    }

    private function updatedBy(mixed $line): ?string
    {
        if (! $line instanceof CategoryBudget) {
            return null;
        }

        $editor = $line->editor;

        if (
            $editor === null
            || $line->updated_at === null
            || $line->created_at === null
            || ! $line->updated_at->greaterThan($line->created_at)
        ) {
            return null;
        }

        return $editor->name;
    }

    /**
     * @return array{amount: string, currency: string, formatted: string, negative: bool, base_amount: string}
     */
    private function figure(string $baseAmount, Currency $display): array
    {
        $normalized = Decimal::of($baseAmount)->toString(MoneyConverter::SCALE);
        $amount = $this->converter->displayAmount($normalized, $display);

        return [
            'amount' => $amount,
            'currency' => $display->value,
            'formatted' => $this->formatter->format($amount, $display),
            'negative' => Decimal::of($amount)->isNegative(),
            'base_amount' => $normalized,
        ];
    }
}
