<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\Currency;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
final class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(AccountType::cases()),
            'currency' => Currency::EUR,
            'initial_balance' => 0,
            'is_shared' => false,
        ];
    }

    /**
     * Indicate that the account is shared.
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_shared' => true,
        ]);
    }

    /**
     * Indicate that the account is a cash account.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Cash,
        ]);
    }

    /**
     * Indicate that the account is a bank account.
     */
    public function bank(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Bank,
        ]);
    }

    /**
     * Indicate that the account has a specific initial balance.
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'initial_balance' => $balance,
        ]);
    }
}
