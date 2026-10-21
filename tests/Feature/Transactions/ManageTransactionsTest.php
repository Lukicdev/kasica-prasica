<?php

use App\Enums\Currency;
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

test('guests cannot view or change transactions', function () {
    $transaction = Transaction::factory()->create();

    $this->get(route('transactions.index'))->assertRedirect(route('login'));
    $this->get(route('transactions.create'))->assertRedirect(route('login'));
    $this->get(route('transactions.show', $transaction))->assertRedirect(route('login'));
    $this->post(route('transactions.store'), [])->assertRedirect(route('login'));
    $this->patch(route('transactions.update', $transaction), [])->assertRedirect(route('login'));
    $this->delete(route('transactions.destroy', $transaction))->assertRedirect(route('login'));
});

test('unverified users cannot view or change transactions', function () {
    $user = User::factory()->unverified()->create();
    $transaction = Transaction::factory()->create();

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->post(route('transactions.store'), [])
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->patch(route('transactions.update', $transaction), [])
        ->assertRedirect(route('verification.notice'));
});

test('a member can add an expense and the confirmation shows the new balance', function () {
    $user = User::factory()->create(['name' => 'Ada']);
    $account = Account::factory()->create([
        'name' => 'Household cash',
        'opening_balance' => '1000.00',
    ]);
    $category = Category::factory()->create(['name' => 'Corner shop']);

    $this->actingAs($user)
        ->post(route('transactions.store'), transactionPayload($account, $category, $user))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('transactions.index', ['month' => '2026-10']));

    $transaction = Transaction::query()->where('note', 'Weekly shop')->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe(TransactionType::Expense)
        ->and(Decimal::of((string) $transaction->amount)->toString(2))->toBe('42.50')
        ->and($transaction->currency)->toBe(Currency::Rsd)
        ->and(Decimal::of((string) $transaction->account_amount)->toString(2))->toBe('42.50')
        ->and(Decimal::of((string) $transaction->base_amount)->toString(2))->toBe('42.50')
        ->and(Decimal::of((string) $transaction->exchange_rate)->toString(0))->toBe('1')
        ->and($transaction->booked_on->toDateString())->toBe('2026-10-02')
        ->and($transaction->category_id)->toBe($category->id)
        ->and($transaction->person_id)->toBe($user->id)
        ->and($transaction->created_by)->toBe($user->id)
        ->and($transaction->updated_by)->toBe($user->id)
        ->and($transaction->workspace_id)->toBe($user->workspace_id);

    $this->actingAs($user)
        ->get(route('transactions.show', $transaction))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transaction.type_label', 'Expense')
            ->where('transaction.category', 'Corner shop')
            ->where('transaction.note', 'Weekly shop')
            ->where('transaction.person', 'Ada')
            ->where('transaction.amount.formatted', '42.50 RSD')
            ->where('transaction.amount.original_formatted', null)
            ->where('transaction.account_amount.formatted', '42.50 RSD')
            ->where('transaction.balance.amount', '957.50')
            ->where('transaction.balance.formatted', '957.50 RSD')
            ->where('transaction.updated_by', null));
});

test('income increases the account balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['opening_balance' => '100.00']);
    $category = Category::factory()->income()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transactionPayload($account, $category, $user, [
            'type' => 'income',
            'amount' => '50',
            'note' => 'Salary',
        ]))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('transactions.show', Transaction::query()->first()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transaction.type_label', 'Income')
            ->where('transaction.balance.amount', '150.00'));
});

test('a refund is a negative expense and increases the balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['opening_balance' => '100.00']);
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transactionPayload($account, $category, $user, [
            'amount' => '25',
            'refund' => '1',
            'note' => 'Returned milk',
        ]))
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->where('note', 'Returned milk')->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->isRefund())->toBeTrue()
        ->and(Decimal::of((string) $transaction->amount)->toString(2))->toBe('-25.00')
        ->and(Decimal::of((string) $transaction->base_amount)->toString(2))->toBe('-25.00');

    $this->actingAs($user)
        ->get(route('transactions.show', $transaction))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transaction.type_label', 'Refund')
            ->where('transaction.balance.amount', '125.00'));
});

