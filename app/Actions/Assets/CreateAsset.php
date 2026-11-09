<?php

namespace App\Actions\Assets;

use App\Enums\AssetType;
use App\Enums\Valuation;
use App\Models\Asset;
use App\Models\User;

class CreateAsset
{
    /**
     * @param  array{
     *     name: string,
     *     icon: string|null,
     *     type: AssetType,
     *     currency: string,
     *     value: string,
     *     valued_on: string,
     *     note: string|null
     * }  $attributes
     */
    public function handle(User $actor, array $attributes): Asset
    {
        return Asset::query()->create([
            'workspace_id' => $actor->workspace_id,
            'name' => $attributes['name'],
            'icon' => $attributes['icon'],
            'type' => $attributes['type'],
            'currency' => $attributes['currency'],
            'value' => $attributes['value'],
            'valued_on' => $attributes['valued_on'],
            'valuation' => Valuation::Manual,
            'note' => $attributes['note'],
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }
}
