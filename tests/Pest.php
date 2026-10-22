<?php

use App\Actions\Transactions\CreateTransaction;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @param  array<string, mixed>  $overrides
 */
function recordTransaction(Account $account, User $user, array $overrides = []): Transaction
{
    $currency = $overrides['currency'] ?? $account->currency;

    return app(CreateTransaction::class)->handle($account, $user, array_merge([
        'type' => TransactionType::Expense,
        'amount' => '10.00',
        'currency' => $currency,
        'booked_on' => '2026-10-02',
        'note' => null,
    ], $overrides));
}

function recordExpense(
    Account $account,
    User $user,
    string $amount,
    string $bookedOn = '2026-10-02',
    ?string $note = null,
    string $currency = 'RSD',
): Transaction {
    return recordTransaction($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => $amount,
        'currency' => $currency,
        'booked_on' => $bookedOn,
        'note' => $note,
    ]);
}
