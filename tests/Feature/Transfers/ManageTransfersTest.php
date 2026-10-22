<?php

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AccountBalance;
use App\Support\Decimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests cannot transfer money', function () {
    $this->get(route('transfers.create'))->assertRedirect(route('login'));
    $this->post(route('transfers.store'), [])->assertRedirect(route('login'));
});

test('a transfer moves both balances and is not income or spending', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create(['name' => 'Ada']);
    $cash = Account::factory()->create([
        'name' => 'Cash',
        'icon' => 'banknote',
        'opening_balance' => '1000.00',
    ]);
    $bank = Account::factory()->create([
        'name' => 'Bank',
        'icon' => 'landmark',
        'type' => 'bank',
        'opening_balance' => '500.00',
    ]);
    recordExpense($cash, $user, '40.00', '2026-10-03', 'Market');

    $this->actingAs($user)
        ->post(route('transfers.store'), transferPayload($cash, $bank, $user))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('transactions.index', ['month' => '2026-10']));

    $transfer = Transaction::query()->where('type', TransactionType::Transfer)->first();
    $balances = app(AccountBalance::class);

    expect($transfer)->not->toBeNull()
        ->and($transfer->account_id)->toBe($cash->id)
        ->and($transfer->destination_account_id)->toBe($bank->id)
        ->and($transfer->category_id)->toBeNull()
        ->and(Decimal::of((string) $transfer->amount)->toString(2))->toBe('250.00')
        ->and(Decimal::of((string) $transfer->account_amount)->toString(2))->toBe('250.00')
        ->and(Decimal::of((string) $transfer->destination_amount)->toString(2))->toBe('250.00')
        ->and(Decimal::of((string) $transfer->base_amount)->toString(2))->toBe('250.00')
        ->and($transfer->person_id)->toBe($user->id)
        ->and($transfer->created_by)->toBe($user->id)
        ->and($balances->of($cash))->toBe('710.00')
        ->and($balances->of($bank))->toBe('750.00');

    $this->actingAs($user)
        ->get(route('transactions.show', $transfer))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transaction.type', 'transfer')
            ->where('transaction.type_label', 'Transfer')
            ->where('transaction.excluded_from_budget', true)
            ->where('transaction.account.name', 'Cash')
            ->where('transaction.account.icon', 'banknote')
            ->where('transaction.destination.name', 'Bank')
            ->where('transaction.destination.icon', 'landmark')
            ->where('transaction.balance.amount', '710.00')
            ->where('transaction.destination_balance.amount', '750.00')
            ->where('transaction.category', null));

    $this->actingAs($user)
        ->get(route('dashboard', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.spent.base_amount', '40.00')
            ->where('totals.income.base_amount', '0.00'));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('total.amount', '-40.00')
            ->where('transactions', function ($rows) use ($transfer) {
                $row = collect($rows)->firstWhere('id', $transfer->id);

                return $row !== null
                    && $row['account'] === 'Cash'
                    && $row['account_icon'] === 'banknote'
                    && $row['destination'] === 'Bank'
                    && $row['destination_icon'] === 'landmark';
            }));

    $this->actingAs($user)
        ->get(route('accounts.show', $bank))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('lines.1.type_label', 'Transfer from Cash')
            ->where('lines.1.counterparty.name', 'Cash')
            ->where('lines.1.counterparty.icon', 'banknote')
            ->where('lines.1.counterparty.direction', 'from')
            ->where('lines.1.amount.formatted', '250.00 RSD')
            ->where('lines.1.running_balance.amount', '750.00'));

    $this->actingAs($user)
        ->get(route('accounts.show', $cash))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('lines.2.type_label', 'Transfer to Bank')
            ->where('lines.2.counterparty.name', 'Bank')
            ->where('lines.2.counterparty.icon', 'landmark')
            ->where('lines.2.counterparty.direction', 'to')
            ->where('lines.2.running_balance.amount', '710.00'));
});

test('a transfer between currencies uses the fixed rate and leaves the combined total unchanged', function () {
    $user = User::factory()->create();
    $euros = Account::factory()->inCurrency('EUR')->create([
        'name' => 'Euro cash',
        'opening_balance' => '100.00',
    ]);
    $dinars = Account::factory()->create([
        'name' => 'Dinar cash',
        'opening_balance' => '0.00',
    ]);

    $this->actingAs($user)
        ->post(route('transfers.store'), transferPayload($euros, $dinars, $user, [
            'amount' => '10',
            'currency' => 'EUR',
            'note' => 'Changed euros',
        ]))
        ->assertSessionHasNoErrors();

    $transfer = Transaction::query()->where('note', 'Changed euros')->first();
    $balances = app(AccountBalance::class);

    expect(Decimal::of((string) $transfer->account_amount)->toString(2))->toBe('10.00')
        ->and(Decimal::of((string) $transfer->destination_amount)->toString(2))->toBe('1175.00')
        ->and(Decimal::of((string) $transfer->base_amount)->toString(2))->toBe('1175.00')
        ->and(Decimal::of((string) $transfer->exchange_rate)->toString(1))->toBe('117.5')
        ->and($balances->of($euros))->toBe('90.00')
        ->and($balances->of($dinars))->toBe('1175.00');

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total.amount', '11750.00'));

    $this->actingAs($user)
        ->get(route('accounts.show', $dinars))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('lines.1.amount.formatted', '1,175.00 RSD')
            ->where('lines.1.amount.original_formatted', '10.00 EUR'));
});

