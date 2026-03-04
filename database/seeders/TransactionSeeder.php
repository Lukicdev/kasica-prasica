<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

final class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->where('email', 'test@example.com')->first();

        if (! $user) {
            return;
        }

        $accounts = $user->accounts()->get();
        $incomeCategories = Category::query()->where('type', CategoryType::Income)->get();
        $expenseCategories = Category::query()->where('type', CategoryType::Expense)->get();

        if ($accounts->isEmpty() || $incomeCategories->isEmpty() || $expenseCategories->isEmpty()) {
            return;
        }

        $incomeDescriptions = ['Monthly salary', 'Project payment', 'Dividends', 'Birthday gift', 'Store refund'];
        $expenseDescriptions = ['Supermarket', 'Bus ticket', 'Electricity bill', 'New shoes', 'Cinema', 'Pharmacy', 'Restaurant', 'Streaming'];

        foreach (range(1, 25) as $i) {
            Transaction::factory()->income()->create([
                'user_id' => $user->id,
                'account_id' => $accounts->random()->id,
                'category_id' => $incomeCategories->random()->id,
                'amount' => fake()->randomFloat(2, 50, 3000),
                'description' => fake()->randomElement($incomeDescriptions),
                'transaction_date' => fake()->dateTimeBetween('-11 months', 'now'),
            ]);
        }

        foreach (range(1, 55) as $i) {
            Transaction::factory()->expense()->create([
                'user_id' => $user->id,
                'account_id' => $accounts->random()->id,
                'category_id' => $expenseCategories->random()->id,
                'amount' => fake()->randomFloat(2, 2, 250),
                'description' => fake()->randomElement($expenseDescriptions),
                'transaction_date' => fake()->dateTimeBetween('-11 months', 'now'),
            ]);
        }

        $this->recalculateAccountBalances();
    }

    private function recalculateAccountBalances(): void
    {
        Account::query()->each(function (Account $account): void {
            $balance = Transaction::query()
                ->where('account_id', $account->id)
                ->get()
                ->sum(function (Transaction $t): float {
                    $amount = (float) $t->amount;

                    return match ($t->type) {
                        TransactionType::Income => $amount,
                        TransactionType::Expense => -$amount,
                        TransactionType::Transfer => $t->related_transaction_id ? $amount : -$amount,
                    };
                });
            $account->update(['balance' => round($balance, 2)]);
        });
    }
}
