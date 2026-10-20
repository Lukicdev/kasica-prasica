<?php

use App\Actions\Budgets\UpdateCategoryBudget;
use App\Enums\CategoryKind;
use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\BudgetMonth;
use App\Models\Category;
use App\Models\CategoryBudget;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CalendarMonth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests cannot view or change the budget', function () {
    $this->get(route('budget.index'))->assertRedirect(route('login'));
    $this->post(route('budget.store'), ['month' => '2026-10', 'copy' => false])->assertRedirect(route('login'));
    $this->put(route('budget.update'), [
        'month' => '2026-10',
        'category_id' => 1,
        'amount' => '10',
    ])->assertRedirect(route('login'));
});

test('unverified users cannot view or change the budget', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('budget.index'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->post(route('budget.store'), ['month' => '2026-10', 'copy' => false])
        ->assertRedirect(route('verification.notice'));
});

test('the budget opens on the current month and lists every expense category', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => 'nope']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('budget/index')
            ->where('month.value', '2026-10')
            ->where('month.label', 'October 2026')
            ->where('month.previous', '2026-09')
            ->where('month.next', '2026-11')
            ->where('started', false)
            ->where('editable', true)
            ->where('copy_from', null)
            ->where('income.base_amount', '0.00')
            ->where('income.formatted', '0.00 RSD')
            ->where('budget.base_amount', '0.00')
            ->where('unallocated.base_amount', '0.00')
            ->where('categories', function ($categories) {
                $names = collect($categories)->pluck('name');
                $mobile = collect($categories)->firstWhere('name', 'A1 (mobile provider)');

                return count($categories) === 14
                    && $names->contains('Mortgage')
                    && $names->doesntContain('Salary')
                    && $names->doesntContain('Bonus')
                    && $mobile['description'] === 'Mobile provider'
                    && $mobile['amount'] === '0.00'
                    && collect($categories)->every(fn ($category) => $category['amount'] === '0.00' && $category['archived'] === false);
            }));
});

test('a chosen month is kept in the page address', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('month.value', '2026-09')
            ->where('month.label', 'September 2026'));
});

test('a month can be started empty and each expense budget is edited in dinars', function () {
    $user = User::factory()->create(['name' => 'Ada']);
    $mortgage = Category::query()->where('name', 'Mortgage')->first();

    $this->actingAs($user)
        ->post(route('budget.store'), [
            'month' => '2026-10',
            'copy' => false,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('budget.index', ['month' => '2026-10']));

    expect(BudgetMonth::query()->where('month', '2026-10')->where('created_by', $user->id)->exists())->toBeTrue()
        ->and(CategoryBudget::query()->count())->toBe(0);

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $mortgage->id,
            'amount' => '75700.5',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('budget.index', ['month' => '2026-10']));

    $line = CategoryBudget::query()->where('category_id', $mortgage->id)->first();

    expect($line)->not->toBeNull()
        ->and($line->amount)->toBe('75700.50')
        ->and($line->created_by)->toBe($user->id)
        ->and($line->updated_by)->toBe($user->id);

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('started', true)
            ->where('editable', true)
            ->where('budget.base_amount', '75700.50')
            ->where('budget.formatted', '75,700.50 RSD')
            ->where('categories', fn ($categories) => collect($categories)->firstWhere('name', 'Mortgage')['amount'] === '75700.50'
                && collect($categories)->firstWhere('name', 'Mortgage')['icon'] === 'house'
                && collect($categories)->firstWhere('name', 'Mortgage')['updated_by'] === null
                && collect($categories)->firstWhere('name', 'Groceries')['amount'] === '0.00'
                && collect($categories)->firstWhere('name', 'Groceries')['icon'] === 'shopping-cart'));
});

