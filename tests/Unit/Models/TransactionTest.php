<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('transaction can be created', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create(['user_id' => $user->id]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => TransactionType::Income,
        'amount' => 1000.50,
        'description' => 'Salary',
        'transaction_date' => now(),
    ]);

    expect($transaction->type)->toBe(TransactionType::Income)
        ->and($transaction->amount)->toBe('1000.50')
        ->and($transaction->description)->toBe('Salary');
});

test('transaction belongs to a user', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create(['user_id' => $user->id]);

    expect($transaction->user)->toBeInstanceOf(User::class)
        ->and($transaction->user->id)->toBe($user->id);
});

test('transaction belongs to an account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
    ]);

    expect($transaction->account)->toBeInstanceOf(Account::class)
        ->and($transaction->account->id)->toBe($account->id);
});

test('transaction belongs to a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    expect($transaction->category)->toBeInstanceOf(Category::class)
        ->and($transaction->category->id)->toBe($category->id);
});

test('transaction can have null category', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => null,
    ]);

    expect($transaction->category)->toBeNull();
});

test('transaction type is cast to enum', function () {
    $transaction = Transaction::factory()->create(['type' => TransactionType::Expense]);

    expect($transaction->type)->toBeInstanceOf(TransactionType::class)
        ->and($transaction->type)->toBe(TransactionType::Expense);
});

test('transaction amount is cast to decimal', function () {
    $transaction = Transaction::factory()->create(['amount' => 1234.56]);

    expect($transaction->amount)->toBe('1234.56');
});

test('transaction date is cast to date', function () {
    $date = now();
    $transaction = Transaction::factory()->create(['transaction_date' => $date]);

    expect($transaction->transaction_date->format('Y-m-d'))->toBe($date->format('Y-m-d'));
});

test('transaction factory can create income transaction', function () {
    $transaction = Transaction::factory()->income()->create();

    expect($transaction->type)->toBe(TransactionType::Income);
});

test('transaction factory can create expense transaction', function () {
    $transaction = Transaction::factory()->expense()->create();

    expect($transaction->type)->toBe(TransactionType::Expense);
});

test('transaction factory can create transfer transaction', function () {
    $transaction = Transaction::factory()->transfer()->create();

    expect($transaction->type)->toBe(TransactionType::Transfer)
        ->and($transaction->category_id)->toBeNull();
});

test('user has many transactions', function () {
    $user = User::factory()->create();
    Transaction::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->transactions)->toHaveCount(3);
});

test('account has many transactions', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    Transaction::factory()->count(3)->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
    ]);

    expect($account->transactions)->toHaveCount(3);
});

test('category has many transactions', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    Transaction::factory()->count(3)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    expect($category->transactions)->toHaveCount(3);
});
