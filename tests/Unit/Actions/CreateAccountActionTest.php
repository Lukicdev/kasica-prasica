<?php

declare(strict_types=1);

use App\Actions\CreateAccountAction;
use App\Enums\AccountType;
use App\Enums\Currency;
use App\Models\Account;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('create account action creates an account', function () {
    $user = User::factory()->create();
    $action = new CreateAccountAction();

    $data = [
        'name' => 'My Cash Account',
        'type' => AccountType::Cash,
        'currency' => Currency::EUR,
        'initial_balance' => 1000.50,
        'is_shared' => false,
    ];

    $account = $action->handle($user, $data);

    expect($account)->toBeInstanceOf(Account::class)
        ->and($account->name)->toBe('My Cash Account')
        ->and($account->type)->toBe(AccountType::Cash)
        ->and($account->currency)->toBe(Currency::EUR)
        ->and($account->initial_balance)->toBe('1000.50')
        ->and($account->is_shared)->toBeFalse()
        ->and($account->user_id)->toBe($user->id);
});

test('create account action defaults is shared to false', function () {
    $user = User::factory()->create();
    $action = new CreateAccountAction();

    $data = [
        'name' => 'My Account',
        'type' => AccountType::Bank,
        'currency' => Currency::EUR,
        'initial_balance' => 500,
    ];

    $account = $action->handle($user, $data);

    expect($account->is_shared)->toBeFalse();
});

test('create account action can create shared account', function () {
    $user = User::factory()->create();
    $action = new CreateAccountAction();

    $data = [
        'name' => 'Shared Account',
        'type' => AccountType::Bank,
        'currency' => Currency::EUR,
        'initial_balance' => 2000,
        'is_shared' => true,
    ];

    $account = $action->handle($user, $data);

    expect($account->is_shared)->toBeTrue();
});
