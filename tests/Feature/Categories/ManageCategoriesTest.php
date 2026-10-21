<?php

use App\Actions\Categories\SeedDefaultCategories;
use App\Enums\CategoryKind;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Support\DefaultCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests cannot view or change categories', function () {
    $category = Category::factory()->create();

    $this->get(route('categories.index'))->assertRedirect(route('login'));
    $this->get(route('categories.create'))->assertRedirect(route('login'));
    $this->get(route('categories.edit', $category))->assertRedirect(route('login'));
    $this->post(route('categories.store'), categoryPayload())->assertRedirect(route('login'));
    $this->patch(route('categories.update', $category), categoryPayload())->assertRedirect(route('login'));
    $this->delete(route('categories.destroy', $category))->assertRedirect(route('login'));
});

test('unverified users cannot view or change categories', function () {
    $user = User::factory()->unverified()->create();
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->post(route('categories.store'), categoryPayload())
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->patch(route('categories.update', $category), categoryPayload())
        ->assertRedirect(route('verification.notice'));
});

test('a new workspace starts with the spreadsheet categories', function () {
    $workspace = Workspace::factory()->create();

    $categories = Category::query()->whereBelongsTo($workspace)->ordered()->get();
    $income = $categories->where('kind', CategoryKind::Income)->values();
    $expenses = $categories->where('kind', CategoryKind::Expense)->values();

    expect($income->pluck('name')->all())->toBe(array_column(DefaultCategories::forKind(CategoryKind::Income), 'name'))
        ->and($expenses->pluck('name')->all())->toBe(array_column(DefaultCategories::forKind(CategoryKind::Expense), 'name'))
        ->and($expenses->firstWhere('name', 'A1 (mobile provider)')->description)->toBe('Mobile provider')
        ->and($expenses->firstWhere('name', 'Groceries')->icon)->toBe('shopping-cart')
        ->and($income->firstWhere('name', 'Salary')->icon)->toBe('briefcase')
        ->and($income->first()->position)->toBe(1)
        ->and($expenses->last()->position)->toBe(14)
        ->and($categories->every(fn (Category $category): bool => $category->archived_at === null))->toBeTrue();

    app(SeedDefaultCategories::class)->handle($workspace);

    expect(Category::query()->whereBelongsTo($workspace)->count())->toBe(17);
});

test('the category list shows descriptions and the label used when picking one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.0.kind', 'income')
            ->where('groups.0.label', 'Income')
            ->where('groups.0.categories.0.name', 'Salary')
            ->where('groups.0.categories.1.name', 'Bonus')
            ->where('groups.0.categories.2.name', 'Gifts and other income')
            ->where('groups.0.archived', [])
            ->where('groups.1.kind', 'expense')
            ->where('groups.1.label', 'Expenses')
            ->where('groups.1.categories', fn ($categories) => count($categories) === 14)
            ->where('groups.1.categories.8.name', 'A1 (mobile provider)')
            ->where('groups.1.categories.8.description', 'Mobile provider')
            ->where('groups.1.categories.8.label', 'A1 (mobile provider): Mobile provider')
            ->where('groups.1.categories.8.icon', 'smartphone')
            ->where('groups.0.categories.0.icon', 'briefcase')
            ->where('groups.1.categories.13.name', 'Medical')
            ->where('groups.1.archived', []));
});

test('default categories can be renamed straight away', function () {
    $user = User::factory()->create(['name' => 'Ada']);
    $mortgage = Category::query()->where('name', 'Mortgage')->first();

    expect($mortgage)->not->toBeNull();

    $this->travel(5)->seconds();

    $this->actingAs($user)
        ->patch(route('categories.update', $mortgage), categoryPayload([
            'name' => 'Home loan',
            'kind' => 'expense',
            'description' => 'The flat',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('categories.edit', $mortgage));

    $mortgage->refresh();

    expect($mortgage->name)->toBe('Home loan')
        ->and($mortgage->description)->toBe('The flat')
        ->and($mortgage->kind)->toBe(CategoryKind::Expense)
        ->and($mortgage->updated_by)->toBe($user->id);

    $this->actingAs($user)
        ->get(route('categories.edit', $mortgage))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('category.name', 'Home loan')
            ->where('category.description', 'The flat')
            ->where('category.label', 'Home loan: The flat')
            ->where('category.updated_by', 'Ada')
            ->where('category.kind_locked', false)
            ->where('category.can_delete', true));
});

test('a member can choose an icon and clear it later', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), categoryPayload(['icon' => 'dog']))
        ->assertSessionHasNoErrors();

    $category = Category::query()->where('name', 'Dog food')->first();

    expect($category)->not->toBeNull()
        ->and($category->icon)->toBe('dog');

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.1.categories', fn ($categories) => collect($categories)->firstWhere('name', 'Dog food')['icon'] === 'dog'));

    $this->actingAs($user)
        ->patch(route('categories.update', $category), categoryPayload(['icon' => '']))
        ->assertSessionHasNoErrors();

    expect($category->refresh()->icon)->toBeNull();
});

