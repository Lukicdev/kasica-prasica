<?php

namespace Database\Factories;

use App\Enums\AssetType;
use App\Enums\Valuation;
use App\Models\Asset;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => fn (): int => Workspace::shared()->id,
            'name' => fake()->words(2, true),
            'icon' => null,
            'type' => AssetType::Vehicle,
            'currency' => 'RSD',
            'value' => '0.00',
            'valued_on' => '2026-10-01',
            'valuation' => Valuation::Manual,
            'note' => null,
            'archived_at' => null,
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

    public function inCurrency(string $currency): static
    {
        return $this->state(fn (array $attributes): array => [
            'currency' => $currency,
        ]);
    }
}
