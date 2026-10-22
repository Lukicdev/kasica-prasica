<?php

use App\Actions\Budgets\StartBudgetMonth;
use App\Actions\Budgets\UpdateCategoryBudget;
use App\Actions\Demo\SeedSpreadsheetMonth;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\AccountType;
use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Support\CalendarMonth;
use App\Support\Decimal;
use App\Support\SpreadsheetReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests cannot view the overview', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('unverified users cannot view the overview', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('the spreadsheet figures add up to the published totals', function () {
    $income = Decimal::of('0');

    foreach (SpreadsheetReference::income() as $row) {
        $income = $income->add(Decimal::of($row['amount']));
    }

    $spent = Decimal::of('0');
    $budget = Decimal::of('0');

    foreach (SpreadsheetReference::expenses() as $row) {
        $spent = $spent->add(Decimal::of($row['spent']));
        $budget = $budget->add(Decimal::of($row['budget']));
    }

    expect($income->toString(2))->toBe(SpreadsheetReference::INCOME)
        ->and($spent->toString(2))->toBe(SpreadsheetReference::SPENT)
        ->and($budget->toString(2))->toBe(SpreadsheetReference::BUDGET)
        ->and($spent->subtract($budget)->toString(2))->toBe(SpreadsheetReference::DIFFERENCE)
        ->and($income->subtract($spent)->toString(2))->toBe(SpreadsheetReference::LEFTOVER)
        ->and($income->subtract($budget)->toString(2))->toBe(SpreadsheetReference::UNALLOCATED);
});

test('a new workspace opens on the current month with first-run guidance', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => 'nope']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('overview/index')
            ->where('month.value', '2026-10')
            ->where('month.label', 'October 2026')
            ->where('month.previous', '2026-09')
            ->where('month.next', '2026-11')
            ->where('month.entry_date', '2026-10-20')
            ->where('first_run', true)
            ->where('recorded', false)
            ->where('budget_started', false)
            ->where('drilldown', null)
            ->where('steps', [
                ['key' => 'account', 'done' => false],
                ['key' => 'budget', 'done' => false],
                ['key' => 'transaction', 'done' => false],
            ])
            ->where('totals.income.base_amount', '0.00')
            ->where('totals.spent.base_amount', '0.00')
            ->where('totals.budget.base_amount', '0.00')
            ->where('totals.difference.base_amount', '0.00')
            ->where('totals.leftover.base_amount', '0.00')
            ->where('totals.unallocated.base_amount', '0.00')
            ->where('income.rows', fn ($rows) => count($rows) === 3 && collect($rows)->pluck('name')->all() === ['Salary', 'Bonus', 'Gifts and other income'])
            ->where('expenses.rows', fn ($rows) => count($rows) === 14)
            ->where('accounts.items', []));
});

test('first-run steps mark an account and a budget done before any transaction', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $account = Account::factory()->create(['name' => 'Wallet']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('first_run', true)
            ->where('steps.0.done', true)
            ->where('steps.1.done', false)
            ->where('steps.2.done', false)
            ->where('accounts.items.0.name', 'Wallet'));

    app(StartBudgetMonth::class)->handle($user, CalendarMonth::fromValue('2026-10'), false);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('first_run', true)
            ->where('budget_started', true)
            ->where('steps.1.done', true));

    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();

    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '12.50',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-20',
        'category' => $groceries,
        'note' => 'Market',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('first_run', false)
            ->where('recorded', true)
            ->where('totals.spent.base_amount', '12.50'));
});

