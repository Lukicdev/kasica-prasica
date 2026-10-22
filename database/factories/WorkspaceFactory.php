<?php

namespace Database\Factories;

use App\Models\ExchangeRateQuote;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Workspace::DEFAULT_NAME,
            'base_currency' => 'EUR',
        ];
    }

    /**
     * The sample household: dinars as the base, with euros enabled.
     *
     * A quote of 117.5 dinars per euro keeps conversion stable until Frankfurter
     * publishes a newer rate for that date.
     */
    public function household(): static
    {
        return $this->state(fn (): array => [
            'base_currency' => 'RSD',
        ])->afterCreating(function (Workspace $workspace): void {
            ExchangeRateQuote::query()->firstOrCreate(
                ['quoted_on' => '2020-01-01', 'currency' => 'RSD'],
                ['per_euro' => '117.5'],
            );

            $workspace->currencies()->firstOrCreate(
                ['code' => 'EUR'],
                ['manual_rate' => null],
            );
        });
    }
}
