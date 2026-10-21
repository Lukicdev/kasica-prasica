<?php

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Support\AccountBalance;
use App\Support\Decimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests cannot reconcile an account', function () {
    $account = Account::factory()->create();

    $this->get(route('accounts.reconcile', $account))->assertRedirect(route('login'));
    $this->post(route('accounts.reconcile.store', $account), [])->assertRedirect(route('login'));
});

test('reconciliation shows the gap and a balancing entry that is left out of the budget', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create(['name' => 'Ada']);
    $account = Account::factory()->create([
        'name' => 'Cash',
        'opening_balance' => '1000.00',
    ]);
    $salary = Category::query()->where('name', 'Salary')->firstOrFail();
    recordTransaction($account, $user, [
        'type' => TransactionType::Income,
        'amount' => '200.00',
        'booked_on' => '2026-10-02',
        'category' => $salary,
    ]);
    recordExpense($account, $user, '100.00', '2026-10-03');

    $this->actingAs($user)
        ->get(route('accounts.reconcile', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('account.balance.amount', '1100.00')
            ->where('account.balance.formatted', '1,100.00 RSD')
            ->where('defaults.note', 'Reconciliation'));

    $this->actingAs($user)
        ->post(route('accounts.reconcile.store', $account), [
            'real_balance' => '1075',
            'booked_on' => '2026-10-20',
            'person_id' => $user->id,
            'note' => 'Counted the cash',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $adjustment = Transaction::query()->where('type', TransactionType::Adjustment)->first();

    expect($adjustment)->not->toBeNull()
        ->and($adjustment->category_id)->toBeNull()
        ->and(Decimal::of((string) $adjustment->account_amount)->toString(2))->toBe('-25.00')
        ->and(Decimal::of((string) $adjustment->base_amount)->toString(2))->toBe('-25.00')
        ->and($adjustment->created_by)->toBe($user->id)
        ->and(app(AccountBalance::class)->of($account))->toBe('1075.00');

    $this->actingAs($user)
        ->get(route('transactions.show', $adjustment))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transaction.type_label', 'Balance adjustment')
            ->where('transaction.excluded_from_budget', true)
            ->where('transaction.balance.amount', '1075.00')
            ->where('transaction.note', 'Counted the cash'));

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.income.base_amount', '200.00')
            ->where('totals.spent.base_amount', '100.00')
            ->where('totals.leftover.base_amount', '100.00'));

    $this->actingAs($user)
        ->get(route('budgets.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('income.base_amount', '200.00'));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total.amount', '100.00'));
});

test('a higher real balance adds money without counting as income', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'name' => 'Cash',
        'opening_balance' => '10.00',
    ]);

    $this->actingAs($user)
        ->post(route('accounts.reconcile.store', $account), reconciliationPayload($user, [
            'real_balance' => '18.50',
        ]))
        ->assertSessionHasNoErrors();

    $adjustment = Transaction::query()->first();

    expect(Decimal::of((string) $adjustment->account_amount)->toString(2))->toBe('8.50')
        ->and(app(AccountBalance::class)->of($account))->toBe('18.50');

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.income.base_amount', '0.00')
            ->where('totals.spent.base_amount', '0.00'));
});

test('a matching balance does not create an entry and a negative balance is allowed', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'opening_balance' => '10.00',
    ]);

    $this->actingAs($user)
        ->from(route('accounts.reconcile', $account))
        ->post(route('accounts.reconcile.store', $account), reconciliationPayload($user, [
            'real_balance' => '10.00',
        ]))
        ->assertSessionHasErrors('real_balance');

    expect(Transaction::query()->count())->toBe(0);

    $this->actingAs($user)
        ->post(route('accounts.reconcile.store', $account), reconciliationPayload($user, [
            'real_balance' => '-5',
        ]))
        ->assertSessionHasNoErrors();

    expect(app(AccountBalance::class)->of($account))->toBe('-5.00')
        ->and(Decimal::of((string) Transaction::query()->first()->account_amount)->toString(2))->toBe('-15.00');
});

test('editing or deleting a balancing entry updates the account and still skips the budget', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $cash = Account::factory()->create(['name' => 'Cash', 'opening_balance' => '100.00']);
    $bank = Account::factory()->create(['name' => 'Bank', 'opening_balance' => '40.00']);

    $this->actingAs($user)
        ->post(route('accounts.reconcile.store', $cash), reconciliationPayload($user, [
            'real_balance' => '90',
        ]))
        ->assertSessionHasNoErrors();

    $adjustment = Transaction::query()->first();

    $this->actingAs($user)
        ->get(route('transactions.edit', $adjustment))
        ->assertRedirect(route('adjustments.edit', $adjustment));

    $this->actingAs($user)
        ->put(route('adjustments.update', $adjustment), [
            'amount' => '-30.00',
            'account_id' => $bank->id,
            'booked_on' => '2026-10-18',
            'person_id' => $user->id,
            'note' => 'Bank was short',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('transactions.show', $adjustment));

    $balances = app(AccountBalance::class);

    expect($adjustment->fresh()->account_id)->toBe($bank->id)
        ->and(Decimal::of((string) $adjustment->fresh()->account_amount)->toString(2))->toBe('-30.00')
        ->and($balances->of($cash))->toBe('100.00')
        ->and($balances->of($bank))->toBe('10.00');

    $this->actingAs($user)
        ->get(route('budgets.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('income.base_amount', '0.00'));

    $this->actingAs($user)
        ->delete(route('transactions.destroy', $adjustment))
        ->assertRedirect();

    expect($balances->of($bank))->toBe('40.00')
        ->and(Transaction::query()->count())->toBe(0);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function reconciliationPayload(User $user, array $overrides = []): array
{
    return array_merge([
        'real_balance' => '0.00',
        'booked_on' => '2026-10-20',
        'person_id' => $user->id,
        'note' => 'Reconciliation',
    ], $overrides);
}
