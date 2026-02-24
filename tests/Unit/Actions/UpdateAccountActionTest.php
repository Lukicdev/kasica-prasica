<?php

declare(strict_types=1);

use App\Actions\UpdateAccountAction;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('update account action updates an account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
        'type' => AccountType::Cash,
        'is_shared' => false,
    ]);

    $action = new UpdateAccountAction();

    $data = [
        'name' => 'Updated Name',
        'type' => AccountType::Bank,
        'is_shared' => true,
    ];

    $updatedAccount = $action->handle($account, $data);

    expect($updatedAccount->name)->toBe('Updated Name')
        ->and($updatedAccount->type)->toBe(AccountType::Bank)
        ->and($updatedAccount->is_shared)->toBeTrue();
});

test('update account action can update partial data', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
    ]);

    $action = new UpdateAccountAction();

    $data = [
        'name' => 'Updated Name',
    ];

    $updatedAccount = $action->handle($account, $data);

    expect($updatedAccount->name)->toBe('Updated Name');
});
