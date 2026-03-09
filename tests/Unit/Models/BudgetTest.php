<?php

declare(strict_types=1);

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('budget can be created', function () {
    $user = User::factory()->create();
    $expenseCategory = Category::factory()->expense()->create([
        'user_id' => $user->id,
    ]);

    $budget = Budget::factory()->create([
        'user_id' => $user->id,
        'category_id' => $expenseCategory->id,
        'amount' => 500.50,
        'period' => '2025-03-01',
    ]);

    expect($budget->amount)->toBe('500.50')
        ->and($budget->user_id)->toBe($user->id)
        ->and($budget->category_id)->toBe($expenseCategory->id)
        ->and($budget->period->format('Y-m-d'))->toBe('2025-03-01');
});

test('budget belongs to a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->expense()->create([
        'user_id' => $user->id,
    ]);
    $budget = Budget::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    expect($budget->category)->toBeInstanceOf(Category::class)
        ->and($budget->category->id)->toBe($category->id);
});

test('budget belongs to a user', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->create(['user_id' => $user->id]);

    expect($budget->user)->toBeInstanceOf(User::class)
        ->and($budget->user->id)->toBe($user->id);
});

test('user has many budgets', function () {
    $user = User::factory()->create();
    Budget::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->budgets)->toHaveCount(3);
});

test('budget amount is cast to decimal', function () {
    $budget = Budget::factory()->create(['amount' => 123.45]);

    expect($budget->amount)->toBe('123.45');
});