test('a euro amount stores the dinar value from the exchange rate', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'name' => 'Cash',
        'opening_balance' => '2000.00',
        'currency' => Currency::Rsd,
    ]);
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transactionPayload($account, $category, $user, [
            'amount' => '10',
            'currency' => 'EUR',
            'account_amount' => '1170',
            'note' => 'Taxi',
        ]))
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->where('note', 'Taxi')->first();

    expect($transaction)->not->toBeNull()
        ->and(Decimal::of((string) $transaction->amount)->toString(2))->toBe('10.00')
        ->and($transaction->currency)->toBe(Currency::Eur)
        ->and(Decimal::of((string) $transaction->account_amount)->toString(2))->toBe('1175.00')
        ->and(Decimal::of((string) $transaction->base_amount)->toString(2))->toBe('1175.00')
        ->and(Decimal::of((string) $transaction->exchange_rate)->toString(1))->toBe('117.5');

    $this->actingAs($user)
        ->get(route('transactions.show', $transaction))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transaction.balance.amount', '825.00')
            ->where('transaction.amount.formatted', '1,175.00 RSD')
            ->where('transaction.amount.original_formatted', '10.00 EUR')
            ->where('transaction.account_amount.formatted', '1,175.00 RSD'));
});

test('a dinar amount on a euro account is converted at the exchange rate', function () {
    $user = User::factory()->create();
    $account = Account::factory()->inCurrency(Currency::Eur)->create([
        'opening_balance' => '10.00',
    ]);
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transactionPayload($account, $category, $user, [
            'amount' => '100',
            'currency' => 'RSD',
            'account_amount' => '-1',
        ]))
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->first();

    expect($transaction)->not->toBeNull()
        ->and(Decimal::of((string) $transaction->base_amount)->toString(2))->toBe('100.00')
        ->and(Decimal::of((string) $transaction->account_amount)->toString(2))->toBe('0.85');
});

test('a matching currency ignores a submitted account amount', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['opening_balance' => '50.00']);
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transactionPayload($account, $category, $user, [
            'amount' => '10',
            'account_amount' => '99',
        ]))
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->first();

    expect(Decimal::of((string) $transaction->account_amount)->toString(2))->toBe('10.00');
});

test('transaction details are validated', function (array $overrides, string $field) {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->from(route('transactions.create'))
        ->post(route('transactions.store'), transactionPayload($account, $category, $user, $overrides))
        ->assertRedirect(route('transactions.create'))
        ->assertSessionHasErrors($field);

    expect(Transaction::query()->count())->toBe(0);
})->with([
    'missing amount' => [['amount' => ''], 'amount'],
    'zero amount' => [['amount' => '0'], 'amount'],
    'too many decimals' => [['amount' => '10.555'], 'amount'],
    'unknown type' => [['type' => 'transfer'], 'type'],
    'unknown currency' => [['currency' => 'USD'], 'currency'],
    'missing category' => [['category_id' => ''], 'category_id'],
    'missing account' => [['account_id' => ''], 'account_id'],
    'missing person' => [['person_id' => ''], 'person_id'],
    'missing date' => [['booked_on' => ''], 'booked_on'],
    'long note' => [['note' => str_repeat('a', 1001)], 'note'],
]);

test('income cannot use an expense category and a negative amount', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $expense = Category::factory()->create();
    $income = Category::factory()->income()->create();

    $this->actingAs($user)
        ->from(route('transactions.create'))
        ->post(route('transactions.store'), transactionPayload($account, $expense, $user, [
            'type' => 'income',
            'amount' => '20',
        ]))
        ->assertSessionHasErrors('category_id');

    $this->actingAs($user)
        ->from(route('transactions.create'))
        ->post(route('transactions.store'), transactionPayload($account, $income, $user, [
            'type' => 'income',
            'amount' => '-20',
        ]))
        ->assertSessionHasErrors('amount');

    expect(Transaction::query()->count())->toBe(0);
});

