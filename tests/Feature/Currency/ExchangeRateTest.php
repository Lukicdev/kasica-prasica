<?php

use App\Models\ExchangeRateQuote;
use App\Models\User;
use App\Models\WorkspaceCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('daily rates from frankfurter are stored', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.frankfurter.dev/*' => Http::response([
            ['date' => '2026-10-22', 'base' => 'EUR', 'quote' => 'USD', 'rate' => 1.1241],
            ['date' => '2026-10-22', 'base' => 'EUR', 'quote' => 'EUR', 'rate' => 1],
        ]),
    ]);

    $this->artisan('exchange-rates:fetch')->assertSuccessful();

    expect(ExchangeRateQuote::query()->where('currency', 'USD')->where('quoted_on', '2026-10-22')->exists())->toBeTrue()
        ->and(ExchangeRateQuote::query()->where('currency', 'EUR')->exists())->toBeFalse();
});

test('a failed rate download leaves stored quotes unchanged', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.frankfurter.dev/*' => Http::response(status: 500),
    ]);

    ExchangeRateQuote::query()->create([
        'quoted_on' => '2026-10-21',
        'currency' => 'USD',
        'per_euro' => '1.10000000',
    ]);

    $this->artisan('exchange-rates:fetch')->assertFailed();

    expect(ExchangeRateQuote::query()->count())->toBe(1)
        ->and(ExchangeRateQuote::query()->where('currency', 'USD')->value('per_euro'))->toBe('1.10000000');
});

test('currency settings keep the shared list used by the rest of the app', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('currencies.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/currencies')
            ->where('currencies.base', 'RSD')
            ->where('currencies.supported.0.code', 'RSD')
            ->where('currencySettings.base', 'RSD')
            ->where('currencySettings.locked', false));
});

test('a household can add a currency with its own rate', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('currencies.store'), [
            'code' => 'USD',
            'manual_rate' => '108.5',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $currency = WorkspaceCurrency::query()->where('code', 'USD')->first();

    expect($currency)->not->toBeNull()
        ->and($currency->manual_rate)->not->toBeNull();

    $this->actingAs($user)
        ->delete(route('currencies.destroy', 'USD'))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(WorkspaceCurrency::query()->where('code', 'USD')->exists())->toBeFalse();
});
