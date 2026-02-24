<?php

declare(strict_types=1);

use App\Enums\AccountType;
use App\Enums\Currency;
use App\Models\Account;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('account can be created', function () {
    $user = User::factory()->create();

    $account = Account::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Cash Account',
        'type' => AccountType::Cash,
        'currency' => Currency::EUR,
        'initial_balance' => 1000.50,
        'is_shared' => false,
    ]);

    expect($account->name)->toBe('My Cash Account')
        ->and($account->type)->toBe(AccountType::Cash)
        ->and($account->currency)->toBe(Currency::EUR)
        ->and($account->initial_balance)->toBe('1000.50')
        ->and($account->is_shared)->toBeFalse();
});

test('account belongs to a user', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);

    expect($account->user)->toBeInstanceOf(User::class)
        ->and($account->user->id)->toBe($user->id);
});

test('user has many accounts', function () {
    $user = User::factory()->create();
    Account::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->accounts)->toHaveCount(3);
});

test('account type is cast to enum', function () {
    $account = Account::factory()->create(['type' => AccountType::Bank]);

    expect($account->type)->toBeInstanceOf(AccountType::class)
        ->and($account->type)->toBe(AccountType::Bank);
});

test('account currency is cast to enum', function () {
    $account = Account::factory()->create(['currency' => Currency::USD]);

    expect($account->currency)->toBeInstanceOf(Currency::class)
        ->and($account->currency)->toBe(Currency::USD);
});

test('account initial balance is cast to decimal', function () {
    $account = Account::factory()->create(['initial_balance' => 1234.56]);

    expect($account->initial_balance)->toBe('1234.56');
});

test('account is shared is cast to boolean', function () {
    $account = Account::factory()->create(['is_shared' => true]);

    expect($account->is_shared)->toBeTrue();
});

test('account factory can create cash account', function () {
    $account = Account::factory()->cash()->create();

    expect($account->type)->toBe(AccountType::Cash);
});

test('account factory can create bank account', function () {
    $account = Account::factory()->bank()->create();

    expect($account->type)->toBe(AccountType::Bank);
});

test('account factory can create shared account', function () {
    $account = Account::factory()->shared()->create();

    expect($account->is_shared)->toBeTrue();
});

test('account factory can combine states', function () {
    $account = Account::factory()->cash()->shared()->create();

    expect($account->type)->toBe(AccountType::Cash)
        ->and($account->is_shared)->toBeTrue();
});

test('account has default currency of EUR', function () {
    $account = Account::factory()->create();

    expect($account->currency)->toBe(Currency::EUR);
});

test('account has default initial balance of zero', function () {
    $account = Account::factory()->create();

    expect($account->initial_balance)->toBe('0.00');
});

test('account has default is shared of false', function () {
    $account = Account::factory()->create();

    expect($account->is_shared)->toBeFalse();
});
