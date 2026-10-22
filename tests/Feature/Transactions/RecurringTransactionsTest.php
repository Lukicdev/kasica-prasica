<?php

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Decimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests cannot confirm a recurring transaction', function () {
    $template = Transaction::factory()->repeating()->create();

    $this->get(route('recurrences.edit', ['transaction' => $template, 'month' => '2026-11']))
        ->assertRedirect(route('login'));

    $this->post(route('recurrences.store', $template), ['month' => '2026-11'])
        ->assertRedirect(route('login'));
});

test('a recurring expense stays pending until it is confirmed', function () {
    $user = User::factory()->create(['name' => 'Ada']);
    $account = Account::factory()->create([
        'name' => 'Household bank',
        'opening_balance' => '1000.00',
    ]);
    $mortgage = Category::factory()->create(['name' => 'House loan']);

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($account, $mortgage, $user, [
            'amount' => '200',
            'booked_on' => '2026-10-05',
            'note' => 'October mortgage',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $template = Transaction::query()->where('note', 'October mortgage')->first();

    expect($template)->not->toBeNull()
        ->and($template->repeats)->toBeTrue();

    $this->actingAs($user)
        ->get(route('transactions.show', $template))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transaction.repeats', true)
            ->where('transaction.balance.amount', '800.00'));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('pending', []));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions', [])
            ->where('total.formatted', '0.00 RSD')
            ->where('pending.0.id', $template->id)
            ->where('pending.0.status_label', 'Pending')
            ->where('pending.0.category', 'House loan')
            ->where('pending.0.account', 'Household bank')
            ->where('pending.0.date', '2026-11-05')
            ->where('pending.0.amount.formatted', '200.00 RSD'));

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.spent.amount', '0.00')
            ->where('balance.amount', '800.00'));

    $this->actingAs($user)
        ->post(route('recurrences.store', $template), ['month' => '2026-11'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('transactions.index', ['month' => '2026-11']));

    $confirmed = Transaction::query()->where('recurrence_id', $template->id)->first();

    expect($confirmed)->not->toBeNull()
        ->and($confirmed->repeats)->toBeFalse()
        ->and($confirmed->type)->toBe(TransactionType::Expense)
        ->and(Decimal::of((string) $confirmed->amount)->toString(2))->toBe('200.00')
        ->and($confirmed->currency)->toBe('RSD')
        ->and($confirmed->account_id)->toBe($account->id)
        ->and($confirmed->category_id)->toBe($mortgage->id)
        ->and($confirmed->person_id)->toBe($user->id)
        ->and($confirmed->booked_on->toDateString())->toBe('2026-11-05')
        ->and($confirmed->note)->toBe('October mortgage')
        ->and($confirmed->created_by)->toBe($user->id)
        ->and($template->refresh()->repeats)->toBeTrue();

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('pending', [])
            ->where('transactions.0.id', $confirmed->id)
            ->where('total.formatted', '-200.00 RSD'));

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.spent.amount', '200.00')
            ->where('balance.amount', '600.00'));

    $this->actingAs($user)
        ->post(route('recurrences.store', $template), ['month' => '2026-11'])
        ->assertRedirect(route('transactions.show', $confirmed));

    expect(Transaction::query()->where('recurrence_id', $template->id)->count())->toBe(1);
});

