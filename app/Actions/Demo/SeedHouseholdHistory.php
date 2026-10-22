<?php

namespace App\Actions\Demo;

use App\Actions\Accounts\CreateAccount;
use App\Actions\Budgets\StartBudgetMonth;
use App\Actions\Budgets\UpdateCategoryBudget;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\AccountType;
use App\Enums\CategoryKind;
use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CalendarMonth;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

/**
 * Four months of household transactions and an expense budget.
 *
 * Each month has both salaries, and every other month a third income.
 * Bills stay the same; groceries, dining, and similar spending move a little.
 * The plan is set on the oldest month and copied forward.
 */
class SeedHouseholdHistory
{
    private const int MONTHS = 4;

    public function __construct(
        private CreateAccount $accounts,
        private CreateTransaction $transactions,
        private StartBudgetMonth $startBudget,
        private UpdateCategoryBudget $categoryBudgets,
    ) {}

    public function handle(User $first, User $second): void
    {
        if ($first->workspace_id !== $second->workspace_id) {
            throw new InvalidArgumentException('Both people must share the workspace.');
        }

        $workspace = $first->workspace;
        $oldest = $this->oldestMonth();
        $cash = $this->cashAccount($first, $oldest);
        $bank = $this->bankAccount($first, $oldest);
        $categories = $this->categories($workspace);
        $month = $oldest;

        for ($index = 0; $index < self::MONTHS; $index++) {
            $this->seedMonth($month, $index, $first, $second, $cash, $bank, $categories);
            $month = $month->next();
        }
    }

    private function cashAccount(User $actor, CalendarMonth $oldest): Account
    {
        return $this->accounts->handle($actor, [
            'name' => 'Cash',
            'icon' => 'banknote',
            'type' => AccountType::Cash,
            'currency' => Currency::Rsd,
            'opening_balance' => '0.00',
            'opening_balance_on' => $oldest->date(),
            'note' => null,
        ]);
    }

    private function bankAccount(User $actor, CalendarMonth $oldest): Account
    {
        return $this->accounts->handle($actor, [
            'name' => 'Bank',
            'icon' => 'landmark',
            'type' => AccountType::Bank,
            'currency' => Currency::Rsd,
            'opening_balance' => '0.00',
            'opening_balance_on' => $oldest->date(),
            'note' => null,
        ]);
    }

    /**
     * @return Collection<string, Category>
     */
    private function categories(Workspace $workspace): Collection
    {
        return Category::query()
            ->whereBelongsTo($workspace)
            ->get()
            ->keyBy(fn (Category $category): string => $category->kind->value.'|'.$category->name);
    }

    /**
     * @param  Collection<string, Category>  $categories
     */
    private function seedMonth(
        CalendarMonth $month,
        int $monthIndex,
        User $first,
        User $second,
        Account $cash,
        Account $bank,
        Collection $categories,
    ): void {
        foreach ($this->incomes($monthIndex) as $row) {
            $this->record(
                $this->category($categories, $row['name'], CategoryKind::Income),
                $row['partner'] === 'one' ? $first : $second,
                $bank,
                $row['amount'],
                $month,
                $row['day'],
                TransactionType::Income,
                $row['name'],
            );
        }

        foreach ($this->expenses() as $row) {
            $this->record(
                $this->category($categories, $row['name'], CategoryKind::Expense),
                $row['partner'] === 'one' ? $first : $second,
                $row['account'] === 'cash' ? $cash : $bank,
                $this->amount($row['amount'], $monthIndex, $row['varies']),
                $month,
                $row['day'],
                TransactionType::Expense,
                $row['note'],
            );
        }

        $this->seedBudget($month, $monthIndex, $first, $categories);
    }

    /**
     * @param  Collection<string, Category>  $categories
     */
    private function seedBudget(CalendarMonth $month, int $monthIndex, User $actor, Collection $categories): void
    {
        if ($monthIndex > 0) {
            $this->startBudget->handle($actor, $month, true);

            return;
        }

        $this->startBudget->handle($actor, $month, false);

        foreach ($this->budgets() as $name => $amount) {
            $this->categoryBudgets->handle(
                $actor,
                $month,
                $this->category($categories, $name, CategoryKind::Expense),
                $amount,
            );
        }
    }

