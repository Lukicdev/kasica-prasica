<?php

namespace Database\Factories;

use App\Enums\CategoryKind;
use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => fn (): int => Workspace::shared()->id,
            'name' => fake()->unique()->bothify('Category ????'),
            'kind' => CategoryKind::Expense,
            'description' => null,
            'position' => 100_000,
            'archived_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(fn (): array => [
            'kind' => CategoryKind::Income,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'archived_at' => now(),
        ]);
    }
}