test('archived accounts and archived categories cannot be used for a new transaction', function () {
    $user = User::factory()->create();
    $open = Account::factory()->create();
    $archivedAccount = Account::factory()->archived()->create();
    $category = Category::factory()->create();
    $archivedCategory = Category::factory()->archived()->create();

    $this->actingAs($user)
        ->from(route('transactions.create'))
        ->post(route('transactions.store'), transactionPayload($archivedAccount, $category, $user))
        ->assertSessionHasErrors('account_id');

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories', fn ($categories): bool => collect($categories)->pluck('id')->contains($archivedCategory->id) === false)
            ->where('accounts', fn ($accounts): bool => collect($accounts)->pluck('id')->contains($archivedAccount->id) === false
                && collect($accounts)->pluck('id')->contains($open->id)));

    $this->actingAs($user)
        ->from(route('transactions.create'))
        ->post(route('transactions.store'), transactionPayload($open, $archivedCategory, $user))
        ->assertSessionHasErrors('category_id');

    expect(Transaction::query()->count())->toBe(0);
});

test('another workspace cannot be used and cannot see the transaction', function () {
    $member = User::factory()->create(['name' => 'Ada']);
    $account = Account::factory()->create(['name' => 'Shared cash', 'opening_balance' => '80.00']);
    $category = Category::factory()->create(['name' => 'Corner shop']);
    $elsewhere = Workspace::factory()->create();
    $outsider = User::factory()->create(['workspace_id' => $elsewhere->id]);
    $foreignAccount = Account::factory()->create(['workspace_id' => $elsewhere->id]);
    $foreignCategory = Category::factory()->create(['workspace_id' => $elsewhere->id]);

    $this->actingAs($member)
        ->post(route('transactions.store'), transactionPayload($account, $category, $user = $member, [
            'note' => 'Shared shop',
        ]))
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->where('note', 'Shared shop')->first();

    $this->actingAs($member)
        ->from(route('transactions.create'))
        ->post(route('transactions.store'), transactionPayload($foreignAccount, $category, $member))
        ->assertSessionHasErrors('account_id');

    $this->actingAs($member)
        ->from(route('transactions.create'))
        ->post(route('transactions.store'), transactionPayload($account, $foreignCategory, $member))
        ->assertSessionHasErrors('category_id');

    $this->actingAs($member)
        ->from(route('transactions.create'))
        ->post(route('transactions.store'), transactionPayload($account, $category, $outsider))
        ->assertSessionHasErrors('person_id');

    $this->actingAs($outsider)
        ->get(route('transactions.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('transactions', []));

    $this->actingAs($outsider)->get(route('transactions.show', $transaction))->assertForbidden();
    $this->actingAs($outsider)
        ->patch(route('transactions.update', $transaction), transactionPayload($account, $category, $member))
        ->assertForbidden();
    $this->actingAs($outsider)->delete(route('transactions.destroy', $transaction))->assertForbidden();

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions.0.note', 'Shared shop'));
});

test('save and add another keeps the date, account and type and confirms the balance', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'name' => 'Wallet',
        'opening_balance' => '500.00',
    ]);
    $other = Account::factory()->create(['name' => 'Card']);
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transactionPayload($account, $category, $user, [
            'another' => '1',
            'booked_on' => '2026-10-18',
            'amount' => '20',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('transactions.create', [
            'type' => 'expense',
            'account' => $account->id,
            'date' => '2026-10-18',
        ]));

    $this->actingAs($user)
        ->get(route('transactions.create', [
            'type' => 'expense',
            'account' => $account->id,
            'date' => '2026-10-18',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('confirmation.id', Transaction::query()->sole()->id)
            ->where('confirmation.account', 'Wallet')
            ->where('confirmation.formatted', '480.00 RSD')
            ->where('defaults.type', 'expense')
            ->where('defaults.account_id', $account->id)
            ->where('defaults.date', '2026-10-18')
            ->where('defaults.account_id', fn (int $id): bool => $id !== $other->id));
});

test('the form defaults to today, the current person and the last account used', function () {
    $this->travelTo('2026-10-20');

    $user = User::factory()->create();
    $partner = User::factory()->create(['name' => 'Bea']);
    $first = Account::factory()->create(['name' => 'First']);
    $second = Account::factory()->create(['name' => 'Second']);
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('needs_account', false)
            ->where('defaults.type', 'expense')
            ->where('defaults.person_id', $user->id)
            ->where('defaults.date', '2026-10-20')
            ->where('defaults.category_id', null)
            ->where('defaults.account_id', fn (int $id): bool => in_array($id, [$first->id, $second->id], true)));

    $this->actingAs($user)
        ->post(route('transactions.store'), transactionPayload($second, $category, $partner, [
            'booked_on' => '2026-10-20',
        ]))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('defaults.account_id', $second->id));
});

