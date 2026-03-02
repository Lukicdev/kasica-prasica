<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
final class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Category::class;

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
            'type' => fake()->randomElement(CategoryType::cases()),
            'color' => fake()->hexColor(),
        ];
    }

    /**
     * Indicate that the category is shared (no user).
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the category is for income.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CategoryType::Income,
        ]);
    }

    /**
     * Indicate that the category is for expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CategoryType::Expense,
        ]);
    }
}
