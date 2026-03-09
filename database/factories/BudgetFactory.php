<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Budget>
 */
final class BudgetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Budget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(2024, 2026);
        $month = fake()->numberBetween(1, 12);
        $period = sprintf('%04d-%02d-01', $year, $month);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->expense(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'period' => $period,
        ];
    }
}