test('the spreadsheet month matches the expected overview', function () {
    [$first, $second] = seedSpreadsheetMonth();
    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();

    expect($groceries->transactions()->count())->toBe(3);

    $this->actingAs($first)
        ->get(route('dashboard', ['month' => SpreadsheetReference::MONTH]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('overview/index')
            ->where('month.value', '2026-09')
            ->where('month.label', 'September 2026')
            ->where('month.previous', '2026-08')
            ->where('month.next', '2026-10')
            ->where('month.entry_date', '2026-09-01')
            ->where('first_run', false)
            ->where('recorded', true)
            ->where('budget_started', true)
            ->where('totals.income.base_amount', SpreadsheetReference::INCOME)
            ->where('totals.income.formatted', '503,354.00 RSD')
            ->where('totals.spent.base_amount', SpreadsheetReference::SPENT)
            ->where('totals.spent.formatted', '488,076.54 RSD')
            ->where('totals.budget.base_amount', SpreadsheetReference::BUDGET)
            ->where('totals.budget.formatted', '455,189.00 RSD')
            ->where('totals.difference.base_amount', SpreadsheetReference::DIFFERENCE)
            ->where('totals.difference.formatted', '32,887.54 RSD')
            ->where('totals.leftover.base_amount', SpreadsheetReference::LEFTOVER)
            ->where('totals.leftover.formatted', '15,277.46 RSD')
            ->where('totals.unallocated.base_amount', SpreadsheetReference::UNALLOCATED)
            ->where('totals.unallocated.formatted', '48,165.00 RSD')
            ->where('expenses.total.status', 'over')
            ->where('expenses.total.status_label', 'Over budget')
            ->where('income.total.base_amount', SpreadsheetReference::INCOME)
            ->where('income.rows', fn ($rows) => incomeRowsMatch($rows))
            ->where('expenses.rows', fn ($rows) => expenseRowsMatch($rows))
            ->where('accounts.items', fn ($items) => collect($items)->pluck('name')->all() === ['Cash', 'Bank'])
            ->where('accounts.total.amount', SpreadsheetReference::LEFTOVER)
            ->where('accounts.total.currency', 'RSD')
            ->where('accounts.total.formatted', '15,277.46 RSD'));

    $this->actingAs($second)
        ->get(route('dashboard', ['month' => SpreadsheetReference::MONTH]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.income.base_amount', SpreadsheetReference::INCOME)
            ->where('totals.spent.base_amount', SpreadsheetReference::SPENT)
            ->where('totals.leftover.base_amount', SpreadsheetReference::LEFTOVER));

    $this->actingAs($first)
        ->get(route('dashboard', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('recorded', false)
            ->where('first_run', false)
            ->where('budget_started', false)
            ->where('totals.spent.base_amount', '0.00')
            ->where('totals.income.base_amount', '0.00')
            ->where('accounts.total.amount', SpreadsheetReference::LEFTOVER));
});

test('the euro view converts overview totals from the dinar total', function () {
    [$first] = seedSpreadsheetMonth();
    $first->update(['display_currency' => Currency::Eur]);

    $this->actingAs($first)
        ->get(route('dashboard', ['month' => SpreadsheetReference::MONTH]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.income.amount', '4283.86')
            ->where('totals.income.formatted', '4,283.86 EUR')
            ->where('totals.income.base_amount', SpreadsheetReference::INCOME)
            ->where('totals.income.original_formatted', '503,354.00 RSD')
            ->where('totals.spent.amount', '4153.84')
            ->where('totals.spent.formatted', '4,153.84 EUR')
            ->where('totals.spent.base_amount', SpreadsheetReference::SPENT)
            ->where('totals.budget.amount', '3873.95')
            ->where('totals.budget.formatted', '3,873.95 EUR')
            ->where('totals.difference.amount', '279.89')
            ->where('totals.leftover.amount', '130.02')
            ->where('totals.unallocated.amount', '409.91')
            ->where('accounts.total.amount', '130.02')
            ->where('accounts.total.formatted', '130.02 EUR')
            ->where('expenses.rows', function ($rows) {
                $sum = Decimal::of('0');
                $mortgage = null;

                foreach ($rows as $row) {
                    $sum = $sum->add(Decimal::of($row['spent']['amount']));

                    if ($row['name'] === 'Mortgage') {
                        $mortgage = $row;
                    }
                }

                return $sum->toString(2) === '4153.85'
                    && $mortgage !== null
                    && $mortgage['spent']['amount'] === '643.82'
                    && $mortgage['spent']['original_formatted'] === '75,649.00 RSD'
                    && $mortgage['status_label'] === 'Under budget';
            }));
});

test('a category shows the transactions behind its spent figure', function () {
    [$first] = seedSpreadsheetMonth();
    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();

    $this->actingAs($first)
        ->get(route('dashboard', [
            'month' => SpreadsheetReference::MONTH,
            'category' => $groceries->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('drilldown.id', $groceries->id)
            ->where('drilldown.name', 'Groceries')
            ->where('drilldown.kind', 'expense')
            ->where('drilldown.total.base_amount', '42608.00')
            ->where('drilldown.budget.base_amount', '39649.00')
            ->where('drilldown.difference.base_amount', '2959.00')
            ->where('drilldown.status', 'over')
            ->where('drilldown.status_label', 'Over budget')
            ->where('drilldown.transactions', function ($transactions) {
                $sum = Decimal::of('0');

                foreach ($transactions as $transaction) {
                    $sum = $sum->add(Decimal::of($transaction['amount']['base_amount']));

                    if (! str_contains((string) $transaction['amount']['formatted'], 'RSD')) {
                        return false;
                    }
                }

                return count($transactions) === 3
                    && $sum->toString(2) === '42608.00';
            }));

    $this->actingAs($first)
        ->get(route('dashboard', [
            'month' => SpreadsheetReference::MONTH,
            'category' => 999_999,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('drilldown', null));
});

test('a euro transaction keeps its original amount on the drilldown', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'name' => 'Euro cash',
        'icon' => 'wallet',
    ]);
    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();

    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '10.00',
        'currency' => Currency::Eur,
        'booked_on' => '2026-10-04',
        'category' => $groceries,
        'note' => 'Train ticket',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-10', 'category' => $groceries->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('drilldown.total.base_amount', '1175.00')
            ->where('drilldown.transactions.0.note', 'Train ticket')
            ->where('drilldown.transactions.0.account', 'Euro cash')
            ->where('drilldown.transactions.0.account_icon', 'wallet')
            ->where('drilldown.transactions.0.amount.formatted', '1,175.00 RSD')
            ->where('drilldown.transactions.0.amount.original_formatted', '10.00 EUR')
            ->where('drilldown.transactions.0.amount.original_currency', 'EUR'));

    $user->update(['display_currency' => Currency::Eur]);

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-10', 'category' => $groceries->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('drilldown.transactions.0.amount.formatted', '10.00 EUR')
            ->where('drilldown.transactions.0.amount.original_formatted', null)
            ->where('drilldown.total.amount', '10.00')
            ->where('drilldown.total.formatted', '10.00 EUR'));
});

test('other months, refunds and archived categories stay in the right rows', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();
    $month = CalendarMonth::fromValue('2026-10');

    app(StartBudgetMonth::class)->handle($user, $month, false);
    app(UpdateCategoryBudget::class)->handle($user, $month, $groceries, '80');

    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '80.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-02',
        'category' => $groceries,
    ]);
    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '-30.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-18',
        'category' => $groceries,
        'note' => 'Returned',
    ]);
    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '999.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-09-02',
        'category' => $groceries,
    ]);

    Category::factory()->archived()->create(['name' => 'Old hobby']);
    $clinic = Category::factory()->create(['name' => 'Old clinic']);

    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '15.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-09',
        'category' => $clinic,
    ]);

    $clinic->update(['archived_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.spent.base_amount', '65.00')
            ->where('expenses.rows', function ($rows) {
                $names = collect($rows)->pluck('name');
                $groceries = collect($rows)->firstWhere('name', 'Groceries');
                $clinic = collect($rows)->firstWhere('name', 'Old clinic');

                return $names->doesntContain('Old hobby')
                    && $groceries['spent']['base_amount'] === '50.00'
                    && $groceries['budget']['base_amount'] === '80.00'
                    && $groceries['difference']['base_amount'] === '-30.00'
                    && $groceries['status'] === 'under'
                    && $groceries['status_label'] === 'Under budget'
                    && $clinic['archived'] === true
                    && $clinic['spent']['base_amount'] === '15.00';
            }));

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('budget_started', false)
            ->where('totals.spent.base_amount', '999.00')
            ->where('totals.budget.base_amount', '0.00')
            ->where('expenses.rows', function ($rows) {
                $groceries = collect($rows)->firstWhere('name', 'Groceries');

                return $groceries['spent']['base_amount'] === '999.00'
                    && $groceries['budget']['base_amount'] === '0.00'
                    && $groceries['difference']['base_amount'] === '999.00'
                    && $groceries['status'] === 'over'
                    && $groceries['status_label'] === 'Over budget';
            }));
});

