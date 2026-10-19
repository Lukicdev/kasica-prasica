<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('the first registered user becomes the admin of the shared workspace', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Ada Admin',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $user = User::query()->with('workspace')->where('email', 'ada@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->is_admin)->toBeTrue()
        ->and($user->workspace->name)->toBe(Workspace::DEFAULT_NAME)
        ->and(Workspace::query()->count())->toBe(1);
});

test('later registered users join the same workspace without becoming admin', function () {
    $this->post(route('register.store'), [
        'name' => 'Ada Admin',
        'email' => 'ada@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->post(route('logout'));

    $this->post(route('register.store'), [
        'name' => 'Bea Member',
        'email' => 'bea@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $admin = User::query()->where('email', 'ada@example.com')->first();
    $member = User::query()->where('email', 'bea@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($member)->not->toBeNull()
        ->and($admin->is_admin)->toBeTrue()
        ->and($member->is_admin)->toBeFalse()
        ->and($member->workspace_id)->toBe($admin->workspace_id)
        ->and(Workspace::query()->count())->toBe(1);
});

test('invalid registration does not create a workspace', function () {
    $this->post(route('register.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'different',
    ]);

    $this->assertGuest();
    expect(User::query()->exists())->toBeFalse()
        ->and(Workspace::query()->exists())->toBeFalse();
});

test('profile updates cannot grant admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Renamed',
            'email' => $user->email,
            'is_admin' => true,
        ])
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->is_admin)->toBeFalse()
        ->and($user->name)->toBe('Renamed');
});
