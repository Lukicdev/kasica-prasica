<?php

declare(strict_types=1);

use App\Actions\Transaction\CreateTransactionAction;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('create transaction action creates a transaction', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create(['user_id' => $user->id]);
    $action = new CreateTransactionAction();

    $data = [
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => TransactionType::Income,
        'amount' => 1000.50,
        'description' => 'Salary',
        'transaction_date' => now()->format('Y-m-d'),
    ];

    $transaction = $action->handle($user, $data);

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->user_id)->toBe($user->id)
        ->and($transaction->account_id)->toBe($account->id)
        ->and($transaction->category_id)->toBe($category->id)
        ->and($transaction->type)->toBe(TransactionType::Income)
        ->and($transaction->amount)->toBe('1000.50')
        ->and($transaction->description)->toBe('Salary');
});

test('create transaction action can create transaction without category', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $action = new CreateTransactionAction();

    $data = [
        'account_id' => $account->id,
        'type' => TransactionType::Transfer,
        'amount' => 500.00,
        'transaction_date' => now()->format('Y-m-d'),
    ];

    $transaction = $action->handle($user, $data);

    expect($transaction->category_id)->toBeNull();
});
