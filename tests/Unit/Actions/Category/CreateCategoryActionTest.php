<?php

declare(strict_types=1);

use App\Actions\Category\CreateCategoryAction;
use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('create category action creates a category', function () {
    $user = User::factory()->create();
    $action = new CreateCategoryAction();

    $data = [
        'name' => 'Groceries',
        'type' => CategoryType::Expense,
        'color' => '#FF5733',
        'is_shared' => false,
    ];

    $category = $action->handle($user, $data);

    expect($category)->toBeInstanceOf(Category::class)
        ->and($category->name)->toBe('Groceries')
        ->and($category->type)->toBe(CategoryType::Expense)
        ->and($category->color)->toBe('#FF5733')
        ->and($category->user_id)->toBe($user->id);
});

test('create category action can create shared category', function () {
    $user = User::factory()->create();
    $action = new CreateCategoryAction();

    $data = [
        'name' => 'Shared Category',
        'type' => CategoryType::Income,
        'color' => '#33FF57',
        'is_shared' => true,
    ];

    $category = $action->handle($user, $data);

    expect($category->user_id)->toBeNull();
});

test('create category action defaults is shared to false', function () {
    $user = User::factory()->create();
    $action = new CreateCategoryAction();

    $data = [
        'name' => 'My Category',
        'type' => CategoryType::Expense,
        'color' => '#3357FF',
    ];

    $category = $action->handle($user, $data);

    expect($category->user_id)->toBe($user->id);
});