test('a member can add a category at the end of its kind', function () {
    $user = User::factory()->create(['name' => 'Ada']);

    $this->actingAs($user)
        ->post(route('categories.store'), categoryPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('categories.index'));

    $category = Category::query()->where('name', 'Dog food')->first();
    $lastExpense = Category::query()
        ->where('kind', CategoryKind::Expense)
        ->selectable()
        ->ordered()
        ->pluck('name')
        ->last();

    expect($category)->not->toBeNull()
        ->and($category->kind)->toBe(CategoryKind::Expense)
        ->and($category->description)->toBe('Vet snacks')
        ->and($category->created_by)->toBe($user->id)
        ->and($category->workspace_id)->toBe($user->workspace_id)
        ->and($category->archived_at)->toBeNull()
        ->and($lastExpense)->toBe('Dog food');
});

test('a blank description is stored as empty and a name is trimmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), categoryPayload([
            'name' => '  Pet care  ',
            'description' => '   ',
        ]))
        ->assertSessionHasNoErrors();

    $category = Category::query()->where('name', 'Pet care')->first();

    expect($category)->not->toBeNull()
        ->and($category->description)->toBeNull()
        ->and($category->choiceLabel())->toBe('Pet care');
});

test('category details are validated', function (array $overrides, string $field) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('categories.create'))
        ->post(route('categories.store'), categoryPayload($overrides))
        ->assertRedirect(route('categories.create'))
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'unknown kind' => [['kind' => 'transfer'], 'kind'],
    'long name' => [['name' => str_repeat('a', 101)], 'name'],
    'long description' => [['description' => str_repeat('a', 256)], 'description'],
    'duplicate name in the same kind' => [['name' => 'Groceries', 'kind' => 'expense'], 'name'],
    'unknown icon' => [['icon' => 'not-an-icon'], 'icon'],
]);

test('the same name can exist once as income and once as expense', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), categoryPayload([
            'name' => 'Salary',
            'kind' => 'expense',
            'description' => null,
        ]))
        ->assertSessionHasNoErrors();

    expect(Category::query()->where('name', 'Salary')->count())->toBe(2);
});

test('changing kind moves the category to the end of the other list', function () {
    $user = User::factory()->create();
    $bonus = Category::query()->where('name', 'Bonus')->first();

    expect($bonus)->not->toBeNull();

    $this->actingAs($user)
        ->patch(route('categories.update', $bonus), categoryPayload([
            'name' => 'Bonus',
            'kind' => 'expense',
            'description' => '',
        ]))
        ->assertSessionHasNoErrors();

    $bonus->refresh();

    expect($bonus->kind)->toBe(CategoryKind::Expense)
        ->and($bonus->description)->toBeNull()
        ->and(Category::query()->where('kind', CategoryKind::Income)->ordered()->pluck('name')->all())
        ->toBe(['Salary', 'Gifts and other income'])
        ->and(Category::query()->where('kind', CategoryKind::Expense)->selectable()->ordered()->pluck('name')->last())
        ->toBe('Bonus');
});

