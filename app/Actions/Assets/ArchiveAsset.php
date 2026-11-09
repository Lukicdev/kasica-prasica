<?php

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Models\User;

class ArchiveAsset
{
    public function handle(Asset $asset, User $actor): Asset
    {
        if ($asset->archived_at !== null) {
            return $asset;
        }

        $asset->update([
            'archived_at' => now(),
            'updated_by' => $actor->id,
        ]);

        return $asset;
    }
}
