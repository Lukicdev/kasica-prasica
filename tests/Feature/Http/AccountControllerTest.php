<?php

declare(strict_types=1);

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests cannot access accounts index', function () {
    $this->get(route('accounts.index'))->assertRedirect(route('login'));
});

test('authenticated users can view accounts index', function () {
    $user = User::factory()->create();
    Account::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('accounts.index'));

    $response->assertOk();
});

test('authenticated users can view create account page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('accounts.create'));

    $response->assertOk();
});

test('authenticated users can create an account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('accounts.store'), [
        'name' => 'My Cash Account',
        'type' => AccountType::Cash->value,
        'currency' => 'EUR',
        'initial_balance' => 1000.50,
        'is_shared' => false,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('accounts.index'));

    expect(Account::where('name', 'My Cash Account')->exists())->toBeTrue();
});

test('account creation requires valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('accounts.store'), []);

    $response->assertSessionHasErrors(['name', 'type', 'currency', 'initial_balance']);
});

test('authenticated users can view an account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('accounts.show', $account));

    $response->assertOk();
});

test('authenticated users can view edit account page', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('accounts.edit', $account));

    $response->assertOk();
});

test('authenticated users can update an account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->put(route('accounts.update', $account), [
        'name' => 'Updated Account Name',
        'type' => AccountType::Bank->value,
        'currency' => 'EUR',
        'initial_balance' => 2000.00,
        'is_shared' => true,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('accounts.index'));

    $account->refresh();

    expect($account->name)->toBe('Updated Account Name')
        ->and($account->type)->toBe(AccountType::Bank)
        ->and($account->is_shared)->toBeTrue();
});

test('authenticated users can delete an account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('accounts.destroy', $account));

    $response->assertSessionHasNoErrors()->assertRedirect(route('accounts.index'));

    expect(Account::find($account->id))->toBeNull();
});
