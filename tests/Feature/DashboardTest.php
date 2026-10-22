<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('overview/index')
            ->has('summary')
            ->has('flow')
            ->missing('first_run'));
});

test('the dashboard shows whether the user is the workspace admin', function (bool $isAdmin) {
    $user = User::factory()->when($isAdmin, fn ($factory) => $factory->admin())->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.is_admin', $isAdmin)
            ->where('auth.user.workspace.name', Workspace::DEFAULT_NAME));
})->with([
    'admin' => true,
    'member' => false,
]);
