<?php

declare(strict_types=1);

use App\Actions\Transaction\UpdateTransactionAction;
use App\Models\Transaction;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('update transaction action updates a transaction', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => 100.00,
        'description' => 'Original Description',
    ]);

    $action = new UpdateTransactionAction();

    $data = [
        'amount' => 200.00,
        'description' => 'Updated Description',
    ];

    $updatedTransaction = $action->handle($transaction, $data);

    expect($updatedTransaction->amount)->toBe('200.00')
        ->and($updatedTransaction->description)->toBe('Updated Description');
});

test('update transaction action can update partial data', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'description' => 'Original Description',
    ]);

    $action = new UpdateTransactionAction();

    $data = [
        'description' => 'Updated Description',
    ];

    $updatedTransaction = $action->handle($transaction, $data);

    expect($updatedTransaction->description)->toBe('Updated Description');
});
