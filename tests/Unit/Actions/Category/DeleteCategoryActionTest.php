<?php

declare(strict_types=1);

use App\Actions\Category\DeleteCategoryAction;
use App\Models\Category;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('delete category action deletes a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $action = new DeleteCategoryAction();

    $action->handle($category);

    expect(Category::find($category->id))->toBeNull();
});
