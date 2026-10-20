<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\RecurrenceController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [OverviewController::class, 'index'])->name('dashboard');
    Route::get('insights', [InsightsController::class, 'index'])->name('insights.index');

    Route::resource('accounts', AccountController::class);
    Route::get('accounts/{account}/reconcile', [ReconciliationController::class, 'create'])->name('accounts.reconcile');
    Route::post('accounts/{account}/reconcile', [ReconciliationController::class, 'store'])->name('accounts.reconcile.store');
    Route::patch('accounts/{account}/archive', [AccountController::class, 'archive'])->name('accounts.archive');
    Route::patch('accounts/{account}/restore', [AccountController::class, 'restore'])->name('accounts.restore');

    Route::patch('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::patch('categories/{category}/archive', [CategoryController::class, 'archive'])->name('categories.archive');
    Route::patch('categories/{category}/restore', [CategoryController::class, 'restore'])->name('categories.restore');

    Route::resource('transactions', TransactionController::class);

    Route::get('recurrences/{transaction}', [RecurrenceController::class, 'edit'])->name('recurrences.edit');
    Route::post('recurrences/{transaction}', [RecurrenceController::class, 'store'])->name('recurrences.store');

    Route::get('transfers/create', [TransferController::class, 'create'])->name('transfers.create');
    Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('transfers/{transaction}/edit', [TransferController::class, 'edit'])->name('transfers.edit');
    Route::put('transfers/{transaction}', [TransferController::class, 'update'])->name('transfers.update');

    Route::get('adjustments/{transaction}/edit', [ReconciliationController::class, 'edit'])->name('adjustments.edit');
    Route::put('adjustments/{transaction}', [ReconciliationController::class, 'update'])->name('adjustments.update');

    Route::get('budget', [BudgetController::class, 'index'])->name('budget.index');
    Route::post('budget', [BudgetController::class, 'store'])->name('budget.store');
    Route::put('budget', [BudgetController::class, 'update'])->name('budget.update');
});

require __DIR__.'/settings.php';
