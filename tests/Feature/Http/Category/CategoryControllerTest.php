<?php

declare(strict_types=1);

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests cannot access categories index', function () {
    $this->get(route('categories.index'))->assertRedirect(route('login'));
});

test('authenticated users can view categories index', function () {
    $user = User::factory()->create();
    Category::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('categories.index'));

    $response->assertOk();
});

test('authenticated users can view shared categories', function () {
    $user = User::factory()->create();
    Category::factory()->shared()->create();

    $response = $this->actingAs($user)->get(route('categories.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('categories'));
});

test('authenticated users can view create category page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('categories.create'));

    $response->assertOk();
});

test('authenticated users can create a category', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('categories.store'), [
        'name' => 'Groceries',
        'type' => CategoryType::Expense->value,
        'color' => '#FF5733',
        'is_shared' => false,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('categories.index'));

    expect(Category::where('name', 'Groceries')->exists())->toBeTrue();
});

test('authenticated users can create a shared category', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('categories.store'), [
        'name' => 'Shared Category',
        'type' => CategoryType::Income->value,
        'color' => '#33FF57',
        'is_shared' => true,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('categories.index'));

    $category = Category::where('name', 'Shared Category')->first();
    expect($category->user_id)->toBeNull();
});

test('category creation requires valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('categories.store'), []);

    $response->assertSessionHasErrors(['name', 'type', 'color']);
});

test('authenticated users can view a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('categories.show', $category));

    $response->assertOk();
});

test('authenticated users can view edit category page', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('categories.edit', $category));

    $response->assertOk();
});

test('authenticated users can update a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->put(route('categories.update', $category), [
        'name' => 'Updated Category',
        'type' => CategoryType::Income->value,
        'color' => '#3357FF',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('categories.index'));

    $category->refresh();

    expect($category->name)->toBe('Updated Category')
        ->and($category->type)->toBe(CategoryType::Income);
});

test('authenticated users can delete a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

    $response->assertSessionHasNoErrors()->assertRedirect(route('categories.index'));

    expect(Category::find($category->id))->toBeNull();
});
