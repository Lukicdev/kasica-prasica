<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [OverviewController::class, 'index'])->name('dashboard');

    Route::resource('accounts', AccountController::class);
    Route::patch('accounts/{account}/archive', [AccountController::class, 'archive'])->name('accounts.archive');
    Route::patch('accounts/{account}/restore', [AccountController::class, 'restore'])->name('accounts.restore');

    Route::patch('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::patch('categories/{category}/archive', [CategoryController::class, 'archive'])->name('categories.archive');
    Route::patch('categories/{category}/restore', [CategoryController::class, 'restore'])->name('categories.restore');

    Route::resource('transactions', TransactionController::class);

    Route::get('budget', [BudgetController::class, 'index'])->name('budget.index');
    Route::post('budget', [BudgetController::class, 'store'])->name('budget.store');
    Route::put('budget', [BudgetController::class, 'update'])->name('budget.update');
});

require __DIR__.'/settings.php';
