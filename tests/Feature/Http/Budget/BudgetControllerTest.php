<?php

declare(strict_types=1);

use App\Models\Budget;
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
});

test('authenticated users can create a budget', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('budgets.store'), [
        'name' => 'Monthly groceries',
        'amount' => 500.50,
        'period_start' => '2025-03-01',
        'period_end' => '2025-03-31',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('budgets.index'));

    $budget = Budget::where('user_id', $user->id)->where('name', 'Monthly groceries')->first();
    expect($budget)->not->toBeNull()
        ->and($budget->amount)->toBe('500.50')
        ->and($budget->name)->toBe('Monthly groceries')
        ->and($budget->period_start->format('Y-m-d'))->toBe('2025-03-01')
        ->and($budget->period_end->format('Y-m-d'))->toBe('2025-03-31');
});

test('budget creation requires valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('budgets.store'), []);

    $response->assertSessionHasErrors(['name', 'amount', 'period_start', 'period_end']);
});

test('budget period_end must be on or after period_start', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('budgets.store'), [
        'name' => 'Monthly groceries',
        'amount' => 500,
        'period_start' => '2025-03-31',
        'period_end' => '2025-03-01',
    ]);

    $response->assertSessionHasErrors(['period_end']);
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
});

test('authenticated users can update their own budget', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->put(route('budgets.update', $budget), [
        'name' => 'Updated budget',
        'amount' => 750.00,
        'period_start' => '2025-04-01',
        'period_end' => '2025-04-30',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('budgets.index'));

    $budget->refresh();
    expect($budget->amount)->toBe('750.00')
        ->and($budget->name)->toBe('Updated budget')
        ->and($budget->period_start->format('Y-m-d'))->toBe('2025-04-01')
        ->and($budget->period_end->format('Y-m-d'))->toBe('2025-04-30');
});

test('authenticated users can delete their own budget', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('budgets.destroy', $budget));

    $response->assertSessionHasNoErrors()->assertRedirect(route('budgets.index'));

    expect(Budget::find($budget->id))->toBeNull();
});
