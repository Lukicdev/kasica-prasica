<?php

declare(strict_types=1);

use App\Enums\TransactionType;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $expenseByCategory = $request->user()
            ->transactions()
            ->where('transactions.type', TransactionType::Expense)
            ->whereNotNull('transactions.category_id')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, categories.color, sum(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->category_name,
                'total' => (float) $row->total,
                'color' => $row->color ?? '#94a3b8',
            ])
            ->values()
            ->all();

        return Inertia::render('dashboard', [
            'expenseByCategory' => $expenseByCategory,
        ]);
    })->name('dashboard');

    Route::resource('accounts', AccountController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('transactions', TransactionController::class);
});

require __DIR__.'/settings.php';