test('a budget cannot be edited until the month is started or with a bad amount', function () {
    $user = User::factory()->create();
    $mortgage = Category::query()->where('name', 'Mortgage')->first();
    $salary = Category::query()->where('name', 'Salary')->first();

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $mortgage->id,
            'amount' => '10',
        ])
        ->assertSessionHasErrors('month');

    $this->actingAs($user)
        ->post(route('budget.store'), ['month' => '2026-10', 'copy' => false])
        ->assertSessionHasNoErrors();

    foreach ([
        ['amount' => '-5', 'error' => 'amount'],
        ['amount' => '1.234', 'error' => 'amount'],
        ['amount' => '', 'error' => 'amount'],
        ['amount' => '10', 'error' => 'category_id', 'category_id' => $salary->id],
    ] as $attempt) {
        $this->actingAs($user)
            ->from(route('budget.index', ['month' => '2026-10']))
            ->put(route('budget.update'), [
                'month' => '2026-10',
                'category_id' => $attempt['category_id'] ?? $mortgage->id,
                'amount' => $attempt['amount'],
            ])
            ->assertSessionHasErrors($attempt['error']);
    }

    expect(CategoryBudget::query()->count())->toBe(0);
});

test('the euro view shows converted budgets and refuses edits', function () {
    $user = User::factory()->displaysIn(Currency::Eur)->create();
    $groceries = Category::query()->where('name', 'Groceries')->first();
    $dining = Category::query()->where('name', 'Dining Out')->first();

    $this->actingAs($user)
        ->post(route('budget.store'), ['month' => '2026-10', 'copy' => false]);

    app(UpdateCategoryBudget::class)->handle($user, CalendarMonth::fromValue('2026-10'), $groceries, '50');
    app(UpdateCategoryBudget::class)->handle($user, CalendarMonth::fromValue('2026-10'), $dining, '50');

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('editable', false)
            ->where('budget.amount', '0.85')
            ->where('budget.currency', 'EUR')
            ->where('budget.formatted', '0.85 EUR')
            ->where('budget.base_amount', '100.00')
            ->where('categories', function ($categories) {
                $groceries = collect($categories)->firstWhere('name', 'Groceries');

                return $groceries['amount'] === '50.00'
                    && $groceries['display']['amount'] === '0.43'
                    && $groceries['display']['formatted'] === '0.43 EUR'
                    && $groceries['display']['original_formatted'] === '50.00 RSD';
            }));

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $groceries->id,
            'amount' => '80',
        ])
        ->assertSessionHasErrors('amount');

    expect(CategoryBudget::query()->where('category_id', $groceries->id)->first()->amount)->toBe('50.00');
});

test('opening a month offers a copy of the previous month and can still start empty', function () {
    $user = User::factory()->create();
    $mortgage = Category::query()->where('name', 'Mortgage')->first();
    $groceries = Category::query()->where('name', 'Groceries')->first();

    $this->actingAs($user)->post(route('budget.store'), [
        'month' => '2026-09',
        'copy' => false,
    ]);

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-09']))
        ->put(route('budget.update'), [
            'month' => '2026-09',
            'category_id' => $mortgage->id,
            'amount' => '100',
        ]);
    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-09']))
        ->put(route('budget.update'), [
            'month' => '2026-09',
            'category_id' => $groceries->id,
            'amount' => '20.5',
        ]);

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('started', false)
            ->where('copy_from.value', '2026-09')
            ->where('copy_from.label', 'September 2026'));

    $this->actingAs($user)
        ->post(route('budget.store'), [
            'month' => '2026-11',
            'copy' => false,
        ])
        ->assertSessionHasNoErrors();

    expect(CategoryBudget::query()->whereHas('budgetMonth', fn ($query) => $query->where('month', '2026-11'))->count())->toBe(0)
        ->and(CategoryBudget::query()->where('category_id', $mortgage->id)->where('amount', '100.00')->count())->toBe(1);

    $this->actingAs($user)
        ->post(route('budget.store'), [
            'month' => '2026-10',
            'copy' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('budget.index', ['month' => '2026-10']));

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('started', true)
            ->where('copy_from', null)
            ->where('budget.base_amount', '120.50')
            ->where('categories', fn ($categories) => collect($categories)->firstWhere('name', 'Mortgage')['amount'] === '100.00'
                && collect($categories)->firstWhere('name', 'Groceries')['amount'] === '20.50'
                && collect($categories)->firstWhere('name', 'Medical')['amount'] === '0.00'));

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $mortgage->id,
            'amount' => '250',
        ])
        ->assertSessionHasNoErrors();

    expect(CategoryBudget::query()->where('category_id', $mortgage->id)->orderBy('id')->get()->map->amount->all())
        ->toBe(['100.00', '250.00']);

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => '2026-09']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('budget.base_amount', '120.50')
            ->where('categories', fn ($categories) => collect($categories)->firstWhere('name', 'Mortgage')['amount'] === '100.00'));
});

