<?php

namespace Database\Factories;

use App\Models\BudgetMonth;
use App\Models\Category;
use App\Models\CategoryBudget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryBudget>
 */
class CategoryBudgetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_month_id' => BudgetMonth::factory(),
            'category_id' => Category::factory(),
            'amount' => '0.00',
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
