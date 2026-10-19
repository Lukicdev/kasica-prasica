<?php

use App\Actions\Transactions\DeleteTransaction;
use App\Actions\Transactions\UpdateTransaction;
use App\Contracts\ExchangeRate;
use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Support\AccountBalance;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('income, expenses, and refunds change the balance immediately', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'opening_balance' => '1000.00',
        'currency' => Currency::Rsd,
    ]);

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertInertia(fn (Assert $page) => $page->where('account.balance.amount', '1000.00'));

    recordExpense($account, $user, '200.00', '2026-10-03', 'Groceries');
    $income = recordTransaction($account, $user, [
        'type' => TransactionType::Income,
        'amount' => '50.00',
        'booked_on' => '2026-10-02',
        'note' => 'Gift',
    ]);
    recordTransaction($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '-25.00',
        'booked_on' => '2026-10-04',
        'note' => 'Returned milk',
    ]);

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('account.balance.amount', '875.00')
            ->where('lines.0.running_balance.amount', '1000.00')
            ->where('lines.1.type_label', 'Income')
            ->where('lines.1.date', '2026-10-02')
            ->where('lines.1.running_balance.amount', '1050.00')
            ->where('lines.1.person', $user->name)
            ->where('lines.2.type_label', 'Expense')
            ->where('lines.2.note', 'Groceries')
            ->where('lines.2.running_balance.amount', '850.00')
            ->where('lines.3.type_label', 'Refund')
            ->where('lines.3.running_balance.amount', '875.00')
            ->where('lines.3.amount.negative', true));

    expect($income->base_amount)->toBe('50.00');
});

test('transactions on the same day stay in the order they were recorded', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['opening_balance' => '100.00']);

    recordExpense($account, $user, '10.00', '2026-10-04');
    recordTransaction($account, $user, [
        'type' => TransactionType::Income,
        'amount' => '3.00',
        'booked_on' => '2026-10-04',
    ]);

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertInertia(fn (Assert $page) => $page
            ->where('lines.1.type_label', 'Expense')
            ->where('lines.1.running_balance.amount', '90.00')
            ->where('lines.2.type_label', 'Income')
            ->where('lines.2.running_balance.amount', '93.00'));
});

test('editing a transaction updates the balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['opening_balance' => '1000.00']);
    $transaction = recordExpense($account, $user, '100.00');

    app(UpdateTransaction::class)->handle($transaction, $account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '40.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-02',
        'note' => 'Corrected',
    ]);

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertInertia(fn (Assert $page) => $page
            ->where('account.balance.amount', '960.00')
            ->where('lines.1.note', 'Corrected')
            ->where('lines.1.running_balance.amount', '960.00'));
});

test('moving a transaction updates both accounts', function () {
    $user = User::factory()->create();
    $cash = Account::factory()->create(['name' => 'Cash', 'opening_balance' => '1000.00']);
    $card = Account::factory()->create([
        'name' => 'Card',
        'type' => 'card',
        'opening_balance' => '0.00',
    ]);
    $transaction = recordExpense($cash, $user, '100.00', '2026-10-03', 'Taxi');

    app(UpdateTransaction::class)->handle($transaction, $card, $user, [
        'type' => TransactionType::Expense,
        'amount' => '100.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-03',
        'note' => 'Taxi',
    ]);

    $this->actingAs($user)
        ->get(route('accounts.show', $cash))
        ->assertInertia(fn (Assert $page) => $page
            ->where('account.balance.amount', '1000.00')
            ->where('lines', fn ($lines) => count($lines) === 1));

    $this->actingAs($user)
        ->get(route('accounts.show', $card))
        ->assertInertia(fn (Assert $page) => $page
            ->where('account.balance.amount', '-100.00')
            ->where('account.balance.negative', true)
            ->where('lines.1.running_balance.amount', '-100.00'));
});

test('deleting a transaction puts the balance back', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['opening_balance' => '1000.00']);
    $transaction = recordExpense($account, $user, '100.00');

    app(DeleteTransaction::class)->handle($transaction, $user);

    expect(Transaction::query()->whereKey($transaction->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertInertia(fn (Assert $page) => $page
            ->where('account.balance.amount', '1000.00')
            ->where('lines', fn ($lines) => count($lines) === 1));
});

