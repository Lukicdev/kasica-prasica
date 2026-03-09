<?php

declare(strict_types=1);

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests cannot access budgets index', function () {
    $this->get(route('budgets.index'))->assertRedirect(route('login'));
});

test('authenticated users can view budgets index', function () {
    $user = User::factory()->create();
    Budget::factory()->count(2)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('budgets.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('budgets'));
});

test('authenticated users can view create budget page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('budgets.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('expenseCategories'));
});

test('authenticated users can create a budget', function () {
    $user = User::factory()->create();
    $expenseCategory = Category::factory()->expense()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('budgets.store'), [
        'amount' => 500.50,
        'period' => '2025-03-01',
        'category_id' => $expenseCategory->id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('budgets.index'));

    $budget = Budget::where('user_id', $user->id)
        ->where('category_id', $expenseCategory->id)
        ->whereDate('period', '2025-03-01')
        ->first();
    expect($budget)->not->toBeNull()
        ->and($budget->amount)->toBe('500.50')
        ->and($budget->category_id)->toBe($expenseCategory->id)
        ->and($budget->period->format('Y-m-d'))->toBe('2025-03-01');
});

test('budget creation requires valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('budgets.store'), []);

    $response->assertSessionHasErrors([
        'amount',
        'period',
        'category_id',
    ]);
});

test('cannot create duplicate budget for same category and month', function () {
    $user = User::factory()->create();
    $expenseCategory = Category::factory()->expense()->create([
        'user_id' => $user->id,
    ]);
    Budget::factory()->create([
        'user_id' => $user->id,
        'category_id' => $expenseCategory->id,
        'period' => '2025-03-01',
    ]);

    $response = $this->actingAs($user)->post(route('budgets.store'), [
        'amount' => 300,
        'period' => '2025-03-01',
        'category_id' => $expenseCategory->id,
    ]);

    $response->assertSessionHasErrors(['period']);
});

test('budget can only use expense categories', function () {
    $user = User::factory()->create();
    $incomeCategory = Category::factory()->income()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('budgets.store'), [
        'amount' => 500,
        'period' => '2025-03-01',
        'category_id' => $incomeCategory->id,
    ]);

    $response->assertSessionHasErrors(['category_id']);
});

test('authenticated users can view their own budget', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('budgets.show', $budget));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('budget'));
});

test('authenticated users can view edit budget page', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('budgets.edit', $budget));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('expenseCategories'));
});

test('authenticated users can update their own budget', function () {
    $user = User::factory()->create();
    $expenseCategory = Category::factory()->expense()->create([
        'user_id' => $user->id,
    ]);
    $budget = Budget::factory()->create([
        'user_id' => $user->id,
        'category_id' => $expenseCategory->id,
        'period' => '2025-03-01',
    ]);
    $otherExpenseCategory = Category::factory()->expense()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->put(route('budgets.update', $budget), [
        'amount' => 750.00,
        'period' => '2025-04-01',
        'category_id' => $otherExpenseCategory->id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('budgets.index'));

    $budget->refresh();
    expect($budget->amount)->toBe('750.00')
        ->and($budget->category_id)->toBe($otherExpenseCategory->id)
        ->and($budget->period->format('Y-m-d'))->toBe('2025-04-01');
});

test('authenticated users can delete their own budget', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('budgets.destroy', $budget));

    $response->assertSessionHasNoErrors()->assertRedirect(route('budgets.index'));

    expect(Budget::find($budget->id))->toBeNull();
});
