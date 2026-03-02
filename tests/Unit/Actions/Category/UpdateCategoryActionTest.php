<?php

declare(strict_types=1);

use App\Actions\Category\UpdateCategoryAction;
use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('update category action updates a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
        'type' => CategoryType::Expense,
    ]);

    $action = new UpdateCategoryAction();

    $data = [
        'name' => 'Updated Name',
        'type' => CategoryType::Income,
        'color' => '#FF3357',
    ];

    $updatedCategory = $action->handle($category, $data);

    expect($updatedCategory->name)->toBe('Updated Name')
        ->and($updatedCategory->type)->toBe(CategoryType::Income)
        ->and($updatedCategory->color)->toBe('#FF3357');
});

test('update category action can update partial data', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
    ]);

    $action = new UpdateCategoryAction();

    $data = [
        'name' => 'Updated Name',
    ];

    $updatedCategory = $action->handle($category, $data);

    expect($updatedCategory->name)->toBe('Updated Name');
});

test('update category action can make category shared', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $action = new UpdateCategoryAction();

    $data = [
        'is_shared' => true,
    ];

    $updatedCategory = $action->handle($category, $data);

    expect($updatedCategory->user_id)->toBeNull();
});