test('the accounts panel converts the total', function () {
    $user = User::factory()->create();
    Account::factory()->create([
        'name' => 'Wallet',
        'type' => AccountType::Cash,
        'opening_balance' => '100.00',
        'currency' => Currency::Rsd,
    ]);
    $euro = Account::factory()->inCurrency(Currency::Eur)->create([
        'name' => 'Euro cash',
        'type' => AccountType::Bank,
        'opening_balance' => '10.00',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('accounts.items', function ($items) use ($euro) {
                $names = collect($items)->pluck('name');
                $euroCash = collect($items)->firstWhere('name', 'Euro cash');

                return $names->all() === ['Wallet', 'Euro cash']
                    && $euroCash['id'] === $euro->id
                    && $euroCash['balance']['formatted'] === '10.00 EUR'
                    && $euroCash['balance']['currency'] === 'EUR';
            })
            ->where('accounts.total.amount', '1275.00')
            ->where('accounts.total.formatted', '1,275.00 RSD'));
});

/**
 * @return array{0: User, 1: User}
 */
function seedSpreadsheetMonth(): array
{
    $first = User::factory()->admin()->create(['name' => 'Partner 1']);
    $second = User::factory()->create([
        'name' => 'Partner 2',
        'workspace_id' => $first->workspace_id,
    ]);

    app(SeedSpreadsheetMonth::class)->handle(
        $first,
        $second,
        CalendarMonth::fromValue(SpreadsheetReference::MONTH),
    );

    return [$first, $second];
}

function incomeRowsMatch(mixed $rows): bool
{
    $byName = collect($rows)->keyBy('name');
    $salary = $byName->get('Salary');
    $bonus = $byName->get('Bonus');
    $gifts = $byName->get('Gifts and other income');

    if (! is_array($salary) || ! is_array($bonus) || ! is_array($gifts) || $byName->count() !== 3) {
        return false;
    }

    $people = collect($salary['people'])->keyBy('name');

    return $salary['amount']['base_amount'] === '493354.00'
        && $people->get('Partner 1')['amount']['base_amount'] === '261434.00'
        && $people->get('Partner 2')['amount']['base_amount'] === '231920.00'
        && $bonus['amount']['base_amount'] === '0.00'
        && $bonus['people'] === []
        && $gifts['amount']['base_amount'] === '10000.00';
}

function expenseRowsMatch(mixed $rows): bool
{
    $byName = collect($rows)->keyBy('name');

    if ($byName->count() !== count(SpreadsheetReference::expenses())) {
        return false;
    }

    foreach (SpreadsheetReference::expenses() as $expected) {
        $row = $byName->get($expected['name']);

        if (! is_array($row)) {
            return false;
        }

        $difference = Decimal::of($expected['spent'])->subtract(Decimal::of($expected['budget']))->toString(2);
        $status = match (true) {
            Decimal::of($difference)->isZero() => 'on',
            Decimal::of($difference)->isNegative() => 'under',
            default => 'over',
        };
        $label = match ($status) {
            'on' => 'On budget',
            'under' => 'Under budget',
            default => 'Over budget',
        };

        if ($row['spent']['base_amount'] !== $expected['spent']
            || $row['budget']['base_amount'] !== $expected['budget']
            || $row['difference']['base_amount'] !== $difference
            || $row['status'] !== $status
            || $row['status_label'] !== $label) {
            return false;
        }
    }

    $mobile = $byName->get('A1 (mobile provider)');

    return is_array($mobile) && $mobile['description'] === 'Mobile provider';
}