test('confirming a recurring expense can change the amount, account and currency', function () {
    $user = User::factory()->create();
    $cash = Account::factory()->create([
        'name' => 'Cash',
        'opening_balance' => '5000.00',
    ]);
    $euro = Account::factory()->inCurrency('EUR')->create([
        'name' => 'Euro card',
        'opening_balance' => '50.00',
    ]);
    $category = Category::factory()->create(['name' => 'Fibre']);

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($cash, $category, $user, [
            'amount' => '200',
            'booked_on' => '2026-10-05',
            'note' => 'Internet',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $template = Transaction::query()->where('note', 'Internet')->firstOrFail();

    $this->actingAs($user)
        ->get(route('recurrences.edit', ['transaction' => $template, 'month' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('recurrences/edit')
            ->where('recurrence.amount', '200.00')
            ->where('recurrence.currency', 'RSD')
            ->where('recurrence.account_id', $cash->id)
            ->where('recurrence.booked_on', '2026-11-05'));

    $this->actingAs($user)
        ->post(route('recurrences.store', $template), [
            'month' => '2026-11',
            'amount' => '10',
            'currency' => 'EUR',
            'account_id' => $euro->id,
            'booked_on' => '2026-11-08',
        ])
        ->assertSessionHasNoErrors();

    $confirmed = Transaction::query()->where('recurrence_id', $template->id)->first();

    expect($confirmed)->not->toBeNull()
        ->and(Decimal::of((string) $confirmed->amount)->toString(2))->toBe('10.00')
        ->and($confirmed->currency)->toBe('EUR')
        ->and($confirmed->account_id)->toBe($euro->id)
        ->and(Decimal::of((string) $confirmed->account_amount)->toString(2))->toBe('10.00')
        ->and(Decimal::of((string) $confirmed->base_amount)->toString(2))->toBe('1175.00')
        ->and($confirmed->booked_on->toDateString())->toBe('2026-11-08');

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.spent.amount', '1175.00')
            ->where('balance.amount', '9500.00'));
});

test('a recurring salary stays pending until it is confirmed', function () {
    $user = User::factory()->create(['name' => 'Ada']);
    $account = Account::factory()->create([
        'name' => 'Salary account',
        'opening_balance' => '1000.00',
    ]);
    $salary = Category::factory()->income()->create(['name' => 'Pay']);

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($account, $salary, $user, [
            'amount' => '5000',
            'booked_on' => '2026-10-01',
            'note' => 'October salary',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $template = Transaction::query()->where('note', 'October salary')->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.income.amount', '0.00')
            ->where('balance.amount', '6000.00'));

    $this->actingAs($user)
        ->post(route('recurrences.store', $template), ['month' => '2026-11'])
        ->assertSessionHasNoErrors();

    $confirmed = Transaction::query()->where('recurrence_id', $template->id)->first();

    expect($confirmed)->not->toBeNull()
        ->and($confirmed->type)->toBe(TransactionType::Income)
        ->and(Decimal::of((string) $confirmed->amount)->toString(2))->toBe('5000.00')
        ->and($confirmed->person_id)->toBe($user->id);

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.income.amount', '5000.00')
            ->where('balance.amount', '11000.00'));
});

test('editing the repeating transaction changes later pending amounts only', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['opening_balance' => '1000.00']);
    $category = Category::factory()->create(['name' => 'Streaming plan']);

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($account, $category, $user, [
            'amount' => '100',
            'booked_on' => '2026-10-03',
            'note' => 'Streaming',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $template = Transaction::query()->where('note', 'Streaming')->firstOrFail();

    $this->actingAs($user)
        ->post(route('recurrences.store', $template), ['month' => '2026-11'])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->patch(route('transactions.update', $template), recurringEntry($account, $category, $user, [
            'amount' => '180',
            'booked_on' => '2026-10-03',
            'note' => 'Streaming',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $november = Transaction::query()->where('recurrence_id', $template->id)->first();

    expect($november)->not->toBeNull()
        ->and(Decimal::of((string) $november->amount)->toString(2))->toBe('100.00');

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-12']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('pending.0.amount.formatted', '180.00 RSD')
            ->where('pending.0.date', '2026-12-03'));
});

test('unchecking repeat stops later pending items and keeps confirmed ones', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create(['name' => 'House loan']);

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($account, $category, $user, [
            'amount' => '200',
            'booked_on' => '2026-10-05',
            'note' => 'Mortgage',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $template = Transaction::query()->where('note', 'Mortgage')->firstOrFail();

    $this->actingAs($user)
        ->post(route('recurrences.store', $template), ['month' => '2026-11'])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->patch(route('transactions.update', $template), recurringEntry($account, $category, $user, [
            'amount' => '200',
            'booked_on' => '2026-10-05',
            'note' => 'Mortgage',
        ]))
        ->assertSessionHasNoErrors();

    expect($template->refresh()->repeats)->toBeFalse()
        ->and(Transaction::query()->where('recurrence_id', $template->id)->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-12']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('pending', []));
});

test('a repeat on the 31st is due on the last day of a shorter month', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create(['name' => 'House loan']);

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($account, $category, $user, [
            'amount' => '75',
            'booked_on' => '2026-01-31',
            'note' => 'Month end',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $template = Transaction::query()->where('note', 'Month end')->firstOrFail();

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-02']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('pending.0.date', '2026-02-28'));

    $this->actingAs($user)
        ->post(route('recurrences.store', $template), ['month' => '2026-02'])
        ->assertSessionHasNoErrors();

    expect(Transaction::query()->where('recurrence_id', $template->id)->first()->booked_on->toDateString())
        ->toBe('2026-02-28');
});

test('pending repeats follow the transaction list filters', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $mortgage = Category::factory()->create(['name' => 'House loan']);
    $salary = Category::factory()->income()->create(['name' => 'Pay']);

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($account, $mortgage, $user, [
            'amount' => '200',
            'booked_on' => '2026-10-05',
            'note' => 'Mortgage',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($account, $salary, $user, [
            'amount' => '5000',
            'booked_on' => '2026-10-01',
            'note' => 'Salary',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-11', 'type' => 'income']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('pending', fn ($rows): bool => count($rows) === 1)
            ->where('pending.0.category', 'Pay'));
});

test('an archived account cannot be confirmed until another account is chosen', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['opening_balance' => '1000.00']);
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($account, $category, $user, [
            'amount' => '200',
            'booked_on' => '2026-10-05',
            'note' => 'Bill',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $template = Transaction::query()->where('note', 'Bill')->firstOrFail();
    $account->update(['archived_at' => now()]);

    $this->actingAs($user)
        ->post(route('recurrences.store', $template), ['month' => '2026-11'])
        ->assertRedirect(route('recurrences.edit', ['transaction' => $template, 'month' => '2026-11']))
        ->assertSessionHasErrors('account_id');

    expect(Transaction::query()->where('recurrence_id', $template->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-11']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.spent.amount', '0.00')
            ->where('balance.amount', '800.00'));
});

test('a date outside the month is rejected', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($account, $category, $user, [
            'amount' => '20',
            'booked_on' => '2026-10-05',
            'note' => 'Bill',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $template = Transaction::query()->where('note', 'Bill')->firstOrFail();

    $this->actingAs($user)
        ->post(route('recurrences.store', $template), [
            'month' => '2026-11',
            'amount' => '20',
            'currency' => 'RSD',
            'account_id' => $account->id,
            'booked_on' => '2026-12-01',
        ])
        ->assertRedirect(route('recurrences.edit', ['transaction' => $template, 'month' => '2026-11']))
        ->assertSessionHasErrors('booked_on');

    expect(Transaction::query()->where('recurrence_id', $template->id)->exists())->toBeFalse();
});

test('the month of the repeating transaction cannot be confirmed again', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($account, $category, $user, [
            'amount' => '20',
            'booked_on' => '2026-10-05',
            'note' => 'Bill',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $template = Transaction::query()->where('note', 'Bill')->firstOrFail();

    $this->actingAs($user)
        ->post(route('recurrences.store', $template), ['month' => '2026-10'])
        ->assertNotFound();

    expect(Transaction::query()->count())->toBe(1);
});

test('someone outside the workspace cannot confirm a repeat', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create();
    $elsewhere = Workspace::factory()->create();
    $outsider = User::factory()->create(['workspace_id' => $elsewhere->id]);

    $this->actingAs($user)
        ->post(route('transactions.store'), recurringEntry($account, $category, $user, [
            'amount' => '20',
            'booked_on' => '2026-10-05',
            'note' => 'Bill',
            'recurring' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $template = Transaction::query()->where('note', 'Bill')->firstOrFail();

    $this->actingAs($outsider)
        ->post(route('recurrences.store', $template), ['month' => '2026-11'])
        ->assertForbidden();

    $this->actingAs($outsider)
        ->get(route('recurrences.edit', ['transaction' => $template, 'month' => '2026-11']))
        ->assertForbidden();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function recurringEntry(Account $account, Category $category, User $person, array $overrides = []): array
{
    return array_merge([
        'type' => $category->kind->value,
        'amount' => '42.50',
        'currency' => $account->currency,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'booked_on' => '2026-10-02',
        'person_id' => $person->id,
        'note' => 'Weekly shop',
    ], $overrides);
}
