<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    private const COLORS = [
        '#3b82f6', '#22c55e', '#eab308', '#ef4444', '#8b5cf6',
        '#ec4899', '#06b6d4', '#f97316', '#84cc16', '#6366f1',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $incomeNames = ['Salary', 'Freelance', 'Investments', 'Gifts', 'Refunds'];
        $expenseNames = ['Food & Groceries', 'Transport', 'Utilities', 'Shopping', 'Entertainment', 'Health', 'Dining Out', 'Subscriptions'];

        foreach ($incomeNames as $name) {
            Category::factory()->income()->shared()->create([
                'name' => $name,
                'color' => fake()->randomElement(self::COLORS),
            ]);
        }

        foreach ($expenseNames as $name) {
            Category::factory()->expense()->shared()->create([
                'name' => $name,
                'color' => fake()->randomElement(self::COLORS),
            ]);
        }
    }
}
