<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('accounts', AccountController::class);
    Route::patch('accounts/{account}/archive', [AccountController::class, 'archive'])->name('accounts.archive');
    Route::patch('accounts/{account}/restore', [AccountController::class, 'restore'])->name('accounts.restore');
});

require __DIR__.'/settings.php';
