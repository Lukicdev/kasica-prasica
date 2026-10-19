<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\Currency;
use App\Models\Account;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => fn (): int => Workspace::shared()->id,
            'name' => fake()->words(2, true),
            'type' => AccountType::Cash,
            'currency' => Currency::Rsd,
            'opening_balance' => '0.00',
            'opening_balance_on' => '2026-10-01',
            'note' => null,
            'archived_at' => null,
            'is_imported_history' => false,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'archived_at' => now(),
        ]);
    }

    public function importedHistory(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Imported history',
            'is_imported_history' => true,
        ]);
    }

    public function inCurrency(Currency $currency): static
    {
        return $this->state(fn (array $attributes): array => [
            'currency' => $currency,
        ]);
    }
}
