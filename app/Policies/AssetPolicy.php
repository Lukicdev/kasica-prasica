<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->sharesWorkspace($user, $asset);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Asset $asset): bool
    {
        return $this->sharesWorkspace($user, $asset);
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $this->sharesWorkspace($user, $asset);
    }

    private function sharesWorkspace(User $user, Asset $asset): bool
    {
        return $user->workspace_id === $asset->workspace_id;
    }
}
