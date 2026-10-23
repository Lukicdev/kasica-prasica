<?php

namespace Database\Seeders;

use App\Actions\Accounts\CreateAccount;
use App\Actions\Budgets\StartBudgetMonth;
use App\Actions\Budgets\UpdateCategoryBudget;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\AccountType;
use App\Enums\CategoryKind;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CalendarMonth;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use Database\Seeders\Data\HouseholdPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Four months of household transactions and an expense budget.
 *
 * Each month has both salaries, and every other month a third income.
 * Bills stay the same; groceries, dining, and similar spending move a little.
 * The plan is set on the oldest month and copied forward.
 *
 * A second run does nothing once these users exist. Rebuild with migrate:fresh --seed.
 */
class HouseholdHistorySeeder extends Seeder
{
    private const int MONTHS = 4;

    public function __construct(
        private CreateAccount $accounts,
        private CreateTransaction $transactions,
        private StartBudgetMonth $startBudget,
        private UpdateCategoryBudget $categoryBudgets,
    ) {}

    public function run(): void
    {
        if (User::query()->where('email', 'test@example.com')->exists()) {
            return;
        }

        $first = User::factory()->admin()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $second = User::factory()->create([
            'name' => 'Partner',
            'email' => 'partner@example.com',
            'workspace_id' => $first->workspace_id,
        ]);

        $oldest = $this->oldestMonth();
        $cash = $this->openAccount($first, 'Cash', 'banknote', AccountType::Cash, $oldest);
        $bank = $this->openAccount($first, 'Bank', 'landmark', AccountType::Bank, $oldest);
        $categories = $this->categories($first->workspace);
        $month = $oldest;

        for ($index = 0; $index < self::MONTHS; $index++) {
            $this->seedMonth($month, $index, $first, $second, $cash, $bank, $categories);
            $month = $month->next();
        }
    }

    private function openAccount(User $actor, string $name, string $icon, AccountType $type, CalendarMonth $oldest): Account
    {
        return $this->accounts->handle($actor, [
            'name' => $name,
            'icon' => $icon,
            'type' => $type,
            'currency' => 'RSD',
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
        foreach (HouseholdPlan::incomes($monthIndex) as $row) {
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

        foreach (HouseholdPlan::expenses() as $row) {
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

        foreach (HouseholdPlan::budgets() as $name => $amount) {
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
            'currency' => 'RSD',
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
}
