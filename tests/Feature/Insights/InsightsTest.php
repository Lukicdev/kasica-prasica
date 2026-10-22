<?php

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests cannot view insights', function () {
    $this->get(route('insights.index'))->assertRedirect(route('login'));
});

test('unverified users cannot view insights', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertRedirect(route('verification.notice'));
});

test('two months show spent per category and the change', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();
    $salary = Category::query()->where('name', 'Salary')->firstOrFail();

    recordTransaction($account, $user, [
        'category' => $groceries,
        'amount' => '100.00',
        'booked_on' => '2026-09-15',
    ]);
    recordTransaction($account, $user, [
        'category' => $groceries,
        'amount' => '40.00',
        'booked_on' => '2026-10-02',
    ]);
    recordTransaction($account, $user, [
        'type' => TransactionType::Income,
        'category' => $salary,
        'amount' => '500.00',
        'booked_on' => '2026-10-03',
    ]);
    Transaction::factory()->create([
        'workspace_id' => $user->workspace_id,
        'account_id' => $account->id,
        'type' => TransactionType::Transfer,
        'amount' => '999.00',
        'account_amount' => '999.00',
        'base_amount' => '999.00',
        'booked_on' => '2026-10-04',
    ]);

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('insights/index')
            ->where('comparison.left.value', '2026-09')
            ->where('comparison.left.label', 'September 2026')
            ->where('comparison.right.value', '2026-10')
            ->where('comparison.right.label', 'October 2026')
            ->where('comparison.rows', function ($rows): bool {
                $groceries = collect($rows)->firstWhere('name', 'Groceries');
                $mortgage = collect($rows)->firstWhere('name', 'Mortgage');

                return is_array($groceries)
                    && is_array($mortgage)
                    && $groceries['left']['base_amount'] === '100.00'
                    && $groceries['right']['base_amount'] === '40.00'
                    && $groceries['change']['base_amount'] === '-60.00'
                    && $groceries['direction'] === 'down'
                    && $groceries['direction_label'] === 'Spent less'
                    && $mortgage['left']['base_amount'] === '0.00'
                    && $mortgage['right']['base_amount'] === '0.00'
                    && $mortgage['direction'] === 'same'
                    && $mortgage['direction_label'] === 'No change';
            })
            ->where('comparison.total.left.base_amount', '100.00')
            ->where('comparison.total.right.base_amount', '40.00')
            ->where('comparison.total.change.base_amount', '-60.00')
            ->where('comparison.total.direction', 'down')
            ->where('trends.through.value', '2026-10')
            ->where('trends.months', fn ($months): bool => count($months) === 12));
});

test('trends cover the last twelve months', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();
    $salary = Category::query()->where('name', 'Salary')->firstOrFail();

    recordTransaction($account, $user, [
        'type' => TransactionType::Income,
        'category' => $salary,
        'amount' => '200.00',
        'booked_on' => '2026-05-02',
    ]);
    recordTransaction($account, $user, [
        'category' => $groceries,
        'amount' => '30.00',
        'booked_on' => '2026-10-02',
    ]);
    recordTransaction($account, $user, [
        'category' => $groceries,
        'amount' => '10.00',
        'booked_on' => '2025-11-15',
    ]);

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('trends.months', function ($months): bool {
                $values = collect($months)->pluck('value')->all();
                $may = collect($months)->firstWhere('value', '2026-05');
                $october = collect($months)->firstWhere('value', '2026-10');
                $november = collect($months)->firstWhere('value', '2025-11');

                return $values === [
                    '2025-11', '2025-12', '2026-01', '2026-02', '2026-03', '2026-04',
                    '2026-05', '2026-06', '2026-07', '2026-08', '2026-09', '2026-10',
                ]
                    && is_array($november)
                    && $november['spent']['base_amount'] === '10.00'
                    && $months[0]['short_label'] === 'Nov 2025'
                    && is_array($may)
                    && $may['income']['base_amount'] === '200.00'
                    && $may['spent']['base_amount'] === '0.00'
                    && is_array($october)
                    && $october['income']['base_amount'] === '0.00'
                    && $october['spent']['base_amount'] === '30.00';
            }));
});

test('comparison figures follow the display currency', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->displaysIn('EUR')->create();
    $account = Account::factory()->create();
    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();

    recordTransaction($account, $user, [
        'category' => $groceries,
        'amount' => '10.00',
        'currency' => 'EUR',
        'booked_on' => '2026-10-02',
    ]);
    recordTransaction($account, $user, [
        'category' => $groceries,
        'amount' => '100.00',
        'booked_on' => '2026-10-03',
    ]);
    recordTransaction($account, $user, [
        'category' => $groceries,
        'amount' => '-20.00',
        'booked_on' => '2026-10-04',
    ]);

    $this->actingAs($user)
        ->get(route('insights.index', ['right' => '2026-10', 'left' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('comparison.total.right.formatted', '10.68 EUR')
            ->where('comparison.total.right.original_formatted', '1,255.00 RSD'));
});
