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
            ->missing('first_run')
            ->missing('income')
            ->missing('drilldown')
            ->missing('pending')
            ->missing('accounts')
            ->where('totals.income.base_amount', '0.00')
            ->where('totals.spent.base_amount', '0.00')
            ->where('summary.savings_rate', null)
            ->where('summary.pace.base_amount', '0.00')
            ->where('summary.pace.formatted', '0.00 RSD')
            ->where('flow.points', [])
            ->where('flow.change', null)
            ->where('flow.previous', null)
            ->where('expenses.rows', fn ($rows) => count($rows) === 14)
            ->where('balance.amount', '0.00')
            ->where('balance.formatted', '0.00 RSD'));
});

test('a recorded expense shows up in the month total', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $account = Account::factory()->create(['name' => 'Wallet']);
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
            ->where('totals.income.base_amount', SpreadsheetReference::INCOME)
            ->where('totals.income.formatted', '503,354.00 RSD')
            ->where('totals.spent.base_amount', SpreadsheetReference::SPENT)
            ->where('totals.spent.formatted', '488,076.54 RSD')
            ->where('summary.savings_rate', 3)
            ->where('expenses.rows', fn ($rows) => expenseRowsMatch($rows))
            ->where('balance.amount', SpreadsheetReference::LEFTOVER)
            ->where('balance.currency', 'RSD')
            ->where('balance.formatted', '15,277.46 RSD'));

    $this->actingAs($second)
        ->get(route('dashboard', ['month' => SpreadsheetReference::MONTH]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.income.base_amount', SpreadsheetReference::INCOME)
            ->where('totals.spent.base_amount', SpreadsheetReference::SPENT)
            ->where('balance.amount', SpreadsheetReference::LEFTOVER));

    $this->actingAs($first)
        ->get(route('dashboard', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.spent.base_amount', '0.00')
            ->where('totals.income.base_amount', '0.00')
            ->where('balance.amount', SpreadsheetReference::LEFTOVER));
});

test('the overview summary reports the savings rate and the month-end pace', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $salary = Category::query()->where('name', 'Salary')->firstOrFail();
    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();

    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Income,
        'amount' => '100.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-10',
        'category' => $salary,
    ]);
    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '26.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-12',
        'category' => $groceries,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.savings_rate', 74)
            ->where('summary.pace.base_amount', '40.30')
            ->where('summary.pace.formatted', '40.30 RSD'));

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.savings_rate', null)
            ->where('summary.pace', null));

    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '124.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-13',
        'category' => $groceries,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.savings_rate', -50)
            ->where('summary.pace.base_amount', '232.50'));
});

test('the balance flow follows the household total through today and the same day last month', function () {
    $this->travelTo('2026-10-22');
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'opening_balance' => '1000.00',
        'opening_balance_on' => '2026-01-01',
    ]);
    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();
    $salary = Category::query()->where('name', 'Salary')->firstOrFail();

    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '200.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-09-22',
        'category' => $groceries,
    ]);
    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '100.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-10',
        'category' => $groceries,
    ]);
    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Income,
        'amount' => '50.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-20',
        'category' => $salary,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('flow.from', '1 Oct 2026')
            ->where('flow.to', '22 Oct 2026')
            ->where('flow.change.base_amount', '-50.00')
            ->where('flow.change.formatted', '-50.00 RSD')
            ->where('flow.points', fn ($points) => count($points) === 22
                && $points[0]['day'] === 1
                && $points[0]['amount'] === '800.00'
                && $points[9]['amount'] === '700.00'
                && $points[21]['day'] === 22
                && $points[21]['amount'] === '750.00'
                && $points[21]['formatted'] === '750.00 RSD')
            ->where('flow.previous.month', 'September 2026')
            ->where('flow.previous.day', 22)
            ->where('flow.previous.balance.base_amount', '800.00')
            ->where('flow.previous.percent', -6)
            ->where('flow.previous_points', fn ($points) => count($points) === 30
                && $points[21]['amount'] === '800.00')
            ->where('balance.amount', '750.00'));

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('flow.from', '1 Sep 2026')
            ->where('flow.to', '30 Sep 2026')
            ->where('flow.change.base_amount', '-200.00')
            ->where('flow.points', fn ($points) => count($points) === 30 && $points[29]['amount'] === '800.00')
            ->where('flow.previous.month', 'August 2026')
            ->where('flow.previous.day', 30)
            ->where('flow.previous.balance.base_amount', '1000.00')
            ->where('flow.previous.percent', -20));

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('flow.points', [])
            ->where('flow.change', null));
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
            ->where('summary.savings_rate', 3)
            ->where('balance.amount', '130.02')
            ->where('balance.formatted', '130.02 EUR')
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
            ->where('totals.spent.base_amount', '999.00')
            ->where('expenses.rows', function ($rows) {
                $groceries = collect($rows)->firstWhere('name', 'Groceries');

                return $groceries['spent']['base_amount'] === '999.00'
                    && $groceries['budget']['base_amount'] === '0.00'
                    && $groceries['difference']['base_amount'] === '999.00'
                    && $groceries['status'] === 'over'
                    && $groceries['status_label'] === 'Over budget';
            }));
});

test('the overview balance converts every account into the display currency', function () {
    $user = User::factory()->create();
    Account::factory()->create([
        'name' => 'Wallet',
        'type' => AccountType::Cash,
        'opening_balance' => '100.00',
        'currency' => Currency::Rsd,
    ]);
    Account::factory()->inCurrency(Currency::Eur)->create([
        'name' => 'Euro cash',
        'type' => AccountType::Bank,
        'opening_balance' => '10.00',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('balance.amount', '1275.00')
            ->where('balance.currency', 'RSD')
            ->where('balance.formatted', '1,275.00 RSD'));
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

        $budget = Decimal::of($expected['budget']);
        $percent = $budget->isZero()
            ? null
            : (int) Decimal::of($expected['spent'])->multiply(Decimal::of('100'))->divide($budget, 0)->toString(0);

        if ($row['spent']['base_amount'] !== $expected['spent']
            || $row['budget']['base_amount'] !== $expected['budget']
            || $row['difference']['base_amount'] !== $difference
            || $row['status'] !== $status
            || $row['status_label'] !== $label
            || $row['budget_percent'] !== $percent) {
            return false;
        }
    }

    $mobile = $byName->get('A1 (mobile provider)');

    return is_array($mobile) && $mobile['description'] === 'Mobile provider';
}
