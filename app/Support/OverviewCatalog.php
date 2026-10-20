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
 * The month overview: income, expenses against the budget, and account balances.
 *
 * Spent and income are sums of transactions in the base currency. Difference
 * is spent minus budget. Leftover is income minus spent. Unallocated is
 * income minus the budget.
 */
class OverviewCatalog
{
    public function __construct(
        private AccountCatalog $accounts,
        private MoneyConverter $converter,
        private RecurrenceCatalog $recurrences,
    ) {}

    /**
     * @return array{
     *     month: array{value: string, label: string, previous: string, next: string, entry_date: string},
     *     first_run: bool,
     *     recorded: bool,
     *     budget_started: bool,
     *     steps: list<array{key: string, done: bool}>,
     *     income: array{rows: list<array<string, mixed>>, total: array<string, mixed>},
     *     expenses: array{rows: list<array<string, mixed>>, total: array<string, mixed>, sort: string, overspends: list<array<string, mixed>>},
     *     totals: array<string, array<string, mixed>>,
     *     accounts: array{items: list<array<string, mixed>>, total: array<string, mixed>},
     *     drilldown: array<string, mixed>|null,
     *     pending: list<array<string, mixed>>
     * }
     */
    public function page(User $user, CalendarMonth $month, ?int $categoryId, string $sort = 'category'): array
    {
        $workspace = $user->workspace;
        $display = $user->display_currency;

        $transactions = Transaction::query()
            ->whereBelongsTo($workspace)
            ->where('booked_on', '>=', $month->start()->toDateString())
            ->where('booked_on', '<', $month->endExclusive()->toDateString())
            ->get(['id', 'category_id', 'person_id', 'type', 'base_amount']);

        $expenses = $transactions
            ->filter(fn (Transaction $transaction): bool => $transaction->type === TransactionType::Expense)
            ->values();
        $incomes = $transactions
            ->filter(fn (Transaction $transaction): bool => $transaction->type === TransactionType::Income)
            ->values();

        $spentByCategory = $this->totalsByCategory($expenses);
        $incomeByCategory = $this->totalsByCategory($incomes);
        $spentBase = $this->sumTransactions($expenses);
        $incomeBase = $this->sumTransactions($incomes);

        $categories = Category::query()
            ->whereBelongsTo($workspace)
            ->ordered()
            ->get();

        $budgetMonth = BudgetMonth::query()
            ->whereBelongsTo($workspace)
            ->where('month', $month->value())
            ->with('lines')
            ->first();
        $lines = $budgetMonth instanceof BudgetMonth
            ? $budgetMonth->lines->keyBy('category_id')
            : new Collection;
        $budgetBase = $this->budgetBase($lines);

        $expenseCategories = $categories
            ->filter(fn (Category $category): bool => $category->kind === CategoryKind::Expense)
            ->values();
        $incomeCategories = $categories
            ->filter(fn (Category $category): bool => $category->kind === CategoryKind::Income)
            ->values();

        $expenseRows = $this->expenseRows(
            $this->visible($expenseCategories, $spentByCategory, $lines),
            $spentByCategory,
            $lines,
            $display,
        );
        $incomeRows = $this->incomeRows(
            $this->visible($incomeCategories, $incomeByCategory, new Collection),
            $incomeByCategory,
            $this->peopleByCategory($incomes),
            $display,
        );

        $uncategorisedSpent = $spentByCategory['none'] ?? null;
        if ($uncategorisedSpent !== null) {
            $expenseRows[] = $this->expenseRow(null, 'Uncategorised', null, false, $uncategorisedSpent, '0.00', $display);
        }

        $sort = $sort === 'overspend' ? 'overspend' : 'category';
        $overspends = $this->byOverspend($expenseRows);
        $overspends = array_values(array_filter(
            $overspends,
            fn (array $row): bool => $row['status'] === 'over',
        ));

        if ($sort === 'overspend') {
            $expenseRows = $this->byOverspend($expenseRows);
        }

        $uncategorisedIncome = $incomeByCategory['none'] ?? null;
        if ($uncategorisedIncome !== null) {
            $incomeRows[] = $this->incomeRow(null, 'Uncategorised', null, false, $uncategorisedIncome, [], $display);
        }

        $differenceBase = Decimal::of($spentBase)->subtract(Decimal::of($budgetBase))->toString(MoneyConverter::SCALE);
        $difference = $this->standing($differenceBase);
        $hasAccount = $user->workspace->accounts()->exists();
        $firstRun = $transactions->isEmpty()
            && ! Transaction::query()->whereBelongsTo($workspace)->exists();

        return [
            'month' => [
                'value' => $month->value(),
                'label' => $month->label(),
                'previous' => $month->previous()->value(),
                'next' => $month->next()->value(),
                'entry_date' => $month->value() === CalendarMonth::current()->value()
                    ? now()->toDateString()
                    : $month->date(),
            ],
            'first_run' => $firstRun,
            'recorded' => $transactions->isNotEmpty(),
            'budget_started' => $budgetMonth instanceof BudgetMonth,
            'steps' => [
                ['key' => 'account', 'done' => $hasAccount],
                ['key' => 'budget', 'done' => $budgetMonth instanceof BudgetMonth],
                ['key' => 'transaction', 'done' => ! $firstRun],
            ],
            'income' => [
                'rows' => $incomeRows,
                'total' => $this->figure($incomeBase, $display),
            ],
            'expenses' => [
                'rows' => $expenseRows,
                'total' => [
                    'spent' => $this->figure($spentBase, $display),
                    'budget' => $this->figure($budgetBase, $display),
                    'difference' => $this->figure($differenceBase, $display),
                    'status' => $difference['status'],
                    'status_label' => $difference['label'],
                ],
                'sort' => $sort,
                'overspends' => $overspends,
            ],
            'totals' => [
                'income' => $this->figure($incomeBase, $display),
                'spent' => $this->figure($spentBase, $display),
                'budget' => $this->figure($budgetBase, $display),
                'difference' => $this->figure($differenceBase, $display),
                'leftover' => $this->figure(
                    Decimal::of($incomeBase)->subtract(Decimal::of($spentBase))->toString(MoneyConverter::SCALE),
                    $display,
                ),
                'unallocated' => $this->figure(
                    Decimal::of($incomeBase)->subtract(Decimal::of($budgetBase))->toString(MoneyConverter::SCALE),
                    $display,
                ),
            ],
            'accounts' => $this->accountPanel($user, $display),
            'drilldown' => $this->drilldown($user, $month, $categories, $categoryId, $spentByCategory, $incomeByCategory, $lines, $display),
            'pending' => $this->recurrences->pending($user, $month),
        ];
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  array<int|string, string>  $activity
     * @param  Collection<int, CategoryBudget>  $lines
     * @return Collection<int, Category>
     */
    private function visible(Collection $categories, array $activity, Collection $lines): Collection
    {
        return $categories
            ->filter(function (Category $category) use ($activity, $lines): bool {
                if ($category->archived_at === null) {
                    return true;
                }

                if (array_key_exists($category->id, $activity)) {
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
     * @param  Collection<int, Transaction>  $transactions
     * @return array<int|string, string>
     */
    private function totalsByCategory(Collection $transactions): array
    {
        /** @var array<int|string, Decimal> $totals */
        $totals = [];

        foreach ($transactions as $transaction) {
            $key = $transaction->category_id ?? 'none';
            $totals[$key] = ($totals[$key] ?? Decimal::of('0'))->add(Decimal::of((string) $transaction->base_amount));
        }

        $result = [];

        foreach ($totals as $key => $total) {
            $result[$key] = $total->toString(MoneyConverter::SCALE);
        }

        return $result;
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return array<int|string, list<array{id: int|null, name: string, base: string}>>
     */
    private function peopleByCategory(Collection $transactions): array
    {
        /** @var array<int|string, array<int|string, Decimal>> $totals */
        $totals = [];
        /** @var array<int|string, int|null> $ids */
        $ids = [];

        foreach ($transactions as $transaction) {
            $categoryKey = $transaction->category_id ?? 'none';
            $personKey = $transaction->person_id ?? 'none';
            $totals[$categoryKey][$personKey] = ($totals[$categoryKey][$personKey] ?? Decimal::of('0'))
                ->add(Decimal::of((string) $transaction->base_amount));
            $ids[$personKey] = $transaction->person_id;
        }

        $personIds = array_values(array_filter($ids, fn (?int $id): bool => $id !== null));
        $names = $personIds === []
            ? new Collection
            : User::query()->whereIn('id', $personIds)->pluck('name', 'id');

        $grouped = [];

        foreach ($totals as $categoryKey => $people) {
            $rows = [];

            foreach ($people as $personKey => $total) {
                $id = $ids[$personKey];
                $rows[] = [
                    'id' => $id,
                    'name' => $id === null ? 'Unknown' : (string) $names->get($id, 'Unknown'),
                    'base' => $total->toString(MoneyConverter::SCALE),
                ];
            }

            usort($rows, fn (array $left, array $right): int => [$left['name'], $left['id'] ?? 0] <=> [$right['name'], $right['id'] ?? 0]);
            $grouped[$categoryKey] = $rows;
        }

        return $grouped;
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  array<int|string, string>  $incomeByCategory
     * @param  array<int|string, list<array{id: int|null, name: string, base: string}>>  $peopleByCategory
     * @return list<array<string, mixed>>
     */
    private function incomeRows(Collection $categories, array $incomeByCategory, array $peopleByCategory, Currency $display): array
    {
        $rows = [];

        foreach ($categories as $category) {
            $rows[] = $this->incomeRow(
                $category->id,
                $category->name,
                $category->description,
                $category->archived_at !== null,
                $incomeByCategory[$category->id] ?? '0.00',
                $peopleByCategory[$category->id] ?? [],
                $display,
            );
        }

        return $rows;
    }

    /**
     * @param  list<array{id: int|null, name: string, base: string}>  $people
     * @return array<string, mixed>
     */
    private function incomeRow(
        ?int $id,
        string $name,
        ?string $description,
        bool $archived,
        string $base,
        array $people,
        Currency $display,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'archived' => $archived,
            'amount' => $this->figure($base, $display),
            'people' => array_map(fn (array $person): array => [
                'id' => $person['id'],
                'name' => $person['name'],
                'amount' => $this->figure($person['base'], $display),
            ], $people),
        ];
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  array<int|string, string>  $spentByCategory
     * @param  Collection<int, CategoryBudget>  $lines
     * @return list<array<string, mixed>>
     */
    private function expenseRows(Collection $categories, array $spentByCategory, Collection $lines, Currency $display): array
    {
        $rows = [];

        foreach ($categories as $category) {
            $rows[] = $this->expenseRow(
                $category->id,
                $category->name,
                $category->description,
                $category->archived_at !== null,
                $spentByCategory[$category->id] ?? '0.00',
                $this->budgetAmount($lines, $category->id),
                $display,
            );
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function expenseRow(
        ?int $id,
        string $name,
        ?string $description,
        bool $archived,
        string $spent,
        string $budget,
        Currency $display,
    ): array {
        $differenceBase = Decimal::of($spent)->subtract(Decimal::of($budget))->toString(MoneyConverter::SCALE);
        $standing = $this->standing($differenceBase);

        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'archived' => $archived,
            'spent' => $this->figure($spent, $display),
            'budget' => $this->figure($budget, $display),
            'difference' => $this->figure($differenceBase, $display),
            'status' => $standing['status'],
            'status_label' => $standing['label'],
        ];
    }

    /**
     * @param  Collection<int, CategoryBudget>  $lines
     */
    private function budgetAmount(Collection $lines, int $categoryId): string
    {
        $line = $lines->get($categoryId);

        if (! $line instanceof CategoryBudget) {
            return '0.00';
        }

        return Decimal::of((string) $line->amount)->toString(MoneyConverter::SCALE);
    }

    /**
     * @param  Collection<int, CategoryBudget>  $lines
     */
    private function budgetBase(Collection $lines): string
    {
        $total = Decimal::of('0');

        foreach ($lines as $line) {
            $total = $total->add(Decimal::of((string) $line->amount));
        }

        return $total->toString(MoneyConverter::SCALE);
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    private function sumTransactions(Collection $transactions): string
    {
        $total = Decimal::of('0');

        foreach ($transactions as $transaction) {
            $total = $total->add(Decimal::of((string) $transaction->base_amount));
        }

        return $total->toString(MoneyConverter::SCALE);
    }

    /**
     * Largest amount over budget first. Difference is spent minus budget.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function byOverspend(array $rows): array
    {
        usort($rows, function (array $left, array $right): int {
            $leftDifference = is_array($left['difference'] ?? null) ? (string) ($left['difference']['base_amount'] ?? '0') : '0';
            $rightDifference = is_array($right['difference'] ?? null) ? (string) ($right['difference']['base_amount'] ?? '0') : '0';
            $byAmount = $this->compareMoney($rightDifference, $leftDifference);

            if ($byAmount !== 0) {
                return $byAmount;
            }

            return [(string) ($left['name'] ?? ''), (int) ($left['id'] ?? 0)]
                <=> [(string) ($right['name'] ?? ''), (int) ($right['id'] ?? 0)];
        });

        return $rows;
    }

    private function compareMoney(string $left, string $right): int
    {
        $difference = Decimal::of($left)->subtract(Decimal::of($right));

        if ($difference->isZero()) {
            return 0;
        }

        return $difference->isNegative() ? -1 : 1;
    }

    /**
     * @return array{status: 'over'|'under'|'on', label: string}
     */
    private function standing(string $difference): array
    {
        $value = Decimal::of($difference);

        if ($value->isZero()) {
            return ['status' => 'on', 'label' => 'On budget'];
        }

        if ($value->isNegative()) {
            return ['status' => 'under', 'label' => 'Under budget'];
        }

        return ['status' => 'over', 'label' => 'Over budget'];
    }

    /**
     * @return array{
     *     amount: string,
     *     currency: string,
     *     formatted: string,
     *     original_amount: string|null,
     *     original_currency: string|null,
     *     original_formatted: string|null,
     *     negative: bool,
     *     base_amount: string
     * }
     */
    private function figure(string $baseAmount, Currency $display): array
    {
        $normalized = Decimal::of($baseAmount)->toString(MoneyConverter::SCALE);
        $presented = $this->converter->present($normalized, Currency::base(), $normalized, $display);

        return [
            ...$presented->toArray(),
            'negative' => Decimal::of($presented->amount)->isNegative(),
            'base_amount' => $normalized,
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: array<string, mixed>}
     */
    private function accountPanel(User $user, Currency $display): array
    {
        $accounts = $user->workspace->accounts()->get();
        $list = $this->accounts->list($accounts, $display);
        $items = [];

        foreach ($list['groups'] as $group) {
            foreach ($group['accounts'] as $account) {
                $items[] = $account;
            }
        }

        return [
            'items' => $items,
            'total' => $list['total'],
        ];
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  array<int|string, string>  $spentByCategory
     * @param  array<int|string, string>  $incomeByCategory
     * @param  Collection<int, CategoryBudget>  $lines
     * @return array<string, mixed>|null
     */
    private function drilldown(
        User $user,
        CalendarMonth $month,
        Collection $categories,
        ?int $categoryId,
        array $spentByCategory,
        array $incomeByCategory,
        Collection $lines,
        Currency $display,
    ): ?array {
        if ($categoryId === null) {
            return null;
        }

        $category = $categories->firstWhere('id', $categoryId);

        if (! $category instanceof Category) {
            return null;
        }

        $type = $category->kind === CategoryKind::Income
            ? TransactionType::Income
            : TransactionType::Expense;
        $base = $category->kind === CategoryKind::Income
            ? ($incomeByCategory[$category->id] ?? '0.00')
            : ($spentByCategory[$category->id] ?? '0.00');

        $transactions = Transaction::query()
            ->whereBelongsTo($user->workspace)
            ->where('category_id', $category->id)
            ->where('type', $type)
            ->where('booked_on', '>=', $month->start()->toDateString())
            ->where('booked_on', '<', $month->endExclusive()->toDateString())
            ->with(['account:id,name', 'person:id,name'])
            ->orderBy('booked_on')
            ->orderBy('id')
            ->get([
                'id',
                'account_id',
                'person_id',
                'amount',
                'currency',
                'base_amount',
                'booked_on',
                'note',
            ]);

        $budget = null;
        $difference = null;
        $status = null;
        $statusLabel = null;

        if ($category->kind === CategoryKind::Expense) {
            $budgetBase = $this->budgetAmount($lines, $category->id);
            $differenceBase = Decimal::of($base)->subtract(Decimal::of($budgetBase))->toString(MoneyConverter::SCALE);
            $standing = $this->standing($differenceBase);
            $budget = $this->figure($budgetBase, $display);
            $difference = $this->figure($differenceBase, $display);
            $status = $standing['status'];
            $statusLabel = $standing['label'];
        }

        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'kind' => $category->kind->value,
            'total' => $this->figure($base, $display),
            'budget' => $budget,
            'difference' => $difference,
            'status' => $status,
            'status_label' => $statusLabel,
            'transactions' => $transactions->map(function (Transaction $transaction) use ($display): array {
                $presented = $this->converter->present(
                    (string) $transaction->amount,
                    $transaction->currency,
                    (string) $transaction->base_amount,
                    $display,
                    $transaction->booked_on,
                );

                return [
                    'id' => $transaction->id,
                    'date' => $transaction->booked_on->toDateString(),
                    'date_label' => $transaction->booked_on->format('j M Y'),
                    'note' => $transaction->note,
                    'account' => $transaction->account->name,
                    'person' => $transaction->person?->name,
                    'amount' => [
                        ...$presented->toArray(),
                        'negative' => Decimal::of($presented->amount)->isNegative(),
                        'base_amount' => Decimal::of((string) $transaction->base_amount)->toString(MoneyConverter::SCALE),
                    ],
                ];
            })->values()->all(),
        ];
    }
}