test('a transaction in another currency uses the confirmed account amount', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'opening_balance' => '2000.00',
        'currency' => Currency::Rsd,
    ]);

    $transaction = recordTransaction($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '10.00',
        'currency' => Currency::Eur,
        'account_amount' => '1200.00',
        'booked_on' => '2026-10-05',
        'note' => 'Train ticket',
    ]);

    expect(Decimal::of((string) $transaction->base_amount)->toString(2))->toBe('1175.00')
        ->and(Decimal::of((string) $transaction->exchange_rate)->toString(1))->toBe('117.5')
        ->and(Decimal::of((string) $transaction->account_amount)->toString(2))->toBe('1200.00');

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertInertia(fn (Assert $page) => $page
            ->where('account.balance.amount', '800.00')
            ->where('lines.1.amount.formatted', '1,200.00 RSD')
            ->where('lines.1.amount.original_formatted', '10.00 EUR')
            ->where('lines.1.running_balance.formatted', '800.00 RSD'));
});

test('an euro account balance stays in euros', function () {
    $user = User::factory()->create();
    $account = Account::factory()->inCurrency(Currency::Eur)->create([
        'opening_balance' => '50.00',
    ]);

    recordExpense($account, $user, '10.00', '2026-10-02', 'Coffee', Currency::Eur);

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.0.accounts.0.balance.amount', '40.00')
            ->where('groups.0.accounts.0.balance.currency', 'EUR')
            ->where('total.amount', '4700.00')
            ->where('total.currency', 'RSD'));
});

test('a later exchange rate does not change a saved transaction', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['currency' => Currency::Rsd]);

    $saved = recordTransaction($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '10.00',
        'currency' => Currency::Eur,
        'account_amount' => '1175.00',
    ]);

    app()->instance(ExchangeRate::class, new class implements ExchangeRate
    {
        public function rsdPerUnit(Currency $currency, ?CarbonInterface $on = null): string
        {
            return $currency->isBase() ? '1' : '100';
        }
    });

    $later = recordTransaction($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '10.00',
        'currency' => Currency::Eur,
        'account_amount' => '1000.00',
    ]);

    expect(Decimal::of((string) $saved->refresh()->base_amount)->toString(2))->toBe('1175.00')
        ->and(Decimal::of((string) $later->base_amount)->toString(2))->toBe('1000.00');
});

test('matching currencies ignore a different account amount', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['currency' => Currency::Rsd, 'opening_balance' => '0.00']);

    $transaction = recordTransaction($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '15.00',
        'currency' => Currency::Rsd,
        'account_amount' => '99.00',
    ]);

    expect(Decimal::of((string) $transaction->account_amount)->toString(2))->toBe('15.00');
});

test('a different currency needs a confirmed account amount with the same sign', function (array $overrides) {
    $user = User::factory()->create();
    $account = Account::factory()->create(['currency' => Currency::Rsd]);

    expect(fn () => recordTransaction($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '10.00',
        'currency' => Currency::Eur,
        ...$overrides,
    ]))->toThrow(ValidationException::class);
})->with([
    'missing amount' => [[]],
    'zero amount' => [['account_amount' => '0']],
    'opposite sign' => [['account_amount' => '-1175.00']],
]);

test('archived and imported accounts cannot be picked for a new transaction', function () {
    $user = User::factory()->create();
    $archived = Account::factory()->archived()->create();
    $imported = Account::factory()->importedHistory()->create();

    expect(fn () => recordExpense($archived, $user, '10.00'))->toThrow(ValidationException::class)
        ->and(fn () => recordExpense($imported, $user, '10.00'))->toThrow(ValidationException::class);
});

test('a transaction already on an archived account can be corrected but not moved onto one', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['opening_balance' => '100.00']);
    $other = Account::factory()->create(['name' => 'Other']);
    $transaction = recordExpense($account, $user, '30.00');

    $account->update(['archived_at' => now()]);
    $other->update(['archived_at' => now()]);

    app(UpdateTransaction::class)->handle($transaction, $account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '20.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-02',
    ]);

    expect(Decimal::of(app(AccountBalance::class)->of($account->refresh()))->toString(2))->toBe('80.00');

    expect(fn () => app(UpdateTransaction::class)->handle($transaction->refresh(), $other, $user, [
        'type' => TransactionType::Expense,
        'amount' => '20.00',
        'currency' => Currency::Rsd,
        'booked_on' => '2026-10-02',
    ]))->toThrow(ValidationException::class);
});

test('a zero amount is rejected', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();

    expect(fn () => recordExpense($account, $user, '0'))->toThrow(ValidationException::class);
});
