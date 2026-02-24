<?php

declare(strict_types=1);

use App\Actions\DeleteAccountAction;
use App\Models\Account;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('delete account action deletes an account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);

    $action = new DeleteAccountAction();

    $action->handle($account);

    expect(Account::find($account->id))->toBeNull();
});
