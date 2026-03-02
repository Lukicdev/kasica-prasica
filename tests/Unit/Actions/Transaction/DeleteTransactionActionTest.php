<?php

declare(strict_types=1);

use App\Actions\Transaction\DeleteTransactionAction;
use App\Models\Transaction;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('delete transaction action deletes a transaction', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create(['user_id' => $user->id]);

    $action = new DeleteTransactionAction();

    $action->handle($transaction);

    expect(Transaction::find($transaction->id))->toBeNull();
});
