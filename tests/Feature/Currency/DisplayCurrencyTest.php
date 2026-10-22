<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a new user shows figures in dinars', function () {
    $user = User::factory()->create();

    expect($user->display_currency)->toBe('RSD');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.display_currency', 'RSD')
            ->where('currencies.base', 'RSD')
            ->where('currencies.supported', [
                ['code' => 'RSD', 'name' => 'Serbian Dinar', 'rateToBase' => '1'],
                ['code' => 'EUR', 'name' => 'Euro', 'rateToBase' => '117.5'],
            ]));
});

test('a user can remember euros as the display currency', function () {
    $user = User::factory()->create(['name' => 'Ada']);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->patch(route('display-currency.update'), [
            'currency' => 'EUR',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->display_currency)->toBe('EUR')
        ->and($user->name)->toBe('Ada')
        ->and($user->is_admin)->toBeFalse();
});

test('a member can switch back to dinars', function () {
    $user = User::factory()->displaysIn('EUR')->create();

    $this->actingAs($user)
        ->patch(route('display-currency.update'), [
            'currency' => 'RSD',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->display_currency)->toBe('RSD');
});

test('each user keeps their own display currency', function () {
    $ada = User::factory()->create();
    $bea = User::factory()->create();

    $this->actingAs($ada)
        ->patch(route('display-currency.update'), [
            'currency' => 'EUR',
        ])
        ->assertSessionHasNoErrors();

    expect($ada->refresh()->display_currency)->toBe('EUR')
        ->and($bea->refresh()->display_currency)->toBe('RSD');
});

test('an unknown currency is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->patch(route('display-currency.update'), [
            'currency' => 'USD',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('currency');

    expect($user->refresh()->display_currency)->toBe('RSD');
});

test('profile updates cannot change the display currency', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Renamed',
            'email' => $user->email,
            'display_currency' => 'EUR',
        ])
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->display_currency)->toBe('RSD')
        ->and($user->name)->toBe('Renamed');
});

test('guests cannot change the display currency', function () {
    $this->patch(route('display-currency.update'), [
        'currency' => 'EUR',
    ])->assertRedirect(route('login'));
});

test('unverified users cannot change the display currency', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->patch(route('display-currency.update'), [
            'currency' => 'EUR',
        ])
        ->assertRedirect(route('verification.notice'));

    expect($user->refresh()->display_currency)->toBe('RSD');
});