test('a category in the address opens the form with that category selected', function () {
    $user = User::factory()->create();
    Account::factory()->create();
    $income = Category::factory()->income()->create(['name' => 'Year-end bonus']);
    $elsewhere = Workspace::factory()->create();
    $foreign = Category::factory()->income()->create(['workspace_id' => $elsewhere->id]);

    $this->actingAs($user)
        ->get(route('transactions.create', ['category' => $income->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('defaults.type', 'income')
            ->where('defaults.category_id', $income->id));

    $this->actingAs($user)
        ->get(route('transactions.create', ['category' => $foreign->id, 'type' => 'expense']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('defaults.type', 'expense')
            ->where('defaults.category_id', null));
});

test('adding a transaction asks for an account when the workspace has none', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('needs_account', true)
            ->where('accounts', []));
});

test('editing and deleting update the affected balances', function () {
    $owner = User::factory()->create(['name' => 'Ada']);
    $editor = User::factory()->create(['name' => 'Bea']);
    $cash = Account::factory()->create(['name' => 'Cash', 'opening_balance' => '1000.00']);
    $card = Account::factory()->create(['name' => 'Card', 'opening_balance' => '200.00']);
    $category = Category::factory()->create();
    $archived = Category::factory()->archived()->create(['name' => 'Old']);

    $this->actingAs($owner)
        ->post(route('transactions.store'), transactionPayload($cash, $category, $owner, [
            'amount' => '100',
            'note' => 'Taxi',
        ]))
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->where('note', 'Taxi')->first();

    $this->travel(5)->seconds();

    $this->actingAs($editor)
        ->patch(route('transactions.update', $transaction), transactionPayload($card, $category, $owner, [
            'amount' => '40',
            'note' => 'Corrected',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('transactions.show', $transaction));

    $transaction->refresh();

    expect(Decimal::of((string) $transaction->amount)->toString(2))->toBe('40.00')
        ->and($transaction->account_id)->toBe($card->id)
        ->and($transaction->note)->toBe('Corrected')
        ->and($transaction->updated_by)->toBe($editor->id)
        ->and($transaction->created_by)->toBe($owner->id);

    $this->actingAs($editor)
        ->get(route('transactions.show', $transaction))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transaction.balance.amount', '160.00')
            ->where('transaction.updated_by', 'Bea'));

    $this->actingAs($editor)
        ->get(route('accounts.show', $cash))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('account.balance.amount', '1000.00'));

    $this->actingAs($editor)
        ->from(route('transactions.edit', $transaction))
        ->patch(route('transactions.update', $transaction), transactionPayload($card, $archived, $owner))
        ->assertSessionHasErrors('category_id');

    $transaction->update(['category_id' => $archived->id]);

    $this->actingAs($editor)
        ->patch(route('transactions.update', $transaction), transactionPayload($card, $archived, $owner, [
            'note' => 'Still archived',
        ]))
        ->assertSessionHasNoErrors();

    expect($transaction->refresh()->note)->toBe('Still archived');

    $this->actingAs($editor)
        ->delete(route('transactions.destroy', $transaction))
        ->assertRedirect(route('transactions.index', ['month' => '2026-10']));

    expect(Transaction::query()->whereKey($transaction->id)->exists())->toBeFalse();

    $this->actingAs($editor)
        ->get(route('accounts.show', $card))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('account.balance.amount', '200.00'));
});

test('opening the list without a month uses the current month', function () {
    $this->travelTo('2026-03-15');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('transactions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('month.value', '2026-03')
            ->where('month.label', 'March 2026')
            ->where('filtered', false));
});

