<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return $this->sharesWorkspace($user, $category);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Category $category): bool
    {
        return $this->sharesWorkspace($user, $category);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->sharesWorkspace($user, $category)
            && ! $category->isInUse();
    }

    private function sharesWorkspace(User $user, Category $category): bool
    {
        return $user->workspace_id === $category->workspace_id;
    }
}
