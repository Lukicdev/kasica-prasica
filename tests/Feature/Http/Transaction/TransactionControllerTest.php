<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests cannot access transactions index', function () {
    $this->get(route('transactions.index'))->assertRedirect(route('login'));
});

test('authenticated users can view transactions index', function () {
    $user = User::factory()->create();
    Transaction::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('transactions.index'));

    $response->assertOk();
});

test('authenticated users can view create transaction page', function () {
    $user = User::factory()->create();
    Account::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('transactions.create'));

    $response->assertOk();
});

test('authenticated users can create an income transaction', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->income()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('transactions.store'), [
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => TransactionType::Income->value,
        'amount' => 1000.50,
        'description' => 'Salary',
        'transaction_date' => now()->format('Y-m-d'),
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('transactions.index'));

    expect(Transaction::where('description', 'Salary')->exists())->toBeTrue();
});

test('authenticated users can create an expense transaction', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->expense()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('transactions.store'), [
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => TransactionType::Expense->value,
        'amount' => 50.25,
        'description' => 'Groceries',
        'transaction_date' => now()->format('Y-m-d'),
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('transactions.index'));

    expect(Transaction::where('description', 'Groceries')->exists())->toBeTrue();
});

test('transaction creation requires valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('transactions.store'), []);

    $response->assertSessionHasErrors(['account_id', 'type', 'amount', 'transaction_date']);
});

test('authenticated users can view a transaction', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('transactions.show', $transaction));

    $response->assertOk();
});

test('authenticated users can view edit transaction page', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('transactions.edit', $transaction));

    $response->assertOk();
});

test('authenticated users can update a transaction', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
        'amount' => 2000.00,
        'description' => 'Updated Description',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('transactions.index'));

    $transaction->refresh();

    expect($transaction->amount)->toBe('2000.00')
        ->and($transaction->description)->toBe('Updated Description');
});

test('authenticated users can delete a transaction', function () {
    $user = User::factory()->create();
    $transaction = Transaction::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

    $response->assertSessionHasNoErrors()->assertRedirect(route('transactions.index'));

    expect(Transaction::find($transaction->id))->toBeNull();
});