test('editing or deleting a transfer updates both the old and the new accounts', function () {
    $user = User::factory()->create();
    $cash = Account::factory()->create(['name' => 'Cash', 'opening_balance' => '1000.00']);
    $bank = Account::factory()->create(['name' => 'Bank', 'opening_balance' => '500.00']);
    $wallet = Account::factory()->create(['name' => 'Wallet', 'opening_balance' => '200.00']);

    $this->actingAs($user)
        ->post(route('transfers.store'), transferPayload($cash, $bank, $user, ['amount' => '100']))
        ->assertSessionHasNoErrors();

    $transfer = Transaction::query()->first();

    $this->actingAs($user)
        ->put(route('transfers.update', $transfer), transferPayload($wallet, $bank, $user, [
            'amount' => '40',
            'note' => 'Moved again',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('transactions.show', $transfer));

    $balances = app(AccountBalance::class);

    expect($transfer->fresh()->account_id)->toBe($wallet->id)
        ->and($transfer->fresh()->destination_account_id)->toBe($bank->id)
        ->and(Decimal::of((string) $transfer->fresh()->account_amount)->toString(2))->toBe('40.00')
        ->and($balances->of($cash))->toBe('1000.00')
        ->and($balances->of($wallet))->toBe('160.00')
        ->and($balances->of($bank))->toBe('540.00');

    $this->actingAs($user)
        ->delete(route('transactions.destroy', $transfer))
        ->assertRedirect();

    expect(Transaction::query()->count())->toBe(0)
        ->and($balances->of($wallet))->toBe('200.00')
        ->and($balances->of($bank))->toBe('500.00');
});

test('a transfer must use two different selectable accounts in this workspace', function () {
    $user = User::factory()->create();
    $cash = Account::factory()->create(['name' => 'Cash']);
    $archived = Account::factory()->archived()->create(['name' => 'Old card']);
    $elsewhere = Workspace::factory()->create();
    $foreign = Account::factory()->create(['workspace_id' => $elsewhere->id, 'name' => 'Foreign']);

    $this->actingAs($user)
        ->from(route('transfers.create'))
        ->post(route('transfers.store'), transferPayload($cash, $cash, $user))
        ->assertSessionHasErrors('to_account_id');

    $this->actingAs($user)
        ->from(route('transfers.create'))
        ->post(route('transfers.store'), transferPayload($cash, $archived, $user))
        ->assertSessionHasErrors('to_account_id');

    $this->actingAs($user)
        ->from(route('transfers.create'))
        ->post(route('transfers.store'), transferPayload($cash, $foreign, $user))
        ->assertSessionHasErrors('to_account_id');

    $this->actingAs($user)
        ->from(route('transfers.create'))
        ->post(route('transfers.store'), transferPayload($cash, Account::factory()->create(), $user, [
            'amount' => '0',
        ]))
        ->assertSessionHasErrors('amount');

    expect(Transaction::query()->count())->toBe(0);
});

test('the transaction form cannot be used to create or rewrite a transfer', function () {
    $user = User::factory()->create();
    $cash = Account::factory()->create(['opening_balance' => '100.00']);
    $bank = Account::factory()->create(['opening_balance' => '100.00']);
    $category = Category::query()->where('name', 'Groceries')->firstOrFail();

    $this->actingAs($user)
        ->post(route('transfers.store'), transferPayload($cash, $bank, $user, ['amount' => '25']))
        ->assertSessionHasNoErrors();

    $transfer = Transaction::query()->first();

    $this->actingAs($user)
        ->get(route('transactions.edit', $transfer))
        ->assertRedirect(route('transfers.edit', $transfer));

    $this->actingAs($user)
        ->from(route('transactions.edit', $transfer))
        ->put(route('transactions.update', $transfer), [
            'type' => 'expense',
            'amount' => '25',
            'currency' => 'RSD',
            'account_id' => $cash->id,
            'category_id' => $category->id,
            'booked_on' => '2026-10-04',
            'person_id' => $user->id,
        ])
        ->assertSessionHasErrors('type');

    expect($transfer->fresh()->type)->toBe(TransactionType::Transfer)
        ->and(app(AccountBalance::class)->of($bank))->toBe('125.00');
});

test('an account that only receives a transfer cannot be deleted', function () {
    $user = User::factory()->create();
    $cash = Account::factory()->create(['name' => 'Cash', 'opening_balance' => '80.00']);
    $bank = Account::factory()->create(['name' => 'Bank']);

    $this->actingAs($user)
        ->post(route('transfers.store'), transferPayload($cash, $bank, $user, ['amount' => '15']))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->delete(route('accounts.destroy', $bank))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('accounts.edit', $bank))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('account.can_delete', false)
            ->where('account.currency_locked', true));

    expect($bank->fresh())->not->toBeNull();
});

test('filtering the list by either account includes the transfer once', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $cash = Account::factory()->create(['name' => 'Cash']);
    $bank = Account::factory()->create(['name' => 'Bank']);

    $this->actingAs($user)
        ->post(route('transfers.store'), transferPayload($cash, $bank, $user))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10', 'account' => $bank->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions', fn ($rows) => count($rows) === 1)
            ->where('total.amount', '0.00'));
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function transferPayload(Account $from, Account $to, User $person, array $overrides = []): array
{
    return array_merge([
        'amount' => '250.00',
        'currency' => 'RSD',
        'from_account_id' => $from->id,
        'to_account_id' => $to->id,
        'booked_on' => '2026-10-04',
        'person_id' => $person->id,
        'note' => 'Cash withdrawal',
    ], $overrides);
}
