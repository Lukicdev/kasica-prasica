<?php

declare(strict_types=1);

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('category can be created', function () {
    $user = User::factory()->create();

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Groceries',
        'type' => CategoryType::Expense,
        'color' => '#FF5733',
    ]);

    expect($category->name)->toBe('Groceries')
        ->and($category->type)->toBe(CategoryType::Expense)
        ->and($category->color)->toBe('#FF5733');
});

test('category can be shared', function () {
    $category = Category::factory()->shared()->create();

    expect($category->user_id)->toBeNull();
});

test('category belongs to a user', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    expect($category->user)->toBeInstanceOf(User::class)
        ->and($category->user->id)->toBe($user->id);
});

test('user has many categories', function () {
    $user = User::factory()->create();
    Category::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->categories)->toHaveCount(3);
});

test('category type is cast to enum', function () {
    $category = Category::factory()->create(['type' => CategoryType::Income]);

    expect($category->type)->toBeInstanceOf(CategoryType::class)
        ->and($category->type)->toBe(CategoryType::Income);
});

test('category factory can create income category', function () {
    $category = Category::factory()->income()->create();

    expect($category->type)->toBe(CategoryType::Income);
});

test('category factory can create expense category', function () {
    $category = Category::factory()->expense()->create();

    expect($category->type)->toBe(CategoryType::Expense);
});

test('category factory can create shared category', function () {
    $category = Category::factory()->shared()->create();

    expect($category->user_id)->toBeNull();
});

test('category factory can combine states', function () {
    $category = Category::factory()->income()->shared()->create();

    expect($category->type)->toBe(CategoryType::Income)
        ->and($category->user_id)->toBeNull();
});
