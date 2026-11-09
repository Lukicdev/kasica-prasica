<?php

namespace App\Actions\Assets;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\User;

class UpdateAsset
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
    public function handle(Asset $asset, User $actor, array $attributes): Asset
    {
        $asset->update([
            'name' => $attributes['name'],
            'icon' => $attributes['icon'],
            'type' => $attributes['type'],
            'currency' => $attributes['currency'],
            'value' => $attributes['value'],
            'valued_on' => $attributes['valued_on'],
            'note' => $attributes['note'],
            'updated_by' => $actor->id,
        ]);

        return $asset;
    }
}
