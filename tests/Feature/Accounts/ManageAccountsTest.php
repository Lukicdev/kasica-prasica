<?php

use App\Enums\Currency;
use App\Models\Account;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Decimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests cannot view or change accounts', function () {
    $account = Account::factory()->create();

    $this->get(route('accounts.index'))->assertRedirect(route('login'));
    $this->get(route('accounts.create'))->assertRedirect(route('login'));
    $this->get(route('accounts.show', $account))->assertRedirect(route('login'));
    $this->post(route('accounts.store'), accountPayload())->assertRedirect(route('login'));
    $this->patch(route('accounts.update', $account), accountPayload())->assertRedirect(route('login'));
    $this->delete(route('accounts.destroy', $account))->assertRedirect(route('login'));
});

test('unverified users cannot view or change accounts', function () {
    $user = User::factory()->unverified()->create();
    $account = Account::factory()->create();

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->post(route('accounts.store'), accountPayload())
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->patch(route('accounts.update', $account), accountPayload())
        ->assertRedirect(route('verification.notice'));
});

test('a member can create an account', function () {
    $user = User::factory()->create(['name' => 'Ada']);

    $this->actingAs($user)
        ->post(route('accounts.store'), accountPayload([
            'opening_balance' => '17049.79',
            'type' => 'bank',
            'currency' => 'EUR',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts.index'));

    $account = Account::query()->where('name', 'Household cash')->first();

    expect($account)->not->toBeNull()
        ->and($account->type->value)->toBe('bank')
        ->and($account->currency)->toBe(Currency::Eur)
        ->and(Decimal::of((string) $account->opening_balance)->toString(2))->toBe('17049.79')
        ->and($account->opening_balance_on->toDateString())->toBe('2026-10-01')
        ->and($account->note)->toBe('Wallet in the kitchen')
        ->and($account->created_by)->toBe($user->id)
        ->and($account->workspace_id)->toBe($user->workspace_id)
        ->and($account->archived_at)->toBeNull();

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('account.name', 'Household cash')
            ->where('account.currency', 'EUR')
            ->where('account.opening_balance', '17049.79')
            ->where('account.balance.formatted', '17,049.79 EUR')
            ->where('account.balance.negative', false)
            ->where('account.note', 'Wallet in the kitchen')
            ->where('lines.0.type_label', 'Opening balance')
            ->where('lines.0.date_label', '1 Oct 2026')
            ->where('lines.0.running_balance.amount', '17049.79'));
});

test('an opening balance can be negative and a note can be left blank', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), accountPayload([
            'name' => 'Credit card',
            'type' => 'card',
            'opening_balance' => '-20.5',
            'note' => '',
        ]))
        ->assertSessionHasNoErrors();

    $account = Account::query()->where('name', 'Credit card')->first();

    expect($account)->not->toBeNull()
        ->and($account->note)->toBeNull()
        ->and(Decimal::of((string) $account->opening_balance)->toString(2))->toBe('-20.50');

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.0.accounts.0.balance.negative', true)
            ->where('groups.0.accounts.0.balance.formatted', '-20.50 RSD')
            ->where('groups.0.accounts.0.display_balance', null)
            ->where('total.negative', true)
            ->where('total.formatted', '-20.50 RSD'));
});

test('account details are validated', function (array $overrides, string $field) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('accounts.create'))
        ->post(route('accounts.store'), accountPayload($overrides))
        ->assertRedirect(route('accounts.create'))
        ->assertSessionHasErrors($field);

    expect(Account::query()->count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'unknown type' => [['type' => 'wallet'], 'type'],
    'unknown currency' => [['currency' => 'USD'], 'currency'],
    'too many decimals' => [['opening_balance' => '10.555'], 'opening_balance'],
    'missing date' => [['opening_balance_on' => ''], 'opening_balance_on'],
    'long note' => [['note' => str_repeat('a', 1001)], 'note'],
    'unknown icon' => [['icon' => 'not-an-icon'], 'icon'],
]);

