<?php

namespace App\Actions\Assets;

use App\Models\Asset;
use App\Models\User;

class DeleteAsset
{
    public function handle(Asset $asset, User $actor): void
    {
        $asset->delete();
    }
}