test('archiving hides a category from picking and keeps it in the history', function () {
    $user = User::factory()->create();
    $mobile = Category::query()->where('name', 'A1 (mobile provider)')->first();

    expect($mobile)->not->toBeNull();

    $this->actingAs($user)
        ->patch(route('categories.archive', $mobile))
        ->assertRedirect(route('categories.edit', $mobile));

    expect(Category::query()->selectable()->whereKey($mobile->id)->exists())->toBeFalse()
        ->and($mobile->refresh()->archived_at)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.1.categories', fn ($categories) => collect($categories)->pluck('name')->doesntContain('A1 (mobile provider)'))
            ->where('groups.1.archived.0.name', 'A1 (mobile provider)')
            ->where('groups.1.archived.0.description', 'Mobile provider')
            ->where('groups.1.archived.0.label', 'A1 (mobile provider): Mobile provider')
            ->where('groups.1.archived.0.archived', true));

    $this->actingAs($user)
        ->patch(route('categories.restore', $mobile))
        ->assertRedirect(route('categories.edit', $mobile));

    expect($mobile->refresh()->archived_at)->toBeNull()
        ->and(Category::query()->selectable()->whereKey($mobile->id)->exists())->toBeTrue()
        ->and(Category::query()->where('kind', CategoryKind::Expense)->selectable()->ordered()->pluck('name')->last())
        ->toBe('A1 (mobile provider)');
});

test('a category with transactions cannot be deleted or change kind', function () {
    $user = User::factory()->create(['name' => 'Ada']);
    $editor = User::factory()->create(['name' => 'Bea']);
    $category = Category::factory()->create([
        'name' => 'Used',
        'kind' => CategoryKind::Expense,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    $account = Account::factory()->create();

    Transaction::factory()->create([
        'workspace_id' => $category->workspace_id,
        'account_id' => $account->id,
        'category_id' => $category->id,
    ]);

    $this->travel(5)->seconds();

    $this->actingAs($editor)
        ->delete(route('categories.destroy', $category))
        ->assertForbidden();

    expect($category->fresh())->not->toBeNull()
        ->and(Transaction::query()->where('category_id', $category->id)->exists())->toBeTrue();

    $this->actingAs($editor)
        ->from(route('categories.edit', $category))
        ->patch(route('categories.update', $category), categoryPayload([
            'name' => 'Used',
            'kind' => 'income',
        ]))
        ->assertRedirect(route('categories.edit', $category))
        ->assertSessionHasErrors('kind');

    expect($category->refresh()->kind)->toBe(CategoryKind::Expense);

    $this->actingAs($editor)
        ->patch(route('categories.update', $category), categoryPayload([
            'name' => 'Used more',
            'kind' => 'expense',
            'description' => 'Still an expense',
        ]))
        ->assertSessionHasNoErrors();

    $this->actingAs($editor)
        ->get(route('categories.edit', $category))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('category.name', 'Used more')
            ->where('category.kind_locked', true)
            ->where('category.can_delete', false)
            ->where('category.updated_by', 'Bea'));

    $this->actingAs($editor)
        ->patch(route('categories.archive', $category))
        ->assertRedirect(route('categories.edit', $category));

    expect(Category::query()->selectable()->whereKey($category->id)->exists())->toBeFalse()
        ->and(Transaction::query()->where('category_id', $category->id)->exists())->toBeTrue();
});

test('a category without transactions can be deleted', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Temporary']);

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    expect(Category::query()->whereKey($category->id)->exists())->toBeFalse()
        ->and(Category::query()->where('name', 'Mortgage')->exists())->toBeTrue();
});

test('another member sees the same categories and an outsider does not', function () {
    $member = User::factory()->create();
    $other = User::factory()->create();
    Category::factory()->create([
        'workspace_id' => $member->workspace_id,
        'name' => 'Dog food',
        'position' => 50,
    ]);
    $salary = Category::query()->whereBelongsTo($member->workspace)->where('name', 'Salary')->first();
    $elsewhere = Workspace::factory()->create(['name' => 'Elsewhere']);
    $outsider = User::factory()->create(['workspace_id' => $elsewhere->id]);

    expect($salary)->not->toBeNull();

    $this->actingAs($other)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.1.categories', fn ($categories) => collect($categories)->pluck('name')->contains('Dog food')));

    $this->actingAs($outsider)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups', fn ($groups) => collect($groups)
                ->flatMap(fn ($group) => [...$group['categories'], ...$group['archived']])
                ->pluck('name')
                ->doesntContain('Dog food')));

    $this->actingAs($outsider)
        ->get(route('categories.edit', $salary))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->patch(route('categories.update', $salary), categoryPayload([
            'name' => 'Stolen',
            'kind' => 'income',
        ]))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->delete(route('categories.destroy', $salary))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->patch(route('categories.archive', $salary))
        ->assertForbidden();

    expect($salary->refresh()->name)->toBe('Salary');
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function categoryPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Dog food',
        'kind' => 'expense',
        'description' => 'Vet snacks',
    ], $overrides);
}