test('copying is refused when the previous month has no budget and a month cannot be started twice', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->post(route('budget.store'), [
            'month' => '2026-10',
            'copy' => true,
        ])
        ->assertSessionHasErrors('copy');

    expect(BudgetMonth::query()->count())->toBe(0);

    $this->actingAs($user)
        ->post(route('budget.store'), ['month' => '2026-10', 'copy' => false])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->post(route('budget.store'), ['month' => '2026-10', 'copy' => false])
        ->assertSessionHasErrors('month');

    expect(BudgetMonth::query()->count())->toBe(1);
});

test('the planned balance is income so far minus the budget', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $salary = Category::query()->where('name', 'Salary')->first();
    $gifts = Category::query()->where('name', 'Gifts and other income')->first();
    $mortgage = Category::query()->where('name', 'Mortgage')->first();

    recordTransaction($account, $user, [
        'type' => TransactionType::Income,
        'amount' => '261434.00',
        'category' => $salary,
        'booked_on' => '2026-10-01',
    ]);
    recordTransaction($account, $user, [
        'type' => TransactionType::Income,
        'amount' => '231920.00',
        'category' => $salary,
        'booked_on' => '2026-10-02',
    ]);
    recordTransaction($account, $user, [
        'type' => TransactionType::Income,
        'amount' => '10000.00',
        'category' => $gifts,
        'booked_on' => '2026-10-03',
    ]);
    recordTransaction($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '999.00',
        'category' => $mortgage,
        'booked_on' => '2026-10-04',
    ]);
    recordTransaction($account, $user, [
        'type' => TransactionType::Income,
        'amount' => '500.00',
        'category' => $gifts,
        'booked_on' => '2026-09-30',
    ]);

    $this->actingAs($user)->post(route('budget.store'), [
        'month' => '2026-10',
        'copy' => false,
    ]);

    foreach ([
        'Mortgage' => '75700',
        'Utilities & Property Tax' => '12000',
        'Internet' => '4300',
        'Groceries' => '39649',
        'Dining Out' => '17903',
        'Debt' => '14798',
        'Beauty' => '10700',
        'Transportation' => '3500',
        'A1 (mobile provider)' => '2400',
        'Subscriptions' => '5400',
        'Personal care (Nails/Haircuts)' => '7600',
        'Shopping' => '26114',
        'Extra expenses (Gifts, miscellaneous, etc.)' => '229125',
        'Medical' => '6000',
    ] as $name => $amount) {
        $category = Category::query()->where('name', $name)->first();

        app(UpdateCategoryBudget::class)->handle(
            $user,
            CalendarMonth::fromValue('2026-10'),
            $category,
            $amount,
        );
    }

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('income.base_amount', '503354.00')
            ->where('income.formatted', '503,354.00 RSD')
            ->where('budget.base_amount', '455189.00')
            ->where('budget.formatted', '455,189.00 RSD')
            ->where('unallocated.base_amount', '48165.00')
            ->where('unallocated.formatted', '48,165.00 RSD')
            ->where('unallocated.negative', false));

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $mortgage->id,
            'amount' => '600000',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('unallocated.negative', true)
            ->where('unallocated.base_amount', '-476135.00'));
});

test('euro totals are converted from the dinar total', function () {
    $user = User::factory()->displaysIn(Currency::Eur)->create();
    $account = Account::factory()->create();
    $salary = Category::query()->where('name', 'Salary')->first();

    recordTransaction($account, $user, [
        'type' => TransactionType::Income,
        'amount' => '50.00',
        'category' => $salary,
        'booked_on' => '2026-10-01',
    ]);
    recordTransaction($account, $user, [
        'type' => TransactionType::Income,
        'amount' => '50.00',
        'category' => $salary,
        'booked_on' => '2026-10-02',
    ]);

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => '2026-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('income.base_amount', '100.00')
            ->where('income.amount', '0.85')
            ->where('income.formatted', '0.85 EUR')
            ->where('unallocated.amount', '0.85'));
});

