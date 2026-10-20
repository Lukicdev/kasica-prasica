<?php

namespace Database\Factories;

use App\Models\BudgetMonth;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetMonth>
 */
class BudgetMonthFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => fn (): int => Workspace::shared()->id,
            'month' => '2026-10',
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
