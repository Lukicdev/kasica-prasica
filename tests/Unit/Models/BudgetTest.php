<?php

declare(strict_types=1);

use App\Models\Budget;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('budget can be created', function () {
    $user = User::factory()->create();

    $budget = Budget::factory()->create([
        'user_id' => $user->id,
        'name' => 'Groceries',
        'amount' => 500.50,
        'period_start' => '2025-03-01',
        'period_end' => '2025-03-31',
    ]);

    expect($budget->amount)->toBe('500.50')
        ->and($budget->name)->toBe('Groceries')
        ->and($budget->user_id)->toBe($user->id)
        ->and($budget->period_start->format('Y-m-d'))->toBe('2025-03-01')
        ->and($budget->period_end->format('Y-m-d'))->toBe('2025-03-31');
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