test('a member can choose an account icon and clear it later', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('accounts.store'), accountPayload(['icon' => 'wallet']))
        ->assertSessionHasNoErrors();

    $account = Account::query()->where('name', 'Household cash')->first();

    expect($account)->not->toBeNull()
        ->and($account->icon)->toBe('wallet');

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.0.accounts.0.icon', 'wallet')
            ->where('groups.0.accounts.0.name', 'Household cash'));

    $this->actingAs($user)
        ->patch(route('accounts.update', $account), accountPayload(['icon' => '']))
        ->assertSessionHasNoErrors();

    expect($account->refresh()->icon)->toBeNull();
});

test('a member can edit every field while the account has no transactions', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'name' => 'Cash',
        'currency' => Currency::Rsd,
        'opening_balance' => '10.00',
    ]);

    $this->actingAs($user)
        ->patch(route('accounts.update', $account), accountPayload([
            'name' => 'Kitchen jar',
            'type' => 'savings',
            'currency' => 'EUR',
            'opening_balance' => '80',
            'opening_balance_on' => '2026-02-01',
            'note' => 'Moved',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts.show', $account));

    $account->refresh();

    expect($account->name)->toBe('Kitchen jar')
        ->and($account->type->value)->toBe('savings')
        ->and($account->currency)->toBe(Currency::Eur)
        ->and(Decimal::of((string) $account->opening_balance)->toString(2))->toBe('80.00')
        ->and($account->opening_balance_on->toDateString())->toBe('2026-02-01')
        ->and($account->note)->toBe('Moved')
        ->and($account->updated_by)->toBe($user->id);
});

test('currency stays fixed once the account has a transaction', function () {
    $owner = User::factory()->create(['name' => 'Ada']);
    $editor = User::factory()->create(['name' => 'Bea']);
    $account = Account::factory()->create([
        'name' => 'Cash',
        'currency' => Currency::Rsd,
        'opening_balance' => '500.00',
        'created_by' => $owner->id,
        'updated_by' => $owner->id,
    ]);

    recordExpense($account, $owner, '25.00');

    $this->travel(5)->seconds();

    $this->actingAs($editor)
        ->from(route('accounts.edit', $account))
        ->patch(route('accounts.update', $account), accountPayload([
            'name' => 'Wallet',
            'currency' => 'EUR',
            'opening_balance' => '400.00',
        ]))
        ->assertRedirect(route('accounts.edit', $account))
        ->assertSessionHasErrors('currency');

    $account->refresh();
    expect($account->currency)->toBe(Currency::Rsd)
        ->and($account->name)->toBe('Cash');

    $this->actingAs($editor)
        ->patch(route('accounts.update', $account), accountPayload([
            'name' => 'Wallet',
            'currency' => 'RSD',
            'opening_balance' => '400.00',
            'note' => 'Counted again',
        ]))
        ->assertSessionHasNoErrors();

    $this->actingAs($editor)
        ->get(route('accounts.show', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('account.name', 'Wallet')
            ->where('account.currency_locked', true)
            ->where('account.balance.amount', '375.00')
            ->where('account.updated_by', 'Bea'));
});

test('an account with transactions cannot be deleted and one without them can', function () {
    $user = User::factory()->create();
    $used = Account::factory()->create(['name' => 'Used']);
    $empty = Account::factory()->create(['name' => 'Empty']);
    recordExpense($used, $user, '10.00');

    $this->actingAs($user)
        ->delete(route('accounts.destroy', $used))
        ->assertForbidden();

    expect($used->fresh())->not->toBeNull();

    $this->actingAs($user)
        ->delete(route('accounts.destroy', $empty))
        ->assertRedirect(route('accounts.index'));

    expect(Account::query()->whereKey($empty->id)->exists())->toBeFalse()
        ->and(Account::query()->whereKey($used->id)->exists())->toBeTrue();
});

test('archiving keeps the history and hides the account from new transactions', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['name' => 'Old card', 'type' => 'card']);
    recordExpense($account, $user, '15.00');

    $this->actingAs($user)
        ->patch(route('accounts.archive', $account))
        ->assertRedirect(route('accounts.show', $account));

    expect(Account::query()->selectable()->whereKey($account->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.0.accounts.0.name', 'Old card')
            ->where('groups.0.accounts.0.archived', true)
            ->where('groups.0.accounts.0.balance.amount', '-15.00'));

    $this->actingAs($user)
        ->get(route('accounts.show', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('lines', fn ($lines) => count($lines) === 2));

    $this->actingAs($user)
        ->patch(route('accounts.restore', $account))
        ->assertRedirect(route('accounts.show', $account));

    expect($account->refresh()->archived_at)->toBeNull()
        ->and(Account::query()->selectable()->whereKey($account->id)->exists())->toBeTrue();
});

test('the list groups accounts by type and totals them in the display currency', function () {
    $user = User::factory()->create();

    Account::factory()->create([
        'name' => 'Wallet',
        'type' => 'cash',
        'currency' => Currency::Rsd,
        'opening_balance' => '100.00',
    ]);
    Account::factory()->create([
        'name' => 'Salary',
        'type' => 'bank',
        'currency' => Currency::Rsd,
        'opening_balance' => '50.00',
    ]);
    Account::factory()->create([
        'name' => 'Current',
        'type' => 'bank',
        'currency' => Currency::Eur,
        'opening_balance' => '10.00',
    ]);
    Account::factory()->archived()->create([
        'name' => 'Closed',
        'type' => 'bank',
        'opening_balance' => '5.00',
    ]);

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups', fn ($groups) => count($groups) === 2)
            ->where('groups.0.type', 'cash')
            ->where('groups.0.label', 'Cash')
            ->where('groups.0.accounts.0.name', 'Wallet')
            ->where('groups.0.accounts.0.balance.formatted', '100.00 RSD')
            ->where('groups.1.type', 'bank')
            ->where('groups.1.label', 'Bank account')
            ->where('groups.1.accounts.0.name', 'Current')
            ->where('groups.1.accounts.0.balance.formatted', '10.00 EUR')
            ->where('groups.1.accounts.1.name', 'Salary')
            ->where('groups.1.accounts.2.name', 'Closed')
            ->where('groups.1.accounts.2.archived', true)
            ->where('total.currency', 'RSD')
            ->where('total.formatted', '1,330.00 RSD')
            ->missing('groups.2'));
});

test('the euro total is the dinar total divided once', function () {
    $user = User::factory()->displaysIn(Currency::Eur)->create();

    Account::factory()->create(['name' => 'A', 'opening_balance' => '10.00']);
    Account::factory()->create(['name' => 'B', 'opening_balance' => '10.00']);

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.0.accounts.0.balance.formatted', '10.00 RSD')
            ->where('groups.0.accounts.1.balance.formatted', '10.00 RSD')
            ->where('total.currency', 'EUR')
            ->where('total.amount', '0.17')
            ->where('total.formatted', '0.17 EUR'));
});

test('another member sees the same accounts and an outsider does not', function () {
    $member = User::factory()->create();
    $account = Account::factory()->create(['name' => 'Shared cash']);
    $elsewhere = Workspace::factory()->create(['name' => 'Elsewhere']);
    $outsider = User::factory()->create(['workspace_id' => $elsewhere->id]);

    $this->actingAs($member)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('groups.0.accounts.0.name', 'Shared cash'));

    $this->actingAs($outsider)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('groups', []));

    $this->actingAs($outsider)
        ->get(route('accounts.show', $account))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->patch(route('accounts.update', $account), accountPayload())
        ->assertForbidden();

    $this->actingAs($outsider)
        ->delete(route('accounts.destroy', $account))
        ->assertForbidden();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function accountPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Household cash',
        'type' => 'cash',
        'currency' => 'RSD',
        'opening_balance' => '1500.50',
        'opening_balance_on' => '2026-10-01',
        'note' => 'Wallet in the kitchen',
    ], $overrides);
}