    private function oldestMonth(): CalendarMonth
    {
        $month = CalendarMonth::current();

        for ($index = 1; $index < self::MONTHS; $index++) {
            $month = $month->previous();
        }

        return $month;
    }

    /**
     * @param  Collection<string, Category>  $categories
     */
    private function category(Collection $categories, string $name, CategoryKind $kind): Category
    {
        $category = $categories->get($kind->value.'|'.$name);

        if (! $category instanceof Category) {
            throw new RuntimeException("Missing {$kind->value} category [{$name}].");
        }

        return $category;
    }

    private function record(
        Category $category,
        User $person,
        Account $account,
        string $amount,
        CalendarMonth $month,
        int $day,
        TransactionType $type,
        string $note,
    ): void {
        $this->transactions->handle($account, $person, [
            'type' => $type,
            'amount' => $amount,
            'currency' => Currency::Rsd,
            'booked_on' => $this->bookedOn($month, $day),
            'note' => $note,
            'person' => $person,
            'category' => $category,
        ]);
    }

    /**
     * Keep the current month from running past today.
     */
    private function bookedOn(CalendarMonth $month, int $day): string
    {
        $date = $month->start()->day(min($day, $month->start()->daysInMonth));
        $today = CarbonImmutable::now()->startOfDay();

        if ($date->greaterThan($today)) {
            return $today->toDateString();
        }

        return $date->toDateString();
    }

    private function amount(string $base, int $monthIndex, bool $varies): string
    {
        if (! $varies) {
            return $base;
        }

        $factor = match ($monthIndex) {
            0 => '1.00',
            1 => '0.94',
            2 => '1.06',
            default => '0.98',
        };

        return Decimal::of($base)->multiply(Decimal::of($factor))->toString(2);
    }

    /**
     * @return list<array{name: string, partner: 'one'|'two', amount: string, day: int}>
     */
    private function incomes(int $monthIndex): array
    {
        $rows = [
            ['name' => 'Salary', 'partner' => 'one', 'amount' => '261434.00', 'day' => 1],
            ['name' => 'Salary', 'partner' => 'two', 'amount' => '231920.00', 'day' => 5],
        ];

        if ($monthIndex % 2 === 1) {
            $rows[] = $monthIndex === 1
                ? ['name' => 'Bonus', 'partner' => 'one', 'amount' => '40000.00', 'day' => 15]
                : ['name' => 'Gifts and other income', 'partner' => 'two', 'amount' => '10000.00', 'day' => 18];
        }

        return $rows;
    }

    /**
     * The expense plan, close to a typical month so some categories land over it.
     *
     * @return array<string, string>
     */
    private function budgets(): array
    {
        return [
            'Mortgage' => '75700.00',
            'Utilities & Property Tax' => '11000.00',
            'Internet' => '4300.00',
            'Groceries' => '20000.00',
            'Dining Out' => '7500.00',
            'Debt' => '14800.00',
            'Beauty' => '3000.00',
            'Transportation' => '7000.00',
            'A1 (mobile provider)' => '2400.00',
            'Subscriptions' => '2500.00',
            'Personal care (Nails/Haircuts)' => '2500.00',
            'Shopping' => '10000.00',
            'Extra expenses (Gifts, miscellaneous, etc.)' => '6000.00',
            'Medical' => '2000.00',
        ];
    }

