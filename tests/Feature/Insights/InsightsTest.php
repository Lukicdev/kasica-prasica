<?php

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
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
            ->missing('comparison')
            ->where('trends.through.value', '2026-10')
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

test('net worth is accounts plus assets at each month', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->displaysIn('EUR')->create();
    $cash = Account::factory()->create([
        'opening_balance' => '1000.00',
        'opening_balance_on' => '2026-05-01',
    ]);
    Account::factory()->archived()->create([
        'opening_balance' => '50.00',
        'opening_balance_on' => '2026-01-01',
    ]);
    Account::factory()->inCurrency('EUR')->create([
        'opening_balance' => '10.00',
        'opening_balance_on' => '2026-09-01',
    ]);
    $pocket = Account::factory()->create([
        'opening_balance' => '0.00',
        'opening_balance_on' => '2026-06-01',
    ]);
    Account::factory()->create([
        'workspace_id' => Workspace::factory()->create()->id,
        'opening_balance' => '50000.00',
        'opening_balance_on' => '2020-01-01',
    ]);

    $salary = Category::query()->where('name', 'Salary')->firstOrFail();
    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();

    recordTransaction($cash, $user, [
        'type' => TransactionType::Income,
        'category' => $salary,
        'amount' => '200.00',
        'booked_on' => '2026-05-02',
    ]);
    recordTransaction($cash, $user, [
        'category' => $groceries,
        'amount' => '30.00',
        'booked_on' => '2026-10-02',
    ]);
    recordTransaction($cash, $user, [
        'category' => $groceries,
        'amount' => '999.00',
        'booked_on' => '2026-10-21',
    ]);
    Transaction::factory()->create([
        'workspace_id' => $user->workspace_id,
        'account_id' => $cash->id,
        'destination_account_id' => $pocket->id,
        'type' => TransactionType::Transfer,
        'amount' => '100.00',
        'account_amount' => '100.00',
        'destination_amount' => '100.00',
        'base_amount' => '100.00',
        'booked_on' => '2026-06-15',
    ]);
    Asset::factory()->create([
        'value' => '5000.00',
        'valued_on' => '2026-08-15',
    ]);
    Asset::factory()->create([
        'value' => '99999.00',
        'valued_on' => '2026-11-01',
    ]);

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('net_worth.through.value', '2026-10')
            ->where('net_worth.previous.month', 'September 2026')
            ->where('net_worth.previous.day', 20)
            ->where('net_worth.previous.balance.base_amount', '7425.00')
            ->where('net_worth.previous.balance.formatted', '63.19 EUR')
            ->where('net_worth.previous.balance.original_formatted', '7,425.00 RSD')
            ->where('net_worth.previous.percent', 0)
            ->where('net_worth.months', function ($months): bool {
                $amounts = collect($months)->mapWithKeys(fn ($month) => [
                    $month['value'] => $month['net_worth']['base_amount'],
                ]);
                $october = collect($months)->firstWhere('value', '2026-10');

                return count($months) === 12
                    && $months[0]['short_label'] === 'Nov 2025'
                    && $amounts->only([
                        '2025-11', '2025-12', '2026-01', '2026-04', '2026-05',
                        '2026-06', '2026-07', '2026-08', '2026-09', '2026-10',
                    ])->all() === [
                        '2025-11' => '0.00',
                        '2025-12' => '0.00',
                        '2026-01' => '50.00',
                        '2026-04' => '50.00',
                        '2026-05' => '1250.00',
                        '2026-06' => '1250.00',
                        '2026-07' => '1250.00',
                        '2026-08' => '6250.00',
                        '2026-09' => '7425.00',
                        '2026-10' => '7395.00',
                    ]
                    && is_array($october)
                    && $october['net_worth']['formatted'] === '62.94 EUR'
                    && $october['net_worth']['original_formatted'] === '7,395.00 RSD';
            }));
});

test('trend figures follow the display currency', function () {
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
        ->get(route('insights.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('trends.months', function ($months): bool {
                $october = collect($months)->firstWhere('value', '2026-10');

                return is_array($october)
                    && $october['spent']['formatted'] === '10.68 EUR'
                    && $october['spent']['original_formatted'] === '1,255.00 RSD';
            }));
});