test('the table lists one month and can be filtered', function () {
    $user = User::factory()->create(['name' => 'Ada']);
    $partner = User::factory()->create(['name' => 'Bea']);
    $cash = Account::factory()->create(['name' => 'Cash']);
    $card = Account::factory()->inCurrency(Currency::Eur)->create(['name' => 'Euro card']);
    $groceries = Category::factory()->create(['name' => 'Corner shop']);
    $taxi = Category::factory()->create(['name' => 'Taxi']);
    $salary = Category::factory()->income()->create(['name' => 'Paycheck']);

    $this->actingAs($user)->post(route('transactions.store'), transactionPayload($cash, $groceries, $user, [
        'amount' => '40',
        'booked_on' => '2026-10-03',
        'note' => 'Market',
    ]));
    $this->actingAs($user)->post(route('transactions.store'), transactionPayload($card, $taxi, $partner, [
        'amount' => '10',
        'currency' => 'EUR',
        'account_amount' => '10',
        'booked_on' => '2026-10-04',
        'note' => 'Airport taxi',
    ]));
    $this->actingAs($partner)->post(route('transactions.store'), transactionPayload($cash, $salary, $partner, [
        'type' => 'income',
        'amount' => '100',
        'booked_on' => '2026-10-01',
        'note' => 'October salary',
    ]));
    $this->actingAs($user)
        ->post(route('transactions.store'), transactionPayload($cash, $groceries, $user, [
            'amount' => '15',
            'booked_on' => '2026-09-30',
            'note' => 'September shop',
        ]))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('month.label', 'October 2026')
            ->where('month.previous', '2026-09')
            ->where('month.next', '2026-11')
            ->where('filtered', false)
            ->where('sort.column', 'date')
            ->where('sort.direction', 'desc')
            ->where('transactions', fn ($rows): bool => count($rows) === 3)
            ->where('transactions.0.note', 'Airport taxi')
            ->where('transactions.2.note', 'October salary')
            ->where('total.amount', '-1115.00')
            ->where('total.formatted', '-1,115.00 RSD')
            ->missing('transactions.3'));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10', 'type' => 'expense']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filtered', true)
            ->where('transactions', fn ($rows): bool => count($rows) === 2)
            ->where('total.amount', '-1215.00'));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10', 'category' => $groceries->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions', fn ($rows): bool => count($rows) === 1)
            ->where('transactions.0.note', 'Market'));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10', 'account' => $card->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions.0.note', 'Airport taxi')
            ->where('transactions', fn ($rows): bool => count($rows) === 1));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10', 'person' => $partner->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions', fn ($rows): bool => count($rows) === 2));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10', 'currency' => 'EUR']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions.0.note', 'Airport taxi')
            ->where('transactions', fn ($rows): bool => count($rows) === 1));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10', 'search' => 'taxi']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions.0.note', 'Airport taxi')
            ->where('transactions', fn ($rows): bool => count($rows) === 1));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10', 'search' => 'Corner shop']))
        ->assertInertia(fn (Assert $page) => $page->where('transactions', []));

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-09']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions.0.note', 'September shop')
            ->where('transactions', fn ($rows): bool => count($rows) === 1));
});