test('an archived category stays on a month that already budgeted it', function () {
    $user = User::factory()->create();
    $beauty = Category::query()->where('name', 'Beauty')->first();
    $medical = Category::query()->where('name', 'Medical')->first();

    $this->actingAs($user)->post(route('budget.store'), [
        'month' => '2026-10',
        'copy' => false,
    ]);

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $beauty->id,
            'amount' => '40',
        ]);

    $this->actingAs($user)->patch(route('categories.archive', $beauty));
    $this->actingAs($user)->patch(route('categories.archive', $medical));

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories', function ($categories) {
                $names = collect($categories)->pluck('name');
                $beauty = collect($categories)->firstWhere('name', 'Beauty');

                return $names->contains('Beauty')
                    && $names->doesntContain('Medical')
                    && $beauty['archived'] === true
                    && $beauty['amount'] === '40.00';
            }));

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $medical->id,
            'amount' => '5',
        ])
        ->assertSessionHasErrors('category_id');

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $beauty->id,
            'amount' => '12',
        ])
        ->assertSessionHasNoErrors();

    expect($beauty->budgets()->first()->amount)->toBe('12.00');
});

test('a category with a budget cannot be deleted or change kind', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'name' => 'Dog food',
        'kind' => CategoryKind::Expense,
    ]);

    $this->actingAs($user)->post(route('budget.store'), [
        'month' => '2026-10',
        'copy' => false,
    ]);

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $category->id,
            'amount' => '15',
        ]);

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertForbidden();

    $this->actingAs($user)
        ->from(route('categories.edit', $category))
        ->patch(route('categories.update', $category), [
            'name' => 'Dog food',
            'kind' => 'income',
            'description' => 'Vet snacks',
        ])
        ->assertSessionHasErrors('kind');

    expect($category->fresh())->not->toBeNull()
        ->and($category->refresh()->kind)->toBe(CategoryKind::Expense);

    $this->actingAs($user)
        ->get(route('categories.edit', $category))
        ->assertInertia(fn (Assert $page) => $page
            ->where('category.can_delete', false)
            ->where('category.kind_locked', true));
});

test('another member sees the same budget and is recorded when they change it', function () {
    $ada = User::factory()->create(['name' => 'Ada']);
    $bea = User::factory()->create(['name' => 'Bea']);
    $mortgage = Category::query()->where('name', 'Mortgage')->first();

    $this->actingAs($ada)->post(route('budget.store'), [
        'month' => '2026-10',
        'copy' => false,
    ]);

    $this->actingAs($ada)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $mortgage->id,
            'amount' => '10',
        ]);

    $this->travel(5)->seconds();

    $this->actingAs($bea)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $mortgage->id,
            'amount' => '30',
        ])
        ->assertSessionHasNoErrors();

    $line = CategoryBudget::query()->where('category_id', $mortgage->id)->first();

    expect($line->created_by)->toBe($ada->id)
        ->and($line->updated_by)->toBe($bea->id);

    $this->actingAs($bea)
        ->get(route('budget.index', ['month' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories', fn ($categories) => collect($categories)->firstWhere('name', 'Mortgage')['amount'] === '30.00'
                && collect($categories)->firstWhere('name', 'Mortgage')['updated_by'] === 'Bea'));
});

test('a budget from another workspace is not visible or editable', function () {
    $user = User::factory()->create();
    $otherWorkspace = Workspace::factory()->create(['name' => 'Elsewhere']);
    $other = User::factory()->create(['workspace_id' => $otherWorkspace->id]);
    $otherCategory = Category::query()->whereBelongsTo($otherWorkspace)->where('name', 'Mortgage')->first();

    $this->actingAs($other)->post(route('budget.store'), [
        'month' => '2026-10',
        'copy' => false,
    ]);

    $this->actingAs($other)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $otherCategory->id,
            'amount' => '80',
        ]);

    $this->actingAs($user)
        ->get(route('budget.index', ['month' => '2026-10']))
        ->assertInertia(fn (Assert $page) => $page->where('started', false));

    $this->actingAs($user)
        ->from(route('budget.index', ['month' => '2026-10']))
        ->put(route('budget.update'), [
            'month' => '2026-10',
            'category_id' => $otherCategory->id,
            'amount' => '5',
        ])
        ->assertSessionHasErrors('category_id');

    expect($otherCategory->budgets()->first()->amount)->toBe('80.00');
});
