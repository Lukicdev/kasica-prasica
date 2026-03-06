<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class ListCategoriesAction
{
    /**
     * Get categories for the user (user's categories and global ones).
     *
     * @return Collection<int, Category>
     */
    public function handle(User $user): Collection
    {
        return Category::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->latest()
            ->get();
    }
}
