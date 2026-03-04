<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

final class AccountSeeder extends Seeder
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

        $definitions = [
            ['name' => 'Cash Wallet', 'type' => AccountType::Cash],
            ['name' => 'Main Bank', 'type' => AccountType::Bank],
            ['name' => 'Savings', 'type' => AccountType::Bank],
        ];

        foreach ($definitions as $def) {
            Account::factory()->create([
                'user_id' => $user->id,
                'name' => $def['name'],
                'type' => $def['type'],
                'balance' => 0,
            ]);
        }
    }
}