test('clicking a column title sorts that column and clicking again reverses it', function () {
    $ada = User::factory()->create(['name' => 'Ada']);
    $bea = User::factory()->create(['name' => 'Bea']);
    $cash = Account::factory()->create(['name' => 'Cash']);
    $card = Account::factory()->create(['name' => 'Card']);
    $alpha = Category::factory()->create(['name' => 'Alpha']);
    $zulu = Category::factory()->create(['name' => 'Zulu']);
    $salary = Category::factory()->income()->create(['name' => 'Paycheck']);

    $early = transactionFrom($ada, $cash, $zulu, [
        'amount' => '100.00',
        'account_amount' => '100.00',
        'base_amount' => '100.00',
        'booked_on' => '2026-10-01',
        'note' => 'Zebra',
        'person_id' => $bea->id,
    ]);
    $euro = transactionFrom($bea, $card, $alpha, [
        'amount' => '1.00',
        'currency' => Currency::Eur,
        'account_amount' => '1.00',
        'base_amount' => '117.50',
        'exchange_rate' => '117.5',
        'booked_on' => '2026-10-02',
        'note' => 'Apple',
        'person_id' => $ada->id,
    ]);
    $income = transactionFrom($ada, $cash, $salary, [
        'type' => TransactionType::Income,
        'amount' => '50.00',
        'account_amount' => '50.00',
        'base_amount' => '50.00',
        'booked_on' => '2026-10-03',
        'note' => 'Middle',
        'person_id' => $ada->id,
    ]);

    $this->actingAs($ada)
        ->get(route('transactions.index', ['month' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions.0.id', $income->id)
            ->where('transactions.1.id', $euro->id)
            ->where('transactions.2.id', $early->id));

    $this->actingAs($ada)
        ->get(route('transactions.index', ['month' => '2026-10', 'sort' => 'date', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sort.column', 'date')
            ->where('sort.direction', 'asc')
            ->where('transactions.0.id', $early->id)
            ->where('transactions.2.id', $income->id));

    $this->actingAs($ada)
        ->get(route('transactions.index', ['month' => '2026-10', 'sort' => 'amount', 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions.0.id', $euro->id)
            ->where('transactions.1.id', $early->id)
            ->where('transactions.2.id', $income->id));

    $this->actingAs($ada)
        ->get(route('transactions.index', ['month' => '2026-10', 'sort' => 'amount', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page->where('transactions.0.id', $income->id));

    $this->actingAs($ada)
        ->get(route('transactions.index', ['month' => '2026-10', 'sort' => 'category', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions.0.id', $euro->id)
            ->where('transactions.1.id', $income->id)
            ->where('transactions.2.id', $early->id));

    $this->actingAs($ada)
        ->get(route('transactions.index', ['month' => '2026-10', 'sort' => 'category', 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page->where('transactions.0.id', $early->id));

    $this->actingAs($ada)
        ->get(route('transactions.index', ['month' => '2026-10', 'sort' => 'note', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page->where('transactions.0.note', 'Apple'));

    $this->actingAs($ada)
        ->get(route('transactions.index', ['month' => '2026-10', 'sort' => 'account', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page->where('transactions.0.account', 'Card'));

    $this->actingAs($ada)
        ->get(route('transactions.index', ['month' => '2026-10', 'sort' => 'person', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page->where('transactions.0.person', 'Ada'));

    $this->actingAs($ada)
        ->get(route('transactions.index', ['month' => '2026-10', 'sort' => 'type', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page->where('transactions.0.type', 'expense'));

    $this->actingAs($ada)
        ->get(route('transactions.index', ['month' => '2026-10', 'sort' => 'name;drop', 'direction' => 'sideways']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sort.column', 'date')
            ->where('sort.direction', 'desc'));
});

test('the euro total is the dinar total divided once', function () {
    $user = User::factory()->displaysIn(Currency::Eur)->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create();

    foreach (['10.00', '10.00', '10.00'] as $amount) {
        $this->actingAs($user)->post(route('transactions.store'), transactionPayload($account, $category, $user, [
            'amount' => $amount,
            'booked_on' => '2026-10-02',
            'note' => 'Small '.$amount,
        ]))->assertSessionHasNoErrors();
    }

    $this->actingAs($user)
        ->get(route('transactions.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('transactions.0.amount.formatted', '0.09 EUR')
            ->where('transactions.0.amount.original_formatted', '10.00 RSD')
            ->where('total.amount', '-0.26')
            ->where('total.currency', 'EUR')
            ->where('total.formatted', '-0.26 EUR'));
});

test('a blank note is stored as empty', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('transactions.store'), transactionPayload($account, $category, $user, [
            'note' => '   ',
        ]))
        ->assertSessionHasNoErrors();

    expect(Transaction::query()->first()->note)->toBeNull();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function transactionPayload(Account $account, Category $category, User $person, array $overrides = []): array
{
    return array_merge([
        'type' => $category->kind->value,
        'amount' => '42.50',
        'currency' => $account->currency->value,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'booked_on' => '2026-10-02',
        'person_id' => $person->id,
        'note' => 'Weekly shop',
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function transactionFrom(User $actor, Account $account, Category $category, array $overrides = []): Transaction
{
    return Transaction::factory()->create(array_merge([
        'workspace_id' => $actor->workspace_id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'person_id' => $actor->id,
        'type' => $category->kind->value === 'income' ? TransactionType::Income : TransactionType::Expense,
        'amount' => '10.00',
        'currency' => $account->currency,
        'account_amount' => '10.00',
        'base_amount' => '10.00',
        'exchange_rate' => '1',
        'booked_on' => '2026-10-02',
        'created_by' => $actor->id,
        'updated_by' => $actor->id,
    ], $overrides));
}
