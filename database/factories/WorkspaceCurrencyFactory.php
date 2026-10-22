<?php

namespace Database\Factories;

use App\Models\Workspace;
use App\Models\WorkspaceCurrency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceCurrency>
 */
class WorkspaceCurrencyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'code' => 'USD',
            'manual_rate' => null,
        ];
    }
}
