<?php

use App\Actions\Demo\SeedHouseholdHistory;
use App\Enums\CategoryKind;
use App\Enums\TransactionType;
use App\Models\BudgetMonth;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\Transaction;
use App\Models\User;
use App\Support\DefaultCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('the database seeder fills four months for two people', function () {
    $this->travelTo('2026-10-22');

    $this->seed();

    $users = User::query()->orderBy('email')->get();

    expect($users)->toHaveCount(2)
        ->and($users->pluck('email')->all())->toBe([
            'partner@example.com',
            'test@example.com',
        ])
        ->and($users->pluck('workspace_id')->unique())->toHaveCount(1)
        ->and(Transaction::query()->count())->toBe(102)
        ->and(Category::query()->count())->toBe(
            count(DefaultCategories::forKind(CategoryKind::Income)) + count(DefaultCategories::forKind(CategoryKind::Expense)),
        );

    expect(Carbon::parse(Transaction::query()->min('booked_on'))->toDateString())->toBe('2026-07-01')
        ->and(Carbon::parse(Transaction::query()->max('booked_on'))->toDateString())->toBe('2026-10-22');

    $incomes = Transaction::query()
        ->where('type', TransactionType::Income)
        ->get()
        ->groupBy(fn (Transaction $transaction): string => $transaction->booked_on->format('Y-m'))
        ->sortKeys()
        ->map->count()
        ->all();

    expect($incomes)->toBe([
        '2026-07' => 2,
        '2026-08' => 3,
        '2026-09' => 2,
        '2026-10' => 3,
    ]);

    $expenseCategories = count(DefaultCategories::forKind(CategoryKind::Expense));

    expect(BudgetMonth::query()->orderBy('month')->pluck('month')->all())->toBe([
        '2026-07',
        '2026-08',
        '2026-09',
        '2026-10',
    ])->and(CategoryBudget::query()->count())->toBe(4 * $expenseCategories);

    $mortgage = Category::query()->where('name', 'Mortgage')->firstOrFail();

    expect(CategoryBudget::query()->where('category_id', $mortgage->id)->pluck('amount')->unique()->values()->all())
        ->toBe(['75700.00']);
});

test('seeding early in the month keeps every income inside that month', function () {
    $this->travelTo('2026-10-04');

    $first = User::factory()->admin()->create();
    $second = User::factory()->create([
        'workspace_id' => $first->workspace_id,
    ]);

    app(SeedHouseholdHistory::class)->handle($first, $second);

    expect(Transaction::query()->whereDate('booked_on', '>', '2026-10-04')->exists())->toBeFalse();

    $octoberIncomes = Transaction::query()
        ->where('type', TransactionType::Income)
        ->whereDate('booked_on', '>=', '2026-10-01')
        ->whereDate('booked_on', '<=', '2026-10-04')
        ->count();

    expect($octoberIncomes)->toBe(3);
});
