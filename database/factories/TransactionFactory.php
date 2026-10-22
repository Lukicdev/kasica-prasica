<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => fn (): int => Workspace::shared()->id,
            'account_id' => Account::factory(),
            'category_id' => null,
            'person_id' => User::factory(),
            'type' => TransactionType::Expense,
            'amount' => '10.00',
            'currency' => 'RSD',
            'account_amount' => '10.00',
            'base_amount' => '10.00',
            'exchange_rate' => '1',
            'booked_on' => '2026-10-02',
            'note' => null,
            'repeats' => false,
            'recurrence_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function repeating(): static
    {
        return $this->state(fn (): array => [
            'repeats' => true,
        ]);
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TransactionType::Income,
        ]);
    }
}