    /**
     * Everyday spending for one month. Repeated across the four seeded months.
     *
     * @return list<array{name: string, amount: string, day: int, account: 'cash'|'bank', partner: 'one'|'two', note: string, varies: bool}>
     */
    private function expenses(): array
    {
        return [
            ['name' => 'Mortgage', 'amount' => '75649.00', 'day' => 4, 'account' => 'bank', 'partner' => 'one', 'note' => 'Mortgage payment', 'varies' => false],
            ['name' => 'Utilities & Property Tax', 'amount' => '9200.00', 'day' => 7, 'account' => 'bank', 'partner' => 'one', 'note' => 'Electricity', 'varies' => true],
            ['name' => 'Utilities & Property Tax', 'amount' => '2800.00', 'day' => 11, 'account' => 'bank', 'partner' => 'two', 'note' => 'Water and heating', 'varies' => true],
            ['name' => 'Internet', 'amount' => '4300.00', 'day' => 6, 'account' => 'bank', 'partner' => 'one', 'note' => 'Internet', 'varies' => false],
            ['name' => 'Groceries', 'amount' => '4200.00', 'day' => 2, 'account' => 'cash', 'partner' => 'two', 'note' => 'Maxi', 'varies' => true],
            ['name' => 'Groceries', 'amount' => '6150.00', 'day' => 8, 'account' => 'bank', 'partner' => 'one', 'note' => 'Idea', 'varies' => true],
            ['name' => 'Groceries', 'amount' => '3890.00', 'day' => 14, 'account' => 'cash', 'partner' => 'two', 'note' => 'Lidl', 'varies' => true],
            ['name' => 'Groceries', 'amount' => '5400.00', 'day' => 19, 'account' => 'bank', 'partner' => 'one', 'note' => 'Maxi', 'varies' => true],
            ['name' => 'Groceries', 'amount' => '2100.00', 'day' => 22, 'account' => 'cash', 'partner' => 'two', 'note' => 'Market', 'varies' => true],
            ['name' => 'Dining Out', 'amount' => '4500.00', 'day' => 9, 'account' => 'bank', 'partner' => 'one', 'note' => 'Dinner', 'varies' => true],
            ['name' => 'Dining Out', 'amount' => '890.00', 'day' => 16, 'account' => 'cash', 'partner' => 'two', 'note' => 'Coffee', 'varies' => true],
            ['name' => 'Dining Out', 'amount' => '1650.00', 'day' => 21, 'account' => 'cash', 'partner' => 'one', 'note' => 'Takeaway', 'varies' => true],
            ['name' => 'Debt', 'amount' => '14797.00', 'day' => 10, 'account' => 'bank', 'partner' => 'one', 'note' => 'Loan payment', 'varies' => false],
            ['name' => 'Beauty', 'amount' => '3500.00', 'day' => 13, 'account' => 'cash', 'partner' => 'two', 'note' => 'Salon', 'varies' => true],
            ['name' => 'Transportation', 'amount' => '6500.00', 'day' => 5, 'account' => 'bank', 'partner' => 'one', 'note' => 'Fuel', 'varies' => true],
            ['name' => 'Transportation', 'amount' => '1800.00', 'day' => 18, 'account' => 'cash', 'partner' => 'two', 'note' => 'Bus pass', 'varies' => true],
            ['name' => 'A1 (mobile provider)', 'amount' => '2400.00', 'day' => 3, 'account' => 'bank', 'partner' => 'one', 'note' => 'Mobile bill', 'varies' => false],
            ['name' => 'Subscriptions', 'amount' => '1890.00', 'day' => 1, 'account' => 'bank', 'partner' => 'two', 'note' => 'Streaming', 'varies' => false],
            ['name' => 'Personal care (Nails/Haircuts)', 'amount' => '2500.00', 'day' => 15, 'account' => 'cash', 'partner' => 'two', 'note' => 'Haircut', 'varies' => true],
            ['name' => 'Shopping', 'amount' => '7800.00', 'day' => 12, 'account' => 'bank', 'partner' => 'one', 'note' => 'Clothes', 'varies' => true],
            ['name' => 'Shopping', 'amount' => '3200.00', 'day' => 20, 'account' => 'cash', 'partner' => 'two', 'note' => 'Household', 'varies' => true],
            ['name' => 'Extra expenses (Gifts, miscellaneous, etc.)', 'amount' => '4500.00', 'day' => 17, 'account' => 'cash', 'partner' => 'one', 'note' => 'Gift', 'varies' => true],
            ['name' => 'Medical', 'amount' => '2200.00', 'day' => 20, 'account' => 'bank', 'partner' => 'two', 'note' => 'Pharmacy', 'varies' => true],
        ];
    }
}
