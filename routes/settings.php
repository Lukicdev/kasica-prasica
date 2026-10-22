<?php

use App\Http\Controllers\Settings\CurrencyController;
use App\Http\Controllers\Settings\DisplayCurrencyController;
use App\Http\Controllers\Settings\ExchangeRateController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::patch('settings/display-currency', DisplayCurrencyController::class)
        ->name('display-currency.update');

    Route::get('settings/currencies', [CurrencyController::class, 'edit'])->name('currencies.edit');
    Route::post('settings/currencies', [CurrencyController::class, 'store'])->name('currencies.store');
    Route::patch('settings/currencies/{code}', [CurrencyController::class, 'update'])
        ->where('code', '[A-Za-z]{3}')
        ->name('currencies.update');
    Route::delete('settings/currencies/{code}', [CurrencyController::class, 'destroy'])
        ->where('code', '[A-Za-z]{3}')
        ->name('currencies.destroy');
    Route::patch('settings/base-currency', [CurrencyController::class, 'updateBase'])->name('base-currency.update');
    Route::post('settings/exchange-rates', ExchangeRateController::class)->name('exchange-rates.store');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
