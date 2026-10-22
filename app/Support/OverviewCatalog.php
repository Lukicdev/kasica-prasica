<?php

namespace App\Support;

use App\Enums\CategoryKind;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\BudgetMonth;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * The month overview: savings, spending by category, and the household balance.
 *
 * Spent and income are sums of transactions in the base currency. The savings
 * rate is leftover as a percent of income. Pace projects spending through the
 * last day of the current month. The balance flow is the household total at
 * the end of each day.
 */
class OverviewCatalog
{
    public function __construct(
        private AccountCatalog $accounts,
        private AccountBalance $accountBalances,
        private MoneyConverter $converter,
    ) {}

    /**
     * @return array{
     *     month: array{value: string, label: string, previous: string, next: string, entry_date: string},
     *     expenses: array{rows: list<array<string, mixed>>},
     *     totals: array{income: array<string, mixed>, spent: array<string, mixed>},
     *     summary: array{savings_rate: int|null, pace: array<string, mixed>|null},
     *     flow: array{
     *         from: string|null,
     *         to: string|null,
     *         change: array<string, mixed>|null,
     *         previous: array{month: string, day: int, balance: array<string, mixed>, percent: int|null}|null,
     *         points: list<array{day: int, amount: string, formatted: string}>,
     *         previous_points: list<array{day: int, amount: string, formatted: string}>
     *     },
     *     balance: array{amount: string, currency: string, formatted: string, negative: bool}
     * }
     */
    public function page(User $user, CalendarMonth $month): array
    {
        $workspace = $user->workspace;
        $display = $user->display_currency;

        $transactions = Transaction::query()
            ->whereBelongsTo($workspace)
            ->where('booked_on', '>=', $month->start()->toDateString())
            ->where('booked_on', '<', $month->endExclusive()->toDateString())
            ->get(['category_id', 'type', 'base_amount']);

        $expenses = $transactions
            ->filter(fn (Transaction $transaction): bool => $transaction->type === TransactionType::Expense)
            ->values();
        $incomes = $transactions
            ->filter(fn (Transaction $transaction): bool => $transaction->type === TransactionType::Income)
            ->values();

        $spentByCategory = $this->totalsByCategory($expenses);
        $spentBase = $this->sumTransactions($expenses);
        $incomeBase = $this->sumTransactions($incomes);

        $categories = Category::query()
            ->whereBelongsTo($workspace)
            ->where('kind', CategoryKind::Expense)
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

        $expenseRows = $this->expenseRows(
            $this->visible($categories, $spentByCategory, $lines),
            $spentByCategory,
            $lines,
            $display,
            $workspace,
        );

        $uncategorisedSpent = $spentByCategory['none'] ?? null;
        if ($uncategorisedSpent !== null) {
            $expenseRows[] = $this->expenseRow(null, 'Uncategorised', null, null, null, false, $uncategorisedSpent, '0.00', $display, $workspace);
        }

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
            'expenses' => [
                'rows' => $expenseRows,
            ],
            'totals' => [
                'income' => $this->figure($incomeBase, $display, $workspace),
                'spent' => $this->figure($spentBase, $display, $workspace),
            ],
            'summary' => $this->summary($incomeBase, $spentBase, $month, $display, $workspace),
            'flow' => $this->flow($user, $month, $display),
            'balance' => $this->balance($user, $display),
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
     * @param  Collection<int, Category>  $categories
     * @param  array<int|string, string>  $spentByCategory
     * @param  Collection<int, CategoryBudget>  $lines
     * @return list<array<string, mixed>>
     */
    private function expenseRows(Collection $categories, array $spentByCategory, Collection $lines, string $display, Workspace $workspace): array
    {
        $rows = [];

        foreach ($categories as $category) {
            $rows[] = $this->expenseRow(
                $category->id,
                $category->name,
                $category->description,
                $category->icon,
                $category->color,
                $category->archived_at !== null,
                $spentByCategory[$category->id] ?? '0.00',
                $this->budgetAmount($lines, $category->id),
                $display,
                $workspace,
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
        ?string $icon,
        ?string $color,
        bool $archived,
        string $spent,
        string $budget,
        string $display,
        Workspace $workspace,
    ): array {
        $differenceBase = Decimal::of($spent)->subtract(Decimal::of($budget))->toString(MoneyConverter::SCALE);
        $standing = $this->standing($differenceBase);

        return [
            'id' => $id,
            'name' => $name,
            'icon' => $icon,
            'color' => $color,
            'description' => $description,
            'archived' => $archived,
            'spent' => $this->figure($spent, $display, $workspace),
            'budget' => $this->figure($budget, $display, $workspace),
            'difference' => $this->figure($differenceBase, $display, $workspace),
            'status' => $standing['status'],
            'status_label' => $standing['label'],
            'budget_percent' => $this->budgetPercent($spent, $budget),
        ];
    }

    /**
     * Spent as a percent of the budget. Omitted when the category has no budget.
     */
    private function budgetPercent(string $spent, string $budget): ?int
    {
        $budgetAmount = Decimal::of($budget);

        if ($budgetAmount->isZero()) {
            return null;
        }

        return (int) Decimal::of($spent)
            ->multiply(Decimal::of('100'))
            ->divide($budgetAmount, 0)
            ->toString(0);
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
     * Savings rate is omitted when the month has no income. Pace is only
     * meaningful while the month is still in progress.
     *
     * @return array{savings_rate: int|null, pace: array<string, mixed>|null}
     */
    private function summary(string $incomeBase, string $spentBase, CalendarMonth $month, string $display, Workspace $workspace): array
    {
        $current = $month->value() === CalendarMonth::current()->value();

        return [
            'savings_rate' => $this->savingsRate($incomeBase, $spentBase),
            'pace' => $current ? $this->figure($this->projectedSpend($spentBase, $month), $display, $workspace) : null,
        ];
    }

    private function savingsRate(string $incomeBase, string $spentBase): ?int
    {
        $income = Decimal::of($incomeBase);

        if ($income->isZero()) {
            return null;
        }

        $saved = $income->subtract(Decimal::of($spentBase));
        $percent = $saved->multiply(Decimal::of('100'))->divide($income, 0);

        return (int) $percent->toString(0);
    }

    /**
     * Spent so far, scaled from today's day of the month to the last day.
     */
    private function projectedSpend(string $spentBase, CalendarMonth $month): string
    {
        $days = $month->start()->daysInMonth;
        $elapsed = min(max(now()->day, 1), $days);

        return Decimal::of($spentBase)
            ->multiply(Decimal::of((string) $days))
            ->divide(Decimal::of((string) $elapsed), MoneyConverter::SCALE)
            ->toString(MoneyConverter::SCALE);
    }

    /**
     * Household balance at the end of each day, and the same days last month.
     *
     * The window runs from the first of the month through today when that
     * month is still open, or through the last day when it is already over.
     * Change is the movement inside the window. The comparison is the
     * balance on the same day number of the previous month.
     *
     * @return array{
     *     from: string|null,
     *     to: string|null,
     *     change: array<string, mixed>|null,
     *     previous: array{month: string, day: int, balance: array<string, mixed>, percent: int|null}|null,
     *     points: list<array{day: int, amount: string, formatted: string}>,
     *     previous_points: list<array{day: int, amount: string, formatted: string}>
     * }
     */
    private function flow(User $user, CalendarMonth $month, string $display): array
    {
        $accounts = $user->workspace->accounts()->get(['id', 'currency', 'opening_balance']);
        $through = $this->throughDay($month);

        if ($accounts->isEmpty() || $through === 0) {
            return $this->emptyFlow();
        }

        $previous = $month->previous();
        $native = [];

        foreach ($accounts as $account) {
            $native[$account->id] = Decimal::of((string) $account->opening_balance)->toString(MoneyConverter::SCALE);
        }

        $byDate = $this->movementsByDate($user, $accounts, $native, $previous->start(), $month->endExclusive());
        $previousSeries = $this->flowSeries($accounts, $native, $byDate, $previous->start(), $previous->start()->daysInMonth, $display, $user->workspace);
        $openingBase = $this->householdBase($accounts, $native);
        $series = $this->flowSeries($accounts, $native, $byDate, $month->start(), $through, $display, $user->workspace);
        $closingBase = $this->householdBase($accounts, $native);
        $day = min($through, $previous->start()->daysInMonth);
        $previousBase = $previousSeries['bases'][$day] ?? $openingBase;
        $from = $month->start();
        $to = $month->start()->addDays($through - 1);

        return [
            'from' => $from->format('j M Y'),
            'to' => $to->format('j M Y'),
            'change' => $this->figure(
                Decimal::of($closingBase)->subtract(Decimal::of($openingBase))->toString(MoneyConverter::SCALE),
                $display,
                $user->workspace,
            ),
            'previous' => [
                'month' => $previous->label(),
                'day' => $day,
                'balance' => $this->figure($previousBase, $display, $user->workspace),
                'percent' => $this->changePercent($closingBase, $previousBase),
            ],
            'points' => $series['points'],
            'previous_points' => $previousSeries['points'],
        ];
    }

    /**
     * Days elapsed in the month being viewed. A future month has none yet.
     */
    private function throughDay(CalendarMonth $month): int
    {
        $today = CarbonImmutable::now()->startOfDay();

        if ($today->lessThan($month->start())) {
            return 0;
        }

        if ($today->greaterThanOrEqualTo($month->endExclusive())) {
            return $month->start()->daysInMonth;
        }

        return $today->day;
    }

    /**
     * @param  EloquentCollection<int, Account>  $accounts
     * @param  array<int, string>  $native
     * @return array<string, list<Transaction>>
     */
    private function movementsByDate(User $user, EloquentCollection $accounts, array &$native, CarbonImmutable $from, CarbonImmutable $until): array
    {
        $ids = $accounts->modelKeys();
        $transactions = Transaction::query()
            ->whereBelongsTo($user->workspace)
            ->where(function ($query) use ($ids): void {
                $query->whereIn('account_id', $ids)
                    ->orWhereIn('destination_account_id', $ids);
            })
            ->where('booked_on', '<', $until->toDateString())
            ->orderBy('booked_on')
            ->orderBy('id')
            ->get([
                'account_id',
                'destination_account_id',
                'type',
                'account_amount',
                'destination_amount',
                'booked_on',
            ]);

        $byDate = [];
        $fromDate = $from->toDateString();

        foreach ($transactions as $transaction) {
            $date = $transaction->booked_on->toDateString();

            if ($date < $fromDate) {
                $this->applyNative($native, $transaction);

                continue;
            }

            $byDate[$date][] = $transaction;
        }

        return $byDate;
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @param  array<int, string>  $native
     * @param  array<string, list<Transaction>>  $byDate
     * @return array{points: list<array{day: int, amount: string, formatted: string}>, bases: array<int, string>}
     */
    private function flowSeries(
        Collection $accounts,
        array &$native,
        array $byDate,
        CarbonImmutable $start,
        int $days,
        string $display,
        Workspace $workspace,
    ): array {
        $points = [];
        $bases = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $cursor = $start->addDays($offset);

            foreach ($byDate[$cursor->toDateString()] ?? [] as $transaction) {
                $this->applyNative($native, $transaction);
            }

            $base = $this->householdBase($accounts, $native);
            $presented = $this->figure($base, $display, $workspace);
            $points[] = [
                'day' => $cursor->day,
                'amount' => $presented['amount'],
                'formatted' => $presented['formatted'],
            ];
            $bases[$cursor->day] = $base;
        }

        return ['points' => $points, 'bases' => $bases];
    }

    /**
     * @param  array<int, string>  $native
     */
    private function applyNative(array &$native, Transaction $transaction): void
    {
        foreach ([$transaction->account_id, $transaction->destination_account_id] as $accountId) {
            if ($accountId === null) {
                continue;
            }

            $accountId = (int) $accountId;

            if (! array_key_exists($accountId, $native)) {
                continue;
            }

            $native[$accountId] = $this->accountBalances->applyOnAccount($native[$accountId], $transaction, $accountId);
        }
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @param  array<int, string>  $native
     */
    private function householdBase(Collection $accounts, array $native): string
    {
        $total = Decimal::of('0');
        $workspace = $accounts->first()?->workspace;

        foreach ($accounts as $account) {
            $balance = $native[$account->id];
            $base = ! $workspace instanceof Workspace || $account->currency === $workspace->base_currency
                ? Decimal::of($balance)->toString(MoneyConverter::SCALE)
                : $this->converter->baseAmount($balance, $account->currency, $workspace);
            $total = $total->add(Decimal::of($base));
        }

        return $total->toString(MoneyConverter::SCALE);
    }

    private function changePercent(string $current, string $previous): ?int
    {
        $baseline = Decimal::of($previous);

        if ($baseline->isZero()) {
            return null;
        }

        if ($baseline->isNegative()) {
            $baseline = Decimal::of('0')->subtract($baseline);
        }

        return (int) Decimal::of($current)
            ->subtract(Decimal::of($previous))
            ->multiply(Decimal::of('100'))
            ->divide($baseline, 0)
            ->toString(0);
    }

    /**
     * @return array{
     *     from: null,
     *     to: null,
     *     change: null,
     *     previous: null,
     *     points: list<array{day: int, amount: string, formatted: string}>,
     *     previous_points: list<array{day: int, amount: string, formatted: string}>
     * }
     */
    private function emptyFlow(): array
    {
        return [
            'from' => null,
            'to' => null,
            'change' => null,
            'previous' => null,
            'points' => [],
            'previous_points' => [],
        ];
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
    private function figure(string $baseAmount, string $display, Workspace $workspace): array
    {
        $normalized = Decimal::of($baseAmount)->toString(MoneyConverter::SCALE);
        $presented = $this->converter->present($normalized, $workspace->base_currency, $normalized, $display, $workspace);

        return [
            ...$presented->toArray(),
            'negative' => Decimal::of($presented->amount)->isNegative(),
            'base_amount' => $normalized,
        ];
    }

    /**
     * @return array{amount: string, currency: string, formatted: string, negative: bool}
     */
    private function balance(User $user, string $display): array
    {
        $accounts = $user->workspace->accounts()->get();

        return $this->accounts->list($accounts, $display, $user->workspace)['total'];
    }
}
